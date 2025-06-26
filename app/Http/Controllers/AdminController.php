<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Statistic;
use Carbon\Carbon;
use App\Http\Requests;
use App\Models\Blog;
use App\Models\Profile;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AdminController extends Controller
{
    public function formatDate($date){
		$format=explode("/",$date);
		$day = array_shift($format);
        $year = array_pop($format);
        $month = implode(" ", $format);
		$dateFormat= $year."-".$month."-".$day;
    	return $dateFormat;
    }

    public function index(Request $request){
        $service = Service::all()->count();
        $post = Blog::all()->count();
        $order = Order::all()->count();
        $customer = Customer::all()->count();
        $getAllUnit = Unit::orderBy('unit_code','ASC')->get();
        return view('pages.admin.index')->with(compact('service','post','order','customer','getAllUnit'));
    }

    public function chat(){
        return view('pages.admin.chatbox.admin');
    }

    public function login(){
        if(Auth::check()){
            return Redirect::route('dashboard.index');
        }else{
            return view('pages.admin.login.index');
        }
    }

    public function logout(){
        Auth::logout();
        return Redirect::to('login');
    }

    public function information(){
    	return view('admin.AdminInfomation.view_infomation');
    }

    public function settings(){
    	return view('admin.AdminInfomation.edit_infomation');
    }

    public function save_information(Request $request){
        $data=$request->all();
        $profile= Profile::find(Auth::user()->profile_id);
        $profile->profile_firstname=$data['profile_firstname'];
        $profile->profile_lastname=$data['profile_lastname'];
        $profile->profile_phone=$data['profile_phone'];
        $profile->profile_email=$data['profile_email'];
        $profile->profile_gender=$data['profile_gender'];
        $profile->day_of_birth=$data['day_of_birth'];
        $profile->save();
        if($data['admin_password']!=null){
            $user=User::find(Auth::id());
            $user->password=bcrypt($data['admin_password']);
            $user->save();
        }
        return Redirect::to('/admin/information')->with('success','Cập nhật thông tin thành công');
       
    }
    
    public function revenue_statistics_by_date(Request $request){
        $data = $request->all();
        $from_date = $data['from_date'];
        $to_date = $data['to_date'];
        $from_date_format = Date('d/m/Y', strtotime($data['from_date']));
        $to_date_format = Date('d/m/Y', strtotime($data['to_date']));
        $total=0;
        $total_orders=0;
        $getStatistics = Statistic::whereBetween('order_date',[$from_date,$to_date])->orderBy('order_date','ASC')->get();
        if (count($getStatistics) > 0) {
            foreach($getStatistics as $key => $val){
                $total += $val->sales;
                $total_orders += $val->total_order;
                $chart_data[] = array(
                    'period' =>  date('d/m/Y', strtotime($val->order_date)),
                    'order' => $val->total_order,
                    'sales' => $val->sales,
                    'quantity' => $val->quantity,    
                );
            }
            $chart = $chart_data;
            return response()->json(array('success'=>true,'chart'=>$chart,'total' => $total,'total_orders' => $total_orders,'from_date' => $from_date_format,'to_date' => $to_date_format));
        } else {
            return response()->json(array('success'=>false));
        }
    }

    public function revenue_statistics_by_unit(Request $request){
        $data = $request->all();
        $unit_id = $data['unit_id'];
        $total=0;
        $getStatistics = Order::where('unit_id',$unit_id)->orderBy('created_at','ASC')->get();
        if (count($getStatistics) > 0) {
            foreach($getStatistics as $key => $val){
                $total += $val->order_price;
                $chart_data[] = array(
                    'period' => $val->created_at->format('d/m/Y'),
                    'quantity' => $val->order_quantity,
                    'sales' => $val->order_price,
                    'total' => $total, 
                );
            }
            $chart = $chart_data;
            return response()->json(array('success'=>true,'chart'=>$chart,'total' => $total,'total_orders' => count($getStatistics)));
        } else {
            return response()->json(array('success'=>false));
        } 
    }

    public function optional_revenue_statistics(Request $request){
        $data = $request->all();
        $firstThisMonth = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();
        $earlyLastMonth = Carbon::now('Asia/Ho_Chi_Minh')->subMonth()->startOfMonth()->toDateString();
        $endOfLastMonth = Carbon::now('Asia/Ho_Chi_Minh')->subMonth()->endOfMonth()->toDateString();
        $sub7days = Carbon::now('Asia/Ho_Chi_Minh')->subDays(7)->toDateString();
        $sub365days = Carbon::now('Asia/Ho_Chi_Minh')->subDays(365)->toDateString();
        $total=0;
        $total_orders=0;
        $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
    
        if($data['optional_revenue']=='7ngay'){
            $getStatistics = Statistic::whereBetween('order_date',[$sub7days,$now])->orderBy('order_date','ASC')->get();
            $option = '7 Ngày qua';
        }elseif($data['optional_revenue']=='thangtruoc'){
            $getStatistics = Statistic::whereBetween('order_date',[$earlyLastMonth,$endOfLastMonth])->orderBy('order_date','ASC')->get();
            $option = 'Tháng trước';
        }elseif($data['optional_revenue']=='thangnay'){
            $getStatistics = Statistic::whereBetween('order_date',[$firstThisMonth,$now])->orderBy('order_date','ASC')->get();
            $option = 'Tháng này';
        }else{
            $getStatistics = Statistic::whereBetween('order_date',[$sub365days,$now])->orderBy('order_date','ASC')->get();
            $option = 'Năm nay';
        }

        if (count($getStatistics) > 0) {
            foreach($getStatistics as $key => $val){
                $total += $val->sales;
                $total_orders += $val->total_order;
                $chart_data[] = array(
                    'period' =>  date('d/m/Y', strtotime($val->order_date)),
                    'order' => $val->total_order,
                    'sales' => $val->sales,
                    'quantity' => $val->quantity,
                );
            }
            $chart = $chart_data;
            return response()->json(array('success'=>true,'chart'=>$chart,'total' => $total,'total_orders' => $total_orders,'option' => $option));
        } else {
            return response()->json(array('success'=>false));
        } 
    }

    public function revenue_statistics_for_the_month(){
        $sub30days = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();
        $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
        $month = Carbon::now('Asia/Ho_Chi_Minh')->format('m');
        $total=0;
        $total_orders=0;
        $getStatistics = Statistic::whereBetween('order_date',[$sub30days,$now])->orderBy('order_date','ASC')->get();
        if (count($getStatistics) > 0) {
            foreach($getStatistics as $key => $val){
                $total += $val->sales;
                $total_orders += $val->total_order;
                $chart_data[] = array(
                    'period' => date('d/m/Y', strtotime($val->order_date)),
                    'order' => $val->total_order,
                    'sales' => $val->sales,
                    'quantity' => $val->quantity,
                );
            }
            $chart = $chart_data;
            return response()->json(array('success'=>true,'chart'=>$chart,'total' => $total,'total_orders' => $total_orders,'month' => $month));
        } else {
            return response()->json(array('success'=>false));
        }
    }
}
