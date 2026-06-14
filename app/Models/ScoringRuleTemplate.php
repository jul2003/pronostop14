<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringRuleTemplate extends Model
{
    //
    protected $fillable = [
        'scoring_profile_id',
        'code',
        'label',
        'points',
        'position',
    ];

    public function profile()
    {
        return $this->belongsTo(ScoringProfile::class, 'scoring_profile_id');
    }
}
