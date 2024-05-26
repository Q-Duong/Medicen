<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequestForm extends FormRequest
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
                'customer_name' => 'required',
                'customer_phone' => 'required',
                'customer_address' => 'required',
                'ord_cty_name' => 'required',
                'ord_start_day' => 'required',
                'ord_time' => 'required',
                'ord_deadline' => 'required',
                'ord_deliver_results' => 'required',
                'order_quantity' => 'required',
                'order_price' => 'required',
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'customer_name' => 'required',
                'customer_phone' => ['required', 'regex:/^0\d{9}$/', 'numeric'],
                'customer_address' => 'required',
                'ord_cty_name' => 'required',
                'ord_start_day' => 'required',
                'ord_time' => 'required',
                'ord_deadline' => 'required',
                'ord_deliver_results' => 'required',
                'order_quantity' => 'required',
                'order_price' => 'required',
            ];
        }
        return [];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng điền họ và tên',
            'customer_phone.required' => 'Vui lòng điền số điện thoại',
            'customer_phone.regex' => 'Vui lòng kiểm tra lại số điện thoại',
            'customer_phone.numeric' => 'Vui lòng kiểm tra lại số điện thoại',
            'customer_address.required' => 'Vui lòng điền địa chỉ',
            'ord_cty_name.required' => 'Vui lòng điền tên công ty',
            'ord_start_day.required' => 'Vui lòng điền ngày chụp',
            'ord_deadline.required' => 'Vui lòng điền ngày trả kết quả',
            'ord_deliver_results.required' => 'Vui lòng điền thông tin nhận kết quả',
            'ord_time.required' => 'Vui lòng điền giờ khám',
            'order_quantity.required' => 'Vui lòng điền số lượng chụp',
            'order_price.required' => 'Vui lòng điền tổng tiền',
        ];
    }
}
