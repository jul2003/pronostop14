<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\AppDateService;
use App\Services\AppSettingService;
use Illuminate\Support\Collection;

class UpcomingMatchController extends Controller
{
    public function index(AppSettingService $settings, AppDateService $dateService)
    {
        $season = Season::where('is_active', true)
            ->with([
                'journees' => function ($query) {
                    $query->where('type', '!=', 'preseason')
                        ->withCount([
                            'matches',
                            'matches as finished_matches_count' => function ($query) {
                                $query->where('is_finished', true);
                            },
                        ])
                        ->orderBy('number');
                },
            ])
            ->first();

        $journeesToPrepareCount = $settings->upcomingJourneesToPrepareCount();

        $journees = collect();

        if ($season) {
            $allJournees = $season->journees
                ->filter(fn ($journee) => $journee->expectedMatchesCount() !== null)
                ->values();

            $windowStartIndex = $this->windowStartIndex($allJournees, $dateService);

            $journees = $allJournees
                ->slice($windowStartIndex, $journeesToPrepareCount)
                ->filter(fn ($journee) => $this->journeeNeedsPreparation($journee))
                ->values();
        }

        return view('admin.upcoming-matches.index', [
            'season' => $season,
            'journees' => $journees,
            'journeesToPrepareCount' => $journeesToPrepareCount,
        ]);
    }

    private function windowStartIndex(Collection $journees, AppDateService $dateService): int
    {
        $now = $dateService->now();

        $firstPastUnfinishedJourneeIndex = $journees->search(function ($journee) use ($now) {
            if (! $journee->prediction_deadline) {
                return false;
            }

            if ($journee->prediction_deadline->gt($now)) {
                return false;
            }

            return $this->matchesAreIncomplete($journee)
                || $this->resultsAreIncomplete($journee);
        });

        if ($firstPastUnfinishedJourneeIndex !== false) {
            return (int) $firstPastUnfinishedJourneeIndex;
        }

        $firstJourneeNeedingPreparationIndex = $journees->search(function ($journee) {
            return $this->journeeNeedsPreparation($journee);
        });

        if ($firstJourneeNeedingPreparationIndex !== false) {
            return (int) $firstJourneeNeedingPreparationIndex;
        }

        return 0;
    }

    private function journeeNeedsPreparation($journee): bool
    {
        return $this->deadlineIsMissing($journee)
            || $this->matchesAreIncomplete($journee);
    }

    private function deadlineIsMissing($journee): bool
    {
        return $journee->prediction_deadline === null;
    }

    private function matchesAreIncomplete($journee): bool
    {
        $expectedMatchesCount = $journee->expectedMatchesCount();

        if ($expectedMatchesCount === null) {
            return false;
        }

        return (int) $journee->matches_count < $expectedMatchesCount;
    }

    private function resultsAreIncomplete($journee): bool
    {
        $expectedMatchesCount = $journee->expectedMatchesCount();

        if ($expectedMatchesCount === null) {
            return false;
        }

        if ((int) $journee->matches_count < $expectedMatchesCount) {
            return true;
        }

        return (int) $journee->finished_matches_count < $expectedMatchesCount;
    }
}
