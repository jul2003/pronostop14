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
         * Les nouveautés ne prennent jamais
         * la priorité sur un changement
         * obligatoire de mot de passe.
         */
        if (
            $user
            && ! $user
                ->must_change_password
        ) {
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
                 * Un unique payload sert :
                 *
                 * 1. à la modale affichée maintenant ;
                 * 2. au snapshot historique.
                 *
                 * Les deux contenus sont donc
                 * strictement identiques.
                 */
                $payloads =
                    $featureAnnouncements
                        ->payloadFor(
                            $features
                        );

                $request
                    ->session()
                    ->flash(
                        'login_feature_announcements',
                        $payloads
                    );

                $featureAnnouncements
                    ->markSeen(
                        $user,
                        $features,
                        $payloads
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
