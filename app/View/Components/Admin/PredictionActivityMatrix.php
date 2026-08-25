<?php

namespace App\View\Components\Admin;

use App\Models\Season;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class PredictionActivityMatrix extends Component
{
    public function __construct(
        public Season $season
    ) {
    }

    public function render(): View
    {
        /*
         * Les colonnes correspondent uniquement aux joueurs
         * réellement inscrits à cette saison.
         *
         * Season::players() applique déjà display_order.
         */
        $players = $this->season
            ->players()
            ->get();

        /*
         * Toutes les journées de la saison sont affichées,
         * même lorsqu'aucun match n'a encore été créé.
         *
         * Le champ number porte désormais l'ordre fonctionnel :
         *
         * Avant-saison
         * J1 à J26
         * Finale PRO D2
         * Access Match
         * Barrages TOP 14
         * Demi-finales TOP 14
         * Finale TOP 14
         */
        $journees = $this->season
            ->journees()
            ->reorder()
            ->orderBy('number')
            ->orderBy('id')
            ->get();

        $activity = [];

        if ($players->isNotEmpty()) {
            $playerIds = $players
                ->pluck('id')
                ->values();

            /*
             * Pronostics des matchs.
             *
             * Pour chaque combinaison journée / joueur,
             * on conserve le updated_at le plus récent parmi
             * les pronostics encore présents.
             */
            $matchActivities = DB::table('pronos')
                ->join(
                    'match_games',
                    'match_games.id',
                    '=',
                    'pronos.match_game_id'
                )
                ->join(
                    'journees',
                    'journees.id',
                    '=',
                    'match_games.journee_id'
                )
                ->where(
                    'journees.season_id',
                    $this->season->id
                )
                ->whereIn(
                    'pronos.user_id',
                    $playerIds
                )
                ->select([
                    'journees.id as journee_id',
                    'pronos.user_id',
                ])
                ->selectRaw(
                    'MAX(pronos.updated_at) as last_saved_at'
                )
                ->groupBy(
                    'journees.id',
                    'pronos.user_id'
                )
                ->get();

            foreach ($matchActivities as $matchActivity) {
                $lastSavedAt = $this->parseDate(
                    $matchActivity->last_saved_at
                );

                if (! $lastSavedAt) {
                    continue;
                }

                $activity[
                    (int) $matchActivity->journee_id
                ][
                    (int) $matchActivity->user_id
                ] = $lastSavedAt;
            }

            /*
             * Pronostics avant-saison.
             *
             * submitted_at représente précisément la dernière
             * soumission d'une réponse avant-saison.
             *
             * updated_at reste utilisé en secours pour les
             * éventuelles anciennes données.
             */
            $preseasonJournee = $journees
                ->firstWhere(
                    'type',
                    'preseason'
                );

            if ($preseasonJournee) {
                $preseasonActivities = DB::table(
                    'season_preseason_predictions'
                )
                    ->where(
                        'season_id',
                        $this->season->id
                    )
                    ->whereIn(
                        'user_id',
                        $playerIds
                    )
                    ->select('user_id')
                    ->selectRaw(
                        'MAX(COALESCE(submitted_at, updated_at)) as last_saved_at'
                    )
                    ->groupBy('user_id')
                    ->get();

                foreach (
                    $preseasonActivities
                    as $preseasonActivity
                ) {
                    $lastSavedAt = $this->parseDate(
                        $preseasonActivity
                            ->last_saved_at
                    );

                    if (! $lastSavedAt) {
                        continue;
                    }

                    $activity[
                        $preseasonJournee->id
                    ][
                        (int) $preseasonActivity
                            ->user_id
                    ] = $lastSavedAt;
                }
            }
        }

        return view(
            'components.admin.prediction-activity-matrix',
            [
                'players' => $players,
                'journees' => $journees,
                'activity' => $activity,
            ]
        );
    }

    private function parseDate(
        ?string $value
    ): ?Carbon {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(
                config(
                    'app.timezone',
                    'Europe/Paris'
                )
            );
    }
}
