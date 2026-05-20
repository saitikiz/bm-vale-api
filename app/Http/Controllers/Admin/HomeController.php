<?php

namespace App\Http\Controllers\Admin;
use DOMDocument;
use DOMXPath;

class HomeController
{
    public function index(){
        return view('home');
    }
}
