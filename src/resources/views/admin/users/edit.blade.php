@extends('layouts.admin')

@section('title', 'ユーザー編集')

@section('content')
<div class="px-4 sm:px-0">
    <div class="mb-8">
        <div class="flex items-center">
            <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-900 mr-4">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">ユーザー編集</h1>
                <p class="mt-2 text-gray-600">{{ $user->name }}の情報を編集します</p>
            </div>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- 名前 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- メールアドレス -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- パスワード -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">パスワード（変更する場合のみ入力）</label>
                        <input type="password" name="password" id="password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- パスワード確認 -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">パスワード確認</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- 管理者権限 -->
                    <div class="flex items-center">
                        <input type="checkbox" name="is_admin" id="is_admin" value="1" 
                               {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_admin" class="ml-2 block text-sm text-gray-900">
                            管理者権限を付与する
                        </label>
                    </div>

                    <!-- 現在の状態 -->
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">現在の状態</h3>
                        <div class="flex items-center space-x-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $user->is_admin ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $user->is_admin ? '管理者' : '一般ユーザー' }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $user->is_active ? '有効' : '無効' }}
                            </span>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('admin.users.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
