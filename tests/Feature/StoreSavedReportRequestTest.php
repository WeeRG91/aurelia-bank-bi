<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Http\Requests\Analytics\StoreSavedReportRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Tests\TestCase;

class StoreSavedReportRequestTest extends TestCase
{
    public function test_valid_report_can_be_canonicalized_for_storage(): void
    {
        $request = $this->validatedRequest([
            'name' => ' Monthly EUR movements ',
            'description' => ' Monthly transaction analysis ',
            'dataset' => 'transactions',
            'dimensions' => [
                'booking_month',
                'currency',
            ],
            'measures' => ['total_amount'],
            'filters' => [
                [
                    'dimension' => 'currency',
                    'operator' => 'equals',
                    'value' => ' EUR ',
                ],
            ],
            'relative_date' => [
                'dimension' => 'booking_date',
                'preset' => 'last_30_days',
            ],
            'limit' => 75,
        ]);

        $this->assertSame([
            'dimensions' => [
                'booking_month',
                'currency',
            ],
            'measures' => ['total_amount'],
            'filters' => [
                [
                    'dimension' => 'currency',
                    'operator' => 'equals',
                    'value' => 'EUR',
                ],
            ],
            'relative_date' => [
                'dimension' => 'booking_date',
                'preset' => 'last_30_days',
            ],
            'limit' => 75,
            'visualization' => null,
        ], $request->toStoredDefinition());
    }

    public function test_name_cannot_contain_only_whitespace(): void
    {
        $validator = $this->validator([
            'name' => '   ',
            'description' => null,
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => [],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'name',
            $validator->errors()->toArray(),
        );
    }

    public function test_authorized_analyst_can_store_reports(): void
    {
        $request = $this->request(
            $this->validPayload(),
            EmployeeRole::BRANCH_ANALYST,
        );

        $this->assertTrue($request->authorize());
    }

    public function test_administrator_cannot_store_business_reports(): void
    {
        $request = $this->request(
            $this->validPayload(),
            EmployeeRole::ADMINISTRATOR,
        );

        $this->assertFalse($request->authorize());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatedRequest(
        array $payload,
    ): StoreSavedReportRequest {
        $request = StoreSavedReportRequest::create(
            '/analytics/saved-reports',
            'POST',
            $payload,
        );

        $validator = $this->validatorFor($request, $payload);

        $request->setValidator($validator);

        $validator->validate();

        return $request;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validator(array $payload): Validator
    {
        $request = StoreSavedReportRequest::create(
            '/analytics/saved-reports',
            'POST',
            $payload,
        );

        return $this->validatorFor($request, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatorFor(
        StoreSavedReportRequest $request,
        array $payload,
    ): Validator {
        $validator = ValidatorFacade::make(
            $payload,
            $request->rules(),
        );

        foreach ($request->after() as $callback) {
            $validator->after($callback);
        }

        return $validator;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function request(
        array $payload,
        EmployeeRole $role,
    ): StoreSavedReportRequest {
        $request = StoreSavedReportRequest::create(
            '/analytics/saved-reports',
            'POST',
            $payload,
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

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Monthly EUR movements',
            'description' => null,
            'dataset' => 'transactions',
            'dimensions' => ['currency'],
            'measures' => [],
            'filters' => [],
            'limit' => 100,
        ];
    }
}
