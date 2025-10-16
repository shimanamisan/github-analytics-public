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
            // 既存のユニーク制約を削除
            $table->dropUnique(['project', 'date']);
            
            // repository_id + dateの組み合わせでユニーク制約を追加
            $table->unique(['repository_id', 'date'], 'github_views_repository_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_views', function (Blueprint $table) {
            // 新しい制約を削除
            $table->dropUnique('github_views_repository_date_unique');
            
            // 元の制約を復元
            $table->unique(['project', 'date']);
        });
    }
};
