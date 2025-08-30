<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\GitHubViewController::class, 'index'])->name('home');

// GitHubフォロワー情報ルート（認証必須）
Route::prefix('github')->name('github.')->middleware('auth')->group(function () {
    Route::get('/followers', [App\Http\Controllers\GitHubFollowerController::class, 'index'])->name('followers');
    Route::get('/follower-details', [App\Http\Controllers\GitHubFollowerController::class, 'details'])->name('follower-details');
    
    // API エンドポイント
    Route::get('/api/followers/chart', [App\Http\Controllers\GitHubFollowerController::class, 'chart'])->name('followers.chart');
    Route::get('/api/followers/stats', [App\Http\Controllers\GitHubFollowerController::class, 'stats'])->name('followers.stats');
    Route::get('/api/followers/influential', [App\Http\Controllers\GitHubFollowerController::class, 'influential'])->name('followers.influential');
    Route::get('/api/followers/recent', [App\Http\Controllers\GitHubFollowerController::class, 'recentActivity'])->name('followers.recent');
});



// 認証ルート
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 管理画面のルート（認証必須）
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/repositories', [App\Http\Controllers\AdminController::class, 'repositories'])->name('repositories');
    Route::get('/repositories/create', [App\Http\Controllers\AdminController::class, 'createRepository'])->name('repositories.create');
    Route::get('/followers', [App\Http\Controllers\AdminController::class, 'followers'])->name('followers');
    Route::get('/follower-details', [App\Http\Controllers\AdminController::class, 'followerDetails'])->name('follower-details');
});
