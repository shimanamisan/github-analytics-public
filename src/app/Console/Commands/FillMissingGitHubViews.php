<?php

namespace App\Console\Commands;

use App\Models\GitHubRepository;
use App\Models\GitHubView;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillMissingGitHubViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:fill-missing-views 
                           {--repository= : 特定のリポジトリIDを指定} 
                           {--start-date= : 開始日 (Y-m-d形式)}
                           {--end-date= : 終了日 (Y-m-d形式)}
                           {--dry-run : 実際には登録せず、対象データのみ表示}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '欠損している日付のGitHub訪問数データを0件として登録します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('欠損日付の補完処理を開始します...');

        // 対象リポジトリを決定
        if ($repositoryId = $this->option('repository')) {
            $repositories = GitHubRepository::where('id', $repositoryId)->get();
            if ($repositories->isEmpty()) {
                $this->error("指定されたリポジトリ（ID: {$repositoryId}）が見つかりません。");
                return 1;
            }
        } else {
            $repositories = GitHubRepository::all();
        }

        if ($repositories->isEmpty()) {
            $this->warn('処理対象のリポジトリが見つかりません。');
            return 0;
        }

        $totalInserted = 0;
        $isDryRun = $this->option('dry-run');

        foreach ($repositories as $repository) {
            $this->line("処理中: {$repository->display_name} ({$repository->full_name})");
            
            $insertedCount = $this->fillMissingDatesForRepository($repository, $isDryRun);
            $totalInserted += $insertedCount;
            
            if ($isDryRun) {
                $this->info("  → 補完対象: {$insertedCount}件");
            } else {
                $this->info("  ✓ 補完完了: {$insertedCount}件");
            }
        }

        if ($isDryRun) {
            $this->info("ドライラン完了: 補完対象 {$totalInserted}件");
            $this->line('実際に補完するには --dry-run オプションを外して実行してください。');
        } else {
            $this->info("補完処理完了: 総補完 {$totalInserted}件");
        }

        return 0;
    }

    /**
     * 個別リポジトリの欠損日付を補完
     */
    private function fillMissingDatesForRepository(GitHubRepository $repository, bool $isDryRun): int
    {
        // 期間を決定
        $startDate = $this->option('start-date') 
            ? Carbon::parse($this->option('start-date'))
            : $this->getRepositoryFirstDate($repository);
            
        $endDate = $this->option('end-date')
            ? Carbon::parse($this->option('end-date'))
            : now()->subDay(); // 昨日まで

        if (!$startDate) {
            $this->warn("  リポジトリ {$repository->full_name} にはデータが存在しません。スキップします。");
            return 0;
        }

        // 既存のデータ日付を取得
        $existingDates = GitHubView::where('repository_id', $repository->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        $insertedCount = 0;
        $currentDate = $startDate->copy();

        // 日付範囲をループして欠損日を特定
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            if (!in_array($dateString, $existingDates)) {
                if ($isDryRun) {
                    $this->line("    補完対象日: {$dateString}");
                } else {
                    // 欠損日を0件データとして登録
                    GitHubView::create([
                        'repository_id' => $repository->id,
                        'project' => $repository->full_name,
                        'date' => $dateString,
                        'count' => 0,
                        'uniques' => 0
                    ]);
                }
                $insertedCount++;
            }

            $currentDate->addDay();
        }

        return $insertedCount;
    }

    /**
     * リポジトリの最初のデータ日付を取得
     */
    private function getRepositoryFirstDate(GitHubRepository $repository): ?Carbon
    {
        $firstRecord = GitHubView::where('repository_id', $repository->id)
            ->orderBy('date', 'asc')
            ->first();

        return $firstRecord ? Carbon::parse($firstRecord->date) : null;
    }
}