<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneeTypeScoringProfile extends Model
{
    //
    protected $fillable = [
        'journee_type',
        'scoring_profile_id',
    ];

    public function profile()
    {
        return $this->belongsTo(ScoringProfile::class, 'scoring_profile_id');
    }
}
