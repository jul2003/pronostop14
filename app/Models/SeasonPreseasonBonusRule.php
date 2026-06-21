<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonPreseasonBonusRule extends Model
{
    protected $fillable = [
        'season_id',
        'source_template_id',
        'label',
        'points',
        'position',
        'is_active',
        'stop_after_match',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'stop_after_match' => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function sourceTemplate()
    {
        return $this->belongsTo(PreseasonBonusRuleTemplate::class, 'source_template_id');
    }

    public function questions()
    {
        return $this->belongsToMany(
            SeasonPreseasonQuestion::class,
            'season_preseason_bonus_rule_questions',
            'bonus_rule_id',
            'question_id'
        )->withTimestamps();
    }
}
