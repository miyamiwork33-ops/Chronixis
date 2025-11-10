<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreHabitLogRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'habit_goal_id' => [
                'required',
                'integer',
                'exists:habit_goals,id,user_id,' . $this->user()->id,
            ],
            'log_time' => [
                'required',
                'date'
            ],
            'is_achieved' => ['nullable', 'boolean'],
            'execution_time' => [
                'required',
                'integer',
                'min:0',
                'lt:86400',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'habit_goal_id.required'   => '習慣化目標IDが指定されていません。',
            'habit_goal_id.integer'    => '習慣化目標IDの形式が不正です。',
            'habit_goal_id.exists'     => '指定された習慣化目標が存在しません。',

            'log_time.required'        => '記録日時は必須です。',
            'log_time.date'            => '記録日時の形式が不正です。',
            'log_time.before_or_equal' => '記録日時は現在時刻以前で指定してください。',

            'is_achieved.boolean'      => '達成フラグの形式が不正です。',

            'execution_time.required'  => '実行時間は必須です。',
            'execution_time.integer'   => '実行時間は整数で指定してください。',
            'execution_time.min'       => '実行時間は0以上で指定してください。',
            'execution_time.lt'        => '実行時間は24時間未満で指定してください。',
        ];
    }
}
