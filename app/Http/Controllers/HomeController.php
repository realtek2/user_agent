<?php

namespace App\Http\Controllers;

use App\Client;
use App\UserClient;
use App\Site;
use App\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Validator;
use TrueBV\Punycode;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $status = [];
        $sites = Auth::user()->sites;
        foreach($sites as $site){
            $client = new \GuzzleHttp\Client(['verify' => false]);
            try {
                $punycode   = new Punycode();
                $parsed_url = parse_url( $site->url );
                $response = $client->get( $parsed_url['scheme']."://".$punycode->encode( $parsed_url['host'] ) );
                $code = $response->getStatusCode();
                if ($code != 200) {
                    $status[$site->id] = 'Ошибка';
                } else {
                    $body = $response->getBody();
                    $remainingBytes = $body->getContents();
                    if (strpos($remainingBytes, $site->code) !== false) {
                        $status[$site->id] = 'OK';
                    } else {
                        $status[$site->id] = 'Код не найден';
                    }
                }
            } catch (RequestException $e){
                $status[$site->id] = 'Ошибка';
            }

        }
        return view('site.sites',['status'=>$status]);
    }

    public function addSite()
    {
        return view('addsite');
    }

    public function saveSite(Request $request)
    {
        // unique + lower
        $punycode = new Punycode();
        if( $request->has('url') ){
            $url = $request->only('url')['url'];
            $validator = Validator::make(['url' => $url],[
                'url' => 'required|string|url'
            ]);
            if( !$validator->fails() ){
                $parsed_url = parse_url($url);
                if( isset($parsed_url['scheme']) && isset($parsed_url['host']) ){
                    $parsed_url['host'] = $punycode->decode( $parsed_url['host'] );
                    $url  = $parsed_url['scheme']."://".$parsed_url['host'];
					if( isset($parsed_url['path']) ){
						$url .= $parsed_url['path'];
					}
                    $site = new Site();
                    $site->user_id = Auth::user()->id;
                    $site->url     = $url;
                    $site->code    = Str::random(12);
                    $site->save();
                }
            }
        }
        return redirect()->back();
    }

    public function deleteSite($id)
    {
        $site = Site::findOrFail($id);
        if( $site->user_id === Auth::user()->id ){
            $site->delete();
        }
        return back();
    }

    public function showActions($id)
    {
        $site = Site::findOrFail($id);
        if($site->user_id != Auth::user()->id){
            return redirect('/home');
        }
        $actions = Action::select('actions.*')->withLocalClientId()->where( 'site_id', $id )->orderBy('created_at', 'desc')->get();
        return view('site.actions', ['actions' => $actions, 'site'=>$site]);
    }

    public function client($id)
    {
        $client      = Client::findOrFail($id);
        $user_client = UserClient::where( [
            'local_client_id' => $id,
            'user_id'         => Auth::user()->id
        ] )->first();


        if( !$user_client ){
            abort(404);
        }
        $sites = Auth::user()->sites;
        $ids   = [];
        $count = [];
        foreach($sites as $site){
            $ids[] = $site->id;
            $count[$site->id] =0;
        }
        
        $actions = $user_client->actions()->whereIn( 'site_id', $ids )->orderBy('created_at', 'desc')->get();
        foreach($actions as $action){
            $count[$action->site_id]++;
        }

        return view('site.client', ['client'=> $client, 'actions' => $actions, 'counts'=>$count,'sites'=>$sites]);
    }
}
