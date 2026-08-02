<?php

namespace App\Http\Controllers;

use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\Prono;
use App\Models\Season;
use App\Models\SeasonPreseasonPrediction;
use App\Models\SeasonPreseasonQuestion;
use App\Services\PreseasonDeadlineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PronoController extends Controller
{
    public function index(PreseasonDeadlineService $preseasonDeadlineService)
    {
        $season = Season::where('is_active', true)
            ->whereHas('players', function ($query) {
                $query->where('users.id', auth()->id());
            })
            ->first();

        if (! $season) {
            return view('pronos.journees', [
                'season' => null,
                'journees' => collect(),
                'preseasonDeadline' => null,
            ]);
        }

        $preseasonDeadline = $preseasonDeadlineService->deadlineForUser($season, auth()->user());
        $preseasonIsLocked = $preseasonDeadlineService->isLockedForUser($season, auth()->user());

        $journees = $this->availableJourneesForUser(
            $season,
            $preseasonDeadline,
            $preseasonIsLocked,
            true,
            true
        );

        return view('pronos.journees', [
            'season' => $season,
            'journees' => $journees,
            'preseasonDeadline' => $preseasonDeadline,
        ]);
    }

    public function show(
        Season $season,
        Journee $journee,
        PreseasonDeadlineService $preseasonDeadlineService
    ) {
        $this->ensureUserCanAccessSeason($season);

        if ($journee->season_id !== $season->id) {
            abort(404);
        }

        if ($journee->type === 'preseason') {
            return $this->showPreseason($season, $journee, $preseasonDeadlineService);
        }

        $matches = MatchGame::with([
            'homeClub',
            'awayClub',
            'journee',
            'predictionDeadlineException',
            'pronos' => fn ($query) => $query->where('user_id', auth()->id()),
        ])
            ->where('journee_id', $journee->id)
            ->orderBy('position')
            ->get();

        $hasOpenMatches = $matches->contains(fn ($match) => ! $match->isPredictionLocked());

        $availableJournees = $this->availableJourneesForUser(
            $season,
            null,
            false,
            false,
            false
        );

        $currentJourneeIndex = $availableJournees->search(
            fn ($availableJournee) => $availableJournee->id === $journee->id
        );

        $previousJournee = null;
        $nextJournee = null;

        if ($currentJourneeIndex !== false) {
            if ($currentJourneeIndex > 0) {
                $previousJournee = $availableJournees->get($currentJourneeIndex - 1);
            }

            if ($currentJourneeIndex < $availableJournees->count() - 1) {
                $nextJournee = $availableJournees->get($currentJourneeIndex + 1);
            }
        }

        return view('pronos.index', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'isLocked' => ! $hasOpenMatches,
            'rankingIsAvailable' => $journee->isLocked() && ! $hasOpenMatches,
            'predictionNotice' => $this->predictionNoticeForJournee($journee, $matches),
            'previousJournee' => $previousJournee,
            'nextJournee' => $nextJournee,
        ]);
    }

    public function storeAll(
        Request $request,
        Season $season,
        Journee $journee,
        PreseasonDeadlineService $preseasonDeadlineService
    ) {
        $this->ensureUserCanAccessSeason($season);

        if ($journee->season_id !== $season->id) {
            abort(404);
        }

        if ($journee->type === 'preseason') {
            return $this->storePreseason($request, $season, $journee, $preseasonDeadlineService);
        }

        $matches = MatchGame::with([
            'homeClub',
            'awayClub',
            'journee',
            'predictionDeadlineException',
        ])
            ->where('journee_id', $journee->id)
            ->get()
            ->keyBy('id');

        if ($request->has('delete_prono_match_id')) {
            $data = $request->validate([
                'delete_prono_match_id' => ['required', 'integer'],
            ]);

            return $this->deletePronoForMatch(
                $season,
                $journee,
                $matches,
                (int) $data['delete_prono_match_id']
            );
        }

        $blockMessage = $this->predictionBlockMessage($journee, $matches);

        if ($blockMessage) {
            return redirect()
                ->route('pronos.show', [$season, $journee])
                ->with('prediction_warning', $blockMessage);
        }

        $data = $request->validate([
            'pronos' => ['required', 'array', 'min:1'],
            'pronos.*.predicted_result' => ['required', Rule::in($journee->allowedResultOptions())],
            'pronos.*.predicted_tries' => ['required', 'integer', 'min:0'],
            'pronos.*.predicted_home_bonus' => ['nullable', 'in:o,-,d'],
            'pronos.*.predicted_away_bonus' => ['nullable', 'in:o,-,d'],
        ]);

        foreach ($data['pronos'] as $matchId => $pronoData) {
            $match = $matches->get((int) $matchId);

            if (! $match) {
                abort(404);
            }

            if ($match->isPredictionLocked()) {
                return redirect()
                    ->route('pronos.show', [$season, $journee])
                    ->with(
                        'prediction_warning',
                        'Saisie clôturée pour '.$match->homeClub->name.' - '.$match->awayClub->name.' : tes pronostics n’ont pas été enregistrés.'
                    );
            }
        }

        foreach ($data['pronos'] as $matchId => $pronoData) {
            $match = $matches->get((int) $matchId);

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

    private function deletePronoForMatch(
        Season $season,
        Journee $journee,
        $matches,
        int $matchId
    ) {
        $match = $matches->get($matchId);

        if (! $match) {
            abort(404);
        }

        if ($match->isPredictionLocked()) {
            return redirect()
                ->route('pronos.show', [$season, $journee])
                ->with(
                    'prediction_warning',
                    'Saisie clôturée pour '.$match->homeClub->name.' - '.$match->awayClub->name.' : ce pronostic ne peut plus être effacé.'
                );
        }

        $deleted = Prono::where('user_id', auth()->id())
            ->where('match_game_id', $match->id)
            ->delete();

        if (! $deleted) {
            return redirect()
                ->route('pronos.show', [$season, $journee])
                ->with('prediction_warning', 'Aucun pronostic à effacer pour ce match.');
        }

        return redirect()
            ->route('pronos.show', [$season, $journee])
            ->with(
                'success',
                'Pronostic effacé pour '.$match->homeClub->name.' - '.$match->awayClub->name.'.'
            );
    }

    private function availableJourneesForUser(
        Season $season,
        $preseasonDeadline,
        bool $preseasonIsLocked,
        bool $includePreseason,
        bool $withUserPronoCount
    ) {
        $counts = ['matches'];

        if ($withUserPronoCount) {
            $userId = auth()->id();

            $counts['matches as user_pronos_count'] = function ($query) use ($userId) {
                $query->whereHas('pronos', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                });
            };
        }

        return Journee::with([
            'season',
            'matches.journee',
            'matches.predictionDeadlineException',
        ])
            ->withCount($counts)
            ->where('season_id', $season->id)
            ->orderBy('number')
            ->get()
            ->filter(function ($journee) use (
                $preseasonDeadline,
                $preseasonIsLocked,
                $includePreseason
            ) {
                if ($journee->type === 'preseason') {
                    if (! $includePreseason) {
                        return false;
                    }

                    if (! $preseasonDeadline || $preseasonIsLocked) {
                        return false;
                    }

                    return $journee->season
                        ->preseasonQuestions()
                        ->where('is_active', true)
                        ->exists();
                }

                if ($journee->predictions_enabled === false) {
                    return false;
                }

                if (! $journee->first_match_at) {
                    return false;
                }

                if (! $journee->hasExpectedMatchesCount()) {
                    return false;
                }

                if ((int) $journee->matches_count === 0) {
                    return false;
                }

                return $journee->matches
                    ->contains(fn ($match) => ! $match->isPredictionLocked());
            })
            ->values();
    }

    private function showPreseason(
        Season $season,
        Journee $journee,
        PreseasonDeadlineService $preseasonDeadlineService
    ) {
        $questions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $predictions = SeasonPreseasonPrediction::where('season_id', $season->id)
            ->where('user_id', auth()->id())
            ->get()
            ->keyBy('question_id');

        $top14Clubs = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->orderBy('name')
            ->get();

        $prod2Clubs = $season->clubs()
            ->wherePivot('competition', 'prod2')
            ->orderBy('name')
            ->get();

        $seasonClubs = $season->clubs()
            ->orderBy('name')
            ->get();

        $preseasonDeadline = $preseasonDeadlineService->deadlineForUser($season, auth()->user());

        return view('pronos.preseason', [
            'season' => $season,
            'journee' => $journee,
            'questions' => $questions,
            'predictions' => $predictions,
            'top14Clubs' => $top14Clubs,
            'prod2Clubs' => $prod2Clubs,
            'seasonClubs' => $seasonClubs,
            'preseasonDeadline' => $preseasonDeadline,
            'isLocked' => $preseasonDeadline
                ? $preseasonDeadlineService->isLockedForUser($season, auth()->user())
                : true,
            'isNotOpen' => $preseasonDeadline === null,
        ]);
    }

    private function storePreseason(
        Request $request,
        Season $season,
        Journee $journee,
        PreseasonDeadlineService $preseasonDeadlineService
    ) {
        $preseasonDeadline = $preseasonDeadlineService->deadlineForUser($season, auth()->user());

        if (! $preseasonDeadline) {
            return redirect()
                ->route('pronos.show', [$season, $journee])
                ->with('prediction_warning', 'Les pronostics avant-saison ne sont pas encore ouverts.');
        }

        if ($preseasonDeadlineService->isLockedForUser($season, auth()->user())) {
            return redirect()
                ->route('pronos.show', [$season, $journee])
                ->with('prediction_warning', 'Saisie avant-saison clôturée : tes pronostics n’ont pas été enregistrés.');
        }

        $questions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateUniquePreseasonGroups($questions, $data['answers'] ?? []);

        foreach ($questions as $question) {
            $answer = $data['answers'][$question->id] ?? null;

            if ($answer === null || $answer === '') {
                continue;
            }

            $this->validatePreseasonAnswer($season, $question, $answer);

            $isClubAnswer = $question->answer_type !== 'free_text';

            SeasonPreseasonPrediction::updateOrCreate(
                [
                    'season_id' => $season->id,
                    'user_id' => auth()->id(),
                    'question_id' => $question->id,
                ],
                [
                    'answer_type' => $question->answer_type,
                    'club_id' => $isClubAnswer ? (int) $answer : null,
                    'text_answer' => $isClubAnswer ? null : $answer,
                    'is_correct' => null,
                    'points' => 0,
                    'submitted_at' => now(),
                ]
            );
        }

        return redirect()
            ->route('pronos.show', [$season, $journee])
            ->with('success', 'Pronostics avant-saison enregistrés.');
    }

    private function predictionNoticeForJournee(Journee $journee, $matches): ?array
    {
        if ($journee->predictions_enabled === false) {
            return [
                'type' => 'warning',
                'message' => 'Saisie non activée pour cette journée.',
            ];
        }

        if (! $journee->first_match_at) {
            return [
                'type' => 'warning',
                'message' => 'Date du premier match non renseignée.',
            ];
        }

        $hasOpenMatches = $matches->contains(fn ($match) => ! $match->isPredictionLocked());

        if (! $hasOpenMatches) {
            return [
                'type' => 'info',
                'message' => 'Pronostics clôturés. Consultation uniquement.',
            ];
        }

        if ($matches->contains(fn ($match) => $match->isPredictionLocked())) {
            return [
                'type' => 'warning',
                'message' => 'Certains matchs sont verrouillés. Tu peux encore modifier les matchs ouverts.',
            ];
        }

        return null;
    }

    private function predictionBlockMessage(Journee $journee, $matches): ?string
    {
        if ($journee->predictions_enabled === false) {
            return 'Saisie non activée : tes pronostics n’ont pas été enregistrés.';
        }

        if (! $journee->first_match_at) {
            return 'Date du premier match non renseignée : tes pronostics n’ont pas été enregistrés.';
        }

        $hasOpenMatches = $matches->contains(fn ($match) => ! $match->isPredictionLocked());

        if (! $hasOpenMatches) {
            return 'Saisie clôturée : tes pronostics n’ont pas été enregistrés.';
        }

        return null;
    }

    private function validateUniquePreseasonGroups($questions, array $answers): void
    {
        $groups = [
            'top14_semifinalists' => fn ($label) => str_contains($label, 'demi')
                && str_contains($label, 'top 14'),
            'prod2_semifinalists' => fn ($label) => str_contains($label, 'demi')
                && str_contains($label, 'pro d2'),
        ];

        foreach ($groups as $matcher) {
            $questionIds = $questions
                ->filter(function ($question) use ($matcher) {
                    return $matcher(mb_strtolower($question->label));
                })
                ->pluck('id')
                ->toArray();

            $selectedClubIds = [];

            foreach ($questionIds as $questionId) {
                $answer = $answers[$questionId] ?? null;

                if ($answer === null || $answer === '') {
                    continue;
                }

                $selectedClubIds[] = (string) $answer;
            }

            if (count($selectedClubIds) !== count(array_unique($selectedClubIds))) {
                abort(422, 'Tu ne peux pas sélectionner plusieurs fois le même club dans les demi-finalistes.');
            }
        }
    }

    private function validatePreseasonAnswer(
        Season $season,
        SeasonPreseasonQuestion $question,
        string $answer
    ): void {
        if ($question->answer_type === 'free_text') {
            return;
        }

        if (! ctype_digit($answer)) {
            abort(422, 'Réponse avant-saison invalide.');
        }

        $clubId = (int) $answer;

        $query = $season->clubs()
            ->where('clubs.id', $clubId);

        if ($question->answer_type === 'top14_club') {
            $query->wherePivot('competition', 'top14');
        }

        if ($question->answer_type === 'prod2_club') {
            $query->wherePivot('competition', 'prod2');
        }

        if (! $query->exists()) {
            abort(422, 'Club sélectionné invalide pour cette question.');
        }
    }

    private function ensureUserCanAccessSeason(Season $season): void
    {
        $canAccess = $season->players()
            ->where('users.id', auth()->id())
            ->exists();

        if (! $canAccess) {
            abort(403);
        }
    }
}
