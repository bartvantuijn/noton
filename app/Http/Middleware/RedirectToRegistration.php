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
        if (!App::hasUsers() && !$request->routeIs('filament.admin.auth.register')) {
            return redirect()->route('filament.admin.auth.register');
        }

        return $next($request);
    }
}
