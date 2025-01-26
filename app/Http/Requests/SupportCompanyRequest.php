<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupportCompanyRequest extends FormRequest
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
            'companyName' => 'required|string|max:255'
        ];
    }
    public function messages()
    {
        return [
            'companyName.required' => '会社名を入力してください。'
        ];
    }
}
