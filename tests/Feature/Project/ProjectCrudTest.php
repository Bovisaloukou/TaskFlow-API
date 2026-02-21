<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    public function test_admin_can_create_project(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'New Project',
            'description' => 'A test project',
            'color' => '#3498db',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Project');

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'organization_id' => $admin->organization_id,
        ]);
    }

    public function test_user_with_permission_can_list_projects(): void
    {
        $admin = $this->actingAsOrgAdmin();

        Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Project 1',
            'created_by' => $admin->id,
        ]);

        Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Project 2',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_update_project(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $project = Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Old Name',
            'created_by' => $admin->id,
        ]);

        $response = $this->putJson("/api/v1/projects/{$project->id}", [
            'name' => 'Updated Name',
            'status' => 'completed',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_admin_can_delete_project(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $project = Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'To Delete',
            'created_by' => $admin->id,
        ]);

        $response = $this->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_can_show_project(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $project = Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Show Me',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Show Me');
    }

    public function test_user_role_cannot_create_project(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $org = $admin->organization;
        
        $user = $this->actingAsOrgUser($org, 'user');

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Should Fail',
        ]);

        $response->assertStatus(403);
    }

    public function test_projects_can_be_filtered_by_status(): void
    {
        $admin = $this->actingAsOrgAdmin();

        Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Active',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Archived',
            'status' => 'archived',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/projects?status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Active');
    }

    public function test_projects_can_be_searched(): void
    {
        $admin = $this->actingAsOrgAdmin();

        Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Website Redesign',
            'created_by' => $admin->id,
        ]);

        Project::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Mobile App',
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/projects?search=website');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
