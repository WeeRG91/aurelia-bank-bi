<?php

namespace App\Policies;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Exports\ReportExportStatus;
use App\Models\ReportExport;
use App\Models\User;

final readonly class ReportExportPolicy
{
    public function __construct(
        private DatasetAccess $datasetAccess,
        private DatasetFieldAccess $fieldAccess,
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
    public function view(User $user, ReportExport $reportExport): bool
    {
        return $this->isOwner($user, $reportExport);
    }

    public function download(User $user, ReportExport $reportExport): bool
    {
        $definition = $reportExport->definition;

        return $this->isOwner($user, $reportExport)
            && $reportExport->status === ReportExportStatus::COMPLETED
            && $reportExport->expires_at?->isFuture() === true
            && is_string($reportExport->disk)
            && is_string($reportExport->path)
            && is_string($reportExport->filename)
            && is_array($definition)
            && $this->datasetAccess->canUse(
                $user,
                $reportExport->dataset,
            )
            && $this->fieldAccess->canUseDefinition(
                $user,
                $reportExport->dataset,
                $definition,
            );
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
    public function update(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    private function isOwner(User $user, ReportExport $reportExport): bool
    {
        return $user->employee?->getKey() === $reportExport->requested_by_employee_id;
    }
}
