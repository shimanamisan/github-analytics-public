<?php

namespace App\Http\Controllers;

use App\Models\GitHubView;
use App\Models\GitHubRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class GitHubViewController extends Controller
{
    /**
     * 訪問数データの一覧を表示
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // デバッグ用ログ
        \Log::info('GitHub views request parameters', [
            'project' => $request->get('project'),
            'repository_id' => $request->get('repository_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'has_project' => $request->has('project'),
            'filled_project' => $request->filled('project'),
            'project_type' => gettype($request->get('project')),
            'project_empty' => empty($request->get('project'))
        ]);
        
        $query = GitHubView::with('repository');
        
        // ユーザーが登録したリポジトリのみに制限（管理者以外）
        if ($user && !$user->isAdmin()) {
            $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
            $query->whereIn('repository_id', $userRepositoryIds);
            // repository_idがnullのデータは除外
            $query->whereNotNull('repository_id');
        } else if (!$user) {
            // 認証されていないユーザーは何も表示しない
            $query->whereRaw('1 = 0');
        }
        
        // リポジトリでフィルタリング
        if ($request->filled('repository_id')) {
            $repositoryId = $request->repository_id;
            
            // 一般ユーザーの場合は、自分のリポジトリかチェック
            if ($user && !$user->isAdmin()) {
                $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
                if (!$userRepositoryIds->contains($repositoryId)) {
                    abort(403, 'このリポジトリにアクセスする権限がありません。');
                }
            }
            
            $query->forRepository($repositoryId);
        }
        
        // プロジェクトでフィルタリング（後方互換性のため）
        if ($request->filled('project')) {
            $project = $request->project;
            \Log::info('Applying project filter', ['project' => $project]);
            
            // プロジェクト名の検証
            if (is_string($project) && !empty(trim($project))) {
                $query->forProject(trim($project));
            } else {
                \Log::warning('Invalid project parameter', ['project' => $project]);
            }
        }
        
        // 日付範囲でフィルタリング（nullや空文字列の場合はフィルタリングしない）
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        
        // プロジェクトパラメータが空の場合（「すべて」が選択されている場合）は、
        // リポジトリごとに集計したデータを表示
        if (!$request->filled('project') && !$request->filled('repository_id')) {
            $views = $query->selectRaw('
                project,
                repository_id,
                COUNT(*) as record_count,
                SUM(count) as count,
                SUM(uniques) as uniques,
                MIN(date) as first_date,
                MAX(date) as last_date
            ')
            ->groupBy('project', 'repository_id')
            ->orderBy('count', 'desc')
            ->paginate(30);
            
            // リポジトリ情報をロード
            $views->getCollection()->load('repository');
            
            // 日付フィールドをCarbonオブジェクトに変換
            $views->getCollection()->transform(function($item) {
                $item->first_date = \Carbon\Carbon::parse($item->first_date);
                $item->last_date = \Carbon\Carbon::parse($item->last_date);
                return $item;
            });
        } else {
            // 特定のプロジェクトまたはリポジトリが選択されている場合は、個別のデータを取得
            $views = $query->orderBy('date', 'desc')
                           ->paginate(30);
        }
        
        // リポジトリ一覧を取得（フィルター用）
        if ($user && $user->isAdmin()) {
            $repositories = GitHubRepository::orderBy('name')->get();
        } else {
            $repositories = $user ? GitHubRepository::forUser($user->id)->orderBy('name')->get() : collect();
        }
        
        // プロジェクト一覧を取得（後方互換性のため）
        if ($user && $user->isAdmin()) {
            $projects = GitHubView::distinct()->pluck('project');
        } else {
            if ($user) {
                $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
                $projects = GitHubView::whereIn('repository_id', $userRepositoryIds)->distinct()->pluck('project');
            } else {
                $projects = collect();
            }
        }
        
        // サーバーサイドでチャートデータを準備
        $chartQuery = GitHubView::with('repository');
        
        // ユーザーが登録したリポジトリのみに制限（管理者以外）
        if ($user && !$user->isAdmin()) {
            $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
            $chartQuery->whereIn('repository_id', $userRepositoryIds);
            // repository_idがnullのデータは除外
            $chartQuery->whereNotNull('repository_id');
        } else if (!$user) {
            // 認証されていないユーザーは何も表示しない
            $chartQuery->whereRaw('1 = 0');
        }
        
        // フィルターを適用（チャート用）
        if ($request->filled('repository_id')) {
            $chartQuery->forRepository($request->repository_id);
        }
        
        if ($request->filled('project')) {
            $project = $request->project;
            \Log::info('Applying project filter for chart', ['project' => $project]);
            
            // プロジェクト名の検証
            if (is_string($project) && !empty(trim($project))) {
                $chartQuery->forProject(trim($project));
            } else {
                \Log::warning('Invalid project parameter for chart', ['project' => $project]);
            }
        }
        
        // 日付範囲でフィルタリング（nullや空文字列の場合はデフォルト値を使用）
        $startDate = $request->filled('start_date') ? $request->start_date : now()->subDays(30)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : now()->format('Y-m-d');
        
        // 日付フィルターをチャートクエリに直接適用
        $chartQuery->whereBetween('date', [$startDate, $endDate]);
        
        // デバッグ用ログ
        \Log::info('Chart date filter applied', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'request_start_date' => $request->get('start_date'),
            'request_end_date' => $request->get('end_date'),
            'start_date_filled' => $request->filled('start_date'),
            'end_date_filled' => $request->filled('end_date')
        ]);
        
        // プロジェクトパラメータが空の場合（「すべて」が選択されている場合）は、
        // すべてのリポジトリのデータを日付ごとに集計
        if (!$request->filled('project') && !$request->filled('repository_id')) {
            $chartData = $chartQuery->selectRaw('date, SUM(count) as count, SUM(uniques) as uniques')
                                     ->groupBy('date')
                                     ->orderBy('date', 'asc')
                                     ->get()
                                     ->map(function($item) {
                                        return [
                                            'date' => $item->date->format('Y-m-d'),
                                            'count' => (int)$item->count,
                                            'uniques' => (int)$item->uniques
                                        ];
                                    });
        } else {
            // 特定のプロジェクトまたはリポジトリが選択されている場合は、個別のデータを取得
            $chartData = $chartQuery->orderBy('date', 'asc')
                                     ->get(['date', 'count', 'uniques'])
                                     ->map(function($item) {
                                        return [
                                            'date' => $item->date->format('Y-m-d'),
                                            'count' => $item->count,
                                            'uniques' => $item->uniques
                                        ];
                                    });
        }
        
        // チャートデータのデバッグログ
        \Log::info('Chart data result', [
            'data_count' => $chartData->count(),
            'first_date' => $chartData->first()['date'] ?? 'no data',
            'last_date' => $chartData->last()['date'] ?? 'no data'
        ]);
        
        // サーバーサイドで統計情報を準備
        $statsQuery = GitHubView::query();
        
        // ユーザーが登録したリポジトリのみに制限（管理者以外）
        if ($user && !$user->isAdmin()) {
            $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
            $statsQuery->whereIn('repository_id', $userRepositoryIds);
            // repository_idがnullのデータは除外
            $statsQuery->whereNotNull('repository_id');
        } else if (!$user) {
            // 認証されていないユーザーは何も表示しない
            $statsQuery->whereRaw('1 = 0');
        }
        
        // フィルターを適用（統計用）
        if ($request->filled('repository_id')) {
            $statsQuery->forRepository($request->repository_id);
        }
        
        if ($request->filled('project')) {
            $project = $request->project;
            \Log::info('Applying project filter for stats', ['project' => $project]);
            
            // プロジェクト名の検証
            if (is_string($project) && !empty(trim($project))) {
                $statsQuery->forProject(trim($project));
            } else {
                \Log::warning('Invalid project parameter for stats', ['project' => $project]);
            }
        }
        
        if ($request->filled('start_date')) {
            $statsQuery->where('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $statsQuery->where('date', '<=', $request->end_date);
        }
        
        // プロジェクトパラメータが空の場合（「すべて」が選択されている場合）は、
        // すべてのリポジトリのデータを集計（フィルターは既に適用済み）
        $stats = $statsQuery->selectRaw('
            COUNT(*) as total_records,
            SUM(count) as total_views,
            SUM(uniques) as total_uniques,
            AVG(count) as avg_views,
            AVG(uniques) as avg_uniques,
            MAX(count) as max_views,
            MAX(uniques) as max_uniques
        ')->first();
        
        return view('github.views', compact(
            'views', 
            'repositories', 
            'projects', 
            'chartData', 
            'stats'
        ));
    }

    /**
     * チャート用のJSONデータを返す（API用、後方互換性のため残す）
     */
    public function chart(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = GitHubView::with('repository');
        
        // ユーザーが登録したリポジトリのみに制限（管理者以外）
        if (!$user->isAdmin()) {
            $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
            $query->whereIn('repository_id', $userRepositoryIds);
        }
        
        // デバッグ用ログ
        \Log::info('Chart request parameters', [
            'project' => $request->get('project'),
            'repository_id' => $request->get('repository_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'has_project' => $request->has('project'),
            'filled_project' => $request->filled('project')
        ]);
        
        // リポジトリでフィルタリング
        if ($request->filled('repository_id')) {
            $query->forRepository($request->repository_id);
        }
        
        // プロジェクトでフィルタリング（後方互換性のため）
        if ($request->filled('project')) {
            $project = $request->project;
            \Log::info('Applying project filter for chart API', ['project' => $project]);
            
            // プロジェクト名の検証
            if (is_string($project) && !empty(trim($project))) {
                $query->forProject(trim($project));
            } else {
                \Log::warning('Invalid project parameter for chart API', ['project' => $project]);
            }
        }
        
        // 日付範囲でフィルタリング（nullや空文字列の場合はデフォルト値を使用）
        $startDate = $request->filled('start_date') ? $request->start_date : now()->subDays(30)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : now()->format('Y-m-d');
        
        // 日付フィルターをクエリに直接適用
        $query->whereBetween('date', [$startDate, $endDate]);
        
        // プロジェクトパラメータが空の場合（「すべて」が選択されている場合）は、
        // すべてのリポジトリのデータを日付ごとに集計
        if (!$request->filled('project') && !$request->filled('repository_id')) {
            $data = $query->selectRaw('date, SUM(count) as count, SUM(uniques) as uniques')
                         ->groupBy('date')
                         ->orderBy('date', 'asc')
                         ->get()
                         ->map(function($item) {
                            return [
                                'date' => $item->date->format('Y-m-d'),
                                'count' => (int)$item->count,
                                'uniques' => (int)$item->uniques
                            ];
                        });
        } else {
            // 特定のプロジェクトまたはリポジトリが選択されている場合は、個別のデータを取得
            $data = $query->orderBy('date', 'asc')
                         ->get(['date', 'count', 'uniques', 'repository_id']);
        }
        
        // デバッグ用ログ
        \Log::info('Chart query result', [
            'data_count' => $data->count(),
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        
        return response()->json($data);
    }

    /**
     * 統計情報を返す（API用、後方互換性のため残す）
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $query = GitHubView::query();
        
        // ユーザーが登録したリポジトリのみに制限（管理者以外）
        if (!$user->isAdmin()) {
            $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
            $query->whereIn('repository_id', $userRepositoryIds);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_records,
            SUM(count) as total_views,
            SUM(uniques) as total_uniques,
            AVG(count) as avg_views,
            AVG(uniques) as avg_uniques,
            MAX(count) as max_views,
            MAX(uniques) as max_uniques
        ')->first();
        
        return response()->json($stats);
    }

    /**
     * プロジェクト別の統計情報を返す
     */
    public function projectStats(): JsonResponse
    {
        $user = Auth::user();
        $query = GitHubView::query();
        
        // ユーザーが登録したリポジトリのみに制限（管理者以外）
        if (!$user->isAdmin()) {
            $userRepositoryIds = GitHubRepository::forUser($user->id)->pluck('id');
            $query->whereIn('repository_id', $userRepositoryIds);
        }
        
        $projectStats = $query->selectRaw('
            project,
            COUNT(*) as record_count,
            SUM(count) as total_views,
            SUM(uniques) as total_uniques,
            AVG(count) as avg_views,
            AVG(uniques) as avg_uniques
        ')
        ->groupBy('project')
        ->get();
        
        return response()->json($projectStats);
    }
}
