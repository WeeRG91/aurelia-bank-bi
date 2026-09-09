<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureActiveEmployee;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');

        $middleware->web(
            append: [
                AssignRequestId::class,
            ],
        );

        $middleware->alias([
            'active.employee' => EnsureActiveEmployee::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(
            function (Response $response): Response {
                $requestId = request()->attributes->get(
                    AssignRequestId::ATTRIBUTE,
                );

                if (is_string($requestId)) {
                    $response->headers->set(
                        AssignRequestId::HEADER,
                        $requestId,
                    );
                }

                return $response;
            },
        );
    })->create();
