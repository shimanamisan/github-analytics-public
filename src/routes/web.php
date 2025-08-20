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

// 認証ルート
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 管理画面のルート（認証必須）
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/repositories', [App\Http\Controllers\AdminController::class, 'repositories'])->name('repositories');
});
