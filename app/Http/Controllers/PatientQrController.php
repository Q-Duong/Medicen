<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientQrController extends Controller
{
    // 1. Hiển thị form upload
    public function index()
    {
        return view('pages.qr.qr-upload');
    }

    // 2. Xử lý file CSV và trả về trang in
    public function process(Request $request)
    {
        // Kiểm tra xem đã chọn file chưa
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xls,xlsx'
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        // Mở file và đọc dữ liệu
        $data = array_map('str_getcsv', file($path));
        
        // Lấy dòng tiêu đề (Header) để tìm vị trí cột
        $header = $data[0];
        
        // Tìm vị trí cột (index) dựa trên tên cột trong file của bạn
        // Lưu ý: Tên cột phải khớp hoặc gần giống file bạn gửi
        $nameIndex = null;
        $linkIndex = null;

        foreach ($header as $index => $colName) {
            // Chuyển về chữ thường để so sánh cho dễ
            $colName = strtolower(trim($colName)); 
            
            if (str_contains($colName, 'tên bệnh nhân')) {
                $nameIndex = $index;
            }
            if (str_contains($colName, 'link') || str_contains($colName, 'share')) {
                $linkIndex = $index;
            }
        }

        // Nếu không tìm thấy cột, báo lỗi
        if ($nameIndex === null || $linkIndex === null) {
            return back()->withErrors(['msg' => 'Không tìm thấy cột "Tên bệnh nhân" hoặc "Link" trong file.']);
        }

        // Duyệt qua các dòng dữ liệu (bỏ dòng đầu tiên là header)
        $patients = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            
            // Kiểm tra dòng có đủ dữ liệu không
            if (isset($row[$nameIndex]) && isset($row[$linkIndex])) {
                $link = trim($row[$linkIndex]);
                // Chỉ lấy dòng nào có link
                if (!empty($link) && $link !== 'nan') {
                    $patients[] = [
                        'name' => trim($row[$nameIndex]),
                        'link' => $link
                    ];
                }
            }
        }

        // Trả về view in với danh sách bệnh nhân
        return view('pages.qr-print', compact('patients'));
    }

	public function processV3(Request $request)
    {
        $request->validate(['excel_file' => 'required|file|mimes:xlsx,xls,csv']);

        // 1. Đọc file Excel (Giữ nguyên như cũ)
        try {
            $data = Excel::toArray([], $request->file('excel_file'))[0];
        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Lỗi file: ' . $e->getMessage()]);
        }

        // 2. Tìm cột Tên và Link (Giữ nguyên logic cũ)
        $header = $data[0];
        $nameIndex = null;
        $linkIndex = null;
        foreach ($header as $index => $colName) {
            $col = strtolower(trim((string)$colName));
            if (str_contains($col, 'tên bệnh nhân')) $nameIndex = $index;
            if (str_contains($col, 'link') || str_contains($col, 'share')) $linkIndex = $index;
        }

        if ($nameIndex === null || $linkIndex === null) {
            return back()->withErrors(['msg' => 'Không tìm thấy cột Tên hoặc Link']);
        }

        // 3. Chuẩn bị dữ liệu
        $patients = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            if (isset($row[$linkIndex]) && !empty($row[$linkIndex])) {
                $link = trim((string)$row[$linkIndex]);
                // Tạo mã QR dạng SVG string để nhúng vào PDF cho nét
                // Chúng ta sẽ generate QR ngay trong View cho dễ, chỉ cần truyền data
                $patients[] = [
                    'name' => trim((string)$row[$nameIndex]),
                    'link' => $link
                ];
            }
        }

        // 4. Xuất ra PDF
        // loadView sẽ lấy file blade và convert sang PDF
        $pdf = Pdf::loadView('pages.qr-print', compact('patients'));
        
        // setPaper('a4', 'portrait'): Khổ A4 đứng
        $pdf->setPaper('a4', 'portrait');

        // Tải xuống file
        return $pdf->download('DanhSach_QR_BenhNhan.pdf');
    }
}