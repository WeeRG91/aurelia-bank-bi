<?php

namespace Tests\Feature;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class RequestIdMiddlewareTest extends TestCase
{
    public function test_successful_web_response_receives_request_id(): void
    {
        Route::middleware('web')->get(
            '/request-id-success',
            static fn (): string => 'ok',
        );

        $response = $this->get('/request-id-success');

        $requestId = $response->headers->get(
            AssignRequestId::HEADER,
        );

        $this->assertIsString($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $requestId,
        );
    }

    public function test_failed_web_response_keeps_request_id(): void
    {
        Route::middleware('web')->get(
            '/request-id-failure',
            static fn () => abort(403),
        );

        $response = $this->get('/request-id-failure');

        $response->assertForbidden();

        $this->assertIsString(
            $response->headers->get(
                AssignRequestId::HEADER,
            ),
        );
    }

    public function test_each_request_receives_a_distinct_identifier(): void
    {
        Route::middleware('web')->get(
            '/request-id-distinct',
            static fn (): string => 'ok',
        );

        $first = $this->get('/request-id-distinct')
            ->headers
            ->get(AssignRequestId::HEADER);

        $second = $this->get('/request-id-distinct')
            ->headers
            ->get(AssignRequestId::HEADER);

        $this->assertNotSame($first, $second);
    }
}
