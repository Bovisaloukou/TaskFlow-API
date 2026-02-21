<?php

namespace Tests\Feature\Task;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    private function createProject($admin): Project
    {
        return Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Test Project',
            'created_by' => $admin->id,
        ]);
    }

    public function test_can_create_task(): void
    {
        Notification::fake();
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        $response = $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'New Task',
            'description' => 'Task description',
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Task')
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_can_list_tasks_in_project(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Task 1',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Task 2',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_update_task(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        $task = Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Original',
            'created_by' => $admin->id,
        ]);

        $response = $this->putJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
            'title' => 'Updated',
            'status' => 'in_progress',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated')
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_completing_task_sets_completed_at(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        $task = Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Complete Me',
            'created_by' => $admin->id,
        ]);

        $response = $this->putJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
            'status' => 'done',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_can_delete_task(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        $task = Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Delete Me',
            'created_by' => $admin->id,
        ]);

        $response = $this->deleteJson("/api/v1/projects/{$project->id}/tasks/{$task->id}");

        $response->assertOk();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_can_list_all_tasks_cross_project(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project1 = $this->createProject($admin);
        $project2 = Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Project 2',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project1->id,
            'title' => 'Task A',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project2->id,
            'title' => 'Task B',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_my_tasks_returns_assigned_tasks_only(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Assigned to me',
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
        ]);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Not assigned',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/my');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Assigned to me');
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Todo',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Done',
            'status' => 'done',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?status=todo");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_tasks_can_be_filtered_by_priority(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $project = $this->createProject($admin);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Urgent',
            'priority' => 'urgent',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'organization_id' => $admin->organization_id,
            'project_id' => $project->id,
            'title' => 'Low',
            'priority' => 'low',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?priority=urgent");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
