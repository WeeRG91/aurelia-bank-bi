<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Datasets\DatasetKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AuditEventIndexRequest;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use DateTimeZone;
use Illuminate\View\View;
use LogicException;

class AnalyticsAuditEventController extends Controller
{
    /**
     * @throws DateInvalidTimeZoneException
     */
    public function index(
        AuditEventIndexRequest $request,
        WebAuditRecorder $audit,
    ): View {
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();

        $timezoneName = (string) config(
            'analytics.reporting_timezone',
            'UTC',
        );

        $timezone = new DateTimeZone($timezoneName);

        $query = AnalyticsAuditEvent::query()
            ->with('actor.user')
            ->when(
                isset($filters['action']),
                fn ($query) => $query->where(
                    'action',
                    $filters['action'],
                ),
            )
            ->when(
                isset($filters['outcome']),
                fn ($query) => $query->where(
                    'outcome',
                    $filters['outcome'],
                ),
            )
            ->when(
                isset($filters['source']),
                fn ($query) => $query->where(
                    'source',
                    $filters['source'],
                ),
            )
            ->when(
                isset($filters['dataset']),
                fn ($query) => $query->where(
                    'dataset',
                    $filters['dataset'],
                ),
            )
            ->when(
                isset($filters['actor_employee_id']),
                fn ($query) => $query->where(
                    'actor_employee_id',
                    $filters['actor_employee_id'],
                ),
            )
            ->when(
                isset($filters['from']),
                fn ($query) => $query->where(
                    'occurred_at',
                    '>=',
                    CarbonImmutable::parse(
                        $filters['from'],
                        $timezone,
                    )->startOfDay()->utc(),
                ),
            )
            ->when(
                isset($filters['to']),
                fn ($query) => $query->where(
                    'occurred_at',
                    '<=',
                    CarbonImmutable::parse(
                        $filters['to'],
                        $timezone,
                    )->endOfDay()->utc(),
                ),
            )
            ->latest('occurred_at')
            ->paginate(50)
            ->withQueryString();

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $audit->record(
            request: $request,
            action: AuditAction::AUDIT_TRAIL_VIEWED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            context: AuditContext::from([
                'filter_count' => count(array_filter(
                    $filters,
                    static fn (mixed $value): bool => $value !== null
                        && $value !== '',
                )),
                'limit' => 50,
                'row_count' => $query->count(),
            ]),
        );

        return view('analytics.audit_events.index', [
            'events' => $query,
            'actions' => AuditAction::cases(),
            'outcomes' => AuditOutcome::cases(),
            'sources' => AuditSource::cases(),
            'datasets' => DatasetKey::cases(),
            'reportingTimezone' => $timezoneName,
        ]);
    }
}
