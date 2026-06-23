<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\AppDateService;

class Journee extends Model
{
    protected $fillable = [
        'season_id',
        'type',
        'number',
        'name',
        'slug',
        'starts_at',
        'prediction_deadline',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'prediction_deadline' => 'datetime',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function matches()
    {
        return $this->hasMany(MatchGame::class);
    }

    public function userScores()
    {
        return $this->hasMany(JourneeUserScore::class);
    }

    public function isLocked(): bool
    {
        if (! $this->prediction_deadline) {
            return false;
        }

        return $this->prediction_deadline->lte(app(AppDateService::class)->now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'preseason' => 'Avant-saison',
            'regular' => 'Journée régulière',
            'prod2_final' => 'Finale PRO D2',
            'access_match' => 'Access match',
            'top14_playoff' => 'Barrages TOP 14',
            'top14_semifinal' => 'Demi-finales TOP 14',
            'top14_final' => 'Finale TOP 14',
            default => $this->type,
        };
    }

    public function expectedMatchesCount(): ?int
    {
        return match ($this->type) {
            'regular' => (int) ($this->season->top14_clubs_count / 2),

            'prod2_final' => 1,
            'access_match' => 1,
            'top14_playoff' => 2,
            'top14_semifinal' => 2,
            'top14_final' => 1,

            'preseason' => null,

            default => null,
        };
    }

    public function hasExpectedMatchesCount(): bool
    {
        $expected = $this->expectedMatchesCount();

        if ($expected === null) {
            return true;
        }

        return $this->matches_count === $expected;
    }

    public function allowedResultOptions(): array
    {
        return match ($this->type) {
            'regular' => ['v', 'n', 'd'],

            'access_match',
            'top14_playoff',
            'prod2_final',
            'top14_semifinal',
            'top14_final' => ['v', 'd'],

            default => ['v', 'n', 'd'],
        };
    }

    public function allowsResult(string $result): bool
    {
        return in_array($result, $this->allowedResultOptions(), true);
    }

    public function resultOptionLabels(): array
    {
        return match ($this->type) {
            'prod2_final',
            'top14_semifinal',
            'top14_final' => [
                'v' => 'Équipe 1',
                'd' => 'Équipe 2',
            ],

            default => [
                'v' => 'Domicile',
                'n' => 'Nul',
                'd' => 'Extérieur',
            ],
        };
    }

    public function resultOptionShortLabels(): array
    {
        return match ($this->type) {
            'prod2_final',
            'top14_semifinal',
            'top14_final' => [
                'v' => 'Éq. 1',
                'd' => 'Éq. 2',
            ],

            default => [
                'v' => 'V',
                'n' => 'N',
                'd' => 'D',
            ],
        };
    }

    public function resultOptionLabel(string $result): string
    {
        return $this->resultOptionLabels()[$result] ?? $result;
    }

    public function resultOptionShortLabel(string $result): string
    {
        return $this->resultOptionShortLabels()[$result] ?? $result;
    }
}
