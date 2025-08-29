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

// GitHubフォロワー数取得（基本統計のみ）を毎日23:30に実行
Schedule::command('github:fetch-followers')
    ->dailyAt('23:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/github-followers.log'))
    ->onFailure(function () {
        \Log::error('GitHubフォロワー数取得スケジュールが失敗しました');
    });

// 詳細フォロワー情報取得を毎週月曜日3:00に実行
Schedule::command('github:fetch-followers --detailed')
    ->weeklyOn(1, '03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/github-followers-detailed.log'))
    ->onFailure(function () {
        \Log::error('GitHub詳細フォロワー情報取得スケジュールが失敗しました');
    });