<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestForm;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Redirect;

class ServiceController extends Controller
{
    public function index()
    {
        return view('pages.admin.service.index');
    }

    public function create()
    {
        return view('pages.admin.service.create');
    }

    public function store(ServiceRequestForm $request)
    {
        $data = $request->all();
        $service = new Service();
        $service->service_title = $data['service_title'];
        $service->service_slug = $data['service_slug'];
        $service->service_content = $data['service_content'];
        $get_image = $request->file('service_image');
        $name = $service->service_title;
        $check = Service::where('service_title', $name)->exists();
        if ($check) {
            return Redirect()->back()->with('error', 'Dịch vụ đã tồn tại, Vui lòng kiểm tra lại.')->withInput();
        }

        if ($get_image) {
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/service/'), $new_image);
            $service->service_image = $new_image;
            $service->save();
            return Redirect::route('service.index')->with('success', 'Thêm dịch vụ thành công');
        } else {
            return Redirect()->back()->with('error', 'Vui lòng thêm hình ảnh');
        }
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        return view('pages.admin.service.edit', compact('service'));
    }

    public function update(ServiceRequestForm $request, $id)
    {
        $data = $request->all();
        $service = Service::findOrFail($id);
        $service->service_title = $data['service_title'];
        $service->service_slug = $data['service_slug'];
        $service->service_content = $data['service_content'];
        $service_image = $service->service_image;
        $get_image = $request->file('service_image');

        if ($get_image) {
            $service_image_old = $service->service_image;
            $path = public_path('uploads/service/');
            unlink($path . $service_image);

            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move($path, $new_image);
            $service->service_image = $new_image;
        }
        $service->save();
        return Redirect::route('service.index')->with('success', 'Cập nhật dịch vụ thành công');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service_image = $service->service_image;
        if ($service_image) {
            unlink(public_path('uploads/service/') . $service_image);
        }
        $service->delete();
        return Redirect()->back()->with('success', 'Xóa dịch vụ thành công');
    }

    //Client
    public function show($service_slug)
    {
        $service = Service::where('service_slug', $service_slug)->first();
        return view('pages.client.service.index', compact('service'));
    }
}
