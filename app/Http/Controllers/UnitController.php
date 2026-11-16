<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequestForm;
use App\Models\Order;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        // Tìm theo mã / tên viết tắt / tên đầy đủ / mã số thuế; kèm số đơn của từng đơn vị
        $query = Unit::query()
            ->leftJoinSub(DB::table('orders')->select('unit_id', DB::raw('COUNT(*) as orders_count'), DB::raw('MAX(created_at) as last_order'))->groupBy('unit_id'), 'oc', 'oc.unit_id', '=', 'units.id')
            ->select('units.*', DB::raw('COALESCE(oc.orders_count, 0) as orders_count'), 'oc.last_order');
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(fn ($w) => $w->where('unit_code', 'LIKE', "%{$q}%")->orWhere('unit_abbreviation', 'LIKE', "%{$q}%")
                ->orWhere('unit_name', 'LIKE', "%{$q}%")->orWhere('unit_tax_code', 'LIKE', "%{$q}%")->orWhere('unit_address', 'LIKE', "%{$q}%"));
        }
        if ($request->input('has_orders') === '1') {
            $query->whereRaw('COALESCE(oc.orders_count, 0) > 0');
        } elseif ($request->input('has_orders') === '0') {
            $query->whereRaw('COALESCE(oc.orders_count, 0) = 0');
        }
        $sort = $request->input('sort') === 'orders' ? 'orders' : 'name';
        $sort === 'orders' ? $query->orderByDesc('orders_count') : $query->orderBy('unit_abbreviation');
        $getAllUnit = $query->orderBy('units.id')->paginate(15)->withQueryString();

        $counts = [
            'all' => Unit::count(),
            'used' => Unit::whereIn('id', Order::select('unit_id'))->count(),
        ];
        $counts['unused'] = $counts['all'] - $counts['used'];

        return view('pages.admin.unit.index', compact('getAllUnit', 'counts'));
    }

    public function create()
    {
        return view('pages.admin.unit.create');
    }

    public function store(UnitRequestForm $request)
    {
        if (Unit::where('unit_code', trim($request->unit_code))->exists()) {
            return back()->withInput()->withErrors(['unit_code' => 'Mã đơn vị này đã có, vui lòng chọn mã khác.']);
        }
        $unit = new Unit();
        $this->fill($unit, $request);
        $unit->save();

        return Redirect::route('unit.index')->with('success', 'Đã thêm đơn vị ' . $unit->unit_abbreviation . '.');
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        $ordersCount = Order::where('unit_id', $unit->id)->count();
        return view('pages.admin.unit.edit', compact('unit', 'ordersCount'));
    }

    public function update(UnitRequestForm $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $code = trim($request->unit_code);
        // Chỉ kiểm tra trùng khi đổi mã (dữ liệu cũ có vài mã trùng sẵn, vẫn sửa được các thông tin khác)
        if ($code !== $unit->unit_code && Unit::where('unit_code', $code)->where('id', '!=', $unit->id)->exists()) {
            return back()->withInput()->withErrors(['unit_code' => 'Mã đơn vị này đã có, vui lòng chọn mã khác.']);
        }
        $this->fill($unit, $request);
        $unit->save();

        return Redirect::route('unit.index')->with('success', 'Đã cập nhật đơn vị ' . $unit->unit_abbreviation . '.');
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        // Đơn vị đã có đơn: không xóa (các đơn cũ sẽ mất đơn vị)
        $orders = Order::where('unit_id', $unit->id)->count();
        if ($orders > 0) {
            return Redirect::route('unit.index')->with('error', 'Đơn vị ' . $unit->unit_abbreviation . ' đang có ' . $orders . ' đơn hàng, không xóa được.');
        }
        $name = $unit->unit_abbreviation;
        $unit->delete();

        return Redirect::route('unit.index')->with('success', 'Đã xóa đơn vị ' . $name . '.');
    }

    private function fill(Unit $unit, Request $request): void
    {
        $unit->unit_code = trim($request->unit_code);
        $unit->unit_abbreviation = trim((string) $request->unit_abbreviation);
        $unit->unit_name = trim((string) $request->unit_name);
        $unit->unit_tax_code = $request->filled('unit_tax_code') ? trim($request->unit_tax_code) : null;
        $unit->unit_address = $request->filled('unit_address') ? trim($request->unit_address) : null;
    }
}
