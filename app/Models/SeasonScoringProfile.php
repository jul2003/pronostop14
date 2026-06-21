<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonScoringProfile extends Model
{
    protected $fillable = [
        'season_id',
        'source_profile_id',
        'stop_on_wrong_result',
        'code',
        'name',
        'description',
        'position',
    ];

    protected $casts = [
        'stop_on_wrong_result' => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function sourceProfile()
    {
        return $this->belongsTo(ScoringProfile::class, 'source_profile_id');
    }

    public function rules()
    {
        return $this->hasMany(SeasonScoringRule::class)
            ->orderBy('position');
    }
}
