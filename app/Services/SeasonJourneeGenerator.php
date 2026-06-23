<?php

namespace App\Services;

use App\Models\Season;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeasonJourneeGenerator
{
    public function generate(Season $season): void
    {
        DB::transaction(function () use ($season) {
            if ($season->journees()->exists()) {
                return;
            }

            $this->createPreseasonJournee($season);
            $this->createRegularJournees($season);
            $this->createSpecialJournees($season);
        });
    }

    private function createPreseasonJournee(Season $season): void
    {
        $this->createJournee($season, [
            'number' => 0,
            'type' => 'preseason',
            'name' => 'Avant-saison',
            'slug' => 'avant-saison',
        ]);
    }

    private function createRegularJournees(Season $season): void
    {
        $regularJourneesCount = $this->regularJourneesCount($season);

        for ($number = 1; $number <= $regularJourneesCount; $number++) {
            $this->createJournee($season, [
                'number' => $number,
                'type' => 'regular',
                'name' => "Journée {$number}",
                'slug' => "journee-{$number}",
            ]);
        }
    }

    private function createSpecialJournees(Season $season): void
    {
        $regularJourneesCount = $this->regularJourneesCount($season);

        $specialJournees = [
            [
                'number' => $regularJourneesCount + 1,
                'type' => 'prod2_final',
                'name' => 'Finale PRO D2',
                'slug' => 'finale-pro-d2',
            ],
            [
                'number' => $regularJourneesCount + 2,
                'type' => 'top14_playoff',
                'name' => 'Barrages TOP 14',
                'slug' => 'barrages-top-14',
            ],
            [
                'number' => $regularJourneesCount + 3,
                'type' => 'access_match',
                'name' => 'Access match TOP 14 / PRO D2',
                'slug' => 'access-match-top-14-pro-d2',
            ],
            [
                'number' => $regularJourneesCount + 4,
                'type' => 'top14_semifinal',
                'name' => 'Demi-finales TOP 14',
                'slug' => 'demi-finales-top-14',
            ],
            [
                'number' => $regularJourneesCount + 5,
                'type' => 'top14_final',
                'name' => 'Finale TOP 14',
                'slug' => 'finale-top-14',
            ],
        ];

        foreach ($specialJournees as $journeeData) {
            $this->createJournee($season, $journeeData);
        }
    }

    private function regularJourneesCount(Season $season): int
    {
        return ((int) $season->top14_clubs_count - 1) * 2;
    }

    private function createJournee(Season $season, array $data): void
    {
        $season->journees()->create([
            'number' => $data['number'],
            'type' => $data['type'],
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'starts_at' => null,
            'prediction_deadline' => null,
        ]);
    }
}
