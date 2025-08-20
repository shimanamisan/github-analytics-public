<?php

namespace App\Livewire;

use App\Models\GitHubRepository;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function openModal()
    {
        $this->showModal = true;
        $this->resetForm();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
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
            if ($this->editMode) {
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
            } else {
                $repository = GitHubRepository::create([
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
            }

            $this->closeModal();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                session()->flash('error', 'このオーナー/リポジトリの組み合わせは既に登録されています。');
            } else {
                session()->flash('error', 'エラーが発生しました: ' . $e->getMessage());
            }
        }
    }

    public function edit($repositoryId)
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
}