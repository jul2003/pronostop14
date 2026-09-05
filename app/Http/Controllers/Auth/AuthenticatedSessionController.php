<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if (User::count() === 0) {
            return redirect()->route(
                'home'
            );
        }

        return view(
            'auth.login'
        );
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request
    ): RedirectResponse {
        $request->authenticate();

        $request
            ->session()
            ->regenerate();

        /*
         * Aucune logique de nouveautés ici.
         *
         * Après cette redirection, la première
         * vraie page authentifiée passera par
         * ShowUnseenFeatures.
         */
        return redirect()->intended(
            route(
                'home',
                absolute: false
            )
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(
        Request $request
    ): RedirectResponse {
        Auth::guard('web')
            ->logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();

        return redirect('/');
    }
}
