<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
        'github_token',
        'github_owner',
        'github_settings_completed',
        'github_token_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'github_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'github_settings_completed' => 'boolean',
            'github_token_updated_at' => 'datetime',
        ];
    }

    /**
     * ユーザーが管理者かどうかをチェック
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * ユーザーが有効かどうかをチェック
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 管理者ユーザーのみを取得
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * 一般ユーザーのみを取得
     */
    public function scopeRegularUsers($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * 有効なユーザーのみを取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 認証時に有効なユーザーのみを対象とする
     */
    public function scopeForAuthentication($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ユーザーが登録したリポジトリとの関連
     */
    public function gitHubRepositories(): HasMany
    {
        return $this->hasMany(GitHubRepository::class);
    }

    /**
     * GitHub設定が完了しているかチェック
     */
    public function hasGitHubSettings(): bool
    {
        return $this->github_settings_completed && 
               !empty($this->github_token) && 
               !empty($this->github_owner);
    }

    /**
     * GitHubトークンを設定（暗号化して保存）
     */
    public function setGitHubToken(string $token): void
    {
        $this->github_token = encrypt($token);
        $this->github_token_updated_at = now();
    }

    /**
     * GitHubトークンを取得（復号化して返す）
     */
    public function getGitHubToken(): ?string
    {
        if (empty($this->github_token)) {
            return null;
        }
        
        try {
            return decrypt($this->github_token);
        } catch (\Exception $e) {
            // 復号化に失敗した場合はnullを返す
            return null;
        }
    }

    /**
     * GitHubトークンが設定されているかチェック
     */
    public function hasGitHubToken(): bool
    {
        return !empty($this->github_token);
    }

    /**
     * GitHubオーナー名を取得
     */
    public function getGitHubOwner(): ?string
    {
        return $this->github_owner;
    }

    /**
     * GitHub設定を完了としてマーク
     */
    public function markGitHubSettingsCompleted(): void
    {
        $this->github_settings_completed = true;
    }

    /**
     * GitHub設定をリセット
     */
    public function resetGitHubSettings(): void
    {
        $this->github_token = null;
        $this->github_owner = null;
        $this->github_settings_completed = false;
        $this->github_token_updated_at = null;
    }
}
