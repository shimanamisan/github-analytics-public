@extends('layouts.admin')

@section('title', 'フォロワー統計')

@section('content')
<div class="px-4 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">フォロワー統計</h1>
        <p class="mt-2 text-gray-600">GitHubフォロワー数の推移と統計情報</p>
    </div>

    <!-- フィルター -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">フィルター</h3>
            <form method="GET" action="{{ route('github.followers') }}" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @auth
                    @if(auth()->user()->isAdmin())
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">ユーザー</label>
                        <select name="user_id" id="user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">すべて</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->github_owner }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                @endauth
                
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">開始日</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">終了日</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        フィルター適用
                    </button>
                    <button type="button" onclick="resetFilters()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        リセット
                    </button>
                </div>
            </form>
        </div>
    </div>
            
    <!-- 統計情報 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">フォロワー数</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->max_followers ? number_format($stats->max_followers) : '-' }}</dd>
                            @if($growthRate !== null)
                                <dd class="text-sm {{ $growthRate >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $growthRate >= 0 ? '+' : '' }}{{ number_format($growthRate, 1) }}% (30日)
                                </dd>
                            @endif
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">フォロー数</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->max_following ? number_format($stats->max_following) : '-' }}</dd>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7l2 2-2 2m-2-2h2m-2 0h-2m2 0v2M7 7l-2 2 2 2m2-2H7m2 0H5m2 0V5"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">リポジトリ数</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats && $stats->max_repos ? number_format($stats->max_repos) : '-' }}</dd>
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
                フォロワー数推移
                @if(request('start_date') || request('end_date'))
                    <span class="text-sm font-normal text-gray-500">
                        ({{ request('start_date') ?: '開始日なし' }} ～ {{ request('end_date') ?: '終了日なし' }})
                    </span>
                @endif
            </h3>
            @if($chartData && count($chartData) > 0)
                <canvas id="followersChart" width="400" height="200"></canvas>
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
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">フォロワー統計データ</h3>
            @if($followers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー名</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">フォロワー数</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">フォロー数</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">リポジトリ数</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($followers as $follower)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $follower->username }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $follower->date->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($follower->followers_count) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($follower->following_count) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($follower->public_repos) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- ページネーション -->
                <div class="mt-6">
                    {{ $followers->links() }}
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'フォロー数',
                            data: chartData.map(function(item) { return item.following_count; }),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'リポジトリ数',
                            data: chartData.map(function(item) { return item.public_repos; }),
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
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
@endsection
