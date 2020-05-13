<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class MainController extends Controller
{
    //
    public function main()
    {
        if( Auth::check() ){
            return redirect( route('home') );
        }
        $referralPath = '';
        if (Cookie::has('ref'))
            $referralPath = "&start=" . Cookie::get('ref');

        return view('site.main', compact('referralPath'));
    }
}
