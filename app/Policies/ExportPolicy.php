<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Export $export): bool
    {
        return $export->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
