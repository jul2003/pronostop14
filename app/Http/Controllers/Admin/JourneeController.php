<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\Season;
use App\Services\AppDateService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JourneeController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.seasons.index');
    }

    public function season(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        $journees = $season->journees()
            ->orderByRaw("
                CASE
                    WHEN type = 'preseason' THEN 0
                    WHEN type = 'regular' THEN number
                    WHEN type = 'prod2_final' THEN 100
                    WHEN type = 'access_match' THEN 101
                    WHEN type = 'top14_playoff' THEN 102
                    WHEN type = 'top14_semifinal' THEN 103
                    WHEN type = 'top14_final' THEN 104
                    ELSE 999
                END
            ")
            ->get();

        return view('admin.journees.season', [
            'season' => $season,
            'journees' => $journees,
        ]);
    }

    public function edit(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.journees', $season)
                ->with('error', 'Cette saison est verrouillée : les journées ne peuvent plus être modifiées.');
        }

        if ($this->preparationIsLocked($journee)) {
            return redirect()
                ->route('admin.seasons.journees', $season)
                ->with('error', 'Cette journée a commencé : seuls les résultats restent accessibles.');
        }

        return view('admin.journees.edit', [
            'season' => $season,
            'journee' => $journee,
        ]);
    }

    public function update(Request $request, Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.journees', $season)
                ->with('error', 'Cette saison est verrouillée : les journées ne peuvent plus être modifiées.');
        }

        if ($this->preparationIsLocked($journee)) {
            return redirect()
                ->route('admin.seasons.journees', $season)
                ->with('error', 'Cette journée a commencé : seuls les résultats restent accessibles.');
        }

        $data = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'prediction_deadline' => ['nullable', 'date'],
        ]);

        $journee->update([
            'starts_at' => $request->filled('starts_at')
                ? Carbon::parse($data['starts_at'])->startOfDay()
                : null,

            'prediction_deadline' => $request->filled('prediction_deadline')
                ? Carbon::parse($data['prediction_deadline'])->endOfDay()
                : null,
        ]);

        return redirect()
            ->route('admin.seasons.journees', $season)
            ->with('success', 'Journée mise à jour.');
    }

    private function preparationIsLocked(Journee $journee): bool
    {
        if (! $journee->starts_at) {
            return false;
        }

        $currentAppDate = app(AppDateService::class)
            ->now()
            ->copy()
            ->startOfDay();

        $journeeStartDate = $journee->starts_at
            ->copy()
            ->startOfDay();

        return $currentAppDate->greaterThanOrEqualTo($journeeStartDate);
    }

    private function resolveSeason(?Season $season = null): Season
    {
        if ($season) {
            return $season;
        }

        return Season::where('is_active', true)->firstOrFail();
    }
}
