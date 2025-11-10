<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class HabitLog extends Model
{
    use SoftDeletes;

    protected $table = 'habit_logs';

    protected $fillable = [
        'habit_goal_id',
        'log_time',
        'is_achieved',
        'execution_time',
    ];
    protected $guarded = ['user_id'];

    protected $casts = [
        'is_achieved'    => 'boolean',
        'execution_time' => 'integer',
    ];

    public static function store($userId, $habitLogs)
    {
        $strHabitLogs = print_r($habitLogs, true);
        Log::info("【store開始】user_id: {$userId}, habitLogs: {$strHabitLogs}");

        // 入力検証
        $required = ['habit_goal_id', 'log_time', 'is_achieved', 'execution_time'];
        foreach ($required as $field) {
            if (!isset($habitLogs[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
        }

        try {
            $habitLog = new self();
            $habitLog->fill([
                'habit_goal_id'     => (int) $habitLogs['habit_goal_id'],
                'log_time'          => Carbon::parse($habitLogs['log_time'])->format('Y-m-d H:i:s'),
                'is_achieved'       => $habitLogs['is_achieved'],
                'execution_time'    => $habitLogs['execution_time']
            ]);
            $habitLog->user_id = $userId;
            $habitLog->save();

            return $habitLog->id;
        } catch (\Exception $e) {
            Log::error("HabitLog作成エラー: {$e->getMessage()}");
            throw $e;
        }
    }
}
