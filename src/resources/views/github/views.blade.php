<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub訪問数集計システム</title>
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
        
        .nav-link.admin {
            background: #28a745;
            color: #ffffff;
            border-color: #28a745;
        }
        
        .nav-link.admin:hover {
            background: #218838;
            border-color: #218838;
        }
        
        .nav-link.active {
            background: #0366d6;
            color: #ffffff;
            border-color: #0366d6;
        }
        
        .nav-link.logout-btn {
            background: #dc3545;
            border: 1px solid #dc3545;
            color: white;
            cursor: pointer;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .nav-link.logout-btn:hover {
            background: #c82333;
            border-color: #c82333;
        }
        
        .main-content {
            padding: 24px;
        }
        
        .filters {
            background: #f6f8fa;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            border: 1px solid #e1e4e8;
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
            <h1>GitHub訪問数集計システム</h1>
            <div class="nav-links">
                <a href="{{ route('home') }}" class="nav-link active">訪問数</a>
                <a href="{{ route('github.followers') }}" class="nav-link">フォロワー統計</a>
                <a href="{{ route('github.follower-details') }}" class="nav-link">フォロワー詳細</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="nav-link admin">管理画面</a>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="nav-link logout-btn">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link">ログイン</a>
                @endauth
            </div>
        </div>
        
        <div class="main-content">
            <!-- フィルター -->
        <div class="filters">
            <form method="GET" action="{{ route('home') }}" id="filterForm">
                <div class="filter-group">
                    <label for="project">プロジェクト:</label>
                    <select name="project" id="project">
                        <option value="">すべて</option>
                        @foreach($projects as $project)
                            <option value="{{ $project }}" {{ request('project') == $project ? 'selected' : '' }}>
                                {{ $project }}
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
                <div class="stat-number" id="totalViews">
                    {{ $stats && $stats->total_views ? number_format($stats->total_views) : '-' }}
                </div>
                <div class="stat-label">総訪問数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalUniques">
                    {{ $stats && $stats->total_uniques ? number_format($stats->total_uniques) : '-' }}
                </div>
                <div class="stat-label">総ユニーク訪問者</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="avgViews">
                    {{ $stats && $stats->avg_views ? round($stats->avg_views) : '-' }}
                </div>
                <div class="stat-label">平均訪問数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="avgUniques">
                    {{ $stats && $stats->avg_uniques ? round($stats->avg_uniques) : '-' }}
                </div>
                <div class="stat-label">平均ユニーク訪問者</div>
            </div>
        </div>
        
        <!-- チャート -->
        <div class="chart-container">
            <h3>訪問数推移
                @if(request('start_date') || request('end_date'))
                    <small style="font-size: 0.8em; color: #666;">
                        ({{ request('start_date') ?: '開始日なし' }} ～ {{ request('end_date') ?: '終了日なし' }})
                    </small>
                @endif
            </h3>
            @if($chartData && count($chartData) > 0)
                <canvas id="viewsChart" width="400" height="200"></canvas>
            @else
                <div class="no-data-message">
                    <h4>データが見つかりません</h4>
                    <p>選択された条件に一致するデータがありません。</p>
                    <p>フィルター条件を変更するか、日付範囲を調整してください。</p>
                </div>
            @endif
        </div>
        
        <!-- データテーブル -->
        <h3>訪問数データ</h3>
        @if($views->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>プロジェクト</th>
                        @if(!request('project') && !request('repository_id'))
                            <th>期間</th>
                            <th>記録数</th>
                        @else
                            <th>日付</th>
                        @endif
                        <th>訪問数</th>
                        <th>ユニーク訪問者</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($views as $view)
                    <tr>
                        <td>
                            @if(!request('project') && !request('repository_id') && $view->repository)
                                {{ $view->repository->display_name ?? $view->project }}
                            @else
                                {{ $view->project }}
                            @endif
                        </td>
                        @if(!request('project') && !request('repository_id'))
                            <td>{{ $view->first_date->format('Y-m-d') }} ～ {{ $view->last_date->format('Y-m-d') }}</td>
                            <td>{{ number_format($view->record_count) }}</td>
                        @else
                            <td>{{ $view->date->format('Y-m-d') }}</td>
                        @endif
                        <td>{{ number_format($view->count) }}</td>
                        <td>{{ number_format($view->uniques) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- ページネーション -->
            <div class="pagination">
                {{ $views->links() }}
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
            const ctx = document.getElementById('viewsChart');
            if (ctx) {
                const chartData = JSON.parse('@json($chartData)');
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(function(item) { return item.date; }),
                        datasets: [
                            {
                                label: '訪問数',
                                data: chartData.map(function(item) { return item.count; }),
                                borderColor: '#007bff',
                                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                tension: 0.1
                            },
                            {
                                label: 'ユニーク訪問者',
                                data: chartData.map(function(item) { return item.uniques; }),
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
            document.getElementById('project').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>
