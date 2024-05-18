<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;

class ContactController extends Controller
{
    public function edit()
    {
        $contact = Contact::find(1);
        return view('pages.admin.contact.edit')->with(compact('contact'));
    }
    
    public function update(Request $request)
    {
        $data = $request->all();
        $contact = Contact::find(1);
        $contact->contact = $data['contact'];
        $contact->contact_map = $data['contact_map'];
        $contact->save();
        return redirect()->back()->with('success', 'Cập nhật thông tin thành công');
    }

    //Client
    public function show()
    {
        $contact = Contact::find(1);
        return view('pages.client.contact.index', compact('contact'));
    }
}
