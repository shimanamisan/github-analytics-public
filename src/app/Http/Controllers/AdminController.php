<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GitHubRepository;
use App\Models\GitHubView;
use App\Models\GitHubFollower;
use App\Models\GitHubFollowerDetail;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // 管理者の場合はすべてのデータを表示、一般ユーザーの場合は自分のリポジトリのみ
        if ($user->isAdmin()) {
            $totalRepositories = GitHubRepository::count();
            $activeRepositories = GitHubRepository::where('is_active', true)->count();
            $totalViews = GitHubView::sum('count');
            $totalUniques = GitHubView::sum('uniques');
            $recentRepositories = GitHubRepository::latest()->take(5)->get();
            $recentViews = GitHubView::with('repository')->latest()->take(10)->get();
        } else {
            $userRepositories = GitHubRepository::forUser($user->id);
            $totalRepositories = $userRepositories->count();
            $activeRepositories = $userRepositories->where('is_active', true)->count();
            
            // ユーザーのリポジトリIDを取得
            $repositoryIds = $userRepositories->pluck('id');
            
            $totalViews = GitHubView::whereIn('repository_id', $repositoryIds)->sum('count');
            $totalUniques = GitHubView::whereIn('repository_id', $repositoryIds)->sum('uniques');
            $recentRepositories = $userRepositories->latest()->take(5)->get();
            $recentViews = GitHubView::with('repository')
                ->whereIn('repository_id', $repositoryIds)
                ->latest()
                ->take(10)
                ->get();
        }
        
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

    /**
     * フォロワー統計画面
     */
    public function followers()
    {
        return redirect()->route('github.followers');
    }

    /**
     * フォロワー詳細画面
     */
    public function followerDetails()
    {
        return redirect()->route('github.follower-details');
    }
}
