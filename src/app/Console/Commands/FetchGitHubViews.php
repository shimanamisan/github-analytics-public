<?php

namespace App\Console\Commands;

use App\Models\GitHubRepository;
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
    protected $signature = 'github:fetch-views {--repository= : 特定のリポジトリIDを指定} {--test : テスト実行}';

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

        // 特定のリポジトリが指定されている場合
        if ($repositoryId = $this->option('repository')) {
            $repositories = GitHubRepository::where('id', $repositoryId)->active()->get();
            if ($repositories->isEmpty()) {
                $this->error("指定されたリポジトリ（ID: {$repositoryId}）が見つからないか、無効になっています。");
                return 1;
            }
        } else {
            // すべてのアクティブなリポジトリを取得
            $repositories = GitHubRepository::active()->get();
        }

        if ($repositories->isEmpty()) {
            $this->warn('処理対象のリポジトリが見つかりません。');
            return 0;
        }

        $totalInserted = 0;
        $totalUpdated = 0;
        $errorCount = 0;

        foreach ($repositories as $repository) {
            $this->line("処理中: {$repository->display_name} ({$repository->full_name})");
            
            try {
                $result = $this->fetchRepositoryViews($repository);
                $totalInserted += $result['inserted'];
                $totalUpdated += $result['updated'];
                
                $this->info("  ✓ 新規: {$result['inserted']}件, 更新: {$result['updated']}件");
                
            } catch (Exception $e) {
                $errorCount++;
                $this->error("  ✗ エラー: {$e->getMessage()}");
                
                Log::error('GitHub訪問数データ取得エラー', [
                    'repository_id' => $repository->id,
                    'repository' => $repository->full_name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("データ取得完了: 総新規 {$totalInserted}件, 総更新 {$totalUpdated}件, エラー {$errorCount}件");
        
        // 統計ログを記録
        Log::info('GitHub訪問数データ取得完了（複数リポジトリ）', [
            'repositories_processed' => $repositories->count(),
            'total_inserted' => $totalInserted,
            'total_updated' => $totalUpdated,
            'error_count' => $errorCount,
            'is_test' => $this->option('test')
        ]);

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * 個別のリポジトリの訪問数データを取得
     */
    private function fetchRepositoryViews(GitHubRepository $repository): array
    {
        $token = $repository->token;
        
        if (!$token) {
            throw new Exception('GitHubトークンが設定されていません');
        }

        // 最新の記録日を取得
        $lastRecord = GitHubView::where('repository_id', $repository->id)
            ->orderBy('date', 'desc')
            ->first();

        $lastDate = $lastRecord ? Carbon::parse($lastRecord->date) : now()->subDays(14);

        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get("https://api.github.com/repos/{$repository->full_name}/traffic/views");

        if (!$response->successful()) {
            throw new Exception("GitHub API エラー: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        
        $insertedCount = 0;
        $updatedCount = 0;

        // GitHub APIから取得したデータを日付でインデックス化
        $apiViews = [];
        if (isset($data['views'])) {
            foreach ($data['views'] as $view) {
                $viewDate = Carbon::parse($view['timestamp'])->format('Y-m-d');
                $apiViews[$viewDate] = [
                    'count' => $view['count'],
                    'uniques' => $view['uniques']
                ];
            }
        }

        // 処理対象期間を決定（最後の記録日の翌日から昨日まで、テスト時は今日まで）
        $startDate = $lastDate->copy()->addDay();
        $endDate = $this->option('test') ? now() : now()->subDay();

        // 日付範囲をループして、アクセス数0の日も含めて登録
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // GitHub APIにデータがある場合はその値を、ない場合は0を使用
            $viewData = $apiViews[$dateString] ?? ['count' => 0, 'uniques' => 0];

            $result = GitHubView::updateOrCreate(
                [
                    'repository_id' => $repository->id,
                    'project' => $repository->full_name,
                    'date' => $dateString
                ],
                [
                    'count' => $viewData['count'],
                    'uniques' => $viewData['uniques']
                ]
            );

            if ($result->wasRecentlyCreated) {
                $insertedCount++;
            } else {
                $updatedCount++;
            }

            $currentDate->addDay();
        }

        return ['inserted' => $insertedCount, 'updated' => $updatedCount];
    }
}
