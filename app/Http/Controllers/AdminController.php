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

    /**
     * Trang "Tài khoản của tôi": xem + sửa thông tin cá nhân (bảng profiles) và đổi mật khẩu của chính mình.
     * Trước đây 2 trang "Thông tin" / "Cài đặt" trỏ tới view không còn (admin.AdminInfomation.*) và form lưu gọi
     * hàm không có (store_information) nên đều lỗi; gộp lại thành một trang.
     */
    public function information()
    {
        $user = Auth::user();
        $profile = $user->profile_id ? Profile::find($user->profile_id) : null;

        return view('pages.admin.setting.account', compact('user', 'profile'));
    }

    public function settings()
    {
        return Redirect::route('information');
    }

    public function store_information(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'profile_firstname' => ['nullable', 'string', 'max:70'],
            'profile_lastname' => ['nullable', 'string', 'max:70'],
            'profile_phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'profile_gender' => ['nullable', 'in:0,1'],
            'day_of_birth' => ['nullable', 'date'],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'profile_phone.regex' => 'Số điện thoại 10 số, bắt đầu bằng 0.',
            'current_password.required_with' => 'Nhập mật khẩu hiện tại để đổi mật khẩu.',
            'password.min' => 'Mật khẩu mới tối thiểu 6 ký tự.',
            'password.confirmed' => 'Nhập lại mật khẩu mới chưa khớp.',
        ]);

        // Đổi mật khẩu: phải đúng mật khẩu hiện tại
        if (!empty($data['password']) && !\Illuminate\Support\Facades\Hash::check((string) $data['current_password'], $user->password)) {
            return back()->withInput()->withErrors(['current_password' => 'Mật khẩu hiện tại chưa đúng.']);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data) {
            $profile = $user->profile_id ? Profile::find($user->profile_id) : null;
            $profile ??= new Profile();
            $profile->forceFill([
                'profile_firstname' => (string) ($data['profile_firstname'] ?? ''),
                'profile_lastname' => (string) ($data['profile_lastname'] ?? ''),
                'profile_phone' => (string) ($data['profile_phone'] ?? ''),
                'profile_email' => $user->email,
                'profile_gender' => (int) ($data['profile_gender'] ?? 0),
                'day_of_birth' => ($data['day_of_birth'] ?? null) ?: null,
            ])->save();
            $user->profile_id = $profile->id;
            if (!empty($data['password'])) {
                $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
                // Các máy đang "ghi nhớ đăng nhập" phải đăng nhập lại; phiên đang dùng vẫn giữ
                $user->setRememberToken(null);
            }
            $user->save();
        });

        return Redirect::route('information')->with('success', 'Đã cập nhật thông tin' . (!empty($data['password']) ? ' và đổi mật khẩu' : '') . '.');
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
