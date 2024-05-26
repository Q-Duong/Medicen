<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequestForm;
use App\Models\Unit;
use Illuminate\Support\Facades\Redirect;

class UnitController extends Controller
{
    public function index()
    {
        $getAllUnit = Unit::orderBy('id', 'ASC')->paginate(10);
        return view('pages.admin.unit.index', compact('getAllUnit'));
    }

    public function create()
    {
        return view('pages.admin.unit.create');
    }

    public function store(UnitRequestForm $request)
    {
        $data = $request->all();
        $unit = new Unit();
        $unit->unit_code = $data['unit_code'];
        $unit->unit_name = $data['unit_name'];
        $unit->save();

        return Redirect::route('unit.index')->with('success', 'Thêm danh đơn vị thành công');
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        return view('pages.admin.unit.edit', compact('unit'));
    }

    public function update(UnitRequestForm $request, $id)
    {
        $data = $request->all();
        $unit = Unit::findOrFail($id);
        $unit->unit_code = $data['unit_code'];
        $unit->unit_name = $data['unit_name'];
        $unit->save();

        return Redirect::route('unit.index')->with('success', 'Cập nhật đơn vị thành công');
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
        return Redirect()->back()->with('success', 'Xóa đơn vị thành công');
    }
}
