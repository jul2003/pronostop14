<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Club;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $Clubs = [
            'Stade Toulousain',
            'Union Bordeaux-Bègles',
            'Toulon',
            'La Rochelle',
            'Castres',
            'Clermont',
            'Bayonne',
            'Pau',
            'Montpellier',
            'Racing 92',
            'Lyon',
            'Stade Français',
            'Perpignan',
            'Vannes',
        ];

        foreach ($Clubs as $Club) {
            Club::create([
                'name' => $Club,
                'slug' => \Illuminate\Support\Str::slug($Club),
            ]);
        }
    }
}
