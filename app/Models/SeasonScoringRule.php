<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonScoringRule extends Model
{
    //
    protected $fillable = [
        'season_id',
        'code',
        'label',
        'points',
        'position',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
