<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GitHubSettingsController extends Controller
{
    /**
     * GitHub設定画面を表示
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // 現在のトークンを取得（復号化）
        $currentToken = $user->getGitHubToken();
        
        return view('github.settings', [
            'user' => $user,
            'hasSettings' => $user->hasGitHubSettings(),
            'githubOwner' => $user->getGitHubOwner(),
            'tokenUpdatedAt' => $user->github_token_updated_at,
            'currentToken' => $currentToken, // 現在のトークンを渡す
        ]);
    }

    /**
     * GitHub設定を保存
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'github_token' => 'required|string|min:40',
            'github_owner' => 'required|string|max:255',
        ], [
            'github_token.required' => 'GitHub Personal Access Tokenは必須です。',
            'github_token.min' => 'GitHub Personal Access Tokenは40文字以上である必要があります。',
            'github_owner.required' => 'GitHubユーザー名またはオーガニゼーション名は必須です。',
            'github_owner.max' => 'GitHubユーザー名またはオーガニゼーション名は255文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = Auth::user();
        $token = $request->input('github_token');
        $owner = $request->input('github_owner');

        // GitHub APIでトークンの有効性を確認
        try {
            $response = Http::withHeaders([
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3+json',
            ])->get('https://api.github.com/user');

            if (!$response->successful()) {
                return redirect()->back()
                    ->withErrors(['github_token' => 'GitHub Personal Access Tokenが無効です。'])
                    ->withInput();
            }

            $githubUser = $response->json();
            
            // オーナー名の検証（ユーザー名またはオーガニゼーション名）
            if ($githubUser['login'] !== $owner) {
                // オーガニゼーションかチェック
                $orgResponse = Http::withHeaders([
                    'Authorization' => 'token ' . $token,
                    'Accept' => 'application/vnd.github.v3+json',
                ])->get("https://api.github.com/orgs/{$owner}");

                if (!$orgResponse->successful()) {
                    return redirect()->back()
                        ->withErrors(['github_owner' => '指定されたGitHubユーザー名またはオーガニゼーション名が見つかりません。'])
                        ->withInput();
                }
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['github_token' => 'GitHub APIとの通信に失敗しました。'])
                ->withInput();
        }

        // 設定を保存
        $user->setGitHubToken($token);
        $user->github_owner = $owner;
        $user->markGitHubSettingsCompleted();
        $user->save();

        return redirect()->route('github.settings')
            ->with('success', 'GitHub設定が正常に保存されました。');
    }

    /**
     * GitHub設定を更新
     */
    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }

    /**
     * GitHub設定をリセット
     */
    public function destroy(): RedirectResponse
    {
        $user = Auth::user();
        $user->resetGitHubSettings();
        $user->save();

        return redirect()->route('github.settings')
            ->with('success', 'GitHub設定がリセットされました。');
    }

    /**
     * GitHubトークンの有効性をテスト
     */
    public function testToken(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'github_token' => 'required|string|min:40',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $token = $request->input('github_token');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3+json',
            ])->get('https://api.github.com/user');

            if ($response->successful()) {
                $githubUser = $response->json();
                return redirect()->back()
                    ->with('success', "トークンは有効です。ユーザー: {$githubUser['login']}")
                    ->withInput();
            } else {
                return redirect()->back()
                    ->withErrors(['github_token' => 'GitHub Personal Access Tokenが無効です。'])
                    ->withInput();
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['github_token' => 'GitHub APIとの通信に失敗しました。'])
                ->withInput();
        }
    }
}