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
        Schema::table('github_views', function (Blueprint $table) {
            // リポジトリIDを追加
            $table->unsignedBigInteger('repository_id')->nullable()->after('id');
            
            // 外部キー制約
            $table->foreign('repository_id')->references('id')->on('github_repositories')->onDelete('cascade');
            
            // インデックス
            $table->index(['repository_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_views', function (Blueprint $table) {
            $table->dropForeign(['repository_id']);
            $table->dropColumn('repository_id');
        });
    }
};
