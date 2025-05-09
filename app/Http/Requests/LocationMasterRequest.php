<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationMasterRequest extends FormRequest
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
            'locationName' => 'required|string|max:255'
        ];
    }
    public function messages()
    {
        return [
            'locationName.required' => '現場名を入力してください。'
        ];
    }
}
