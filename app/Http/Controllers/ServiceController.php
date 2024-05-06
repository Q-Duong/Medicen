<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Redirect;

class ServiceController extends Controller
{
    public function add_service(){
    	return view('admin.Service.add_service');
    }

    public function list_service(){
        $getAllService = Service::orderBy('id','DESC')->get();
    	return view('admin.Service.list_service')->with(compact('getAllService'));
    }

    public function save_service(Request $request){
        // $this->checkPostAdd($request);

    	$data = $request->all();
        $service = new Service();
    	$service->service_title = $data['service_title'];
        $service->service_slug = $data['service_slug'];
        $service->service_content = $data['service_content'];
        $get_image = $request->file('service_image');
        $name = $service->service_title;

        $check = Service::where('service_title',$name)->exists();
        if($check)
        {
            return Redirect()->back()->with('error','Dịch vụ đã tồn tại, Vui lòng kiểm tra lại.')->withInput();
        }
      
        if($get_image){
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.',$get_name_image));
            $new_image =  $name_image.rand(0,99).'.'.$get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/service/'), $new_image);
            $service->service_image=$new_image;
            $service->save();
            return Redirect()->back()->with('success','Thêm dịch vụ thành công');
        }else{
            return Redirect()->back()->with('error','Vui lòng thêm hình ảnh');
        }

    }
    
    public function edit_service($id){
        $service = Service::find($id);
        return view('admin.Service.edit_service')->with(compact('service'));
    }

    public function update_service(Request $request,$id){
        //$this->checkPostUpdate($request);
        
        $data = $request->all();
        $service = Service::find($id);
    	$service->service_title = $data['service_title'];
        $service->service_slug = $data['service_slug'];
        $service->service_content = $data['service_content'];
        $service_image = $service->service_image;
        $get_image = $request->file('service_image');
      
        if($get_image){
            $service_image_old = $service->service_image;
            $path = public_path('uploads/service/');
            unlink($path.$service_image);

            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.',$get_name_image));
            $new_image =  $name_image.rand(0,99).'.'.$get_image->getClientOriginalExtension();
            $get_image->move($path,$new_image);
            $service->service_image=$new_image;
            
        }
        $service->save();
        return Redirect::to('admin/service/list')->with('success','Cập nhật dịch vụ thành công');
    }

    public function delete_service($id){
        $service = Service::find($id);
        $service_image = $service->service_image;
        if($service_image){
            unlink(public_path('uploads/service/').$service_image);
        }
        $service->delete();
        return Redirect()->back()->with('success','Xóa dịch vụ thành công');
    }

    //Front End

    public function show_service(Request $request,$service_slug){
        $service = Service::where('service_slug',$service_slug)->take(1)->get();
        foreach($service as $key =>$ser){
            $title = $ser->service_title;
            $created_at = $ser->created_at;
        }
        return view('pages.service.service_x')->with(compact('service','title','created_at'));
    }

    //Validation
    public function checkPostUpdate(Request $request){
        $this-> validate($request,
        [
            'post_title' => 'required',
            'post_slug' => 'required',
            //'post_desc' => 'required',
            'post_content' => 'required',
        ],
        [
            'post_title.required' =>'Vui lòng điền thông tin',
            'post_slug.required' =>'Vui lòng điền thông tin',
            'post_desc.required' =>'Vui lòng điền thông tin',
            'post_content.required' =>'Vui lòng điền thông tin',
        ]);
    }

    public function checkPostAdd(Request $request){
        $this-> validate($request,
        [
            'post_title' => 'required',
            'post_slug' => 'required',
            'post_image' => 'required',
            // 'post_desc' => 'required',
            'post_content' => 'required',
        ],
        [
            'post_title.required' =>'Vui lòng điền thông tin',
            'post_slug.required' =>'Vui lòng điền thông tin',
            'post_image.required' =>'Vui lòng theem hình ảnh',
            'post_desc.required' =>'Vui lòng điền thông tin',
            'post_content.required' =>'Vui lòng điền thông tin',
        ]);
    }
}
