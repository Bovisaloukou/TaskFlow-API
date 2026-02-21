<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_user_can_register_with_organization(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Acme Corp',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'organization_id'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('organizations', ['name' => 'Acme Corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_register_requires_organization_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('organization_name');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'organization_name' => 'Acme Corp',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_assigns_admin_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Acme Corp',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $response->json('data.user.id'),
        ]);
    }
}
