<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonJourneeTypeScoringProfile extends Model
{
    protected $fillable = [
        'season_id',
        'journee_type',
        'season_scoring_profile_id',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function profile()
    {
        return $this->belongsTo(SeasonScoringProfile::class, 'season_scoring_profile_id');
    }
}
