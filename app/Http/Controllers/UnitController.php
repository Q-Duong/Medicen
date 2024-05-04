<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\Redirect;

class UnitController extends Controller
{
    public function add(){
    	return view('admin.Unit.add_unit');
    }
    
    public function list(){
       $getAllUnit = Unit::orderBy('unit_id','ASC')->get();
    	return view('admin.Unit.list_unit')->with(compact('getAllUnit'));
    }
    public function save(Request $request){
    	$data = $request->all();
        $unit = new Unit();
    	$unit->unit_code = $data['unit_code'];
        $unit->unit_name = $data['unit_name'];
        $unit->save();

    	return Redirect()->back()->with('success','Thêm danh đơn vị thành công');
    }
    
    public function edit($unit_id){
        $unit = Unit::find($unit_id);
        return view('admin.Unit.edit_unit')->with(compact('unit'));
    }
    public function update(Request $request,$unit_id){
        $data = $request->all();
        $unit = Unit::find($unit_id);
    	$unit->unit_code = $data['unit_code'];
        $unit->unit_name = $data['unit_name'];
        $unit->save();

    	return Redirect::to('admin/unit/list')->with('success','Cập nhật đơn vị thành công');
    }
    public function delete($unit_id){
        $unit = Unit::find($unit_id);
        $unit->delete();
        return Redirect()->back()->with('success','Xóa đơn vị thành công');
    }
}
