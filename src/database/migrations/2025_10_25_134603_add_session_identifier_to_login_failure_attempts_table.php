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
        // テーブルが存在しない場合はスキップ
        if (!Schema::hasTable('login_failure_attempts')) {
            return;
        }

        // session_identifierカラムが存在しない場合のみ追加
        if (!Schema::hasColumn('login_failure_attempts', 'session_identifier')) {
            Schema::table('login_failure_attempts', function (Blueprint $table) {
                // Cookieベースの識別子を追加
                $table->string('session_identifier', 64)->nullable()->after('ip_address');
                $table->index('session_identifier');
            });
        }
        
        // ip_addressカラムが存在する場合、nullableに変更（参考情報として残す）
        if (Schema::hasColumn('login_failure_attempts', 'ip_address')) {
            try {
                Schema::table('login_failure_attempts', function (Blueprint $table) {
                    $table->string('ip_address', 45)->nullable()->change();
                });
            } catch (\Exception $e) {
                // 既にnullableの場合はエラーを無視
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_failure_attempts', function (Blueprint $table) {
            $table->dropIndex(['session_identifier']);
            $table->dropColumn('session_identifier');
        });
    }
};
