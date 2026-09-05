<?php

namespace App\Http\Middleware;

use App\Models\Journee;
use App\Models\Season;
use App\Services\JourneeResultAccessService;
use Closure;
use Illuminate\Http\Request;

class BlockFutureJourneeResults
{
    private const RESULT_ROUTES = [
        'admin.seasons.active.preseason-results.edit',

        'admin.seasons.preseason-results.edit',
        'admin.seasons.preseason-results.update',

        'admin.seasons.preseason-auto-results.store',

        'admin.seasons.journees.results',
        'admin.seasons.journees.results.store',
    ];

    public function __construct(
        private readonly JourneeResultAccessService $resultAccessService
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ) {
        if (! $this->isResultRoute($request)) {
            return $next($request);
        }

        $season =
            $this->resolveSeason(
                $request
            );

        if (! $season) {
            return $next($request);
        }

        $journee =
            $this->resolveJournee(
                $request,
                $season
            );

        if (! $journee) {
            return $next($request);
        }

        if (
            $this
                ->resultAccessService
                ->canAccessResults(
                    $journee
                )
        ) {
            return $next($request);
        }

        if (
            ! $this
                ->resultAccessService
                ->hasDefinedDate(
                    $journee
                )
        ) {
            $message =
                'Les résultats de '
                .$journee->name
                .' ne sont pas accessibles tant qu’aucune date de premier match n’est définie.';
        } else {
            $availableFrom =
                $this
                    ->resultAccessService
                    ->availableFromLabel(
                        $journee
                    );

            $message =
                'Les résultats de '
                .$journee->name
                .' ne sont pas encore accessibles.';

            if ($availableFrom) {
                $message .=
                    ' Ils seront accessibles à partir du '
                    .$availableFrom
                    .'.';
            }
        }

        /*
         * Si l'utilisateur venait de
         * "Résultats à saisir", on le ramène
         * à cet endroit et non à la liste
         * générale des journées.
         */
        return $this
            ->redirectAfterBlockedAccess(
                $request,
                $season
            )
            ->with(
                'error',
                $message
            );
    }

    private function isResultRoute(
        Request $request
    ): bool {
        $routeName =
            $request
                ->route()
                ?->getName();

        if (! $routeName) {
            return false;
        }

        return in_array(
            $routeName,
            self::RESULT_ROUTES,
            true
        );
    }

    private function resolveSeason(
        Request $request
    ): ?Season {
        $season =
            $request->route(
                'season'
            );

        if ($season instanceof Season) {
            return $season;
        }

        if (
            is_string($season)
            && $season !== ''
        ) {
            return Season::where(
                'slug',
                $season
            )->first();
        }

        return Season::where(
            'is_active',
            true
        )->first();
    }

    private function resolveJournee(
        Request $request,
        Season $season
    ): ?Journee {
        $journee =
            $request->route(
                'journee'
            );

        if ($journee instanceof Journee) {
            if (
                (int) $journee->season_id
                !== (int) $season->id
            ) {
                return null;
            }

            return $journee;
        }

        if (
            is_string($journee)
            && $journee !== ''
        ) {
            return $season
                ->journees()
                ->where(
                    'slug',
                    $journee
                )
                ->first();
        }

        if (
            $this
                ->isPreseasonResultRoute(
                    $request
                )
        ) {
            return $season
                ->journees()
                ->where(
                    'type',
                    'preseason'
                )
                ->first();
        }

        return null;
    }

    private function isPreseasonResultRoute(
        Request $request
    ): bool {
        $routeName =
            $request
                ->route()
                ?->getName();

        return $routeName
            && str_contains(
                $routeName,
                'preseason'
            );
    }

    private function redirectAfterBlockedAccess(
        Request $request,
        Season $season
    ) {
        /*
         * On conserve le contexte de navigation
         * "Résultats à saisir".
         */
        if (
            $this
                ->isFromPendingResults(
                    $request
                )
        ) {
            return redirect()->route(
                'admin.pending-results.index'
            );
        }

        /*
         * Accès depuis l'administration
         * classique des journées.
         */
        return redirect()->route(
            'admin.seasons.journees',
            $season
        );
    }

    private function isFromPendingResults(
        Request $request
    ): bool {
        return
            $request->query(
                'from'
            ) === 'pending-results'
            || $request->input(
                'from'
            ) === 'pending-results';
    }
}
