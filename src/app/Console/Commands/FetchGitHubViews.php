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
    protected $signature = 'github:fetch-views {--repository= : 特定のリポジトリIDを指定} {--test : テスト実行} {--force : 過去14日分を強制的に再取得}';

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
        // カスタムログチャンネルを使用
        $githubLogger = Log::channel('github-commands');
        
        $forceMode = $this->option('force');
        $testMode = $this->option('test');

        $modeInfo = [];
        if ($forceMode) {
            $modeInfo[] = '強制再取得モード';
        }
        if ($testMode) {
            $modeInfo[] = 'テストモード';
        }
        $modeStr = !empty($modeInfo) ? ' (' . implode(', ', $modeInfo) . ')' : '';

        $githubLogger->info('GitHub訪問数データの取得を開始します...' . $modeStr);
        $this->info('GitHub訪問数データの取得を開始します...' . $modeStr);

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
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
        }

        $completionMsg = "データ取得完了: 総新規 {$totalInserted}件, 総更新 {$totalUpdated}件, エラー {$errorCount}件";
        $githubLogger->info($completionMsg);
        $this->info($completionMsg);
        
        // 統計ログを記録
        Log::info('GitHub訪問数データ取得完了（複数リポジトリ）', [
            'repositories_processed' => $repositories->count(),
            'total_inserted' => $totalInserted,
            'total_updated' => $totalUpdated,
            'error_count' => $errorCount,
            'is_test' => $this->option('test'),
            'is_force' => $this->option('force')
        ]);

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * 個別のリポジトリの訪問数データを取得
     */
    private function fetchRepositoryViews(GitHubRepository $repository): array
    {
        // リポジトリの所有者のトークンを取得
        $user = $repository->user;
        if (!$user || !$user->hasGitHubSettings()) {
            throw new Exception('リポジトリの所有者のGitHub設定が完了していません');
        }

        $token = $user->getGitHubToken();
        if (!$token) {
            throw new Exception('リポジトリの所有者のGitHubトークンが取得できません');
        }

        // 最新の記録日を取得
        $lastRecord = GitHubView::where('repository_id', $repository->id)
            ->orderBy('date', 'desc')
            ->first();

        // --forceオプションが指定された場合は、過去14日分を強制的に再取得
        // そうでない場合は、最後の記録日の翌日から取得
        if ($this->option('force')) {
            // 強制再取得モード: 14日前から取得（GitHub APIの制限）
            $lastDate = now()->subDays(15);
        } else {
            // 通常モード: 最後の記録日を取得（モデルのdateカラムはCarbonオブジェクトとしてキャストされている）
            $lastDate = $lastRecord
                ? ($lastRecord->date instanceof Carbon ? $lastRecord->date->copy() : Carbon::parse($lastRecord->date))
                : now()->subDays(14);
        }

        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get("https://api.github.com/repos/{$repository->full_name}/traffic/views");

        if (!$response->successful()) {
            $errorDetails = [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
                'repository' => $repository->full_name,
                'token_length' => strlen($token)
            ];
            
            Log::error('GitHub API エラー', $errorDetails);
            throw new Exception("GitHub API エラー: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        
        // デバッグログ：APIレスポンスの内容を記録（本番環境でも確認できるようにINFOレベル）
        $githubLogger = Log::channel('github-commands');
        $githubLogger->info('GitHub API レスポンス', [
            'repository_id' => $repository->id,
            'repository' => $repository->full_name,
            'views_count' => isset($data['views']) ? count($data['views']) : 0,
            'sample_view' => isset($data['views'][0]) ? $data['views'][0] : null,
            'full_response_keys' => array_keys($data ?? [])
        ]);
        
        $insertedCount = 0;
        $updatedCount = 0;

        // GitHub APIから取得したデータを日付でインデックス化
        $apiViews = [];
        if (isset($data['views'])) {
            foreach ($data['views'] as $view) {
                // タイムスタンプをUTCとしてパースし、日付文字列に変換
                // GitHub APIはUTCでタイムスタンプを返すため、UTCのまま日付を抽出
                $timestamp = Carbon::parse($view['timestamp'])->utc();
                $viewDate = $timestamp->format('Y-m-d');
                
                $apiViews[$viewDate] = [
                    'count' => $view['count'],
                    'uniques' => $view['uniques']
                ];
                
                // デバッグログ：各データの処理内容を記録（最初の3件のみ、本番環境でも確認できるようにINFOレベル）
                if (count($apiViews) <= 3) {
                    $githubLogger->info('APIデータ処理', [
                        'repository_id' => $repository->id,
                        'original_timestamp' => $view['timestamp'],
                        'parsed_date' => $viewDate,
                        'count' => $view['count'],
                        'uniques' => $view['uniques']
                    ]);
                }
            }
        }
        
        // 処理対象期間を決定（最後の記録日の翌日から昨日まで、テスト時は今日まで）
        $desiredStartDate = $lastDate->copy()->addDay();
        $desiredEndDate = $this->option('test') ? now() : now()->subDay();
        
        // APIから取得できるデータの範囲を確認
        $apiAvailableDates = array_keys($apiViews);
        $apiMinDate = !empty($apiAvailableDates) ? Carbon::parse(min($apiAvailableDates)) : null;
        $apiMaxDate = !empty($apiAvailableDates) ? Carbon::parse(max($apiAvailableDates)) : null;
        
        // 実際に処理できる範囲を決定
        // 開始日は、希望する開始日とAPIが利用可能な開始日のうち、遅い方（新しい方）を使用
        $startDate = $apiMinDate && $desiredStartDate->lt($apiMinDate) 
            ? $apiMinDate->copy() 
            : $desiredStartDate;
        
        // 終了日は、希望する終了日とAPIが利用可能な終了日のうち、早い方（古い方）を使用
        $endDate = $apiMaxDate && $desiredEndDate->gt($apiMaxDate)
            ? $apiMaxDate->copy()
            : $desiredEndDate;
        
        // デバッグログ：処理対象期間とAPIデータの範囲を記録（本番環境でも確認できるようにINFOレベル）
        $githubLogger->info('処理対象期間の決定', [
            'repository_id' => $repository->id,
            'repository' => $repository->full_name,
            'force_mode' => $this->option('force'),
            'last_record_date' => $lastRecord ? $lastRecord->date->format('Y-m-d') : 'なし',
            'last_date' => $lastDate->format('Y-m-d'),
            'desired_start_date' => $desiredStartDate->format('Y-m-d'),
            'desired_end_date' => $desiredEndDate->format('Y-m-d'),
            'api_min_date' => $apiMinDate ? $apiMinDate->format('Y-m-d') : 'なし',
            'api_max_date' => $apiMaxDate ? $apiMaxDate->format('Y-m-d') : 'なし',
            'actual_start_date' => $startDate->format('Y-m-d'),
            'actual_end_date' => $endDate->format('Y-m-d'),
            'api_views_dates' => array_keys($apiViews),
            'api_views_count' => count($apiViews)
        ]);
        
        // 開始日が終了日より後の場合は、処理対象期間がないためスキップ
        if ($startDate->gt($endDate)) {
            // 本番環境でも確認できるようにINFOレベルでログ出力
            $githubLogger = Log::channel('github-commands');
            $githubLogger->info('処理対象期間がありません（既に最新データがあるか、APIにデータがありません）', [
                'repository_id' => $repository->id,
                'repository' => $repository->full_name,
                'last_record_date' => $lastRecord ? $lastRecord->date->format('Y-m-d') : 'なし',
                'desired_start_date' => $desiredStartDate->format('Y-m-d'),
                'desired_end_date' => $desiredEndDate->format('Y-m-d'),
                'api_min_date' => $apiMinDate ? $apiMinDate->format('Y-m-d') : 'なし',
                'api_max_date' => $apiMaxDate ? $apiMaxDate->format('Y-m-d') : 'なし',
                'actual_start_date' => $startDate->format('Y-m-d'),
                'actual_end_date' => $endDate->format('Y-m-d')
            ]);
            return ['inserted' => 0, 'updated' => 0];
        }

        // 日付範囲をループして、アクセス数0の日も含めて登録
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // GitHub APIにデータがある場合はその値を、ない場合は0を使用
            $viewData = $apiViews[$dateString] ?? ['count' => 0, 'uniques' => 0];
            
            // デバッグログ：保存前のデータ内容を記録（最初の3件のみ、本番環境でも確認できるようにINFOレベル）
            if ($insertedCount + $updatedCount < 3) {
                $githubLogger->info('データ保存前', [
                    'repository_id' => $repository->id,
                    'date' => $dateString,
                    'view_data' => $viewData,
                    'api_has_data' => isset($apiViews[$dateString]),
                    'api_views_keys' => array_keys($apiViews)
                ]);
            }

            $result = GitHubView::updateOrCreate(
                [
                    'repository_id' => $repository->id,
                    'date' => $dateString
                ],
                [
                    'project' => $repository->full_name,
                    'count' => $viewData['count'],
                    'uniques' => $viewData['uniques']
                ]
            );
            
            // デバッグログ：保存後のデータ内容を記録（最初の3件のみ、本番環境でも確認できるようにINFOレベル）
            if ($insertedCount + $updatedCount < 3) {
                $githubLogger->info('データ保存後', [
                    'repository_id' => $repository->id,
                    'date' => $dateString,
                    'saved_count' => $result->count,
                    'saved_uniques' => $result->uniques,
                    'was_recently_created' => $result->wasRecentlyCreated
                ]);
            }

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
