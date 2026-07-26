<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchPredictionDeadlineException extends Model
{
    protected $fillable = [
        'match_game_id',
        'prediction_deadline',
    ];

    protected function casts(): array
    {
        return [
            'prediction_deadline' => 'datetime',
        ];
    }

    public function match()
    {
        return $this->belongsTo(MatchGame::class, 'match_game_id');
    }
}
