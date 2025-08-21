<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GitHubRepository;
use App\Models\GitHubView;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalRepositories = GitHubRepository::count();
        $activeRepositories = GitHubRepository::where('is_active', true)->count();
        $totalViews = GitHubView::sum('count');
        $totalUniques = GitHubView::sum('uniques');
        
        $recentRepositories = GitHubRepository::latest()->take(5)->get();
        $recentViews = GitHubView::with('repository')->latest()->take(10)->get();
        
        return view('admin.dashboard', compact(
            'totalRepositories', 
            'activeRepositories', 
            'totalViews', 
            'totalUniques',
            'recentRepositories',
            'recentViews'
        ));
    }

    public function repositories()
    {
        return view('admin.repositories');
    }

    public function createRepository()
    {
        return view('admin.create-repository');
    }
}
