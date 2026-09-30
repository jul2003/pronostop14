<?php

namespace App\Services;

use App\Models\Journee;
use App\Notifications\ResultsAvailableNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ResultAnnouncementService
{
    public function __construct(
        private readonly ScoringService $scoringService
    ) {
    }

    public function sendIfComplete(
        Journee|int $journee
    ): bool {
        $journeeId =
            $journee instanceof Journee
                ? $journee->getKey()
                : $journee;

        $journee = Journee::query()
            ->with('season')
            ->find($journeeId);

        if (! $journee) {
            return false;
        }

        if (
            $journee
                ->result_announcement_sent_at
        ) {
            return false;
        }

        $expectedMatches =
            $journee->expectedMatchesCount();

        /*
         * L'avant-saison n'est pas constituée de MatchGame.
         */
        if (
            $expectedMatches === null
            || $expectedMatches < 1
        ) {
            return false;
        }

        $matchesCount =
            $journee
                ->matches()
                ->count();

        if (
            $matchesCount
            !== $expectedMatches
        ) {
            return false;
        }

        $unfinishedMatches =
            $journee
                ->matches()
                ->whereNull(
                    'actual_result'
                )
                ->count();

        if ($unfinishedMatches > 0) {
            return false;
        }

        try {
            /*
             * Le service peut être appelé par l'observer du dernier match,
             * avant la fin du recalcul effectué par le contrôleur.
             * On garantit donc ici que le classement envoyé est à jour.
             */
            $this->scoringService
                ->recalculateJourneeScores(
                    $journee
                );

            $seasonPlayerIds = $journee
                ->season
                ->players()
                ->pluck(
                    'users.id'
                );

            $ranking = $journee
                ->userScores()
                ->with('user')
                ->whereIn(
                    'user_id',
                    $seasonPlayerIds
                )
                ->orderBy('rank')
                ->orderByDesc('total_points')
                ->orderBy('user_id')
                ->get();

            $players = $journee
                ->season
                ->players()
                ->where(
                    'users.is_active',
                    true
                )
                ->where(
                    'users.notify_results_available',
                    true
                )
                ->whereNotNull(
                    'users.email'
                )
                ->where(
                    'users.email',
                    '<>',
                    ''
                )
                ->get();

            if ($players->isNotEmpty()) {
                Notification::send(
                    $players,
                    new ResultsAvailableNotification(
                        $journee->season,
                        $journee,
                        $ranking
                    )
                );
            }
        } catch (Throwable $exception) {
            Log::error(
                'Échec de la notification de résultats.',
                [
                    'journee_id' =>
                        $journee->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return false;
        }

        $journee->forceFill([
            'result_announcement_sent_at' =>
                now(),
        ])->saveQuietly();

        return true;
    }
}
