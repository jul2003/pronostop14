<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonPreseasonQuestion extends Model
{
    public const AUTO_RESULT_RULE_TOP14_POSITION = 'top14_position';

    public const AUTO_RESULT_RULE_TOP14_FINAL_WINNER = 'top14_final_winner';

    public const AUTO_RESULT_RULE_PROD2_FINAL_WINNER = 'prod2_final_winner';

    public const AUTO_RESULT_RULE_ACCESS_MATCH_WINNER = 'access_match_winner';

    protected $fillable = [
        'season_id',
        'source_template_id',
        'scoring_profile_id',
        'label',
        'answer_type',
        'auto_result_rule',
        'auto_result_journee_number',
        'auto_result_position',
        'correction_group',
        'correction_mode',
        'points',
        'result_club_id',
        'result_text_answer',
        'result_recorded_at',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'result_recorded_at' => 'datetime',
        'auto_result_journee_number' => 'integer',
        'auto_result_position' => 'integer',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function sourceTemplate()
    {
        return $this->belongsTo(PreseasonPredictionTemplate::class, 'source_template_id');
    }

    public function scoringProfile()
    {
        return $this->belongsTo(ScoringProfile::class, 'scoring_profile_id');
    }

    public function resultClub()
    {
        return $this->belongsTo(Club::class, 'result_club_id');
    }

    public function predictions()
    {
        return $this->hasMany(SeasonPreseasonPrediction::class, 'question_id');
    }

    public function bonusRules()
    {
        return $this->belongsToMany(
            SeasonPreseasonBonusRule::class,
            'season_preseason_bonus_rule_questions',
            'season_preseason_question_id',
            'season_preseason_bonus_rule_id'
        );
    }

    public function correctionGroups()
    {
        return $this->belongsToMany(
            SeasonPreseasonCorrectionGroup::class,
            'season_preseason_correction_group_questions',
            'season_preseason_question_id',
            'season_preseason_correction_group_id'
        )->withTimestamps();
    }

    public function hasOfficialResult(): bool
    {
        if ($this->answer_type === 'free_text') {
            return filled($this->result_text_answer);
        }

        return $this->result_club_id !== null;
    }

    public function supportsAutoResult(): bool
    {
        if (blank($this->auto_result_rule)) {
            return false;
        }

        return self::autoResultRuleSupportsAnswerType(
            $this->auto_result_rule,
            $this->answer_type
        );
    }

    public function autoResultRuleLabel(): string
    {
        return self::autoResultRuleOptions()[$this->auto_result_rule] ?? 'Aucun';
    }

    public static function autoResultRuleOptions(): array
    {
        return [
            self::AUTO_RESULT_RULE_TOP14_POSITION => 'Position TOP 14 à la journée cible',
            self::AUTO_RESULT_RULE_TOP14_FINAL_WINNER => 'Vainqueur finale TOP 14',
            self::AUTO_RESULT_RULE_PROD2_FINAL_WINNER => 'Vainqueur finale PRO D2',
            self::AUTO_RESULT_RULE_ACCESS_MATCH_WINNER => 'Vainqueur access match',
        ];
    }

    public static function autoResultRuleCompatibleAnswerTypes(string $rule): array
    {
        return match ($rule) {
            self::AUTO_RESULT_RULE_TOP14_POSITION,
            self::AUTO_RESULT_RULE_TOP14_FINAL_WINNER => [
                'top14_club',
                'season_club',
            ],

            self::AUTO_RESULT_RULE_PROD2_FINAL_WINNER => [
                'prod2_club',
                'season_club',
            ],

            self::AUTO_RESULT_RULE_ACCESS_MATCH_WINNER => [
                'top14_club',
                'prod2_club',
                'season_club',
            ],

            default => [],
        };
    }

    public static function autoResultRuleSupportsAnswerType(?string $rule, ?string $answerType): bool
    {
        if (blank($rule) || blank($answerType)) {
            return false;
        }

        return in_array(
            $answerType,
            self::autoResultRuleCompatibleAnswerTypes($rule),
            true
        );
    }

    public static function autoResultRuleRequiresJourneeNumber(?string $rule): bool
    {
        return $rule === self::AUTO_RESULT_RULE_TOP14_POSITION;
    }

    public static function autoResultRuleRequiresPosition(?string $rule): bool
    {
        return $rule === self::AUTO_RESULT_RULE_TOP14_POSITION;
    }
}
