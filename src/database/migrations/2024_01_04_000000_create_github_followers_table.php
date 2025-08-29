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
        Schema::create('github_followers', function (Blueprint $table) {
            $table->id();
            $table->string('username'); // GitHubユーザー名
            $table->date('date'); // 記録日
            $table->integer('followers_count')->default(0); // フォロワー数
            $table->integer('following_count')->default(0); // フォロー中数
            $table->integer('public_repos')->default(0); // パブリックリポジトリ数
            $table->timestamps();

            // ユーザー名と日付の組み合わせでユニーク制約
            $table->unique(['username', 'date']);
            // 日付でインデックス
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_followers');
    }
};
