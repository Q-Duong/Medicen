<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExcelUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gắn Policy nếu cần phân quyền
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'unit_id'   => ['required', 'integer', 'exists:units,id'],
            'file'      => [$isCreate ? 'required' : 'nullable', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'ghi_chu'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.required'   => 'Chọn đơn vị gửi file.',
            'unit_id.exists'     => 'Đơn vị không tồn tại.',
            'file.required'      => 'Chọn file Excel để tải lên.',
            'file.mimes'         => 'Chỉ nhận file .xlsx, .xls hoặc .csv.',
            'file.max'           => 'File tối đa 10 MB.',
            'ghi_chu.max'        => 'Ghi chú tối đa 1000 ký tự.',
        ];
    }
}
