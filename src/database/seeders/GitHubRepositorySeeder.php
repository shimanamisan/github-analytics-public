<?php

namespace Database\Seeders;

use App\Models\GitHubRepository;
use Illuminate\Database\Seeder;

class GitHubRepositorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $repositories = [
            [
                'owner' => 'shimanamisan',
                'repo' => 'CsharpSample',
                'name' => 'C# サンプルプロジェクト',
                'description' => 'C#の学習用サンプルプロジェクト',
                'is_active' => true,
                'github_token' => null, // 環境設定のトークンを使用
            ],
            // 他のリポジトリもここに追加可能
            // [
            //     'owner' => 'your-username',
            //     'repo' => 'another-repo',
            //     'name' => '別のプロジェクト',
            //     'description' => 'プロジェクトの説明',
            //     'is_active' => true,
            //     'github_token' => null,
            // ],
        ];

        foreach ($repositories as $repo) {
            GitHubRepository::updateOrCreate(
                [
                    'owner' => $repo['owner'],
                    'repo' => $repo['repo'],
                ],
                $repo
            );
        }

        $this->command->info('GitHubリポジトリデータを投入しました。');
    }
}
