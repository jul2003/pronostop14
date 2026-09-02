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
            return redirect()->route('home');
        }

        return view('auth.login');
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

        $user = $request->user();

        /*
         * Présentation unique de la nouvelle fonctionnalité
         * de notifications email.
         *
         * On ne la présente pas tant qu'un changement
         * obligatoire de mot de passe est en attente.
         */
        if (
            $user
            && ! $user->must_change_password
            && $user->notification_features_seen_at === null
        ) {
            /*
             * Le flash ne sera disponible que sur
             * la première page affichée après connexion.
             */
            $request
                ->session()
                ->flash(
                    'show_notification_features_modal',
                    true
                );

            /*
             * On mémorise que l'annonce a été présentée.
             *
             * Ainsi elle ne réapparaîtra pas à chaque
             * connexion suivante.
             */
            $user->forceFill([
                'notification_features_seen_at' =>
                    now(),
            ])->saveQuietly();
        }

        /*
         * La connexion garde son comportement normal :
         * retour vers l'URL initialement demandée
         * ou vers l'accueil.
         *
         * La modale s'affichera par-dessus cette page.
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
