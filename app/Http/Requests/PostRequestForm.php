<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequestForm extends FormRequest
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
                'post_title' => 'required',
                'post_slug' => 'required',
                'post_image' => 'required',
                'post_content' => 'required',
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'post_title' => 'required',
                'post_slug' => 'required',
                'post_content' => 'required',
            ];
        }
        return [];
    }

    public function messages(): array
    {
        return [
            'post_title.required' => 'Vui lòng điền thông tin',
            'post_slug.required' => 'Vui lòng điền thông tin',
            'post_content.required' => 'Vui lòng điền thông tin',
            'post_image.required' => 'Vui lòng chọn file'
        ];
    }
}
