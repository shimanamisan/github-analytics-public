<?php

namespace App\Http\Controllers;

use App\Models\GitHubView;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GitHubViewController extends Controller
{
    /**
     * 訪問数データの一覧を表示
     */
    public function index(Request $request): View
    {
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
        
        // リポジトリでフィルタリング
        if ($request->filled('repository_id')) {
            $query->forRepository($request->repository_id);
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
        
        $views = $query->orderBy('date', 'desc')
                       ->paginate(30);
        
        // リポジトリ一覧を取得（フィルター用）
        $repositories = \App\Models\GitHubRepository::orderBy('name')->get();
        
        // プロジェクト一覧を取得（後方互換性のため）
        $projects = GitHubView::distinct()->pluck('project');
        
        // サーバーサイドでチャートデータを準備
        $chartQuery = GitHubView::with('repository');
        
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
        
        $chartData = $chartQuery->dateRange($startDate, $endDate)
                                 ->orderBy('date', 'asc')
                                 ->get(['date', 'count', 'uniques'])
                                 ->map(function($item) {
                                    return [
                                        'date' => $item->date->format('Y-m-d'),
                                        'count' => $item->count,
                                        'uniques' => $item->uniques
                                    ];
                                });
        
        // サーバーサイドで統計情報を準備
        $statsQuery = GitHubView::query();
        
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
        $query = GitHubView::with('repository');
        
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
        
        $data = $query->dateRange($startDate, $endDate)
                     ->orderBy('date', 'asc')
                     ->get(['date', 'count', 'uniques', 'repository_id']);
        
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
        $stats = GitHubView::selectRaw('
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
        $projectStats = GitHubView::selectRaw('
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
