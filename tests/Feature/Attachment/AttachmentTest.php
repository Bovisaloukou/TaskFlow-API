<?php

namespace Tests\Feature\Attachment;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    private function createTaskForAdmin($admin): Task
    {
        $project = Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Test Project',
            'created_by' => $admin->id,
        ]);

        return Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Test Task',
            'created_by' => $admin->id,
        ]);
    }

    public function test_can_upload_attachment(): void
    {
        Storage::fake('local');
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.file_name', 'document.pdf');
    }

    public function test_can_list_attachments(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        TaskAttachment::create([
            'organization_id' => $admin->organization_id,
            'task_id' => $task->id,
            'uploaded_by' => $admin->id,
            'file_name' => 'test.pdf',
            'file_path' => 'attachments/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}/attachments");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_delete_attachment(): void
    {
        Storage::fake('local');
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        Storage::disk('local')->put('attachments/test.pdf', 'content');

        $attachment = TaskAttachment::create([
            'organization_id' => $admin->organization_id,
            'task_id' => $task->id,
            'uploaded_by' => $admin->id,
            'file_name' => 'test.pdf',
            'file_path' => 'attachments/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}/attachments/{$attachment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
    }

    public function test_rejects_oversized_file(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        $file = UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf');

        $response = $this->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }
}
