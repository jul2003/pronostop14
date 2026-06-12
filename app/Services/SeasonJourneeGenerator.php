<?php

namespace App\Services;

use App\Models\Journee;
use App\Models\Season;
use Illuminate\Support\Str;

class SeasonJourneeGenerator
{
    public function generate(Season $season): void
    {
        if ($season->journees()->exists()) {
            return;
        }

        $regularCount = ($season->top14_clubs_count - 1) * 2;

        $this->createJournee($season, 'preseason', null, 'Avant-saison');

        for ($i = 1; $i <= $regularCount; $i++) {
            $this->createJournee($season, 'regular', $i, 'Journée '.$i);
        }

        $this->createJournee($season, 'prod2_final', null, 'Finale PRO D2');
        $this->createJournee($season, 'access_match', null, 'Barrage TOP 14 / PRO D2');
        $this->createJournee($season, 'top14_playoff', null, 'Barrages TOP 14');
        $this->createJournee($season, 'top14_semifinal', null, 'Demi-finales TOP 14');
        $this->createJournee($season, 'top14_final', null, 'Finale TOP 14');
    }

    private function createJournee(Season $season, string $type, ?int $number, string $name): void
    {
        Journee::create([
            'season_id' => $season->id,
            'type' => $type,
            'number' => $number,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
