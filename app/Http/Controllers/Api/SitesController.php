<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Log;
use TrueBV\Punycode;
use App\Site;
use Illuminate\Support\Str;

class SitesController extends Controller
{
    public function get(){
        $sites = Auth::user()->sites()->get();
        return response()->json( $sites );
    }

    public function getById( $id ){
        $site = Site::findOrFail($id);
        if( $site->user_id !== Auth::user()->id ){
            return response(null, 404);
        }
        return response()->json( $site );
    }

    public function create( Request $request ){
        $inputs = $request->all();
        if( isset($inputs['site']) ){
            $parsed_url = parse_url( $inputs['site'] );
            if( !isset($parsed_url['scheme']) ){
                $parsed_url['scheme'] = 'http';
                $parsed_url = parse_url( \App\Services\Helpers::unparse_url($parsed_url) );
            }
            if( isset($parsed_url['host']) ){
                $punycode = new Punycode();
                $parsed_url['host'] = $punycode->encode( $parsed_url['host'] );
                $inputs['site'] = \App\Services\Helpers::unparse_url( $parsed_url );
            }
        }
        $validator = \Validator::make( $inputs, [
            'site' => 'required|string|site'
        ]);
        if( $validator->fails() ){
            return response()->json([
                'message' => 'validation_failed',
                'errors'  => $validator->errors()
            ], 422);
        }
        $parsed_url = parse_url( $inputs['site'] );
        $site_url   = $parsed_url['host'].(isset($parsed_url['path']) ? $parsed_url['path'] : '');

        $site = new Site();
        $site->user_id = Auth::user()->id;
        $site->url     = $site_url;
        $site->code    = Str::random(12);
        $site->save();
    }

    public function destroy( $id ){
        $site = Site::findOrFail($id);
        if( $site->user_id !== Auth::user()->id ){
            return response(null, 403);
        }
        $site->deleted = true;
        $site->save();
    }
}
