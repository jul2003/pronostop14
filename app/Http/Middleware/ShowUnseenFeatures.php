<?php

namespace App\Http\Middleware;

use App\Services\FeatureAnnouncementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShowUnseenFeatures
{
    public function __construct(
        private readonly FeatureAnnouncementService $featureAnnouncements
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        /*
         * Pas d'utilisateur connecté :
         * rien à faire.
         */
        if (! $user) {
            return $next($request);
        }

        /*
         * Un changement obligatoire de mot de passe
         * reste prioritaire sur les nouveautés.
         */
        if ($user->must_change_password) {
            return $next($request);
        }

        /*
         * En mode reprise historique / impersonation,
         * on ne doit surtout pas marquer les nouveautés
         * comme vues à la place du joueur.
         */
        if (
            $request
                ->session()
                ->has('impersonator_id')
        ) {
            return $next($request);
        }

        /*
         * La modale doit être présentée uniquement
         * sur une vraie page GET HTML.
         *
         * On évite ainsi :
         * - les POST ;
         * - les requêtes AJAX / JSON ;
         * - les actions techniques.
         */
        if (
            ! $request->isMethod('GET')
            || $request->expectsJson()
        ) {
            return $next($request);
        }

        /*
         * Recherche générique des features
         * disponibles et jamais vues.
         */
        $features =
            $this
                ->featureAnnouncements
                ->unseenFor(
                    $user
                );

        if ($features->isEmpty()) {
            return $next($request);
        }

        /*
         * Le payload correspond exactement
         * au contenu qui sera :
         *
         * - affiché dans la modale ;
         * - sauvegardé dans feature_user.snapshot.
         */
        $payloads =
            $this
                ->featureAnnouncements
                ->payloadFor(
                    $features
                );

        /*
         * On partage les données avec les vues
         * uniquement pour cette requête.
         *
         * Contrairement à session()->flash(),
         * la modale ne risque donc pas de
         * réapparaître sur la page suivante.
         */
        View::share(
            'loginFeatureAnnouncements',
            $payloads
        );

        /*
         * On laisse Laravel générer réellement
         * la page.
         */
        $response =
            $next($request);

        /*
         * On ne considère la feature comme vue
         * que si une vraie page HTML a bien
         * été rendue avec succès.
         *
         * Une redirection, une erreur ou une
         * réponse non HTML ne consomme donc
         * jamais la nouveauté.
         */
        $contentType =
            strtolower(
                (string)
                $response
                    ->headers
                    ->get('Content-Type')
            );

        $isHtmlResponse =
            str_contains(
                $contentType,
                'text/html'
            );

        if (
            $response->isSuccessful()
            && $isHtmlResponse
        ) {
            $this
                ->featureAnnouncements
                ->markSeen(
                    $user,
                    $features,
                    $payloads
                );
        }

        return $response;
    }
}
