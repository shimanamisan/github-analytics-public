<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubFollower extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     */
    protected $table = 'github_followers';

    protected $fillable = [
        'username',
        'date',
        'followers_count',
        'following_count',
        'public_repos'
    ];

    protected $casts = [
        'date' => 'date',
        'followers_count' => 'integer',
        'following_count' => 'integer',
        'public_repos' => 'integer',
    ];

    /**
     * フォロワー詳細情報との関連
     */
    public function followerDetails(): HasMany
    {
        return $this->hasMany(GitHubFollowerDetail::class, 'target_username', 'username');
    }

    /**
     * 特定のユーザーのフォロワー統計を取得
     */
    public function scopeForUser($query, string $username)
    {
        return $query->where('username', $username);
    }

    /**
     * 特定の期間のデータを取得
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * 最新のフォロワー数を取得
     */
    public static function getLatestFollowerCount(string $username): ?int
    {
        $latest = static::forUser($username)->latest('date')->first();
        return $latest ? $latest->followers_count : null;
    }

    /**
     * フォロワー数の成長率を計算
     */
    public function getGrowthRate(int $days = 30): ?float
    {
        $currentData = static::forUser($this->username)
            ->latest('date')
            ->first();

        $pastData = static::forUser($this->username)
            ->where('date', '<=', now()->subDays($days))
            ->latest('date')
            ->first();

        if (!$currentData || !$pastData || $pastData->followers_count == 0) {
            return null;
        }

        return (($currentData->followers_count - $pastData->followers_count) / $pastData->followers_count) * 100;
    }
}
