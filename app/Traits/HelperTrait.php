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

    protected function generateUniqeStringKey($model, string $columnName, int $length = 32): string
    {
        do {
            $uniqueKey = Str::random($length);
        } while ($model::where($columnName, $uniqueKey)->first());
        return $uniqueKey;
    }

    protected function generateUniqeNumericKey($model, string $columnName, int $min = 1100, int $max = 9900): string
    {
        do {
            $uniqueKey = random_int($min, $max);
        } while ($model::where($columnName, $uniqueKey)->first());
        return $uniqueKey;
    }
}
