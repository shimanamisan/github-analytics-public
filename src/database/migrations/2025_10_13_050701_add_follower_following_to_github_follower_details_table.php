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
            $table->integer('follower_following')->default(0)->after('follower_followers'); // フォロワーのフォロー中数
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_follower_details', function (Blueprint $table) {
            $table->dropColumn('follower_following');
        });
    }
};
