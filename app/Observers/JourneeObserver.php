<?php

namespace App\Observers;

use App\Models\Journee;
use App\Services\PredictionAnnouncementService;

class JourneeObserver
{
    public function saved(
        Journee $journee
    ): void {
        app(
            PredictionAnnouncementService::class
        )->sendIfReady(
            $journee
        );
    }
}
