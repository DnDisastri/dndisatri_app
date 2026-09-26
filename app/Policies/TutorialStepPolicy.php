<?php

namespace App\Policies;

use App\Models\TutorialStep;
use App\Models\User;

/**
 * Il tutorial lo leggono tutti, lo scrivono solo gli admin (come le FAQ).
 */
class TutorialStepPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TutorialStep $step): bool
    {
        return $step->is_published || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TutorialStep $step): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TutorialStep $step): bool
    {
        return $user->isAdmin();
    }
}
