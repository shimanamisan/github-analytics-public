<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // デフォルトログイン用のユーザーを作成（一般ユーザー）
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => '一般ユーザー',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_active' => true,
            ]
        );

        // .envファイルから管理者情報を取得
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminName = env('ADMIN_NAME', 'システム管理者');

        // 管理者情報が設定されている場合のみ作成
        if (!empty($adminEmail) && !empty($adminPassword)) {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => $adminName,
                    'email' => $adminEmail,
                    'password' => Hash::make($adminPassword),
                    'email_verified_at' => now(),
                    'is_admin' => true,
                    'is_active' => true,
                ]
            );

            $this->command->info("管理者アカウントを作成しました: {$adminEmail}");
        } else {
            $this->command->warn('管理者情報が.envファイルに設定されていません。');
            $this->command->warn('ADMIN_EMAIL と ADMIN_PASSWORD を設定してください。');
        }

        $this->command->info('一般ユーザー: user@example.com / password');
    }
}