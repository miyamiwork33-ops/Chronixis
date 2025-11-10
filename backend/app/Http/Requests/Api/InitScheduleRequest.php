<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitScheduleRequest extends FormRequest
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
            'targetDate' => ['required', 'date_format:Y-m-d'],
            'isInit'     => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'targetDate.required'    => '日付を選択してください',
            'targetDate.date_format' => '日付の形式が不正です(Y-m-d)',
            'isInit.required'        => 'データ取得の範囲の指定がありません',
            'isInit.boolean'         => 'データ取得の範囲は真偽値で指定してください',
        ];
    }
}
