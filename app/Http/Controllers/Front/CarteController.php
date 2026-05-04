<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class CarteController extends Controller
{
    public function index()
    {
        return view('front.carte.index');
    }
}