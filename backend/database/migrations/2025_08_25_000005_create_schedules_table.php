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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id()->comment('予定ID');
            $table->unsignedBigInteger('user_id')->comment('ユーザID');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('act_category_id')->nullable()->comment('行動カテゴリID');
            $table->foreign('act_category_id')->references('id')->on('act_categories')->onDelete('cascade');
            $table->date('target_date')->comment('対象日');
            $table->time('start_time')->comment('行動開始時間');
            $table->time('end_time')->comment('行動終了時間');
            $table->datetime('created_at')->useCurrent()->comment('登録日時');
            $table->datetime('updated_at')->useCurrentOnUpdate()->comment('変更日時');
            $table->dateTime('deleted_at')->nullable()->comment('論理削除日時');
            $table->index('user_id');
            $table->index('target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
