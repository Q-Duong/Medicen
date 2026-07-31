<?php

namespace App\Http\Controllers;

use App\Repositories\ReportRepositoryInterface;
use App\Http\Requests\UpdateReportRequest;
use App\Mail\ReportUpdatedMail;
use App\Models\Report;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportRepo;

    public function __construct(ReportRepositoryInterface $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    public function index(Request $request)
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        if ($month === 'all') {
            $reports = Report::with('items')->where('year', $year)->get();
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
            $isYearly = false;
        }

        $accStats = $this->reportRepo->getAccountantStats($month, $year);

        return view('pages.admin.reports.index', compact('report', 'month', 'year', 'accStats', 'isYearly'));
    }

    public function update(UpdateReportRequest $request, $id)
    {
        $report = $this->reportRepo->updateReportItems($id, $request->validated('items'));

        if ($request->boolean('send_mail')) {
            $updatedItem = $request->input('items.0'); 
            
            Mail::to('quocduong081000@gmail.com')->send(new ReportUpdatedMail($report, $updatedItem));
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
}