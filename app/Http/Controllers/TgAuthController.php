<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TelegramUser;
use App\User;
use Illuminate\Support\Facades\Auth;

class TgAuthController extends Controller
{


    public function login(Request $request)
    {
        $code = $request->code;
        $user = TelegramUser::where('code',$code)->first();
        if($user){
            $luser = User::where('name', $user->chat_id)->first();
            Auth::login($luser, true);
            return redirect('/home');
        } else {
            return back();
        }
    }
}
