<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;

class DashboardPolicy
{
    public function before(User $user): ?bool
    {
        if (! $user->isActiveEmployee()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dashboard $dashboard): bool
    {
        return $this->isOwner($user, $dashboard);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dashboard $dashboard): bool
    {
        return $this->isOwner($user, $dashboard);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $this->isOwner($user, $dashboard);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dashboard $dashboard): bool
    {
        return $this->isOwner($user, $dashboard);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dashboard $dashboard): bool
    {
        return false;
    }

    public function addWidget(User $user, Dashboard $dashboard): bool
    {
        return $this->isOwner($user, $dashboard);
    }

    private function isOwner(User $user, Dashboard $dashboard): bool
    {
        return $user->employee?->getKey() === $dashboard->owner_employee_id;
    }
}
