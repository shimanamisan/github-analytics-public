<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub訪問数集計システム</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9fafb;
            min-height: 100vh;
            color: #111827;
        }
        
    </style>
</head>
<body>
    <div class="min-h-screen bg-gray-100">
        <!-- ナビゲーションバー -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-900">GitHub訪問数集計システム</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8 sm:items-center">
                            <!-- ナビゲーションリンクは削除 -->
                        </div>
                    </div>
                    @auth
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <div class="relative group">
                                    <button type="button" class="flex items-center text-sm text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <span>{{ Auth::user()->name ?? Auth::user()->email }}</span>
                                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- ドロップダウンメニュー -->
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                        <a href="{{ route('admin.dashboard') }}" 
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            管理画面
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}" class="block">
                                            @csrf
                                            <button type="submit" 
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                ログアウト
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- メインコンテンツ -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
            @auth
            <!-- フィルター -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">フィルター</h3>
                    <form method="GET" action="{{ route('home') }}" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="project" class="block text-sm font-medium text-gray-700 mb-2">プロジェクト</label>
                            <select name="project" id="project" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">すべて</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project }}" {{ request('project') == $project ? 'selected' : '' }}>
                                        {{ $project }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">開始日</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">終了日</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">フィルター適用</button>
                            <button type="button" onclick="resetFilters()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">リセット</button>
                        </div>
                    </form>
                </div>
            </div>
        
            <!-- 統計情報 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">総訪問数</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->total_views ? number_format($stats->total_views) : '-' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">総ユニーク訪問者</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->total_uniques ? number_format($stats->total_uniques) : '-' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">平均訪問数</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->avg_views ? round($stats->avg_views) : '-' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">平均ユニーク訪問者</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->avg_uniques ? round($stats->avg_uniques) : '-' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- チャート -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        訪問数推移
                        @if(request('start_date') || request('end_date'))
                            <span class="text-sm font-normal text-gray-500">
                                ({{ request('start_date') ?: '開始日なし' }} ～ {{ request('end_date') ?: '終了日なし' }})
                            </span>
                        @endif
                    </h3>
                    @if($chartData && count($chartData) > 0)
                        <canvas id="viewsChart" width="400" height="200"></canvas>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">データが見つかりません</h3>
                            <p class="mt-1 text-sm text-gray-500">選択された条件に一致するデータがありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        
            <!-- データテーブル -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">訪問数データ</h3>
                    @if($views->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">プロジェクト</th>
                                        @if(!request('project') && !request('repository_id'))
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">期間</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">記録数</th>
                                        @else
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
                                        @endif
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訪問数</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユニーク訪問者</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($views as $view)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            @if(!request('project') && !request('repository_id') && $view->repository)
                                                {{ $view->repository->display_name ?? $view->project }}
                                            @else
                                                {{ $view->project }}
                                            @endif
                                        </td>
                                        @if(!request('project') && !request('repository_id'))
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $view->first_date->format('Y-m-d') }} ～ {{ $view->last_date->format('Y-m-d') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($view->record_count) }}</td>
                                        @else
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $view->date->format('Y-m-d') }}</td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($view->count) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($view->uniques) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- ページネーション -->
                        <div class="mt-6">
                            {{ $views->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">データが見つかりません</h3>
                            <p class="mt-1 text-sm text-gray-500">選択された条件に一致するデータがありません。</p>
                        </div>
                    @endif
                </div>
            </div>
            @else
            <!-- 認証されていないユーザー向けのメッセージ -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">ログインが必要です</h3>
                        <p class="mt-1 text-sm text-gray-500">GitHub訪問数データを閲覧するには、ログインが必要です。</p>
                        <p class="mt-1 text-sm text-gray-500">ログイン後、登録されたリポジトリの訪問数データを確認できます。</p>
                        <div class="mt-6">
                            <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                ログイン
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endauth
        </main>
    </div>

    @auth
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
    @endauth
</body>
</html>
