<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\InvitationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InvitationService
{
    public function create(array $data): OrganizationInvitation
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $data['organization_id'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'user',
            'token' => Str::random(64),
            'invited_by' => $data['invited_by'],
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationSent($invitation));

        return $invitation;
    }

    public function accept(OrganizationInvitation $invitation, array $userData): User
    {
        return DB::transaction(function () use ($invitation, $userData) {
            $user = User::withoutGlobalScopes()->create([
                'organization_id' => $invitation->organization_id,
                'name' => $userData['name'],
                'email' => $invitation->email,
                'password' => $userData['password'],
            ]);

            $user->assignRole($invitation->role);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }

    public function delete(OrganizationInvitation $invitation): void
    {
        $invitation->delete();
    }
}
