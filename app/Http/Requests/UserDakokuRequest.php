<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserDakokuRequest extends FormRequest
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
            'targetDate'            => 'required|date_format:Y/m/d',
            'syukkinTime'           => 'required|date_format:H:i:s',
            // 'taikinTime'            => 'required|date_format:H:i:s|after:syukkinTime',
            'attendType'            => 'required|integer',
            'dpType'                => 'required|string',
            // 'children.*.occupation' => 'required|integer',
            // 'children.*.timezone'   => 'required|integer',
            // 'children.*.location'   => 'required|integer',
        ];
    }
    public function messages()
    {
        return [
            'targetDate.required'            => '打刻日付を選択してください。',
            'syukkinTime.required'           => '出勤時間を選択してください。',
            // 'taikinTime.required'            => '退勤時刻を選択してください。',
            // 'taikinTime.after'               => '退勤時刻は、出勤時間より後の時間を指定してください。',
            'attendType.required'            => '打刻区分を選択してください。',
            'dpType.required'                => '必須項目です。',
            // 'children.*.occupation.required' => '職種を選択してください。',
            // 'children.*.timezone.required'   => '時間帯を選択してください。',
            // 'children.*.location.required'   => '現場を選択してください。',
        ];
    }
}
