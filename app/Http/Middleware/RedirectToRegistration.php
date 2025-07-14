<?php

namespace App\Http\Middleware;

use App\Helpers\App;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Redirect to registration
        if (!App::hasUsers() && !$request->routeIs('filament.admin.auth.register')) {
            return redirect()->route('filament.admin.auth.register');
        }

        // Redirect from registration
        if (App::hasUsers() && $request->routeIs('filament.admin.auth.register')) {
            return redirect()->route('filament.admin.auth.login');
        }

        return $next($request);
    }
}
