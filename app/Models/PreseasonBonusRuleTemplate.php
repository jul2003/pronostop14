<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreseasonBonusRuleTemplate extends Model
{
    protected $fillable = [
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

    public function questions()
    {
        return $this->belongsToMany(
            PreseasonPredictionTemplate::class,
            'preseason_bonus_rule_template_questions',
            'bonus_rule_template_id',
            'preseason_prediction_template_id'
        )->withTimestamps();
    }
}
