<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class DemoController extends Controller
{
    public function index()
    {
        return view('front.demo.index');
    }
}