<?php

namespace App\Http\Requests;

use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Http\FormRequest;

class DakokuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // 'attendTime' => 'required|date_format:H:i:s',
            'user'                  => 'required',
            'attendType'            => 'required|integer',
            'dpType'                => 'required|string',
            'targetDate'            => 'required|date',
            // 'children.*.occupation' => 'required|integer',
            // 'children.*.timezone'   => 'required|integer',
            // 'children.*.location'   => 'required|integer',
        ];
    }
    public function messages()
    {
        return [
              // 'attendTime.required'               => '打刻区分を選択します。',
            'user.required'                  => '該当するユーザーを選択してください。',
            'attendType.required'            => '打刻区分を選択します。',
            'dpType.required'                => '必須項目です。',
            'attendTime.date_format'         => '打刻する日付を選択します。',
            'targetDate.required'            => '打刻する日付を選択します。',
            // 'children.*.occupation.required' => '職種を選択してください。',
            // 'children.*.timezone.required'   => '時間帯を選択してください。',
            // 'children.*.location.required'   => '現場を選択してください。',
        ];
    }
}
