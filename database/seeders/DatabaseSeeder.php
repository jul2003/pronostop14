<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            Clubs20252026Seeder::class,
            //JourneeScoringProfilesSeeder::class,
            //PreseasonScoringProfilesSeeder::class,
            //PreseasonPredictionTemplatesSeeder::class,
            //PreseasonBonusRuleTemplatesSeeder::class,
            //AppSettingsSeeder::class,
        ]);
    }
}
