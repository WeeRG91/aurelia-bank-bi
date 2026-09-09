<?php

namespace App\Policies;

use App\Enums\EmployeeRole;
use App\Models\AnalyticsAuditEvent;
use App\Models\User;

final class AnalyticsAuditEventPolicy
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
        return in_array(
            $user->employee?->role,
            [
                EmployeeRole::AUDITOR,
                EmployeeRole::ADMINISTRATOR,
            ],
            true,
        );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AnalyticsAuditEvent $analyticsAuditEvent): bool
    {
        return $this->viewAny($user);
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
    public function update(User $user, AnalyticsAuditEvent $analyticsAuditEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AnalyticsAuditEvent $analyticsAuditEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AnalyticsAuditEvent $analyticsAuditEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AnalyticsAuditEvent $analyticsAuditEvent): bool
    {
        return false;
    }
}
