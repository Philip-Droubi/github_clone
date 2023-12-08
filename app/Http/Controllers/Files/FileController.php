<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Http\Resources\FileResource;
use App\Models\File\File;
use App\Models\Group\Group;
use App\Models\Group\GroupUser;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class FileController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index(Request $requet)
    {
        //Omar
        $order = $requet->orderBy ?? "name";
        $desc  = $requet->desc ?? "desc";
        $limit = $requet->limit ?? 20;
        $files = File::where("name", "LIKE", "%" . $requet->name . "%")
            ->orderBy($order, $desc)->paginate($limit);
        $data  = [];
        $items  = [];
        foreach ($files as $file) {
            $items[] = new FileResource($file);
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($files, $data);
        return $this->success($data);
    }

    public function store(FileRequest $request)
    {
        $group   = Group::where("group_key", $request->group_key)->first();
        $user    = $request->user();
        if (!in_array($user->id, $group->contributers->pluck("user_id")->toArray())) return $this->fail("Access Denied!", 403);
        $desc_id = 0;
        $fileCreated = [];
        if ($this->checkFilesNames($request->files_array, $group->id)) {
            DB::beginTransaction();
            foreach ($request->files_array as $file) {
                if ($path = $this->storeFile($file, "groups/" . $group->id . "/Files", Config::get("custom.private_path") . "/")) {
                    $fileCreated[] = [ //Help in not create N+ query problem
                        "name" => $file->getClientOriginalName(),
                        "description" => $request->files_desc[$desc_id] ?? null,
                        "mime" => $file->getClientOriginalExtension(),
                        "size" => (float)round($file->getSize() / 1024, 2), //save in KB
                        "reserved_by" => null,
                        "path" => $path,
                        "file_key" => $this->generateUniqeStringKey(File::class, "file_key", Config::get("custom.file_key_length")),
                        "group_id" => $group->id,
                        "created_by" => auth()->id(),
                        "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                        "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
                    ];
                    $desc_id++;
                } else throw new Exception("Failed to store files");
            }
            $files = File::insert($fileCreated);
            //Create commit
            $filesCollection = collect($fileCreated);
            $filesObject = File::whereIn("file_key", $filesCollection->pluck("file_key")->toArray())->get();
            $this->createCommit(
                $group->id,
                auth()->id(),
                "Add Files",
                "User '@" . $user->account_name . " (" . $user->getFullName() . ")' added " . count($fileCreated) . " files to '" . $group->name . "' group.",
                $filesObject->pluck("id")->toArray()
            );
            //Omar
            //Add Log
            if ($files) {
                foreach ($filesObject as $file) {
                    $this->createFileLog(
                        $file->id,
                        auth()->id(),
                        "Create",
                        5,
                        "File '" . $file->name . "' created by:'" . $user->account_name . "'",
                    );
                }
            }
            DB::commit();
            return $this->success([], "Files uploaded successfully!");
        }
        return $this->fail("One or more files have the same 'name.extension', upload rejected!");
    }

    public function checkFilesNames($files, int $groupID, array $exceptedIDs = null): bool
    {
        //New files
        $uploadedFilesArrayNames = [];
        foreach ($files as $file) {
            $uploadedFilesArrayNames[] = $file->getClientOriginalName();
        }
        //Group files
        $groupFilesNames = File::query()->where('group_id', $groupID);
        if (!is_null($exceptedIDs))
            $groupFilesNames->whereNotIn('id', $exceptedIDs);
        $groupFilesNames = $groupFilesNames->pluck("name")->toArray();
        //check duplicate values
        if (count(array_intersect($uploadedFilesArrayNames, $groupFilesNames)) == 0)
            return true;
        return false;
    }

    public function show(string $id)
    {
        //
    }

    public function getFilesByGroupID(string $id, Request $request)
    {
        //Omar
        //TODO : CHECK IF WITH CAN WORK HERE
        $group = Group::where(['group_key' => $id])->with('contributers')->first();
        if (!$group)
            return $this->fail("Group not found.", 404);

        $contributers = [];
        foreach ($group->contributers as $cont) {
            $contributers[] = $cont->user_id;
        }
        if ($group->created_by != auth()->id() && !$group->is_public && !in_array(auth()->id(), $contributers)) {
            return $this->fail("You don't have access to this group files.", 403);
        }
        $order = $request->orderBy ?? "name";
        $desc  = $request->desc ?? "desc";
        $limit = $request->limit ?? 40;
        $files = File::where('group_id', $group->id)
            ->where("name", "LIKE", "%" . $request->name . "%")
            ->orderBy($order, $desc)
            ->paginate($limit);
        $data = [];
        $items = [];
        foreach ($files as $file) {
            $items[] = new FileResource($file);
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($files, $data);
        return $this->success($data);
    }

    public function getUserFiles(Request $request)
    {
        //Omar
        if (!$user = User::find($request->id ?? auth()->id()))
            return $this->fail("User not found", 404);
        $order = $request->orderBy ?? "name";
        $desc  = $request->desc ?? "desc";
        $limit = $request->limit ?? 40;
        $files = File::where(['created_by' => $user->id])
            ->where("name", "LIKE", "%" . $request->name . "%")
            ->orderBy($order, $desc)->paginate($limit);;
        $data = [];
        $items = [];
        foreach ($files as $file) {
            $items[] = new FileResource($file);
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($files, $data);
        return $this->success($data);
    }

    /**
     * Update the specified resource in storage.
     */

    public function replaceFile(FileRequest $request)
    {
        /**
         * If request has a file:
         * [1] Check for file_name
         * [2] Store file and check if path created
         * [3] Update file data
         * [4] Check if file desc changed
         * ////
         * if file desc changed
         * [1] Change file description
         * [2] Save cahnge
         * ////
         * Create a log for the changes
         * ////
         * Commit to DB a
         * return response
         */
        if (!$file = File::where(["file_key" => $request->file_key, "reserved_by" => auth()->id()])->first()) return $this->fail("File not found!", 404);
        $oldFile = clone $file;
        $fileReplaced = false;
        $user = $request->user();
        DB::beginTransaction();
        if ($newFile = $request->new_file) {
            if ($this->checkFilesNames([$newFile], $file->group_id, [$file->id])) {
                if ($path = $this->storeFile($newFile, "groups/" . $file->group_id . "/Files", Config::get("custom.private_path") . "/", $file->path, true)) {
                    $file->update([
                        "name" => $newFile->getClientOriginalName(),
                        "mime" => $newFile->getClientOriginalExtension(),
                        "size" => (float)round($newFile->getSize() / 1024, 3), //save in KB
                        "path" => $path,
                        "file_key" => $this->generateUniqeStringKey(File::class, "file_key", Config::get("custom.file_key_length")),
                        "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
                    ]);
                    $fileReplaced = true;
                } else throw new Exception("Failed to store files");
            } else return $this->fail("One or more files have the same 'name.extension', upload rejected!");
        }
        if ($request->exists('desc')) {
            $request->desc != $file->description ? $file->description = $request->desc : false;
            $file->save();
        }
        $this->logFileReplacment($oldFile, $file, $request->user(), $fileReplaced);
        $this->createCommit(
            $file->group_id,
            $user->id,
            "Replace File",
            "User ' @" . $user->account_name . " (" . $user->getFillName() . ") updated " . $oldFile->name . ".",
            [$file->id]
        );
        DB::commit();
        return $this->success([], "Update success");
    }

    public function destroy(Request $request)
    {
        // Omar
        DB::beginTransaction();
        $file = File::where("file_key", $request->id)->first();
        if (!$file)
            return $this->fail("File not found.", 404);
        $deletedFile = clone $file;
        if (File::query()->where(["file_key" => $request->id, 'created_by' => auth()->id()])->whereNull('reserved_by')->delete()) {
            Storage::disk("private")->delete($deletedFile->path);
            $user = $request->user();
            $this->createCommit(
                $deletedFile->group_id,
                $user->id,
                "Delete File",
                "File '" . $deletedFile->name . "' was deleted by user @" . $user->account_name . " (" . $user->getFullName() . ")."
            );
            DB::commit();
            return $this->success([], "File deleted successfully", 200);
        }
        DB::rollBack();
        return $this->fail("File is reserved", 403);
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
            if (
                !(in_array($file->group_id, $userGroups)
                    && ($file->reserved_by == null || $file->reserved_by == auth()->id()))
            ) throw new Exception("You have no access to this file");
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
    {
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
            3,
            "file " . $file->name . " was checked out by user " . $request->user()->getFullName()
        );
        DB::commit();
        return $this->success();
    }

    public function logFileReplacment(File $oldFile, File $newFile, User $user, $fileReplaced = false): void
    {
        if ($fileReplaced) {
            $info = "The File was replaced by '" .  $user->getFullName() . "'.";
            $this->createFileLog($newFile->id, $user->id, "File Replacment", 5, $info);
        }

        if (strtok($oldFile->name, ".")  != strtok($newFile->name, ".")) {
            $info = "File name was changed from '" . $oldFile->name . "' to '" . $newFile->name . "' caused by file replacment by user " . $user->getFullName() . ".";
            $this->createFileLog($newFile->id, $user->id, "File Replacment (Name change)", 5, $info);
        }

        if ($oldFile->size != $newFile->size) {
            $info = "File size was changed from '" . $oldFile->size . " KB' to '" . $newFile->size . " KB' caused by file replacment by user " . $user->getFullName() . ".";
            $this->createFileLog($newFile->id, $user->id, "File Replacment  (size change)", 5, $info);
        }

        if ($oldFile->mime != $newFile->mime) {
            $info = "File mime was changed from '" . $oldFile->mime . "' to '" . $newFile->mime . "' caused by file replacment by user " . $user->getFullName() . ".";
            $this->createFileLog($newFile->id, $user->id, "File Replacment  (mime change)", 5, $info);
        }

        if ($oldFile->description != $newFile->description) {
            $info = "File description was changed from '" . $oldFile->description . "' to '" . $newFile->description . "' caused by file replacment by user " . $user->getFullName() . ".";
            $this->createFileLog($newFile->id, $user->id, "File Replacment  (description change)", 4, $info);
        }
    }

    public function downloadFiles(FileRequest $request)
    {
        $files = File::query()->whereIn("file_key", $request->files_keys)->get(); // Get all required files in one query
        $userGroups = GroupUser::where("user_id", auth()->id())->pluck('group_id')->toArray();
        foreach ($files as $file) {
            if (
                !(in_array($file->group_id, $userGroups) //Check if user can access each file
                    // && ($file->reserved_by == null || $file->reserved_by == auth()->id())) // if only none reserved files are downloadable
                )
            )
                throw new Exception("You have no access to this file");
        }
        if ($zipFile = $this->createZipFile(substr($files[0]->name, 0, 10) . "_" . Carbon::now()->format("Y_m_d_H_i"), $files))
            return response()->download($zipFile)->deleteFileAfterSend(true);
        return $this->fail("Failed to create the zip file.", 500);
    }
}
