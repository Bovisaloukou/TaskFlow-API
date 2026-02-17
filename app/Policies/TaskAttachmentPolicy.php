<?php

namespace App\Policies;

use App\Models\TaskAttachment;
use App\Models\User;

class TaskAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attachments.view');
    }

    public function view(User $user, TaskAttachment $attachment): bool
    {
        return $user->can('attachments.view') && $user->organization_id === $attachment->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can('attachments.create');
    }

    public function delete(User $user, TaskAttachment $attachment): bool
    {
        if ($user->organization_id !== $attachment->organization_id) {
            return false;
        }

        return $attachment->uploaded_by === $user->id || $user->can('attachments.delete');
    }
}
