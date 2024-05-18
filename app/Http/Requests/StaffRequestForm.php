<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffRequestForm extends FormRequest
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
            'staff_name' => 'required',
            'staff_phone' => ['required', 'regex:/^0\d{9}$/', 'numeric'],
            'staff_gender' => 'required',
            'staff_birthday' => 'required',
            'staff_role' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'staff_name.required' => 'Vui lòng điền họ và tên',
            'staff_gender.required' => 'Vui lòng chọn giới tính',
            'staff_phone.required' => 'Vui lòng điền số điện thoại',
            'staff_phone.regex' => 'Vui lòng kiểm tra lại số điện thoại',
            'staff_phone.numeric' => 'Vui lòng kiểm tra lại số điện thoại',
            'staff_birthday.required' => 'Vui lòng chọn ngày sinh',
            'staff_role.required' => 'Vui lòng chọn vai trò nhân viên',
        ];
    }
}
