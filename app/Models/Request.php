<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Request extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'ip',
        'endpoint',
        'request',
        'response',
        'status',
        'amount',
        'price_id',
        'user_id',
    ];
}
