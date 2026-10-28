<?php

namespace App\Http\Controllers;

use App\Enums\UploadStatus;
use App\Http\Requests\ExcelUploadRequest;
use App\Models\Unit;
use App\Services\OrderExcelReader;
use App\Models\ExcelUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelUploadController extends Controller
{
    private const DISK   = 'local';          // storage/app (private, không public ra web)
    private const FOLDER = 'excel-uploads';

    /** Danh sách + lọc, mới nhất ở trên */
    public function index(Request $request)
    {
        $filters = array_filter(
            $request->only(['unit_id', 'tu_ngay', 'den_ngay', 'trang_thai', 'q']),
            fn ($v) => $v !== null && $v !== ''
        );

        $uploads = ExcelUpload::with('unit')
            ->filter($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Đếm theo trạng thái (áp dụng các bộ lọc khác, trừ trạng thái)
        $counts = ExcelUpload::filter(Arr::except($filters, 'trang_thai'))
            ->toBase()
            ->selectRaw('trang_thai, COUNT(*) as total')
            ->groupBy('trang_thai')
            ->pluck('total', 'trang_thai');

        return view('pages.admin.excel-uploads.index', [
            'uploads'  => $uploads,
            'units'    => Unit::orderBy('unit_name')->get(),
            'filters'  => $filters,
            'counts'   => $counts,
            'statuses' => UploadStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('pages.admin.excel-uploads.create', [
            'upload' => new ExcelUpload(),
            'units'  => Unit::orderBy('unit_name')->get(),
        ]);
    }

    public function store(ExcelUploadRequest $request, OrderExcelReader $reader)
    {
        $file = $request->file('file');
        $path = $this->storeFile($file);

        // Ngày chụp lấy từ file, không nhập tay
        [$ngay, $error] = $this->readStartDay($reader, $path);
        if ($error) {
            Storage::disk(self::DISK)->delete($path);
            return back()->withInput()->withErrors(['file' => $error]);
        }

        ExcelUpload::create([
            'unit_id'      => $request->integer('unit_id'),
            'ngay'         => $ngay,
            'ghi_chu'      => $request->input('ghi_chu'),
            'ten_file'     => $file->getClientOriginalName(),
            'duong_dan'    => $path,
            'kich_thuoc'   => $file->getSize(),
            'trang_thai'   => UploadStatus::ChoTiepNhan,
            'nguoi_tao_id' => auth()->id(),
        ]);

        return redirect()
            ->route('excel-uploads.index')
            ->with('success', 'Đã thêm file "'.$file->getClientOriginalName().'".');
    }

    /** Xem chi tiết + xem trước nội dung sheet đầu tiên */
    public function show(ExcelUpload $excelUpload)
    {
        $excelUpload->load(['unit', 'nguoiTao', 'nguoiTiepNhan']);
        [$rows, $previewError, $truncated] = $this->readPreview($excelUpload);

        return view('pages.admin.excel-uploads.show', [
            'upload'       => $excelUpload,
            'rows'         => $rows,
            'previewError' => $previewError,
            'truncated'    => $truncated,
        ]);
    }

    public function download(ExcelUpload $excelUpload)
    {
        abort_unless(Storage::disk(self::DISK)->exists($excelUpload->duong_dan), 404, 'Không tìm thấy file.');

        return Storage::disk(self::DISK)->download($excelUpload->duong_dan, $excelUpload->ten_file);
    }

    public function edit(ExcelUpload $excelUpload)
    {
        if ($redirect = $this->guardEditable($excelUpload)) {
            return $redirect;
        }

        return view('pages.admin.excel-uploads.edit', [
            'upload' => $excelUpload,
            'units'  => Unit::orderBy('unit_name')->get(),
        ]);
    }

    public function update(ExcelUploadRequest $request, ExcelUpload $excelUpload, OrderExcelReader $reader)
    {
        if ($redirect = $this->guardEditable($excelUpload)) {
            return $redirect;
        }

        $excelUpload->fill([
            'unit_id'   => $request->integer('unit_id'),
            'ghi_chu'   => $request->input('ghi_chu'),
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $this->storeFile($file);

            [$ngay, $error] = $this->readStartDay($reader, $path);
            if ($error) {
                Storage::disk(self::DISK)->delete($path);
                return back()->withInput()->withErrors(['file' => $error]);
            }

            Storage::disk(self::DISK)->delete($excelUpload->duong_dan);
            $excelUpload->fill([
                'ngay'       => $ngay,
                'ten_file'   => $file->getClientOriginalName(),
                'duong_dan'  => $path,
                'kich_thuoc' => $file->getSize(),
            ]);
        }

        $excelUpload->save();

        return redirect()
            ->route('excel-uploads.show', $excelUpload)
            ->with('success', 'Đã lưu thay đổi.');
    }

    public function destroy(ExcelUpload $excelUpload)
    {
        if ($redirect = $this->guardEditable($excelUpload)) {
            return $redirect;
        }

        $name = $excelUpload->ten_file;
        Storage::disk(self::DISK)->delete($excelUpload->duong_dan);
        $excelUpload->delete();

        return redirect()
            ->route('excel-uploads.index')
            ->with('success', 'Đã xoá file "'.$name.'".');
    }

    /** Tiếp nhận file (chỉ bước này bấm tay) → mở trang tạo đơn */
    public function updateStatus(Request $request, ExcelUpload $excelUpload)
    {
        if ($excelUpload->order_id) {
            return back()->with('error', 'File đã có đơn hàng #'.$excelUpload->order_id.', trạng thái tự cập nhật theo đơn.');
        }

        $allowed = array_map(fn (UploadStatus $s) => $s->value, $excelUpload->trang_thai->nextSteps());

        $data = $request->validate([
            'trang_thai' => ['required', Rule::in($allowed)],
        ], [
            'trang_thai.in' => 'Không thể chuyển trạng thái từ "'.$excelUpload->trang_thai->label().'".',
        ]);

        $excelUpload->trang_thai = UploadStatus::from($data['trang_thai']);

        if ($excelUpload->trang_thai === UploadStatus::DaTiepNhan) {
            $excelUpload->tiep_nhan_luc      = now();
            $excelUpload->nguoi_tiep_nhan_id = auth()->id();
        }

        $excelUpload->save();

        if ($excelUpload->canCreateOrder()) {
            return redirect()->route('excel-uploads.to-order', $excelUpload);
        }

        return back()->with('success', '"'.$excelUpload->ten_file.'" chuyển sang: '.$excelUpload->trang_thai->label().'.');
    }

    /** Đọc file và chuyển sang trang tạo đơn, điền sẵn bằng old() */
    public function toOrder(ExcelUpload $excelUpload, OrderExcelReader $reader)
    {
        $back = redirect()->route('excel-uploads.show', $excelUpload);

        if ($excelUpload->order_id) {
            return $back->with('error', 'File này đã tạo đơn hàng #'.$excelUpload->order_id.'.');
        }
        if (! $excelUpload->canCreateOrder()) {
            return $back->with('error', 'Bấm "Tiếp nhận" trước khi tạo đơn.');
        }
        if (! Storage::disk(self::DISK)->exists($excelUpload->duong_dan)) {
            return $back->with('error', 'Không tìm thấy file trên máy chủ.');
        }

        try {
            $result = $reader->read(Storage::disk(self::DISK)->path($excelUpload->duong_dan));
        } catch (\Throwable $e) {
            report($e);
            return $back->with('error', 'Không đọc được file Excel. Kiểm tra file có đúng mẫu không.');
        }

        if ($result['errors']) {
            return $back->with('error', implode(' ', $result['errors']));
        }

        $input = array_merge($result['data'], [
            'unit_id'         => $excelUpload->unit_id,   // đơn vị lấy theo file upload
            'excel_upload_id' => $excelUpload->id,
        ]);

        return redirect()
            ->route('order.create')
            ->withInput($input)
            ->with('importNotice', [
                'file'     => $excelUpload->ten_file,
                'warnings' => $result['warnings'],
            ]);
    }

    /** Tải file Excel mẫu */
    public function template()
    {
        return response()->download(config('order_import.template_path'), 'mau-don-hang-nkl.xlsx');
    }

    // ───────────────────────── helpers ─────────────────────────

    private function storeFile(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return $file->storeAs(
            self::FOLDER.'/'.now()->format('Y/m'),
            Str::uuid().'.'.$ext,
            self::DISK
        );
    }

    /** Đọc "Ngày chụp" trong file. Trả về [Y-m-d|null, lỗi|null] */
    private function readStartDay(OrderExcelReader $reader, string $path): array
    {
        try {
            $result = $reader->read(Storage::disk(self::DISK)->path($path));
        } catch (\Throwable $e) {
            report($e);
            return [null, 'Không đọc được file Excel. Kiểm tra file có đúng mẫu không.'];
        }

        if ($result['errors']) {
            return [null, $result['errors'][0]];
        }

        if (empty($result['data']['ord_start_day'])) {
            return [null, 'File chưa có “Ngày chụp” hoặc ghi sai dạng. Điền ngày dạng 25/11/2026 rồi tải lên lại.'];
        }

        return [$result['data']['ord_start_day'], null];
    }

    private function guardEditable(ExcelUpload $upload)
    {
        if ($upload->trang_thai->canEdit()) {
            return null;
        }

        return redirect()
            ->route('excel-uploads.show', $upload)
            ->with('error', 'File đã ở trạng thái "'.$upload->trang_thai->label().'" nên không sửa hoặc xoá được.');
    }

    /** @return array{0: array, 1: ?string, 2: bool} */
    private function readPreview(ExcelUpload $upload, int $maxRows = 200, int $maxCols = 30): array
    {
        if (! Storage::disk(self::DISK)->exists($upload->duong_dan)) {
            return [[], 'Không tìm thấy file trên máy chủ.', false];
        }

        try {
            $path   = Storage::disk(self::DISK)->path($upload->duong_dan);
            $sheet  = IOFactory::load($path)->getActiveSheet();

            $highestRow = $sheet->getHighestDataRow();
            $highestCol = min(Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), $maxCols);
            $lastRow    = min($highestRow, $maxRows);

            $rows = $sheet->rangeToArray(
                'A1:'.Coordinate::stringFromColumnIndex($highestCol).$lastRow,
                null, true, true, false
            );

            return [$rows, null, $highestRow > $maxRows];
        } catch (\Throwable $e) {
            report($e);

            return [[], 'Không đọc được nội dung file. Tải file về để mở bằng Excel.', false];
        }
    }
}
