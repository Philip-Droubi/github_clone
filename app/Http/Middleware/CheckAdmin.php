<?php

namespace App\Http\Middleware;

use App\Traits\GeneralTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;

class CheckAdmin
{
    use GeneralTrait;
    public function handle(Request $request, Closure $next): Response
    {
        if (Gate::allows("admin"))
            return $next($request);
        return $this->fail("Access denied.", 403);
    }
}
