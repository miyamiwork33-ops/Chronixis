<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertScheduleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '*.target_date'     => ['required', 'date_format:Y-m-d'],
            '*.start_time'      => ['required', 'date_format:H:i', 'before:*.end_time', 'after_or_equal:00:00'],
            '*.end_time'        => ['required', 'date_format:H:i', 'after:*.start_time', 'before_or_equal:23:59'],
            '*.act_category_id' => [
                'required',
                'integer',
                'exists:act_categories,id',
            ],
        ];
    }

    /**
     * バリデーション前にデータを整形
     */
    protected function prepareForValidation()
    {
        $data = $this->all();

        foreach ($data as $key => $item) {
            if (isset($item['start_time'])) {
                // 00:00:00 → 00:00 に変換
                $data[$key]['start_time'] = substr($item['start_time'], 0, 5);
            }
            if (isset($item['end_time'])) {
                // 00:00:00 → 00:00 に変換
                $data[$key]['end_time'] = substr($item['end_time'], 0, 5);
            }
        }

        // 整形したデータを上書き
        $this->replace($data);
    }

    /**
     * 重複チェック（同じtarget_date内で）
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $schedules = $this->all();

            // 日付ごとにグループ化
            $grouped = collect($schedules)->groupBy('target_date');

            foreach ($grouped as $date => $items) {
                // 時刻でソート
                $sorted = $items->sortBy('start_time')->values();

                for ($i = 0; $i < $sorted->count() - 1; $i++) {
                    $current = $sorted[$i];
                    $next = $sorted[$i + 1];

                    // 必要なフィールドが存在する場合のみチェック
                    if (!isset($current['end_time']) || !isset($next['start_time'])) {
                        continue;
                    }
                    
                    // 時間帯重複チェック
                    if ($current['end_time'] > $next['start_time']) {
                        $validator->errors()->add(
                            "{$i}.start_time",
                            "{$date} のスケジュールが時間帯で重複しています。"
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            '*.target_date.required'      => '日付が指定されていません。',
            '*.target_date.date_format'   => '日付の形式が不正です（Y-m-d）。',
            '*.start_time.required'       => '開始時刻は必須です。',
            '*.start_time.date_format'    => '開始時刻の形式が不正です（H:i）。',
            '*.end_time.required'         => '終了時刻は必須です。',
            '*.end_time.date_format'      => '終了時刻の形式が不正です（H:i）。',
            '*.end_time.after'            => '終了時刻は開始時刻より後にしてください。',
            '*.start_time.before'         => '開始時刻は終了時刻より前にしてください。',
            '*.start_time.after_or_equal' => '開始時刻は00:00以降である必要があります。',
            '*.end_time.before_or_equal'  => '終了時刻は23:59以前である必要があります。',
            '*.act_category_id.required'  => '行動カテゴリを指定してください。',
            '*.act_category_id.exists'    => '指定された行動カテゴリが存在しません。',
        ];
    }
}
