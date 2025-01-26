<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyukinRequest extends FormRequest
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
            'dpType'                => 'required|string',
            'syukkinTime'           => 'required|date_format:H:i:s',
            // 'children.*.occupation' => 'required|integer',
            // 'children.*.timezone'   => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'dpType.required'                => '必須項目です。',
            'syukkinTime.required'           => '出勤時間を選択してください。',
            // 'children.*.occupation.required' => '職種を選択してください。',
            // 'children.*.timezone.required'   => '時間帯を選択してください。',
        ];
    }
}
