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
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_token')->nullable()->comment('GitHub Personal Access Token (ハッシュ化済み)');
            $table->string('github_owner')->nullable()->comment('GitHubユーザー名またはオーガニゼーション名');
            $table->boolean('github_settings_completed')->default(false)->comment('GitHub設定完了フラグ');
            $table->timestamp('github_token_updated_at')->nullable()->comment('GitHubトークン最終更新日時');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'github_token',
                'github_owner', 
                'github_settings_completed',
                'github_token_updated_at'
            ]);
        });
    }
};
