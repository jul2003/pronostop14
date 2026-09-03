<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'feature_user',
                'snapshot'
            )
        ) {
            Schema::table(
                'feature_user',
                function (Blueprint $table) {
                    $table
                        ->json('snapshot')
                        ->nullable()
                        ->after('seen_at');
                }
            );
        }

        /*
         * Les features déjà vues avant cette migration
         * n'ont pas encore de snapshot.
         *
         * On initialise leur historique avec
         * le contenu actuel de la feature.
         */
        $rows = DB::table('feature_user')
            ->join(
                'features',
                'features.id',
                '=',
                'feature_user.feature_id'
            )
            ->whereNull(
                'feature_user.snapshot'
            )
            ->select([
                'feature_user.id as pivot_id',
                'features.id',
                'features.key',
                'features.title',
                'features.description',
                'features.icon',
                'features.details',
                'features.action_label',
                'features.action_url',
            ])
            ->get();

        foreach ($rows as $row) {
            $details = null;

            if ($row->details) {
                $decoded =
                    json_decode(
                        $row->details,
                        true
                    );

                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }

            $snapshot = [
                'id' =>
                    $row->id,

                'key' =>
                    $row->key,

                'title' =>
                    $row->title,

                'description' =>
                    $row->description,

                'icon' =>
                    $row->icon,

                'details' =>
                    $details ?? [],

                'action_label' =>
                    $row->action_label,

                'action_url' =>
                    $row->action_url,
            ];

            DB::table('feature_user')
                ->where(
                    'id',
                    $row->pivot_id
                )
                ->update([
                    'snapshot' =>
                        json_encode(
                            $snapshot,
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ),
                ]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'feature_user',
                'snapshot'
            )
        ) {
            Schema::table(
                'feature_user',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'snapshot'
                    );
                }
            );
        }
    }
};
