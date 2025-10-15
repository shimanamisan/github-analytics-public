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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
}
