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
            // github_tokenカラムを削除
            $table->dropColumn('github_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_repositories', function (Blueprint $table) {
            // github_tokenカラムを復元
            $table->string('github_token')->nullable()->after('is_active');
        });
    }
};
