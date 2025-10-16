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
    
    protected $rules = [
        'owner' => 'required|string|max:255',
        'repo' => 'required|string|max:255',
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'owner.required' => 'オーナー名は必須です',
        'repo.required' => 'リポジトリ名は必須です',
        'owner.max' => 'オーナー名は255文字以内で入力してください',
        'repo.max' => 'リポジトリ名は255文字以内で入力してください',
        'name.max' => '表示名は255文字以内で入力してください',
        'description.max' => '説明は1000文字以内で入力してください',
    ];

    public function render()
    {
        return view('livewire.repository-creator');
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();

        // デバッグログ
        \Log::info('RepositoryCreator::save() - GitHub設定チェック', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'github_settings_completed' => $user->github_settings_completed,
            'github_token_exists' => !empty($user->github_token),
            'github_owner' => $user->github_owner,
            'hasGitHubSettings' => $user->hasGitHubSettings(),
        ]);

        // ユーザーがGitHub設定を完了しているか確認
        if (!$user->hasGitHubSettings()) {
            \Log::warning('GitHub設定が未完了のためリダイレクト', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            session()->flash('error', 'リポジトリを追加する前に、GitHub設定を完了してください。');
            $this->redirect(route('github.settings'));
            return;
        }

        try {
            // ユーザー固有のGitHubトークンを取得
            $token = $user->getGitHubToken();
            
            \Log::info('リポジトリ作成開始', [
                'user_id' => auth()->id(),
                'owner' => $this->owner,
                'repo' => $this->repo,
                'has_token' => !empty($token),
            ]);
            
            $repository = GitHubRepository::create([
                'user_id' => auth()->id(),
                'owner' => $this->owner,
                'repo' => $this->repo,
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'github_token' => $token, // ユーザーのトークンを使用
            ]);
            
            session()->flash('message', 'リポジトリが正常に追加されました。');
            
            // ログに記録
            \Log::info('新しいリポジトリが追加されました', [
                'repository_id' => $repository->id,
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
            $this->redirect(route('admin.repositories'));
            
        } catch (\Exception $e) {
            \Log::error('リポジトリ作成エラー', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'owner' => $this->owner,
                'repo' => $this->repo,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            
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
        $this->resetValidation();
    }

    public function cancel()
    {
        $this->redirect(route('admin.repositories'));
    }
}
