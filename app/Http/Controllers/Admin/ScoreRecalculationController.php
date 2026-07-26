<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\ScoringService;
use Illuminate\Http\RedirectResponse;

class ScoreRecalculationController extends Controller
{
    public function __invoke(ScoringService $scoringService): RedirectResponse
    {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            return redirect()
                ->route('admin.index')
                ->with('error', 'Aucune saison active à recalculer.');
        }

        $scoringService->recalculateRegularJourneeScores($season);

        return redirect()
            ->route('admin.index')
            ->with('success', 'Scores recalculés pour la saison '.$season->name.'.');
    }
}
