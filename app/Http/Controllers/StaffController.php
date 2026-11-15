<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRequestForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Staff;

class StaffController extends Controller
{
    //Admin
    public function index(Request $request)
    {
        // Lọc theo vai trò (?role=KTV|TX), có / chưa có CCHN (?cchn=1|0), tìm theo tên / SĐT (?q=)
        $query = Staff::query();
        if (in_array($request->role, ['KTV', 'TX'], true)) {
            $query->where('staff_role', $request->role);
        }
        if (in_array((string) $request->cchn, ['0', '1'], true)) {
            $query->where('staff_certificate', (int) $request->cchn);
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(fn ($w) => $w->where('staff_name', 'LIKE', "%{$q}%")->orWhere('staff_phone', 'LIKE', "%{$q}%"));
        }
        $getAllStaff = $query->orderBy('id', 'DESC')->paginate(10)->withQueryString();
        $counts = [
            'all' => Staff::count(),
            'KTV' => Staff::where('staff_role', 'KTV')->count(),
            'TX' => Staff::where('staff_role', 'TX')->count(),
        ];
        $hired = self::hiredNames();

        return view('pages.admin.staff.index', compact('getAllStaff', 'counts', 'hired'));
    }

    /** Nhân sự "thuê ngoài" dùng trong Xếp lịch (KTV Thuê, TX Thuê): không cho xóa, đổi tên */
    public static function hiredNames(): array
    {
        return array_values(array_filter([config('schedule.hired_ktv'), config('schedule.hired_driver')]));
    }

    public function create()
    {
        return view('pages.admin.staff.create');
    }

    public function store(StaffRequestForm $request)
    {
        $data = $request->all();
        $staff = new Staff();
        $staff->staff_name = $data['staff_name'];
        $staff->staff_phone = $data['staff_phone'];
        $staff->staff_gender = $data['staff_gender'];
        $staff->staff_birthday = $data['staff_birthday'];
        $staff->staff_role = $data['staff_role'];
        $staff->staff_certificate = $request->boolean('staff_certificate');
        $staff->staff_certificate_note = $request->filled('staff_certificate_note') ? trim($data['staff_certificate_note']) : null;
        $staff->save();
        return Redirect::route('staff.index')->with('success', 'Thêm nhân viên thành công');
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        $hired = in_array($staff->staff_name, self::hiredNames(), true);
        return view('pages.admin.staff.edit', compact('staff', 'hired'));
    }
    public function update(StaffRequestForm $request, $id)
    {
        $data = $request->all();
        $staff = Staff::findOrFail($id);
        // "KTV Thuê" / "TX Thuê" là tên cố định Xếp lịch dùng: giữ nguyên tên và vai trò
        if (in_array($staff->staff_name, self::hiredNames(), true)) {
            $data['staff_name'] = $staff->staff_name;
            $data['staff_role'] = $staff->staff_role;
        }
        $staff->staff_name = $data['staff_name'];
        $staff->staff_phone =  $data['staff_phone'];
        $staff->staff_gender = $data['staff_gender'];
        $staff->staff_birthday = $data['staff_birthday'];
        $staff->staff_role = $data['staff_role'];
        $staff->staff_certificate = $request->boolean('staff_certificate');
        $staff->staff_certificate_note = $request->filled('staff_certificate_note') ? trim($data['staff_certificate_note']) : null;
        $staff->save();
        return Redirect::route('staff.index')->with('success', 'Cập nhật thông tin nhân viên thành công');
    }

    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);
        if (in_array($staff->staff_name, self::hiredNames(), true)) {
            return Redirect::route('staff.index')->with('error', '"' . $staff->staff_name . '" là nhân sự thuê ngoài dùng trong Xếp lịch, không xóa được.');
        }
        $name = $staff->staff_name;
        $staff->leaves()->delete();
        $staff->delete();
        return Redirect::route('staff.index')->with('success', 'Đã xóa nhân viên ' . $name . '. Các lịch xe cũ vẫn giữ tên người này.');
    }
    //End Admin

    public function formatDate($date)
    {
        $format = explode("/", $date);
        $day = array_shift($format);
        $year = array_pop($format);
        $month = implode(" ", $format);
        $dateFormat = $year . "-" . $month . "-" . $day;
        return $dateFormat;
    }
}
