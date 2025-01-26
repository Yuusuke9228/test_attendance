<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BreakTimeRequest extends FormRequest
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
            'code'        => 'required|string|max:255',
            'startTime'   => 'required|date_format:H:i:s|before:startTime_1',
            'endTime'     => 'required|date_format:H:i:s|after:endTime_1|after:startTime',
            'startTime_1' => 'required|date_format:H:i:s',
            'endTime_1'   => 'required|date_format:H:i:s',
        ];
    }
    public function messages()
    {
        return [
            'code.required'        => '勤務形態コードを入力してください。',
            'startTime.required'   => '開始時間を選択してください。',
            'startTime.before'     => '開始時間には、休憩開始時刻より前の日付を指定してください。',
            'endTime.required'     => '終了時間を選択してください。',
            'endTime.after'        => '終了時間には、休憩終了時刻より後の日付を指定してください。',
            'startTime_1.required' => '開始時間１を選択してください。',
            'endTime_1.required'   => '終了時間１を選択してください。',
        ];
    }
}
