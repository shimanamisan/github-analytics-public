<?php

namespace App\Console\Commands;

use App\Models\GitHubView;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGitHubViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:fetch-views';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GitHubリポジトリの訪問数データを取得します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('GitHub訪問数データの取得を開始します...');

        $token = config('services.github.token');
        $owner = config('services.github.owner');
        $repo = config('services.github.repo');

        if (!$token || !$owner || !$repo) {
            $this->error('GitHub設定が不完全です。.envファイルを確認してください。');
            return 1;
        }

        // 最新の記録日を取得
        $lastRecord = GitHubView::where('project', "{$owner}/{$repo}")
            ->orderBy('date', 'desc')
            ->first();

        $lastDate = $lastRecord ? $lastRecord->date : now()->subDays(14);

        try {
            $response = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json',
            ])->get("https://api.github.com/repos/{$owner}/{$repo}/traffic/views");

            if (!$response->successful()) {
                throw new Exception('GitHub API エラー: ' . $response->status() . ' - ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['views'])) {
                $this->warn('訪問数データが見つかりませんでした。');
                return 0;
            }

            $insertedCount = 0;
            $updatedCount = 0;

            foreach ($data['views'] as $view) {
                $viewDate = Carbon::parse($view['timestamp'])->format('Y-m-d');
                
                // 重複チェック & 今日以外のデータのみ
                if ($viewDate <= $lastDate->format('Y-m-d') || $viewDate === now()->format('Y-m-d')) {
                    continue;
                }

                $result = GitHubView::updateOrCreate(
                    [
                        'project' => "{$owner}/{$repo}",
                        'date' => $viewDate
                    ],
                    [
                        'count' => $view['count'],
                        'uniques' => $view['uniques']
                    ]
                );

                if ($result->wasRecentlyCreated) {
                    $insertedCount++;
                } else {
                    $updatedCount++;
                }
            }

            $this->info("データ取得完了: 新規 {$insertedCount}件, 更新 {$updatedCount}件");
            
            // ログに記録
            Log::info('GitHub訪問数データ取得完了', [
                'project' => "{$owner}/{$repo}",
                'inserted' => $insertedCount,
                'updated' => $updatedCount
            ]);

            return 0;

        } catch (Exception $e) {
            $this->error('GitHubデータ取得エラー: ' . $e->getMessage());
            
            Log::error('GitHub訪問数データ取得エラー', [
                'error' => $e->getMessage(),
                'project' => "{$owner}/{$repo}"
            ]);
            
            return 1;
        }
    }
}
