<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 🔐 Paksa API selalu JSON
        $exceptions->shouldRenderJsonWhen(
            fn($request) =>
            $request->is('api/*') || $request->expectsJson()
        );

        // ✅ Unauthenticated (kalau sempat)
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
                'data' => []
            ], 401);
        });

        // 🚨 FIX UTAMA: tangkap redirect login
        $exceptions->render(function (RouteNotFoundException $e, $request) {
            if (str_contains($e->getMessage(), '[login]')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                    'data' => []
                ], 401);
            }

            throw $e;
        });
    })
    ->create();
