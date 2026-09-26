<?php

namespace App\Policies;

use App\Models\AboutPage;
use App\Models\User;

/** «Chi siamo» la legge chiunque (anche gli ospiti), la scrive solo un admin. */
class AboutPagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AboutPage $page): bool
    {
        return true;
    }

    public function update(User $user, AboutPage $page): bool
    {
        return $user->isAdmin();
    }
}
