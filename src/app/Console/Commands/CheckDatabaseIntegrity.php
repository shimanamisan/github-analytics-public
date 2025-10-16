<?php

namespace App\Console\Commands;

use App\Models\GitHubRepository;
use App\Models\GitHubView;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDatabaseIntegrity extends Command
{
    protected $signature = 'github:check-integrity';
    protected $description = 'データベースの整合性をチェックし、重複データを報告します';

    public function handle()
    {
        $this->info('データベース整合性チェックを開始します...');

        // 1. 重複リポジトリのチェック
        $this->checkDuplicateRepositories();

        // 2. 重複ビューデータのチェック
        $this->checkDuplicateViews();

        // 3. 孤立したビューデータのチェック
        $this->checkOrphanedViews();

        $this->info('データベース整合性チェックが完了しました。');
    }

    private function checkDuplicateRepositories()
    {
        $this->line('重複リポジトリをチェック中...');
        
        $duplicates = GitHubRepository::select('owner', 'repo', DB::raw('COUNT(*) as count'))
            ->groupBy('owner', 'repo')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->warn("重複リポジトリが {$duplicates->count()} 件見つかりました:");
            foreach ($duplicates as $dup) {
                $this->line("  - {$dup->owner}/{$dup->repo} ({$dup->count}件)");
            }
        } else {
            $this->info('重複リポジトリは見つかりませんでした。');
        }
    }

    private function checkDuplicateViews()
    {
        $this->line('重複ビューデータをチェック中...');
        
        $duplicates = GitHubView::select('repository_id', 'date', DB::raw('COUNT(*) as count'))
            ->groupBy('repository_id', 'date')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->warn("重複ビューデータが {$duplicates->count()} 件見つかりました:");
            foreach ($duplicates as $dup) {
                $repo = GitHubRepository::find($dup->repository_id);
                $repoName = $repo ? $repo->full_name : "ID:{$dup->repository_id}";
                $this->line("  - {$repoName} ({$dup->date}) - {$dup->count}件");
            }
        } else {
            $this->info('重複ビューデータは見つかりませんでした。');
        }
    }

    private function checkOrphanedViews()
    {
        $this->line('孤立したビューデータをチェック中...');
        
        $orphaned = GitHubView::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('github_repositories')
                ->whereColumn('github_repositories.id', 'github_views.repository_id');
        })->count();

        if ($orphaned > 0) {
            $this->warn("孤立したビューデータが {$orphaned} 件見つかりました。");
        } else {
            $this->info('孤立したビューデータは見つかりませんでした。');
        }
    }
}
