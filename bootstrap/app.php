<?php

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES');
        $proxies = $trustedProxies !== null && trim($trustedProxies) !== ''
            ? array_map('trim', explode(',', $trustedProxies))
            : ['127.0.0.1', '::1'];

        $headers = Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PORT;

        $middleware->trustProxies(at: $proxies, headers: $headers);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $maxUpload = ini_get('upload_max_filesize') ?: '5M';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Berkas unggahan melebihi batas server (maksimal {$maxUpload}).",
                ], 413);
            }

            return back()->withInput()->with('error', "Berkas unggahan melebihi batas server (maksimal {$maxUpload}). Silakan pilih berkas yang lebih kecil.");
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi kedaluwarsa. Silakan muat ulang halaman.',
                ], 419);
            }

            return back()->withInput()->with('error', 'Sesi Anda telah kedaluwarsa. Silakan kirim ulang formulir.');
        });
    })->create();
