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
            'unit_code' => ['required', 'string', 'max:20'],
            'unit_abbreviation' => ['required', 'string', 'max:255'],
            'unit_name' => 'required',
            'unit_tax_code' => ['nullable', 'string', 'max:30'],
        ];
    }
    public function messages()
    {
        return [
            'unit_code.required' => 'Vui lòng nhập mã đơn vị.',
            'unit_name.required' => 'Vui lòng nhập tên đơn vị.',
            'unit_code.max' => 'Mã đơn vị tối đa 20 ký tự.',
            'unit_abbreviation.required' => 'Vui lòng nhập tên viết tắt (tên hiện trên đơn hàng, lịch xe).',
            'unit_tax_code.max' => 'Mã số thuế tối đa 30 ký tự.',
        ];
    }
}
