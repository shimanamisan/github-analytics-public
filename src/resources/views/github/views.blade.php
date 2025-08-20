<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub訪問数集計システム</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>GitHub訪問数集計システム</h1>
        
        <!-- フィルター -->
        <div class="filters">
                            <form method="GET" action="{{ \App\Helpers\UrlHelper::githubUrl('views') }}" id="filterForm">
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
                <div class="stat-number" id="totalViews">-</div>
                <div class="stat-label">総訪問数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalUniques">-</div>
                <div class="stat-label">総ユニーク訪問者</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="avgViews">-</div>
                <div class="stat-label">平均訪問数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="avgUniques">-</div>
                <div class="stat-label">平均ユニーク訪問者</div>
            </div>
        </div>
        
        <!-- チャート -->
        <div class="chart-container">
            <h3>訪問数推移</h3>
            <canvas id="viewsChart" width="400" height="200"></canvas>
        </div>
        
        <!-- データテーブル -->
        <h3>訪問数データ</h3>
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
    </div>

    <script>
        let viewsChart;
        
        // ページ読み込み時にデータを取得
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadChart();
        });
        
        // 統計情報を読み込み
        function loadStats() {
            const params = new URLSearchParams(window.location.search);
                            fetch(`{{ \App\Helpers\UrlHelper::githubUrl('stats') }}?${params}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalViews').textContent = data.total_views ? numberFormat(data.total_views) : '-';
                    document.getElementById('totalUniques').textContent = data.total_uniques ? numberFormat(data.total_uniques) : '-';
                    document.getElementById('avgViews').textContent = data.avg_views ? Math.round(data.avg_views) : '-';
                    document.getElementById('avgUniques').textContent = data.avg_uniques ? Math.round(data.avg_uniques) : '-';
                })
                .catch(error => console.error('統計情報の取得に失敗しました:', error));
        }
        
        // チャートデータを読み込み
        function loadChart() {
            const params = new URLSearchParams(window.location.search);
                            fetch(`{{ \App\Helpers\UrlHelper::githubUrl('chart') }}?${params}`)
                .then(response => response.json())
                .then(data => {
                    createChart(data);
                })
                .catch(error => console.error('チャートデータの取得に失敗しました:', error));
        }
        
        // チャートを作成
        function createChart(data) {
            const ctx = document.getElementById('viewsChart').getContext('2d');
            
            if (viewsChart) {
                viewsChart.destroy();
            }
            
            const chartData = {
                labels: data.map(item => item.date),
                datasets: [
                    {
                        label: '訪問数',
                        data: data.map(item => item.count),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'ユニーク訪問者',
                        data: data.map(item => item.uniques),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.1
                    }
                ]
            };
            
            viewsChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
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
        
        // フィルターリセット
        function resetFilters() {
            document.getElementById('project').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('filterForm').submit();
        }
        
        // 数値フォーマット
        function numberFormat(num) {
            return new Intl.NumberFormat('ja-JP').format(num);
        }
        
        // フィルター変更時にチャートを更新
        document.getElementById('filterForm').addEventListener('submit', function() {
            setTimeout(() => {
                loadStats();
                loadChart();
            }, 100);
        });
    </script>
</body>
</html>
