<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreseasonCorrectionGroupTemplate extends Model
{
    protected $fillable = [
        'label',
        'code',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->belongsToMany(
            PreseasonPredictionTemplate::class,
            'preseason_correction_group_template_questions',
            'preseason_correction_group_template_id',
            'preseason_prediction_template_id'
        )
            ->withTimestamps()
            ->orderBy('position');
    }
}
