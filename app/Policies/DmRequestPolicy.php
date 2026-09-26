<?php

namespace App\Policies;

use App\Models\DmRequest;
use App\Models\User;

/**
 * Solo gli admin approvano i nuovi DM: è il requisito n°1 della riscrittura.
 */
class DmRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, DmRequest $request): bool
    {
        return $user->isAdmin() || $request->user_id === $user->getKey();
    }

    /** Chiede di diventare DM chi non lo è già, e una richiesta alla volta. */
    public function create(User $user): bool
    {
        return ! $user->isDm()
            && ! $user->isAdmin()
            && ! DmRequest::where('user_id', $user->getKey())->pending()->exists();
    }

    public function approve(User $user, DmRequest $request): bool
    {
        return $user->isAdmin() && $request->isPending();
    }

    public function reject(User $user, DmRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    /** Il richiedente può ritirare la propria richiesta. */
    public function cancel(User $user, DmRequest $request): bool
    {
        return $request->isPending() && $request->user_id === $user->getKey();
    }
}
