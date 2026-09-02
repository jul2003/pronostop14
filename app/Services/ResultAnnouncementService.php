<?php

namespace App\Services;

use App\Models\Journee;
use App\Notifications\ResultsAvailableNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ResultAnnouncementService
{
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

        try {
            if ($players->isNotEmpty()) {
                Notification::send(
                    $players,
                    new ResultsAvailableNotification(
                        $journee->season,
                        $journee
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
