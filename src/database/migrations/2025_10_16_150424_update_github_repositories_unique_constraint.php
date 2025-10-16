<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('github_repositories', function (Blueprint $table) {
            // 既存のユニーク制約を削除
            $table->dropUnique('github_repositories_owner_repo_unique');
            
            // user_id, owner, repoの組み合わせでユニーク制約を追加
            $table->unique(['user_id', 'owner', 'repo'], 'github_repositories_user_owner_repo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_repositories', function (Blueprint $table) {
            // 新しい制約を削除
            $table->dropUnique('github_repositories_user_owner_repo_unique');
            
            // 元の制約を復元
            $table->unique(['owner', 'repo'], 'github_repositories_owner_repo_unique');
        });
    }
};
