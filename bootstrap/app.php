<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureOrganizationMember;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/analytics.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'organization.member' => EnsureOrganizationMember::class,
            'not.installed' => \App\Http\Middleware\EnsureNotInstalled::class,
        ]);

        $middleware->web(
            append: [
                HandleAppearance::class,
                HandleInertiaRequests::class,
                AddLinkHeadersForPreloadedAssets::class,
            ],
            prepend: [
                AssignRequestId::class,
            ],
        );

        $middleware->api(prepend: [
            AssignRequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->stopIgnoring(HttpException::class);
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $status = $exception->status() ?? 403;

            return response()->json([
                'message' => $status === 404 ? 'Resource not found.' : 'This action is unauthorized.',
                'code' => $status === 404 ? 'not_found' : 'forbidden',
            ], $status);
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if (! $request->expectsJson() || ! in_array($exception->getStatusCode(), [403, 404], true)) {
                return null;
            }

            return response()->json([
                'message' => $exception->getStatusCode() === 404 ? 'Resource not found.' : 'This action is unauthorized.',
                'code' => $exception->getStatusCode() === 404 ? 'not_found' : 'forbidden',
            ], $exception->getStatusCode());
        });
    })->create();
