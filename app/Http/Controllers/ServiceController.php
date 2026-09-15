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
        return view('pages.client.service.index');
    }
}
