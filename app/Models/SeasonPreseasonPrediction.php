<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonPreseasonPrediction extends Model
{
    protected $fillable = [
        'season_id',
        'user_id',
        'question_id',
        'answer_type',
        'club_id',
        'text_answer',
        'is_correct',
        'points',
        'submitted_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(SeasonPreseasonQuestion::class, 'question_id');
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
