<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\Season;
use App\Services\AppSettingService;
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
            ->withCount([
                'matches',
                'matches as finished_matches_count' => function ($query) {
                    $query->where('is_finished', true);
                },
            ])
            ->orderByRaw("
                CASE
                    WHEN type = 'preseason' THEN 0
                    WHEN type = 'regular' THEN number
                    WHEN type = 'top14_playoff' THEN 100
                    WHEN type = 'access_match' THEN 101
                    WHEN type = 'top14_semifinal' THEN 102
                    WHEN type = 'prod2_final' THEN 103
                    WHEN type = 'top14_final' THEN 104
                    ELSE 999
                END
            ")
            ->get();

        $journees->each(function (Journee $journee) use ($season) {
            $journee->setRelation('season', $season);
        });

        return view('admin.journees.season', [
            'season' => $season,
            'journees' => $journees,
        ]);
    }

    public function edit(Season $season, Journee $journee, AppSettingService $settings)
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

        $suggestedFirstMatchSourceJournee = $this->previousJourneeForFirstMatchSuggestion($season, $journee);
        $suggestedFirstMatchAt = null;

        if (! $journee->first_match_at && $suggestedFirstMatchSourceJournee?->first_match_at) {
            $suggestedFirstMatchAt = $suggestedFirstMatchSourceJournee->first_match_at
                ->copy()
                ->addDays(7);
        }

        return view('admin.journees.edit', [
            'season' => $season,
            'journee' => $journee,
            'defaultFirstMatchTime' => $settings->defaultFirstMatchTime(),
            'suggestedFirstMatchAt' => $suggestedFirstMatchAt,
            'suggestedFirstMatchSourceJournee' => $suggestedFirstMatchSourceJournee,
        ]);
    }

    public function update(
        Request $request,
        Season $season,
        Journee $journee,
        AppSettingService $settings
    ) {
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
            'first_match_date' => ['nullable', 'date'],
            'first_match_time' => ['nullable', 'date_format:H:i'],
            'predictions_enabled' => ['nullable', 'boolean'],
        ]);

        $firstMatchAt = null;

        if ($request->filled('first_match_date')) {
            $time = $request->filled('first_match_time')
                ? $data['first_match_time']
                : $settings->defaultFirstMatchTime();

            $firstMatchAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $data['first_match_date'].' '.$time
            );
        }

        $journee->update([
            'first_match_at' => $firstMatchAt,
            'predictions_enabled' => $request->boolean('predictions_enabled'),
        ]);

        return redirect()
            ->route('admin.seasons.journees', $season)
            ->with('success', 'Journée mise à jour.');
    }

    private function previousJourneeForFirstMatchSuggestion(Season $season, Journee $journee): ?Journee
    {
        if ($journee->type === 'preseason') {
            return null;
        }

        if ((int) $journee->number <= 1) {
            return null;
        }

        if ($journee->type === 'regular') {
            return $season->journees()
                ->where('type', 'regular')
                ->where('number', (int) $journee->number - 1)
                ->first();
        }

        return $season->journees()
            ->where('id', '!=', $journee->id)
            ->where('type', '!=', 'preseason')
            ->where('number', (int) $journee->number - 1)
            ->first();
    }

    private function preparationIsLocked(Journee $journee): bool
    {
        return $journee->isPreparationLocked();
    }

    private function resolveSeason(?Season $season = null): Season
    {
        if ($season) {
            return $season;
        }

        return Season::where('is_active', true)->firstOrFail();
    }
}
