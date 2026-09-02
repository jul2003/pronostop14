<?php

namespace App\Observers;

use App\Models\MatchGame;
use App\Services\PredictionAnnouncementService;

class MatchGameObserver
{
    public function created(
        MatchGame $match
    ): void {
        $journee =
            $match->journee;

        if (! $journee) {
            return;
        }

        app(
            PredictionAnnouncementService::class
        )->sendIfReady(
            $journee
        );
    }
}
