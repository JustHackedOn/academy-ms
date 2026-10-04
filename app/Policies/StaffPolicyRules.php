<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared role rules for every academy record.
 *
 * admin   = full access (read, write, hard delete, settings)
 * partner = full access except hard delete and portal-user management
 *
 * Policies declared in App\Policies are auto-discovered for their model, so each
 * concrete policy only needs to `use` this trait.
 */
trait StaffPolicyRules
{
    /**
     * Short-circuit every ability for administrators.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return true;
    }

    /**
     * Hard deletes are reserved for the academy administrator.
     */
    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function restore(User $user, Model $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }
}
