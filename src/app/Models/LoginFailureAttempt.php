<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ログイン失敗試行を記録するモデル
 * 
 * 連続したログイン失敗を追跡し、5回連続失敗したIPアドレスを記録します。
 * 
 * @package App\Models
 * @property string $ip_address
 * @property string|null $email
 * @property int $consecutive_failures
 * @property \Illuminate\Support\Carbon $last_attempt_at
 * @property bool $is_five_strikes
 */
class LoginFailureAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'session_identifier',
        'email',
        'consecutive_failures',
        'last_attempt_at',
        'is_five_strikes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_attempt_at' => 'datetime',
        'is_five_strikes' => 'boolean',
    ];
    
    /**
     * 指定されたIPアドレスの記録をリセット（互換性のため残す）
     * 
     * @param string $ipAddress
     * @return void
     */
    public static function resetAttempts(string $ipAddress): void
    {
        self::where('ip_address', $ipAddress)->delete();
    }
    
    /**
     * 指定されたセッション識別子の記録をリセット
     * 
     * @param string $sessionIdentifier
     * @return void
     */
    public static function resetAttemptsBySession(string $sessionIdentifier): void
    {
        self::where('session_identifier', $sessionIdentifier)->delete();
    }
    
    /**
     * セッション識別子で失敗記録を取得
     * 
     * @param string $sessionIdentifier
     * @return self|null
     */
    public static function getBySessionIdentifier(string $sessionIdentifier): ?self
    {
        return self::where('session_identifier', $sessionIdentifier)->first();
    }
    
    /**
     * 古い記録（24時間以上前）を削除
     * 
     * @return void
     */
    public static function cleanupOldAttempts(): void
    {
        self::where('last_attempt_at', '<', now()->subHours(24))->delete();
    }
}
