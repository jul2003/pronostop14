<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\FeatureAnnouncementService;
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
        LoginRequest $request,
        FeatureAnnouncementService $featureAnnouncements
    ): RedirectResponse {
        $request->authenticate();

        $request
            ->session()
            ->regenerate();

        $user =
            $request->user();

        /*
         * Les nouveautés ne doivent pas prendre
         * la priorité sur un changement obligatoire
         * de mot de passe.
         */
        if (
            $user
            && ! $user
                ->must_change_password
        ) {
            /*
             * Cette recherche est désormais
             * entièrement générique.
             *
             * Aucune clé de feature n'est codée
             * en dur dans ce contrôleur.
             */
            $features =
                $featureAnnouncements
                    ->unseenFor(
                        $user
                    );

            if (
                $features
                    ->isNotEmpty()
            ) {
                /*
                 * La page suivante saura quelles
                 * nouveautés afficher.
                 */
                $request
                    ->session()
                    ->flash(
                        'login_feature_ids',
                        $features
                            ->modelKeys()
                    );

                /*
                 * Création automatique dans
                 * feature_user.
                 */
                $featureAnnouncements
                    ->markSeen(
                        $user,
                        $features
                    );
            }
        }

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
