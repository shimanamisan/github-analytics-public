<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginFailureAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 認証関連のログイン処理を管理するコントローラー
 * 
 * ユーザーのログイン、ログアウト、ログインフォーム表示の機能を提供します。
 * 
 * @package App\Http\Controllers\Auth
 * @since 1.0.0
 */
class LoginController extends Controller
{
    /**
     * ログイン画面を表示
     * 
     * ユーザーがログイン情報を入力できるフォーム画面を表示します。
     * Cookieベースで失敗回数を判定します。
     * 
     * @param \Illuminate\Http\Request $request HTTPリクエストインスタンス
     * @return \Illuminate\View\View ログインフォームのビュー
     */
    public function showLoginForm(Request $request): View
    {
        // Cookieからセッション識別子を取得（なければ新規作成）
        $sessionIdentifier = $request->cookie('login_attempt_token');
        
        if (!$sessionIdentifier) {
            $sessionIdentifier = Str::random(40);
            Cookie::queue('login_attempt_token', $sessionIdentifier, 60 * 24); // 24時間有効
        }
        
        // セッション識別子で失敗記録を取得
        $failureAttempt = LoginFailureAttempt::getBySessionIdentifier($sessionIdentifier);
        
        // 5回失敗達成フラグをビューに渡す
        $isFiveStrikes = $failureAttempt && $failureAttempt->is_five_strikes;
        $consecutiveFailures = $failureAttempt ? $failureAttempt->consecutive_failures : 0;
        
        return view('auth.login', [
            'isFiveStrikes' => $isFiveStrikes,
            'consecutiveFailures' => $consecutiveFailures,
        ]);
    }

    /**
     * ログイン処理
     * 
     * ユーザーが入力したメールアドレスとパスワードで認証を行い、
     * 成功時は管理者ダッシュボードにリダイレクトします。
     * 失敗時はバリデーションエラーを投げます。
     * 
     * @param \Illuminate\Http\Request $request HTTPリクエストインスタンス
     * @return \Illuminate\Http\RedirectResponse 認証成功時のリダイレクトレスポンス
     * @throws \Illuminate\Validation\ValidationException 認証失敗時またはバリデーションエラー時
     * 
     * @uses \Illuminate\Support\Facades\Auth::attempt()
     * @uses \Illuminate\Http\Request::validate()
     * @uses \Illuminate\Http\Request::session()
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'password.required' => 'パスワードは必須です',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');
        $ipAddress = $request->ip();
        
        // Cookieからセッション識別子を取得（なければ新規作成）
        $sessionIdentifier = $request->cookie('login_attempt_token');
        
        if (!$sessionIdentifier) {
            $sessionIdentifier = Str::random(40);
            Cookie::queue('login_attempt_token', $sessionIdentifier, 60 * 24); // 24時間有効
        }

        // ユーザーの有効状態をチェック
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        
        if ($user && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'このアカウントは無効化されています。管理者にお問い合わせください。',
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            // ログイン成功後にユーザーの状態を再チェック
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                throw ValidationException::withMessages([
                    'email' => 'このアカウントは無効化されています。管理者にお問い合わせください。',
                ]);
            }
            
            // ログイン成功：失敗記録とCookieをクリア
            LoginFailureAttempt::resetAttemptsBySession($sessionIdentifier);
            Cookie::queue(Cookie::forget('login_attempt_token'));
            
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        // ログイン失敗：失敗回数を記録
        $this->recordLoginFailure($sessionIdentifier, $ipAddress, $credentials['email']);

        throw ValidationException::withMessages([
            'email' => 'ログイン情報が正しくありません。',
        ]);
    }
    
    /**
     * ログイン失敗を記録
     * 
     * Cookieのセッション識別子ごとに失敗回数をカウントし、5回目の失敗で特別フラグを立てます。
     * IPアドレスは参考情報として記録されます。
     * 
     * @param string $sessionIdentifier Cookieのセッション識別子
     * @param string $ipAddress 失敗したリクエストのIPアドレス（参考情報）
     * @param string $email 試行されたメールアドレス
     * @return void
     */
    private function recordLoginFailure(string $sessionIdentifier, string $ipAddress, string $email): void
    {
        // 既存の記録を取得
        $attempt = LoginFailureAttempt::getBySessionIdentifier($sessionIdentifier);
        
        if ($attempt) {
            // 既存の記録を更新
            $attempt->consecutive_failures++;
            $attempt->email = $email;
            $attempt->ip_address = $ipAddress; // 参考情報として更新
            $attempt->last_attempt_at = now();
            
            // 5回目の失敗でフラグを立てる
            if ($attempt->consecutive_failures >= 5) {
                $attempt->is_five_strikes = true;
            }
            
            $attempt->save();
        } else {
            // 新規記録を作成
            LoginFailureAttempt::create([
                'session_identifier' => $sessionIdentifier,
                'ip_address' => $ipAddress,
                'email' => $email,
                'consecutive_failures' => 1,
                'last_attempt_at' => now(),
                'is_five_strikes' => false,
            ]);
        }
        
        // 古い記録を削除（24時間以上前）
        LoginFailureAttempt::cleanupOldAttempts();
    }

    /**
     * ログアウト処理
     * 
     * 現在ログインしているユーザーをログアウトし、
     * セッションを無効化してトークンを再生成します。
     * その後、ログイン画面にリダイレクトします。
     * 
     * @param \Illuminate\Http\Request $request HTTPリクエストインスタンス
     * @return \Illuminate\Http\RedirectResponse ログイン画面へのリダイレクトレスポンス
     * 
     * @uses \Illuminate\Support\Facades\Auth::logout()
     * @uses \Illuminate\Http\Request::session()
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}