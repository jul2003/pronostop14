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

        $data = $request->validate([
            'matches' => ['nullable', 'array'],

            'matches.*.actual_result' => ['nullable', 'in:v,n,d'],
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

    private function ensureJourneeBelongsToSeason(Season $season, Journee $journee): void
    {
        if ($journee->season_id !== $season->id) {
            abort(404);
        }
    }
}
