<?php

use Filament\Notifications\Notification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! $request->routeIs('filament.admin.*')) {
                return $response;
            }

            if ($request->routeIs('filament.admin.auth.*')) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), [403, 404], true)) {
                return $response;
            }

            Notification::make()
                ->warning()
                ->title(__('This page is no longer available.'))
                ->send();

            return new RedirectResponse(url('/'));
        });
    })->create();
