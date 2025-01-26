<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function validationData()
    // {
    //     $input = $this->all();
    // }
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
            'code' => ['required','string','max:255', Rule::unique('users')->ignore($this->id)],
            'email' => ['required','email','max:255', Rule::unique('users')->ignore($this->id)],
            'name' => 'required|string|max:50',
        ];
    }
}
