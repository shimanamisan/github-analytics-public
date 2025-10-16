<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubRepository extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     */
    protected $table = 'github_repositories';

    protected $fillable = [
        'user_id',
        'owner',
        'repo',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * GitHubViewsとの関連
     */
    public function gitHubViews(): HasMany
    {
        return $this->hasMany(GitHubView::class, 'repository_id');
    }

    /**
     * ユーザーとの関連
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * アクティブなリポジトリのみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 特定のユーザーのリポジトリのみ取得
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * フルネーム（owner/repo）を取得
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->owner}/{$this->repo}";
    }

    /**
     * 表示名を取得（設定されていない場合はフルネームを返す）
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->full_name;
    }

    /**
     * 使用するGitHubトークンを取得
     * リポジトリの所有者のトークンを使用
     */
    public function getTokenAttribute(): ?string
    {
        return $this->user ? $this->user->getGitHubToken() : null;
    }
}
