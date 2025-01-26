<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyReportRequest extends FormRequest
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
            'date' => 'required|date',
            'user' => 'required|integer',
        ];
    }
    public function messages()
    {
        return [
            "date.required" => '対象日を選択してください。',
            "user.required" => 'ユーザーを選択してください。',
        ];
    }
}
