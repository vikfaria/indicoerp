<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('accounting:process-recurring-journals')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->command('sce:sync-fiscal-calendar --years=2')
            ->dailyAt('02:20')
            ->withoutOverlapping();

        $schedule->command('sce:sync-fiscal-compliance-alerts --continue-on-error')
            ->dailyAt('03:10')
            ->withoutOverlapping();

        $schedule->command('hrm:sync-compliance-alerts --continue-on-error')
            ->hourlyAt(10)
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
