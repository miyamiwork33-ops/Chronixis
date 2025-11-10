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
        Schema::create('act_categories', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->comment('行動カテゴリID');
            $table->unsignedBigInteger('user_id')->comment('ユーザID');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('activity', 30)->comment('行動内容');
            $table->string('color_name', 30)->comment('色名称:auraColors の name（例：Blue-700）');
            $table->string('hex_color_code', 10)->comment('16進数色コード:auraColors の hex（例：#1E88E5）');
            $table->string('text_color_code', 10)->comment('文字色:auraColors の textColor（例：#FFFFFF）');
            $table->datetime('created_at')->useCurrent()->comment('登録日時');
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('変更日時');
            $table->datetime('deleted_at')->nullable()->comment('論理削除日時');

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('act_categories');
    }
};
