<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'top14_clubs_count',
        'prod2_clubs_count',
        'journee_scoring_setup_hash',
        'preseason_setup_hash',
        'is_locked',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_season')
            ->withPivot('competition')
            ->withTimestamps();
    }

    public function players()
    {
        return $this->belongsToMany(User::class, 'season_user')
            ->withPivot([
                'display_order',
                'preseason_prediction_deadline',
            ])
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

    public function journees()
    {
        return $this->hasMany(Journee::class)
            ->orderBy('number');
    }

    public function matches()
    {
        return $this->hasManyThrough(MatchGame::class, Journee::class);
    }

    public function scoringRules()
    {
        return $this->hasMany(SeasonScoringRule::class);
    }

    public function scoringProfiles()
    {
        return $this->hasMany(SeasonScoringProfile::class)
            ->orderBy('position');
    }

    public function journeeTypeScoringProfiles()
    {
        return $this->hasMany(SeasonJourneeTypeScoringProfile::class);
    }

    public function preseasonQuestions()
    {
        return $this->hasMany(SeasonPreseasonQuestion::class)
            ->orderBy('position');
    }

    public function preseasonBonusRules()
    {
        return $this->hasMany(SeasonPreseasonBonusRule::class)
            ->orderBy('position');
    }

    public function preseasonCorrectionGroups()
    {
        return $this->hasMany(SeasonPreseasonCorrectionGroup::class)
            ->orderBy('position');
    }

    public function preseasonPredictions()
    {
        return $this->hasMany(SeasonPreseasonPrediction::class);
    }
}
