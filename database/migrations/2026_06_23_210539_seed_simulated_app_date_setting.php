<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AppSetting::updateOrCreate(
            [
                'key' => 'simulated_app_date',
            ],
            [
                'value' => null,
                'type' => 'date',
                'label' => 'Date simulée de l’application',
                'description' => 'Permet à un administrateur de tester l’application comme si la date du jour était différente. Laisser vide pour utiliser la vraie date.',
                'position' => 20,
            ]
        );
    }

    public function down(): void
    {
        AppSetting::where('key', 'simulated_app_date')->delete();
    }
};
