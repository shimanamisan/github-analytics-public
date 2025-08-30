<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHubフォロワー詳細情報</title>
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
        
        .follower-card {
            background: #ffffff;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
            border: 1px solid #e1e4e8;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
        }
        
        .follower-card:hover {
            background: #f6f8fa;
        }
        
        .follower-card.unfollowed {
            opacity: 0.6;
            border: 1px dashed #dc3545;
            background: #fff5f5;
        }
        
        .follower-card.unfollowed .follower-name {
            color: #dc3545;
            text-decoration: line-through;
        }
        
        .follower-card.unfollowed::after {
            content: '解除済み';
            position: absolute;
            top: 8px;
            right: 12px;
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .follower-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e1e4e8;
        }
        
        .follower-info {
            flex: 1;
        }
        
        .follower-name {
            font-size: 16px;
            font-weight: 600;
            color: #24292e;
            margin-bottom: 2px;
        }
        
        .follower-username {
            color: #586069;
            font-weight: 400;
            margin-bottom: 8px;
        }
        
        .follower-username a {
            color: #0366d6;
            text-decoration: none;
        }
        
        .follower-username a:hover {
            text-decoration: underline;
        }
        
        .follower-bio {
            color: #586069;
            margin-bottom: 8px;
            line-height: 1.4;
            font-size: 14px;
        }
        
        .follower-stats {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .follower-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 12px;
            background: #f6f8fa;
            border-radius: 6px;
            border: 1px solid #e1e4e8;
            min-width: 70px;
        }
        
        .follower-stat-number {
            font-size: 14px;
            font-weight: 600;
            color: #24292e;
        }
        
        .follower-stat-label {
            font-size: 12px;
            color: #586069;
        }
        
        .influence-score {
            background: #0366d6;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            margin-left: auto;
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
            color: #ed8936;
            border: 2px solid #e2e8f0;
            margin: 0 4px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background: #ed8936;
            color: white;
            border-color: #ed8936;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
            border-color: #ed8936;
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
            
            .follower-card {
                flex-direction: column;
                text-align: center;
            }
            
            .follower-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h1>GitHubフォロワー詳細情報</h1>
            <div class="nav-links">
                <a href="{{ route('home') }}" class="nav-link">訪問数</a>
                <a href="{{ route('github.followers') }}" class="nav-link">フォロワー統計</a>
                <a href="{{ route('github.follower-details') }}" class="nav-link active">フォロワー詳細</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="nav-link">管理画面</a>
                @endauth
            </div>
        </div>
        
        <div class="main-content">
            <!-- フィルター -->
            <div class="filters">
                <form method="GET" action="{{ route('github.follower-details') }}" id="filterForm">
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
                        <label for="search">フォロワー検索:</label>
                        <input type="text" name="search" id="search" placeholder="ユーザー名または名前" value="{{ request('search') }}">
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort_by">並び順:</label>
                        <select name="sort_by" id="sort_by">
                            <option value="followed_at" {{ request('sort_by') == 'followed_at' ? 'selected' : '' }}>フォロー日時</option>
                            <option value="followers" {{ request('sort_by') == 'followers' ? 'selected' : '' }}>フォロワー数</option>
                            <option value="repos" {{ request('sort_by') == 'repos' ? 'selected' : '' }}>リポジトリ数</option>
                            <option value="influence" {{ request('sort_by') == 'influence' ? 'selected' : '' }}>影響力スコア</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort_order">順序:</label>
                        <select name="sort_order" id="sort_order">
                            <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>降順</option>
                            <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>昇順</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">フィルター適用</button>
                    <button type="button" class="btn" onclick="resetFilters()">リセット</button>
                </form>
            </div>
            
            <!-- 統計情報 -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-number" id="totalFollowers">
                        {{ $detailStats && $detailStats->total_followers ? number_format($detailStats->total_followers) : '-' }}
                    </div>
                    <div class="stat-label">総フォロワー数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="avgFollowerCount">
                        {{ $detailStats && $detailStats->avg_follower_count ? number_format($detailStats->avg_follower_count) : '-' }}
                    </div>
                    <div class="stat-label">平均フォロワー数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="avgRepos">
                        {{ $detailStats && $detailStats->avg_repos ? number_format($detailStats->avg_repos) : '-' }}
                    </div>
                    <div class="stat-label">平均リポジトリ数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="recentFollowers">
                        {{ $detailStats && $detailStats->recent_followers ? number_format($detailStats->recent_followers) : '-' }}
                    </div>
                    <div class="stat-label">新規フォロワー（7日）</div>
                </div>
                @if($unfollowStats)
                <div class="stat-card">
                    <div class="stat-number" id="recentUnfollowed" style="color: #e53e3e;">
                        {{ $unfollowStats->recent_unfollowed ? number_format($unfollowStats->recent_unfollowed) : '0' }}
                    </div>
                    <div class="stat-label">フォロー解除（7日）</div>
                </div>
                @endif
            </div>
            
            <!-- フォロワー一覧 -->
            <h3>フォロワー詳細一覧</h3>
            @if($followerDetails->count() > 0)
                @foreach($followerDetails as $follower)
                    <div class="follower-card {{ !$follower->is_active ? 'unfollowed' : '' }}">
                        <img src="{{ $follower->follower_avatar_url ?: 'https://via.placeholder.com/80' }}" 
                             alt="{{ $follower->follower_name ?: $follower->follower_username }}" 
                             class="follower-avatar">
                        
                        <div class="follower-info">
                            <div class="follower-name">
                                {{ $follower->follower_name ?: $follower->follower_username }}
                            </div>
                            <div class="follower-username">
                                <a href="https://github.com/{{ $follower->follower_username }}" target="_blank" rel="noopener noreferrer">
                                    &#64;{{ $follower->follower_username }}
                                </a>
                            </div>
                            @if($follower->follower_bio)
                                <div class="follower-bio">
                                    {{ Str::limit($follower->follower_bio, 100) }}
                                </div>
                            @endif
                            <div class="follower-stats">
                                <div class="follower-stat">
                                    <div class="follower-stat-number">{{ number_format($follower->follower_followers) }}</div>
                                    <div class="follower-stat-label">フォロワー</div>
                                </div>
                                <div class="follower-stat">
                                    <div class="follower-stat-number">{{ number_format($follower->follower_public_repos) }}</div>
                                    <div class="follower-stat-label">リポジトリ</div>
                                </div>

                                @if(!$follower->is_active && $follower->unfollowed_at)
                                <div class="follower-stat" style="background: rgba(245, 101, 101, 0.1);">
                                    <div class="follower-stat-number" style="color: #e53e3e;">{{ $follower->unfollowed_at->diffForHumans() }}</div>
                                    <div class="follower-stat-label">解除日</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="influence-score">
                            影響力: {{ number_format($follower->influence_score, 1) }}
                        </div>
                    </div>
                @endforeach
                
                <!-- ページネーション -->
                <div class="pagination">
                    {{ $followerDetails->links('vendor.pagination.tailwind') }}
                </div>
            @else
                <div class="no-data-message">
                    <h4>フォロワーが見つかりません</h4>
                    <p>選択された条件に一致するフォロワーがありません。</p>
                    <p>フィルター条件を変更するか、検索条件を調整してください。</p>
                </div>
            @endif
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
</body>
</html>
