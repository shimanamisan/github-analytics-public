<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * 
     * @return \Illuminate\View\View ログインフォームのビュー
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
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
            
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'ログイン情報が正しくありません。',
        ]);
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