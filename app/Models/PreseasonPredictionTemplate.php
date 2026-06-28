<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreseasonPredictionTemplate extends Model
{
    protected $fillable = [
        'label',
        'answer_type',
        'correction_group',
        'correction_mode',
        'scoring_profile_id',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function profile()
    {
        return $this->belongsTo(ScoringProfile::class, 'scoring_profile_id');
    }
}
