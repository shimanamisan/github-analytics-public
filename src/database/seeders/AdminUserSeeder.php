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

        // 実際の管理者アカウントを作成
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'システム管理者',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_active' => true,
            ]
        );

        $this->command->info('ユーザーアカウントを作成しました。');
        $this->command->info('一般ユーザー: user@example.com / password');
        $this->command->info('管理者: admin@example.com / admin123');
    }
}