<?php

namespace App\Policies;

use App\Models\{User};

class TimeRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->role->isEmployee();
    }
}
