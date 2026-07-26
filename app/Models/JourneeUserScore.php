<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneeUserScore extends Model
{
    protected $fillable = [
        'journee_id',
        'user_id',
        'match_points',
        'perfect_journee_bonus',
        'total_points',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'match_points' => 'integer',
            'perfect_journee_bonus' => 'integer',
            'total_points' => 'integer',
            'rank' => 'integer',
        ];
    }

    public function journee()
    {
        return $this->belongsTo(Journee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
