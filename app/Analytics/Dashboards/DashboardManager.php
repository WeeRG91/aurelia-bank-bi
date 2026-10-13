<?php

namespace App\Analytics\Dashboards;

use App\Models\Dashboard;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DashboardManager
{
    /**
     * @param  array{
     *     name: string,
     *     description: string|null,
     *     is_default: bool
     * }  $attributes
     *
     * @throws Throwable
     */
    public function create(
        Employee $owner,
        array $attributes,
    ): Dashboard {
        return DB::transaction(
            function () use ($owner, $attributes): Dashboard {
                $this->lockOwner($owner);

                if ($attributes['is_default']) {
                    $this->clearExistingDefault(
                        ownerEmployeeId: (int) $owner->getKey(),
                    );
                }

                /** @var Dashboard $dashboard */
                $dashboard = $owner
                    ->dashboards()
                    ->create($attributes);

                return $dashboard;
            },
        );
    }

    /**
     * @param  array{
     *     name: string,
     *     description: string|null,
     *     is_default: bool
     * }  $attributes
     *
     * @throws Throwable
     */
    public function update(
        Dashboard $dashboard,
        array $attributes,
    ): Dashboard {
        return DB::transaction(
            function () use (
                $dashboard,
                $attributes,
            ): Dashboard {
                $ownerEmployeeId = (int) $dashboard
                    ->owner_employee_id;

                $this->lockOwnerById($ownerEmployeeId);

                if ($attributes['is_default']) {
                    $this->clearExistingDefault(
                        ownerEmployeeId: $ownerEmployeeId,
                        exceptDashboardId: (int) $dashboard
                            ->getKey(),
                    );
                }

                $dashboard->update($attributes);

                return $dashboard->refresh();
            },
        );
    }

    private function lockOwner(Employee $owner): void
    {
        $this->lockOwnerById(
            (int) $owner->getKey(),
        );
    }

    private function lockOwnerById(
        int $ownerEmployeeId,
    ): void {
        Employee::query()
            ->whereKey($ownerEmployeeId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function clearExistingDefault(
        int $ownerEmployeeId,
        ?int $exceptDashboardId = null,
    ): void {
        $query = Dashboard::query()
            ->where(
                'owner_employee_id',
                $ownerEmployeeId,
            )
            ->where('is_default', true);

        if ($exceptDashboardId !== null) {
            $query->where(
                'id',
                '<>',
                $exceptDashboardId,
            );
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
