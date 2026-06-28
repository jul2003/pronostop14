<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonPreseasonQuestion extends Model
{
    protected $fillable = [
        'season_id',
        'source_template_id',
        'scoring_profile_id',
        'label',
        'answer_type',
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

    public function hasOfficialResult(): bool
    {
        if ($this->answer_type === 'free_text') {
            return filled($this->result_text_answer);
        }

        return $this->result_club_id !== null;
    }

    public function usesUnorderedCorrectionGroup(): bool
    {
        return filled($this->correction_group)
            && $this->correction_mode === 'unordered'
            && $this->answer_type !== 'free_text';
    }
}
