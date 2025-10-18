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
            // created_atとupdated_atカラムを削除
            $table->dropTimestamps();
        });
        
        Schema::table('users', function (Blueprint $table) {
            // 末尾にcreated_atとupdated_atカラムを再追加
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // created_atとupdated_atカラムを削除
            $table->dropTimestamps();
        });
        
        Schema::table('users', function (Blueprint $table) {
            // 元の位置（remember_tokenの後）にcreated_atとupdated_atカラムを再追加
            $table->timestamps();
        });
    }
};
