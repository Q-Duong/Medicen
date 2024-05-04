<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    //Admin
    public function add_customer_admin(){
    	return view('admin.Customer.add_customer');
    }
    
    public function list_customer(){
        $list_customer = Customer::orderBy('customer_id','DESC')->get();
    	return view('admin.Customer.list_customer')->with(compact('list_customer'));
    }

    public function save_customer(Request $request){
        //$this->checkCustomer($request);
    	$data = $request->all();
        $customer = new Customer();
    	$customer->customer_name = $data['customer_name'];
        $customer->customer_phone = $data['customer_phone'];
        $customer->customer_address = $data['customer_address'];
        $customer->customer_note = '';
        $customer->save();

    	return Redirect()->back()->with('success','Thêm khách hàng thành công');
    }
    //Validation
    public function checkCustomer(Request $request){
        $this-> validate($request,
        [
            'customer_first_name' => 'required',
            'customer_last_name' => 'required',
            'customer_email' => 'required|email|unique:users,email',
            'customer_phone' => 'required|numeric|digits_between:10,10',
            'customer_password' => 'required|min:8',
        ],
        [
            'customer_first_name.required' =>'Vui lòng điền họ và tên lót',
            'customer_last_name.required' =>'Vui lòng điền tên',
            'customer_email.required' =>'Vui lòng điền email',
            'customer_phone.required' =>'Vui lòng điền số điện thoại',
            'customer_password.required' =>'Vui lòng điền mật khẩu',
            'customer_email.email' =>'Vui lòng kiểm tra lại email',
            'customer_phone.digits_between' =>'Vui lòng kiểm tra lại số điện thoại',
            'customer_phone.numeric' =>'Vui lòng kiểm tra lại số điện thoại',
            'customer_password.min' =>'Mật khẩu phải lớn hơn 8 ký tự',
            
        ]);
    }

    public function checkCustomerAdmin(Request $request){
        $this-> validate($request,
        [
            'customer_first_name' => 'required',
            'customer_last_name' => 'required',
            'customer_phone' => 'required|numeric|digits_between:10,10',
            'customer_password' => 'required|min:8',
        ],
        [
            'customer_first_name.required' =>'Vui lòng điền họ và tên lót',
            'customer_last_name.required' =>'Vui lòng điền tên',
            'customer_password.required' =>'Vui lòng điền mật khẩu', 
            'customer_phone.required' =>'Vui lòng điền số điện thoại',
            'customer_phone.digits_between' =>'Vui lòng kiểm tra lại số điện thoại',
            'customer_phone.numeric' =>'Vui lòng kiểm tra lại số điện thoại',
            'customer_password.min' =>'Mật khẩu phải lớn hơn 8 ký tự',
            
        ]);
    }

}
