<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! $request->user()->is_admin) {
            return response()->json(['message' => 'Apenas administradores podem acessar.'], 403);
        }

        return $next($request);
    }
}

