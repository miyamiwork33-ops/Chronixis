<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\HabitLog;
use App\Http\Controllers\Api\HabitGoalController;
use App\Http\Requests\Api\StoreHabitLogRequest;

class HabitLogController extends Controller
{
    /**
     * 初期化処理
     * 
     * クライアント初期表示用に以下のデータを返す：
     * - 習慣目標（HabitGoal）
     * - 行動カテゴリ（ActCategory）
     * - 各習慣に紐づく記録（HabitLog）
     * 
     * 現状では HabitGoalController の init() を直接呼び出しており、
     * 将来的には HabitService などにロジックを分離する想定。
     *
     * @return \Illuminate\Http\JsonResponse JSON形式のレスポンスを返す
     */
    public function init()
    {
        $user = Auth::user();
        $userId = $user->id;

        $habitLogs = HabitLog::where('user_id', $userId)->get();

        // TODO: 一時的に他のコントローラーを直接呼び出しています。
        // HabitServiceなどのサービスクラスにロジックを切り出すか検討。
        $habitGoalController = new HabitGoalController();
        $response = $habitGoalController->init();
        $data = $response->getData(true);

        $habits = [];

        if (
            is_array($data["habitGoals"]) && !empty($data["habitGoals"]) &&
            is_array($data["actCategories"]) && !empty($data["actCategories"])
        ) {
            foreach ($data["habitGoals"] as $habitGoal) {
                $logs = getLogsByID($habitGoal["id"], $habitLogs);
                if ($habitGoal["is_linked"] == false) {
                    $habits[] = [
                        "habit_goal_id" => $habitGoal["id"],
                        "title" => $habitGoal["title"],
                        "duration_minutes" => $habitGoal["duration_minutes"],
                        "detail" => $habitGoal["detail"],
                        "hex_color_code" => $habitGoal["hex_color_code"],
                        "habitLogs" => $logs
                    ];
                } else {
                    $title = "";
                    $hex_color_code = "";
                    foreach ($data["actCategories"] as $actCategory) {
                        if (
                            $habitGoal["act_category_id"] ==
                            $actCategory["id"]
                        ) {
                            $title = $actCategory["activity"];
                            $hex_color_code = $actCategory["hex_color_code"];
                        }
                    }
                    $habits[] = [
                        "habit_goal_id" => $habitGoal["id"],
                        "title" => $title,
                        "duration_minutes" => $habitGoal["duration_minutes"],
                        "detail" => $habitGoal["detail"],
                        "hex_color_code" => $hex_color_code,
                        "habitLogs" => $logs
                    ];
                }
            }
        } else if (is_array($data["habitGoals"]) && count($data["habitGoals"]) > 0) {
            $habits = $data["habitGoals"];
        }

        $data["habits"] = $habits;

        return response()->json($data);
    }

    /**
     * 習慣記録（HabitLog）の登録処理
     * 
     * リクエストは StoreHabitLogRequest で事前バリデーション済み。
     * - バリデーションに成功したデータを HabitLog::store() 経由で登録
     * - 登録結果を取得して返却
     * 
     * @param \App\Http\Requests\Api\StoreHabitLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreHabitLogRequest $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $data = [
            'status'    => 'ng',
            'msgArray'  => [],
            'habitLogs' => []
        ];

        try {
            $logs = [];
            $habitLogs = $request->validated();
            $habitLog = new HabitLog();
            $id = $habitLog->store($userId, $habitLogs);
            $logs = HabitLog::where('id', $id)->get();
            if ($logs->isEmpty()) {
                throw new \Exception('作成された習慣記録が見つかりません');
            }
        } catch (\Throwable $th) {
            $msg = '習慣記録の登録に失敗しました';
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
        $data['habitLogs'] = $logs->first();
        return response()->json($data);
    }
}

/**
 * 指定されたhabit_goal_idでhabitLogsをフィルタリング
 * 
 * @param int $habitGoalID
 * @param \Illuminate\Database\Eloquent\Collection $habitLogs
 * @return array
 */
function getLogsByID($habitGoalID, $habitLogs)
{
    if ($habitLogs->isEmpty()) {
        return [];
    }

    return $habitLogs->filter(function ($habitLog) use ($habitGoalID) {
        return $habitLog->habit_goal_id == $habitGoalID;
    })->values()->all();
}
