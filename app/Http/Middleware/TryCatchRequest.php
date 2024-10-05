<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;

class TryCatchRequest
{
    use GeneralTrait;
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (!empty($response->exception)) {
            DB::transactionLevel() == 2 ? DB::rollBack() : false;
            if (app()->environment() == "local")
                return $this->fail($response->exception->getMessage() . " on_line: " . $response->exception->getLine() . "\n" . " Trace : " . $response->exception->__toString());
            return $this->fail($response->exception->getMessage());
        }
        return $response;
    }
}
