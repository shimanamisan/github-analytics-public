<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// GitHub訪問数データを毎日23:00に実行（重複実行防止付き）
Schedule::command('github:fetch-views')
    ->dailyAt('23:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/github-fetch.log'))
    ->onFailure(function () {
        \Log::error('GitHub訪問数取得スケジュールが失敗しました');
    });