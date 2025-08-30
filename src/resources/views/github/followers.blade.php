<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHubフォロワー統計システム</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            min-height: 100vh;
            color: #24292e;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            padding: 0;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 16px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .header-nav {
            background: #ffffff;
            color: #24292e;
            padding: 16px 24px;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .header-nav h1 {
            color: #24292e;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 8px;
        }
        
        .nav-link {
            color: #24292e;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            background: #ffffff;
        }
        
        .nav-link:hover {
            background: #f6f8fa;
            text-decoration: none;
        }
        
        .nav-link.active {
            background: #0366d6;
            color: #ffffff;
            border-color: #0366d6;
        }
        
        .main-content {
            padding: 24px;
        }
        
        .filters {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .filter-group {
            display: inline-block;
            margin-right: 25px;
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: #24292e;
            font-size: 14px;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            font-size: 14px;
            background: #ffffff;
            min-width: 160px;
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #0366d6;
            box-shadow: 0 0 0 3px rgba(3, 102, 214, 0.1);
        }
        
        .btn {
            background: #ffffff;
            color: #24292e;
            padding: 8px 16px;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn:hover {
            background: #f6f8fa;
        }
        
        .btn:active {
            background: #edeff2;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: #ffffff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #e1e4e8;
            text-align: left;
        }
        
        .stat-card:hover {
            background: #f6f8fa;
        }
        
        .stat-number {
            font-size: 1.75rem;
            font-weight: 600;
            color: #24292e;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #586069;
            font-weight: 400;
            font-size: 14px;
        }
        
        .growth-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .growth-positive {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
        }
        
        .growth-negative {
            background: rgba(245, 101, 101, 0.1);
            color: #e53e3e;
        }
        
        .chart-container {
            background: #ffffff;
            padding: 24px;
            border-radius: 6px;
            border: 1px solid #e1e4e8;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .chart-container h3 {
            color: #24292e;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: left;
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 16px;
            background: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e1e4e8;
        }
        
        .data-table th, .data-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .data-table th {
            background: #f6f8fa;
            font-weight: 600;
            color: #24292e;
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: #f6f8fa;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .pagination {
            margin-top: 30px;
            text-align: center;
        }
        
        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            color: #0366d6;
            border: 1px solid #e1e4e8;
            margin: 0 2px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: #f6f8fa;
            text-decoration: none;
        }
        
        .pagination .active {
            background: #0366d6;
            color: white;
            border-color: #0366d6;
        }
        
        /* ページネーションのSVGアイコンサイズを強制的に小さく */
        .pagination svg {
            width: 12px !important;
            height: 12px !important;
        }
        
        .no-data-message {
            text-align: center;
            padding: 60px 40px;
            color: #718096;
        }
        
        .no-data-message h4 {
            margin-bottom: 15px;
            color: #4a5568;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .no-data-message p {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header-nav {
                padding: 20px;
                flex-direction: column;
                gap: 20px;
            }
            
            .header-nav h1 {
                font-size: 2rem;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .filter-group {
                display: block;
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .filter-group input, .filter-group select {
                min-width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h1>GitHubフォロワー統計システム</h1>
            <div class="nav-links">
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="nav-link">管理画面</a>
                @endauth
            </div>
        </div>
        
        <div class="main-content">
            <!-- フィルター -->
            <div class="filters">
                <form method="GET" action="{{ route('github.followers') }}" id="filterForm">
                    <div class="filter-group">
                        <label for="username">ユーザー名:</label>
                        <select name="username" id="username">
                            <option value="">すべて</option>
                            @foreach($usernames as $username)
                                <option value="{{ $username }}" {{ request('username') == $username ? 'selected' : '' }}>
                                    {{ $username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="start_date">開始日:</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">終了日:</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}">
                    </div>
                    
                    <button type="submit" class="btn">フィルター適用</button>
                    <button type="button" class="btn" onclick="resetFilters()">リセット</button>
                </form>
            </div>
            
            <!-- 統計情報 -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-number" id="maxFollowers">
                        {{ $stats && $stats->max_followers ? number_format($stats->max_followers) : '-' }}
                    </div>
                    <div class="stat-label">最大フォロワー数</div>
                    @if($growthRate !== null)
                        <div class="growth-indicator {{ $growthRate >= 0 ? 'growth-positive' : 'growth-negative' }}">
                            {{ $growthRate >= 0 ? '+' : '' }}{{ number_format($growthRate, 1) }}% (30日)
                        </div>
                    @endif
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="avgFollowers">
                        {{ $stats && $stats->avg_followers ? number_format($stats->avg_followers) : '-' }}
                    </div>
                    <div class="stat-label">平均フォロワー数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="maxFollowing">
                        {{ $stats && $stats->max_following ? number_format($stats->max_following) : '-' }}
                    </div>
                    <div class="stat-label">最大フォロー数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="maxRepos">
                        {{ $stats && $stats->max_repos ? number_format($stats->max_repos) : '-' }}
                    </div>
                    <div class="stat-label">最大リポジトリ数</div>
                </div>
            </div>
            
            <!-- チャート -->
            <div class="chart-container">
                <h3>フォロワー数推移
                    @if(request('start_date') || request('end_date'))
                        <small style="font-size: 0.8em; color: #666;">
                            ({{ request('start_date') ?: '開始日なし' }} ～ {{ request('end_date') ?: '終了日なし' }})
                        </small>
                    @endif
                </h3>
                @if($chartData && count($chartData) > 0)
                    <canvas id="followersChart" width="400" height="200"></canvas>
                @else
                    <div class="no-data-message">
                        <h4>データが見つかりません</h4>
                        <p>選択された条件に一致するデータがありません。</p>
                        <p>フィルター条件を変更するか、日付範囲を調整してください。</p>
                    </div>
                @endif
            </div>
            
            <!-- データテーブル -->
            <h3>フォロワー統計データ</h3>
            @if($followers->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ユーザー名</th>
                            <th>日付</th>
                            <th>フォロワー数</th>
                            <th>フォロー数</th>
                            <th>リポジトリ数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($followers as $follower)
                        <tr>
                            <td>{{ $follower->username }}</td>
                            <td>{{ $follower->date->format('Y-m-d') }}</td>
                            <td>{{ number_format($follower->followers_count) }}</td>
                            <td>{{ number_format($follower->following_count) }}</td>
                            <td>{{ number_format($follower->public_repos) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- ページネーション -->
                <div class="pagination">
                    {{ $followers->links() }}
                </div>
            @else
                <div class="no-data-message">
                    <h4>データが見つかりません</h4>
                    <p>選択された条件に一致するデータがありません。</p>
                    <p>フィルター条件を変更するか、日付範囲を調整してください。</p>
                </div>
            @endif
        </div>
    </div>

    @if($chartData && count($chartData) > 0)
    <script>
        // チャートデータが存在する場合のみChart.jsを初期化
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('followersChart');
            if (ctx) {
                const chartData = JSON.parse('@json($chartData)');
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(function(item) { return item.date; }),
                        datasets: [
                            {
                                label: 'フォロワー数',
                                data: chartData.map(function(item) { return item.followers_count; }),
                                borderColor: '#48bb78',
                                backgroundColor: 'rgba(72, 187, 120, 0.1)',
                                tension: 0.1
                            },
                            {
                                label: 'フォロー数',
                                data: chartData.map(function(item) { return item.following_count; }),
                                borderColor: '#667eea',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.1
                            },
                            {
                                label: 'リポジトリ数',
                                data: chartData.map(function(item) { return item.public_repos; }),
                                borderColor: '#ed8936',
                                backgroundColor: 'rgba(237, 137, 54, 0.1)',
                                tension: 0.1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endif
    
    <script>
        // フィルターリセット
        function resetFilters() {
            document.getElementById('username').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>
