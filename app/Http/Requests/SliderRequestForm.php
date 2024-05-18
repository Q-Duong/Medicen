<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequestForm extends FormRequest
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
                'slider_name' => 'required',
                'slider_status' => 'required',
                'slider_image' => 'required',
                'slider_desc' => 'required',
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'slider_name' => 'required',
                'slider_status' => 'required',
                'slider_desc' => 'required',
            ];
        }
        return [];
    }


    public function messages(): array
    {
        return [
            'slider_name.required' => 'Vui lòng điền thông tin',
            'slider_status.required' => 'Vui lòng điền thông tin',
            'slider_image.required' => 'Vui lòng chọn file',
            'slider_desc.required' => 'Vui lòng điền thông tin',
        ];
    }
}
