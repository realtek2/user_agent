<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\GeoCity;
use App\GeoBase;

class TestController extends Controller
{
    public function main( Request $request ){
        //$telegram->setWebhook(['url' => 'https://quiet-baboon-85.localtunnel.me/telegram/webhook']);
        return view('test_page');
        return;
        set_time_limit(0); // указываем, чтобы скрипт не ограничивался временем по умолчанию
        ignore_user_abort(1);
        /*
        $file = file('cities.txt');
        $pattern = '#(\d+)\s+(.*?)\t+(.*?)\t+(.*?)\t+(.*?)\s+(.*)#';
        foreach ($file as $row)
        {
            $row = iconv('windows-1251', 'utf-8', $row);
            if(preg_match($pattern, $row, $out ))
            {
                $city = new GeoCity();
                //Log::info($out);
                $city->id = $out[1];
                $city->city = $out[2];
                $city->region = $out[3];
                $city->district = $out[4];
                $city->lat = $out[5];
                $city->lng = $out[6];
                $city->save();
            }        
        }   */


        $file = file('cidr_optim.txt');
        $pattern = '#(\d+)\s+(\d+)\s+(\d+\.\d+\.\d+\.\d+)\s+-\s+(\d+\.\d+\.\d+\.\d+)\s+(\w+)\s+(\d+|-)#';
        foreach ($file as $row)
        {
            $row = iconv('windows-1251', 'utf-8', $row);
            if(preg_match($pattern, $row, $out ))
            {
                $base = new GeoBase();
                //Log::info($out);
                $base->long_ip1 = $out[1];
                $base->long_ip2 = $out[2];
                $base->ip1      = $out[3];
                $base->ip2      = $out[4];
                $base->country  = $out[5];
                $base->city_id  = $out[6];
                $base->save();
            }        
        }

        return 123123123;
    }
}
