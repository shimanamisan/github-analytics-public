<?php

namespace App\Console\Commands;

use App\Models\GitHubFollower;
use App\Models\GitHubFollowerDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGitHubFollowers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:fetch-followers {--user= : GitHubユーザー名を指定} {--detailed : 詳細フォロワー情報も取得} {--test : テスト実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GitHubフォロワー情報を取得します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // カスタムログチャンネルを使用
        $githubLogger = Log::channel('github-commands');
        
        $githubLogger->info('GitHubフォロワー情報の取得を開始します...');
        $this->info('GitHubフォロワー情報の取得を開始します...');

        // ユーザー名の取得（GITHUB_USERNAMEまたはGITHUB_OWNERから取得）
        $username = $this->option('user') ?: (config('services.github.username') ?: config('services.github.owner'));
        
        if (!$username) {
            $this->error('GitHubユーザー名が指定されていません。--userオプションで指定するか、GITHUB_USERNAMEまたはGITHUB_OWNERを設定してください。');
            return 1;
        }

        $token = config('services.github.token');
        if (!$token) {
            $this->error('GitHubトークンが設定されていません。');
            return 1;
        }

        try {
            // 基本統計情報を取得
            $githubLogger->info("ユーザー統計情報を取得中: {$username}");
            $this->info("ユーザー統計情報を取得中: {$username}");
            $userStats = $this->fetchUserStats($username, $token);
            
            // 基本統計をデータベースに保存
            $this->saveUserStats($username, $userStats);
            $githubLogger->info("✓ 基本統計情報を保存しました");
            $this->info("✓ 基本統計情報を保存しました");

            // 詳細フォロワー情報を取得（オプション）
            if ($this->option('detailed')) {
                $githubLogger->info("詳細フォロワー情報を取得中...");
                $this->info("詳細フォロワー情報を取得中...");
                $followerCount = $this->fetchDetailedFollowers($username, $token);
                $githubLogger->info("✓ 詳細フォロワー情報を取得しました: {$followerCount}人");
                $this->info("✓ 詳細フォロワー情報を取得しました: {$followerCount}人");
            }

            $githubLogger->info('フォロワー情報の取得が完了しました。');
            $this->info('フォロワー情報の取得が完了しました。');
            
            // 統計ログを記録
            Log::info('GitHubフォロワー情報取得完了', [
                'username' => $username,
                'followers_count' => $userStats['followers'],
                'following_count' => $userStats['following'],
                'public_repos' => $userStats['public_repos'],
                'detailed_fetch' => $this->option('detailed'),
                'is_test' => $this->option('test')
            ]);

            return 0;

        } catch (Exception $e) {
            $errorMsg = "エラーが発生しました: {$e->getMessage()}";
            $githubLogger->error($errorMsg);
            $this->error($errorMsg);
            
            Log::error('GitHubフォロワー情報取得エラー', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * ユーザーの基本統計情報を取得
     */
    private function fetchUserStats(string $username, string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get("https://api.github.com/users/{$username}");

        if (!$response->successful()) {
            throw new Exception("GitHub API エラー（ユーザー情報）: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        return [
            'followers' => $data['followers'] ?? 0,
            'following' => $data['following'] ?? 0,
            'public_repos' => $data['public_repos'] ?? 0,
        ];
    }

    /**
     * ユーザー統計をデータベースに保存
     */
    private function saveUserStats(string $username, array $stats): void
    {
        $today = Carbon::today()->format('Y-m-d');

        GitHubFollower::updateOrCreate(
            [
                'username' => $username,
                'date' => $today
            ],
            [
                'followers_count' => $stats['followers'],
                'following_count' => $stats['following'],
                'public_repos' => $stats['public_repos']
            ]
        );
    }

    /**
     * 詳細フォロワー情報を取得
     */
    private function fetchDetailedFollowers(string $username, string $token): int
    {
        // カスタムログチャンネルを使用
        $githubLogger = Log::channel('github-commands');
        
        $page = 1;
        $perPage = 100;
        $totalProcessed = 0;
        $newFollowers = 0;
        $updatedFollowers = 0;

        // 既存のアクティブフォロワーリストを取得
        $existingFollowers = GitHubFollowerDetail::forUser($username)
            ->active()
            ->pluck('follower_username')
            ->toArray();

        $currentFollowers = [];

        do {
            $githubLogger->info("フォロワーページ {$page} を取得中...");
            $this->info("フォロワーページ {$page} を取得中...");
            
            $response = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json',
            ])->get("https://api.github.com/users/{$username}/followers", [
                'page' => $page,
                'per_page' => $perPage
            ]);

            if (!$response->successful()) {
                throw new Exception("GitHub API エラー（フォロワー取得）: {$response->status()} - {$response->body()}");
            }

            $followers = $response->json();
            
            if (empty($followers)) {
                break;
            }

            foreach ($followers as $follower) {
                $followerUsername = $follower['login'];
                $currentFollowers[] = $followerUsername;

                // 詳細情報を取得
                $followerDetails = $this->fetchFollowerDetails($followerUsername, $token);
                
                // データベースに保存
                $result = GitHubFollowerDetail::updateOrCreate(
                    [
                        'target_username' => $username,
                        'follower_username' => $followerUsername
                    ],
                    [
                        'follower_name' => $followerDetails['name'],
                        'follower_avatar_url' => $followerDetails['avatar_url'],
                        'follower_bio' => $followerDetails['bio'],
                        'follower_public_repos' => $followerDetails['public_repos'],
                        'follower_followers' => $followerDetails['followers'],
                        'is_active' => true
                    ]
                );

                // 新規フォロワーの場合のみfollowed_atを設定
                if ($result->wasRecentlyCreated) {
                    $result->update(['followed_at' => now()]);
                }

                if ($result->wasRecentlyCreated) {
                    $newFollowers++;
                } else {
                    $updatedFollowers++;
                }

                $totalProcessed++;

                // API Rate Limitを考慮して1秒待機
                sleep(1);
            }

            $page++;
            
        } while (count($followers) == $perPage);

        // フォロー解除されたユーザーを非アクティブにマーク
        $unfollowedUsers = array_diff($existingFollowers, $currentFollowers);
        if (!empty($unfollowedUsers)) {
            GitHubFollowerDetail::forUser($username)
                ->whereIn('follower_username', $unfollowedUsers)
                ->update([
                    'is_active' => false,
                    'unfollowed_at' => now()
                ]);
                
            $unfollowedMsg = "✓ フォロー解除されたユーザーを非アクティブにしました: " . count($unfollowedUsers) . "人";
            $githubLogger->info($unfollowedMsg);
            $this->info($unfollowedMsg);
        }

        $completionMsg = "詳細フォロワー情報処理完了: 新規 {$newFollowers}人, 更新 {$updatedFollowers}人";
        $githubLogger->info($completionMsg);
        $this->info($completionMsg);

        return $totalProcessed;
    }

    /**
     * 個別フォロワーの詳細情報を取得
     */
    private function fetchFollowerDetails(string $username, string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get("https://api.github.com/users/{$username}");

        if (!$response->successful()) {
            // エラーの場合はデフォルト値を返す
            return [
                'name' => null,
                'avatar_url' => null,
                'bio' => null,
                'public_repos' => 0,
                'followers' => 0
            ];
        }

        $data = $response->json();

        return [
            'name' => $data['name'],
            'avatar_url' => $data['avatar_url'],
            'bio' => $data['bio'],
            'public_repos' => $data['public_repos'] ?? 0,
            'followers' => $data['followers'] ?? 0
        ];
    }
}
