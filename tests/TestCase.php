<?php

namespace Tests;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function createOrganizationWithAdmin(array $orgAttributes = [], array $userAttributes = []): array
    {
        $organization = Organization::create(array_merge([
            'name' => 'Test Organization',
            'slug' => 'test-org-' . uniqid(),
        ], $orgAttributes));

        $admin = User::withoutGlobalScopes()->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'password',
        ], $userAttributes));

        $organization->update(['owner_id' => $admin->id]);
        $admin->assignRole('admin');

        return [$organization, $admin];
    }

    protected function actingAsOrgAdmin(array $orgAttributes = [], array $userAttributes = []): User
    {
        [$organization, $admin] = $this->createOrganizationWithAdmin($orgAttributes, $userAttributes);
        $this->actingAs($admin);

        return $admin;
    }

    protected function actingAsOrgUser(Organization $organization = null, string $role = 'user', array $userAttributes = []): User
    {
        if (! $organization) {
            $organization = Organization::create([
                'name' => 'Test Organization',
                'slug' => 'test-org-' . uniqid(),
            ]);
        }

        $user = User::withoutGlobalScopes()->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'Test User',
            'email' => 'user-' . uniqid() . '@test.com',
            'password' => 'password',
        ], $userAttributes));

        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }
}
