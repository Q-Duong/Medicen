<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;
use Session;
use Illuminate\Support\Facades\Redirect;
use DB;

class SliderController extends Controller
{
    public function list_slider()
    {
        $all_slide = Slider::orderBy('slider_id', 'DESC')->get();
        return view('admin.Slider.list_slider')->with(compact('all_slide'));
    }

    public function add_slider()
    {
        return view('admin.Slider.add_slider');
    }

    public function unactive_slider($slider_id)
    {
        DB::table('slider')->where('slider_id', $slider_id)->update(['slider_status' => 1]);
        return Redirect::to('admin/slider/list')->with('success', 'Kích hoạt slider thành công');
    }

    public function active_slider($slider_id)
    {
        DB::table('slider')->where('slider_id', $slider_id)->update(['slider_status' => 0]);
        return Redirect::to('admin/slider/list')->with('success', 'Ẩn slider thành công');
    }

    public function insert_slider(Request $request)
    {
        $data = $request->all();
        $get_image = request('slider_image');

        if ($get_image) {
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/slider/'), $new_image);

            $slider = new Slider();
            $slider->slider_name = $data['slider_name'];
            $slider->slider_image = $new_image;
            $slider->slider_status = $data['slider_status'];
            $slider->slider_desc = $data['slider_desc'];
            $slider->save();
            Session::put('success', 'Thêm slider thành công');
            return Redirect::to('admin/slider/add')->with('message', 'Thêm slider thành công');
        }
    }

    public function edit_slider($slider_id)
    {

        $slider = Slider::find($slider_id);

        return view('admin.Slider.edit_slider')->with(compact('slider'));
    }

    public function update_slider(Request $request, $slider_id)
    {


        $data = $request->all();
        $slider = Slider::find($slider_id);
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
        return Redirect::to('/admin/slider/list')->with('success', 'Cập nhật slider thành công');
    }

    public function delete_slider(Request $request, $slider_id)
    {

        $slider = Slider::find($slider_id);
        $slider_image = $slider->slider_image;
        if ($slider_image) {
            unlink(public_path('uploads/slider/') . $slider_image);
        }
        $slider->delete();

        return Redirect()->back()->with('success', 'Xóa slider thành công');
    }
}
