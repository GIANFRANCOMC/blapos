<?php

namespace App\Console;

use Illuminate\Console\Scheduling\{Schedule};
use Illuminate\Foundation\Console\{Kernel as ConsoleKernel};

class Kernel extends ConsoleKernel {

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void {

        $schedule->command("notifications:send-subscriptions --limit=100")
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command("subscriptions:cancel-expired")
            ->hourly()
            ->withoutOverlapping();

        $schedule->command("attendances:close-stale-customers --limit=500")
            ->hourly()
            ->withoutOverlapping();

        $schedule->command("attendances:prune-customers --limit=1000")
            ->dailyAt("03:20")
            ->withoutOverlapping();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {

        $this->load(__DIR__."/Commands");

        require base_path("routes/console.php");

    }

}
