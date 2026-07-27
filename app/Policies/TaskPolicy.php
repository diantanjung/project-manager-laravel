<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->canAccessTask($task);
    }

    public function create(User $user, ?Project $project = null): bool
    {
        return $project === null || $user->canManageProject($project);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->canManageTask($task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->canManageTask($task);
    }
}
