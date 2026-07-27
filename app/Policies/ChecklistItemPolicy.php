<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\Task;
use App\Models\User;

class ChecklistItemPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChecklistItem $checklistItem): bool
    {
        return $user->canAccessTask($checklistItem->task()->firstOrFail());
    }

    public function create(User $user, ?Task $task = null): bool
    {
        return $task === null || $user->canManageTask($task);
    }

    public function update(User $user, ChecklistItem $checklistItem): bool
    {
        return $user->canManageTask($checklistItem->task()->firstOrFail());
    }

    public function delete(User $user, ChecklistItem $checklistItem): bool
    {
        return $user->canManageTask($checklistItem->task()->firstOrFail());
    }
}
