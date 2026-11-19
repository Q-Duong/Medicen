<?php

namespace App\Http\Controllers;

use App\Repositories\ReportRepositoryInterface;
use App\Http\Requests\UpdateReportRequest;
use App\Mail\ReportUpdatedMail;
use App\Models\Report;
use App\Models\ReportItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected $reportRepo;

    public function __construct(ReportRepositoryInterface $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    /**
     * Người được lập / sửa báo cáo thu chi: tài khoản Kế toán (role 3 hoặc phòng ban ke_toan).
     * Các tài khoản khác vào được trang (nhóm quyền debt_accountant) chỉ xem.
     */
    public static function canEdit($user): bool
    {
        return $user && ((int) $user->role === 3 || $user->department === 'ke_toan');
    }

    private function authorizeEdit(): void
    {
        abort_unless(self::canEdit(auth()->user()), 403, 'Chỉ phòng Kế toán được sửa báo cáo thu chi.');
    }

    public function index(Request $request)
    {
        $month = $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));
        if ($month !== 'all') {
            $month = max(1, min(12, (int) $month));
        }

        if ($month === 'all') {
            $reports = Report::with(['items' => function ($query) {
                $query->orderBy('sort_order', 'asc');
            }])->where('year', $year)->get();

            $allItems = collect();
            foreach ($reports as $r) {
                $allItems = $allItems->merge($r->items);
            }

            $report = new Report();
            $report->id = 0;
            $report->month = 'all';
            $report->year = $year;
            $report->setRelation('items', $allItems);

            $isYearly = true;
        } else {
            $report = $this->reportRepo->getReportByPeriod($month, $year);
            // Dòng VCB chú Lộc / CT lấy số từ Sổ VCB và Công nợ CT
            app(\App\Services\Accounting\ReportAutoSync::class)->sync($report);
            $report->load(['items' => fn ($q) => $q->orderBy('sort_order', 'asc')]);
            $isYearly = false;
        }

        $accStats = $this->reportRepo->getAccountantStats($month, $year);

        return view('pages.admin.reports.index', compact('report', 'month', 'year', 'accStats', 'isYearly'));
    }

    public function seedDefault($id)
    {
        $this->authorizeEdit();
        $report = Report::findOrFail($id);
        if ($report->items()->exists()) {
            return redirect()->back()->with('error', 'Báo cáo tháng này đã có dữ liệu.');
        }

        $this->reportRepo->seedDefaultItems($report);
        $sync = app(\App\Services\Accounting\ReportAutoSync::class);
        $sync->tagDefaults($report);
        $sync->sync($report);

        return redirect()->back()->with('success', 'Đã tạo khung báo cáo mẫu thành công!');
    }

    public function update(UpdateReportRequest $request, $id)
    {
        $this->authorizeEdit();
        $report = $this->reportRepo->updateReportItems($id, $request->validated('items'));

        if ($request->boolean('send_mail')) {
            $updatedItem = $request->input('items.0');

            // Người nhận đặt trong .env: REPORT_UPDATED_TO (xem config/reports.php)
            if ($to = config('reports.updated_to')) {
                Mail::to($to)->send(new ReportUpdatedMail($report, $updatedItem));
            } else {
                Log::warning('Báo cáo thu chi: chưa cấu hình REPORT_UPDATED_TO trong .env, không gửi email', ['report' => $id]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Đã cập nhật dòng thành công!',
                'items'   => $report->items
            ]);
        }

        return redirect()->back()->with('success', 'Lưu báo cáo thành công!');
    }

    public function updateOrder(Request $request)
    {
        $this->authorizeEdit();
        $orders = $request->input('orders');

        if ($orders && is_array($orders)) {
            foreach ($orders as $index => $id) {
                ReportItem::where('id', $id)->update(['sort_order' => $index]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function exportPdf($id)
    {
        app(\App\Services\Accounting\ReportAutoSync::class)->sync(Report::findOrFail($id));
        // 1. Tìm cuốn báo cáo và load items kèm theo
        $report = Report::with(['items' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }])->findOrFail($id);

        $month = $report->month;
        $year = $report->year;

        // Lấy thống kê từ Accountant (giống như ở hàm index)
        $accStats = $this->reportRepo->getAccountantStats($month, $year);

        // 2. Tính toán các tổng số (Thay thế cho Javascript trên web)
        $totalThuEst = $report->items->where('type', 'thu')->sum('estimated_amount');
        $totalThuAct = $report->items->where('type', 'thu')->sum('actual_amount');

        $totalChiEst = $report->items->where('type', 'chi')->sum('estimated_amount');
        $totalChiAct = $report->items->where('type', 'chi')->sum('actual_amount');

        $balanceEst = $totalThuEst - $totalChiEst;

        // 3. Truyền toàn bộ biến ra View PDF
        $pdf = Pdf::loadView('pages.admin.reports.pdf_template', compact(
            'report', 'month', 'year', 'accStats',
            'totalThuEst', 'totalThuAct',
            'totalChiEst', 'totalChiAct',
            'balanceEst'
        ));

        // 4. Cấu hình khổ giấy A4 nằm ngang và xuất file
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download("Bao_Cao_Thu_Chi_Thang_{$month}_{$year}.pdf");
    }
}
