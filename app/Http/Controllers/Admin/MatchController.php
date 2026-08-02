<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\MatchPredictionDeadlineException;
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
            ->with([
                'homeClub',
                'awayClub',
            ])
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
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (
            ! in_array((int) $data['home_club_id'], $top14ClubIds, true)
            || ! in_array((int) $data['away_club_id'], $top14ClubIds, true)
        ) {
            return back()->withErrors([
                'clubs' => 'Les clubs sélectionnés doivent appartenir au TOP 14 de cette saison.',
            ]);
        }

        $duplicateMatch = $this->duplicateMatchInSeason(
            $season,
            (int) $data['home_club_id'],
            (int) $data['away_club_id']
        );

        if ($duplicateMatch) {
            return back()->withErrors([
                'clubs' => $this->duplicateMatchMessage($duplicateMatch),
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
            ->route('admin.seasons.journees.matches', $this->matchesPageRouteParameters($season, $journee, $request))
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
            ->with([
                'homeClub',
                'awayClub',
                'predictionDeadlineException',
            ])
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
            'deadline_exceptions' => ['nullable', 'array'],
            'deadline_exceptions.*.prediction_deadline' => ['nullable', 'date'],
        ]);

        foreach ($data['deadline_exceptions'] ?? [] as $matchId => $exceptionData) {
            $match = MatchGame::where('journee_id', $journee->id)
                ->where('id', $matchId)
                ->firstOrFail();

            $deadline = $exceptionData['prediction_deadline'] ?? null;

            if (blank($deadline)) {
                $match->predictionDeadlineException()->delete();

                continue;
            }

            MatchPredictionDeadlineException::updateOrCreate(
                [
                    'match_game_id' => $match->id,
                ],
                [
                    'prediction_deadline' => $deadline,
                ]
            );
        }

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

            $match->load([
                'journee.season.scoringRules',
                'pronos.user',
            ]);

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
            ->with('success', 'Résultats et exceptions de dates enregistrés.');
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
        $pairs = array_chunk($clubIds, 2);

        $top14Clubs = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $top14ClubIds = $top14Clubs
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $usedClubIds = MatchGame::where('journee_id', $journee->id)
            ->get()
            ->flatMap(fn ($match) => [
                (int) $match->home_club_id,
                (int) $match->away_club_id,
            ])
            ->unique()
            ->values()
            ->all();

        $usedClubIdsById = array_fill_keys($usedClubIds, true);
        $createdCount = 0;
        $warnings = [];
        $seenPairKeys = [];

        $nextPosition = ((int) MatchGame::where('journee_id', $journee->id)->max('position')) + 1;

        foreach ($pairs as $index => $pair) {
            if (count($pair) < 2) {
                $warnings[] = 'Sélection incomplète ignorée : un club n’a pas été associé à un adversaire.';

                continue;
            }

            $homeClubId = (int) $pair[0];
            $awayClubId = (int) $pair[1];
            $pairLabel = $this->pairLabel($top14Clubs, $homeClubId, $awayClubId);

            if ($homeClubId === $awayClubId) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' utilise deux fois le même club.';

                continue;
            }

            if (
                ! in_array($homeClubId, $top14ClubIds, true)
                || ! in_array($awayClubId, $top14ClubIds, true)
            ) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' contient un club qui n’appartient pas au TOP 14 de cette saison.';

                continue;
            }

            if (isset($usedClubIdsById[$homeClubId]) || isset($usedClubIdsById[$awayClubId])) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' utilise un club déjà présent sur cette journée.';

                continue;
            }

            $pairKey = $homeClubId.'-'.$awayClubId;

            if (isset($seenPairKeys[$pairKey])) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' est présent deux fois dans la sélection.';

                continue;
            }

            $duplicateMatch = $this->duplicateMatchInSeason(
                $season,
                $homeClubId,
                $awayClubId
            );

            if ($duplicateMatch) {
                $warnings[] = 'Match ignoré : '.$this->duplicateMatchMessage($duplicateMatch);

                continue;
            }

            MatchGame::create([
                'journee_id' => $journee->id,
                'home_club_id' => $homeClubId,
                'away_club_id' => $awayClubId,
                'position' => $nextPosition,
            ]);

            $createdCount++;
            $nextPosition++;

            $seenPairKeys[$pairKey] = true;
            $usedClubIdsById[$homeClubId] = true;
            $usedClubIdsById[$awayClubId] = true;
        }

        $redirect = redirect()
            ->route('admin.seasons.journees.matches', $this->matchesPageRouteParameters($season, $journee, $request));

        if ($createdCount > 0) {
            $redirect->with('success', $createdCount.' match'.($createdCount > 1 ? 's' : '').' ajouté'.($createdCount > 1 ? 's' : '').'.');
        }

        if (! empty($warnings)) {
            if ($createdCount === 0) {
                array_unshift($warnings, 'Aucun match ajouté.');
            }

            $redirect->with('warning', $warnings);
        }

        if ($createdCount === 0 && empty($warnings)) {
            $redirect->with('warning', 'Aucun match ajouté.');
        }

        return $redirect;
    }

    private function duplicateMatchInSeason(Season $season, int $homeClubId, int $awayClubId): ?MatchGame
    {
        return MatchGame::query()
            ->with([
                'journee',
                'homeClub',
                'awayClub',
            ])
            ->where('home_club_id', $homeClubId)
            ->where('away_club_id', $awayClubId)
            ->whereHas('journee', function ($query) use ($season) {
                $query->where('season_id', $season->id);
            })
            ->orderBy('id')
            ->first();
    }

    private function duplicateMatchMessage(MatchGame $match): string
    {
        $journeeName = $match->journee?->name ?? 'une autre journée';
        $homeClubName = $match->homeClub?->name ?? 'club domicile';
        $awayClubName = $match->awayClub?->name ?? 'club extérieur';

        return 'ce match existe déjà dans la saison : '.$journeeName.' — '.$homeClubName.' - '.$awayClubName.'.';
    }

    private function pairLabel($clubs, int $homeClubId, int $awayClubId): string
    {
        $homeClubName = $clubs->get($homeClubId)?->name ?? 'club #'.$homeClubId;
        $awayClubName = $clubs->get($awayClubId)?->name ?? 'club #'.$awayClubId;

        return $homeClubName.' - '.$awayClubName;
    }

    private function matchesPageRouteParameters(Season $season, Journee $journee, Request $request): array
    {
        $parameters = [
            $season,
            $journee,
        ];

        if ($request->query('from') === 'upcoming-matches' || $request->input('from') === 'upcoming-matches') {
            $parameters['from'] = 'upcoming-matches';
        }

        return $parameters;
    }

    private function ensureJourneeBelongsToSeason(Season $season, Journee $journee): void
    {
        if ((int) $journee->season_id !== (int) $season->id) {
            abort(404);
        }
    }
}
