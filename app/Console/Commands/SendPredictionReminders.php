<?php

namespace App\Console\Commands;

use App\Services\PredictionReminderService;
use Illuminate\Console\Command;

class SendPredictionReminders extends Command
{
    protected $signature =
        'pronos:send-reminders';

    protected $description =
        'Envoie les rappels de pronostics incomplets avant leur date limite.';

    public function handle(
        PredictionReminderService $service
    ): int {
        $sentCount =
            $service
                ->sendDueReminders();

        $this->info(
            $sentCount
            .' rappel(s) envoyé(s).'
        );

        return self::SUCCESS;
    }
}
