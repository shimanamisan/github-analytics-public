@extends('layouts.admin')

@section('title', 'GitHub設定')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">GitHub設定</h2>
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            ダッシュボードに戻る
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- 現在の設定状況 -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">現在の設定状況</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        @if($hasSettings)
                            <div class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-medium">GitHub設定が完了しています</span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <p><strong>GitHubオーナー:</strong> {{ $githubOwner }}</p>
                                @if($tokenUpdatedAt)
                                    <p><strong>最終更新:</strong> {{ $tokenUpdatedAt->format('Y年m月d日 H:i') }}</p>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center text-yellow-600">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-medium">GitHub設定が未完了です</span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <p>GitHub APIを使用するために、Personal Access Tokenとオーナー名を設定してください。</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- GitHub設定フォーム -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">GitHub API設定</h3>
                        <p class="mt-1 text-sm text-gray-600">GitHub APIを使用するための認証情報を設定してください。</p>
                    </div>
                    
                    <form method="POST" action="{{ route('github.settings.store') }}" class="px-6 py-6 space-y-8">
                        @csrf
                        
                        <!-- GitHub Token 入力 -->
                        <div class="space-y-3">
                            <label for="github_token" class="block text-sm font-semibold text-gray-900">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    GitHub Personal Access Token
                                </span>
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       name="github_token" 
                                       id="github_token"
                                       value="{{ old('github_token', $currentToken) }}"
                                       class="block w-full px-4 py-3 text-base border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('github_token') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                       placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                       required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <button type="button" 
                                            onclick="togglePasswordVisibility('github_token')"
                                            class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <svg id="eye-icon-github_token" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            @error('github_token')
                                <div class="flex items-center text-red-600 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-sm text-blue-800">
                                    <strong>セキュリティ:</strong> トークンは暗号化して安全に保存されます。GitHubのSettings > Developer settings > Personal access tokensで生成してください。
                                </p>
                            </div>
                        </div>

                        <!-- GitHub Owner 入力 -->
                        <div class="space-y-3">
                            <label for="github_owner" class="block text-sm font-semibold text-gray-900">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                    GitHubユーザー名またはオーガニゼーション名
                                </span>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       name="github_owner" 
                                       id="github_owner"
                                       value="{{ old('github_owner', $githubOwner) }}"
                                       class="block w-full px-4 py-3 text-base border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('github_owner') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                       placeholder="your-username または your-organization"
                                       required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            @error('github_owner')
                                <div class="flex items-center text-red-600 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                <p class="text-sm text-gray-700">
                                    <strong>例:</strong> <code class="bg-gray-100 px-1 rounded">octocat</code> (個人ユーザー) または <code class="bg-gray-100 px-1 rounded">github</code> (オーガニゼーション)
                                </p>
                            </div>
                        </div>

                        <!-- アクションボタン -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-base font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $hasSettings ? '設定を更新' : '設定を保存' }}
                                </span>
                            </button>
                            
                            <button type="button" 
                                    onclick="testToken()"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-base font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <span class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    トークンをテスト
                                </span>
                            </button>
                            
                            @if($hasSettings)
                                <button type="button" 
                                        onclick="resetSettings()"
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg text-base font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    <span class="flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        設定をリセット
                                    </span>
                                </button>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- トークンテスト用の隠しフォーム -->
                <form id="testTokenForm" method="POST" action="{{ route('github.settings.test') }}" style="display: none;">
                    @csrf
                    <input type="hidden" name="github_token" id="testTokenInput">
                </form>

                <!-- 設定リセット用の隠しフォーム -->
                <form id="resetSettingsForm" method="POST" action="{{ route('github.settings.destroy') }}" style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>

                <!-- ヘルプ情報 -->
                <div class="mt-12 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-blue-900 mb-3">GitHub Personal Access Tokenの取得方法</h4>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-sm font-semibold rounded-full flex items-center justify-center mr-3 mt-0.5">1</span>
                                    <p class="text-sm text-blue-800">
                                        GitHubにログインし、<a href="https://github.com/settings/tokens" target="_blank" class="text-blue-600 hover:text-blue-800 underline font-medium">Settings > Developer settings > Personal access tokens</a>にアクセス
                                    </p>
                                </div>
                                <div class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-sm font-semibold rounded-full flex items-center justify-center mr-3 mt-0.5">2</span>
                                    <p class="text-sm text-blue-800">「Generate new token」をクリック</p>
                                </div>
                                <div class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-sm font-semibold rounded-full flex items-center justify-center mr-3 mt-0.5">3</span>
                                    <div class="text-sm text-blue-800">
                                        <p class="mb-2">トークンに名前を付け、必要な権限を選択：</p>
                                        <div class="bg-white bg-opacity-50 rounded-lg p-3 space-y-1">
                                            <div class="flex items-center">
                                                <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-mono mr-2">repo</code>
                                                <span class="text-xs">リポジトリへのアクセス</span>
                                            </div>
                                            <div class="flex items-center">
                                                <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-mono mr-2">read:org</code>
                                                <span class="text-xs">オーガニゼーション情報の読み取り</span>
                                            </div>
                                            <div class="flex items-center">
                                                <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-mono mr-2">read:user</code>
                                                <span class="text-xs">ユーザー情報の読み取り</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-sm font-semibold rounded-full flex items-center justify-center mr-3 mt-0.5">4</span>
                                    <p class="text-sm text-blue-800">「Generate token」をクリックしてトークンを生成</p>
                                </div>
                                <div class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-sm font-semibold rounded-full flex items-center justify-center mr-3 mt-0.5">5</span>
                                    <p class="text-sm text-blue-800">生成されたトークンをコピーして上記フォームに入力</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testToken() {
    const token = document.getElementById('github_token').value;
    if (!token) {
        alert('GitHub Personal Access Tokenを入力してください。');
        return;
    }
    
    document.getElementById('testTokenInput').value = token;
    document.getElementById('testTokenForm').submit();
}

function resetSettings() {
    if (confirm('GitHub設定をリセットしますか？この操作は取り消せません。')) {
        document.getElementById('resetSettingsForm').submit();
    }
}

function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById(`eye-icon-${fieldId}`);
    
    if (field.type === 'password') {
        field.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
    } else {
        field.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}
</script>
@endsection
