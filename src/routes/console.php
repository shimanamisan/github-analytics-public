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
    ->onFailure(function () {
        \Log::error('GitHub訪問数取得スケジュールが失敗しました');
    });

// GitHubフォロワー数取得（基本統計のみ）を毎日23:30に実行
Schedule::command('github:fetch-followers')
    ->dailyAt('23:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('GitHubフォロワー数取得スケジュールが失敗しました');
    });

// 詳細フォロワー情報取得を毎日00:30に実行（API制限を考慮して時間を分ける）
Schedule::command('github:fetch-followers --detailed')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('GitHub詳細フォロワー情報取得スケジュールが失敗しました');
    });

// データベースバックアップを毎日02:00に実行（Gzip圧縮）
Schedule::command('db:backup --format=gz')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Log::info('データベースバックアップが正常に完了しました');
    })
    ->onFailure(function () {
        \Log::error('データベースバックアップが失敗しました');
    });

// 週次データベースバックアップを毎週日曜日03:00に実行（非圧縮）
Schedule::command('db:backup --format=sql')
    ->weeklyOn(0, '03:00') // 日曜日の03:00
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Log::info('週次データベースバックアップが正常に完了しました');
    })
    ->onFailure(function () {
        \Log::error('週次データベースバックアップが失敗しました');
    });