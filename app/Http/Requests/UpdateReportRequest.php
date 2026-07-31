<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'send_mail'                => ['nullable', 'boolean'],
            
            'items'                    => ['required', 'array'],
            'items.*.id'               => ['nullable', 'exists:report_items,id'],
            
            'items.*.type'             => ['nullable', 'string'],
            'items.*.summary_group'    => ['nullable', 'string'],
            'items.*.name'             => ['nullable', 'string'],
            
            // THÊM DÒNG NÀY ĐỂ BẮT TÍN HIỆU XÓA TỪ GIAO DIỆN
            'items.*._delete'          => ['nullable', 'in:0,1'], 
            
            'items.*.estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.actual_amount'    => ['nullable', 'numeric', 'min:0'],
            'items.*.expected_date'    => ['nullable', 'date'],
            'items.*.actual_date'      => ['nullable', 'date'],
        ];
    }
}