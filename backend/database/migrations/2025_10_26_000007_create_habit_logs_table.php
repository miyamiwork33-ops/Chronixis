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
        Schema::create('habit_logs', function (Blueprint $table) {
            $table->id()->comment('習慣記録ID');
            $table->unsignedBigInteger('user_id')->comment('ユーザID');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('habit_goal_id')->comment('習慣化目標ID');
            $table->foreign('habit_goal_id')->references('id')->on('habit_goals')->onDelete('cascade');
            $table->datetime('log_time')->comment('記録日時');
            $table->boolean('is_achieved')->default(false)->comment('達成したか');
            $table->unsignedInteger('execution_time')->default(0)->comment('実行時間（秒単位）');
            $table->datetime('created_at')->useCurrent()->comment('登録日時');
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('変更日時');
            $table->dateTime('deleted_at')->nullable()->comment('論理削除日時');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
    }
};
