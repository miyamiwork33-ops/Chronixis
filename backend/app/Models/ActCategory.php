<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActCategory extends Model
{
    use SoftDeletes; // 論理削除

    protected $table = 'act_categories'; // テーブル名を明示

    protected $fillable = [
        'user_id',
        'activity',
        'color_name',
        'hex_color_code',
        'text_color_code',
    ];

    protected $dates = ['deleted_at']; // 論理削除用のカラム

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'act_category_id');
    }

    /**
     * 行動カテゴリをアップサート（登録・更新・削除）する。
     *
     * - 引数 $categories: クライアントから送信されたカテゴリ配列
     * - 登録・更新・削除を1トランザクションで実行する
     * - 削除は、現DBに存在するがリクエストに含まれないIDを対象とする
     * - 他テーブルで参照中のカテゴリは削除スキップし警告メッセージを返す
     *
     * @param array $categories 登録・更新・削除対象のカテゴリ配列
     * @param int $userId 操作ユーザーのID
     */
    public function upDelete($categories, $userId)
    {
        $strCategories = print_r($categories, true);
        Log::info("【upDelete開始】user_id: {$userId}, categories: {$strCategories}");

        DB::transaction(function () use ($categories, $userId) {

            // 削除
            $existingIds = self::where('user_id', $userId)->pluck('id')->all();
            $incomingIds = collect($categories)->pluck('id')->filter()->values()->all();
            $toDelete = array_diff($existingIds, $incomingIds);
            if (!empty($toDelete)) {
                foreach ($toDelete as $id) {
                    $isUsed = \App\Models\Schedule::where('act_category_id', $id)->exists()
                        || \App\Models\HabitGoal::where('act_category_id', $id)->exists();

                    if ($isUsed) {
                        $msg = "行動カテゴリID {$id} は他テーブルで使用されているため削除できません。";
                        throw new \Exception($msg);
                    }

                    self::where('user_id', $userId)
                        ->where('id', $id)
                        ->delete();
                }
            }

            foreach ($categories as $category) {
                // 必須フィールドの検証
                if (!isset($category['activity']) || empty($category['activity'])) {
                    $msg = "行動内容が空です: " . json_encode($category);
                    throw new \Exception($msg);
                }

                if (!isset($category['id']) || $category['id'] === null || $category['id'] === '') { // 新規登録
                    self::create([
                        'user_id'         => $userId,
                        'activity'        => $category['activity'],
                        'color_name'      => $category['color_name'],
                        'hex_color_code'  => $category['hex_color_code'],
                        'text_color_code' => $category['text_color_code'],
                    ]);
                } else {
                    // 更新
                    $model = self::where('id', $category['id'])
                        ->where('user_id', $userId)
                        ->first();
                    if (!$model) {
                        $msg = "行動カテゴリが見つからないか、アクセス権限がありません。user_id: {$userId}, act_category_id: {$category['id']}";
                        Log::warning($msg);
                        throw new \Exception($msg);
                    }
                    $model->update([
                        'activity'       => $category['activity'],
                        'color_name'     => $category['color_name'],
                        'hex_color_code' => $category['hex_color_code'],
                        'text_color_code' => $category['text_color_code'],
                    ]);
                }
            }
        });
    }
}
