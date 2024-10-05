<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Config;

class CheckApiKey
{
    use GeneralTrait;
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('x-api-key') == Config::get('app.api_key'))
            return $next($request);
        return $this->fail("Unauthenticated", 401);
    }
}
