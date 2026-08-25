<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Season;
use App\Models\SeasonClubPenalty;
use App\Services\Top14StandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Top14StandingPenaltyTest extends TestCase
{
    use RefreshDatabase;

    public function test_penalty_is_deducted_from_official_points(): void
    {
        $season = Season::create([
            'name' => 'Saison test',
            'slug' => 'saison-test',
            'is_active' => true,
            'top14_clubs_count' => 2,
            'prod2_clubs_count' => 0,
            'is_locked' => false,
        ]);

        $clubA = Club::create([
            'name' => 'Club A',
            'short_name' => 'A',
            'slug' => 'club-a',
        ]);

        $clubB = Club::create([
            'name' => 'Club B',
            'short_name' => 'B',
            'slug' => 'club-b',
        ]);

        $season->clubs()->attach([
            $clubA->id => [
                'competition' => 'top14',
            ],

            $clubB->id => [
                'competition' => 'top14',
            ],
        ]);

        SeasonClubPenalty::create([
            'season_id' => $season->id,
            'club_id' => $clubA->id,
            'points_deduction' => 4,
            'effective_from_journee' => 1,
            'reason' => 'Test',
        ]);

        $standings = app(
            Top14StandingService::class
        )->standingsThroughJournee(
            $season,
            1
        );

        $clubARow = $standings->first(
            fn (array $row) =>
                (int) $row['club']->id
                === (int) $clubA->id
        );

        $clubBRow = $standings->first(
            fn (array $row) =>
                (int) $row['club']->id
                === (int) $clubB->id
        );

        $this->assertNotNull($clubARow);
        $this->assertNotNull($clubBRow);

        $this->assertSame(
            0,
            $clubARow['sporting_points']
        );

        $this->assertSame(
            4,
            $clubARow['penalty_points']
        );

        $this->assertSame(
            -4,
            $clubARow['points']
        );

        $this->assertSame(
            1,
            $clubARow['max_points']
        );

        $this->assertSame(
            0,
            $clubBRow['penalty_points']
        );

        $this->assertSame(
            0,
            $clubBRow['points']
        );

        $this->assertSame(
            5,
            $clubBRow['max_points']
        );
    }

    public function test_penalty_only_applies_from_its_effective_journee(): void
    {
        $season = Season::create([
            'name' => 'Saison test 2',
            'slug' => 'saison-test-2',
            'is_active' => false,
            'top14_clubs_count' => 2,
            'prod2_clubs_count' => 0,
            'is_locked' => false,
        ]);

        $club = Club::create([
            'name' => 'Club pénalisé',
            'short_name' => 'PEN',
            'slug' => 'club-penalise',
        ]);

        $otherClub = Club::create([
            'name' => 'Autre club',
            'short_name' => 'AUT',
            'slug' => 'autre-club',
        ]);

        $season->clubs()->attach([
            $club->id => [
                'competition' => 'top14',
            ],

            $otherClub->id => [
                'competition' => 'top14',
            ],
        ]);

        SeasonClubPenalty::create([
            'season_id' => $season->id,
            'club_id' => $club->id,
            'points_deduction' => 3,
            'effective_from_journee' => 10,
            'reason' => 'Sanction J10',
        ]);

        $service = app(
            Top14StandingService::class
        );

        $before = $service
            ->penaltiesByClubId(
                $season,
                9
            );

        $fromJournee10 = $service
            ->penaltiesByClubId(
                $season,
                10
            );

        $this->assertSame(
            0,
            (int) $before->get(
                $club->id,
                0
            )
        );

        $this->assertSame(
            3,
            (int) $fromJournee10->get(
                $club->id,
                0
            )
        );
    }

    public function test_multiple_penalties_are_accumulated(): void
    {
        $season = Season::create([
            'name' => 'Saison test 3',
            'slug' => 'saison-test-3',
            'is_active' => false,
            'top14_clubs_count' => 2,
            'prod2_clubs_count' => 0,
            'is_locked' => false,
        ]);

        $club = Club::create([
            'name' => 'Club sanctions multiples',
            'short_name' => 'CSM',
            'slug' => 'club-sanctions-multiples',
        ]);

        $otherClub = Club::create([
            'name' => 'Club témoin',
            'short_name' => 'TEM',
            'slug' => 'club-temoin',
        ]);

        $season->clubs()->attach([
            $club->id => [
                'competition' => 'top14',
            ],

            $otherClub->id => [
                'competition' => 'top14',
            ],
        ]);

        SeasonClubPenalty::create([
            'season_id' => $season->id,
            'club_id' => $club->id,
            'points_deduction' => 2,
            'effective_from_journee' => 5,
        ]);

        SeasonClubPenalty::create([
            'season_id' => $season->id,
            'club_id' => $club->id,
            'points_deduction' => 3,
            'effective_from_journee' => 18,
        ]);

        $service = app(
            Top14StandingService::class
        );

        $atJournee10 =
            $service->penaltiesByClubId(
                $season,
                10
            );

        $atJournee20 =
            $service->penaltiesByClubId(
                $season,
                20
            );

        $this->assertSame(
            2,
            (int) $atJournee10->get(
                $club->id
            )
        );

        $this->assertSame(
            5,
            (int) $atJournee20->get(
                $club->id
            )
        );
    }
}
