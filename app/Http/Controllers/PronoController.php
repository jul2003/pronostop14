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

        $journees = Journee::with('season')
            ->withCount('matches')
            ->where('season_id', $season->id)
            ->orderBy('number')
            ->get()
            ->filter(function ($journee) use ($preseasonDeadline, $preseasonIsLocked) {
                if ($journee->type === 'preseason') {
                    if (! $preseasonDeadline || $preseasonIsLocked) {
                        return false;
                    }

                    return $journee->season
                        ->preseasonQuestions()
                        ->where('is_active', true)
                        ->exists();
                }

                if (! $journee->prediction_deadline) {
                    return false;
                }

                if ($journee->isLocked()) {
                    return false;
                }

                if (! $journee->hasExpectedMatchesCount()) {
                    return false;
                }

                return (int) $journee->matches_count > 0;
            })
            ->values();

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

        $this->ensureJourneeHasDeadline($journee);

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

        $this->ensureJourneeHasDeadline($journee);

        if ($journee->isLocked()) {
            abort(403);
        }

        $data = $request->validate([
            'pronos' => ['required', 'array'],
            'pronos.*.predicted_result' => ['required', Rule::in($journee->allowedResultOptions())],
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
                ->withErrors([
                    'deadline' => 'Les pronostics avant-saison ne sont pas encore ouverts.',
                ]);
        }

        if ($preseasonDeadlineService->isLockedForUser($season, auth()->user())) {
            abort(403);
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

    private function validatePreseasonAnswer(Season $season, SeasonPreseasonQuestion $question, string $answer): void
    {
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

    private function ensureJourneeHasDeadline(Journee $journee): void
    {
        if (! $journee->prediction_deadline) {
            abort(404);
        }
    }
}
