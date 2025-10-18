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
        // github_followersテーブルの既存データにuser_idを設定
        $followers = DB::table('github_followers')->whereNull('user_id')->get();
        
        foreach ($followers as $follower) {
            // usernameからユーザーを特定
            $user = DB::table('users')
                ->where('github_owner', $follower->username)
                ->where('github_settings_completed', true)
                ->first();
            
            if ($user) {
                DB::table('github_followers')
                    ->where('id', $follower->id)
                    ->update(['user_id' => $user->id]);
            }
        }
        
        // github_follower_detailsテーブルの既存データにuser_idを設定
        $followerDetails = DB::table('github_follower_details')->whereNull('user_id')->get();
        
        foreach ($followerDetails as $detail) {
            // target_usernameからユーザーを特定
            $user = DB::table('users')
                ->where('github_owner', $detail->target_username)
                ->where('github_settings_completed', true)
                ->first();
            
            if ($user) {
                DB::table('github_follower_details')
                    ->where('id', $detail->id)
                    ->update(['user_id' => $user->id]);
            }
        }
        
        // user_idをNOT NULLに変更
        Schema::table('github_followers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
        
        Schema::table('github_follower_details', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // user_idをNULLABLEに戻す
        Schema::table('github_followers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
        
        Schema::table('github_follower_details', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
        
        // user_idをクリア
        DB::table('github_followers')->update(['user_id' => null]);
        DB::table('github_follower_details')->update(['user_id' => null]);
    }
};
