<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public const string ATTRIBUTE = 'analytics_request_id';

    public const string HEADER = 'X-Request-ID';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = Str::uuid()->toString();

        $request->attributes->set(
            self::ATTRIBUTE,
            $requestId,
        );

        $response = $next($request);

        $response->headers->set(
            self::HEADER,
            $requestId,
        );

        return $response;
    }
}
