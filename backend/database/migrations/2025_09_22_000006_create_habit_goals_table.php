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
        Schema::create('habit_goals', function (Blueprint $table) {
            $table->id()->comment('習慣化目標ID');
            $table->unsignedBigInteger('user_id')->comment('ユーザID');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->boolean('is_linked')->default(false)->comment('行動との紐づけ有無');
            $table->integer('act_category_id')->nullable()->comment('行動カテゴリID');
            $table->foreign('act_category_id')->references('id')->on('act_categories')->onDelete('cascade');
            $table->string('color_name', 30)->nullable()->comment('色名称:auraColors の name（例：Blue-700）');
            $table->string('hex_color_code', 10)->nullable()->comment('16進数色コード:auraColors の hex（例：#1E88E5）');
            $table->string('title', 30)->nullable()->comment('タイトル');
            $table->text('detail')->nullable()->comment('詳細内容');
            $table->unsignedInteger('duration_minutes')->default(0)->comment('所要時間（分）');
            $table->datetime('created_at')->useCurrent()->comment('登録日時');
            $table->datetime('updated_at')->useCurrentOnUpdate()->comment('変更日時');
            $table->dateTime('deleted_at')->nullable()->comment('論理削除日時');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habit_goals');
    }
};
