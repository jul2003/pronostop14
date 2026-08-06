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

        $clubs = $knockoutMatchSetupService
            ->eligibleClubsForJournee($season, $journee);

        $automaticSetup = $knockoutMatchSetupService
            ->automaticSetupForJournee($season, $journee);

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

        if ($this->shouldPreventDuplicateMatchInSeason($journee)) {
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
            ->route(
                'admin.seasons.journees.matches',
                $this->matchesPageRouteParameters($season, $journee, $request)
            )
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

        $resultsJournees = $season->journees()
            ->where('type', '!=', 'preseason')
            ->whereHas('matches')
            ->orderBy('number')
            ->orderBy('id')
            ->get();

        $currentJourneeIndex = $resultsJournees->search(
            fn (Journee $resultsJournee) => (int) $resultsJournee->id === (int) $journee->id
        );

        $previousJournee = null;
        $nextJournee = null;

        if ($currentJourneeIndex !== false) {
            if ($currentJourneeIndex > 0) {
                $previousJournee = $resultsJournees->get($currentJourneeIndex - 1);
            }

            if ($currentJourneeIndex < $resultsJournees->count() - 1) {
                $nextJournee = $resultsJournees->get($currentJourneeIndex + 1);
            }
        }

        return view('admin.matches.results', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'previousJournee' => $previousJournee,
            'nextJournee' => $nextJournee,
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
                ->route(
                    'admin.seasons.journees.results',
                    $this->resultsPageRouteParameters($season, $journee, $request)
                )
                ->with(
                    'error',
                    'Cette saison est verrouillée : les résultats ne peuvent plus être modifiés.'
                );
        }

        $data = $request->validate([
            'matches' => ['nullable', 'array'],
            'matches.*.actual_result' => [
                'nullable',
                Rule::in($journee->allowedResultOptions()),
            ],
            'matches.*.actual_tries' => ['nullable', 'integer', 'min:0'],
            'matches.*.actual_home_bonus' => ['nullable', 'in:o,-,d'],
            'matches.*.actual_away_bonus' => ['nullable', 'in:o,-,d'],
            'deadline_exceptions' => ['nullable', 'array'],
            'deadline_exceptions.*.prediction_deadline' => ['nullable', 'date'],
            'accept_preseason_auto_result' => ['nullable', 'boolean'],
            'accept_all_preseason_auto_results' => ['nullable', 'boolean'],
            'auto_result_question_id' => [
                'nullable',
                'integer',
                'exists:season_preseason_questions,id',
            ],
            'auto_result_club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'auto_results' => [
                'required_if:accept_all_preseason_auto_results,1',
                'array',
                'min:1',
            ],
            'auto_results.*.question_id' => [
                'required',
                'integer',
                'distinct',
                'exists:season_preseason_questions,id',
            ],
            'auto_results.*.club_id' => [
                'required',
                'integer',
                'exists:clubs,id',
            ],
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

        if ($request->boolean('accept_all_preseason_auto_results')) {
            $acceptedAutoResult = $this->acceptAllPreseasonAutoResults(
                $season,
                $data['auto_results'] ?? [],
                $preseasonAutoResultService,
                $preseasonScoringService
            );
        } elseif ($request->boolean('accept_preseason_auto_result')) {
            $acceptedAutoResult = $this->acceptPreseasonAutoResult(
                $season,
                $data,
                $preseasonAutoResultService,
                $preseasonScoringService
            );
        }

        if ($acceptedAutoResult && ! $acceptedAutoResult['success']) {
            $currentAutoResultSuggestions = $preseasonAutoResultService
                ->suggestionsAfterJourneeResultsSaved($season, $journee);

            $redirect = redirect()
                ->route(
                    'admin.seasons.journees.results',
                    $this->resultsPageRouteParameters($season, $journee, $request)
                )
                ->with('error', $acceptedAutoResult['message']);

            if (! empty($currentAutoResultSuggestions)) {
                $redirect->with(
                    'preseason_auto_result_suggestions',
                    $currentAutoResultSuggestions
                );
            }

            return $redirect;
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
            ->route(
                'admin.seasons.journees.results',
                $this->resultsPageRouteParameters($season, $journee, $request)
            )
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
        $pairs = array_chunk($clubIds, 2);

        $eligibleClubs = $knockoutMatchSetupService
            ->eligibleClubsForJournee($season, $journee)
            ->keyBy('id');

        $eligibleClubIds = $eligibleClubs
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($eligibleClubIds)) {
            return back()->withErrors([
                'clubs' => 'Aucun club éligible n’est disponible pour cette journée. Vérifie les résultats nécessaires avant de créer les matchs.',
            ]);
        }

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

        foreach ($pairs as $pair) {
            if (count($pair) < 2) {
                $warnings[] = 'Sélection incomplète ignorée : un club n’a pas été associé à un adversaire.';

                continue;
            }

            $homeClubId = (int) $pair[0];
            $awayClubId = (int) $pair[1];
            $pairLabel = $this->pairLabel($eligibleClubs, $homeClubId, $awayClubId);

            if ($homeClubId === $awayClubId) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' utilise deux fois le même club.';

                continue;
            }

            if (
                ! in_array($homeClubId, $eligibleClubIds, true)
                || ! in_array($awayClubId, $eligibleClubIds, true)
            ) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' contient un club qui n’est pas éligible pour cette journée.';

                continue;
            }

            if (
                isset($usedClubIdsById[$homeClubId])
                || isset($usedClubIdsById[$awayClubId])
            ) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' utilise un club déjà présent sur cette journée.';

                continue;
            }

            $pairKey = $homeClubId.'-'.$awayClubId;

            if (isset($seenPairKeys[$pairKey])) {
                $warnings[] = 'Match ignoré : '.$pairLabel.' est présent deux fois dans la sélection.';

                continue;
            }

            if ($this->shouldPreventDuplicateMatchInSeason($journee)) {
                $duplicateMatch = $this->duplicateMatchInSeason(
                    $season,
                    $homeClubId,
                    $awayClubId
                );

                if ($duplicateMatch) {
                    $warnings[] = 'Match ignoré : '.$this->duplicateMatchMessage($duplicateMatch);

                    continue;
                }
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
            ->route(
                'admin.seasons.journees.matches',
                $this->matchesPageRouteParameters($season, $journee, $request)
            );

        if ($createdCount > 0) {
            $redirect->with(
                'success',
                $createdCount
                    .' match'
                    .($createdCount > 1 ? 's' : '')
                    .' ajouté'
                    .($createdCount > 1 ? 's' : '')
                    .'.'
            );
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

        $suggestion = $preseasonAutoResultService
            ->suggestionForQuestion($season, $question);

        if (! $suggestion || (int) $suggestion['club_id'] !== (int) $clubId) {
            return [
                'success' => false,
                'message' => 'Ce résultat automatique n’est plus certain. Le résultat avant-saison n’a pas été mémorisé.',
            ];
        }

        DB::transaction(function () use (
            $question,
            $clubId,
            $season,
            $preseasonScoringService
        ) {
            $question->update([
                'result_club_id' => $clubId,
                'result_text_answer' => null,
                'result_recorded_at' => now(),
            ]);

            $preseasonScoringService->recalculateSeason($season);
        });

        return [
            'success' => true,
            'message' => 'Résultat avant-saison mémorisé et points recalculés : '
                .$suggestion['question_label']
                .' → '
                .$suggestion['club_name']
                .'.',
        ];
    }

    private function acceptAllPreseasonAutoResults(
        Season $season,
        array $submittedResults,
        PreseasonAutoResultService $preseasonAutoResultService,
        PreseasonScoringService $preseasonScoringService
    ): array {
        if (empty($submittedResults)) {
            return [
                'success' => false,
                'message' => 'Aucun résultat automatique n’a été transmis.',
            ];
        }

        $validatedResults = [];

        foreach ($submittedResults as $submittedResult) {
            $questionId = $submittedResult['question_id'] ?? null;
            $clubId = $submittedResult['club_id'] ?? null;

            if (! $questionId || ! $clubId) {
                return [
                    'success' => false,
                    'message' => 'Une proposition automatique est incomplète. Aucun résultat avant-saison n’a été mémorisé.',
                ];
            }

            $question = $season->preseasonQuestions()
                ->whereKey((int) $questionId)
                ->first();

            if (! $question instanceof SeasonPreseasonQuestion) {
                return [
                    'success' => false,
                    'message' => 'Une question avant-saison est introuvable pour cette saison. Aucun résultat n’a été mémorisé.',
                ];
            }

            $suggestion = $preseasonAutoResultService
                ->suggestionForQuestion($season, $question);

            if (! $suggestion || (int) $suggestion['club_id'] !== (int) $clubId) {
                return [
                    'success' => false,
                    'message' => 'Une des propositions n’est plus certaine. Aucun résultat avant-saison n’a été mémorisé ; les propositions ont été recalculées.',
                ];
            }

            $validatedResults[] = [
                'question' => $question,
                'club_id' => (int) $clubId,
                'question_label' => $suggestion['question_label'],
                'club_name' => $suggestion['club_name'],
            ];
        }

        DB::transaction(function () use (
            $validatedResults,
            $season,
            $preseasonScoringService
        ) {
            foreach ($validatedResults as $validatedResult) {
                $validatedResult['question']->update([
                    'result_club_id' => $validatedResult['club_id'],
                    'result_text_answer' => null,
                    'result_recorded_at' => now(),
                ]);
            }

            $preseasonScoringService->recalculateSeason($season);
        });

        $count = count($validatedResults);

        $details = collect($validatedResults)
            ->map(
                fn (array $validatedResult) => $validatedResult['question_label']
                    .' → '
                    .$validatedResult['club_name']
            )
            ->implode(' ; ');

        return [
            'success' => true,
            'message' => $count
                .' résultat'
                .($count > 1 ? 's' : '')
                .' avant-saison mémorisé'
                .($count > 1 ? 's' : '')
                .' et points recalculés : '
                .$details
                .'.',
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

    private function shouldPreventDuplicateMatchInSeason(Journee $journee): bool
    {
        return $journee->type === 'regular';
    }

    private function duplicateMatchInSeason(
        Season $season,
        int $homeClubId,
        int $awayClubId
    ): ?MatchGame {
        return MatchGame::query()
            ->with([
                'journee',
                'homeClub',
                'awayClub',
            ])
            ->where('home_club_id', $homeClubId)
            ->where('away_club_id', $awayClubId)
            ->whereHas('journee', function ($query) use ($season) {
                $query->where('season_id', $season->id)
                    ->where('type', 'regular');
            })
            ->orderBy('id')
            ->first();
    }

    private function duplicateMatchMessage(MatchGame $match): string
    {
        $journeeName = $match->journee?->name ?? 'une autre journée';
        $homeClubName = $match->homeClub?->name ?? 'club domicile';
        $awayClubName = $match->awayClub?->name ?? 'club extérieur';

        return 'ce match existe déjà dans la saison : '
            .$journeeName
            .' — '
            .$homeClubName
            .' - '
            .$awayClubName
            .'.';
    }

    private function pairLabel(
        $clubs,
        int $homeClubId,
        int $awayClubId
    ): string {
        $homeClubName = $clubs->get($homeClubId)?->name ?? 'club #'.$homeClubId;
        $awayClubName = $clubs->get($awayClubId)?->name ?? 'club #'.$awayClubId;

        return $homeClubName.' - '.$awayClubName;
    }

    private function resultsPageRouteParameters(
        Season $season,
        Journee $journee,
        Request $request
    ): array {
        $parameters = [
            $season,
            $journee,
        ];

        if (
            $request->query('from') === 'pending-results'
            || $request->input('from') === 'pending-results'
        ) {
            $parameters['from'] = 'pending-results';
        }

        return $parameters;
    }

    private function matchesPageRouteParameters(
        Season $season,
        Journee $journee,
        Request $request
    ): array {
        $parameters = [
            $season,
            $journee,
        ];

        if (
            $request->query('from') === 'upcoming-matches'
            || $request->input('from') === 'upcoming-matches'
        ) {
            $parameters['from'] = 'upcoming-matches';
        }

        return $parameters;
    }

    private function ensureJourneeBelongsToSeason(
        Season $season,
        Journee $journee
    ): void {
        if ((int) $journee->season_id !== (int) $season->id) {
            abort(404);
        }
    }
}
