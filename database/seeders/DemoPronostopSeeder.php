<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Season;
use App\Models\User;
use App\Services\GlobalSetupHashService;
use App\Services\SeasonJourneeGenerator;
use App\Services\SeasonPreseasonSetupService;
use App\Services\SeasonScoringSetupService;
use App\Support\PlayerColorPalette;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use App\Services\ScoringService;

class DemoPronostopSeeder extends Seeder
{
    private const DEMO_SEASON_SLUG = 'top-14-2025-2026-demo';

    private array $clubCache = [];

    private array $columnCache = [];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function () {
            if (Schema::hasColumn('seasons', 'slug')) {
                Season::query()
                    ->where('slug', self::DEMO_SEASON_SLUG)
                    ->delete();
            }

            $users = $this->seedUsers();
            $season = $this->seedSeason();

            $this->syncSeasonClubs($season);
            $this->syncSeasonPlayers($season, $users);

            app(SeasonScoringSetupService::class)->copyJourneeScoringProfilesToSeason($season);
            app(SeasonPreseasonSetupService::class)->copyTemplatesToSeason($season);

            $this->ensureSeasonCorrectionGroups($season);

            app(SeasonJourneeGenerator::class)->generate($season);

            $this->seedJourneeDates($season);
            $this->seedRegularMatchesAndPronos($season, $users);
            $this->seedPreseasonResults($season);
            $this->seedPreseasonPredictions($season, $users);
            $this->seedPreseasonBonusScores($season, $users);
            $this->recalculateRegularJourneeScores($season);
        });
    }

    private function seedUsers(): Collection
    {
        $colors = array_values(PlayerColorPalette::colors());

        $users = [
            [
                'name' => 'Julien Admin',
                'nickname' => 'AD01',
                'email' => 'julien.admin@example.com',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Claire Organisatrice',
                'nickname' => 'CO02',
                'email' => 'claire.organisatrice@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Alexis Lemaire',
                'nickname' => 'AL03',
                'email' => 'alexis.lemaire@example.com',
                'role' => 'player',
            ],
            [
                'name' => 'Bastien Roussel',
                'nickname' => 'BR04',
                'email' => 'bastien.roussel@example.com',
                'role' => 'player',
            ],
            [
                'name' => 'Chloe Martin',
                'nickname' => 'CH05',
                'email' => 'chloe.martin@example.com',
                'role' => 'player',
            ],
            [
                'name' => 'Damien Lopez',
                'nickname' => 'DL06',
                'email' => 'damien.lopez@example.com',
                'role' => 'player',
            ],
            [
                'name' => 'Eva Moreau',
                'nickname' => 'EV07',
                'email' => 'eva.moreau@example.com',
                'role' => 'player',
            ],
            [
                'name' => 'Farid Mercier',
                'nickname' => 'FM08',
                'email' => 'farid.mercier@example.com',
                'role' => 'player',
            ],
            [
                'name' => 'Louise Girard',
                'nickname' => 'LG09',
                'email' => 'louise.girard@example.com',
                'role' => 'player',
            ],
        ];

        return collect($users)->map(function (array $data, int $index) use ($colors) {
            $user = User::query()->where('nickname', $data['nickname'])->first() ?? new User();

            $payload = $this->tablePayload('users', [
                'name' => $data['name'],
                'nickname' => $data['nickname'],
                'email' => $data['email'],
                'email_pro' => $data['email'],
                'email_perso' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => $data['role'],
                'color' => $colors[$index] ?? '#000000',
                'last_login_at' => $index < 4 ? now()->subDays($index) : null,
            ]);

            $user->forceFill($payload)->save();

            return $user->refresh();
        })->values();
    }

    private function seedSeason(): Season
    {
        if (Schema::hasColumn('seasons', 'is_active')) {
            Season::query()->update([
                'is_active' => false,
            ]);
        }

        $season = new Season();

        $payload = $this->tablePayload('seasons', [
            'name' => 'TOP 14 2025-2026 - Demo',
            'slug' => self::DEMO_SEASON_SLUG,
            'top14_clubs_count' => 14,
            'prod2_clubs_count' => 16,
            'journee_scoring_setup_hash' => app(GlobalSetupHashService::class)->journeeScoringHash(),
            'preseason_setup_hash' => app(GlobalSetupHashService::class)->preseasonHash(),
            'is_active' => true,
            'is_locked' => false,
        ]);

        $season->forceFill($payload)->save();

        return $season->refresh();
    }

    private function syncSeasonClubs(Season $season): void
    {
        $top14Slugs = [
            'bayonne',
            'clermont',
            'castres',
            'lyon',
            'montpellier',
            'toulon',
            'racing',
            'pau',
            'stade-francais',
            'la-rochelle',
            'toulouse',
            'montauban',
            'perpignan',
            'bordeaux-begles',
        ];

        $prod2Slugs = [
            'beziers',
            'biarritz',
            'brive',
            'colomiers',
            'grenoble',
            'oyonnax',
            'provence',
            'vannes',
            'agen',
            'soyaux-angouleme',
            'aurillac',
            'mont-de-marsan',
            'carcassonne',
            'dax',
            'nevers',
            'valence-romans',
        ];

        foreach ($top14Slugs as $slug) {
            $this->syncSeasonClub($season, $this->club($slug), 'top14');
        }

        foreach ($prod2Slugs as $slug) {
            $this->syncSeasonClub($season, $this->club($slug), 'prod2');
        }
    }

    private function syncSeasonClub(Season $season, Club $club, string $competition): void
    {
        DB::table('club_season')->updateOrInsert(
            [
                'season_id' => $season->id,
                'club_id' => $club->id,
            ],
            $this->tablePayload('club_season', [
                'competition' => $competition,
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );
    }

    private function syncSeasonPlayers(Season $season, Collection $users): void
    {
        $defaultDeadline = CarbonImmutable::now('Europe/Paris')->subWeeks(3)->setTime(20, 0);
        $latePlayerDeadline = CarbonImmutable::now('Europe/Paris')->addWeek()->setTime(20, 0);

        foreach ($users->values() as $index => $user) {
            DB::table('season_user')->updateOrInsert(
                [
                    'season_id' => $season->id,
                    'user_id' => $user->id,
                ],
                $this->tablePayload('season_user', [
                    'display_order' => $index + 1,
                    'preseason_prediction_deadline' => $user->nickname === 'LG09'
                        ? $latePlayerDeadline
                        : $defaultDeadline,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function ensureSeasonCorrectionGroups(Season $season): void
    {
        if (! Schema::hasTable('preseason_correction_group_templates') || ! Schema::hasTable('season_preseason_correction_groups')) {
            return;
        }

        $templatePivotTable = 'preseason_correction_group_template_questions';
        $seasonPivotTable = 'season_preseason_correction_group_questions';

        if (! Schema::hasTable($templatePivotTable) || ! Schema::hasTable($seasonPivotTable)) {
            return;
        }

        $templateGroups = DB::table('preseason_correction_group_templates')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        foreach ($templateGroups as $templateGroup) {
            $seasonGroupId = DB::table('season_preseason_correction_groups')
                ->where('season_id', $season->id)
                ->where('source_template_id', $templateGroup->id)
                ->value('id');

            $payload = $this->tablePayload('season_preseason_correction_groups', [
                'season_id' => $season->id,
                'source_template_id' => $templateGroup->id,
                'label' => $templateGroup->label,
                'code' => $templateGroup->code,
                'position' => $templateGroup->position,
                'is_active' => true,
                'updated_at' => now(),
            ]);

            if ($seasonGroupId) {
                DB::table('season_preseason_correction_groups')
                    ->where('id', $seasonGroupId)
                    ->update($payload);
            } else {
                $payload = $this->tablePayload('season_preseason_correction_groups', array_merge($payload, [
                    'created_at' => now(),
                ]));

                $seasonGroupId = DB::table('season_preseason_correction_groups')->insertGetId($payload);
            }

            $templateQuestionIds = DB::table($templatePivotTable)
                ->where('preseason_correction_group_template_id', $templateGroup->id)
                ->pluck('preseason_prediction_template_id');

            $seasonQuestionIds = DB::table('season_preseason_questions')
                ->where('season_id', $season->id)
                ->whereIn('source_template_id', $templateQuestionIds)
                ->pluck('id');

            foreach ($seasonQuestionIds as $seasonQuestionId) {
                DB::table($seasonPivotTable)->updateOrInsert(
                    [
                        'season_preseason_correction_group_id' => $seasonGroupId,
                        'season_preseason_question_id' => $seasonQuestionId,
                    ],
                    $this->tablePayload($seasonPivotTable, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    private function seedJourneeDates(Season $season): void
    {
        $base = CarbonImmutable::now('Europe/Paris')->setTime(20, 45);

        $dates = [
            1 => $base->subWeeks(2),
            2 => $base->subWeek(),
            3 => $base->addWeek(),
        ];

        foreach ($dates as $number => $startsAt) {
            $journee = $season->journees()
                ->where('type', 'regular')
                ->where('number', $number)
                ->first();

            if (! $journee) {
                continue;
            }

            $journee->forceFill($this->tablePayload('journees', [
                'starts_at' => $startsAt,
                'prediction_deadline' => $startsAt->subMinutes(15),
            ]))->save();
        }
    }

    private function seedRegularMatchesAndPronos(Season $season, Collection $users): void
    {
        $journees = $season->journees()
            ->where('type', 'regular')
            ->whereIn('number', [1, 2, 3])
            ->get()
            ->keyBy('number');

        $matchesByJournee = [
            1 => [
                ['home' => 'toulouse', 'away' => 'bayonne', 'result' => 'v', 'tries' => 7, 'home_bonus' => 'o', 'away_bonus' => '-'],
                ['home' => 'la-rochelle', 'away' => 'toulon', 'result' => 'd', 'tries' => 5, 'home_bonus' => '-', 'away_bonus' => 'o'],
                ['home' => 'bordeaux-begles', 'away' => 'perpignan', 'result' => 'v', 'tries' => 6, 'home_bonus' => 'o', 'away_bonus' => '-'],
                ['home' => 'castres', 'away' => 'racing', 'result' => 'n', 'tries' => 4, 'home_bonus' => '-', 'away_bonus' => '-'],
                ['home' => 'clermont', 'away' => 'lyon', 'result' => 'd', 'tries' => 3, 'home_bonus' => '-', 'away_bonus' => 'd'],
                ['home' => 'montpellier', 'away' => 'pau', 'result' => 'v', 'tries' => 2, 'home_bonus' => '-', 'away_bonus' => '-'],
                ['home' => 'stade-francais', 'away' => 'montauban', 'result' => 'v', 'tries' => 5, 'home_bonus' => 'o', 'away_bonus' => '-'],
            ],
            2 => [
                ['home' => 'bayonne', 'away' => 'la-rochelle', 'result' => 'v', 'tries' => 4, 'home_bonus' => '-', 'away_bonus' => 'd'],
                ['home' => 'toulon', 'away' => 'bordeaux-begles', 'result' => 'v', 'tries' => 3, 'home_bonus' => '-', 'away_bonus' => '-'],
                ['home' => 'perpignan', 'away' => 'castres', 'result' => 'd', 'tries' => 5, 'home_bonus' => '-', 'away_bonus' => 'o'],
                ['home' => 'racing', 'away' => 'clermont', 'result' => 'v', 'tries' => 6, 'home_bonus' => 'o', 'away_bonus' => '-'],
                ['home' => 'lyon', 'away' => 'montpellier', 'result' => 'n', 'tries' => 2, 'home_bonus' => '-', 'away_bonus' => '-'],
                ['home' => 'pau', 'away' => 'stade-francais', 'result' => 'd', 'tries' => 4, 'home_bonus' => '-', 'away_bonus' => 'd'],
                ['home' => 'montauban', 'away' => 'toulouse', 'result' => 'd', 'tries' => 8, 'home_bonus' => '-', 'away_bonus' => 'o'],
            ],
            3 => [
                ['home' => 'toulouse', 'away' => 'toulon', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
                ['home' => 'bordeaux-begles', 'away' => 'bayonne', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
                ['home' => 'la-rochelle', 'away' => 'castres', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
                ['home' => 'racing', 'away' => 'lyon', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
                ['home' => 'montpellier', 'away' => 'stade-francais', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
                ['home' => 'pau', 'away' => 'perpignan', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
                ['home' => 'clermont', 'away' => 'montauban', 'result' => null, 'tries' => null, 'home_bonus' => null, 'away_bonus' => null],
            ],
        ];

        $matchIndex = 0;

        foreach ($matchesByJournee as $journeeNumber => $matches) {
            $journee = $journees->get($journeeNumber);

            if (! $journee) {
                continue;
            }

            foreach ($matches as $position => $match) {
                $isFinished = $match['result'] !== null;

                $matchId = DB::table('match_games')->insertGetId($this->tablePayload('match_games', [
                    'journee_id' => $journee->id,
                    'home_club_id' => $this->club($match['home'])->id,
                    'away_club_id' => $this->club($match['away'])->id,
                    'position' => $position + 1,
                    'actual_result' => $match['result'],
                    'actual_tries' => $match['tries'],
                    'actual_home_bonus' => $match['home_bonus'],
                    'actual_away_bonus' => $match['away_bonus'],
                    'is_finished' => $isFinished,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                $this->seedPronosForMatch($users, $matchId, $match, $matchIndex);
                $matchIndex++;
            }
        }
    }

    private function seedPronosForMatch(Collection $users, int $matchId, array $match, int $matchIndex): void
    {
        $resultOptions = ['v', 'n', 'd'];

        foreach ($users->values() as $userIndex => $user) {
            $actualResult = $match['result'];
            $actualTries = $match['tries'];

            if ($actualResult) {
                $predictedResult = (($userIndex + $matchIndex) % 3 === 0)
                    ? $actualResult
                    : $resultOptions[($userIndex + $matchIndex + 1) % count($resultOptions)];

                $predictedTries = max(0, (int) $actualTries + (($userIndex % 3) - 1));
                $predictedHomeBonus = (($userIndex + $matchIndex) % 4 === 0) ? ($match['home_bonus'] ?? '-') : '-';
                $predictedAwayBonus = (($userIndex + $matchIndex) % 5 === 0) ? ($match['away_bonus'] ?? '-') : '-';

                $points = 0;
                $points += $predictedResult === $actualResult ? 2 : 0;
                $points += $predictedTries === (int) $actualTries ? 2 : 0;
                $points += $predictedHomeBonus === ($match['home_bonus'] ?? '-') ? 1 : 0;
                $points += $predictedAwayBonus === ($match['away_bonus'] ?? '-') ? 1 : 0;
            } else {
                $predictedResult = $resultOptions[($userIndex + $matchIndex) % count($resultOptions)];
                $predictedTries = 3 + (($userIndex + $matchIndex) % 4);
                $predictedHomeBonus = '-';
                $predictedAwayBonus = '-';
                $points = 0;
            }

            DB::table('pronos')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'match_game_id' => $matchId,
                ],
                $this->tablePayload('pronos', [
                    'predicted_result' => $predictedResult,
                    'predicted_tries' => $predictedTries,
                    'predicted_home_bonus' => $predictedHomeBonus,
                    'predicted_away_bonus' => $predictedAwayBonus,
                    'points' => $points,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedPreseasonResults(Season $season): void
    {
        $questions = $season->preseasonQuestions()
            ->get()
            ->keyBy('position');

        foreach ($this->officialPreseasonResults() as $position => $result) {
            $question = $questions->get($position);

            if (! $question) {
                continue;
            }

            $clubId = isset($result['club']) ? $this->club($result['club'])->id : null;
            $textAnswer = $result['text'] ?? null;

            $question->forceFill($this->tablePayload('season_preseason_questions', [
                'correct_club_id' => $clubId,
                'correct_text_answer' => $textAnswer,
                'corrected_at' => now(),
                'result_club_id' => $clubId,
                'result_text_answer' => $textAnswer,
                'result_recorded_at' => now(),
            ]))->save();
        }
    }

    private function seedPreseasonPredictions(Season $season, Collection $users): void
    {
        $questions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->keyBy('position');

        $officialResults = $this->officialPreseasonResults();

        foreach ($users->values() as $userIndex => $user) {
            $picks = $this->preseasonPicksForUser($userIndex);
            $awardedTop14Semifinalists = [];
            $awardedProd2Semifinalists = [];

            foreach ($questions as $question) {
                $position = (int) $question->position;
                $clubSlug = $picks['clubs'][$position] ?? null;
                $textAnswer = $picks['texts'][$position] ?? null;
                $clubId = $clubSlug ? $this->club($clubSlug)->id : null;

                [$isCorrect, $points] = $this->evaluatePreseasonPrediction(
                    $position,
                    (int) $question->points,
                    $clubId,
                    $textAnswer,
                    $officialResults,
                    $awardedTop14Semifinalists,
                    $awardedProd2Semifinalists
                );

                DB::table('season_preseason_predictions')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'question_id' => $question->id,
                    ],
                    $this->tablePayload('season_preseason_predictions', [
                        'season_id' => $season->id,
                        'answer_type' => $question->answer_type,
                        'club_id' => $clubId,
                        'text_answer' => $textAnswer,
                        'is_correct' => $isCorrect,
                        'points' => $points,
                        'submitted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    private function evaluatePreseasonPrediction(
        int $position,
        int $questionPoints,
        ?int $clubId,
        ?string $textAnswer,
        array $officialResults,
        array &$awardedTop14Semifinalists,
        array &$awardedProd2Semifinalists
    ): array {
        if (! isset($officialResults[$position])) {
            return [null, 0];
        }

        $official = $officialResults[$position];

        if (in_array($position, [30, 40, 50, 60], true)) {
            return $this->evaluateUnorderedClubPrediction(
                $clubId,
                ['toulouse', 'bordeaux-begles', 'toulon', 'la-rochelle'],
                $questionPoints,
                $awardedTop14Semifinalists
            );
        }

        if (in_array($position, [110, 120, 130, 140], true)) {
            return $this->evaluateUnorderedClubPrediction(
                $clubId,
                ['vannes', 'grenoble', 'brive', 'provence'],
                $questionPoints,
                $awardedProd2Semifinalists
            );
        }

        if (isset($official['club'])) {
            $isCorrect = $clubId === $this->club($official['club'])->id;

            return [$isCorrect, $isCorrect ? $questionPoints : 0];
        }

        if (isset($official['text'])) {
            $isCorrect = $this->normalizeText($textAnswer) === $this->normalizeText($official['text']);

            return [$isCorrect, $isCorrect ? $questionPoints : 0];
        }

        return [null, 0];
    }

    private function evaluateUnorderedClubPrediction(?int $clubId, array $officialClubSlugs, int $questionPoints, array &$awardedClubIds): array
    {
        if (! $clubId) {
            return [false, 0];
        }

        $officialClubIds = collect($officialClubSlugs)
            ->map(fn (string $slug) => $this->club($slug)->id)
            ->all();

        if (! in_array($clubId, $officialClubIds, true)) {
            return [false, 0];
        }

        if (in_array($clubId, $awardedClubIds, true)) {
            return [false, 0];
        }

        $awardedClubIds[] = $clubId;

        return [true, $questionPoints];
    }

    private function seedPreseasonBonusScores(Season $season, Collection $users): void
    {
        if (! Schema::hasTable('season_preseason_bonus_rules') || ! Schema::hasTable('season_preseason_user_bonus_scores')) {
            return;
        }

        $pivotTable = 'season_preseason_bonus_rule_questions';

        if (! Schema::hasTable($pivotTable)) {
            return;
        }

        $bonusRuleColumn = Schema::hasColumn($pivotTable, 'bonus_rule_id')
            ? 'bonus_rule_id'
            : 'season_preseason_bonus_rule_id';

        $questionColumn = Schema::hasColumn($pivotTable, 'question_id')
            ? 'question_id'
            : 'season_preseason_question_id';

        $bonusRules = DB::table('season_preseason_bonus_rules')
            ->where('season_id', $season->id)
            ->where('is_active', true)
            ->get();

        foreach ($bonusRules as $bonusRule) {
            $questionIds = DB::table($pivotTable)
                ->where($bonusRuleColumn, $bonusRule->id)
                ->pluck($questionColumn);

            foreach ($users as $user) {
                $correctQuestionIds = DB::table('season_preseason_predictions')
                    ->where('season_id', $season->id)
                    ->where('user_id', $user->id)
                    ->whereIn('question_id', $questionIds)
                    ->where('is_correct', true)
                    ->pluck('question_id');

                $isAwarded = $questionIds->isNotEmpty()
                    && $questionIds->diff($correctQuestionIds)->isEmpty();

                DB::table('season_preseason_user_bonus_scores')->updateOrInsert(
                    [
                        'season_id' => $season->id,
                        'user_id' => $user->id,
                        'season_preseason_bonus_rule_id' => $bonusRule->id,
                    ],
                    $this->tablePayload('season_preseason_user_bonus_scores', [
                        'is_awarded' => $isAwarded,
                        'points' => $isAwarded ? (int) $bonusRule->points : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    private function officialPreseasonResults(): array
    {
        return [
            10 => ['club' => 'toulouse'],
            20 => ['club' => 'bordeaux-begles'],
            30 => ['club' => 'toulouse'],
            40 => ['club' => 'bordeaux-begles'],
            50 => ['club' => 'toulon'],
            60 => ['club' => 'la-rochelle'],
            70 => ['club' => 'perpignan'],
            80 => ['club' => 'montauban'],
            90 => ['club' => 'grenoble'],
            100 => ['club' => 'vannes'],
            110 => ['club' => 'vannes'],
            120 => ['club' => 'grenoble'],
            130 => ['club' => 'brive'],
            140 => ['club' => 'provence'],
            150 => ['text' => 'Thomas Ramos'],
            160 => ['text' => 'Damian Penaud'],
        ];
    }

    private function preseasonPicksForUser(int $index): array
    {
        $variants = [
            [
                'clubs' => [
                    10 => 'toulouse',
                    20 => 'bordeaux-begles',
                    30 => 'toulouse',
                    40 => 'bordeaux-begles',
                    50 => 'toulon',
                    60 => 'la-rochelle',
                    70 => 'perpignan',
                    80 => 'montauban',
                    90 => 'grenoble',
                    100 => 'vannes',
                    110 => 'vannes',
                    120 => 'grenoble',
                    130 => 'brive',
                    140 => 'provence',
                ],
                'texts' => [
                    150 => 'Thomas Ramos',
                    160 => 'Damian Penaud',
                ],
            ],
            [
                'clubs' => [
                    10 => 'toulon',
                    20 => 'toulouse',
                    30 => 'toulon',
                    40 => 'toulouse',
                    50 => 'la-rochelle',
                    60 => 'bordeaux-begles',
                    70 => 'montauban',
                    80 => 'perpignan',
                    90 => 'vannes',
                    100 => 'grenoble',
                    110 => 'grenoble',
                    120 => 'vannes',
                    130 => 'provence',
                    140 => 'brive',
                ],
                'texts' => [
                    150 => 'Thomas Ramos',
                    160 => 'Louis Bielle-Biarrey',
                ],
            ],
            [
                'clubs' => [
                    10 => 'bordeaux-begles',
                    20 => 'toulon',
                    30 => 'toulouse',
                    40 => 'la-rochelle',
                    50 => 'racing',
                    60 => 'toulon',
                    70 => 'perpignan',
                    80 => 'pau',
                    90 => 'grenoble',
                    100 => 'brive',
                    110 => 'vannes',
                    120 => 'grenoble',
                    130 => 'oyonnax',
                    140 => 'provence',
                ],
                'texts' => [
                    150 => 'Thomas Ramos',
                    160 => 'Damian Penaud',
                ],
            ],
            [
                'clubs' => [
                    10 => 'la-rochelle',
                    20 => 'bordeaux-begles',
                    30 => 'bordeaux-begles',
                    40 => 'toulon',
                    50 => 'toulouse',
                    60 => 'lyon',
                    70 => 'montauban',
                    80 => 'perpignan',
                    90 => 'brive',
                    100 => 'vannes',
                    110 => 'brive',
                    120 => 'provence',
                    130 => 'vannes',
                    140 => 'grenoble',
                ],
                'texts' => [
                    150 => 'Melvyn Jaminet',
                    160 => 'Damian Penaud',
                ],
            ],
            [
                'clubs' => [
                    10 => 'toulouse',
                    20 => 'la-rochelle',
                    30 => 'toulouse',
                    40 => 'bordeaux-begles',
                    50 => 'la-rochelle',
                    60 => 'castres',
                    70 => 'montauban',
                    80 => 'perpignan',
                    90 => 'provence',
                    100 => 'grenoble',
                    110 => 'vannes',
                    120 => 'grenoble',
                    130 => 'brive',
                    140 => 'provence',
                ],
                'texts' => [
                    150 => 'Thomas Ramos',
                    160 => 'Damian Penaud',
                ],
            ],
        ];

        return $variants[$index % count($variants)];
    }

    private function normalizeText(?string $value): string
    {
        return Str::of($value ?? '')
            ->squish()
            ->lower()
            ->toString();
    }

    private function club(string $slug): Club
    {
        if (! array_key_exists($slug, $this->clubCache)) {
            $this->clubCache[$slug] = Club::query()->where('slug', $slug)->first();
        }

        if (! $this->clubCache[$slug]) {
            throw new RuntimeException("Club introuvable pour le slug [{$slug}]. Lance d'abord Clubs20252026Seeder.");
        }

        return $this->clubCache[$slug];
    }

    private function tablePayload(string $table, array $payload): array
    {
        return array_intersect_key($payload, $this->tableColumns($table));
    }

    private function tableColumns(string $table): array
    {
        if (! array_key_exists($table, $this->columnCache)) {
            $this->columnCache[$table] = array_flip(Schema::getColumnListing($table));
        }

        return $this->columnCache[$table];
    }

    private function recalculateRegularJourneeScores(Season $season): void
    {
        $scoringService = app(ScoringService::class);

        $journees = $season->journees()
            ->whereHas('matches', function ($query) {
                $query->where('is_finished', true)
                    ->whereNotNull('actual_result');
            })
            ->with([
                'matches.pronos.user',
                'matches.journee.season.scoringRules',
                'matches.journee.season.journeeTypeScoringProfiles.profile.rules',
            ])
            ->get();

        foreach ($journees as $journee) {
            $userIds = collect();

            foreach ($journee->matches as $match) {
                if (! $match->is_finished || ! $match->actual_result) {
                    continue;
                }

                foreach ($match->pronos as $prono) {
                    $prono->update([
                        'points' => $scoringService->calculateMatchPoints($prono, $match),
                    ]);

                    if ($prono->user_id) {
                        $userIds->push($prono->user_id);
                    }
                }
            }

            foreach ($userIds->unique() as $userId) {
                $user = User::find($userId);

                if ($user) {
                    $scoringService->updateJourneeUserScore($user, $journee);
                }
            }

            $scoringService->updateJourneeRanking($journee);
        }
    }
}
