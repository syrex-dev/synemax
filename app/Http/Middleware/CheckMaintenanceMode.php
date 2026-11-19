<?php

namespace App\Http\Middleware;

use App\Services\CinemaSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(CinemaSettings::class);

        if (! $settings->maintenanceMode()) {
            return $next($request);
        }

        // Allow admin routes, authentication, health checks, and static asset routes
        if (
            $request->is('admin*')
            || $request->is('login')
            || $request->is('logout')
            || $request->is('up')
            || $request->is('build/*')
            || $request->is('storage/*')
            || $request->is('favicon.ico')
            || $request->is('robots.txt')
            || $request->is('sw.js')
            || $request->is('*.webmanifest')
        ) {
            return $next($request);
        }

        // Clear legacy sticky preview from session if present
        if ($request->hasSession() && $request->session()->has('maintenance_preview')) {
            $request->session()->forget('maintenance_preview');
        }

        // Allow preview for logged-in admin only when explicitly requested via ?preview=1
        $isAdmin = (bool) ($request->user()?->is_admin ?? false);
        if ($isAdmin && $request->query('preview') === '1') {
            return $next($request);
        }

        $message = $settings->maintenanceMessage();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'status' => 503,
            ], 503);
        }

        try {
            $sharedData = [];
            try {
                $sharedData = app(HandleInertiaRequests::class)->share($request);
            } catch (\Throwable) {
            }

            return Inertia::render('Error', array_merge($sharedData, [
                'status' => 503,
                'message' => $message,
            ]))
                ->toResponse($request)
                ->setStatusCode(503);
        } catch (\Throwable) {
            return response()->view('errors.503', [
                'message' => $message,
            ], 503);
        }
    }
}
