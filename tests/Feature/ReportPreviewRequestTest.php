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
