<?php

namespace Tests\Unit;

use App\Http\Requests\Analytics\StoreDashboardRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;
use Tests\TestCase;

final class DashboardMetadataRequestTest extends TestCase
{
    public function test_valid_metadata_is_canonicalized(): void
    {
        $request = $this->validatedRequest([
            'name' => '  Treasury overview  ',
            'description' => '  Daily liquidity indicators  ',
            'is_default' => '1',
        ]);

        $this->assertSame(
            [
                'name' => 'Treasury overview',
                'description' => 'Daily liquidity indicators',
                'is_default' => true,
            ],
            $request->toDashboardAttributes(),
        );
    }

    public function test_default_indicator_defaults_to_false(): void
    {
        $request = $this->validatedRequest([
            'name' => 'Branch performance',
            'description' => null,
        ]);

        $this->assertSame(
            [
                'name' => 'Branch performance',
                'description' => null,
                'is_default' => false,
            ],
            $request->toDashboardAttributes(),
        );
    }

    public function test_name_cannot_contain_only_whitespace(): void
    {
        $validator = $this->validator([
            'name' => '   ',
            'description' => null,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'name',
            $validator->errors()->toArray(),
        );
    }

    public function test_blank_description_is_canonicalized_to_null(): void
    {
        $request = $this->validatedRequest([
            'name' => 'Treasury overview',
            'description' => '   ',
        ]);

        $this->assertNull(
            $request->toDashboardAttributes()['description'],
        );
    }

    public function test_default_indicator_must_be_boolean(): void
    {
        $validator = $this->validator([
            'name' => 'Treasury overview',
            'description' => null,
            'is_default' => 'sometimes',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'is_default',
            $validator->errors()->toArray(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatedRequest(
        array $payload,
    ): StoreDashboardRequest {
        $request = StoreDashboardRequest::create(
            '/analytics/dashboards',
            'POST',
            $payload,
        );

        $validator = Validator::make(
            $payload,
            $request->rules(),
        );

        $request->setValidator($validator);
        $validator->validate();

        return $request;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validator(
        array $payload,
    ): LaravelValidator {
        $request = StoreDashboardRequest::create(
            '/analytics/dashboards',
            'POST',
            $payload,
        );

        return Validator::make(
            $payload,
            $request->rules(),
        );
    }
}
