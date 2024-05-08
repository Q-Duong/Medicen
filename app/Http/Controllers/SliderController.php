<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;
use Session;
use Illuminate\Support\Facades\Redirect;
use DB;

class SliderController extends Controller
{
    public function index()
    {
        $getAllSlider = Slider::orderBy('id', 'DESC')->paginate(10);
        return view('pages.admin.slider.index')->with(compact('getAllSlider'));
    }

    public function create()
    {
        return view('pages.admin.slider.create');
    }

    public function unactive_slider($id)
    {
        DB::table('slider')->where('id', $id)->update(['slider_status' => 1]);
        return Redirect::to('admin/slider/list')->with('success', 'Kích hoạt slider thành công');
    }

    public function active_slider($id)
    {
        DB::table('slider')->where('id', $id)->update(['slider_status' => 0]);
        return Redirect::to('admin/slider/list')->with('success', 'Ẩn slider thành công');
    }

    public function insert(Request $request)
    {
        $this->checkValidateSlider($request);
        $data = $request->all();
        $slider = new Slider();
        $slider->slider_name = $data['slider_name'];
        $slider->slider_status = $data['slider_status'];
        $slider->slider_desc = $data['slider_desc'];
        $get_image = $request->file('slider_image');
        dd($request);
        if ($get_image) {
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/slider/'), $new_image);
            $slider->slider_image = $new_image;

            $slider->save();
        }
        Session::put('success', 'Thêm slider thành công');
        return Redirect::route('slider.create')->with('message', 'Thêm slider thành công');
    }

    public function edit($id)
    {

        $slider = Slider::find($id);

        return view('pages.admin.slider.edit', compact('slider'));
    }

    public function update(Request $request, $id)
    {

        $this->checkValidateSlider($request);
        $data = $request->all();
        $slider = Slider::find($id);
        $slider->slider_name = $data['slider_name'];
        $slider->slider_status = $data['slider_status'];
        $slider->slider_desc = $data['slider_desc'];
        $slider_image = $slider->slider_image;
        $get_image = $request->file('slider_image');

        if ($get_image) {
            $slider_image_old = $slider->slider_image;
            $path = public_path('uploads/slider/');
            unlink($path . $slider_image);

            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move($path, $new_image);
            $slider->slider_image = $new_image;
        }

        $slider->save();
        return Redirect::route('slider.index')->with('success', 'Cập nhật slider thành công');
    }

    public function destroy($id)
    {

        $slider = Slider::find($id);
        $slider_image = $slider->slider_image;
        if ($slider_image) {
            unlink(public_path('uploads/slider/') . $slider_image);
        }
        $slider->delete();

        return Redirect()->back()->with('success', 'Xóa slider thành công');
    }
    public function checkValidateSlider(Request $request)
    {
        $this->validate(
            $request,
            [
                'slider_name' => 'required',
                'slider_status' => 'required',
                'slider_desc' => 'required',
            ],
            [
                'slider_name.required' => 'Vui lòng điền thông tin',
                'slider_status.required' => 'Vui lòng điền thông tin',
                'slider_desc.required' => 'Vui lòng điền thông tin',
            ]
        );
    }
}
