<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QrController extends Controller
{
    public function index()
    {
        return view('pages.qr.index');
    }
}