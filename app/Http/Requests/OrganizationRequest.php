<?php

namespace App\Http\Requests;

use App\Rules\ZipCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
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
        $rules = [
            'organizationCode' => ['required', 'string', 'max:255', Rule::unique('organizations', 'organization_code')->ignore($this->id)],
            'organizationName' => 'required|string|max:255',
        ];

        if($this->input('organizationZipCode')) {
            $rules['organizationZipCode'] = [ new ZipCode() ];
        }
        return $rules;
    
    }
    public function messages()
    {
        return [
            'organizationCode.required' => '組織コードを入力してください。',
            'organizationCode.unique' => ' その組織コードは、すでに登録されています。',
            'organizationName.required' => '組織名を入力してください。',
        ];
    }
}
