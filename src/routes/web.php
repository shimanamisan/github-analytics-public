<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\GitHubViewController::class, 'index'])->name('home');



// 認証ルート
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 管理画面のルート（認証必須）
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/repositories', [App\Http\Controllers\AdminController::class, 'repositories'])->name('repositories');
    Route::get('/repositories/create', [App\Http\Controllers\AdminController::class, 'createRepository'])->name('repositories.create');
});
