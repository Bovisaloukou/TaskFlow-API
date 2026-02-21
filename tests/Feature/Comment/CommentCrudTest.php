<?php

namespace Tests\Feature\Comment;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentCrudTest extends TestCase
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

    public function test_can_create_comment(): void
    {
        Notification::fake();
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'This is a comment',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'This is a comment');
    }

    public function test_can_list_comments(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        Comment::create([
            'organization_id' => $admin->organization_id,
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'body' => 'Comment 1',
        ]);

        Comment::create([
            'organization_id' => $admin->organization_id,
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'body' => 'Comment 2',
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}/comments");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_update_own_comment(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        $comment = Comment::create([
            'organization_id' => $admin->organization_id,
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'body' => 'Original',
        ]);

        $response = $this->putJson("/api/v1/tasks/{$task->id}/comments/{$comment->id}", [
            'body' => 'Updated comment',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.body', 'Updated comment');
    }

    public function test_can_delete_own_comment(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $task = $this->createTaskForAdmin($admin);

        $comment = Comment::create([
            'organization_id' => $admin->organization_id,
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'body' => 'To delete',
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}/comments/{$comment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
