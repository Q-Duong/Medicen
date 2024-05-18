<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    //Admin
    public function index()
    {
        $getAllCustomer = Customer::orderBy('id', 'DESC')->paginate(10);
        return view('pages.admin.customer.index',compact('getAllCustomer'));
    }
}
