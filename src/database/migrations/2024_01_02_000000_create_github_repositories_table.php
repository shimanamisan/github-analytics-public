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
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->string('owner')->comment('GitHubリポジトリのオーナー名');
            $table->string('repo')->comment('GitHubリポジトリ名');
            $table->string('name')->nullable()->comment('表示用の名前');
            $table->text('description')->nullable()->comment('説明');
            $table->boolean('is_active')->default(true)->comment('データ取得の有効/無効');
            $table->string('github_token')->nullable()->comment('リポジトリ専用のGitHubトークン');
            $table->timestamps();
            
            // オーナー名とリポジトリ名の組み合わせを一意にする
            $table->unique(['owner', 'repo']);
            
            // インデックス
            $table->index(['is_active']);
            $table->index(['owner']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
