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
         * Catalogue des fonctionnalités / nouveautés.
         */
        if (! Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();

                $table
                    ->string('key')
                    ->unique();

                $table->string('title');

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->string('icon', 20)
                    ->nullable();

                /*
                 * Liste de sous-éléments affichés
                 * dans la modale.
                 */
                $table
                    ->json('details')
                    ->nullable();

                /*
                 * Activation propre à la feature.
                 */
                $table
                    ->boolean('is_active')
                    ->default(true);

                /*
                 * Optionnel :
                 * permet de conditionner automatiquement
                 * l'annonce à un AppSetting booléen.
                 *
                 * Exemple :
                 * prediction_recap_early_visibility_enabled
                 */
                $table
                    ->string('activation_setting_key')
                    ->nullable();

                /*
                 * Action facultative dans la modale.
                 */
                $table
                    ->string('action_label')
                    ->nullable();

                $table
                    ->string('action_url', 2048)
                    ->nullable();

                $table
                    ->unsignedInteger('position')
                    ->default(0);

                /*
                 * Permet éventuellement de programmer
                 * l'apparition / disparition d'une annonce.
                 */
                $table
                    ->timestamp('starts_at')
                    ->nullable();

                $table
                    ->timestamp('ends_at')
                    ->nullable();

                $table->timestamps();
            });
        }

        /*
         * Liaison entre utilisateur et feature vue.
         */
        if (! Schema::hasTable('feature_user')) {
            Schema::create('feature_user', function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('feature_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->timestamp('seen_at');

                $table->timestamps();

                $table->unique([
                    'feature_id',
                    'user_id',
                ]);
            });
        }

        $now = now();

        /*
         * Feature 1 :
         * notifications email.
         */
        DB::table('features')->updateOrInsert(
            [
                'key' => 'email_notifications',
            ],
            [
                'title' =>
                    'Notifications par email',

                'description' =>
                    'PronosTOP14 peut maintenant t’envoyer des notifications par email.',

                'icon' =>
                    '📧',

                'details' =>
                    json_encode(
                        [
                            [
                                'title' =>
                                    '🏉 Nouveau prono disponible',

                                'text' =>
                                    'Un email lorsqu’une nouvelle journée est prête à être pronostiquée.',
                            ],
                            [
                                'title' =>
                                    '🏆 Résultats disponibles',

                                'text' =>
                                    'Un email lorsque tous les résultats d’une journée sont enregistrés.',
                            ],
                            [
                                'title' =>
                                    '⏰ Rappel avant clôture',

                                'text' =>
                                    'Un rappel lorsqu’un pronostic est encore incomplet à l’approche de sa date limite.',
                            ],
                            [
                                'title' =>
                                    'ℹ️ À savoir',

                                'text' =>
                                    'Aucune notification n’est activée automatiquement. Tu peux choisir celles que tu souhaites recevoir depuis ton profil.',
                            ],
                        ],
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    ),

                'is_active' =>
                    true,

                'activation_setting_key' =>
                    null,

                'action_label' =>
                    'Configurer mes notifications',

                'action_url' =>
                    '/mon-profil#notifications-email',

                'position' =>
                    10,

                'starts_at' =>
                    null,

                'ends_at' =>
                    null,

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ]
        );

        /*
         * Feature 2 :
         * affichage anticipé.
         *
         * Elle dépend automatiquement du paramètre
         * application correspondant.
         */
        DB::table('features')->updateOrInsert(
            [
                'key' =>
                    'prediction_recap_early_visibility',
            ],
            [
                'title' =>
                    'Affichage anticipé des pronostics',

                'description' =>
                    'Le récapitulatif des pronostics d’une journée peut désormais être rendu visible avant la clôture de la saisie.',

                'icon' =>
                    '👀',

                'details' =>
                    json_encode(
                        [
                            [
                                'title' =>
                                    '📋 Voir les pronostics avant le premier match',

                                'text' =>
                                    'Pour certaines journées, les pronostics de tous les joueurs pourront être consultés sur la page Résultats avant la fermeture de la saisie.',
                            ],
                            [
                                'title' =>
                                    '✏️ La saisie peut rester ouverte',

                                'text' =>
                                    'L’affichage du récapitulatif ne signifie pas forcément que la saisie est clôturée. Ton pronostic reste modifiable tant que sa date limite n’est pas atteinte.',
                            ],
                            [
                                'title' =>
                                    'ℹ️ À savoir',

                                'text' =>
                                    'À partir de la date d’affichage définie pour la journée, les autres joueurs pourront également voir le pronostic que tu as saisi à cet instant.',
                            ],
                        ],
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    ),

                'is_active' =>
                    true,

                'activation_setting_key' =>
                    'prediction_recap_early_visibility_enabled',

                'action_label' =>
                    null,

                'action_url' =>
                    null,

                'position' =>
                    20,

                'starts_at' =>
                    null,

                'ends_at' =>
                    null,

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ]
        );

        /*
         * Migration de l'ancien système :
         * notifications déjà vues.
         */
        $emailFeatureId =
            DB::table('features')
                ->where(
                    'key',
                    'email_notifications'
                )
                ->value('id');

        if (
            $emailFeatureId
            && Schema::hasColumn(
                'users',
                'notification_features_seen_at'
            )
        ) {
            DB::table('users')
                ->select([
                    'id',
                    'notification_features_seen_at',
                ])
                ->whereNotNull(
                    'notification_features_seen_at'
                )
                ->orderBy('id')
                ->chunkById(
                    500,
                    function ($users) use (
                        $emailFeatureId
                    ) {
                        $rows = [];

                        foreach ($users as $user) {
                            $rows[] = [
                                'feature_id' =>
                                    $emailFeatureId,

                                'user_id' =>
                                    $user->id,

                                'seen_at' =>
                                    $user
                                        ->notification_features_seen_at,

                                'created_at' =>
                                    $user
                                        ->notification_features_seen_at,

                                'updated_at' =>
                                    $user
                                        ->notification_features_seen_at,
                            ];
                        }

                        if ($rows !== []) {
                            DB::table(
                                'feature_user'
                            )->insertOrIgnore(
                                $rows
                            );
                        }
                    }
                );
        }

        /*
         * Si tu avais déjà créé la colonne
         * de la seconde feature avant ce refactor,
         * on la migre également automatiquement.
         */
        $predictionRecapFeatureId =
            DB::table('features')
                ->where(
                    'key',
                    'prediction_recap_early_visibility'
                )
                ->value('id');

        if (
            $predictionRecapFeatureId
            && Schema::hasColumn(
                'users',
                'prediction_recap_feature_seen_at'
            )
        ) {
            DB::table('users')
                ->select([
                    'id',
                    'prediction_recap_feature_seen_at',
                ])
                ->whereNotNull(
                    'prediction_recap_feature_seen_at'
                )
                ->orderBy('id')
                ->chunkById(
                    500,
                    function ($users) use (
                        $predictionRecapFeatureId
                    ) {
                        $rows = [];

                        foreach ($users as $user) {
                            $rows[] = [
                                'feature_id' =>
                                    $predictionRecapFeatureId,

                                'user_id' =>
                                    $user->id,

                                'seen_at' =>
                                    $user
                                        ->prediction_recap_feature_seen_at,

                                'created_at' =>
                                    $user
                                        ->prediction_recap_feature_seen_at,

                                'updated_at' =>
                                    $user
                                        ->prediction_recap_feature_seen_at,
                            ];
                        }

                        if ($rows !== []) {
                            DB::table(
                                'feature_user'
                            )->insertOrIgnore(
                                $rows
                            );
                        }
                    }
                );
        }

        /*
         * L'historique est maintenant dans feature_user.
         * On peut supprimer les anciennes colonnes.
         */
        if (
            Schema::hasColumn(
                'users',
                'notification_features_seen_at'
            )
        ) {
            Schema::table(
                'users',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'notification_features_seen_at'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'users',
                'prediction_recap_feature_seen_at'
            )
        ) {
            Schema::table(
                'users',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'prediction_recap_feature_seen_at'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        /*
         * On restaure au minimum l'ancienne colonne
         * des notifications avant de supprimer
         * le nouveau système.
         */
        if (
            ! Schema::hasColumn(
                'users',
                'notification_features_seen_at'
            )
        ) {
            Schema::table(
                'users',
                function (Blueprint $table) {
                    $table
                        ->timestamp(
                            'notification_features_seen_at'
                        )
                        ->nullable();
                }
            );
        }

        if (
            Schema::hasTable('features')
            && Schema::hasTable('feature_user')
        ) {
            $featureId =
                DB::table('features')
                    ->where(
                        'key',
                        'email_notifications'
                    )
                    ->value('id');

            if ($featureId) {
                $seenRows =
                    DB::table('feature_user')
                        ->where(
                            'feature_id',
                            $featureId
                        )
                        ->get();

                foreach ($seenRows as $row) {
                    DB::table('users')
                        ->where(
                            'id',
                            $row->user_id
                        )
                        ->update([
                            'notification_features_seen_at' =>
                                $row->seen_at,
                        ]);
                }
            }
        }

        Schema::dropIfExists(
            'feature_user'
        );

        Schema::dropIfExists(
            'features'
        );
    }
};
