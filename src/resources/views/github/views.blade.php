<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub訪問数集計システム</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        .filter-group {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            margin-bottom: 30px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            color: #007bff;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        .pagination .active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .no-data-message h4 {
            margin-bottom: 10px;
            color: #333;
        }
        .no-data-message p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>GitHub訪問数集計システム</h1>
        
        <!-- フィルター -->
        <div class="filters">
            <form method="GET" action="{{ route('github.views') }}" id="filterForm">
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
            <h3>訪問数推移</h3>
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
                        <th>日付</th>
                        <th>訪問数</th>
                        <th>ユニーク訪問者</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($views as $view)
                    <tr>
                        <td>{{ $view->project }}</td>
                        <td>{{ $view->date->format('Y-m-d') }}</td>
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
