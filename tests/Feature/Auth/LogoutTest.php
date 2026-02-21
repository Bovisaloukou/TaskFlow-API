<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function test_user_can_logout(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_user_can_get_profile(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', $admin->email);
    }

    public function test_user_can_refresh_token(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }
}
