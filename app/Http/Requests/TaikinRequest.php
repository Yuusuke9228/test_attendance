<?php

namespace App\Http\Requests;

use App\Rules\TaikinTimeRule;
use Illuminate\Foundation\Http\FormRequest;

class TaikinRequest extends FormRequest
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
        $id = $this->request->get('id');
        return [
            'taikinTime' => ['required','date_format:H:i:s', new TaikinTimeRule($id)],
            // 'children.*.occupation' => 'required|integer',
            // 'children.*.timezone'   => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'taikinTime.required' => '退勤時刻を選択してください。',
            // 'children.*.occupation.required' => '職種を選択してください。',
            // 'children.*.timezone.required'   => '時間帯を選択してください。',
        ];
    }
}
