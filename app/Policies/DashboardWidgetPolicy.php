<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\User;

class DashboardWidgetPolicy
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
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DashboardWidget $dashboardWidget): bool
    {
        return $this->isDashboardOwner($user, $dashboardWidget);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DashboardWidget $dashboardWidget): bool
    {
        return $this->isDashboardOwner($user, $dashboardWidget);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DashboardWidget $dashboardWidget): bool
    {
        return $this->isDashboardOwner($user, $dashboardWidget);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DashboardWidget $dashboardWidget): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DashboardWidget $dashboardWidget): bool
    {
        return false;
    }

    private function isDashboardOwner(User $user, DashboardWidget $dashboardWidget): bool
    {
        $dashboard = $dashboardWidget->dashboard;

        return $dashboard instanceof Dashboard
            && $user->employee?->getKey() === $dashboard->owner_employee_id;
    }
}
