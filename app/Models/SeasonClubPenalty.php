<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonClubPenalty extends Model
{
    protected $fillable = [
        'season_id',
        'club_id',
        'points_deduction',
        'effective_from_journee',
        'reason',
    ];

    protected $casts = [
        'points_deduction' => 'integer',
        'effective_from_journee' => 'integer',
    ];

    public function season()
    {
        return $this->belongsTo(
            Season::class
        );
    }

    public function club()
    {
        return $this->belongsTo(
            Club::class
        );
    }
}
