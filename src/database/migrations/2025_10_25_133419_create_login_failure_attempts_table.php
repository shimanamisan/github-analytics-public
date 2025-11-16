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
        // テーブルが既に存在する場合はスキップ（前回のデプロイで作成済みの可能性がある）
        if (Schema::hasTable('login_failure_attempts')) {
            return;
        }

        Schema::create('login_failure_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // IPv6対応のため45文字
            $table->string('email')->nullable(); // 試行されたメールアドレス
            $table->integer('consecutive_failures')->default(1); // 連続失敗回数
            $table->timestamp('last_attempt_at'); // 最後の試行時刻
            $table->boolean('is_five_strikes')->default(false); // 5回達成フラグ
            $table->timestamps();
            
            // IPアドレスでインデックスを作成してクエリを高速化
            $table->index('ip_address');
            $table->index('last_attempt_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_failure_attempts');
    }
};
