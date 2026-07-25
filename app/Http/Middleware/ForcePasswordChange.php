<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        if ($request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if ($request->routeIs(
            'password.force.*',
            'logout',
            'verification.*',
            'password.confirm'
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(409, 'Le mot de passe doit être changé avant de continuer.');
        }

        return redirect()->route('password.force.edit');
    }
}
