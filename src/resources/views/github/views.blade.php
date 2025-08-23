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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 0;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            margin-top: 20px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .header-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .header-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
        
        .header-nav h1 {
            color: white;
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .nav-links {
            display: inline;
            position: relative;
            z-index: 1;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .nav-link.admin {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border-color: #48bb78;
            display: inline;
            margin-right: 20px;
        }
        
        .nav-link.admin:hover {
            background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
            transform: translateY(-2px);
        }
        
        .nav-link.logout-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            cursor: pointer;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        
        .nav-link.logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .main-content {
            padding: 40px;
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
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group input, .filter-group select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            min-width: 200px;
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stat-label {
            color: #718096;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .chart-container h3 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .data-table th, .data-table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table th {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }
        
        .data-table tr:hover {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
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
            padding: 12px 20px;
            text-decoration: none;
            color: #667eea;
            border: 2px solid #e2e8f0;
            margin: 0 4px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
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
