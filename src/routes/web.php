<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// GitHub訪問数集計システムのルート
Route::prefix('github')->group(function () {
    Route::get('/views', [App\Http\Controllers\GitHubViewController::class, 'index'])->name('github.views');
    Route::get('/chart', [App\Http\Controllers\GitHubViewController::class, 'chart'])->name('github.chart');
    Route::get('/stats', [App\Http\Controllers\GitHubViewController::class, 'stats'])->name('github.stats');
    Route::get('/project-stats', [App\Http\Controllers\GitHubViewController::class, 'projectStats'])->name('github.project-stats');
});
