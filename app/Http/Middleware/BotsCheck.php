<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\GeneralTrait;

class BotsCheck
{
    use GeneralTrait;
    public function handle(Request $request, Closure $next)
    {
        $validator = Validator::make($request->only('c_name'), [
            'c_name' => ['present'],
        ]);
        if ($validator->fails())
            return $this->fail($validator->errors()->first(), 400);
        if (is_null($request->c_name)) {
            return $next($request);
        }
        return $this->success([], "ok", 202);
    }
}
