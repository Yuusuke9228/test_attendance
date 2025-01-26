<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
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
            'users' => 'required|array',
            'date' => 'required|date_format:Y/m/d'
        ];
    }
    public function messages()
    {
        return [
            'users.required' => '従業員は必須項目です。',
            'date.required' => '日付は必須項目です。'
        ];
    }
}
