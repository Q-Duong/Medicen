<?php

namespace App\Http\Controllers;

use App\Http\Requests\AboutRequestForm;
use App\Models\About;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Redirect;

class AboutController extends Controller
{
    public function about()
    {
        return view('pages.about.about_details');
    }
    public function aboutWhy()
    {
        return view('pages.about.about_details_why');
    }
    public function aboutInfrastructure()
    {
        return view('pages.about.about_details_infrastructure');
    }

    public function information()
    {
        $contact = Contact::where('info_id', 1)->get();
        return view('admin.Information.add_information')->with(compact('contact'));
    }
    public function update_info(Request $request, $info_id)
    {
        $data = $request->all();
        $contact = Contact::find($info_id);
        $contact->info_contact = $data['info_contact'];
        $contact->info_map = $data['info_map'];

        $get_image = $request->file('info_image');
        $path = 'public/uploads/contact/';
        if ($get_image) {
            unlink($path . $contact->info_logo);
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move($path, $new_image);
            $contact->info_logo = $new_image;
        }

        $contact->save();
        return redirect()->back()->with('success', 'Cập nhật thông tin Apple Store thành công');
    }
    public function save_info(Request $request)
    {
        $data = $request->all();
        $contact = new Contact();
        $contact->info_contact = $data['info_contact'];
        $contact->info_map = $data['info_map'];

        $get_image = $request->file('info_image');
        $path = 'public/uploads/contact/';
        if ($get_image) {
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move($path, $new_image);
            $contact->info_logo = $new_image;
        }

        $contact->save();
        return redirect()->back()->with('success', 'Cập nhật thông tin website thành công');
    }

    //Back End
    public function create()
    {
        return view('pages.admin.about.create');
    }

    public function index()
    {
        $getAllAbout = About::orderBy('id', 'DESC')->paginate(10);
        return view('pages.admin.about.index')->with(compact('getAllAbout'));
    }

    public function store(AboutRequestForm $request)
    {
        $data = $request->all();
        $about = new About();
        $about->about_title = $data['about_title'];
        $about->about_slug = $data['about_slug'];
        $about->about_content = $data['about_content'];
        $get_image = $request->file('about_image');
        $name = $about->about_title;
        $check = About::where('about_title', $name)->exists();
        if ($check) {
            return Redirect()->back()->with('error', 'Dịch vụ đã tồn tại, Vui lòng kiểm tra lại.')->withInput();
        }

        if ($get_image) {
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/about/'), $new_image);
            $about->about_image = $new_image;
            $about->save();
            return Redirect::route('about.index')->with('success', 'Thêm dịch vụ thành công');
        } else {
            return Redirect()->back()->with('error', 'Vui lòng thêm hình ảnh');
        }
    }

    public function edit($id)
    {
        $about = About::find($id);
        return view('pages.admin.about.edit', compact('about'));
    }

    public function update(AboutRequestForm $request, $id)
    {
        $data = $request->all();
        $about = About::find($id);
        $about->about_title = $data['about_title'];
        $about->about_slug = $data['about_slug'];
        $about->about_content = $data['about_content'];
        $about_image = $about->about_image;
        $get_image = $request->file('about_image');

        if ($get_image) {
            $about_image_old = $about->about_image;
            $path = public_path('uploads/about/');
            unlink($path . $about_image);

            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move($path, $new_image);
            $about->about_image = $new_image;
        }
        $about->save();
        return Redirect::route('about.index')->with('success', 'Cập nhật dịch vụ thành công');
    }

    public function destroy($id)
    {
        $about = About::find($id);
        $about_image = $about->about_image;
        if ($about_image) {
            unlink(public_path('uploads/about/') . $about_image);
        }
        $about->delete();
        return Redirect()->back()->with('success', 'Xóa dịch vụ thành công');
    }

    //Client
    public function show($about_slug)
    {
        $about = About::where('about_slug', $about_slug)->first();
        return view('pages.client.about.index', compact('about'));
    }
}
