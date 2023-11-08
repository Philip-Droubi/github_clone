<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XSSProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        $userInput = $request->all();
        array_walk_recursive($userInput, function (&$userInput) {
            if (!$userInput == false)
                $userInput = strip_tags($userInput);
        });
        $request->merge($userInput);
        return $next($request);
    }
}
