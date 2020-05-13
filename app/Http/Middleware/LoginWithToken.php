<?php

namespace App\Http\Middleware;

use Closure;

use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Browser;

class LoginWithToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if( $request->has('token') ){
            // получаем массив параметров без токена
            $inputs = $request->all();
            unset( $inputs['token'] );
            // получаем строку параметров, без токена
            $url_parameters = http_build_query($inputs);
            if( !Browser::isBot() ){
                // получаем айди пользователя и токен
                $token    = base64_decode($request->only('token')['token']);
                $foo      = explode( '_', $token );
                //$cache_id = 'atoken:'.$foo[0];
                if( count($foo) === 2 ){
                    $token    = $foo[1];

                    $user = User::find( $foo[0] );
                    if( $user ){
                        $token_length = 32;// стоит в миграциях
                        if( is_string( $user->token ) && mb_strlen( $user->token, 'UTF-8' ) === $token_length && $user->token === $token && $user->token_expires && $user->token_expires->gt( Carbon::now() ) ){
                            Auth::login($user, true);
                            $user->token = NULL;
                            $user->save();
                        }
                    }
                }
            }
            // редиректим на эту же страничку, но без токена
            return redirect( $request->url()."?$url_parameters" );
        }
        return $next($request);
    }
}
