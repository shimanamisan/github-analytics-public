<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubView extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     */
    protected $table = 'github_views';

    protected $fillable = [
        'repository_id',
        'project',
        'date',
        'count',
        'uniques'
    ];
    
    protected $casts = [
        'date' => 'date',
        'count' => 'integer',
        'uniques' => 'integer',
    ];

    /**
     * GitHubRepositoryとの関連
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class);
    }

    /**
     * プロジェクト名でフィルタリング
     */
    public function scopeForProject($query, $project)
    {
        return $query->where('project', $project);
    }

    /**
     * リポジトリIDでフィルタリング
     */
    public function scopeForRepository($query, $repositoryId)
    {
        return $query->where('repository_id', $repositoryId);
    }

    /**
     * 日付範囲でフィルタリング
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * リポジトリ情報を含む結果を取得
     */
    public function scopeWithRepository($query)
    {
        return $query->with('repository');
    }
}
