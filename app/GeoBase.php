<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GeoBase
 *
 * @property int $id
 * @property int $long_ip1
 * @property int $long_ip2
 * @property string $ip1
 * @property string $ip2
 * @property string $country
 * @property string $city_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereIp1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereIp2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereLongIp1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoBase whereLongIp2($value)
 * @mixin \Eloquent
 */
class GeoBase extends Model
{
    protected $table = 'geo_base';
    public    $timestamps = false;
}
