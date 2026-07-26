<?php

namespace App\Models;

use App\Services\AppDateService;
use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'actual_tries' => 'integer',
            'is_finished' => 'boolean',
        ];
    }

    public function journee()
    {
        return $this->belongsTo(Journee::class);
    }

    public function homeClub()
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }

    public function awayClub()
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }

    public function pronos()
    {
        return $this->hasMany(Prono::class);
    }

    public function predictionDeadlineException()
    {
        return $this->hasOne(MatchPredictionDeadlineException::class);
    }

    public function effectivePredictionDeadline()
    {
        return $this->predictionDeadlineException?->prediction_deadline
            ?? $this->journee?->prediction_deadline;
    }

    public function hasPredictionDeadlineException(): bool
    {
        if ($this->relationLoaded('predictionDeadlineException')) {
            return $this->predictionDeadlineException !== null;
        }

        return $this->predictionDeadlineException()->exists();
    }

    public function isPredictionLocked(): bool
    {
        $deadline = $this->effectivePredictionDeadline();

        if (! $deadline) {
            return false;
        }

        return $deadline->lte(app(AppDateService::class)->now());
    }
}
