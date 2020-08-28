<?php

namespace App\Http\Middleware;

use Closure;

class DebugUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = \App\User::first();
        auth()->login($user);
        return $next($request);
    }
}
