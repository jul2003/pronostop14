<?php

namespace App\Http\Controllers;

use App\Models\Journee;
use App\Models\JourneeUserScore;
use App\Models\MatchGame;
use App\Models\Prono;
use App\Models\Season;
use App\Models\SeasonPreseasonUserBonusScore;
use App\Services\AppSettingService;
use App\Services\PreseasonDeadlineService;

class RankingController extends Controller
{
    public function general(Season $season)
    {
        $scores = $season->players()
            ->with(['journeeScores' => function ($query) use ($season) {
                $query->whereHas('journee', function ($query) use ($season) {
                    $query->where('season_id', $season->id);
                });
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user,
                    'total_points' => $user->journeeScores->sum('total_points'),
                ];
            })
            ->sortByDesc('total_points')
            ->values();

        $rank = 0;
        $position = 0;
        $previousPoints = null;

        $scores = $scores->map(function ($score) use (&$rank, &$position, &$previousPoints) {
            $position++;

            if ($previousPoints !== $score['total_points']) {
                $rank = $position;
            }

            $score['rank'] = $rank;
            $previousPoints = $score['total_points'];

            return $score;
        });

        return view('rankings.general', [
            'season' => $season,
            'scores' => $scores,
        ]);
    }

    public function journee(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        if (! $journee->isLocked()) {
            abort(403, 'Le classement de cette journée sera visible après la clôture des pronostics.');
        }

        $playerIds = $season->players()
            ->pluck('users.id');

        $scores = JourneeUserScore::with('user')
            ->where('journee_id', $journee->id)
            ->whereIn('user_id', $playerIds)
            ->orderBy('rank')
            ->orderByDesc('total_points')
            ->get();

        return view('rankings.journee', [
            'season' => $season,
            'journee' => $journee,
            'scores' => $scores,
        ]);
    }

