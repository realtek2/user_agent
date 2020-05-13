<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App\TelegramUser
 *
 * @property int $id
 * @property string $chat_id
 * @property string|null $user_name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $code
 * @property string|null $last_command
 * @property int|null $owner_id
 * @property boolean|null $owner_has_phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramUser whereUserName($value)
 * @mixin \Eloquent
 */
class TelegramUser extends Model
{
    protected $fillable = [
        'chat_id',
        'first_name',
        'last_name',
        'username',
        'code',
        'last_command',
        'owner_id',
        'owner_has_phone'
    ];


    public function refreshCode()
    {
        $this->update([
            'code' => Str::upper(
                Str::random(16)
            )
        ]);

        return $this;
    }
}
