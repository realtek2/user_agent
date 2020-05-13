<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LwtController extends Controller
{
    //
    public function lwt()
    {
        return redirect( route('home') );
    }
}
