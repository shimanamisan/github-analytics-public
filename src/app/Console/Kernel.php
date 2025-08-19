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
        // GitHub訪問数データを毎日午前2時に取得
        $schedule->command('github:fetch-views')
                 ->dailyAt('02:00')
                 ->appendOutputTo(storage_path('logs/github-fetch.log'))
                 ->onFailure(function () {
                     \Log::error('GitHub訪問数取得スケジュールが失敗しました');
                 });

        // テスト用：毎分実行（開発環境のみ）
        if (app()->environment('local', 'development')) {
            $schedule->command('github:fetch-views')
                     ->everyMinute()
                     ->appendOutputTo(storage_path('logs/github-fetch-test.log'));
        }
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
