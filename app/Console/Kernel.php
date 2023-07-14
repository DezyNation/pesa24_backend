<?php

namespace App\Console;

use DateTimeZone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function scheduleTimezone(): DateTimeZone|string|null
{
    return 'Asia/Kolkata';
}
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function(){
            $wallet_sum = User::role(['retailer', 'distributor', 'super_distributor'])->sum('wallet');
            $capped_sum = User::role(['retailer', 'distributor', 'super_distributor'])->sum('minimum_balance');
            DB::table('market_balance')->insert([
                'market_balance' => $wallet_sum,
                'capped_balance' => $capped_sum,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        })
        ->dailyAt('02:35');
        // $schedule->command('inspire')->hourly();
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
