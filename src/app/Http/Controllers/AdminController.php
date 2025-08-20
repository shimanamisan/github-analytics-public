<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * 管理画面のダッシュボードを表示
     */
    public function dashboard(): View
    {
        return view('admin.dashboard');
    }

    /**
     * リポジトリ管理画面を表示
     */
    public function repositories(): View
    {
        return view('admin.repositories');
    }
}
