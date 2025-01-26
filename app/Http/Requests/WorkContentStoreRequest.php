<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkContentStoreRequest extends FormRequest
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
            'occupation'        => 'required|integer',
            'workContentName'   => 'required|string|max:255',
        ];
    }
    public function messages()
    {
        return [
            'occupation.required'       => '職種管理を選択してください。',
            'workContentName.required'  => '作業名を入力してください。',
        ];
    }
}
