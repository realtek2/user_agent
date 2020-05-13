<?php

namespace App;

use App\Client;
use App\Site;
use Illuminate\Database\Eloquent\Model;

/**
 * App\UserClient
 *
 * @property int $id
 * @property int $user_id
 * @property int $client_id
 * @property int $local_client_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient whereLocalClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserClient whereUserId($value)
 * @mixin \Eloquent
 */
class UserClient extends Model
{
    protected $table = 'user_clients';
    public    $timestamps = false;

    public function actions( $sites = false )
    {
        return $this->hasMany(Action::class, 'client_id', 'client_id');
    }
}
