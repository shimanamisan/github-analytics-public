<?php

namespace App\Livewire;

use App\Models\GitHubRepository;
use Livewire\Component;

class RepositoryCreator extends Component
{
    // フォームフィールド
    public $owner = '';
    public $repo = '';
    public $name = '';
    public $description = '';
    public $is_active = true;
    public $github_token = '';
    
    protected $rules = [
        'owner' => 'required|string|max:255',
        'repo' => 'required|string|max:255',
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
        'github_token' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'owner.required' => 'オーナー名は必須です',
        'repo.required' => 'リポジトリ名は必須です',
        'owner.max' => 'オーナー名は255文字以内で入力してください',
        'repo.max' => 'リポジトリ名は255文字以内で入力してください',
        'name.max' => '表示名は255文字以内で入力してください',
        'description.max' => '説明は1000文字以内で入力してください',
        'github_token.max' => 'GitHubトークンは255文字以内で入力してください',
    ];

    public function render()
    {
        return view('livewire.repository-creator');
    }

    public function save()
    {
        $this->validate();

        try {
            $repository = GitHubRepository::create([
                'user_id' => auth()->id(),
                'owner' => $this->owner,
                'repo' => $this->repo,
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'github_token' => $this->github_token ?: null,
            ]);
            
            session()->flash('message', 'リポジトリが正常に追加されました。');
            
            // ログに記録
            \Log::info('新しいリポジトリが追加されました', [
                'user' => auth()->user()->email,
                'repository' => "{$this->owner}/{$this->repo}",
                'action' => 'create'
            ]);
            
            // 追加後に即座にデータ取得を実行（オプション）
            if ($this->is_active) {
                try {
                    \Artisan::call('github:fetch-views', ['--repository' => $repository->id, '--test' => true]);
                    session()->flash('message', 'リポジトリが正常に追加され、初回データ取得も完了しました。');
                } catch (\Exception $e) {
                    \Log::warning('初回データ取得に失敗しました', [
                        'repository' => $repository->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // リポジトリ一覧ページにリダイレクト
            return redirect()->route('admin.repositories');
            
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                session()->flash('error', 'このオーナー/リポジトリの組み合わせは既に登録されています。');
            } else {
                session()->flash('error', 'エラーが発生しました: ' . $e->getMessage());
            }
        }
    }

    public function resetForm()
    {
        $this->owner = '';
        $this->repo = '';
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->github_token = '';
        $this->resetValidation();
    }

    public function cancel()
    {
        return redirect()->route('admin.repositories');
    }
}
