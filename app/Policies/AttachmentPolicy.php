<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;

class AttachmentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $user->canAccessTask($attachment->task()->firstOrFail());
    }

    public function create(User $user, ?Task $task = null): bool
    {
        return $task === null || $user->canAccessTask($task);
    }

    public function update(User $user, Attachment $attachment): bool
    {
        return $attachment->uploader_id === $user->id || $user->canManageTask($attachment->task()->firstOrFail());
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $attachment->uploader_id === $user->id || $user->canManageTask($attachment->task()->firstOrFail());
    }
}
