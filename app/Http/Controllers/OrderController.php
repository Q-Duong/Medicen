<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use App\Exports\ExcelExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Customer;
use App\Models\CarKTV;
use App\Models\Accountant;
use App\Models\HistoryEdit;
use App\Models\TempFile;
use App\Models\Zalo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDF;

class OrderController extends Controller
{
	//Customer
	public function create_order()
	{
		return view('pages.Order.create_order');
	}

	public function save_order_f(Request $request)
	{

		$this->checkOrder($request);
		$data = $request->all();

		$customer = new Customer();
		$customer->customer_name = $data['customer_name'];
		$customer->customer_phone = $data['customer_phone'];
		$customer->customer_address = $data['customer_address'];
		$customer->customer_note = '';
		$customer->save();

		$orderDetail = new OrderDetail();
		$orderDetail->ord_start_day = $data['ord_start_day'];
		$orderDetail->ord_end_day = $data['ord_end_day'];
		$orderDetail->ord_select = $data['ord_select'];
		$orderDetail->ord_doctor_read = '';
		$orderDetail->ord_film = '';
		$orderDetail->ord_form = 'ko in';
		$orderDetail->ord_print = '';
		$orderDetail->ord_form_print = '';
		$orderDetail->ord_print_result = '';
		$orderDetail->ord_film_sheet = '';
		$orderDetail->ord_note = '';
		$orderDetail->ord_deadline = '';
		$orderDetail->ord_deliver_results = '';
		$orderDetail->ord_cty_name = '';
		$orderDetail->ord_time = '';
		$orderDetail->ord_list_file = '';
		$orderDetail->ord_list_file_path = '';
		$orderDetail->ord_email = '';
		$orderDetail->save();

		$order = new Order();
		$order->customer_id = $customer->customer_id;
		$order->order_detail_id = $orderDetail->order_detail_id;
		$order->unit_id = 1;
		$order->order_quantity = $data['order_quantity'];
		$order->order_cost = 0;
		$order->order_all_in_one = 0;
		$order->order_price = 0;
		$order->order_discount = 0;
		$order->order_profit = 0;
		$order->order_status = 0;
		$order->schedule_status = 0;
		$order->order_warning = 'Không';
		$order->accountant_updated = 0;
		$order->save();

		$format = explode("-", $data['ord_start_day']);

		$accountant = new Accountant();
		$accountant->order_id = $order->order_id;
		$accountant->accountant_owe = 0;

		if ($format[1] == 10) {
			$accountant->accountant_month = $format[1];
		} else {
			$month = explode("0", $format[1]);
			if (count($month) > 1) {
				$accountant->accountant_month = $month[1];
			} else {
				$accountant->accountant_month = $month[0];
			}
		}
		$accountant->save();

		return Redirect::to('/successful-medical-registration');
	}

	public function createCustomerAuto()
	{
		return view('pages.Order.create_order_customer');
	}

	public function save_order_customer(Request $request)
	{

		$this->checkOrderCustomer($request);
		$data = $request->all();

		$customer = new Customer();
		$customer->customer_name = $data['customer_name'];
		$customer->customer_phone = $data['customer_phone'];
		$customer->customer_address = $data['customer_address'];
		$customer->customer_note = $data['customer_note'];
		$customer->save();

		$orderDetail = new OrderDetail();
		$orderDetail->ord_start_day = $data['ord_start_day'];
		$orderDetail->ord_end_day = $data['ord_end_day'];
		$orderDetail->ord_select = $data['ord_select'];
		$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
		$orderDetail->ord_film = $data['ord_film'];
		$orderDetail->ord_form = $data['ord_form'];
		$orderDetail->ord_print = $data['ord_print'];
		$orderDetail->ord_form_print = $data['ord_form_print'];
		$orderDetail->ord_print_result = $data['ord_print_result'];
		$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
		$orderDetail->ord_note = $data['ord_note'];
		$orderDetail->ord_deadline = $data['ord_deadline'];
		$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
		$orderDetail->ord_cty_name = $data['ord_cty_name'];
		$orderDetail->ord_time = $data['ord_time'];
		$orderDetail->ord_list_file = '';
		$orderDetail->ord_list_file_path = '';
		$orderDetail->ord_email = '';
		$orderDetail->save();

		$order = new Order();
		$order->customer_id = $customer->customer_id;
		$order->order_detail_id = $orderDetail->order_detail_id;
		$order->unit_id = 1;
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = 0;
		$order->order_all_in_one = 0;
		$order->order_cost = 0;
		$order->order_discount = 0;
		$order->order_profit = 0;
		$order->order_status = 0;
		$order->schedule_status = 0;
		$order->order_warning = 'Không';
		$order->accountant_updated = 0;
		$order->save();

		// for($i=0 ;$i<=4; $i++){
		//     $car = new CarKTV();
		//     $car->order_id = $order->order_id;
		//     $car->car_name = $i+1;
		// 	$car->car_active = 0;
		// 	$car->car_driver_name = '';
		// 	$car->car_ktv_name_1 = '';
		// 	$car->car_ktv_name_2 = '';
		// 	$car->order_quantity_draft = 0;
		//     $car->save();
		// }

		$format = explode("-", $data['ord_start_day']);

		$accountant = new Accountant();
		$accountant->order_id = $order->order_id;
		$accountant->accountant_owe = 0;

		if ($format[1] == 10) {
			$accountant->accountant_month = $format[1];
		} else {
			$month = explode("0", $format[1]);
			if (count($month) > 1) {
				$accountant->accountant_month = $month[1];
			} else {
				$accountant->accountant_month = $month[0];
			}
		}
		$accountant->save();

		return Redirect::to('/successful-medical-registration');
	}

