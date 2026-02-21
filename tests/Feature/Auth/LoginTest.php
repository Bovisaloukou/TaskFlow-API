<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_user_can_login(): void
    {
        $organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        User::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }
}
