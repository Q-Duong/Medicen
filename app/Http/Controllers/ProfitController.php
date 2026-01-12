<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfitReport;
use App\Models\Unit;
use App\Models\HistoryEdit;
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

    public function index()
    {
        $getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
        $reports = ProfitReport::with('unit')->orderBy('created_at', 'desc')->get();
        return view('pages.admin.calculation.profit_calculation', compact('reports', 'getAllUnit'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'total_revenue' => 'required|numeric',
            'input_data' => 'required|array',
        ]);

        $report = ProfitReport::create([
            'unit_id' => $request->unit_id,
            'comments' => $request->comments,
            'total_revenue' => $request->total_revenue,
            'total_cost' => $request->total_cost,
            'net_profit' => $request->net_profit,
            'profit_margin' => $request->profit_margin,
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
