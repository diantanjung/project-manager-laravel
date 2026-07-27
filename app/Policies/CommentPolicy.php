<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->canAccessTask($comment->task()->firstOrFail());
    }

    public function create(User $user, ?Task $task = null): bool
    {
        return $task === null || $user->canAccessTask($task);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->author_id === $user->id || $user->canManageTask($comment->task()->firstOrFail());
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->author_id === $user->id || $user->canManageTask($comment->task()->firstOrFail());
    }
}