	public function successful_medical_registration()
	{
		return view('pages.successful_medical_registration');
	}

	//Admin

	public function add_order()
	{
		$getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
		return view('admin.Order.add_order')->with(compact('getAllUnit'));
	}

	public function list_order()
	{
		$getAllOrder = Order::join('tbl_order_details', 'tbl_order_details.order_detail_id', '=', 'tbl_orders.order_detail_id')
			->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_orders.unit_id')
			->orderBy('order_id', 'DESC')->select('order_id', 'tbl_orders.created_at', 'order_quantity', 'order_price', 'order_status', 'unit_code', 'unit_name', 'ord_start_day', 'ord_end_day', 'ord_select', 'schedule_status')->get();
		return view('admin.Order.list_order')->with(compact('getAllOrder'));
	}

	public function upload(Request $request)
	{
		$getFile = $request->file('ord_list_file');
		if ($getFile) {
			foreach ($getFile as $key => $file) {
				$fileUploaded = saveImageFileDrive($file);
				TempFile::create([
					'folder' => $fileUploaded['path'],
					'filename' => $fileUploaded['fileName'],
				]);
			}
			return $fileUploaded['path'];
		}
		return response('Failed upload', 500);
	}

	public function save_order(Request $request)
	{
		$this->checkOrderAdmin($request);
		DB::beginTransaction();
		try {
			$data = $request->all();
			$orderDetail = new OrderDetail();
			$orderDetail->ord_start_day = $data['ord_start_day'];
			$orderDetail->ord_end_day = $data['ord_end_day'];
			$orderDetail->ord_select = $data['ord_select'];
			$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
			$orderDetail->ord_film = $data['ord_film'];
			$orderDetail->ord_form = $data['ord_form'];
			$orderDetail->ord_print = $data['ord_print'];
			$orderDetail->ord_form_print = $data['ord_form_print'];
			$orderDetail->ord_print_result = $data['ord_print_result'];
			$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
			$orderDetail->ord_note = $data['ord_note'];
			$orderDetail->ord_deadline = $data['ord_deadline'];
			$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
			$orderDetail->ord_cty_name = $data['ord_cty_name'];
			$orderDetail->ord_time = $data['ord_time'];
			$orderDetail->ord_email = $data['ord_email'];
			$get_file = $request->ord_list_file;
			if ($get_file) {
				$name = '';
				$path = '';
				foreach ($get_file as $key => $file) {
					$tmp_file = TempFile::where('folder', $file)->first();
					$name .= $tmp_file->filename . ',';
					$path .= $file . ',';
				}
				$orderDetail->ord_list_file = substr($name, 0, -1);
				$orderDetail->ord_list_file_path = substr($path, 0, -1);
			} else {
				$orderDetail->ord_list_file = '';
				$orderDetail->ord_list_file_path = '';
			}
			$orderDetail->save();

			$customer = new Customer();
			$customer->customer_name = $data['customer_name'];
			$customer->customer_phone = $data['customer_phone'];
			$customer->customer_address = $data['customer_address'];
			$customer->customer_note = $data['customer_note'];
			$customer->save();

			$order = new Order();
			$order->customer_id = $customer->customer_id;
			$order->order_detail_id = $orderDetail->order_detail_id;
			$order->unit_id = $data['unit_id'];
			$order->order_quantity = $data['order_quantity'];
			$order->order_price = formatPrice($data['order_price']);
			$order->order_cost = $data['order_all_in_one'] == 0 ? formatPrice($data['order_cost']) : 0;
			$order->order_all_in_one = $data['order_all_in_one'];
			$order->order_percent_discount = $data['order_percent_discount'];
			$order->order_vat = $data['order_vat'];
			$order->order_discount = 0;
			$order->order_profit = 0;
			$order->order_status = 1;
			$order->schedule_status = 0;
			$order->accountant_updated = 0;
			$order->order_warning = $data['order_warning'];
			$order->order_child = $data['order_child'];
			$order->order_surcharge = $data['order_surcharge'];
			$order->save();

			$format = explode("-", $data['ord_start_day']);

			$accountant = new Accountant();
			$accountant->order_id = $order->order_id;
			$accountant->accountant_owe = formatPrice($data['order_price']);
			$accountant->accountant_distance = $data['accountant_distance'];

			if ($format[1] == 10) {
				$accountant->accountant_month = $format[1];
			} else {
				$month = explode("0", $format[1]);
				if (count($month) > 1) {
					$accountant->accountant_month = $month[1];
				} else {
					$accountant->accountant_month = $month[0];
				}
			}
			$accountant->save();

			$now = Carbon::now('Asia/Ho_Chi_Minh')->toDateTimeString();
			$history = new HistoryEdit();
			$history->order_id = $order->order_id;
			$history->user_name = Auth::user()->email;
			$history->history_action = 'Thêm đơn hàng';
			$history->created_at = $now;
			$history->save();
			DB::commit();
			return Redirect()->back()->with('success', 'Thêm đơn hàng thành công');
		} catch (\Exception $e) {
			DB::rollback();
			return Redirect()->back()->with('errors', 'Thêm đơn hàng thất bại');
		}
	}

	public function getAccessToken($value)
	{
		$response = Http
			::withHeaders([
				'secret_key' => env('ZALO_SECRET_KEY'),
			])->post(
				'https://oauth.zaloapp.com/v4/oa/access_token',
				[
					'code' => $value,
					'app_id' => env('ZALO_APP_ID'),
					'grant_type' => 'authorization_code',
					'code_verifier' => env('CODE_VERIFIER')
				]
			);

		$jsonData = $response->json();
	}

