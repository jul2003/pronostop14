<?php

namespace App\Observers;

use App\Models\MatchGame;
use App\Services\PredictionAnnouncementService;
use App\Services\ResultAnnouncementService;

class MatchGameObserver
{
    public function saved(
        MatchGame $match
    ): void {
        $match->loadMissing(
            'journee'
        );

        if (! $match->journee) {
            return;
        }

        /*
         * Le 7e match peut rendre le prono disponible.
         */
        app(
            PredictionAnnouncementService::class
        )->sendIfReady(
            $match->journee
        );

        /*
         * Le dernier résultat peut rendre la journée
         * complètement terminée.
         */
        app(
            ResultAnnouncementService::class
        )->sendIfComplete(
            $match->journee
        );
    }
}
