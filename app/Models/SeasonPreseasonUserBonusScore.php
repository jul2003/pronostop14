<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonPreseasonUserBonusScore extends Model
{
    protected $fillable = [
        'season_id',
        'user_id',
        'season_preseason_bonus_rule_id',
        'is_awarded',
        'points',
    ];

    protected $casts = [
        'is_awarded' => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bonusRule()
    {
        return $this->belongsTo(SeasonPreseasonBonusRule::class, 'season_preseason_bonus_rule_id');
    }
}
