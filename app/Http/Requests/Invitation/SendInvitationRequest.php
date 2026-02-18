<?php

namespace App\Http\Requests\Invitation;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('invitations.create');
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', 'in:admin,manager,user'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $orgId = $this->user()->organization_id;

            // Check if already a member
            $exists = User::withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->where('email', $this->email)
                ->exists();

            if ($exists) {
                $validator->errors()->add('email', 'This user is already a member of your organization.');
            }

            // Check if already invited (pending)
            $pending = OrganizationInvitation::withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->where('email', $this->email)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($pending) {
                $validator->errors()->add('email', 'An invitation has already been sent to this email.');
            }
        });
    }
}
