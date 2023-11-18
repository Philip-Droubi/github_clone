<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Models\File\File;
use App\Models\Group\Group;
use App\Models\Group\GroupUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        if ($this->checkFilesNames($request->files_array, $group, $request)) {
            foreach ($request->files_array as $file) {
                if ($path = $this->storeFile($file, "/groups/" . $group->id . "/Files", Config::get("custom.private_path")));
                $fileCreated[] = [ //Help in not create N+ query problem
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

    public function checkFilesNames($files, Group $group, Request $request): bool
    {
        //New files
        $uploadedFilesArrayNames = [];
        foreach ($request->files_array as $file) {
            $uploadedFilesArrayNames[] = $file->getClientOriginalName();
        }
        //Group files
        $groupFilesNames = File::query()->where('group_id', $group->id)->pluck("name")->toArray();
        //check duplicate values
        if (count(array_intersect($uploadedFilesArrayNames, $groupFilesNames)) == 0)
            return true;
        return false;
    }

    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function checkIn(FileRequest $request)
    {
        /** INFO:
         * Check if I'm in group
         * Check if file is not reserved by others & exists
         */
        DB::beginTransaction();
        $files = File::query()->whereIn("file_key", $request->files_keys)->get(); // Get all required files in one query
        $userGroups = GroupUser::where("user_id", auth()->id())->pluck('group_id')->toArray(); // Get all groups id that user belongs to
        /** INFO:
         * Why not to check if all files belongs to user groups here?
         * cause while this action is runing user could be removed from one group or more.
         * the function will not detect if the user was removed so that it's better to be done inside the foreach loop for each file alone
         * If an exception happen the DB will not commit (will rollback) and all the files will be free to reserve again.
         */
        foreach ($files as $file) {
            if ((!in_array($file->group_id, $userGroups)) && $file->reserved_by == null) throw new Exception("You have no access to this file");
            $file->reserved_by = auth()->id();
            $file->save();
            $this->createFileLog(
                $file->id,
                auth()->id(),
                "CheckIn",
                4,
                "file " . $file->name . " was checked-in by user " . $request->user()->getFullName()
            );
        }
        DB::commit();
        return $this->success([], "All required files have been reserved.");
    }

    public function checkOut(Request $request) //Take only one file by request
    { //TODO: Add upload file here instead of new request
        /** INFO:
         * Check if:
         * File exist
         * File reserved by this user
         * File is in one group of that this user is in
         */
        if (!$file = File::where(["file_key" => $request->file_key, "reserved_by" => auth()->id()])->whereIn("group_id", GroupUser::where("user_id", auth()->id())->pluck('group_id')->toArray())->first()) return $this->fail("File not found!", 400);
        DB::beginTransaction();
        $file->reserved_by = null;
        $file->save();
        $this->createFileLog(
            $file->id,
            auth()->id(),
            "CheckOut",
            2,
            "file " . $file->name . " was checked out by user " . $request->user()->getFullName()
        );
        DB::commit();
        return $this->success();
    }
}
