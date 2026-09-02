<?php

namespace App\Policies;

use App\Analytics\Datasets\DatasetAccess;
use App\Models\SavedReport;
use App\Models\User;

final readonly class SavedReportPolicy
{
    public function __construct(
        private DatasetAccess $datasetAccess,
    ) {}

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
    public function view(User $user, SavedReport $savedReport): bool
    {
        return $this->isOwner($user, $savedReport);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->datasetAccess->discoverableTo($user) !== [];
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavedReport $savedReport): bool
    {
        return $this->isOwner($user, $savedReport);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavedReport $savedReport): bool
    {
        return $this->isOwner($user, $savedReport);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavedReport $savedReport): bool
    {
        return $this->isOwner($user, $savedReport);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavedReport $savedReport): bool
    {
        return false;
    }

    private function isOwner(User $user, SavedReport $savedReport): bool
    {
        return $user->employee?->getKey() === $savedReport->owner_employee_id;
    }
}
