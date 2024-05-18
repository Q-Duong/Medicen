<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitRequestForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'unit_code' => 'required',
            'unit_name' => 'required',
        ];
    }
    public function messages()
    {
        return [
            'unit_code.required' => 'Vui lòng nhập mã đơn vị.',
            'unit_name.required' => 'Vui lòng nhập tên đơn vị.',
        ];
    }
}
