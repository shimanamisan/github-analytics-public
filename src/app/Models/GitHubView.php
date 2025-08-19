<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GitHubView extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     */
    protected $table = 'github_views';

    protected $fillable = ['project', 'date', 'count', 'uniques'];
    
    protected $casts = [
        'date' => 'date',
        'count' => 'integer',
        'uniques' => 'integer',
    ];

    /**
     * プロジェクト名でフィルタリング
     */
    public function scopeForProject($query, $project)
    {
        return $query->where('project', $project);
    }

    /**
     * 日付範囲でフィルタリング
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
