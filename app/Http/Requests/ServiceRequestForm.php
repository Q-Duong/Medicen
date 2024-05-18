<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequestForm extends FormRequest
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
        if ($this->isMethod('post')) {
            return [
                'service_title' => 'required',
                'service_slug' => 'required',
                'service_content' => 'required',
                'service_image' => 'required',
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'service_title' => 'required',
                'service_slug' => 'required',
                'service_content' => 'required',
            ];
        }
        return [];
    }
    public function messages(): array
    {
        return [
            'service_title.required' => 'Vui lòng điền thông tin',
            'service_slug.required' => 'Vui lòng điền thông tin',
            'service_content.required' => 'Vui lòng điền thông tin',
            'service_image.required' => 'Vui lòng chọn file',

        ];
    }
}
