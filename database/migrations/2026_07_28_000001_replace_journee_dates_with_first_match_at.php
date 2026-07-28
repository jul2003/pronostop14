<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $defaultFirstMatchTime = '12:00';

    public function up(): void
    {
        $this->addApplicationSettings();
        $this->addFirstMatchAtColumn();

        /*
         * Important :
         * on recopie starts_at dans first_match_at AVANT de supprimer starts_at.
         * La date vient de starts_at.
         * L'heure vient du paramètre d'application default_first_match_time.
         */
        $this->copyStartsAtToFirstMatchAt();

        /*
         * Sécurité :
         * si une journée n'avait pas starts_at mais avait prediction_deadline,
         * on reconstruit une date de premier match avec prediction_deadline + 1 jour.
         */
        $this->fallbackFromPredictionDeadline();

        $this->dropOldJourneeDateColumns();
    }

    public function down(): void
    {
        $this->restoreOldJourneeDateColumns();
        $this->backfillOldJourneeDateColumns();

        if (Schema::hasColumn('journees', 'first_match_at')) {
            Schema::table('journees', function (Blueprint $table) {
                $table->dropColumn('first_match_at');
            });
        }

        DB::table('app_settings')
            ->where('key', 'default_first_match_time')
            ->delete();

        DB::table('app_settings')
            ->where('key', 'simulated_app_date')
            ->update([
                'type' => 'date',
                'updated_at' => now(),
            ]);
    }

    private function addApplicationSettings(): void
    {
        DB::table('app_settings')->updateOrInsert(
            [
                'key' => 'default_first_match_time',
            ],
            [
                'value' => $this->defaultFirstMatchTime,
                'type' => 'time',
                'label' => 'Heure par défaut du premier match',
                'description' => 'Heure appliquée automatiquement quand une date de premier match est choisie.',
                'position' => 45,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $simulatedDateSetting = DB::table('app_settings')
            ->where('key', 'simulated_app_date')
            ->first();

        if ($simulatedDateSetting) {
            $value = $simulatedDateSetting->value;

            if ($value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $value .= ' '.$this->defaultFirstMatchTime.':00';
            }

            DB::table('app_settings')
                ->where('key', 'simulated_app_date')
                ->update([
                    'value' => $value,
                    'type' => 'datetime',
                    'label' => $simulatedDateSetting->label ?: 'Date simulée',
                    'description' => $simulatedDateSetting->description ?: 'Date et heure utilisées pour simuler la date courante dans l’application.',
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('app_settings')->insert([
            'key' => 'simulated_app_date',
            'value' => null,
            'type' => 'datetime',
            'label' => 'Date simulée',
            'description' => 'Date et heure utilisées pour simuler la date courante dans l’application.',
            'position' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addFirstMatchAtColumn(): void
    {
        if (Schema::hasColumn('journees', 'first_match_at')) {
            return;
        }

        Schema::table('journees', function (Blueprint $table) {
            $table->dateTime('first_match_at')
                ->nullable()
                ->after('slug');
        });
    }

    private function copyStartsAtToFirstMatchAt(): void
    {
        if (! Schema::hasColumn('journees', 'starts_at')) {
            return;
        }

        DB::table('journees')
            ->whereNotNull('starts_at')
            ->orderBy('id')
            ->get(['id', 'starts_at'])
            ->each(function ($journee) {
                $startsAt = Carbon::parse($journee->starts_at);
                [$hours, $minutes] = $this->defaultFirstMatchTimeParts();

                DB::table('journees')
                    ->where('id', $journee->id)
                    ->update([
                        'first_match_at' => $startsAt
                            ->copy()
                            ->setTime($hours, $minutes, 0)
                            ->format('Y-m-d H:i:s'),
                        'updated_at' => now(),
                    ]);
            });
    }

    private function fallbackFromPredictionDeadline(): void
    {
        if (! Schema::hasColumn('journees', 'prediction_deadline')) {
            return;
        }

        DB::table('journees')
            ->whereNull('first_match_at')
            ->whereNotNull('prediction_deadline')
            ->orderBy('id')
            ->get(['id', 'type', 'prediction_deadline'])
            ->each(function ($journee) {
                $firstMatchDate = Carbon::parse($journee->prediction_deadline);

                if (($journee->type ?? null) !== 'preseason') {
                    $firstMatchDate = $firstMatchDate->copy()->addDay();
                }

                [$hours, $minutes] = $this->defaultFirstMatchTimeParts();

                DB::table('journees')
                    ->where('id', $journee->id)
                    ->update([
                        'first_match_at' => $firstMatchDate
                            ->copy()
                            ->setTime($hours, $minutes, 0)
                            ->format('Y-m-d H:i:s'),
                        'updated_at' => now(),
                    ]);
            });
    }

    private function dropOldJourneeDateColumns(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('journees', 'starts_at')) {
            $columnsToDrop[] = 'starts_at';
        }

        if (Schema::hasColumn('journees', 'prediction_deadline')) {
            $columnsToDrop[] = 'prediction_deadline';
        }

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('journees', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    private function restoreOldJourneeDateColumns(): void
    {
        if (! Schema::hasColumn('journees', 'starts_at')) {
            Schema::table('journees', function (Blueprint $table) {
                $table->dateTime('starts_at')
                    ->nullable()
                    ->after('slug');
            });
        }

        if (! Schema::hasColumn('journees', 'prediction_deadline')) {
            Schema::table('journees', function (Blueprint $table) {
                $table->dateTime('prediction_deadline')
                    ->nullable()
                    ->after('starts_at');
            });
        }
    }

    private function backfillOldJourneeDateColumns(): void
    {
        if (! Schema::hasColumn('journees', 'first_match_at')) {
            return;
        }

        DB::table('journees')
            ->whereNotNull('first_match_at')
            ->orderBy('id')
            ->get(['id', 'first_match_at'])
            ->each(function ($journee) {
                $firstMatchAt = Carbon::parse($journee->first_match_at);

                DB::table('journees')
                    ->where('id', $journee->id)
                    ->update([
                        'starts_at' => $firstMatchAt->copy()->startOfDay()->format('Y-m-d H:i:s'),
                        'prediction_deadline' => $firstMatchAt->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s'),
                        'updated_at' => now(),
                    ]);
            });
    }

    private function defaultFirstMatchTimeParts(): array
    {
        return array_map('intval', explode(':', $this->defaultFirstMatchTime));
    }
};
