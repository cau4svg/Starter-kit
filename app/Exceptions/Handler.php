<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;

class Handler extends Exception
{
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
{
    if ($request->expectsJson()) {
     return $request->expectsJson()
        ? response()->json(['message' => 'Não autenticado'], 401)
        : response()->json(['message' => 'Não autenticado'], 401);
    }

    return redirect()->guest(route('login'));
}
}
