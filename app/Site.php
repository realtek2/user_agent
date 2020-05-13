<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use TrueBV\Punycode;
use Log;

/**
 * App\Site
 *
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $code
 * @property boolean $visits
 * @property boolean $start_of_input
 * @property boolean $form_submission
 * @property boolean $clicks_on_phone
 * @property boolean $clicks_on_whatsapp
 * @property boolean $whatsapp_id
 * @property boolean $deleted
 * @property int $wb_widget_phone
 * @property string $wb_widget_text
 * @property boolean $wb_widget_state
 * @property boolean $wb_widget_desktop_state
 * @property boolean $wb_widget_mobile_state
 * @property boolean $wb_widget_show_side
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Action[] $actions
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Site whereUserId($value)
 * @mixin \Eloquent
 */
class Site extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'code',
        'visits',
        'start_of_input',
        'form_submission',
        'clicks_on_phone',
        'clicks_on_whatsapp',
        'whatsapp_id',
        'deleted',
        'wb_widget_phone',
        'wb_widget_text',
        'wb_widget_state',
        'wb_widget_desktop_state',
        'wb_widget_mobile_state',
        'wb_widget_show_side'
    ];

    protected $visible = [
        'id',
        'url',
        'code',
        'created_at'
    ];

    public function getUrlAttribute($value)
    {
        $parsed_url = parse_url($value);
        if( !isset($parsed_url['scheme']) ){
            $value_with_scheme = "http://$value";
            $parsed_url = parse_url($value_with_scheme);
        }
        $punycode = new Punycode();
        $parsed_url['host'] = $punycode->decode( $parsed_url['host'] );
        unset($parsed_url['scheme']);
        return \App\Services\Helpers::unparse_url($parsed_url);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}
