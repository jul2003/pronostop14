<?php

namespace App\Models;

use App\Services\AppDateService;
use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    protected $fillable = [
        'journee_id',
        'position',
        'home_club_id',
        'away_club_id',
        'actual_result',
        'actual_tries',
        'actual_home_bonus',
        'actual_away_bonus',
        'is_finished',
    ];

    protected function casts(): array
    {
        return [
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
        return $this->hasOne(MatchPredictionDeadlineException::class, 'match_game_id');
    }

    public function effectivePredictionDeadline()
    {
        if ($this->predictionDeadlineException?->prediction_deadline) {
            return $this->predictionDeadlineException->prediction_deadline;
        }

        return $this->journee?->first_match_at;
    }

    public function hasPredictionDeadlineException(): bool
    {
        return $this->predictionDeadlineException?->prediction_deadline !== null;
    }

    public function isPredictionLocked(): bool
    {
        if (! $this->journee) {
            return true;
        }

        if ($this->journee->predictions_enabled === false) {
            return true;
        }

        $deadline = $this->effectivePredictionDeadline();

        if (! $deadline) {
            return true;
        }

        return app(AppDateService::class)->now()->greaterThanOrEqualTo($deadline);
    }
}
