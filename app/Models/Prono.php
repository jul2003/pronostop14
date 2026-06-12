<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prono extends Model
{
    //
    protected $fillable = [
        'user_id',
        'match_game_id',
        'predicted_result',
        'predicted_tries',
        'predicted_home_bonus',
        'predicted_away_bonus',
        'points',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matchGame()
    {
        return $this->belongsTo(MatchGame::class);
    }
}
