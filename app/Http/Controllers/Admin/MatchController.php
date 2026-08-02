<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\MatchPredictionDeadlineException;
use App\Models\Season;
use App\Models\SeasonPreseasonQuestion;
use App\Services\KnockoutMatchSetupService;
use App\Services\PreseasonAutoResultService;
use App\Services\PreseasonScoringService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.seasons.index');
    }

    public function manage(
        Season $season,
        Journee $journee,
        KnockoutMatchSetupService $knockoutMatchSetupService
    ) {
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

        $clubs = $knockoutMatchSetupService->eligibleClubsForJournee($season, $journee);
        $automaticSetup = $knockoutMatchSetupService->automaticSetupForJournee($season, $journee);

        return view('admin.matches.manage', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'clubs' => $clubs,
            'usedClubIds' => $usedClubIds,
            'automaticSetup' => $automaticSetup,
        ]);
    }

    public function store(
        Request $request,
        Season $season,
        Journee $journee,
        KnockoutMatchSetupService $knockoutMatchSetupService
    ) {
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

        $clubValidationError = $this->eligibleClubValidationError(
            $season,
            $journee,
            [
                (int) $data['home_club_id'],
                (int) $data['away_club_id'],
            ],
            $knockoutMatchSetupService
        );

        if ($clubValidationError) {
            return back()->withErrors($clubValidationError);
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
        ScoringService $scoringService,
        PreseasonAutoResultService $preseasonAutoResultService,
        PreseasonScoringService $preseasonScoringService
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

            'accept_preseason_auto_result' => ['nullable', 'boolean'],
            'auto_result_question_id' => ['nullable', 'integer', 'exists:season_preseason_questions,id'],
            'auto_result_club_id' => ['nullable', 'integer', 'exists:clubs,id'],
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

        $acceptedAutoResult = null;

        if ($request->boolean('accept_preseason_auto_result')) {
            $acceptedAutoResult = $this->acceptPreseasonAutoResult(
                $season,
                $data,
                $preseasonAutoResultService,
                $preseasonScoringService
            );

            if (! $acceptedAutoResult['success']) {
                return redirect()
                    ->route('admin.seasons.journees.results', [$season, $journee])
                    ->with('error', $acceptedAutoResult['message']);
            }
        }

        $autoResultSuggestions = $preseasonAutoResultService
            ->suggestionsAfterJourneeResultsSaved($season, $journee);

        if ($acceptedAutoResult) {
            $successMessage = $acceptedAutoResult['message'];
        } else {
            $successMessage = 'Résultats et exceptions de dates enregistrés.';

            if (! empty($autoResultSuggestions)) {
                $successMessage .= ' Résultat avant-saison détecté : validation requise.';
            }
        }

        $redirect = redirect()
            ->route('admin.seasons.journees.results', [$season, $journee])
            ->with('success', $successMessage);

        if (! empty($autoResultSuggestions)) {
            $redirect->with('preseason_auto_result_suggestions', $autoResultSuggestions);
        }

        return $redirect;
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

    public function storeBulk(
        Request $request,
        Season $season,
        Journee $journee,
        KnockoutMatchSetupService $knockoutMatchSetupService
    ) {
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

        $clubValidationError = $this->eligibleClubValidationError(
            $season,
            $journee,
            $clubIds,
            $knockoutMatchSetupService
        );

        if ($clubValidationError) {
            return back()->withErrors($clubValidationError);
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
            ->route('admin.seasons.journees.matches', $this->matchesPageRouteParameters($season, $journee, $request))
            ->with('success', 'Matchs ajoutés.');
    }

    private function acceptPreseasonAutoResult(
        Season $season,
        array $data,
        PreseasonAutoResultService $preseasonAutoResultService,
        PreseasonScoringService $preseasonScoringService
    ): array {
        $questionId = $data['auto_result_question_id'] ?? null;
        $clubId = $data['auto_result_club_id'] ?? null;

        if (! $questionId || ! $clubId) {
            return [
                'success' => false,
                'message' => 'Résultat automatique incomplet : la question ou le club est manquant.',
            ];
        }

        $question = $season->preseasonQuestions()
            ->whereKey((int) $questionId)
            ->first();

        if (! $question instanceof SeasonPreseasonQuestion) {
            return [
                'success' => false,
                'message' => 'La question avant-saison concernée est introuvable pour cette saison.',
            ];
        }

        $suggestion = $preseasonAutoResultService->suggestionForQuestion($season, $question);

        if (! $suggestion || (int) $suggestion['club_id'] !== (int) $clubId) {
            return [
                'success' => false,
                'message' => 'Ce résultat automatique n’est plus certain. Le résultat avant-saison n’a pas été mémorisé.',
            ];
        }

        DB::transaction(function () use ($question, $clubId, $season, $preseasonScoringService) {
            $question->update([
                'result_club_id' => $clubId,
                'result_text_answer' => null,
                'result_recorded_at' => now(),
            ]);

            $preseasonScoringService->recalculateSeason($season);
        });

        return [
            'success' => true,
            'message' => 'Résultat avant-saison mémorisé et points recalculés : '.$suggestion['question_label'].' → '.$suggestion['club_name'].'.',
        ];
    }

    private function eligibleClubValidationError(
        Season $season,
        Journee $journee,
        array $clubIds,
        KnockoutMatchSetupService $knockoutMatchSetupService
    ): ?array {
        $eligibleClubIds = $knockoutMatchSetupService
            ->eligibleClubsForJournee($season, $journee)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($eligibleClubIds)) {
            return [
                'clubs' => 'Aucun club éligible n’est disponible pour cette journée. Vérifie les résultats nécessaires avant de créer les matchs.',
            ];
        }

        foreach ($clubIds as $clubId) {
            if (! in_array((int) $clubId, $eligibleClubIds, true)) {
                return [
                    'clubs' => 'Un club sélectionné n’est pas éligible pour cette journée.',
                ];
            }
        }

        return null;
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
