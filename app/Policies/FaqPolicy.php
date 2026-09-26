<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

/**
 * La guida la leggono tutti, la scrivono solo gli admin (come le news).
 */
class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Le bozze le vedono solo gli admin. */
    public function view(User $user, Faq $faq): bool
    {
        return $faq->is_published || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->isAdmin();
    }
}
