<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Log;

use App\Client;
use App\UserClient;
use App\Site;
use App\Action;

class ClientsController extends Controller
{
    public function get( $siteId, $clientId ){
        $user_client = UserClient::where( [
            'local_client_id' => $clientId,
            'user_id'         => Auth::user()->id
        ] )->first();
        if( !$user_client ){
            return response(null, 404);
        }
        $client_id = $user_client->client_id;
        $sites     = Auth::user()->sites()->get();
        $sites->transform(function( $item ) use ( $client_id ){
            return[
                'url' => $item->url,
                'actions_count' => Action::where([
                    'client_id' => $client_id,
                    'site_id'   => $item->id
                ])->count()
            ];
        });
        $actions = Action::where([
            'client_id' => $client_id,
            'site_id'   => $siteId
        ])->orderBy('created_at', 'desc')->limit(1000)->get();
        $actions->transform(function($item){
            return [
                'id'         => $item->id,
                'action'     => $item->action,
                'data'       => $item->data,
                'referer'    => $item->referer,
                'created_at' => $item->created_at->format('d.m.Y H:i')
            ];
        });

        return response()->json([
            'sites'   => $sites,
            'actions' => $actions
        ]);
    }
}
