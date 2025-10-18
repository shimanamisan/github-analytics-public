<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubFollowerDetail extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     */
    protected $table = 'github_follower_details';

    protected $fillable = [
        'user_id',
        'target_username',
        'follower_username',
        'follower_name',
        'follower_avatar_url',
        'follower_bio',
        'follower_public_repos',
        'follower_followers',
        'follower_following',
        'followed_at',
        'unfollowed_at',
        'is_active'
    ];

    protected $casts = [
        'follower_public_repos' => 'integer',
        'follower_followers' => 'integer',
        'follower_following' => 'integer',
        'followed_at' => 'datetime',
        'unfollowed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * ユーザーとの関連
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * フォロワー統計との関連
     */
    public function githubFollower(): BelongsTo
    {
        return $this->belongsTo(GitHubFollower::class, 'target_username', 'username');
    }

    /**
     * アクティブなフォロワーのみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 特定のシステムユーザーのフォロワーを取得
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 特定のGitHubユーザー名のフォロワーを取得
     */
    public function scopeForGitHubUsername($query, string $username)
    {
        return $query->where('target_username', $username);
    }

    /**
     * 最近フォローされたフォロワーを取得
     */
    public function scopeRecentFollowers($query, int $days = 7)
    {
        return $query->where('followed_at', '>=', now()->subDays($days))
                    ->active()
                    ->orderBy('followed_at', 'desc');
    }


    /**
     * 特定の期間にフォローされたユーザー数を取得
     */
    public static function getNewFollowersCount(string $username, int $days = 7): int
    {
        return static::forUser($username)
                    ->recentFollowers($days)
                    ->count();
    }

    /**
     * 特定の期間にフォロー解除されたユーザーを取得
     */
    public function scopeRecentlyUnfollowed($query, int $days = 7)
    {
        return $query->where('unfollowed_at', '>=', now()->subDays($days))
                    ->where('is_active', false)
                    ->orderBy('unfollowed_at', 'desc');
    }

    /**
     * 特定の期間にフォロー解除されたユーザー数を取得
     */
    public static function getUnfollowedCount(string $username, int $days = 7): int
    {
        return static::forUser($username)
                    ->recentlyUnfollowed($days)
                    ->count();
    }

    /**
     * フォロワー解除をマーク
     */
    public function markAsUnfollowed(): void
    {
        $this->update([
            'is_active' => false,
            'unfollowed_at' => now()
        ]);
    }

}
