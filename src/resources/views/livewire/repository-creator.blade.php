<div>
    <form wire:submit="save" class="space-y-6">
        <!-- 基本情報セクション -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">基本情報</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        オーナー名 <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        wire:model="owner" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('owner') border-red-500 @enderror"
                        placeholder="例: octocat"
                        required
                    >
                    @error('owner') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">GitHubのユーザー名または組織名</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        リポジトリ名 <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        wire:model="repo" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('repo') border-red-500 @enderror"
                        placeholder="例: Hello-World"
                        required
                    >
                    @error('repo') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">リポジトリの名前</p>
                </div>
            </div>
        </div>

        <!-- 表示設定セクション -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">表示設定</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">表示名</label>
                    <input 
                        type="text" 
                        wire:model="name" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                        placeholder="例: Hello World プロジェクト"
                    >
                    @error('name') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">管理画面で表示する名前（空白の場合はリポジトリ名が使用されます）</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">説明</label>
                    <textarea 
                        wire:model="description" 
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                        placeholder="リポジトリの説明を入力してください"
                    ></textarea>
                    @error('description') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">リポジトリの詳細な説明</p>
                </div>
            </div>
        </div>

        <!-- 設定セクション -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">設定</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GitHubトークン（オプション）</label>
                    <input 
                        type="password" 
                        wire:model="github_token" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('github_token') border-red-500 @enderror"
                        placeholder="リポジトリ専用トークン（空白の場合は環境設定を使用）"
                    >
                    @error('github_token') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">このリポジトリ専用のGitHubトークン。空白の場合は環境変数のGITHUB_TOKENが使用されます。</p>
                </div>
                
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model="is_active" 
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        id="is_active"
                    >
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        アクティブ（データ取得を有効にする）
                    </label>
                </div>
                <p class="mt-1 text-sm text-gray-500">チェックを外すと、このリポジトリからのデータ取得が停止します</p>
            </div>
        </div>

        <!-- 注意事項 -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">注意事項</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>リポジトリが公開されていることを確認してください</li>
                            <li>GitHubトークンには適切な権限が設定されている必要があります</li>
                            <li>追加後、初回データ取得が自動実行されます</li>
                            <li>重複するリポジトリは登録できません</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ボタン -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <button 
                type="button" 
                wire:click="cancel"
                class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
            >
                キャンセル
            </button>
            <button 
                type="button" 
                wire:click="resetForm"
                class="px-6 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors"
            >
                フォームをリセット
            </button>
            <button 
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                リポジトリを追加
            </button>
        </div>
    </form>
    

</div>
