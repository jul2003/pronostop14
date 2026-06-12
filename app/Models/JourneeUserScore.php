<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneeUserScore extends Model
{
    protected $fillable = [
        'journee_id',
        'user_id',
        'match_points',
        'perfect_round_bonus',
        'total_points',
        'rank',
    ];

    public function journee()
    {
        return $this->belongsTo(Journee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
