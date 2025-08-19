<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubRepository extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     */
    protected $table = 'github_repositories';

    protected $fillable = [
        'owner',
        'repo',
        'name',
        'description',
        'is_active',
        'github_token'
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
     * アクティブなリポジトリのみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * リポジトリ専用トークンがない場合は、環境設定のトークンを使用
     */
    public function getTokenAttribute(): string
    {
        return $this->github_token ?: config('services.github.token');
    }
}
