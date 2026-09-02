<?php

namespace App\Services;

use App\Models\Journee;
use Carbon\Carbon;

class JourneeResultAccessService
{
    public function __construct(
        private readonly AppDateService $appDateService
    ) {
    }

    public function hasDefinedDate(
        Journee $journee
    ): bool {
        return filled(
            $journee->first_match_at
        );
    }

    public function isFuture(
        Journee $journee
    ): bool {
        if (! $this->hasDefinedDate($journee)) {
            return false;
        }

        $journeeDate = Carbon::parse(
            $journee->first_match_at
        )->startOfDay();

        $currentDate = $this
            ->appDateService
            ->now()
            ->copy()
            ->startOfDay();

        return $journeeDate->gt(
            $currentDate
        );
    }

    public function canAccessResults(
        Journee $journee
    ): bool {
        if (! $this->hasDefinedDate($journee)) {
            return false;
        }

        return ! $this->isFuture(
            $journee
        );
    }

    public function availableFromLabel(
        Journee $journee
    ): ?string {
        if (! $this->hasDefinedDate($journee)) {
            return null;
        }

        return Carbon::parse(
            $journee->first_match_at
        )->format('d/m/Y');
    }
}
