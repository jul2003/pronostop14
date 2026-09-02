<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionReminder extends Model
{
    protected $fillable = [
        'user_id',
        'season_id',
        'journee_id',
        'deadline_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function season()
    {
        return $this->belongsTo(
            Season::class
        );
    }

    public function journee()
    {
        return $this->belongsTo(
            Journee::class
        );
    }
}
