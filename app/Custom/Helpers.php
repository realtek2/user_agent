<?php

namespace App\Custom;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Helpers{
    public static function genToken( $user ){
        $token_length = 32;// стоит в миграциях
        $expires_minutes = 60*24;
        // Пытаемся получить старый токен
        if( is_string( $user->token ) && mb_strlen( $user->token, 'UTF-8' ) === $token_length &&
        $user->token_expires && $user->token_expires->gt( Carbon::now() ) ){
            $user->token_expires = Carbon::now()->addMinutes( $expires_minutes );
        }else{
            $token = Str::random($token_length);
            $user->token = $token;
            $user->token_expires = Carbon::now()->addMinutes( $expires_minutes );
        }
        $user->save();
        $base64_token = base64_encode( $user->id.'_'.$user->token );
        return $base64_token;
    }
}