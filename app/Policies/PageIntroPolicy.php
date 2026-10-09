<?php

namespace App\Policies;

use App\Models\PageIntro;
use App\Models\User;

/** Le introduzioni le scrive solo un admin. Le righe sono una per pagina: non se ne creano né cancellano. */
class PageIntroPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PageIntro $intro): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PageIntro $intro): bool
    {
        return $user->isAdmin();
    }
}
