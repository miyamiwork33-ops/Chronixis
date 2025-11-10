<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpsertActCategoryRequest extends FormRequest
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
            '*.activity'       => 'required|string|max:30',
            '*.color_name'     => 'required|string|max:30',
            '*.hex_color_code' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
                // ユーザー単位でユニーク制約（新規登録時のみチェック）
                function ($attribute, $value, $fail) {
                    $user = $this->user();
                    $idPath = str_replace('.hex_color_code', '.id', $attribute);
                    $id = $this->input($idPath);

                    $query = \App\Models\ActCategory::where('user_id', $user->id)
                        ->where('hex_color_code', $value)
                        ->whereNull('deleted_at');

                    // 更新時は、IDがユーザーに属していることを確認してから除外
                    if ($id !== null) {
                        $categoryExists = \App\Models\ActCategory::where('id', $id)
                            ->where('user_id', $user->id)
                            ->exists();
                        if (!$categoryExists) {
                            $fail('指定されたIDは無効です。');
                            return;
                        }
                        $query->where('id', '!=', $id);
                    }

                    if ($query->exists()) {
                        $fail('同じ色コードは既に登録されています。');
                    }
                }
            ],
            '*.text_color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '*.id'              => [
                'nullable',
                'integer',
                'exists:act_categories,id',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $user = $this->user();
                        $exists = \App\Models\ActCategory::where('id', $value)
                            ->where('user_id', $user->id)
                            ->exists();
                        if (!$exists) {
                            $fail('指定されたIDは無効です。');
                        }
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            '*.activity.required'        => '行動内容は必須です',
            '*.activity.string'          => '行動内容は文字列で入力してください',
            '*.activity.max'             => '行動内容は30文字以内で入力してください',

            '*.color_name.required'      => '色名称は必須です',
            '*.color_name.string'        => '色名称は文字列で入力してください',
            '*.color_name.max'           => '色名称は30文字以内で入力してください',

            '*.hex_color_code.required'  => '色コードは必須です',
            '*.hex_color_code.string'    => '色コードは文字列で入力してください',
            '*.hex_color_code.regex'     => '色コードの形式が正しくありません（例：#FFFFFF）',

            '*.text_color_code.required' => 'テキスト色コードは必須です',
            '*.text_color_code.string'   => 'テキスト色コードは文字列で入力してください',
            '*.text_color_code.regex'    => 'テキスト色コードの形式が正しくありません（例：#000000）',

            '*.id.integer'               => 'IDは整数である必要があります',
            '*.id.exists'                => '指定されたIDは存在しません',
        ];
    }
}
