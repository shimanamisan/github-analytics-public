<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既存のレコードのcreated_atとupdated_atを本日の日時に更新
        $today = now()->format('Y-m-d H:i:s');
        
        DB::table('users')
            ->whereNull('created_at')
            ->orWhereNull('updated_at')
            ->update([
                'created_at' => $today,
                'updated_at' => $today,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // このマイグレーションは元に戻すことができないため、何もしない
        // 既存のデータを変更するため、ロールバックは推奨されない
    }
};
