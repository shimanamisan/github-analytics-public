@extends('layouts.admin')

@section('title', 'フォロワー詳細')

@section('content')
<div class="px-4 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">フォロワー詳細</h1>
        <p class="mt-2 text-gray-600">個別フォロワーの詳細情報と影響力スコア</p>
    </div>

    <!-- フィルター -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">フィルター</h3>
            <form method="GET" action="{{ route('github.follower-details') }}" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @auth
                    @if(auth()->user()->isAdmin())
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">ユーザー名</label>
                        <select name="username" id="username" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">すべて</option>
                            @foreach($usernames as $username)
                                <option value="{{ $username }}" {{ request('username') == $username ? 'selected' : '' }}>
                                    {{ $username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                @endauth
                
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">フォロワー検索</label>
                    <input type="text" name="search" id="search" placeholder="ユーザー名または名前" value="{{ request('search') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-2">並び順</label>
                    <select name="sort_by" id="sort_by" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="followed_at" {{ request('sort_by') == 'followed_at' ? 'selected' : '' }}>フォロー日時</option>
                        <option value="followers" {{ request('sort_by') == 'followers' ? 'selected' : '' }}>フォロワー数</option>
                        <option value="repos" {{ request('sort_by') == 'repos' ? 'selected' : '' }}>リポジトリ数</option>
                    </select>
                </div>
                
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">順序</label>
                    <select name="sort_order" id="sort_order" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>降順</option>
                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>昇順</option>
                    </select>
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                            <dt class="text-sm font-medium text-gray-500 truncate">総フォロワー数</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $detailStats && $detailStats->total_followers ? number_format($detailStats->total_followers) : '-' }}</dd>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">平均フォロワー数</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $detailStats && $detailStats->avg_follower_count ? number_format($detailStats->avg_follower_count) : '-' }}</dd>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7l2 2-2 2m-2-2h2m-2 0h-2m2 0v2M7 7l-2 2 2 2m2-2H7m2 0H5m2 0V5"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">平均リポジトリ数</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $detailStats && $detailStats->avg_repos ? number_format($detailStats->avg_repos) : '-' }}</dd>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">新規フォロワー（7日）</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $detailStats && $detailStats->recent_followers ? number_format($detailStats->recent_followers) : '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        @if($unfollowStats)
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">フォロー解除（7日）</dt>
                            <dd class="text-lg font-medium text-red-600">{{ $unfollowStats->recent_unfollowed ? number_format($unfollowStats->recent_unfollowed) : '0' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
            
    <!-- フォロワー一覧 -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">フォロワー詳細一覧</h3>
            @if($followerDetails->count() > 0)
                <div class="space-y-4">
                    @foreach($followerDetails as $follower)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 {{ !$follower->is_active ? 'opacity-60 border-dashed border-red-300 bg-red-50' : '' }}">
                            <div class="flex items-center space-x-4">
                                <img src="{{ $follower->follower_avatar_url ?: 'https://via.placeholder.com/80' }}" 
                                     alt="{{ $follower->follower_name ?: $follower->follower_username }}" 
                                     class="h-12 w-12 rounded-full object-cover border border-gray-200">
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 truncate {{ !$follower->is_active ? 'line-through text-red-600' : '' }}">
                                                {{ $follower->follower_name ?: $follower->follower_username }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <a href="https://github.com/{{ $follower->follower_username }}" target="_blank" rel="noopener noreferrer" class="hover:text-blue-600">
                                                    {{ $follower->follower_username }}
                                                </a>
                                            </p>
                                            @if($follower->follower_bio)
                                                <p class="text-sm text-gray-600 mt-1">{{ Str::limit($follower->follower_bio, 100) }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <div class="flex space-x-3">
                                                <div class="text-center">
                                                    <div class="text-sm font-semibold text-gray-900">{{ number_format($follower->follower_followers) }}</div>
                                                    <div class="text-xs text-gray-500">フォロワー</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-sm font-semibold text-gray-900">{{ number_format($follower->follower_following) }}</div>
                                                    <div class="text-xs text-gray-500">フォロー中</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-sm font-semibold text-gray-900">{{ number_format($follower->follower_public_repos) }}</div>
                                                    <div class="text-xs text-gray-500">リポジトリ</div>
                                                </div>
                                                @if(!$follower->is_active && $follower->unfollowed_at)
                                                <div class="text-center">
                                                    <div class="text-sm font-semibold text-red-600">{{ $follower->unfollowed_at->diffForHumans() }}</div>
                                                    <div class="text-xs text-red-500">解除日</div>
                                                </div>
                                                @endif
                                            </div>
                                            @if(!$follower->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    解除済み
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- ページネーション -->
                <div class="mt-6">
                    {{ $followerDetails->links('vendor.pagination.tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">フォロワーが見つかりません</h3>
                    <p class="mt-1 text-sm text-gray-500">選択された条件に一致するフォロワーがありません。</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // フィルターリセット
    function resetFilters() {
        document.getElementById('username').value = '';
        document.getElementById('search').value = '';
        document.getElementById('sort_by').value = 'followed_at';
        document.getElementById('sort_order').value = 'desc';
        document.getElementById('filterForm').submit();
    }
</script>
@endsection
