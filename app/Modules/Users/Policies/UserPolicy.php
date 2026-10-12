<?php

namespace App\Modules\Users\Policies;

use App\Modules\Users\Enums\UserType;
use App\Modules\Users\Models\Identity;

/**
 * Minimal user policy for the skeleton.
 *
 * Super Admins bypass everything through `before()`; Admins manage the user
 * population; everyone may view and update their own record. A tenancy layer
 * would scope the Admin abilities to the admin's own tenant here.
 */
class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(Identity $user, string $ability): ?bool
    {
        if ($user->type === UserType::SuperAdmin) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Identity $user): bool
    {
        return $user->type === UserType::Admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Identity $user, Identity $model): bool
    {
        if ($user->type === UserType::Admin) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Identity $user): bool
    {
        return $user->type === UserType::Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Identity $user, Identity $model): bool
    {
        if ($user->type === UserType::Admin) {
            // Admins may not mutate Super Admin rows
            return $model->type !== UserType::SuperAdmin;
        }

        // Users may update their own record. Which *columns* a payload may
        // touch is not enforced here: policies gate model access, not fields.
        // The role column stays out of payloads via Identity::$fillable and
        // is written only by trusted flows.
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Identity $user, Identity $model): bool
    {
        if ($user->type === UserType::Admin) {
            // Can't delete themselves or Super Admin rows
            return $user->id !== $model->id
                && $model->type !== UserType::SuperAdmin;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Identity $user, Identity $model): bool
    {
        if ($user->type === UserType::Admin) {
            return $model->type !== UserType::SuperAdmin;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Identity $user, Identity $model): bool
    {
        if ($user->type === UserType::Admin) {
            // Can't force delete themselves or Super Admin rows
            return $user->id !== $model->id
                && $model->type !== UserType::SuperAdmin;
        }

        return false;
    }

    /**
     * Determine whether the user can change user types.
     */
    public function changeType(Identity $user, Identity $model): bool
    {
        if ($user->type === UserType::Admin) {
            return $model->type !== UserType::SuperAdmin;
        }

        return false;
    }
}
