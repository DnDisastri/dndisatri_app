<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * La sezione redazionale la gestiscono **solo gli admin** (D1).
 *
 * Nella vecchia applicazione gli annunci li pubblicava chiunque: il controllo
 * «solo i DM» stava nel client, e la regola Firestore lasciava creare notifiche
 * a qualsiasi utente autenticato (§1-bis).
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Le bozze e le pubblicazioni programmate le vedono solo gli admin. */
    public function view(User $user, Post $post): bool
    {
        return $post->isPublished() || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}
