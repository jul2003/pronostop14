<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    //
    protected $fillable = [
        'name',
        'is_active',
        'slug',
        'top14_clubs_count',
        'prod2_clubs_count',
    ];

    public function journees()
    {
        return $this->hasMany(Journee::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function clubs()
    {
        return $this->belongsToMany(Club::class)
            ->withPivot('competition')
            ->withTimestamps();
    }

    public function scoringRules()
    {
        return $this->hasMany(SeasonScoringRule::class);
    }

    public function players()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }
}
