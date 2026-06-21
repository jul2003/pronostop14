<?php

namespace Database\Seeders;

use App\Models\Club;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class Clubs20252026Seeder extends Seeder
{
    public function run(): void
    {
        $clubs = [

            // TOP 14 2025-2026

            ['name' => 'Bayonne', 'short_name' => 'bayonne', 'slug' => 'bayonne'],
            ['name' => 'Clermont', 'short_name' => 'clermont', 'slug' => 'clermont'],
            ['name' => 'Castres', 'short_name' => 'castres', 'slug' => 'castres'],
            ['name' => 'Lyon', 'short_name' => 'lyon', 'slug' => 'lyon'],
            ['name' => 'Montpellier', 'short_name' => 'montpellier', 'slug' => 'montpellier'],
            ['name' => 'Toulon', 'short_name' => 'toulon', 'slug' => 'toulon'],
            ['name' => 'Racing', 'short_name' => 'racing', 'slug' => 'racing'],
            ['name' => 'Pau', 'short_name' => 'pau', 'slug' => 'pau'],
            ['name' => 'Stade Français', 'short_name' => 'paris', 'slug' => 'stade-francais'],
            ['name' => 'La Rochelle', 'short_name' => 'la-rochelle', 'slug' => 'la-rochelle'],
            ['name' => 'Toulouse', 'short_name' => 'toulouse', 'slug' => 'toulouse'],
            ['name' => 'Montauban', 'short_name' => 'montauban', 'slug' => 'montauban'],
            ['name' => 'Perpignan', 'short_name' => 'perpignan', 'slug' => 'perpignan'],
            ['name' => 'Bordeaux-Bègles', 'short_name' => 'bordeaux-begles', 'slug' => 'bordeaux-begles'],

            // PRO D2 2025-2026

            ['name' => 'Béziers', 'short_name' => 'beziers', 'slug' => 'beziers'],
            ['name' => 'Biarritz', 'short_name' => 'biarritz', 'slug' => 'biarritz'],
            ['name' => 'Brive', 'short_name' => 'brive', 'slug' => 'brive'],
            ['name' => 'Colomiers', 'short_name' => 'colomiers', 'slug' => 'colomiers'],
            ['name' => 'Grenoble', 'short_name' => 'grenoble', 'slug' => 'grenoble'],
            ['name' => 'Oyonnax', 'short_name' => 'oyonnax', 'slug' => 'oyonnax'],
            ['name' => 'Provence', 'short_name' => 'provence', 'slug' => 'provence'],
            ['name' => 'Vannes', 'short_name' => 'vannes', 'slug' => 'vannes'],
            ['name' => 'Agen', 'short_name' => 'agen', 'slug' => 'agen'],
            ['name' => 'Soyaux-Angoulême', 'short_name' => 'angouleme', 'slug' => 'soyaux-angouleme'],
            ['name' => 'Aurillac', 'short_name' => 'aurillac', 'slug' => 'aurillac'],
            ['name' => 'Mont-de-Marsan', 'short_name' => 'mont-de-marsan', 'slug' => 'mont-de-marsan'],
            ['name' => 'Carcassonne', 'short_name' => 'carcassonne', 'slug' => 'carcassonne'],
            ['name' => 'Dax', 'short_name' => 'dax', 'slug' => 'dax'],
            ['name' => 'Nevers', 'short_name' => 'nevers', 'slug' => 'nevers'],
            ['name' => 'Valence-Romans', 'short_name' => 'valence-romans', 'slug' => 'valence-romans'],

            // Clubs qui monteront en Pro D2 a la fin de la saison

            ['name' => 'Nice', 'short_name' => 'nice', 'slug' => 'nice'],
            ['name' => 'Narbonne', 'short_name' => 'narbonne', 'slug' => 'narbonne'],

        ];

        foreach ($clubs as $club) {
            Club::updateOrCreate(
                ['slug' => $club['slug']],
                $club
            );
        }
    }
}
