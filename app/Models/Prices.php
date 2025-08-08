<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prices extends Model
{
     protected $fillable = [
        'name',
        'value_buy',
        'value_sell',
    ];
}
