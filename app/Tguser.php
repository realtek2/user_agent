<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tguser extends Model
{
    protected $fillable = [
        'chat_id',
        'first_name',
        'last_name',
        'username',
    ];
}
