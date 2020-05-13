<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Client;
use Illuminate\Support\Facades\Auth;

/**
 * App\Action
 *
 * @property int $id
 * @property int|null $site_id
 * @property string|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $action
 * @property string|null $ip
 * @property string|null $region
 * @property string|null $referer
 * @property string|null $utm
 * @property int $client_id
 * @property string $url
 * @property string $browser
 * @property string $browser_v
 * @property string $platform
 * @property string $platform_v
 * @property string $country
 * @property string $city
 * @property-read \App\Client $client
 * @property-read \App\Site|null $site
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereBrowserV($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action wherePlatformV($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereReferer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action whereUtm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Action withLocalClientId()
 * @mixin \Eloquent
 */
class Action extends Model
{
    protected $fillable = ['site_id', 'client_id', 'data','action', 'ip','region', 'referer', 'utm'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id','id');
    }

    public function scopeWithLocalClientId( $query ){
        return $query->leftJoin( 'user_clients', function( $query ){
            $query->on( 'actions.client_id', 'user_clients.client_id' )
                ->where( 'user_clients.user_id', Auth::user()->id );
        })
        ->addSelect( 'user_clients.local_client_id as local_client_id' );
    }
    
}
