<?php

namespace Tests\Feature\Tenant;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_user_cannot_see_other_organization_projects(): void
    {
        // Create org 1
        $org1 = Organization::create(['name' => 'Org 1', 'slug' => 'org-1']);
        $admin1 = User::withoutGlobalScopes()->create([
            'organization_id' => $org1->id,
            'name' => 'Admin 1',
            'email' => 'admin1@test.com',
            'password' => 'password',
        ]);
        $admin1->assignRole('admin');

        Project::withoutGlobalScopes()->create([
            'organization_id' => $org1->id,
            'name' => 'Org 1 Project',
            'created_by' => $admin1->id,
        ]);

        // Create org 2
        $org2 = Organization::create(['name' => 'Org 2', 'slug' => 'org-2']);
        $admin2 = User::withoutGlobalScopes()->create([
            'organization_id' => $org2->id,
            'name' => 'Admin 2',
            'email' => 'admin2@test.com',
            'password' => 'password',
        ]);
        $admin2->assignRole('admin');

        Project::withoutGlobalScopes()->create([
            'organization_id' => $org2->id,
            'name' => 'Org 2 Project',
            'created_by' => $admin2->id,
        ]);

        // Admin 1 should only see Org 1 projects
        $this->actingAs($admin1);
        $response = $this->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Org 1 Project');
    }

    public function test_user_cannot_see_other_organization_tasks(): void
    {
        // Create org 1 with project and task
        $org1 = Organization::create(['name' => 'Org 1', 'slug' => 'org-1']);
        $admin1 = User::withoutGlobalScopes()->create([
            'organization_id' => $org1->id,
            'name' => 'Admin 1',
            'email' => 'admin1@test.com',
            'password' => 'password',
        ]);
        $admin1->assignRole('admin');

        $project1 = Project::withoutGlobalScopes()->create([
            'organization_id' => $org1->id,
            'name' => 'Org 1 Project',
            'created_by' => $admin1->id,
        ]);

        Task::withoutGlobalScopes()->create([
            'organization_id' => $org1->id,
            'project_id' => $project1->id,
            'title' => 'Org 1 Task',
            'created_by' => $admin1->id,
        ]);

        // Create org 2 with project and task
        $org2 = Organization::create(['name' => 'Org 2', 'slug' => 'org-2']);
        $admin2 = User::withoutGlobalScopes()->create([
            'organization_id' => $org2->id,
            'name' => 'Admin 2',
            'email' => 'admin2@test.com',
            'password' => 'password',
        ]);
        $admin2->assignRole('admin');

        $project2 = Project::withoutGlobalScopes()->create([
            'organization_id' => $org2->id,
            'name' => 'Org 2 Project',
            'created_by' => $admin2->id,
        ]);

        Task::withoutGlobalScopes()->create([
            'organization_id' => $org2->id,
            'project_id' => $project2->id,
            'title' => 'Org 2 Task',
            'created_by' => $admin2->id,
        ]);

        // Admin 1 should only see their tasks
        $this->actingAs($admin1);
        $response = $this->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Org 1 Task');
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/organization');
        $response->assertStatus(401);
    }
}
