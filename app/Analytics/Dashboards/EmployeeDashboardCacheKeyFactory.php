<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Time\ReportingTimezone;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use DateTimeInterface;
use JsonException;

final readonly class EmployeeDashboardCacheKeyFactory
{
    private const int VERSION = 1;

    public function __construct(
        private DashboardCacheGeneration $cacheGeneration,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     * @throws JsonException
     */
    public function forUser(
        User $user,
        Dashboard $dashboard,
        ReportingTimezone $reportingTimezone,
        DateTimeInterface $now,
    ): ?string {
        $employee = $user->employee;

        if (
            ! $employee instanceof Employee
            || ! $employee->role instanceof EmployeeRole
            || ! $employee->status instanceof EmployeeStatus
        ) {
            return null;
        }

        if (! $dashboard->relationLoaded('widgets')) {
            $dashboard->load('widgets.savedReport');
        } else {
            foreach ($dashboard->getRelation('widgets') as $widget) {
                if (
                    $widget instanceof DashboardWidget
                    && ! $widget->relationLoaded('savedReport')
                ) {
                    $widget->load('savedReport');
                }
            }
        }

        $state = [
            'version' => self::VERSION,
            'generation' => $this->cacheGeneration->current(),
            'employee' => [
                'id' => $employee->getKey(),
                'role' => $employee->role->value,
                'status' => $employee->status->value,
                'branch_id' => $employee->branch_id,
            ],
            'dashboard' => [
                'id' => $dashboard->getKey(),
                'owner_employee_id' => $dashboard->owner_employee_id,
                'updated_at' => $this->timestamp(
                    $dashboard->getAttributes()['updated_at'] ?? null,
                ),
            ],
            'reporting_timezone' => $reportingTimezone->name,
            'reporting_date' => CarbonImmutable::instance($now)
                ->setTimezone($reportingTimezone->toDateTimeZone())
                ->toDateString(),
            'widgets' => $dashboard->widgets
                ->filter(
                    static fn (mixed $widget): bool => $widget
                        instanceof DashboardWidget,
                )
                ->map(
                    fn (DashboardWidget $widget): array => [
                        'id' => $widget->getKey(),
                        'saved_report_id' => $widget->saved_report_id,
                        'title' => $widget->title,
                        'widget_type' => $widget
                            ->widget_type
                            ->value,
                        'layout' => [
                            'column' => $widget->grid_column,
                            'row' => $widget->grid_row,
                            'width' => $widget->grid_width,
                            'height' => $widget->grid_height,
                        ],
                        'configuration' => $widget->configuration,
                        'updated_at' => $this->timestamp(
                            $widget->getAttributes()['updated_at'] ?? null,
                        ),
                        'report' => $this->reportState(
                            $widget->savedReport,
                        ),
                    ],
                )
                ->values()
                ->all(),
        ];

        return sprintf(
            'analytics:employee-dashboard:v%d:%s',
            self::VERSION,
            hash(
                'sha256',
                json_encode(
                    $state,
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function reportState(
        mixed $report,
    ): ?array {
        if (! $report instanceof SavedReport) {
            return null;
        }

        return [
            'id' => $report->getKey(),
            'dataset' => $report->dataset->value,
            'definition_version' => $report->definition_version,
            'definition' => $report->definition,
            'updated_at' => $this->timestamp(
                $report->getAttributes()['updated_at'] ?? null,
            ),
        ];
    }

    private function timestamp(
        mixed $value,
    ): ?string {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
