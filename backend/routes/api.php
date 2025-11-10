<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * 行動カテゴリ
 */
use App\Http\Controllers\Api\ActCategoryController;
Route::middleware(['auth:sanctum'])->post('/categories/upsert', [ActCategoryController::class, 'upsert']);
Route::middleware(['auth:sanctum'])->get('/categories/getAllData', [ActCategoryController::class, 'getAllData']);

/**
 * 予定
*/
use App\Http\Controllers\Api\ScheduleController;
Route::middleware(['auth:sanctum'])->post('/schedules/init', [ScheduleController::class, 'init']);
Route::middleware(['auth:sanctum'])->post('/schedules/upsert', [ScheduleController::class, 'upsert']);

/**
 * 習慣目標
 */
use App\Http\Controllers\Api\HabitGoalController;
Route::middleware(['auth:sanctum'])->get('/habit/goal/init', [HabitGoalController::class, 'init']);
Route::middleware(['auth:sanctum'])->post('/habit/goal/upsert', [HabitGoalController::class, 'upsert']);

use App\Http\Controllers\Api\HabitLogController;
Route::middleware(['auth:sanctum'])->get('/habit/log/init', [HabitLogController::class, 'init']);
Route::middleware(['auth:sanctum'])->post('/habit/log/store', [HabitLogController::class, 'store']);