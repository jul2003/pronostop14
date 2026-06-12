<?php

namespace App\Http\Controllers;

use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\Prono;
use App\Models\Season;
use Illuminate\Http\Request;

class PronoController extends Controller
{
    public function index()
    {
        $season = Season::where('is_active', true)->firstOrFail();

        $journees = Journee::with('season')
            ->withCount('matches')
            ->whereHas('season', fn ($query) => $query->where('is_active', true))
            //Mise en commentaire de ces 2 filtres pour saisie de l'historique
            //->whereNotNull('prediction_deadline')
            //->where('prediction_deadline', '>', now())
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
            ->get()
            ->filter(fn ($journee) => $journee->hasExpectedMatchesCount())
            ->values();

        return view('pronos.journees', [
            'season' => $season,
            'journees' => $journees,
        ]);
    }

    public function show(Season $season, Journee $journee)
    {
        if ($journee->season_id !== $season->id) {
            abort(404);
        }

        $matches = MatchGame::with([
            'homeClub',
            'awayClub',
            'journee',
            'pronos' => fn ($query) => $query->where('user_id', auth()->id()),
        ])
            ->where('journee_id', $journee->id)
            ->orderBy('position')
            ->get();

        return view('pronos.index', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'isLocked' => $journee->isLocked(),
        ]);
    }

    public function storeAll(Request $request, Season $season, Journee $journee)
    {
        if ($journee->season_id !== $season->id) {
            abort(404);
        }

        if ($journee->isLocked()) {
            abort(403);
        }

        $data = $request->validate([
            'pronos' => ['required', 'array'],
            'pronos.*.predicted_result' => ['required', 'in:v,n,d'],
            'pronos.*.predicted_tries' => ['required', 'integer', 'min:0'],
            'pronos.*.predicted_home_bonus' => ['nullable', 'in:o,-,d'],
            'pronos.*.predicted_away_bonus' => ['nullable', 'in:o,-,d'],
        ]);

        foreach ($data['pronos'] as $matchId => $pronoData) {
            $match = MatchGame::where('journee_id', $journee->id)
                ->where('id', $matchId)
                ->firstOrFail();

            Prono::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'match_game_id' => $match->id,
                ],
                [
                    'predicted_result' => $pronoData['predicted_result'],
                    'predicted_tries' => $pronoData['predicted_tries'],
                    'predicted_home_bonus' => $pronoData['predicted_home_bonus'] ?? null,
                    'predicted_away_bonus' => $pronoData['predicted_away_bonus'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('pronos.show', [$season, $journee])
            ->with('success', 'Pronostics enregistrés.');
    }
}
