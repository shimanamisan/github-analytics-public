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
        Schema::create('github_views', function (Blueprint $table) {
            $table->id();
            $table->string('project');
            $table->date('date');
            $table->integer('count');
            $table->integer('uniques');
            $table->timestamps();
            
            $table->unique(['project', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_views');
    }
};
