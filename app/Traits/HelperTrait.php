<?php

namespace App\Traits;

use App\Models\File\FileLog;
use App\Models\Group\Commit;
use App\Models\Group\CommitFile;
use App\Models\Group\Group;
use App\Models\Group\GroupLog;
use ArrayObject;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use ZipArchive;

trait HelperTrait
{
    /**
     * @param \File $file
     * @description Used to store files
     * @return string $file_path
     */
    protected function storeFile($file, String $path, String $mainPath = "public/assets/", String $deletePath = null, Bool $isInPrivate = false, Bool $isDir = false): string
    {
        if ($deletePath)
            if ($isInPrivate)
                !$isDir ? Storage::disk("private")->delete($deletePath) : Storage::disk("private")->deleteDirectory($deletePath);
            else !$isDir ? Storage::delete($deletePath) : Storage::deleteDirectory($deletePath);
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

    protected function createFileLog($file_id, $user_id, string $action, int $importance = 1, string $info = ""): bool
    {
        if ($importance < 1) $importance = 1;
        elseif ($importance > 5) $importance = 5;
        $log = FileLog::create([
            "action" => $action,
            "additional_info" => $info,
            "importance" => $importance,
            "file_id" => $file_id,
            "user_id" => $user_id,
        ]);
        if ($log) return true;
        return false;
    }
    protected function createGroupLog($group_id, $user_id, string $action, string $info = "", int $importance = 1): bool
    {
        if ($importance < 1)
            $importance = 1;
        elseif ($importance > 5)
            $importance = 5;
        $log = GroupLog::create([
            "action"          => $action,
            "additional_info" => $info,
            "importance"      => $importance,
            "group_id"        => $group_id,
            "user_id"         => $user_id,
        ]);
        if ($log)
            return true;
        return false;
    }

    public function createZipFile(string $name, $files)
    {
        $zip = new ZipArchive;
        $zipFileName = str_replace(' ', '_', $name) . "_" . Carbon::now()->format("Y_m_d_H_i") . '.zip';
        if ($zip->open(storage_path($zipFileName), ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($files as $file) {
                $zip->addFile(storage_path("app/private/" . $file->path), $file->name);
            }
            $zip->close();
            return storage_path($zipFileName);
        }
        return false;
    }

    public function createCommit(int $groupID, int $userID, string $action, string $description = "", array $files = []): bool
    {
        $commit = Commit::create([
            "action" => $action,
            "description" => $description ?? "",
            "commiter_id" => $userID,
            "group_id" => $groupID,
        ]);
        $commitFiles = [];
        foreach ($files as $file) {
            $commitFiles[] = [
                "file_id" => $file,
                "commit_id" => $commit->id,
                "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
            ];
        }
        if ($commitFiles)
            CommitFile::insert($commitFiles);
        return true;
    }

    public function setPaginationData($objects, array $data): array
    {
        $data['last_page'] = $objects->lastPage();
        $data['total'] = $objects->total();
        $data['perPage'] = $objects->perPage();
        $data['currentPage'] = $objects->currentPage();
        return $data;
        // return [
        //     'last_page' => $objects->lastPage(),
        //     'total' => $objects->total(),
        //     'currentPage' => $objects->currentPage(),
        //     'perPage' => $objects->perPage(),
        // ];
    }
}
