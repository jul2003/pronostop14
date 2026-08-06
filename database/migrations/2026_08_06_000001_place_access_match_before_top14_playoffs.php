<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->swapJourneesWhen(
            fn (
                int $accessNumber,
                int $playoffNumber
            ): bool => $accessNumber > $playoffNumber
        );
    }

    public function down(): void
    {
        $this->swapJourneesWhen(
            fn (
                int $accessNumber,
                int $playoffNumber
            ): bool => $accessNumber < $playoffNumber
        );
    }

    private function swapJourneesWhen(
        callable $shouldSwap
    ): void {
        DB::transaction(function () use ($shouldSwap) {
            $seasonIds = DB::table('journees')
                ->whereIn('type', [
                    'access_match',
                    'top14_playoff',
                ])
                ->distinct()
                ->pluck('season_id');

            foreach ($seasonIds as $seasonId) {
                $accessMatch = DB::table('journees')
                    ->where('season_id', $seasonId)
                    ->where('type', 'access_match')
                    ->first([
                        'id',
                        'number',
                    ]);

                $top14Playoffs = DB::table('journees')
                    ->where('season_id', $seasonId)
                    ->where('type', 'top14_playoff')
                    ->first([
                        'id',
                        'number',
                    ]);

                if (
                    ! $accessMatch
                    || ! $top14Playoffs
                    || $accessMatch->number === null
                    || $top14Playoffs->number === null
                ) {
                    continue;
                }

                $accessNumber = (int) $accessMatch->number;
                $playoffNumber = (int) $top14Playoffs->number;

                if (
                    ! $shouldSwap(
                        $accessNumber,
                        $playoffNumber
                    )
                ) {
                    continue;
                }

                $maximumNumber = (int) DB::table('journees')
                    ->where('season_id', $seasonId)
                    ->max('number');

                $temporaryNumber = max(
                    $maximumNumber,
                    $accessNumber,
                    $playoffNumber
                ) + 1000;

                DB::table('journees')
                    ->where('id', $accessMatch->id)
                    ->update([
                        'number' => $temporaryNumber,
                    ]);

                DB::table('journees')
                    ->where('id', $top14Playoffs->id)
                    ->update([
                        'number' => $accessNumber,
                    ]);

                DB::table('journees')
                    ->where('id', $accessMatch->id)
                    ->update([
                        'number' => $playoffNumber,
                    ]);
            }
        });
    }
};
