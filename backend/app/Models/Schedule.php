<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Schedule extends Model
{
    use SoftDeletes; // 論理削除

    protected $table = 'schedules'; // テーブル名を明示

    // ホワイトリスト：一括代入（mass assignment）可能なカラムを指定
    protected $fillable = [
        'user_id',
        'target_date',
        'start_time',
        'end_time',
        'act_category_id',
    ];

    // 論理削除用のカラム
    protected $casts = [
        'deleted_at' => 'datetime',
    ];


    public function actCategory()
    {
        return $this->belongsTo(ActCategory::class, 'act_category_id');
    }

    /**
     * 一日の予定をアップサート（登録・更新・削除）する。
     *
     * - 引数 $schedules: クライアントから送信された予定配列
     * - 登録・更新・削除を1トランザクションで実行する
     * - 削除は、現DBに存在するがリクエストに含まれないIDを対象とする
     *
     * @param int $userId 操作ユーザーのID
     * @param date $targetDate 対象日時
     * @param array $schedules 登録・更新・削除対象の予定配列
     */
    public function upDelete($userId, $targetDate, $schedules)
    {
        $strSchedules = print_r($schedules, true);
        Log::info("【upDelete開始】user_id: {$userId}, target_date: {$targetDate}, schedules: {$strSchedules}");

        DB::transaction(function () use ($userId, $targetDate, $schedules) {
            $existingIds = Schedule::where('user_id', $userId)
                ->where('target_date', $targetDate)
                ->pluck('id')->all();

            $incomingIds = array_values(array_filter(array_map(fn($it) => $it['id'] ?? null, $schedules)));
            $toDelete = array_diff($existingIds, $incomingIds);
            if (!empty($toDelete)) {
                Schedule::where('user_id', $userId)
                    ->where('target_date', $targetDate)
                    ->whereIn('id', $toDelete)
                    ->delete();
            }

            foreach ($schedules as $item) {
                if (!empty($item['id'])) {
                    Schedule::where('user_id', $userId)
                        ->where('target_date', $targetDate)
                        ->where('id', $item['id'])
                        ->update([
                            'start_time'      => $item['start_time'],
                            'end_time'        => $item['end_time'],
                            'act_category_id' => $item['act_category_id'],
                        ]);
                } else {
                    Schedule::create([
                        'user_id'         => $userId,
                        'target_date'     => $targetDate,
                        'start_time'      => $item['start_time'],
                        'end_time'        => $item['end_time'],
                        'act_category_id' => $item['act_category_id'],
                    ]);
                }
            }
        });
    }
}
