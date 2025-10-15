<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GitHubRepository;
use App\Models\User;

class UpdateRepositoryUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のリポジトリにユーザーIDを設定
        // 管理者ユーザーを取得（最初の管理者、または最初のユーザー）
        $adminUser = User::where('is_admin', true)->first();
        
        if (!$adminUser) {
            $adminUser = User::first();
        }
        
        if ($adminUser) {
            // user_idがnullのリポジトリに管理者のIDを設定
            GitHubRepository::whereNull('user_id')->update(['user_id' => $adminUser->id]);
            
            $this->command->info("既存のリポジトリにユーザーID ({$adminUser->email}) を設定しました。");
        } else {
            $this->command->warn('ユーザーが見つかりませんでした。リポジトリのユーザーIDは設定されませんでした。');
        }
    }
}
