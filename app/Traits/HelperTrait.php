<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait HelperTrait
{
    protected function storeFile($file, $path, $mainPath = "public/assets/")
    {
        $destination_path = $mainPath . $path;
        $fileToStore = $file;
        $randomString = Str::random(30);
        $file_name =  $randomString . str_replace(' ', '_', $fileToStore->getClientOriginalName());
        $savePath = $fileToStore->storeAs($destination_path, $file_name);
        return $path . "/" . $file_name;
    }
}
