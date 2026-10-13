<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetType;
use App\Http\Requests\Analytics\StoreDashboardWidgetRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use JsonException;
use Tests\TestCase;

final class DashboardWidgetRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(
            'saved_reports',
            function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'owner_employee_id',
                );
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('dataset', 50);
                $table
                    ->unsignedSmallInteger(
                        'definition_version',
                    )
                    ->default(1);
                $table->json('definition');
                $table->timestamps();
                $table->softDeletes();
            },
        );

        DB::table('saved_reports')->insert([
            'id' => 500,
            'owner_employee_id' => 10,
            'name' => 'Transaction total',
            'description' => null,
            'dataset' => 'transactions',
            'definition_version' => 1,
            'definition' => json_encode(
                [
                    'dimensions' => ['currency'],
                    'measures' => ['total_amount'],
                    'filters' => [],
                    'relative_date' => null,
                    'limit' => 100,
                    'visualization' => null,
                ],
                JSON_THROW_ON_ERROR,
            ),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    public function test_valid_widget_is_canonicalized(): void
    {
        $this->replaceDefinition([
            'dimensions' => ['currency'],
            'measures' => ['total_amount'],
            'filters' => [],
            'relative_date' => [
                'dimension' => 'booked_at',
                'preset' => 'last_30_days',
            ],
            'limit' => 100,
            'visualization' => null,
        ]);

        [$request, $validator] = $this->request(
            payload: [
                'saved_report_id' => 500,
                'title' => '  Total transaction value  ',
                'widget_type' => 'kpi',
                'grid_column' => '6',
                'grid_row' => '2',
                'grid_width' => '6',
                'grid_height' => '3',
                'configuration' => [
                    'show_comparison' => '1',
                ],
            ],
        );

        $validator->validate();

        $this->assertSame(
            [
                'saved_report_id' => 500,
                'title' => 'Total transaction value',
                'widget_type' => DashboardWidgetType::KPI,
                'grid_column' => 6,
                'grid_row' => 2,
                'grid_width' => 6,
                'grid_height' => 3,
                'configuration' => [
                    'show_comparison' => true,
                ],
            ],
            $request->toWidgetAttributes(),
        );

        $this->assertSame(
            500,
            $request->authorizedSavedReport()->getKey(),
        );
    }

    public function test_grid_overflow_is_rejected(): void
    {
        [, $validator] = $this->request(
            payload: [
                ...$this->validPayload(),
                'grid_column' => 8,
                'grid_width' => 5,
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'layout',
            $validator->errors()->toArray(),
        );
    }

    public function test_kpi_requires_exactly_one_measure(): void
    {
        $this->replaceDefinition([
            'dimensions' => ['currency'],
            'measures' => [
                'transaction_count',
                'total_amount',
            ],
            'filters' => [],
            'relative_date' => null,
            'limit' => 100,
            'visualization' => null,
        ]);

        [, $validator] = $this->request(
            payload: $this->validPayload(),
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'widget_type',
            $validator->errors()->toArray(),
        );
    }

    public function test_chart_requires_saved_visualization(): void
    {
        [, $validator] = $this->request(
            payload: [
                ...$this->validPayload(),
                'widget_type' => 'chart',
                'configuration' => null,
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'widget_type',
            $validator->errors()->toArray(),
        );
    }

    public function test_unknown_configuration_is_rejected(): void
    {
        [, $validator] = $this->request(
            payload: [
                ...$this->validPayload(),
                'configuration' => [
                    'sql' => 'SELECT * FROM accounts',
                ],
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'configuration',
            $validator->errors()->toArray(),
        );
    }

    public function test_report_denied_by_policy_is_rejected(): void
    {
        [, $validator] = $this->request(
            payload: $this->validPayload(),
            reportDenied: true,
        );

        $this->assertTrue($validator->fails());

        $this->assertSame(
            'The selected report cannot be used in this dashboard.',
            $validator->errors()->first(
                'saved_report_id',
            ),
        );
    }

    public function test_kpi_rejects_dimensions_outside_measure_context(): void
    {
        $this->replaceDefinition([
            'dimensions' => [
                'branch',
                'currency',
            ],
            'measures' => ['total_amount'],
            'filters' => [],
            'relative_date' => null,
            'limit' => 100,
            'visualization' => null,
        ]);

        [, $validator] = $this->request(
            payload: $this->validPayload(),
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'widget_type',
            $validator->errors()->toArray(),
        );
    }

    public function test_kpi_comparison_requires_relative_date_definition(): void
    {
        [, $validator] = $this->request(
            payload: [
                ...$this->validPayload(),
                'configuration' => [
                    'show_comparison' => true,
                ],
            ],
        );

        $this->assertTrue($validator->fails());

        $this->assertSame(
            'The selected report is not compatible with this widget type.',
            $validator->errors()->first(
                'widget_type',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     StoreDashboardWidgetRequest,
     *     Validator
     * }
     */
    private function request(
        array $payload,
        bool $reportDenied = false,
    ): array {
        $gate = $this->createMock(GateContract::class);

        $gate
            ->method('denies')
            ->willReturn($reportDenied);

        Gate::shouldReceive('forUser')
            ->andReturn($gate);

        $request = StoreDashboardWidgetRequest::create(
            '/analytics/dashboards/1/widgets',
            'POST',
            $payload,
        );

        $user = new User;

        $request->setUserResolver(
            static fn (): User => $user,
        );

        $validator = ValidatorFacade::make(
            $payload,
            $request->rules(),
        );

        foreach ($request->after() as $callback) {
            $validator->after($callback);
        }

        $request->setValidator($validator);

        return [$request, $validator];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'saved_report_id' => 500,
            'title' => 'Total transaction value',
            'widget_type' => 'kpi',
            'grid_column' => 0,
            'grid_row' => 0,
            'grid_width' => 6,
            'grid_height' => 3,
            'configuration' => [
                'show_comparison' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     *
     * @throws JsonException
     */
    private function replaceDefinition(
        array $definition,
    ): void {
        DB::table('saved_reports')
            ->where('id', 500)
            ->update([
                'definition' => json_encode(
                    $definition,
                    JSON_THROW_ON_ERROR,
                ),
            ]);
    }
}
