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
        // Volunteer matching system scheduled tasks
        $schedule->command('volunteer:send-digest --type=weekly')
                 ->weekly()
                 ->sundays()
                 ->at('09:00')
                 ->description('Send weekly volunteer opportunity digest');

        $schedule->command('volunteer:send-digest --type=trending')
                 ->dailyAt('18:00')
                 ->description('Send trending volunteer opportunities');

        // Performance optimization tasks
        $schedule->command('volunteering:optimize --cache')
                 ->hourly()
                 ->description('Warm up volunteering cache');

        $schedule->command('volunteering:optimize --cleanup')
                 ->daily()
                 ->at('02:00')
                 ->description('Clean up old cache entries');

        $schedule->command('volunteering:optimize --indexes')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->description('Optimize database indexes');

        // Profile analytics reporting tasks
        $schedule->command('profile:analytics-report --type=summary --period=daily --format=json --save')
                 ->dailyAt('01:00')
                 ->description('Generate daily profile analytics summary');

        $schedule->command('profile:analytics-report --type=comprehensive --period=weekly --format=html --save')
                 ->weekly()
                 ->mondays()
                 ->at('06:00')
                 ->description('Generate weekly comprehensive profile analytics');

        $schedule->command('profile:analytics-report --type=behavioral --period=monthly --format=csv --save')
                 ->monthly()
                 ->at('07:00')
                 ->description('Generate monthly behavioral analytics report');

        $schedule->command('profile:analytics-report --type=scoring --period=quarterly --format=json --save')
                 ->quarterly()
                 ->at('08:00')
                 ->description('Generate quarterly profile scoring report');

        // Profile analytics cleanup tasks
        $schedule->command('profile:cleanup-reports --force')
                 ->weekly()
                 ->sundays()
                 ->at('04:00')
                 ->description('Clean up old profile analytics reports');
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