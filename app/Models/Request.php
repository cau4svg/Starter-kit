<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'type',
        'ip',
        'endpoint',
        'request',
        'response',
        'status',
        'ammount',
        'price_id',
        'user_id',
    ];
}
