<?php

namespace App\Models;

use App\Services\AppDateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Journee extends Model
{
    protected $fillable = [
        'season_id',
        'type',
        'number',
        'name',
        'slug',
        'first_match_at',
        'predictions_enabled',
    ];

    protected function casts(): array
    {
        return [
            'first_match_at' => 'datetime',
            'predictions_enabled' => 'boolean',
        ];
    }

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
        if (! $this->first_match_at) {
            return false;
        }

        return $this->first_match_at->lte(
            app(AppDateService::class)->now()
        );
    }

    public function isPredictionOpen(): bool
    {
        if ($this->predictions_enabled === false) {
            return false;
        }

        if (! $this->first_match_at) {
            return false;
        }

        return app(AppDateService::class)
            ->now()
            ->lt($this->first_match_at);
    }

    public function isPredictionLocked(): bool
    {
        return ! $this->isPredictionOpen();
    }

    public function isPreparationLocked(): bool
    {
        return $this->isLocked();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $journee = $this->routeBindingQueryForCurrentSeason()
            ->where($field, $value)
            ->first();

        if ($journee || $field !== 'slug') {
            return $journee;
        }

        $currentSlug = $this->currentSlugForLegacySlug(
            (string) $value
        );

        if (! $currentSlug) {
            return null;
        }

        return $this->routeBindingQueryForCurrentSeason()
            ->where('slug', $currentSlug)
            ->first();
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
            'regular' => (int) (
                $this->season->top14_clubs_count / 2
            ),
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
        return in_array(
            $result,
            $this->allowedResultOptions(),
            true
        );
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
        $labels = [
            'v' => 'v',
            'n' => 'n',
            'd' => 'd',
        ];

        return collect($this->allowedResultOptions())
            ->mapWithKeys(
                fn (string $result) => [
                    $result => $labels[$result] ?? $result,
                ]
            )
            ->all();
    }

    public function bonusOptionShortLabels(): array
    {
        return [
            'o' => 'o',
            '-' => '-',
            'd' => 'd',
        ];
    }

    public function resultOptionLabel(string $result): string
    {
        return $this->resultOptionLabels()[$result] ?? $result;
    }

    public function resultOptionShortLabel(string $result): string
    {
        return $this->resultOptionShortLabels()[$result] ?? $result;
    }

    private function routeBindingQueryForCurrentSeason(): Builder
    {
        $query = $this->newQuery();
        $season = request()->route('season');

        /*
         * Les routes raccourcies comme /admin/saisons/journees
         * ne contiennent aucun paramètre {season}. Elles ne résolvent
         * toutefois aucune journée directement, donc la recherche
         * globale reste disponible dans ce cas.
         */
        if (! $season) {
            return $query;
        }

        /*
         * Laravel résout normalement Season avant Journee puisque
         * {season} apparaît avant {journee}. Cette partie couvre
         * également le cas où le paramètre contient encore le slug.
         */
        if (! $season instanceof Season) {
            $season = (new Season())->resolveRouteBinding($season);
        }

        if ($season instanceof Season) {
            $query->where(
                'season_id',
                $season->getKey()
            );
        }

        return $query;
    }

    private function currentSlugForLegacySlug(string $slug): ?string
    {
        if (preg_match('/^journee-(\d+)$/i', $slug, $matches)) {
            return 'J'.((int) $matches[1]);
        }

        if (preg_match('/^j(\d+)$/i', $slug, $matches)) {
            return 'J'.((int) $matches[1]);
        }

        if ($slug === 'access-match-top-14-pro-d2') {
            return 'access-match';
        }

        return null;
    }
}
