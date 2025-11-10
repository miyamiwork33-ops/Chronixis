<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ActCategory;
use App\Models\HabitGoal;
use App\Http\Requests\Api\UpsertHabitGoalRequest;

class HabitGoalController extends Controller
{
    /**
     * 目標初期データ取得
     * - 行動カテゴリ一覧も取得
     * 
     * @param UpsertActCategoryRequest $request バリデーション済みのカテゴリ情報を含むリクエスト
     * @return \Illuminate\Http\JsonResponse JSON形式のレスポンスを返す
     * 
     * 例：
     * [
     *   {
     *     "status": 'ok',                      // 状態 ('ok' または 'ng')
     *     "msgArray": [                        // メッセージ
     *       {
     *         "severity": "danger",            // 重大度
     *         "summary": "サーバーエラー",      // 概要
     *         "detail": "",                    // 詳細メッセージ
     *         "life": 5000,                    // 秒
     *        }
     *     ]
     *     "actCategories": [                                // 行動カテゴリ配列
     *        {
     *          "id": 1,                                     // 行動カテゴリID
     *          "user_id": 1,                                // ユーザID
     *          "activity": "睡眠",                          // 行動内容
     *          "color_name": "Indigo-700",                  // 色名
     *          "hex_color_code": "#3949AB",               // 16進数色コード
     *          "text_color_code": "#FFFFFF",              // 文字色
     *          "created_at": "2025-10-27T14:51:17.000000Z", // 登録日時
     *          "updated_at": "2025-10-27T14:51:17.000000Z", // 更新日時
     *          "deleted_at": null                           // 論理削除日時
     *        }
     *      ],
     *     "habitGoals": [                            // 目標配列
     *        {
     *          "id": 1,                              // 習慣化目標ID
     *          "is_linked": true,                    // 行動との紐づけ有無
     *          "act_category_id": 3,                 // 行動カテゴリID
     *          "color_name": null,                   // 色名
     *          "hex_color_code": null,               // 色コード
     *          "title": null,                        // タイトル
     *          "detail": "毎日30分のストレッチや運動", // 詳細内容
     *          "duration_minutes": 30                // 所要時間（分）
     *          "can_delete": true                    // 削除してOK
     *        },
     *      ]
     *   }
     * ] 
     * 
     */
    public function init()
    {
        $user = Auth::user();
        $userId = $user->id;

        $data = [
            'status'        => 'ng',
            'msgArray'      => [],
            'actCategories' => [],
            'habitGoals'    => [],
        ];

        try {
            $data['actCategories'] = ActCategory::all();

            if ($data['actCategories']->isEmpty()) {
                $data['msgArray'][] =
                    [
                        "severity" => "error",
                        "summary" => "入力エラー",
                        "detail" => '行動カテゴリを登録してください。',
                        "life" => 5000,
                    ];
                return response()->json($data, 422);
            }

            $habitGoals = HabitGoal::where('user_id', $userId)
                ->withCount('habitLogs')
                ->get();
            $data['habitGoals'] = $habitGoals->map(function ($goal) {
                return [
                    'id'               => $goal->id,
                    'is_linked'        => $goal->is_linked,
                    'act_category_id'  => $goal->act_category_id,
                    'color_name'       => $goal->color_name,
                    'hex_color_code'   => $goal->hex_color_code,
                    'title'            => $goal->title,
                    'detail'           => $goal->detail,
                    'duration_minutes' => $goal->duration_minutes,
                    'can_delete'       => $goal->habit_logs_count === 0,
                ];
            });
        } catch (\Throwable $th) {
            $msg = '習慣化目標の取得に失敗しました';
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
        return response()->json($data);
    }

    /**
     * 習慣化目標の登録・更新処理
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
     *        },
     *     "habitGoals": [                            // 目標配列
     *        {
     *          "id": 1,                              // 習慣化目標ID
     *          "is_linked": true,                    // 行動との紐づけ有無
     *          "act_category_id": 3,                 // 行動カテゴリID
     *          "color_name": null,                   // 色名
     *          "hex_color_code": null,               // 色コード
     *          "title": null,                        // タイトル
     *          "detail": "毎日30分のストレッチや運動", // 詳細内容
     *          "duration_minutes": 30                // 所要時間（分）
     *          "can_delete": true                    // 削除してOK
     *        },
     *      ]
     *     ]
     *   }
     * ]
     */
    public function upsert(UpsertHabitGoalRequest $request)
    {
        $user = Auth::user();
        $data = [
            'status'   => 'ng',
            'msgArray' => []
        ];

        $userId = $user->id;
        $habitGoals = $request->validated();
        if (empty($habitGoals)) {
            $data['msgArray'][] =
                [
                    "severity" => "error",
                    "summary" => "入力エラー",
                    "detail" => '登録データがありません',
                    "life" => 5000,
                ];
            return response()->json($data, 422);
        }

        try {
            $habitGoal = new HabitGoal();
            $habitGoal->upDelete($userId, $habitGoals);
            $habitGoals = HabitGoal::where('user_id', $userId)
                ->withCount('habitLogs')
                ->get();
            $data['habitGoals'] = $habitGoals->map(function ($goal) {
                return [
                    'id'               => $goal->id,
                    'is_linked'        => $goal->is_linked,
                    'act_category_id'  => $goal->act_category_id,
                    'color_name'       => $goal->color_name,
                    'hex_color_code'   => $goal->hex_color_code,
                    'title'            => $goal->title,
                    'detail'           => $goal->detail,
                    'duration_minutes' => $goal->duration_minutes,
                    'can_delete'       => $goal->habit_logs_count === 0,
                ];
            });
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $data['msgArray'] = 'サーバーエラー';
            return response()->json($data, 500);
        }

        $data['status'] = 'ok';

        return response()->json($data);
    }
}
