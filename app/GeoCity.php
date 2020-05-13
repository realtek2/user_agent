<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GeoCity
 *
 * @property int $id
 * @property string $city
 * @property string $region
 * @property string $district
 * @property float $lat
 * @property float $lng
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GeoCity whereRegion($value)
 * @mixin \Eloquent
 */
class GeoCity extends Model
{
    protected $table = 'geo_cities';
    public    $timestamps = false;
}
