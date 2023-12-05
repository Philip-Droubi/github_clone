<?php

namespace App\Http\Middleware;

use App\Models\File\File;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Config;

class MaxFilesUpload
{
    use GeneralTrait;
    public function handle(Request $request, Closure $next): Response
    {
        if (
            count(File::where("created_by", auth()->id())->get(["id"]) ?? [])
            +
            count($request->files_array ?? []) > Config::get("custom.max_files_per_user")
        )
            return $this->fail("You have reached the maximum number of files allowed to be uploaded by you to the system.");
        return $next($request);
    }
}
