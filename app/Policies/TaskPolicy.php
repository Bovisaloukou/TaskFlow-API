<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('tasks.view') && $user->organization_id === $task->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->organization_id !== $task->organization_id) {
            return false;
        }

        if ($user->can('tasks.update')) {
            return true;
        }

        // Users can update their own assigned tasks
        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->organization_id !== $task->organization_id) {
            return false;
        }

        if ($user->can('tasks.delete')) {
            return true;
        }

        return $task->created_by === $user->id;
    }
}
