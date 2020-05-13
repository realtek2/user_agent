<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Site;
use Illuminate\Support\Facades\Auth;
use App\Action;
use Log;

class ActionsController extends Controller
{
    public function get( $id ){
        $site = Site::findOrFail($id);
        if( $site->user_id !== Auth::user()->id ){
            return response(null, 404);
        }
        $actions = Action::select('actions.*')->withLocalClientId()->where('site_id', $site->id)->orderBy('created_at', 'desc')->limit(1000)->get();
        $actions->transform(function($item){
            return [
                'id'              => $item->id,
                'action'          => $item->action,
                'data'            => $item->data,
                'local_client_id' => $item->local_client_id,
                'referer'         => $item->referer,
                'created_at'      => $item->created_at->format('d.m.Y H:i')
            ];
        });
        return response()->json( $actions );
    }
}
