<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType ='string';
}
