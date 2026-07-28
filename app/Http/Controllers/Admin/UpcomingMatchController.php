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

            $windowStartIndex = $this->windowStartIndex(
                $allJournees,
                $journeesToPrepareCount,
                $dateService
            );

            $journees = $allJournees
                ->slice($windowStartIndex, $journeesToPrepareCount)
                ->values();
        }

        return view('admin.upcoming-matches.index', [
            'season' => $season,
            'journees' => $journees,
            'journeesToPrepareCount' => $journeesToPrepareCount,
        ]);
    }

    private function windowStartIndex(
        Collection $journees,
        int $journeesToPrepareCount,
        AppDateService $dateService
    ): int {
        if ($journees->isEmpty()) {
            return 0;
        }

        $now = $dateService->now();

        $firstUpcomingJourneeIndex = $journees->search(function ($journee) use ($now) {
            if (! $journee->first_match_at) {
                return true;
            }

            return $journee->first_match_at->greaterThan($now);
        });

        if ($firstUpcomingJourneeIndex !== false) {
            return (int) $firstUpcomingJourneeIndex;
        }

        return max($journees->count() - $journeesToPrepareCount, 0);
    }
}
