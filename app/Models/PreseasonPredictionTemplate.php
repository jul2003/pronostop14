<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreseasonPredictionTemplate extends Model
{
    protected $fillable = [
        'label',
        'answer_type',
        'auto_result_rule',
        'auto_result_journee_number',
        'auto_result_position',
        'correction_group',
        'correction_mode',
        'scoring_profile_id',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_result_journee_number' => 'integer',
        'auto_result_position' => 'integer',
    ];

    public function profile()
    {
        return $this->belongsTo(ScoringProfile::class, 'scoring_profile_id');
    }

    public function correctionGroups()
    {
        return $this->belongsToMany(
            PreseasonCorrectionGroupTemplate::class,
            'preseason_correction_group_template_questions',
            'preseason_prediction_template_id',
            'preseason_correction_group_template_id'
        )->withTimestamps();
    }
}
