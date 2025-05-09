<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CsvFileRequest extends FormRequest
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
            "file"=> "required|mimes:csv",
        ];
    }
    public function messages()
    {
        return [
            "file.required"=> "CSVファイルを選択してください。",
            "file.mime"=> "CSVファイルを選択してください。",
        ];
    }
}
