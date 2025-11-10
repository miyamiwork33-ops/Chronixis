<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\InitScheduleRequest;
use App\Http\Requests\Api\UpsertScheduleRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\ActCategory;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * 一日の予定初期データ取得
     * - 初回(isInit=true)の場合、行動カテゴリ一覧も取得
     * - 当日のスケジュール情報を取得
     * 
     * @param InitScheduleRequest $request バリデーション済みのリクエスト     
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
     *     "schedules": [                // 一日の予定配列
     *        {
     *          "id": 30,                // 一日の予定ID
     *          "start_time": "00:00",   // 行動開始時間
     *          "end_time": "06:00",     // 行動終了時間
     *          "color": "#3949AB",    // 色
     *          "activity": "睡眠",      // 行動内容
     *          "act_category_id": 1     // 行動カテゴリID
     *        }
     *      ]
     *   }
     * ] 
     * 
     */
    public function init(InitScheduleRequest $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $data = [
            'status'        => 'ng',
            'msgArray'      => [],
            'actCategories' => [],
            'schedules'     => [],
        ];

        $validated = $request->validated();
        $targetDate = $validated['targetDate'];

        try {
            if ($validated['isInit']) {
                $data['actCategories'] = ActCategory::all();

                if ($data['actCategories']->isEmpty()) {
                    $data['msgArray'][] =
                        [
                            "severity" => "error",
                            "summary"  => "入力エラー",
                            "detail"   => '行動カテゴリを登録してください。',
                            "life"     => 5000,
                        ];
                    return response()->json($data, 400);
                }
            }

            $data['schedules'] = Schedule::with('actCategory')
                ->where([
                    ['user_id', $userId],
                    ['target_date', $targetDate],
                ])
                ->get()
                ->map(function ($schedule) {
                    return [
                        'id'              => $schedule->id,
                        'start_time'      => Carbon::parse($schedule->start_time)->format('H:i'),
                        'end_time'        => Carbon::parse($schedule->end_time)->format('H:i'),
                        'color'           => $schedule->actCategory?->hex_color_code,
                        'activity'        => $schedule->actCategory?->activity,
                        'act_category_id' => $schedule->act_category_id,
                    ];
                })
                ->sortBy('start_time')
                ->values();
        } catch (\Throwable $th) {
            $msg = 'スケジュールの取得に失敗しました';
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
     * スケジュール登録・更新処理
     * - 同一日付のスケジュールをまとめて更新
     * - 時間形式・カテゴリ存在チェックを実施
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
    public function upsert(UpsertScheduleRequest $request)
    {
        $user = Auth::user();
        $data = [
            'status'   => 'ng',
            'msgArray' => []
        ];

        $userId = $user->id;
        $schedules = $request->validated();
        if (empty($schedules)) {
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
            $targetDate = $schedules[0]['target_date'] ?? null;
            if (!$targetDate) {
                $msg = "target_dateが指定されていません";
                Log::error($msg);
                $data['msgArray'][] = [
                    "severity" => "error",
                    "summary"  => "入力エラー",
                    "detail"   => $msg,
                    "life"     => 5000,
                ];
                return response()->json($data, 422);
            }

            $schedule = new Schedule();
            $schedule->upDelete($userId, $targetDate, $schedules);
        } catch (\Throwable $th) {
        } catch (\Throwable $th) {
            $msg = "スケジュールの登録・更新に失敗しました";
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
}
