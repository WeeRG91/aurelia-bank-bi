<?php

namespace App\Policies;

use App\Models\ScheduledReport;
use App\Models\User;

final class ScheduledReportPolicy
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
    public function view(User $user, ScheduledReport $scheduledReport): bool
    {
        return $this->isOwner($user, $scheduledReport);
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
    public function update(User $user, ScheduledReport $scheduledReport): bool
    {
        return $this->isOwner($user, $scheduledReport);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduledReport $scheduledReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScheduledReport $scheduledReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScheduledReport $scheduledReport): bool
    {
        return false;
    }

    private function isOwner(
        User $user,
        ScheduledReport $scheduledReport
    ): bool {
        return $user->employee?->getKey()
            === $scheduledReport->created_by_employee_id;
    }
}