	public function getAccessTokenFromRefreshToken()
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/x-www-form-urlencoded',
				'secret_key' => config("app.zaloSecretKey"),
			])->withBody(http_build_query([
				'refresh_token' => $this->getRefreshTokenDB(),
				'app_id' => config("app.zaloAppId"),
				'grant_type' => 'refresh_token',
			]), 'application/json')->post('https://oauth.zaloapp.com/v4/oa/access_token');

		$jsonData = $response->json();

		$value = collect($jsonData);
		$zalo = Zalo::find(1);
		$zalo->access_token = $value['access_token'];
		$zalo->refresh_token = $value['refresh_token'];
		$zalo->save();
	}

	public function getAccessTokenDB()
	{
		$zalo = Zalo::first();
		$access_token = $zalo->access_token;
		return $access_token;
	}

	public function getRefreshTokenDB()
	{
		$zalo = Zalo::first();
		$refresh_token = $zalo->refresh_token;
		return $refresh_token;
	}

	public function zaloNotificationServiceD($carActive)
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => $carActive->car_driver_phone,
					'template_id' => '259945',
					'template_data' => [
						'order_code' => $carActive->order_id,
						'date' => date('d/m/Y', strtotime($carActive->order->orderdetail->ord_start_day)),
						'car' => $carActive->car_name,
						'name' => $carActive->car_driver_name,
						'time' => $carActive->order->orderdetail->ord_time
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
	}

	public function zaloNotificationServiceK1($carActive)
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => $carActive->car_ktv_phone_1,
					'template_id' => '259945',
					'template_data' => [
						'order_code' => $carActive->order_id,
						'date' => date('d/m/Y', strtotime($carActive->order->orderdetail->ord_start_day)),
						'car' => $carActive->car_name,
						'name' => $carActive->car_ktv_name_1,
						'time' => $carActive->order->orderdetail->ord_time
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
	}

	public function zaloNotificationServiceK2($carActive)
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => $carActive->car_ktv_phone_2,
					'template_id' => '259945',
					'template_data' => [
						'order_code' => $carActive->order_id,
						'date' => date('d/m/Y', strtotime($carActive->order->orderdetail->ord_start_day)),
						'car' => $carActive->car_name,
						'name' => $carActive->car_ktv_name_2,
						'time' => $carActive->order->orderdetail->ord_time
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
	}

	public function zaloNotificationCancleD($carActive)
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => $carActive->car_driver_phone,
					'template_id' => '261885',
					'template_data' => [
						'order_code' => $carActive->order_id,
						'date' => date('d/m/Y', strtotime($carActive->order->orderdetail->ord_start_day)),
						'car' => $carActive->car_name,
						'name' => $carActive->car_driver_name,
						'time' => $carActive->order->orderdetail->ord_time
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
	}

	public function zaloNotificationCancleK1($carActive)
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => $carActive->car_ktv_phone_1,
					'template_id' => '261885',
					'template_data' => [
						'order_code' => $carActive->order_id,
						'date' => date('d/m/Y', strtotime($carActive->order->orderdetail->ord_start_day)),
						'car' => $carActive->car_name,
						'name' => $carActive->car_ktv_name_1,
						'time' => $carActive->order->orderdetail->ord_time
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
	}

	public function zaloNotificationCancleK2($carActive)
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => $carActive->car_ktv_phone_2,
					'template_id' => '261885',
					'template_data' => [
						'order_code' => $carActive->order_id,
						'date' => date('d/m/Y', strtotime($carActive->order->orderdetail->ord_start_day)),
						'car' => $carActive->car_name,
						'name' => $carActive->car_ktv_name_2,
						'time' => $carActive->order->orderdetail->ord_time
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
	}

	public function test_zalo()
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => '84943705326',
					'template_id' => '259945',
					'template_data' => [
						'order_code' => 100,
						'date' => date('d/m/Y', strtotime(2023 - 05 - 16)),
						'car' => 1,
						'name' => 'Quốc Dương',
						'time' => '7'
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
		return $jsonData;
	}

	public function test_zalo_cancle()
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/json',
				'access_token' => $this->getAccessTokenDB()
			])->post(
				'https://business.openapi.zalo.me/message/template',
				[
					'phone' => '84943705326',
					'template_id' => '261885',
					'template_data' => [
						'order_code' => 100,
						'date' => date('d/m/Y', strtotime(2023 - 05 - 16)),
						'car' => 1,
						'name' => 'Quốc Dương',
						'time' => '7'
					],
					'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
				]
			);

		$jsonData = $response->json();
		return $jsonData;
	}

	public function add_schedule($order_id)
	{
		$order = Order::find($order_id);
		$staff = Staff::orderBy('staff_id', 'ASC')->get();
		return view('admin.Order.add_schedule')->with(compact('staff', 'order'));
	}

	public function save_schedule(Request $request)
	{
		$data = $request->all();
		if (!empty($data['zalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car = new CarKTV();
				$driver[$key] = explode("_", $request->car_driver_name[$key]);
				$name_driver[$key] = array_shift($driver[$key]);
				$phone_driver[$key] = array_pop($driver[$key]);

				$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
				$name_ktv1[$key] = array_shift($ktv1[$key]);
				$phone_ktv1[$key] = array_pop($ktv1[$key]);

				$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
				$name_ktv2[$key] = array_shift($ktv2[$key]);
				$phone_ktv2[$key] = array_pop($ktv2[$key]);

				$car->order_id = $data['order_id'];
				$car->car_name = $request->car_name[$key];
				$car->car_active = $request->car_active[$key];
				$car->car_driver_name = $name_driver[$key];
				$car->car_driver_phone = $phone_driver[$key];
				$car->car_ktv_name_1 = $name_ktv1[$key];
				$car->car_ktv_phone_1 = $phone_ktv1[$key];
				$car->car_ktv_name_2 = $name_ktv2[$key];
				$car->car_ktv_phone_2 = $phone_ktv2[$key];
				$car->save();
			}
			$order = Order::find($data['order_id']);
			$order->schedule_status = 1;
			$order->save();

			$getOrderId = CarKTV::orderBy('updated_at', 'DESC')->first();
			$carActive = CarKTV::orderBy('updated_at', 'DESC')->where('order_id', $getOrderId->order_id)->where('car_active', 1)->get();

			foreach ($carActive as $key => $value) {
				if ($value->car_driver_phone != null && $value->car_driver_name != null) {
					$this->zaloNotificationServiceD($value);
				}
				if ($value->car_ktv_phone_1 != null && $value->car_ktv_name_1 != null) {
					$this->zaloNotificationServiceK1($value);
				}
				if ($value->car_ktv_phone_2 != null && $value->car_ktv_name_2 != null) {
					$this->zaloNotificationServiceK2($value);
				}
			}

			return Redirect()->back()->with('success', 'Thêm lịch gửi Zalo thành công');
		}
		if (!empty($data['notZalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car = new CarKTV();
				$driver[$key] = explode("_", $request->car_driver_name[$key]);
				$name_driver[$key] = array_shift($driver[$key]);
				$phone_driver[$key] = array_pop($driver[$key]);

				$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
				$name_ktv1[$key] = array_shift($ktv1[$key]);
				$phone_ktv1[$key] = array_pop($ktv1[$key]);

				$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
				$name_ktv2[$key] = array_shift($ktv2[$key]);
				$phone_ktv2[$key] = array_pop($ktv2[$key]);

				$car->order_id = $data['order_id'];
				$car->car_name = $request->car_name[$key];
				$car->car_active = $request->car_active[$key];
				$car->car_driver_name = $name_driver[$key];
				$car->car_driver_phone = $phone_driver[$key];
				$car->car_ktv_name_1 = $name_ktv1[$key];
				$car->car_ktv_phone_1 = $phone_ktv1[$key];
				$car->car_ktv_name_2 = $name_ktv2[$key];
				$car->car_ktv_phone_2 = $phone_ktv2[$key];
				$car->save();
			}
			$order = Order::find($data['order_id']);
			$order->schedule_status = 1;
			$order->save();

			return Redirect()->back()->with('success', 'Thêm lịch không gửi Zalo thành công');
		}
	}

	public function edit_schedule($order_id)
	{
		$order = $order_id;
		$staff = Staff::orderBy('staff_id', 'ASC')->get();
		$car = CarKTV::where('order_id', $order_id)->get();
		return view('admin.Order.edit_schedule')->with(compact('staff', 'order', 'car'));
	}

	public function update_schedule(Request $request, $order_id)
	{
		$data = $request->all();

		if (!empty($data['zalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car_id = CarKTV::where('order_id', $order_id)->get();
				foreach ($car_id as $key => $car_i) {
					$car = CarKTV::find($car_i->car_ktv_id);

					$driver[$key] = explode("_", $request->car_driver_name[$key]);
					$name_driver[$key] = array_shift($driver[$key]);
					$phone_driver[$key] = array_pop($driver[$key]);

					$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
					$name_ktv1[$key] = array_shift($ktv1[$key]);
					$phone_ktv1[$key] = array_pop($ktv1[$key]);

					$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
					$name_ktv2[$key] = array_shift($ktv2[$key]);
					$phone_ktv2[$key] = array_pop($ktv2[$key]);

					$car->order_id = $order_id;
					$car->car_name = $request->car_name[$key];
					$car->car_active = $request->car_active[$key];
					$car->car_driver_name = $name_driver[$key];
					$car->car_driver_phone = $phone_driver[$key];
					$car->car_ktv_name_1 = $name_ktv1[$key];
					$car->car_ktv_phone_1 = $phone_ktv1[$key];
					$car->car_ktv_name_2 = $name_ktv2[$key];
					$car->car_ktv_phone_2 = $phone_ktv2[$key];
					$car->save();
				}
			}

			$getOrderId = CarKTV::orderBy('updated_at', 'DESC')->first();
			$carActive = CarKTV::orderBy('updated_at', 'DESC')->where('order_id', $getOrderId->order_id)->where('car_active', 1)->get();

			foreach ($carActive as $key => $value) {
				if ($value->car_driver_phone != null && $value->car_driver_name != null) {
					$this->zaloNotificationServiceD($value);
				}
				if ($value->car_ktv_phone_1 != null && $value->car_ktv_name_1 != null) {
					$this->zaloNotificationServiceK1($value);
				}
				if ($value->car_ktv_phone_2 != null && $value->car_ktv_name_2 != null) {
					$this->zaloNotificationServiceK2($value);
				}
			}
			return Redirect()->back()->with('success', 'Cập nhật lịch xe gửi Zalo thành công');
		}
		if (!empty($data['notZalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car_id = CarKTV::where('order_id', $order_id)->get();
				foreach ($car_id as $key => $car_i) {
					$car = CarKTV::find($car_i->car_ktv_id);

					$driver[$key] = explode("_", $request->car_driver_name[$key]);
					$name_driver[$key] = array_shift($driver[$key]);
					$phone_driver[$key] = array_pop($driver[$key]);

					$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
					$name_ktv1[$key] = array_shift($ktv1[$key]);
					$phone_ktv1[$key] = array_pop($ktv1[$key]);

					$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
					$name_ktv2[$key] = array_shift($ktv2[$key]);
					$phone_ktv2[$key] = array_pop($ktv2[$key]);

					$car->order_id = $order_id;
					$car->car_name = $request->car_name[$key];
					$car->car_active = $request->car_active[$key];
					$car->car_driver_name = $name_driver[$key];
					$car->car_driver_phone = $phone_driver[$key];
					$car->car_ktv_name_1 = $name_ktv1[$key];
					$car->car_ktv_phone_1 = $phone_ktv1[$key];
					$car->car_ktv_name_2 = $name_ktv2[$key];
					$car->car_ktv_phone_2 = $phone_ktv2[$key];
					$car->save();
				}
			}
			return Redirect()->back()->with('success', 'Cập nhật lịch xe không gửi Zalo thành công');
		}
	}

	public function cancle_schedule(Request $request)
	{
		$data = $request->all();
		$car = CarKTV::where('order_id', $data['order_id'])->where('car_name', $data['car_name'])->first();
		if ($car->car_driver_phone != null && $car->car_driver_name != null) {
			$this->zaloNotificationCancleD($car);
		}
		if ($car->car_ktv_phone_1 != null && $car->car_ktv_name_1 != null) {
			$this->zaloNotificationCancleK1($car);
		}
		if ($car->car_ktv_phone_2 != null && $car->car_ktv_name_2 != null) {
			$this->zaloNotificationCancleK2($car);
		}

		$car->car_active = 0;
		$car->car_driver_name = '';
		$car->car_driver_phone = '';
		$car->car_ktv_name_1 = '';
		$car->car_ktv_phone_1 = '';
		$car->car_ktv_name_2 = '';
		$car->car_ktv_phone_2 = '';
		$car->save();

		return response()->json(['success' => 'Huỷ lịch thành công.']);
	}

	public function edit_order($order_id)
	{
		$order = Accountant::where('order_id', $order_id)->first();
		$name_path = array_combine(explode(',', $order->order->orderdetail->ord_list_file), explode(',', $order->order->orderdetail->ord_list_file_path));
		$getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
		$files = [
			[
				'source' => Storage::url('path_to_your_file'),
				'options' => [
					'type' => 'local'
				]
			]
		];
		return view('admin.Order.edit_order')->with(compact('order', 'getAllUnit', 'name_path'));
	}
	public function delete_file_order(Request $request)
	{
		$data = $request->all();
		$content = Storage::cloud()->delete($data['path']);
		$orderDetail = OrderDetail::find($data['order_detail_id']);
		$orderDetail->ord_list_file = '';
		$orderDetail->ord_list_file_path = '';
		$orderDetail->save();
		return response()->json($content);
	}
	public function update_order(Request $request, $order_id)
	{
		$this->checkOrderAdmin($request);
		$data = $request->all();
		$order = Order::find($order_id);
		$order->unit_id = $data['unit_id'];
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = formatPrice($data['order_price']);
		$order->order_cost = $data['order_all_in_one'] == 0 ? formatPrice($data['order_cost']) : 0;
		$order->order_all_in_one = $data['order_all_in_one'];
		$order->order_percent_discount = $data['order_percent_discount'];
		$order->order_vat = $data['order_vat'];
		$order->order_warning = $data['order_warning'];
		if ($order->order_status == 0) {
			$order->order_status = 1;
		}
		$order->order_child = $data['order_child'];
		$order->order_surcharge = $data['order_surcharge'];
		$order->save();

		$accountant = Accountant::where('order_id', $order_id)->first();
		$accountant->accountant_owe = formatPrice($data['order_price']);
		$accountant->accountant_distance = $data['accountant_distance'];
		$accountant->save();

		$customer_id = $order->customer_id;
		$order_detail_id = $order->order_detail_id;

		$customer = Customer::find($customer_id);
		$customer->customer_name = $data['customer_name'];
		$customer->customer_phone = $data['customer_phone'];
		$customer->customer_address = $data['customer_address'];
		$customer->customer_note = $data['customer_note'];
		$customer->save();

		$orderDetail = OrderDetail::find($order_detail_id);
		$orderDetail->ord_start_day = $data['ord_start_day'];
		$orderDetail->ord_end_day = $data['ord_end_day'];
		$orderDetail->ord_select = $data['ord_select'];
		$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
		$orderDetail->ord_film = $data['ord_film'];
		$orderDetail->ord_form = $data['ord_form'];
		$orderDetail->ord_print = $data['ord_print'];
		$orderDetail->ord_form_print = $data['ord_form_print'];
		$orderDetail->ord_print_result = $data['ord_print_result'];
		$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
		$orderDetail->ord_note = $data['ord_note'];
		$orderDetail->ord_deadline = $data['ord_deadline'];
		$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
		$orderDetail->ord_cty_name = $data['ord_cty_name'];
		$orderDetail->ord_time = $data['ord_time'];
		$orderDetail->ord_email = $data['ord_email'];
		$get_file = $request->file('ord_list_file');
		if ($get_file) {
			$name = '';
			$path = '';
			foreach ($get_file as $key => $file) {
				$fileData = File::get($file);
				$get_name_file = $file->getClientOriginalName();
				$name_file = current(explode('.', $get_name_file));
				$new_file =  $name_file . rand(0, 99) . '.' . $file->getClientOriginalExtension();
				Storage::cloud()->put($new_file, $fileData);
				$content = collect(Storage::cloud()->listContents());
				$file_path = $content->where('name', '=', $new_file)->first();
				$name .= $new_file . ',';
				$path .= $file_path['path'] . ',';
			}
			$orderDetail->ord_list_file = substr($name, 0, -1);
			$orderDetail->ord_list_file_path = substr($path, 0, -1);
		}
		$orderDetail->save();

		$now = Carbon::now('Asia/Ho_Chi_Minh')->toDateTimeString();
		$history = new HistoryEdit();
		$history->order_id = $order->order_id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Sửa đơn hàng';
		$history->created_at = $now;
		$history->save();

		return Redirect::to('admin/order/list')->with('success', 'Cập nhật thông tin đơn hàng thành công');
	}
	public function coppy_order($order_id)
	{
		$order = Accountant::where('order_id', $order_id)->first();
		$getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
		return view('admin.Order.coppy_order')->with(compact('order', 'getAllUnit'));
	}
	public function save_coppy_order(Request $request)
	{
		$this->checkOrderAdmin($request);
		$data = $request->all();
		// $ord_select = '';

		// foreach($request->ord_select as $key => $ord_sel){
		// 	$ord_select.=$request->ord_select[$key].', ';
		// }
		$customer = new Customer();
		$customer->customer_name = $data['customer_name'];
		$customer->customer_phone = $data['customer_phone'];
		$customer->customer_address = $data['customer_address'];
		$customer->customer_note = $data['customer_note'];
		$customer->save();

		$orderDetail = new OrderDetail();
		$orderDetail->ord_start_day = $data['ord_start_day'];
		$orderDetail->ord_end_day = $data['ord_end_day'];
		$orderDetail->ord_select = $data['ord_select'];
		$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
		$orderDetail->ord_film = $data['ord_film'];
		$orderDetail->ord_form = $data['ord_form'];
		$orderDetail->ord_print = $data['ord_print'];
		$orderDetail->ord_form_print = $data['ord_form_print'];
		$orderDetail->ord_print_result = $data['ord_print_result'];
		$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
		$orderDetail->ord_note = $data['ord_note'];
		$orderDetail->ord_deadline = $data['ord_deadline'];
		$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
		$orderDetail->ord_cty_name = $data['ord_cty_name'];
		$orderDetail->ord_time = $data['ord_time'];
		$orderDetail->ord_email = $data['ord_email'];
		$get_file = $request->file('ord_list_file');

		if ($get_file) {
			$name = '';
			$path = '';
			foreach ($get_file as $key => $file) {
				$fileData = File::get($file);
				$get_name_file = $file->getClientOriginalName();
				$name_file = current(explode('.', $get_name_file));
				$new_file =  $name_file . rand(0, 99) . '.' . $file->getClientOriginalExtension();
				Storage::cloud()->put($new_file, $fileData);
				$content = collect(Storage::cloud()->listContents('test', true));
				$file_path = $content->where('name', '=', $new_file)->first();
				$name .= $new_file . ',';
				$path .= $file_path['path'] . ',';
			}

			$orderDetail->ord_list_file = substr($name, 0, -1);
			$orderDetail->ord_list_file_path = substr($path, 0, -1);
		} else {
			$orderDetail->ord_list_file = '';
			$orderDetail->ord_list_file_path = '';
		}
		$orderDetail->save();

		$order = new Order();
		$order->customer_id = $customer->customer_id;
		$order->order_detail_id = $orderDetail->order_detail_id;
		$order->unit_id = $data['unit_id'];
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = formatPrice($data['order_price']);
		$order->order_cost = $data['order_all_in_one'] == 0 ? formatPrice($data['order_cost']) : 0;
		$order->order_all_in_one = $data['order_all_in_one'];
		$order->order_vat = $data['order_vat'];
		$order->order_percent_discount = $data['order_percent_discount'];
		$order->order_profit = 0;
		$order->order_status = 1;
		$order->schedule_status = 0;
		$order->accountant_updated = 0;
		$order->order_warning = $data['order_warning'];
		$order->order_child = $data['order_child'];
		$order->order_surcharge = $data['order_surcharge'];
		$order->save();

		$format = explode("-", $data['ord_start_day']);

		$accountant = new Accountant();
		$accountant->order_id = $order->order_id;
		$accountant->accountant_owe = formatPrice($data['order_price']);
		$accountant->accountant_distance = $data['accountant_distance'];
		$accountant->save();

		if ($format[1] == 10) {
			$accountant->accountant_month = $format[1];
		} else {
			$month = explode("0", $format[1]);
			if (count($month) > 1) {
				$accountant->accountant_month = $month[1];
			} else {
				$accountant->accountant_month = $month[0];
			}
		}
		$accountant->save();

		$now = Carbon::now('Asia/Ho_Chi_Minh')->toDateTimeString();
		$history = new HistoryEdit();
		$history->order_id = $order->order_id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Thêm đơn hàng';
		$history->created_at = $now;
		$history->save();

		return Redirect::to('admin/order/list')->with('success', 'Thêm đơn hàng thành công');
	}
	public function update_order_schedule(Request $request, $order_id)
	{
		//$this->checkOrderAdmin($request);
		$data = $request->all();

		$order = Order::find($order_id);
		$order->order_warning = $data['order_warning'];
		$order->save();

		$customer_id = $order->customer_id;
		$order_detail_id = $order->order_detail_id;

		$customer = Customer::find($customer_id);
		$customer->customer_name = $data['customer_name'];
		$customer->customer_phone = $data['customer_phone'];
		$customer->customer_address = $data['customer_address'];
		$customer->customer_note = $data['customer_note'];
		$customer->save();

		$orderDetail = OrderDetail::find($order_detail_id);
		$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
		$orderDetail->ord_film = $data['ord_film'];
		$orderDetail->ord_form = $data['ord_form'];
		$orderDetail->ord_print = $data['ord_print'];
		$orderDetail->ord_form_print = $data['ord_form_print'];
		$orderDetail->ord_print_result = $data['ord_print_result'];
		$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
		$orderDetail->ord_note = $data['ord_note'];
		$orderDetail->ord_deadline = $data['ord_deadline'];
		$orderDetail->ord_cty_name = $data['ord_cty_name'];
		$orderDetail->ord_time = $data['ord_time'];
		$orderDetail->save();

		$now = Carbon::now('Asia/Ho_Chi_Minh')->toDateTimeString();
		$history = new HistoryEdit();
		$history->order_id = $order->order_id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Sửa đơn hàng';
		$history->created_at = $now;
		$history->save();

		return response()->json(['success' => 'Cập nhật thông tin đơn hàng thành công']);
	}
	public function export_excel(Request $request)
	{
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
		// $year=$request->year;
		// $month=$request->month;
		//$user = Customer::findOrFail($customer_id);
		// return Excel::download(new ExcelExport($customer_id), $user->customer_name.'.xlsx');

		// return Excel::download(new ExcelExport($date), $date.'.xlsx');
		return Excel::download(new ExcelExport($firstDayofThisMonth, $lastDayofThisMonth), 'Acountant.xlsx');
	}
	public function delete_order($order_id)
	{
		$order = Order::find($order_id);
		$order->delete();
		$customer = Customer::find($order->customer_id);
		$customer->delete();
		$order_detail = OrderDetail::find($order->order_detail_id);
		$order_detail->delete();

		return Redirect()->back()->with('success', 'Xóa đơn hàng thành công');
	}
	public function view_order($order_id)
	{
		$order = Order::find($order_id);
		$customer = Customer::find($order->customer_id);
		$order_detail = OrderDetail::find($order->order_detail_id);
		return view('admin.Order.view_order')->with(compact('order', 'customer', 'order_detail'));
	}

	public function list_history_order()
	{
		$all_history = HistoryEdit::orderBy('history_id', 'DESC')->get();
		return view('admin.History.list_history')->with(compact('all_history'));
	}

	public function print_order($order_id)
	{
		// $pdf = \App::make('dompdf.wrapper');
		// $pdf->loadHTML($this->print_order_convert($order_id));

		// return $pdf->stream();

		$order = Order::find($order_id);
		$customer = Customer::find($order->customer_id);
		$order_detail = OrderDetail::find($order->order_detail_id);
		$now = Carbon::now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i');
		$pdf = PDF::loadView('admin.Order.print_order_convert',  compact('order', 'customer', 'order_detail', 'now'))->setOptions(['defaultFont' => 'sans-serif']);
		return $pdf->stream();
	}

	public function print_order_convert($order_id)
	{
		$order = Order::find($order_id);
		$customer = Customer::find($order->customer_id);
		$order_detail = OrderDetail::find($order->order_detail_id);
		// foreach($order as $key => $ord){
		// 	$customer_id = $ord->customer_id;
		// 	$order_detail_id = $ord-> order_detail_id;

		// }
		// $all_order = Order::orderBy('order_id','DESC')->get();
		// $customer = Customer::where('customer_id',$customer_id)->first();
		// $shipping = Shipping::where('shipping_id',$shipping_id)->first();

		// $order_details = OrderDetails::where('order_code', $checkout_code)->get();

		$now = Carbon::now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s');

		$output = '';

		$output .= '<style>body{
			font-family: DejaVu Sans;
			width: 100%;
		}
		.table-styling{
			width: 100%;
			border:1px solid #000;
			border-collapse: collapse;
			font-weight: 200;
		}
		
		.table-styling thead tr th{
			border:1px solid #000;
		}
		.table-styling tbody tr th{
			font-weight: 300;
			border:1px solid #000;
		}
		p{
			font-weight: 600;
		}
		.title{
			width:100%;
			height: 50px;
			margin-bottom:-30px;
		}
		.title h4{
			width:30%;
			float:left;
			font-size: 30px;
		}
		.title h5{
			width:40%;
			float: left;
			text-align: center;
			margin-top:5px;
			font-weight:400;
			font-size: 15px;
		}
		.title p{
			width:30%;
			float: right;
			text-align: center;
			font-size: 13px;
			margin-top:5px;
			font-weight:400;
		}
		.title_a{
			clear: both;
		}
		.title_a h2{
			text-align: center;
		}
		.title_a h5{
			margin-top:-26px;
			text-align: center;
			font-weight:400;
		}
		.content{
			width:100%;
			font-weight:200;
		}
		.content_left{
			float: left;
			width:55%;
			font-weight:200;
		}
		.content_right{
			float: right;
			width:45%;
			font-weight:200;
		}
		span{
			font-weight:200;
		}
		.signal{
			width:100%;
			margin-top:80px;
		}
		.signal p{
			font-weight:200;
		}

		</style>
		<div class="title">
			<h4>CTY TNHH ĐẦU TƯ TRANG THIẾT BỊ NAM KHÁNH LINH</h4>
			<h5>Hóa đơn </h5>
			<p>' . $now . '<br>59 Đường số 9, KDC Nam Sài Gòn - Thế Kỷ 21, Xã Bình Hưng, Huyện Bình Chánh, TP.Hồ Chí Minh</p>
		</div>
		<div class="title_a">
			<h2>HOÁ ĐƠN</h2>
		</div>
		<div class="content">
			<div class="content_left">
				<h5>Tên khách hàng: ' . $customer->customer_name . '</h5>
				<h5>Số điện thoại: ' . $customer->customer_phone . '</h5>
				<h5>Địa chỉ: ' . $customer->customer_address . '</h5>
			</div>
			<div class="content_right">
			<h5>Ngày lập hóa đơn: ' . $now . '</h5>
			<h5>Mã hóa đơn: ' . $order_id . '</h5>
			<h5>Loại tiền: VNĐ</h5>
			</div>
		</div>
		
		<p>Thông tin đơn hàng:</p>				
			<table class="table-styling">
				<thead>
					<tr>
						<th>Stt</th>
						<th>Bộ phận chụp</th>
						<th>Số lượng</th>
						<th>Thành tiền</th>
					</tr>
				</thead>
				<tbody>';

		$output .= '		
					<tr>
						<th>1</th>
						<th>' . $order->orderdetail->ord_select . '</th>
						<th>' . $order->order_quantity . '</th>
						<th>' . number_format($order->order_price, 0, ',', '.') . '₫' . '</th>				
					</tr>';

		$output .= '<tr>
			<th colspan="6">
				
			</th>
		</tr>
		<tr>
				
		</tr>';
		$output .= '				
				</tbody>
			
		</table>
		<p></p>
		<table>
			<thead>
				<tr>
					<th width="500px">Người lập phiếu</th>
					<th width="200px">Người nhận</th>
				</tr>
			</thead>
			<tbody>	
			</tbody>
		</table>
		<div class="signal">
				<p>Huỳnh Quốc Dương</p>
		</div>';


		return $output;
	}

	public function Storage()
	{
		$content = collect(Storage::cloud()->listContents('test', true));
		dd($content);
	}
	//Validation
	public function checkOrder(Request $request)
	{
		$this->validate(
			$request,
			[
				'customer_name' => 'required',
				'customer_phone' => 'required|numeric|digits_between:10,10',
				'customer_address' => 'required',
				'ord_start_day' => 'required',
				'ord_end_day' => 'required',
				'order_quantity' => 'required',
			],
			[
				'customer_name.required' => 'Vui lòng điền họ và tên',
				'customer_phone.required' => 'Vui lòng điền số điện thoại',
				'customer_phone.digits_between' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_phone.numeric' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_address.required' => 'Vui lòng điền địa chỉ',
				'ord_start_day.required' => 'Vui lòng điền ngày bắt đầu',
				'ord_end_day.required' => 'Vui lòng điền ngày kết thúc',
				'order_quantity.required' => 'Vui lòng điền số lượng chụp',
			]
		);
	}

	public function checkOrderCustomer(Request $request)
	{
		$this->validate(
			$request,
			[
				'customer_name' => 'required',
				'customer_phone' => 'required|numeric|digits_between:10,10',
				'customer_address' => 'required',
				'ord_cty_name' => 'required',
				'ord_start_day' => 'required',
				'ord_end_day' => 'required',
				'ord_deadline' => 'required',
				'ord_deliver_results' => 'required',
				'ord_time' => 'required',
				'order_quantity' => 'required',
			],
			[
				'customer_name.required' => 'Vui lòng điền họ và tên',
				'customer_phone.required' => 'Vui lòng điền số điện thoại',
				'customer_phone.digits_between' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_phone.numeric' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_address.required' => 'Vui lòng điền địa chỉ',
				'ord_cty_name.required' => 'Vui lòng điền tên công ty',
				'ord_start_day.required' => 'Vui lòng điền ngày bắt đầu',
				'ord_end_day.required' => 'Vui lòng điền ngày kết thúc',
				'ord_deadline.required' => 'Vui lòng điền ngày trả kết quả',
				'ord_deliver_results.required' => 'Vui lòng điền thông tin nhận kết quả',
				'ord_time.required' => 'Vui lòng điền giờ khám',
				'order_quantity.required' => 'Vui lòng điền số lượng chụp',
			]
		);
	}

	public function checkOrderAdmin(Request $request)
	{
		$this->validate(
			$request,
			[
				'customer_name' => 'required',
				'customer_phone' => 'required|numeric|digits_between:10,10',
				'customer_address' => 'required',
				'ord_cty_name' => 'required',
				'ord_start_day' => 'required',
				'ord_end_day' => 'required',
				'ord_deadline' => 'required',
				'ord_deliver_results' => 'required',
				'ord_time' => 'required',
				'order_quantity' => 'required',
				'order_price' => 'required',
			],
			[
				'customer_name.required' => 'Vui lòng điền họ và tên',
				'customer_phone.required' => 'Vui lòng điền số điện thoại',
				'customer_phone.digits_between' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_phone.numeric' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_address.required' => 'Vui lòng điền địa chỉ',
				'ord_cty_name.required' => 'Vui lòng điền tên công ty',
				'ord_start_day.required' => 'Vui lòng điền ngày bắt đầu',
				'ord_end_day.required' => 'Vui lòng điền ngày kết thúc',
				'ord_deadline.required' => 'Vui lòng điền ngày trả kết quả',
				'ord_deliver_results.required' => 'Vui lòng điền thông tin nhận kết quả',
				'ord_time.required' => 'Vui lòng điền giờ khám',
				'order_quantity.required' => 'Vui lòng điền số lượng chụp',
				'order_price.required' => 'Vui lòng điền tổng tiền',
			]
		);
	}
}
