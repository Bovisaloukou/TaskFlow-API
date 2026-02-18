<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'email' => $this->email,
            'role' => $this->role,
            'invited_by' => $this->invited_by,
            'inviter' => new UserResource($this->whenLoaded('inviter')),
            'accepted_at' => $this->accepted_at,
            'expires_at' => $this->expires_at,
            'is_pending' => $this->isPending(),
            'created_at' => $this->created_at,
        ];
    }
}
