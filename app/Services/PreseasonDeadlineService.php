<?php

namespace App\Services;

use App\Models\Season;
use App\Models\User;
use Carbon\Carbon;

class PreseasonDeadlineService
{
    public function deadlineForUser(Season $season, User $user): ?Carbon
    {
        $player = $season->players()
            ->where('users.id', $user->id)
            ->first();

        $personalDeadline = $player?->pivot?->preseason_prediction_deadline;

        if ($personalDeadline) {
            return Carbon::parse($personalDeadline);
        }

        return $this->preseasonJourneeDeadline($season)
            ?? $this->firstRegularJourneeDeadline($season);
    }

    public function isLockedForUser(Season $season, User $user): bool
    {
        $deadline = $this->deadlineForUser($season, $user);

        if (! $deadline) {
            return false;
        }

        return $deadline->lte(app(AppDateService::class)->now());
    }

    public function deadlineForNewParticipant(Season $season): ?Carbon
    {
        if (! $this->seasonHasStarted($season)) {
            return null;
        }

        $now = app(AppDateService::class)->now();

        $upcomingJournee = $season->journees()
            ->where('type', '!=', 'preseason')
            ->whereNotNull('prediction_deadline')
            ->where('prediction_deadline', '>', $now)
            ->orderBy('prediction_deadline')
            ->orderBy('number')
            ->first();

        return $upcomingJournee?->prediction_deadline;
    }

    public function seasonHasStarted(Season $season): bool
    {
        $firstDeadline = $this->firstRegularJourneeDeadline($season);

        if (! $firstDeadline) {
            return false;
        }

        return $firstDeadline->lte(app(AppDateService::class)->now());
    }

    private function preseasonJourneeDeadline(Season $season): ?Carbon
    {
        $journee = $season->journees()
            ->where('type', 'preseason')
            ->first();

        return $journee?->prediction_deadline;
    }

    private function firstRegularJourneeDeadline(Season $season): ?Carbon
    {
        $journee = $season->journees()
            ->where('type', 'regular')
            ->where('number', 1)
            ->first();

        return $journee?->prediction_deadline;
    }
}
