<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HelperTrait
{
    /**
     * @param \File $file
     * @description Used to store files
     * @return string $file_path
     */
    protected function storeFile($file, String $path, String $mainPath = "public/assets/", String $deletePath = null, Bool $isDir = false): string
    {
        if ($deletePath)
            !$isDir ? Storage::delete($deletePath) : Storage::deleteDirectory($deletePath);
        $destination_path = $mainPath . $path;
        $fileToStore = $file;
        $randomString = Str::random(30);
        $file_name =  $randomString . str_replace(' ', '_', $fileToStore->getClientOriginalName());
        $savePath = $fileToStore->storeAs($destination_path, $file_name);
        return $path . "/" . $file_name;
    }
}
