<?php

namespace App\Http\Middleware;

use Closure;

class Lang
{
    public function handle($request, Closure $next)
    {
       \Lang::setLocale($request['lang']);
        return $next($request);
    }
}