    public function journeeResults(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        if (! $journee->isLocked()) {
            abort(403, 'Les résultats de cette journée ne sont pas encore visibles.');
        }

        $matches = $journee->matches()
            ->with(['homeClub', 'awayClub', 'pronos.user'])
            ->orderBy('position')
            ->get();

        $players = $season->players()
            ->get();

        return view('journees.results', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'players' => $players,
        ]);
    }

    public function resultsIndex()
    {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            return redirect()
                ->route('home')
                ->with('error', 'Aucune saison active pour le moment.');
        }

        return redirect()->route('results.season', $season);
    }

    public function resultsSeason(
        Season $season,
        PreseasonDeadlineService $preseasonDeadlineService,
        AppSettingService $appSettingService
    ) {
        return view('seasons.results', $this->buildResultsViewData(
            season: $season,
            selectedJournee: null,
            preseasonDeadlineService: $preseasonDeadlineService,
            appSettingService: $appSettingService,
        ));
    }

    public function resultsJournee(
        Season $season,
        Journee $journee,
        PreseasonDeadlineService $preseasonDeadlineService,
        AppSettingService $appSettingService
    ) {
        abort_if($journee->season_id !== $season->id, 404);
        abort_if($journee->type === 'preseason', 404);

        return view('seasons.results', $this->buildResultsViewData(
            season: $season,
            selectedJournee: $journee,
            preseasonDeadlineService: $preseasonDeadlineService,
            appSettingService: $appSettingService,
        ));
    }

    public function seasonResults(Season $season)
    {
        return redirect()->route('results.season', $season);
    }

    public function bilanIndex()
    {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            return redirect()
                ->route('home')
                ->with('error', 'Aucune saison active pour le moment.');
        }

        return redirect()->route('bilan.season', $season);
    }

    public function bilanSeason(Season $season)
    {
        $seasons = Season::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return view('bilan.index', [
            'seasons' => $seasons,
            'selectedSeason' => $season,
        ]);
    }

    private function buildResultsViewData(
        Season $season,
        ?Journee $selectedJournee,
        PreseasonDeadlineService $preseasonDeadlineService,
        AppSettingService $appSettingService,
    ): array {
        $season->load([
            'scoringRules',
            'journeeTypeScoringProfiles.profile.rules',
        ]);

        $seasons = Season::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $players = $season->players()
            ->get();

        $preseasonDeadline = $preseasonDeadlineService->deadlineForUser($season, auth()->user());
        $preseasonIsVisible = $preseasonDeadline
            ? $preseasonDeadlineService->isLockedForUser($season, auth()->user())
            : false;

        $preseasonQuestions = collect();
        $preseasonBonusRules = collect();
        $preseasonBonusScoresByRule = collect();

        if ($preseasonIsVisible) {
            $preseasonQuestions = $season->preseasonQuestions()
                ->where('is_active', true)
                ->with([
                    'resultClub',
                    'predictions.user',
                    'predictions.club',
                ])
                ->orderBy('position')
                ->get();

            $preseasonBonusRules = $season->preseasonBonusRules()
                ->where('is_active', true)
                ->with(['questions' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('position');
                }])
                ->orderBy('position')
                ->get();

            $preseasonBonusScoresByRule = SeasonPreseasonUserBonusScore::with(['user', 'bonusRule'])
                ->where('season_id', $season->id)
                ->get()
                ->groupBy('season_preseason_bonus_rule_id');
        }

        $journees = $season->journees()
            ->where('type', '!=', 'preseason')
            ->with([
                'matches' => function ($query) {
                    $query->with(['homeClub', 'awayClub', 'pronos.user'])
                        ->orderBy('position');
                },
                'userScores',
            ])
            ->withCount('matches')
            ->orderByRaw("
                CASE
                    WHEN type = 'regular' THEN 1
                    WHEN type = 'top14_playoff' THEN 2
                    WHEN type = 'access_match' THEN 3
                    WHEN type = 'top14_semifinal' THEN 4
                    WHEN type = 'prod2_final' THEN 5
                    WHEN type = 'top14_final' THEN 6
                    ELSE 99
                END
            ")
            ->orderBy('number')
            ->orderBy('id')
            ->get();

        $preseasonQuestionTotals = [];
        $preseasonBonusTotals = [];
        $preseasonTotals = [];
        $preseasonBonusQuestionHighlights = [];
        $journeeTotals = [];
        $journeeMatchPoints = [];
        $journeePerfectBonuses = [];
        $rankingRows = [];
        $matchBreakdowns = [];

        foreach ($players as $player) {
            $preseasonQuestionTotals[$player->id] = 0;
            $preseasonBonusTotals[$player->id] = 0;
            $preseasonTotals[$player->id] = 0;
            $journeeTotals[$player->id] = 0;
        }

        if ($preseasonIsVisible) {
            foreach ($preseasonQuestions as $question) {
                foreach ($players as $player) {
                    $prediction = $question->predictions->firstWhere('user_id', $player->id);
                    $preseasonQuestionTotals[$player->id] += (int) ($prediction?->points ?? 0);
                }
            }

            foreach ($players as $player) {
                $stopFollowingBonuses = false;

                foreach ($preseasonBonusRules as $bonusRule) {
                    $scores = $preseasonBonusScoresByRule->get($bonusRule->id, collect());
                    $score = $scores->firstWhere('user_id', $player->id);

                    $isAwarded = ! $stopFollowingBonuses && (bool) ($score?->is_awarded ?? false);
                    $points = $isAwarded ? (int) ($score?->points ?? $bonusRule->points) : 0;

                    if ($isAwarded) {
                        $preseasonBonusTotals[$player->id] += $points;

                        foreach ($bonusRule->questions as $question) {
                            $preseasonBonusQuestionHighlights[$question->id][$player->id] = true;
                        }

                        if ($bonusRule->stop_after_match) {
                            $stopFollowingBonuses = true;
                        }
                    }
                }
            }

            foreach ($players as $player) {
                $preseasonTotals[$player->id] =
                    ($preseasonQuestionTotals[$player->id] ?? 0)
                    + ($preseasonBonusTotals[$player->id] ?? 0);
            }
        }

        foreach ($journees as $journee) {
            foreach ($players as $player) {
                $score = $journee->userScores->firstWhere('user_id', $player->id);

                $matchPoints = (int) ($score?->match_points ?? 0);
                $total = (int) ($score?->total_points ?? 0);
                $perfectBonus = $this->perfectJourneeBonusFromScore($score, $matchPoints, $total);

                $journeeMatchPoints[$journee->id][$player->id] = $matchPoints;
                $journeePerfectBonuses[$journee->id][$player->id] = $perfectBonus;

                if ($journee->isLocked()) {
                    $journeeTotals[$player->id] += $total;
                }
            }

            if (! $journee->isLocked()) {
                continue;
            }

            $rules = $this->rulesForJournee($journee);
            $stopOnWrongResult = $this->stopOnWrongResult($journee);

            foreach ($journee->matches as $match) {
                foreach ($players as $player) {
                    $prono = $match->pronos->firstWhere('user_id', $player->id);

                    $matchBreakdowns[$match->id][$player->id] = $this->matchBreakdown(
                        match: $match,
                        prono: $prono,
                        rules: $rules,
                        stopOnWrongResult: $stopOnWrongResult,
                    );
                }
            }
        }

        foreach ($players as $player) {
            $rankingRows[] = [
                'user' => $player,
                'journee_points' => $journeeTotals[$player->id] ?? 0,
                'preseason_points' => $preseasonTotals[$player->id] ?? 0,
                'total_points' => ($journeeTotals[$player->id] ?? 0) + ($preseasonTotals[$player->id] ?? 0),
            ];
        }

        $rankingRows = collect($rankingRows)
            ->sortByDesc('total_points')
            ->values();

        return [
            'seasons' => $seasons,
            'selectedSeason' => $season,
            'players' => $players,
            'rankingRows' => $rankingRows,
            'preseasonDeadline' => $preseasonDeadline,
            'preseasonIsVisible' => $preseasonIsVisible,
            'preseasonQuestions' => $preseasonQuestions,
            'preseasonBonusRules' => $preseasonBonusRules,
            'preseasonBonusQuestionHighlights' => $preseasonBonusQuestionHighlights,
            'preseasonQuestionTotals' => $preseasonQuestionTotals,
            'preseasonBonusTotals' => $preseasonBonusTotals,
            'preseasonTotals' => $preseasonTotals,
            'journees' => $journees,
            'selectedJournee' => $selectedJournee,
            'journeeMatchPoints' => $journeeMatchPoints,
            'journeePerfectBonuses' => $journeePerfectBonuses,
            'matchBreakdowns' => $matchBreakdowns,
            'resultColors' => [
                'correct' => $appSettingService->string('results_color_correct', '#008000'),
                'wrong' => $appSettingService->string('results_color_wrong', '#FF0000'),
                'bonus_offset' => $appSettingService->string(
                    'results_color_bonus_offset',
                    $appSettingService->string('results_color_bonus', '#92D050')
                ),
                'preseason_bonus' => $appSettingService->string('results_color_preseason_bonus', '#FFD966'),
            ],
        ];
    }

    private function matchBreakdown(
        MatchGame $match,
        ?Prono $prono,
        array $rules,
        bool $stopOnWrongResult,
    ): array {
        $empty = [
            'result_status' => 'neutral',
            'tries_status' => 'neutral',
            'home_bonus_status' => 'neutral',
            'away_bonus_status' => 'neutral',
            'match_points' => null,
        ];

        if (! $prono || ! $match->actual_result) {
            return $empty;
        }

        $resultIsCorrect = $prono->predicted_result === $match->actual_result;

        $breakdown = $empty;
        $breakdown['result_status'] = $resultIsCorrect ? 'good' : 'bad';
        $breakdown['match_points'] = (int) ($prono->points ?? 0);

        if (! $resultIsCorrect && $stopOnWrongResult) {
            return $breakdown;
        }

        if ($match->actual_tries !== null && $prono->predicted_tries !== null) {
            $difference = abs((int) $prono->predicted_tries - (int) $match->actual_tries);

            if ($difference === 0) {
                $breakdown['tries_status'] = 'good';
            } elseif ($difference === 1 && (($rules['tries_near'] ?? 0) !== 0)) {
                $breakdown['tries_status'] = 'bonus';
            } else {
                $breakdown['tries_status'] = 'bad';
            }
        }

        $breakdown['home_bonus_status'] = $this->bonusStatus(
            predictedBonus: $prono->predicted_home_bonus,
            actualBonus: $match->actual_home_bonus,
        );

        $breakdown['away_bonus_status'] = $this->bonusStatus(
            predictedBonus: $prono->predicted_away_bonus,
            actualBonus: $match->actual_away_bonus,
        );

        return $breakdown;
    }

    private function bonusStatus(?string $predictedBonus, ?string $actualBonus): string
    {
        if ($predictedBonus === null || $predictedBonus === '') {
            return 'neutral';
        }

        if ($actualBonus === null || $actualBonus === '') {
            return 'neutral';
        }

        if ($predictedBonus === $actualBonus) {
            return 'good';
        }

        return 'bonus';
    }

    private function perfectJourneeBonusFromScore(?JourneeUserScore $score, int $matchPoints, int $total): int
    {
        if (! $score) {
            return 0;
        }

        $value = $score->perfect_journee_bonus
            ?? $score->perfect_round_bonus
            ?? null;

        if ($value !== null) {
            return (int) $value;
        }

        return max(0, $total - $matchPoints);
    }

    private function rulesForJournee(Journee $journee): array
    {
        $journee->loadMissing([
            'season.scoringRules',
            'season.journeeTypeScoringProfiles.profile.rules',
        ]);

        $profile = $this->profileForJournee($journee);

        if ($profile) {
            return $profile->rules
                ->pluck('points', 'code')
                ->toArray();
        }

        return $journee->season
            ->scoringRules
            ->pluck('points', 'code')
            ->toArray();
    }

    private function stopOnWrongResult(Journee $journee): bool
    {
        $profile = $this->profileForJournee($journee);

        return (bool) ($profile?->stop_on_wrong_result ?? true);
    }

    private function profileForJournee(Journee $journee)
    {
        $journee->loadMissing([
            'season.journeeTypeScoringProfiles.profile.rules',
        ]);

        $mapping = $journee->season
            ->journeeTypeScoringProfiles
            ->firstWhere('journee_type', $journee->type);

        return $mapping?->profile;
    }
}
