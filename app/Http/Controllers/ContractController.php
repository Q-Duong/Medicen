<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\Statistic;
use App\Models\Accountant;
use App\Models\HistoryEdit;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ContractController extends Controller
{
	public function index()
	{
		if (!Session::has('year')) {
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.contract.index');
	}

	public function getContract(Request $request)
	{
		if (isset($request->year) && !empty($request->year)) {
			$year = $request->year;
		} else {
			$year = Session::has('year') ? Session::get('year') : Carbon::now()->year;
		}
		Session::put('year', $year);
		$getAllContract = Accountant::getAllContractByFilter($year);
		$unitNames = $getAllContract->pluck('unit_name')->unique()->sort();
		$accountantNumbers = $getAllContract->pluck('accountant_number')->unique()->sort();
		$accountantDates = $getAllContract->where('accountant_date', '!=', null)->pluck('accountant_date')->unique()->sort();
		$liquidationNumbers = $getAllContract->where('liquidation_number', '!=', null)->pluck('liquidation_number')->unique()->sort();
		$contractTypes = $getAllContract->where('contract_type', '!=', null)->pluck('contract_type')->unique()->sort();
		$contractDates = $getAllContract->where('contract_date', '!=', null)->pluck('contract_date')->unique()->sort();
		$html = view('pages.admin.contract.render')->with(compact('getAllContract', 'unitNames', 'accountantNumbers', 'accountantDates', 'liquidationNumbers', 'contractTypes', 'contractDates'))->render();
		return response()->json(array('success' => true, 'html' => $html));
	}

	public function update(Request $request)
	{
		$data = $request->all();
		$paramsDeny = ['accountant_status', 'accountant_method', 'accountant_doctor_read'];
		$subParams = ['order_quantity', 'order_cost', 'accountant_amount_paid', 'order_discount'];
		$currentChange = $data['currentChange'];
		$accountants = Accountant::select(
			'id',
			$currentChange
		)->where('accountant_number', $data['accountant_number'])->get();

		foreach ($accountants as $key => $accountant) {
			$accountant->$currentChange =  $data[$currentChange];
			$accountant->save();
		}
		// if (!in_array($currentChange, $paramsDeny)) {
		// 	$year = Session::get('year');
		// 	if (in_array($currentChange, $subParams)) {
		// 		switch ($currentChange) {
		// 			case 'order_quantity':
		// 				$subFilters = Accountant::renewFilterWhenUpdated('order_price', $year);
		// 				$subCurrentChange = 'order_price';
		// 				break;
		// 			case 'order_cost':
		// 				$subFilters = Accountant::renewFilterWhenUpdated('order_price', $year);
		// 				$subCurrentChange = 'order_price';
		// 				break;
		// 			case 'accountant_amount_paid':
		// 				$subFilters = Accountant::renewFilterWhenUpdated('accountant_owe', $year);
		// 				$subCurrentChange = 'accountant_owe';
		// 				break;
		// 			case 'order_discount':
		// 				$subFilters = Accountant::renewFilterWhenUpdated('order_profit', $year);
		// 				$subCurrentChange = 'order_profit';
		// 				break;
		// 		}
		// 		$filters = Accountant::renewFilterWhenUpdated($currentChange, $year);

		// 		$html = view('pages.admin.accountant.filter_render')->with(compact('filters', 'currentChange'))->render();
		// 		$subHtml = view('pages.admin.accountant.sub_filter_render')->with(compact('subFilters', 'subCurrentChange'))->render();
		// 		$className = str_replace('_', '-', $currentChange);
		// 		$subClassName = str_replace('_', '-', $subCurrentChange);
		// 		return response()->json(array('success' => true, 'multi' => true, 'html' => $html, 'className' => $className, 'subHtml' => $subHtml, 'subClassName' => $subClassName));
		// 	}
		// 	$filters = Accountant::renewFilterWhenUpdated($currentChange, $year);
		// 	$className = str_replace('_', '-', $currentChange);
		// 	$html = view('pages.admin.accountant.filter_render')->with(compact('filters', 'currentChange'))->render();
		// 	return response()->json(array('success' => true, 'multi' => false, 'html' => $html, 'className' => $className));
		// }
		return response()->json(array('success' => true));
	}

	public function filter(Request $request)
	{
		$searchData = $request->all();
		$flagEmpty = false;
		$year = Session::get('year');
		$getAllContract = Accountant::getContractQueryBuilderBySearchData($searchData, $year);
		if (empty(array_filter($searchData))) {
			$flagEmpty = true;
		}
		$html = view('pages.admin.contract.filter_index')->with(compact('getAllContract'))->render();
		return response()->json(array('html' => $html, 'flagEmpty' => $flagEmpty));
	}

	//View Only
	public function indexViewOnly()
	{
		if (!Session::has('year')) {
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.contract.viewOnly.index');
	}

	public function getContractViewOnly(Request $request)
	{
		if (isset($request->year) && !empty($request->year)) {
			$year = $request->year;
		} else {
			$year = Session::has('year') ? Session::get('year') : Carbon::now()->year;
		}
		Session::put('year', $year);
		$getAllContract = Accountant::getAllContractByFilter($year);
		$unitNames = $getAllContract->pluck('unit_name')->unique()->sort();
		$accountantNumbers = $getAllContract->pluck('accountant_number')->unique()->sort();
		$accountantDates = $getAllContract->where('accountant_date', '!=', null)->pluck('accountant_date')->unique()->sort();
		$liquidationNumbers = $getAllContract->where('liquidation_number', '!=', null)->pluck('liquidation_number')->unique()->sort();
		$contractTypes = $getAllContract->where('contract_type', '!=', null)->pluck('contract_type')->unique()->sort();
		$contractDates = $getAllContract->where('contract_date', '!=', null)->pluck('contract_date')->unique()->sort();
		$html = view('pages.admin.contract.viewOnly.render')->with(compact('getAllContract', 'unitNames', 'accountantNumbers', 'accountantDates', 'liquidationNumbers', 'contractTypes', 'contractDates'))->render();
		return response()->json(array('success' => true, 'html' => $html));
	}

	public function filterViewOnly(Request $request)
	{
		$searchData = $request->all();
		$flagEmpty = false;
		$year = Session::get('year');
		$getAllContract = Accountant::getContractQueryBuilderBySearchData($searchData, $year);
		if (empty(array_filter($searchData))) {
			$flagEmpty = true;
		}
		$html = view('pages.admin.contract.viewOnly.filter_index')->with(compact('getAllContract'))->render();
		return response()->json(array('html' => $html, 'flagEmpty' => $flagEmpty));
	}
}
