<?php

namespace Tests\Feature\Invitation;

use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    public function test_admin_can_send_invitation(): void
    {
        Notification::fake();
        $admin = $this->actingAsOrgAdmin();

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'newuser@example.com');

        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'newuser@example.com',
            'organization_id' => $admin->organization_id,
        ]);
    }

    public function test_can_list_invitations(): void
    {
        $admin = $this->actingAsOrgAdmin();

        OrganizationInvitation::create([
            'organization_id' => $admin->organization_id,
            'email' => 'invite1@example.com',
            'token' => Str::random(64),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/v1/invitations');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_accept_invitation(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $token = Str::random(64);

        OrganizationInvitation::withoutGlobalScopes()->create([
            'organization_id' => $admin->organization_id,
            'email' => 'newuser@example.com',
            'role' => 'user',
            'token' => $token,
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Act as unauthenticated
        auth()->forgetGuards();
        
        $response = $this->postJson("/api/v1/invitations/{$token}/accept", [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'organization_id' => $admin->organization_id,
        ]);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $admin = $this->actingAsOrgAdmin();
        $token = Str::random(64);

        OrganizationInvitation::withoutGlobalScopes()->create([
            'organization_id' => $admin->organization_id,
            'email' => 'expired@example.com',
            'token' => $token,
            'invited_by' => $admin->id,
            'expires_at' => now()->subDay(),
        ]);

        auth()->forgetGuards();

        $response = $this->postJson("/api/v1/invitations/{$token}/accept", [
            'name' => 'Expired User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_delete_invitation(): void
    {
        $admin = $this->actingAsOrgAdmin();

        $invitation = OrganizationInvitation::create([
            'organization_id' => $admin->organization_id,
            'email' => 'delete@example.com',
            'token' => Str::random(64),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->deleteJson("/api/v1/invitations/{$invitation->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('organization_invitations', ['id' => $invitation->id]);
    }

    public function test_user_role_cannot_send_invitation(): void
    {
        Notification::fake();
        $admin = $this->actingAsOrgAdmin();
        $org = $admin->organization;

        $user = $this->actingAsOrgUser($org, 'user');

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'nope@example.com',
            'role' => 'user',
        ]);

        $response->assertStatus(403);
    }
}
