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
        Schema::table('github_follower_details', function (Blueprint $table) {
            $table->timestamp('unfollowed_at')->nullable()->after('followed_at'); // フォロー解除日時
            $table->index('unfollowed_at'); // 検索用インデックス
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_follower_details', function (Blueprint $table) {
            $table->dropIndex(['unfollowed_at']);
            $table->dropColumn('unfollowed_at');
        });
    }
};
