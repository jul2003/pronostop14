<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    //
    protected $fillable = [
        'journee_id',
        'position',
        'home_club_id',
        'away_club_id',
        'actual_result',
        'actual_tries',
        'actual_home_bonus',
        'actual_away_bonus',
        'is_finished',
    ];

    public function journee()
    {
        return $this->belongsTo(Journee::class);
    }

    public function homeClub()
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }


    public function awayClub()
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }

    public function pronos()
    {
        return $this->hasMany(Prono::class);
    }
}
