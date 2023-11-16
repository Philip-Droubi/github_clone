<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Models\File\File;
use App\Models\Group\Group;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;

class FileController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index()
    {
        //
    }

    public function store(FileRequest $request)
    {
        $group = Group::where("group_key", $request->group_key)->first();
        $desc_id = 0;
        if ($this->checkFilesNames($request->files_array)) {
            foreach ($request->files_array as $file) {
                if ($path = $this->storeFile($file, "/groups/" . $group->id . "/Files", Config::get("custom.private_path")));
                $fileCreated[] = [
                    "name" => $file->getClientOriginalName(),
                    "description" => $request->files_desc[$desc_id] ?? null,
                    "mime" => $file->getClientOriginalExtension(),
                    "size" => (float)$file->getSize() / 1024, //save in KB
                    "reserved_by" => null,
                    "path" => $path,
                    "file_key" => $this->generateUniqeStringKey(File::class, "file_key", Config::get("custom.file_key_length")),
                    "group_id" => $group->id,
                    "created_by" => auth()->id(),
                    "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                    "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
                ];
                $desc_id++;
            }
            $files = File::insert($fileCreated);
            return $this->success([], "Files uploaded successfully!");
        }
        return $this->fail("One or more files have the same 'name.extension', upload rejected!");
    }

    public function checkFilesNames($files)
    {
        return true;
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
