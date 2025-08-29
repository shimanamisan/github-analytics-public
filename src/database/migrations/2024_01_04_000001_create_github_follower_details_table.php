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
        Schema::create('github_follower_details', function (Blueprint $table) {
            $table->id();
            $table->string('target_username'); // フォロー対象のユーザー名
            $table->string('follower_username'); // フォロワーのユーザー名
            $table->string('follower_name')->nullable(); // フォロワーの表示名
            $table->string('follower_avatar_url')->nullable(); // アバターURL
            $table->string('follower_bio')->nullable(); // プロフィール
            $table->integer('follower_public_repos')->default(0); // フォロワーのパブリックリポジトリ数
            $table->integer('follower_followers')->default(0); // フォロワーのフォロワー数
            $table->timestamp('followed_at')->nullable(); // フォローされた日時
            $table->boolean('is_active')->default(true); // アクティブフラグ（フォロー解除された場合はfalse）
            $table->timestamps();

            // 対象ユーザーとフォロワーの組み合わせでユニーク制約
            $table->unique(['target_username', 'follower_username']);
            // 検索用インデックス
            $table->index('target_username');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_follower_details');
    }
};
