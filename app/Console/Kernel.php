<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Auto-sync every 5 minutes on school/office installs. Harmless
        // no-op on the central server (sync:push/pull both bail out
        // immediately when SYNC_ROLE != school). This only fires at all
        // if something on the machine is actually calling
        // `php artisan schedule:run` on a timer (cron on Linux, or a
        // Windows Task Scheduler entry) — otherwise the "Sync Now"
        // button on /sync is the way changes go out.
        if (config('sync.role') === 'school') {
            $schedule->command('sync:push')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('sync:pull')->everyFiveMinutes()->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
