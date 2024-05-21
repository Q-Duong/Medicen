<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRequestForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Staff;

class StaffController extends Controller
{
    //Admin
    public function index()
    {
        $getAllStaff = Staff::orderBy('id', 'DESC')->paginate(10);
        return view('pages.admin.staff.index', compact('getAllStaff'));
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
        $staff->save();
        return Redirect::route('staff.index')->with('success', 'Thêm nhân viên thành công');
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        return view('pages.admin.staff.edit', compact('staff'));
    }
    public function update(StaffRequestForm $request, $id)
    {
        $data = $request->all();
        $staff = Staff::findOrFail($id);
        $staff->staff_name = $data['staff_name'];
        $staff->staff_phone =  $data['staff_phone'];
        $staff->staff_gender = $data['staff_gender'];
        $staff->staff_birthday = $data['staff_birthday'];
        $staff->staff_role = $data['staff_role'];
        $staff->save();
        return Redirect::route('staff.index')->with('success', 'Cập nhật thông tin nhân viên thành công');
    }

    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->delete();
        return Redirect()->back()->with('success', 'Xóa nhân viên thành công');
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
