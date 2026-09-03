<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Http\Requests\Analytics\ReportPreviewRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Tests\TestCase;

class ReportPreviewRequestTest extends TestCase
{
    public function test_valid_semantic_selection_passes_validation(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => [
                'booking_month',
                'currency',
            ],
            'measures' => ['total_amount'],
            'limit' => 100,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_unknown_semantic_keys_are_rejected(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['password'],
            'measures' => ['arbitrary_sql'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'dimensions.0',
            $validator->errors()->toArray(),
        );
        $this->assertArrayHasKey(
            'measures.0',
            $validator->errors()->toArray(),
        );
    }

    public function test_measure_requires_its_context_dimensions(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['booking_month'],
            'measures' => ['total_amount'],
        ]);

        $this->assertTrue($validator->fails());

        $this->assertSame(
            [
                'Measure [total_amount] requires dimension [currency].',
            ],
            $validator->errors()->get('measures.0'),
        );
    }

    public function test_empty_report_definition_is_rejected(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => [],
            'measures' => [],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'dimensions',
            $validator->errors()->toArray(),
        );
    }

    public function test_limits_and_duplicate_keys_are_rejected(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => [
                'currency',
                'currency',
            ],
            'measures' => [],
            'limit' => 501,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'dimensions.1',
            $validator->errors()->toArray(),
        );
        $this->assertArrayHasKey(
            'limit',
            $validator->errors()->toArray(),
        );
    }

    public function test_only_authorized_analytical_roles_can_preview_dataset(): void
    {
        $analystRequest = $this->request(
            EmployeeRole::BRANCH_ANALYST,
        );

        $administratorRequest = $this->request(
            EmployeeRole::ADMINISTRATOR,
        );

        $this->assertTrue($analystRequest->authorize());
        $this->assertFalse($administratorRequest->authorize());
    }

    public function test_valid_filter_passes_semantic_validation(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['transaction_reference'],
            'measures' => [],
            'filters' => [
                [
                    'dimension' => 'currency',
                    'operator' => 'equals',
                    'value' => 'EUR',
                ],
            ],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_incompatible_filter_operator_is_rejected(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['transaction_reference'],
            'measures' => [],
            'filters' => [
                [
                    'dimension' => 'currency',
                    'operator' => 'before',
                    'value' => 'EUR',
                ],
            ],
        ]);

        $this->assertTrue($validator->fails());

        $this->assertSame(
            [
                'Operator [before] is not supported for dimension [currency].',
            ],
            $validator->errors()->get('filters.0'),
        );
    }

    public function test_null_filter_operator_accepts_explicit_null(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['transaction_reference'],
            'measures' => [],
            'filters' => [
                [
                    'dimension' => 'booked_at',
                    'operator' => 'is_null',
                    'value' => null,
                ],
            ],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_valid_relative_date_preset_passes_validation(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => [],
            'relative_date' => [
                'dimension' => 'booking_date',
                'preset' => 'last_30_days',
            ],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_relative_date_rejects_non_temporal_dimension(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => [],
            'relative_date' => [
                'dimension' => 'currency',
                'preset' => 'last_30_days',
            ],
        ]);

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'relative_date.dimension',
            $validator->errors()->toArray(),
        );
    }

    public function test_relative_date_conflicts_with_explicit_filter(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => [],
            'filters' => [
                [
                    'dimension' => 'booking_date',
                    'operator' => 'after',
                    'value' => '2026-08-01',
                ],
            ],
            'relative_date' => [
                'dimension' => 'booking_date',
                'preset' => 'last_30_days',
            ],
        ]);

        $this->assertTrue($validator->fails());

        $this->assertSame(
            [
                'Explicit filter for [booking_date] cannot be combined with a relative date preset on the same dimension.',
            ],
            $validator->errors()->get('relative_date.dimension'),
        );
    }

    public function test_line_chart_requires_a_temporal_dimension(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => ['transaction_count'],
            'visualization' => [
                'type' => 'line',
                'dimension' => 'currency',
                'measure' => 'transaction_count',
            ],
        ]);

        $this->assertTrue($validator->fails());

        $this->assertSame(
            ['Line charts require a temporal dimension.'],
            $validator->errors()->get(
                'visualization.dimension',
            ),
        );
    }

    public function test_bar_chart_accepts_selected_dimension_and_measure(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => ['transaction_count'],
            'visualization' => [
                'type' => 'bar',
                'dimension' => 'currency',
                'measure' => 'transaction_count',
            ],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_line_chart_accepts_a_separate_series_dimension(): void
    {
        $validator = $this->validator([
            'dataset' => 'account_balances',
            'dimensions' => [
                'snapshot_date',
                'currency',
            ],
            'measures' => [
                'average_available_balance',
            ],
            'visualization' => [
                'type' => 'line',
                'dimension' => 'snapshot_date',
                'measure' => 'average_available_balance',
                'series' => 'currency',
            ],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_chart_series_must_be_a_selected_dimension(): void
    {
        $validator = $this->validator([
            'dataset' => 'transactions',
            'dimensions' => ['booking_month'],
            'measures' => ['transaction_count'],
            'visualization' => [
                'type' => 'line',
                'dimension' => 'booking_month',
                'measure' => 'transaction_count',
                'series' => 'currency',
            ],
        ]);

        $this->assertTrue($validator->fails());

        $this->assertSame(
            ['The chart series must be selected in the report.'],
            $validator->errors()->get('visualization.series'),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validator(array $payload): Validator
    {
        $request = ReportPreviewRequest::create(
            '/analytics/report-preview',
            'POST',
            $payload,
        );

        $validator = ValidatorFacade::make(
            $payload,
            $request->rules(),
        );

        foreach ($request->after() as $callback) {
            $validator->after($callback);
        }

        return $validator;
    }

    private function request(
        EmployeeRole $role,
    ): ReportPreviewRequest {
        $request = ReportPreviewRequest::create(
            '/analytics/report-preview',
            'POST',
            [
                'dataset' => 'transactions',
                'dimensions' => ['transaction_reference'],
                'measures' => [],
            ],
        );

        $user = new User;
        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'branch_id' => $role === EmployeeRole::BRANCH_ANALYST
                    ? 42
                    : null,
                'role' => $role,
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        $request->setUserResolver(
            static fn (): User => $user,
        );

        return $request;
    }
}
