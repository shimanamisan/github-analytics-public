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
        $query = GitHubView::query();
        
        // プロジェクトでフィルタリング
        if ($request->has('project') && $request->project) {
            $query->forProject($request->project);
        }
        
        // 日付範囲でフィルタリング
        if ($request->has('start_date') && $request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }
        
        $views = $query->orderBy('date', 'desc')
                      ->paginate(30);
        
        // プロジェクト一覧を取得（フィルター用）
        $projects = GitHubView::distinct()->pluck('project');
        
        return view('github.views', compact('views', 'projects'));
    }

    /**
     * チャート用のJSONデータを返す
     */
    public function chart(Request $request): JsonResponse
    {
        $query = GitHubView::query();
        
        // プロジェクトでフィルタリング
        if ($request->has('project') && $request->project) {
            $query->forProject($request->project);
        }
        
        // 日付範囲でフィルタリング（デフォルトは過去30日）
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $data = $query->dateRange($startDate, $endDate)
                     ->orderBy('date', 'asc')
                     ->get(['date', 'count', 'uniques']);
        
        return response()->json($data);
    }

    /**
     * 統計情報を返す
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
