<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function add()
    {
        return view('dashboard.app');
    }

    public function dashboard()
    {
        return view('dashboard.app');
    }

    public function dashboardClient()
    {
        return view('dashboard.app');
    }

    public function home()
    {
        return view('dashboard.app');
    }
}
