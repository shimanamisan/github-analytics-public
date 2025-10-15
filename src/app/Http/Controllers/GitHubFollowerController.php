<?php

namespace App\Http\Controllers;

use App\Models\GitHubFollower;
use App\Models\GitHubFollowerDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class GitHubFollowerController extends Controller
{
    /**
     * フォロワー統計データの一覧を表示
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $query = GitHubFollower::query();
        
        // ユーザー制限を適用
        if ($user && !$user->isAdmin()) {
            // 一般ユーザーは自分のデータのみアクセス可能
            $query->forUser($user->name);
        } else if (!$user) {
            // 認証されていないユーザーは何も表示しない
            $query->whereRaw('1 = 0');
        }
        
        // ユーザーでフィルタリング（管理者のみ）
        if ($request->filled('username') && $user && $user->isAdmin()) {
            $query->forUser($request->username);
        }
        
        // 日付範囲でフィルタリング
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        
        // フォロワー統計データを取得
        $followers = $query->orderBy('date', 'desc')->paginate(30);
        
        // ユーザー一覧を取得（フィルター用）
        if ($user && $user->isAdmin()) {
            $usernames = GitHubFollower::distinct()->pluck('username');
        } else if ($user) {
            $usernames = collect([$user->name]);
        } else {
            $usernames = collect();
        }
        
        // チャートデータを準備
        $chartQuery = GitHubFollower::query();
        
        // ユーザー制限を適用（チャート用）
        if ($user && !$user->isAdmin()) {
            $chartQuery->forUser($user->name);
        } else if (!$user) {
            $chartQuery->whereRaw('1 = 0');
        }
        
        // フィルターを適用（チャート用）
        if ($request->filled('username') && $user && $user->isAdmin()) {
            $chartQuery->forUser($request->username);
        }
        
        // 日付範囲でフィルタリング（デフォルトは過去30日）
        $startDate = $request->filled('start_date') ? $request->start_date : now()->subDays(30)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : now()->format('Y-m-d');
        
        $chartQuery->whereBetween('date', [$startDate, $endDate]);
        
        $chartData = $chartQuery->orderBy('date', 'asc')
                              ->get(['date', 'followers_count', 'following_count', 'public_repos'])
                              ->map(function($item) {
                                  return [
                                      'date' => $item->date->format('Y-m-d'),
                                      'followers_count' => $item->followers_count,
                                      'following_count' => $item->following_count,
                                      'public_repos' => $item->public_repos
                                  ];
                              });
        
        // 統計情報を準備
        $statsQuery = GitHubFollower::query();
        
        // ユーザー制限を適用（統計用）
        if ($user && !$user->isAdmin()) {
            $statsQuery->forUser($user->name);
        } else if (!$user) {
            $statsQuery->whereRaw('1 = 0');
        }
        
        // フィルターを適用（統計用）
        if ($request->filled('username') && $user && $user->isAdmin()) {
            $statsQuery->forUser($request->username);
        }
        
        if ($request->filled('start_date')) {
            $statsQuery->where('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $statsQuery->where('date', '<=', $request->end_date);
        }
        
        $stats = $statsQuery->selectRaw('
            COUNT(*) as total_records,
            MAX(followers_count) as max_followers,
            MIN(followers_count) as min_followers,
            AVG(followers_count) as avg_followers,
            MAX(following_count) as max_following,
            AVG(following_count) as avg_following,
            MAX(public_repos) as max_repos,
            AVG(public_repos) as avg_repos
        ')->first();
        
        // 最新のフォロワー成長率を計算
        $growthRate = null;
        if ($user && !$user->isAdmin()) {
            $latestRecord = GitHubFollower::forUser($user->name)->latest('date')->first();
            if ($latestRecord) {
                $growthRate = $latestRecord->getGrowthRate(30);
            }
        } else if ($request->filled('username') && $user && $user->isAdmin()) {
            $latestRecord = GitHubFollower::forUser($request->username)->latest('date')->first();
            if ($latestRecord) {
                $growthRate = $latestRecord->getGrowthRate(30);
            }
        }

        return view('github.followers', compact(
            'followers', 
            'usernames', 
            'chartData', 
            'stats',
            'growthRate'
        ));
    }

    /**
     * フォロワー詳細情報を表示
     */
    public function details(Request $request): View
    {
        $user = Auth::user();
        
        $query = GitHubFollowerDetail::with('githubFollower');
        
        // ユーザー制限を適用
        if ($user && !$user->isAdmin()) {
            $query->forUser($user->name);
        } else if (!$user) {
            $query->whereRaw('1 = 0');
        }
        
        // ユーザーでフィルタリング（管理者のみ）
        if ($request->filled('username') && $user && $user->isAdmin()) {
            $query->forUser($request->username);
        }
        
        // フォロワー名で検索
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('follower_username', 'like', "%{$search}%")
                  ->orWhere('follower_name', 'like', "%{$search}%");
            });
        }
        
        // 並び順
        $sortBy = $request->get('sort_by', 'followed_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        switch ($sortBy) {
            case 'followers':
                $query->orderBy('follower_followers', $sortOrder);
                break;
            case 'repos':
                $query->orderBy('follower_public_repos', $sortOrder);
                break;
            default:
                $query->orderBy('followed_at', $sortOrder);
        }
        
        // フォロワー詳細データを取得
        $followerDetails = $query->paginate(20);
        
        // ユーザー一覧を取得（フィルター用）
        if ($user && $user->isAdmin()) {
            $usernames = GitHubFollowerDetail::distinct()->pluck('target_username');
        } else if ($user) {
            $usernames = collect([$user->name]);
        } else {
            $usernames = collect();
        }
        
        // 統計情報
        $detailStats = GitHubFollowerDetail::active();
        
        // ユーザー制限を適用
        if ($user && !$user->isAdmin()) {
            $detailStats->forUser($user->name);
        } else if (!$user) {
            $detailStats->whereRaw('1 = 0');
        } else if ($request->filled('username')) {
            $detailStats->forUser($request->username);
        }
        
        $detailStats = $detailStats->selectRaw('
                COUNT(*) as total_followers,
                AVG(follower_followers) as avg_follower_count,
                AVG(follower_public_repos) as avg_repos,
                MAX(follower_followers) as max_follower_count,
                COUNT(CASE WHEN followed_at >= ? THEN 1 END) as recent_followers
            ', [now()->subDays(7)])
            ->first();

        // フォロー解除統計を追加
        $unfollowStats = null;
        if ($user && !$user->isAdmin()) {
            $unfollowStats = GitHubFollowerDetail::forUser($user->name)
                ->selectRaw('
                    COUNT(CASE WHEN unfollowed_at >= ? THEN 1 END) as recent_unfollowed,
                    COUNT(CASE WHEN is_active = false THEN 1 END) as total_unfollowed
                ', [now()->subDays(7)])
                ->first();
        } else if ($request->filled('username') && $user && $user->isAdmin()) {
            $unfollowStats = GitHubFollowerDetail::forUser($request->username)
                ->selectRaw('
                    COUNT(CASE WHEN unfollowed_at >= ? THEN 1 END) as recent_unfollowed,
                    COUNT(CASE WHEN is_active = false THEN 1 END) as total_unfollowed
                ', [now()->subDays(7)])
                ->first();
        }

        return view('github.follower-details', compact(
            'followerDetails', 
            'usernames',
            'detailStats',
            'unfollowStats'
        ));
    }

    /**
     * チャート用のJSONデータを返す
     */
    public function chart(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = GitHubFollower::query();
        
        // ユーザー制限を適用
        if ($user && !$user->isAdmin()) {
            $query->forUser($user->name);
        } else if (!$user) {
            $query->whereRaw('1 = 0');
        }
        
        // ユーザーでフィルタリング（管理者のみ）
        if ($request->filled('username') && $user && $user->isAdmin()) {
            $query->forUser($request->username);
        }
        
        // 日付範囲でフィルタリング
        $startDate = $request->filled('start_date') ? $request->start_date : now()->subDays(30)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : now()->format('Y-m-d');
        
        $query->dateRange($startDate, $endDate);
        
        $data = $query->orderBy('date', 'asc')
                     ->get(['date', 'followers_count', 'following_count', 'public_repos'])
                     ->map(function($item) {
                         return [
                             'date' => $item->date->format('Y-m-d'),
                             'followers_count' => $item->followers_count,
                             'following_count' => $item->following_count,
                             'public_repos' => $item->public_repos
                         ];
                     });
        
        return response()->json($data);
    }

    /**
     * フォロワー統計情報を返す
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = GitHubFollower::query();
        
        // ユーザー制限を適用
        if ($user && !$user->isAdmin()) {
            $query->forUser($user->name);
        } else if (!$user) {
            $query->whereRaw('1 = 0');
        } else if ($request->filled('username')) {
            $query->forUser($request->username);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_records,
            MAX(followers_count) as max_followers,
            MIN(followers_count) as min_followers,
            AVG(followers_count) as avg_followers,
            MAX(following_count) as max_following,
            AVG(following_count) as avg_following,
            MAX(public_repos) as max_repos,
            AVG(public_repos) as avg_repos
        ')->first();
        
        return response()->json($stats);
    }


    /**
     * 最近のフォロワー増減を取得
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $username = $request->get('username');
        $days = $request->get('days', 7);
        
        $query = GitHubFollowerDetail::active()
                                   ->recentFollowers($days);
        
        if ($username) {
            $query->forUser($username);
        }
        
        $recentFollowers = $query->get();
        
        return response()->json([
            'new_followers' => $recentFollowers->count(),
            'followers' => $recentFollowers
        ]);
    }
}
