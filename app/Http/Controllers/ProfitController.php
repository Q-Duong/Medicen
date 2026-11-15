<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfitReport;
use App\Models\Unit;
use App\Models\HistoryEdit;
use App\Services\ProfitCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class ProfitController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    private $locale;

    public function __construct()
    {
        $this->locale = App::getLocale();
    }

    public function index(ProfitCalculator $calculator)
    {
        $getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
        $reports = ProfitReport::with('unit')->orderBy('created_at', 'desc')->get();
        $profitDefaults = $calculator->defaults();
        return view('pages.admin.calculation.profit_calculation', compact('reports', 'getAllUnit', 'profitDefaults'));
    }

    public function store(Request $request, ProfitCalculator $calculator)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'input_data' => 'required|array',
        ]);

        // Tính lại ở server bằng cùng công thức với trang (không dùng số tổng trình duyệt gửi lên)
        $result = $calculator->calculate($request->input_data);

        $report = ProfitReport::create([
            'unit_id' => $request->unit_id,
            'comments' => $request->comments,
            'total_revenue' => $result['total_revenue'],
            'total_cost' => $result['total_cost'],
            'net_profit' => $result['net_profit'],
            'profit_margin' => $result['profit_margin'],
            'input_data' => $request->input_data // Laravel tự convert array sang JSON
        ]);

        $history = new HistoryEdit();
        $history->order_id = $report->id;
        $history->user_name = Auth::user()->email;
        $history->history_action = 'Thêm đơn tính lợi nhuận';
        $history->save();

        return response()->json([
            'success' => true,
            'message' => 'Lưu báo cáo thành công!',
            'id' => $report->id
        ]);
    }

    public function show($id)
    {
        $report = ProfitReport::findOrFail($id);
        return response()->json($report);
    }

    public function destroy($id)
    {
        ProfitReport::destroy($id);
        return response()->json(['success' => true]);
    }

    public function approve($id)
    {
        $report = ProfitReport::findOrFail($id);

        $report->status = 1;
        $report->save();

        $history = new HistoryEdit();
        $history->order_id = $report->id;
        $history->user_name = Auth::user()->email;
        $history->history_action = 'Duyệt đơn';
        $history->save();

        return response()->json(['success' => true]);
    }
}
