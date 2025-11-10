<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ActCategory;
use App\Models\HabitGoal;
use App\Models\HabitLog;
use Illuminate\Validation\Validator;


class UpsertHabitGoalRequest extends FormRequest
{
    /**
     * 認可チェック
     * 認証済みユーザーであれば true を返す。
     * false の場合は 403 エラーとなりコントローラが実行されない。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * バリデーション前にデータを整形
     * 
     * - is_linked=true の場合: color_name, hex_color_code, title は null
     * - is_linked=false の場合: act_category_id は null
     */
    protected function prepareForValidation()
    {
        $items = $this->all();

        foreach ($items as $key => $item) {
            $isLinked = filter_var($item['is_linked'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($isLinked) {
                $items[$key]['color_name'] = null;
                $items[$key]['hex_color_code'] = null;
                $items[$key]['title'] = null;
            } else {
                $items[$key]['act_category_id'] = null;
            }
        }

        $this->merge($items);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '*.id'               => ['nullable', 'integer', 'exists:habit_goals,id'],
            '*.is_linked'        => ['required', 'boolean'],
            '*.act_category_id'  => [
                'nullable',
                'integer',
                'exists:act_categories,id',
            ],
            '*.color_name'       => ['nullable', 'string', 'max:30'],
            '*.hex_color_code'   => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            '*.title'            => ['nullable', 'string', 'max:30'],
            '*.detail'           => ['nullable', 'string', 'max:1000'],
            '*.duration_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ];
    }

    /**
     * バリデーション後の追加ロジック
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $userId = $this->user()->id;
            $habitGoals = collect($this->all());

            // 1️. is_linked=true の場合、act_category_id 必須チェック
            foreach ($habitGoals as $i => $goal) {
                if (!empty($goal['is_linked']) && empty($goal['act_category_id'])) {
                    $validator->errors()->add(
                        "{$i}.act_category_id",
                        "紐づけ有効な習慣は行動カテゴリを指定してください。"
                    );
                }
            }

            // 2️. act_category_id が自分のカテゴリか確認
            $userCategoryIds = ActCategory::where('user_id', $userId)->pluck('id')->toArray();
            foreach ($habitGoals as $i => $goal) {
                if (($goal['is_linked'] ?? false) && !empty($goal['act_category_id']) && !in_array($goal['act_category_id'], $userCategoryIds, true)) {
                    $validator->errors()->add(
                        "{$i}.act_category_id",
                        "他ユーザーの行動カテゴリを参照することはできません。"
                    );
                }
            }

            // 3️. act_category_id の重複登録チェック（同じカテゴリに複数habit_goal禁止）
            $linkedIds = $habitGoals
                ->where('is_linked', true)
                ->pluck('act_category_id')
                ->filter()
                ->toArray();

            $duplicates = array_diff_assoc($linkedIds, array_unique($linkedIds));
            if (!empty($duplicates)) {
                $validator->errors()->add(
                    'act_category_id',
                    '同一の行動カテゴリに複数の習慣化目標を紐づけることはできません。'
                );
            }

            // 4. 所有者確認: habit_goal.id が自分のものかチェック
            $habitGoalIds = $habitGoals->pluck('id')->filter()->toArray();
            if (!empty($habitGoalIds)) {
                $userHabitGoalIds = HabitGoal::where('user_id', $userId)
                    ->whereIn('id', $habitGoalIds)
                    ->pluck('id')
                    ->toArray();

                foreach ($habitGoals as $i => $goal) {
                    if (!empty($goal['id']) && !in_array($goal['id'], $userHabitGoalIds, true)) {
                        $validator->errors()->add(
                            "{$i}.id",
                            "他ユーザーの習慣化目標を参照することはできません。"
                        );
                    }
                }
            }

            // 5. 削除チェック（habit_logsで使用中のhabit_goalは削除禁止）
            $existingIds = HabitGoal::where('user_id', $userId)->pluck('id')->toArray();
            $incomingIds = $habitGoals->pluck('id')->filter()->toArray();
            $toDelete = array_diff($existingIds, $incomingIds);

            if (!empty($toDelete)) {
                $usedIds = HabitLog::whereIn('habit_goal_id', $toDelete)->pluck('habit_goal_id')->toArray();
                if (!empty($usedIds)) {
                    $validator->errors()->add(
                        'habit_goal_id',
                        '使用中の習慣化目標（habit_logsに記録があるもの）は削除できません。'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            '*.is_linked.required'            => '紐づけ有無は必須です。',
            '*.is_linked.boolean'             => '紐づけ有無はtrueまたはfalseで指定してください。',
            '*.act_category_id.exists'        => '指定された行動カテゴリが存在しません。',
            '*.act_category_id.required_if'   => '連携する場合は行動カテゴリを選択してください。',
            '*.act_category_id.prohibited_if' => '独自の習慣では行動カテゴリを設定できません。',
            '*.hex_color_code.regex'          => '色コードは「#」から始まる6桁の16進数で指定してください。',
            '*.duration_minutes.max'          => '所要時間は1440分（24時間）以内で指定してください。',
        ];
    }
}
