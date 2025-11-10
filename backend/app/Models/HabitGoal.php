<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HabitGoal extends Model
{
    use SoftDeletes;

    protected $table = 'habit_goals';

    protected $fillable = [
        'user_id',
        'is_linked',
        'act_category_id',
        'color_name',
        'hex_color_code',
        'title',
        'detail',
        'duration_minutes',
    ];

    protected $casts = [
        'is_linked' => 'boolean',
        'duration_minutes' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 行動カテゴリとのリレーション（紐づけ時）
     */
    public function actCategory()
    {
        return $this->belongsTo(ActCategory::class, 'act_category_id');
    }

    public function habitLogs()
    {
        return $this->hasMany(HabitLog::class);
    }

    /**
     * 習慣化目標をアップサート（登録・更新・削除）する。
     *
     * - 引数 $habitGoals: クライアントから送信された習慣化目標配列
     * - 登録・更新・削除を1トランザクションで実行する
     * - 削除は、現DBに存在するがリクエストに含まれないIDを対象とする
     *
     * @param int $userId 操作ユーザーのID
     * @param array $habitGoals 登録・更新・削除対象の習慣化目標配列
     */
    public function upDelete($userId, $habitGoals)
    {
        $strHabitGoals = print_r($habitGoals, true);
        Log::info("【upDelete開始】user_id: {$userId}, habitGoals: {$strHabitGoals}");

        DB::transaction(function () use ($userId, $habitGoals) {
            $existingIds = HabitGoal::where('user_id', $userId)
                ->pluck('id')->all();

            $incomingIds = array_values(array_filter(array_map(fn($it) => $it['id'] ?? null, $habitGoals)));
            $toDelete = array_diff($existingIds, $incomingIds);
            if (!empty($toDelete)) {
                HabitGoal::where('user_id', $userId)
                    ->whereIn('id', $toDelete)
                    ->delete();
            }

            foreach ($habitGoals as $item) {

                $requiredFields = ['color_name', 'hex_color_code', 'title', 'detail', 'duration_minutes'];
                foreach ($requiredFields as $field) {
                    if (!array_key_exists($field, $item)) {
                        throw new \InvalidArgumentException("Missing required field: {$field}");
                    }
                }

                if (!empty($item['id'])) {
                    $affected = HabitGoal::where('user_id', $userId)
                        ->where('id', $item['id'])
                        ->update([
                            'color_name'       => $item['color_name'],
                            'hex_color_code'   => $item['hex_color_code'],
                            'title'            => $item['title'],
                            'detail'           => $item['detail'],
                            'duration_minutes' => $item['duration_minutes']
                        ]);

                    if ($affected === 0) {
                        Log::warning("更新対象のレコードが見つかりません: user_id={$userId}, id={$item['id']}");
                    }
                } else {
                    $requiredCreateFields = ['is_linked', 'act_category_id'];
                    foreach ($requiredCreateFields as $field) {
                        if (!array_key_exists($field, $item)) {
                            throw new \InvalidArgumentException("Missing required field for create: {$field}");
                        }
                    }

                    HabitGoal::create([
                        'user_id'          => $userId,
                        'is_linked'        => $item['is_linked'],
                        'act_category_id'  => $item['act_category_id'],
                        'color_name'       => $item['color_name'],
                        'hex_color_code'   => $item['hex_color_code'],
                        'title'            => $item['title'],
                        'detail'           => $item['detail'],
                        'duration_minutes' => $item['duration_minutes'],
                    ]);
                }
            }
        });
    }
}
