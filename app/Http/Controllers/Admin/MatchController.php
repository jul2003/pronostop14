<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\Prono;
use App\Models\Season;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.seasons.index');
    }

    public function manage(Season $season, Journee $journee)
    {
        $this->ensureJourneeBelongsToSeason($season, $journee);

        $journee->load([
            'matches.homeClub',
            'matches.awayClub',
        ]);

        $matches = $journee->matches()
            ->with(['homeClub', 'awayClub'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $usedClubIds = $matches
            ->flatMap(fn ($match) => [
                $match->home_club_id,
                $match->away_club_id,
            ])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $clubs = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->orderBy('name')
            ->get();

        return view('admin.matches.manage', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'clubs' => $clubs,
            'usedClubIds' => $usedClubIds,
        ]);
    }

    public function store(Request $request, Season $season, Journee $journee)
    {
        $this->ensureJourneeBelongsToSeason($season, $journee);

        if ($season->is_locked) {
            return back()->withErrors([
                'season' => 'Cette saison est verrouillée : les matchs ne peuvent plus être modifiés.',
            ]);
        }

        if ($journee->isLocked()) {
            return back()->withErrors([
                'journee' => 'Cette journée est verrouillée.',
            ]);
        }

        $data = $request->validate([
            'home_club_id' => ['required', 'integer', 'exists:clubs,id'],
            'away_club_id' => ['required', 'integer', 'exists:clubs,id', 'different:home_club_id'],
        ]);

        $top14ClubIds = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->pluck('clubs.id')
            ->toArray();

        if (
            ! in_array((int) $data['home_club_id'], $top14ClubIds, true)
            || ! in_array((int) $data['away_club_id'], $top14ClubIds, true)
        ) {
            return back()->withErrors([
                'clubs' => 'Les clubs sélectionnés doivent appartenir au TOP 14 de cette saison.',
            ]);
        }

        $clubAlreadyUsed = MatchGame::where('journee_id', $journee->id)
            ->where(function ($query) use ($data) {
                $query->whereIn('home_club_id', [
                    $data['home_club_id'],
                    $data['away_club_id'],
                ])->orWhereIn('away_club_id', [
                    $data['home_club_id'],
                    $data['away_club_id'],
                ]);
            })
            ->exists();

        if ($clubAlreadyUsed) {
            return back()->withErrors([
                'clubs' => 'Un des deux clubs est déjà utilisé sur cette journée.',
            ]);
        }

        $nextPosition = ((int) MatchGame::where('journee_id', $journee->id)->max('position')) + 1;

        MatchGame::create([
            'journee_id' => $journee->id,
            'home_club_id' => $data['home_club_id'],
            'away_club_id' => $data['away_club_id'],
            'position' => $nextPosition,
        ]);

        return redirect()
            ->route('admin.seasons.journees.matches', [$season, $journee])
            ->with('success', 'Match ajouté.');
    }

    public function destroy(MatchGame $match)
    {
        $match->load('journee.season');

        if ($match->journee?->season?->is_locked) {
            return back()->withErrors([
                'season' => 'Cette saison est verrouillée : les matchs ne peuvent plus être modifiés.',
            ]);
        }

        if ($match->journee?->isLocked()) {
            return back()->withErrors([
                'journee' => 'Cette journée est verrouillée.',
            ]);
        }

        $match->delete();

        return back()->with('success', 'Match supprimé.');
    }

    public function results(Season $season, Journee $journee)
    {
        $this->ensureJourneeBelongsToSeason($season, $journee);

        $matches = $journee->matches()
            ->with(['homeClub', 'awayClub'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view('admin.matches.results', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
        ]);
    }

    public function storeResults(
        Request $request,
        Season $season,
        Journee $journee,
        ScoringService $scoringService
    ) {
        $this->ensureJourneeBelongsToSeason($season, $journee);

        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.journees.results', [$season, $journee])
                ->with('error', 'Cette saison est verrouillée : les résultats ne peuvent plus être modifiés.');
        }

        $data = $request->validate([
            'matches' => ['nullable', 'array'],

            'matches.*.actual_result' => ['nullable', Rule::in($journee->allowedResultOptions())],
            'matches.*.actual_tries' => ['nullable', 'integer', 'min:0'],
            'matches.*.actual_home_bonus' => ['nullable', 'in:o,-,d'],
            'matches.*.actual_away_bonus' => ['nullable', 'in:o,-,d'],
        ]);

        foreach ($data['matches'] ?? [] as $matchId => $matchData) {
            $match = MatchGame::where('journee_id', $journee->id)
                ->where('id', $matchId)
                ->firstOrFail();

            $hasResult = ! empty($matchData['actual_result']);

            if (! $hasResult) {
                $match->update([
                    'actual_result' => null,
                    'actual_tries' => null,
                    'actual_home_bonus' => null,
                    'actual_away_bonus' => null,
                    'is_finished' => false,
                ]);
            } else {
                $match->update([
                    'actual_result' => $matchData['actual_result'],
                    'actual_tries' => $matchData['actual_tries'] ?? null,
                    'actual_home_bonus' => $matchData['actual_home_bonus'] ?? null,
                    'actual_away_bonus' => $matchData['actual_away_bonus'] ?? null,
                    'is_finished' => true,
                ]);
            }

            $match->refresh();
            $match->load('journee.season.scoringRules', 'pronos.user');

            foreach ($match->pronos as $prono) {
                $prono->update([
                    'points' => $scoringService->calculateMatchPoints($prono, $match),
                ]);
            }

            foreach ($match->pronos as $prono) {
                $scoringService->updateJourneeUserScore(
                    $prono->user,
                    $journee
                );
            }
        }

        $scoringService->updateJourneeRanking($journee);

        return redirect()
            ->route('admin.seasons.journees.results', [$season, $journee])
            ->with('success', 'Résultats enregistrés.');
    }

    public function reorder(Request $request, Season $season, Journee $journee)
    {
        $this->ensureJourneeBelongsToSeason($season, $journee);

        if ($season->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Cette saison est verrouillée : les matchs ne peuvent plus être réordonnés.',
            ], 403);
        }

        if ($journee->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette journée est verrouillée.',
            ], 403);
        }

        $data = $request->validate([
            'matches' => ['required', 'array'],
            'matches.*' => ['integer', 'exists:match_games,id'],
        ]);

        foreach ($data['matches'] as $index => $matchId) {
            MatchGame::where('journee_id', $journee->id)
                ->where('id', $matchId)
                ->update([
                    'position' => $index + 1,
                ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function storeBulk(Request $request, Season $season, Journee $journee)
    {
        $this->ensureJourneeBelongsToSeason($season, $journee);

        if ($season->is_locked) {
            return back()->withErrors([
                'season' => 'Cette saison est verrouillée : les matchs ne peuvent plus être modifiés.',
            ]);
        }

        if ($journee->isLocked()) {
            return back()->withErrors([
                'journee' => 'Cette journée est verrouillée.',
            ]);
        }

        $data = $request->validate([
            'clubs' => ['required', 'array', 'min:2'],
            'clubs.*' => ['integer', 'exists:clubs,id'],
        ]);

        $clubIds = array_map('intval', $data['clubs']);

        if (count($clubIds) % 2 !== 0) {
            return back()->withErrors([
                'clubs' => 'Le nombre de clubs sélectionnés doit être pair.',
            ]);
        }

        if (count($clubIds) !== count(array_unique($clubIds))) {
            return back()->withErrors([
                'clubs' => 'Un club ne peut pas être utilisé deux fois.',
            ]);
        }

        $top14ClubIds = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->pluck('clubs.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($clubIds as $clubId) {
            if (! in_array($clubId, $top14ClubIds, true)) {
                return back()->withErrors([
                    'clubs' => 'Tous les clubs sélectionnés doivent appartenir au TOP 14 de cette saison.',
                ]);
            }
        }

        $alreadyUsedClubIds = MatchGame::where('journee_id', $journee->id)
            ->get()
            ->flatMap(fn ($match) => [
                (int) $match->home_club_id,
                (int) $match->away_club_id,
            ])
            ->unique()
            ->toArray();

        foreach ($clubIds as $clubId) {
            if (in_array($clubId, $alreadyUsedClubIds, true)) {
                return back()->withErrors([
                    'clubs' => 'Un des clubs sélectionnés est déjà utilisé sur cette journée.',
                ]);
            }
        }

        $nextPosition = ((int) MatchGame::where('journee_id', $journee->id)->max('position')) + 1;

        foreach (array_chunk($clubIds, 2) as $pair) {
            MatchGame::create([
                'journee_id' => $journee->id,
                'home_club_id' => $pair[0],
                'away_club_id' => $pair[1],
                'position' => $nextPosition,
            ]);

            $nextPosition++;
        }

        return redirect()
            ->route('admin.seasons.journees.matches', [$season, $journee])
            ->with('success', 'Matchs ajoutés.');
    }

    private function ensureJourneeBelongsToSeason(Season $season, Journee $journee): void
    {
        if ($journee->season_id !== $season->id) {
            abort(404);
        }
    }
}
