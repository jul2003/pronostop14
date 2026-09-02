<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * La première tentative de cette migration a pu
         * ajouter certaines colonnes avant d'échouer.
         *
         * On vérifie donc chaque élément avant de le créer.
         */

        if (
            ! Schema::hasColumn(
                'users',
                'notify_new_prediction'
            )
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_new_prediction')
                    ->default(false);
            });
        }

        if (
            ! Schema::hasColumn(
                'users',
                'notify_results_available'
            )
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_results_available')
                    ->default(false);
            });
        }

        if (
            ! Schema::hasColumn(
                'users',
                'notify_prediction_reminder'
            )
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_prediction_reminder')
                    ->default(false);
            });
        }

        if (
            ! Schema::hasColumn(
                'users',
                'notification_features_seen_at'
            )
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp(
                    'notification_features_seen_at'
                )->nullable();
            });
        }

        /*
         * IMPORTANT :
         *
         * prediction_announcement_sent_at existe déjà
         * grâce à la migration précédente concernant
         * la notification "nouveau prono".
         *
         * Cette migration ne doit donc surtout pas
         * essayer de la recréer ni de la supprimer.
         */

        if (
            ! Schema::hasColumn(
                'journees',
                'result_announcement_sent_at'
            )
        ) {
            Schema::table('journees', function (Blueprint $table) {
                $table->timestamp(
                    'result_announcement_sent_at'
                )->nullable();
            });
        }

        if (
            ! Schema::hasTable(
                'prediction_reminders'
            )
        ) {
            Schema::create(
                'prediction_reminders',
                function (Blueprint $table) {
                    $table->id();

                    $table->foreignId('user_id')
                        ->constrained()
                        ->cascadeOnDelete();

                    $table->foreignId('season_id')
                        ->constrained()
                        ->cascadeOnDelete();

                    $table->foreignId('journee_id')
                        ->constrained()
                        ->cascadeOnDelete();

                    /*
                     * On conserve la deadline réellement
                     * utilisée au moment du rappel.
                     *
                     * C'est notamment utile pour
                     * l'avant-saison, où un joueur arrivé
                     * plus tard peut avoir une deadline
                     * personnelle.
                     */
                    $table->timestamp(
                        'deadline_at'
                    );

                    $table->timestamp(
                        'sent_at'
                    );

                    $table->timestamps();

                    /*
                     * Un seul rappel par joueur
                     * et par journée.
                     */
                    $table->unique([
                        'user_id',
                        'journee_id',
                    ]);
                }
            );
        }

        /*
         * Délai global du rappel.
         *
         * La page des paramètres applicatifs sait déjà
         * gérer les réglages de type integer.
         */
        DB::table('app_settings')->updateOrInsert(
            [
                'key' =>
                    'prediction_reminder_hours',
            ],
            [
                'value' =>
                    '24',

                'type' =>
                    'integer',

                'label' =>
                    'Rappel avant clôture d’un prono',

                'description' =>
                    'Nombre d’heures avant la date limite pour envoyer un rappel aux joueurs dont le pronostic est incomplet.',

                'position' =>
                    90,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]
        );

        /*
         * On marque comme déjà annoncées les journées
         * dont tous les résultats existaient AVANT
         * l'installation de cette fonctionnalité.
         *
         * Sans cela, la modification ultérieure d'un
         * ancien résultat pourrait envoyer un email
         * "Résultats disponibles".
         */
        $journees = DB::table('journees')
            ->join(
                'seasons',
                'seasons.id',
                '=',
                'journees.season_id'
            )
            ->where(
                'journees.type',
                '!=',
                'preseason'
            )
            ->whereNull(
                'journees.result_announcement_sent_at'
            )
            ->select([
                'journees.id',
                'journees.type',
                'seasons.top14_clubs_count',
            ])
            ->get();

        foreach ($journees as $journee) {
            $expectedMatches = match (
                $journee->type
            ) {
                'regular' =>
                    (int) (
                        $journee
                            ->top14_clubs_count
                        / 2
                    ),

                'prod2_final',
                'access_match',
                'top14_final' =>
                    1,

                'top14_playoff',
                'top14_semifinal' =>
                    2,

                default =>
                    null,
            };

            if (
                $expectedMatches === null
            ) {
                continue;
            }

            $matchesCount =
                DB::table('match_games')
                    ->where(
                        'journee_id',
                        $journee->id
                    )
                    ->count();

            $finishedMatchesCount =
                DB::table('match_games')
                    ->where(
                        'journee_id',
                        $journee->id
                    )
                    ->whereNotNull(
                        'actual_result'
                    )
                    ->count();

            if (
                $matchesCount
                    !== $expectedMatches
                || $finishedMatchesCount
                    !== $expectedMatches
            ) {
                continue;
            }

            DB::table('journees')
                ->where(
                    'id',
                    $journee->id
                )
                ->update([
                    'result_announcement_sent_at' =>
                        now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'prediction_reminders'
        );

        /*
         * prediction_announcement_sent_at n'est
         * volontairement PAS supprimée ici :
         * elle appartient à une migration antérieure.
         */
        if (
            Schema::hasColumn(
                'journees',
                'result_announcement_sent_at'
            )
        ) {
            Schema::table('journees', function (Blueprint $table) {
                $table->dropColumn(
                    'result_announcement_sent_at'
                );
            });
        }

        $userColumns = [
            'notify_new_prediction',
            'notify_results_available',
            'notify_prediction_reminder',
            'notification_features_seen_at',
        ];

        foreach ($userColumns as $column) {
            if (
                Schema::hasColumn(
                    'users',
                    $column
                )
            ) {
                Schema::table(
                    'users',
                    function (
                        Blueprint $table
                    ) use (
                        $column
                    ) {
                        $table->dropColumn(
                            $column
                        );
                    }
                );
            }
        }

        DB::table('app_settings')
            ->where(
                'key',
                'prediction_reminder_hours'
            )
            ->delete();
    }
};
