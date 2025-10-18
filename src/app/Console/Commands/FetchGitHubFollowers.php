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

        // ユーザー名の取得（オプション指定または全ユーザー処理）
        $username = $this->option('user');
        
        if ($username) {
            // 特定のユーザー名が指定された場合、そのユーザーのみ処理
            $user = \App\Models\User::where('github_owner', $username)->first();
            if (!$user || !$user->hasGitHubSettings()) {
                $this->error("GitHub設定が完了していないユーザーです: {$username}");
                return 1;
            }
            
            return $this->processUser($user, $githubLogger);
        }
        
        // ユーザー名が指定されていない場合、全てのGitHub設定完了ユーザーを処理
        $users = \App\Models\User::where('github_settings_completed', true)
            ->whereNotNull('github_token')
            ->whereNotNull('github_owner')
            ->get();
        
        if ($users->isEmpty()) {
            $this->error('GitHub設定が完了しているユーザーが見つかりません。');
            return 1;
        }
        
        $this->info("処理対象ユーザー数: {$users->count()}人");
        $githubLogger->info("処理対象ユーザー数: {$users->count()}人");
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            $this->info("----------------------------------------");
            $this->info("処理中: {$user->name} (ID: {$user->id}, GitHub: {$user->github_owner})");
            
            $result = $this->processUser($user, $githubLogger);
            
            if ($result === 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
            
            // API Rate Limitを考慮して少し待機
            if (!$this->option('test')) {
                sleep(2);
            }
        }
        
        $this->info("----------------------------------------");
        $this->info("全ユーザーの処理が完了しました。");
        $this->info("成功: {$successCount}人, エラー: {$errorCount}人");
        
        $githubLogger->info('全ユーザーのGitHubフォロワー情報取得完了', [
            'total_users' => $users->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'detailed_fetch' => $this->option('detailed'),
            'is_test' => $this->option('test')
        ]);
        
        return $errorCount > 0 ? 1 : 0;
    }
    
    /**
     * 個別ユーザーの処理
     */
    private function processUser(\App\Models\User $user, $githubLogger): int
    {
        $username = $user->github_owner;
        
        $token = $user->getGitHubToken();
        if (!$token) {
            $this->error("  ✗ GitHubトークンが取得できません: {$username}");
            $githubLogger->error("GitHubトークンが取得できません", ['user_id' => $user->id, 'username' => $username]);
            return 1;
        }

        try {
            // 基本統計情報を取得
            $githubLogger->info("ユーザー統計情報を取得中", ['user_id' => $user->id, 'username' => $username]);
            $this->info("  統計情報を取得中...");
            $userStats = $this->fetchUserStats($username, $token);
            
            // 基本統計をデータベースに保存
            $this->saveUserStats($username, $userStats, $user->id);
            $this->info("  ✓ 基本統計情報を保存しました (フォロワー: {$userStats['followers']}人)");
            $githubLogger->info("基本統計情報を保存", [
                'user_id' => $user->id, 
                'username' => $username,
                'followers' => $userStats['followers']
            ]);

            // 詳細フォロワー情報を取得（オプション）
            if ($this->option('detailed')) {
                $this->info("  詳細フォロワー情報を取得中...");
                $githubLogger->info("詳細フォロワー情報を取得開始", ['user_id' => $user->id, 'username' => $username]);
                $followerCount = $this->fetchDetailedFollowers($username, $token, $user->id);
                $this->info("  ✓ 詳細フォロワー情報を取得しました: {$followerCount}人");
                $githubLogger->info("詳細フォロワー情報を取得完了", [
                    'user_id' => $user->id,
                    'username' => $username,
                    'follower_count' => $followerCount
                ]);
            }
            
            // 統計ログを記録
            Log::info('GitHubフォロワー情報取得完了', [
                'user_id' => $user->id,
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
            $githubLogger->error($errorMsg, [
                'user_id' => $user->id,
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            $this->error("  ✗ {$errorMsg}");
            
            Log::error('GitHubフォロワー情報取得エラー', [
                'user_id' => $user->id,
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
    private function saveUserStats(string $username, array $stats, int $userId): void
    {
        $today = Carbon::today()->format('Y-m-d');

        GitHubFollower::updateOrCreate(
            [
                'user_id' => $userId,
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
    private function fetchDetailedFollowers(string $username, string $token, int $userId): int
    {
        // カスタムログチャンネルを使用
        $githubLogger = Log::channel('github-commands');
        
        $page = 1;
        $perPage = 100;
        $totalProcessed = 0;
        $newFollowers = 0;
        $updatedFollowers = 0;

        // 既存のアクティブフォロワーリストを取得
        $existingFollowers = GitHubFollowerDetail::forUser($userId)
            ->forGitHubUsername($username)
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
                        'user_id' => $userId,
                        'target_username' => $username,
                        'follower_username' => $followerUsername
                    ],
                    [
                        'follower_name' => $followerDetails['name'],
                        'follower_avatar_url' => $followerDetails['avatar_url'],
                        'follower_bio' => $followerDetails['bio'],
                        'follower_public_repos' => $followerDetails['public_repos'],
                        'follower_followers' => $followerDetails['followers'],
                        'follower_following' => $followerDetails['following'],
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
            GitHubFollowerDetail::forUser($userId)
                ->forGitHubUsername($username)
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
                'followers' => 0,
                'following' => 0
            ];
        }

        $data = $response->json();

        return [
            'name' => $data['name'],
            'avatar_url' => $data['avatar_url'],
            'bio' => $data['bio'],
            'public_repos' => $data['public_repos'] ?? 0,
            'followers' => $data['followers'] ?? 0,
            'following' => $data['following'] ?? 0
        ];
    }
}
