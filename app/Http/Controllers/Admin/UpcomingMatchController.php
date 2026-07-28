<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\Season;
use App\Services\AppDateService;
use App\Services\AppSettingService;
use Illuminate\Http\Request;
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

    public function updatePredictionsEnabled(Request $request, Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);
        abort_if($journee->type === 'preseason', 404);

        if ($season->is_locked) {
            return redirect()
                ->route('admin.upcoming-matches.index')
                ->with('error', 'Cette saison est verrouillée : la saisie des pronostics ne peut plus être modifiée.');
        }

        $request->validate([
            'predictions_enabled' => ['required', 'boolean'],
        ]);

        $journee->update([
            'predictions_enabled' => $request->boolean('predictions_enabled'),
        ]);

        return redirect()
            ->route('admin.upcoming-matches.index')
            ->with(
                'success',
                $journee->name.' : saisie des pronostics '.($journee->predictions_enabled ? 'activée.' : 'désactivée.')
            );
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
