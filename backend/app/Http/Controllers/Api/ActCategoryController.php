<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ActCategory;
use App\Http\Requests\Api\UpsertActCategoryRequest;

class ActCategoryController extends Controller
{
    /**
     * 行動カテゴリの登録・更新・削除を一括で行うアップサート処理。
     *
     * - リクエスト配列（カテゴリ一覧）を受け取る
     * - ユーザーID一致を検証
     * - モデルの upDelete() に処理を委譲
     * 
     * ※ 各カテゴリは ID の有無で「新規 or 更新」を自動判定。
     * ※ 削除は「DB上にあるがリクエストに含まれないID」を削除対象とみなす。
     *
     * @param UpsertActCategoryRequest $request バリデーション済みのカテゴリ情報を含むリクエスト
     * @return \Illuminate\Http\JsonResponse JSON形式のレスポンスを返す
     * 
     * 例：
     * [
     *   {
     *     "status": 'ok',                           // 状態 ('ok' または 'ng')
     *     "msgArray": [                             // メッセージ
     *       {
     *         "severity": "danger",                 // 重大度
     *         "summary": "サーバーエラー",           // 概要
     *         "detail": "",                         // 詳細メッセージ
     *         "life": 5000,                         // 秒
     *        }
     *     ]
     *   }
     * ]
     */
    public function upsert(UpsertActCategoryRequest $request)
    {
        $user = Auth::user();

        $data = [
            'status'   => 'ng',
            'msgArray' => []
        ];

        $userId = $user->id;

        try {
            $categories = $request->validated();
            if (empty($categories)) {
                $data['msgArray'][] =
                    [
                        "severity" => "error",
                        "summary" => "入力エラー",
                        "detail" => '登録データがありません',
                        "life" => 5000,
                    ];
                return response()->json($data, 422);
            }

            foreach ($categories as $category) {
                if (isset($category['id'])) {
                    $model = ActCategory::find($category['id']);
                    if ($model && $model->user_id !== $user->id) {
                        $data['msgArray'][] =
                            [
                                "severity" => "error",
                                "summary" => "権限エラー",
                                "detail" => '他ユーザーのデータは変更できません',
                                "life" => 5000,
                            ];
                        return response()->json($data, 403);
                    }
                }
            }

            $actCategory = new ActCategory();
            $actCategory->upDelete($categories, $userId);
            $data['status'] = 'ok';
            return response()->json($data);
        } catch (\Exception $th) {
            $msg = '行動カテゴリの登録に失敗しました';
            Log::error($msg . ': ' . $th->getMessage());
            $data['msgArray'][] = [
                "severity" => "error",
                "summary" => "サーバーエラー",
                "detail" => $msg,
                "life" => 5000,
            ];
            return response()->json($data, 500);
        }
    }

    /**
     * ログインユーザーに紐づく、論理削除されていない行動カテゴリを全件取得する。
     *
     * `ActCategory` モデルから `user_id` が一致するレコードを取得し、
     * JSON形式でレスポンスとして返す。
     * ※ `SoftDeletes` を使用している場合、論理削除されたデータは自動的に除外される。
     * また、schedules、habit_goalsの両テーブルで act_category_id が使用されているかどうか
     * のフラグ(can_delete)を追加し、GUIで削除できないようにする。
     *
     * @return \Illuminate\Http\JsonResponse 行動カテゴリ一覧（配列形式）
     *
     * 例：
     * "status": "ok",                          // 状態
     * "msgArray": [                            // メッセージ
     *   {
     *     "severity": "error",                 // 重大度
     *     "summary": "サーバーエラー",          // 概要
     *     "detail": "",                        // 詳細メッセージ
     *     "life": 5000,                        // 秒
     *    }
     * ],
     * "categories": [
     *   {
     *     "id": 1,                             // 行動カテゴリID
     *     "user_id": 5,                        // ユーザID
     *     "activity": "運動",                  // 行動内容
     *     "color_name": "Indigo-700",          // 色名称
     *     "hex_color_code": "#3949AB",       // 16進数色コード
     *     "text_color_code": "#FFFFFF",      // 文字色
     *     "created_at": "2025-10-27 14:51:17", // 登録日時
     *     "updated_at": "2025-10-27 14:51:17", // 変更日時
     *     "deleted_at": null                   // 論理削除日時（未削除ならnull）
     *     "can_delete": false                  // 他のテーブルでact_category_idが参照されている場合、削除できない
     *   },
     *   ...
     * ]
     */
    public function getAllData()
    {
        $user = Auth::user();
        $data = [
            'status'   => 'ng',
            'msgArray' => []
        ];

        try {
            $categories = ActCategory::where('user_id', $user->id)->get();

            // 各カテゴリに対して、他テーブルで使用中かを確認して削除可否フラグを付与
            // can_delete = true なら削除ボタンを有効化、false なら無効化
            if (!$categories->isEmpty()) {
                $categoryIds = $categories->pluck('id');

                // 使用中のカテゴリIDを一括取得
                $usedInSchedules = \App\Models\Schedule::whereIn('act_category_id', $categoryIds)
                    ->pluck('act_category_id')
                    ->unique();
                $usedInHabitGoals = \App\Models\HabitGoal::whereIn('act_category_id', $categoryIds)
                    ->pluck('act_category_id')
                    ->unique();
                $usedCategoryIds = $usedInSchedules->merge($usedInHabitGoals)->unique();

                $categories = $categories->map(function ($category) use ($usedCategoryIds) {
                    $category->can_delete = !$usedCategoryIds->contains($category->id);
                    return $category;
                });
            }
        } catch (\Exception $th) {
            $msg = '行動カテゴリの取得に失敗しました';
            Log::error($msg . ': ' . $th->getMessage());
            $data['msgArray'][] = [
                "severity" => "error",
                "summary" => "サーバーエラー",
                "detail" => $msg,
                "life" => 5000,
            ];
            return response()->json($data, 500);
        }

        $data['status'] = 'ok';
        $data['categories'] = $categories;
        return response()->json($data);
    }
}
