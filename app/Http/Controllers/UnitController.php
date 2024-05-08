<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\Redirect;

class UnitController extends Controller
{
    public function create()
    {
        return view('pages.admin.unit.create');
    }

    public function index()
    {
        $getAllUnit = Unit::orderBy('id', 'ASC')->paginate(10);
        return view('pages.admin.unit.index', compact('getAllUnit'));
    }
    public function store(Request $request)
    {
        $this->checkUnit($request);
        $data = $request->all();
        $unit = new Unit();
        $unit->unit_code = $data['unit_code'];
        $unit->unit_name = $data['unit_name'];
        $unit->save();

        return Redirect()->back()->with('success', 'Thêm danh đơn vị thành công');
    }

    public function edit($id)
    {
        $unit = Unit::find($id);
        return view('pages.admin.unit.edit',compact('unit'));
    }
    public function update(Request $request, $id)
    {
        $this->checkUnit($request);
        $data = $request->all();
        $unit = Unit::find($id);
        $unit->unit_code = $data['unit_code'];
        $unit->unit_name = $data['unit_name'];
        $unit->save();

        return Redirect::route('unit.index')->with('success', 'Cập nhật đơn vị thành công');
    }
    public function destroy($id)
    {
        $unit = Unit::find($id);
        $unit->delete();
        return Redirect()->back()->with('success', 'Xóa đơn vị thành công');
    }
    public function checkUnit(Request $request)
    {
        $this->validate(
            $request,
            [
                'unit_code' => 'required',
                'unit_name' => 'required',
            ],
            [
                'unit_code.required' => 'Vui lòng điền mã đơn vị',
                'unit_name.required' => 'Vui lòng điền tên đơn vị',
            ]
        );
    }
}
