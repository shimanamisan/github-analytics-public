<?php

namespace App\Livewire;

use App\Models\GitHubRepository;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RepositoryManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $repositoryId = null;
    
    // フォームフィールド
    public $owner = '';
    public $repo = '';
    public $name = '';
    public $description = '';
    public $is_active = true;
    public $github_token = '';
    
    // フィルタリング
    public $search = '';
    public $activeFilter = 'all';
    
    // 手動実行の状態管理
    public $isFetching = false;
    public $fetchMessage = '';
    
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
        $repositories = GitHubRepository::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('owner', 'like', '%' . $this->search . '%')
                      ->orWhere('repo', 'like', '%' . $this->search . '%')
                      ->orWhere('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->activeFilter !== 'all', function ($query) {
                $query->where('is_active', $this->activeFilter === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.repository-manager', compact('repositories'));
    }

    public function openEditModal($repositoryId)
    {
        $repository = GitHubRepository::findOrFail($repositoryId);
        
        $this->editMode = true;
        $this->repositoryId = $repository->id;
        $this->owner = $repository->owner;
        $this->repo = $repository->repo;
        $this->name = $repository->name;
        $this->description = $repository->description;
        $this->is_active = $repository->is_active;
        $this->github_token = $repository->github_token;
        
        $this->showModal = true;
    }

    public function closeEditModal()
    {
        $this->showModal = false;
        $this->resetEditForm();
    }

    public function resetEditForm()
    {
        $this->editMode = false;
        $this->repositoryId = null;
        $this->owner = '';
        $this->repo = '';
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->github_token = '';
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        try {
            $repository = GitHubRepository::findOrFail($this->repositoryId);
            $repository->update([
                'owner' => $this->owner,
                'repo' => $this->repo,
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'github_token' => $this->github_token ?: null,
            ]);
            session()->flash('message', 'リポジトリが正常に更新されました。');
            
            // ログに記録
            \Log::info('リポジトリが更新されました', [
                'user' => auth()->user()->email,
                'repository' => "{$this->owner}/{$this->repo}",
                'action' => 'update'
            ]);

            $this->closeEditModal();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                session()->flash('error', 'このオーナー/リポジトリの組み合わせは既に登録されています。');
            } else {
                session()->flash('error', 'エラーが発生しました: ' . $e->getMessage());
            }
        }
    }

    public function delete($repositoryId)
    {
        try {
            $repository = GitHubRepository::findOrFail($repositoryId);
            $repository->delete();
            session()->flash('message', 'リポジトリが正常に削除されました。');
        } catch (\Exception $e) {
            session()->flash('error', '削除中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    public function toggleStatus($repositoryId)
    {
        try {
            $repository = GitHubRepository::findOrFail($repositoryId);
            $repository->update(['is_active' => !$repository->is_active]);
            
            $status = $repository->is_active ? 'アクティブ' : '非アクティブ';
            session()->flash('message', "リポジトリが{$status}に変更されました。");
        } catch (\Exception $e) {
            session()->flash('error', 'ステータス変更中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveFilter()
    {
        $this->resetPage();
    }

    /**
     * 特定のリポジトリの訪問数データを手動で取得
     */
    public function fetchViews($repositoryId)
    {
        try {
            $this->isFetching = true;
            $this->fetchMessage = 'データ取得中...';
            
            $repository = GitHubRepository::findOrFail($repositoryId);
            
            if (!$repository->is_active) {
                session()->flash('error', 'このリポジトリは非アクティブです。データ取得を有効にしてください。');
                return;
            }
            
            // リポジトリ名を安全に取得
            $repoName = $repository->display_name ?: $repository->full_name;
            
            // Artisanコマンドを実行
            $exitCode = Artisan::call('github:fetch-views', [
                '--repository' => $repositoryId
            ]);
            
            if ($exitCode === 0) {
                $this->fetchMessage = 'データ取得が完了しました';
                session()->flash('message', "リポジトリ「{$repoName}」の訪問数データを正常に取得しました。");
                
                // ログに記録
                Log::info('手動でGitHub訪問数データを取得しました', [
                    'user' => auth()->user()->email,
                    'repository' => $repository->full_name,
                    'repository_id' => $repositoryId
                ]);
            } else {
                $this->fetchMessage = 'データ取得中にエラーが発生しました';
                session()->flash('error', 'データ取得中にエラーが発生しました。');
            }
            
        } catch (\Exception $e) {
            $this->fetchMessage = 'エラーが発生しました';
            session()->flash('error', 'エラーが発生しました: ' . $e->getMessage());
            
            Log::error('手動GitHub訪問数データ取得エラー', [
                'user' => auth()->user()->email,
                'repository_id' => $repositoryId,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isFetching = false;
        }
    }

    /**
     * すべてのアクティブなリポジトリの訪問数データを手動で取得
     */
    public function fetchAllViews()
    {
        try {
            $this->isFetching = true;
            $this->fetchMessage = '全リポジトリのデータ取得中...';
            
            $activeCount = GitHubRepository::where('is_active', true)->count();
            
            if ($activeCount === 0) {
                session()->flash('error', 'アクティブなリポジトリがありません。');
                return;
            }
            
            // Artisanコマンドを実行
            $exitCode = Artisan::call('github:fetch-views');
            
            if ($exitCode === 0) {
                $this->fetchMessage = '全リポジトリのデータ取得が完了しました';
                session()->flash('message', "全{$activeCount}件のリポジトリの訪問数データを正常に取得しました。");
                
                // ログに記録
                Log::info('手動で全リポジトリのGitHub訪問数データを取得しました', [
                    'user' => auth()->user()->email,
                    'repository_count' => $activeCount
                ]);
            } else {
                $this->fetchMessage = 'データ取得中にエラーが発生しました';
                session()->flash('error', 'データ取得中にエラーが発生しました。');
            }
            
        } catch (\Exception $e) {
            $this->fetchMessage = 'エラーが発生しました';
            session()->flash('error', 'エラーが発生しました: ' . $e->getMessage());
            
            Log::error('手動全リポジトリGitHub訪問数データ取得エラー', [
                'user' => auth()->user()->email,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isFetching = false;
        }
    }
}