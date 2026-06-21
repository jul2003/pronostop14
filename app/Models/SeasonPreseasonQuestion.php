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
        'points',
        'position',
        'is_active',
        'correct_club_id',
        'correct_text_answer',
        'corrected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'corrected_at' => 'datetime',
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
        return $this->belongsTo(ScoringProfile::class);
    }

    public function correctClub()
    {
        return $this->belongsTo(Club::class, 'correct_club_id');
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
            'question_id',
            'bonus_rule_id'
        )->withTimestamps();
    }
}
