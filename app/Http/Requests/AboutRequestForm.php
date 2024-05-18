<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AboutRequestForm extends FormRequest
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
                'about_title' => 'required',
                'about_slug' => 'required',
                'about_content' => 'required',
                'about_image' => 'required',
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'about_title' => 'required',
                'about_slug' => 'required',
                'about_content' => 'required',
            ];
        }
        return [];
    }
    public function messages(): array
    {
        return [
            'about_title.required' => 'Vui lòng điền thông tin',
            'about_slug.required' => 'Vui lòng điền thông tin',
            'about_content.required' => 'Vui lòng điền thông tin',
            'about_image.required' => 'Vui lòng chọn file',
        ];
    }
}
