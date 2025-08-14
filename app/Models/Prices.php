<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Prices extends Model
{
    use HasUuids;
    
     protected $fillable = [
        'name',
        'value_buy',
        'value_sell',
    ];
}
