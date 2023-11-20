<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Http\Resources\FileResource;
use App\Models\File\File;
use App\Models\Group\Group;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index(Request $requet)
    {
        //Omar
        //TODO: add admin gate

        $order = $requet->orderBy ?? "name";
        $desc  = $requet->desc ?? "desc";
        $limit = $requet->limit ?? 20;
        $files = File::where("created_by", auth()->id())
            ->where("name", "LIKE", "%" . $requet->name . "%")
            ->orderBy($order, $desc)->paginate($limit);
        $data  = [];
        foreach ($files as $file) {
            $data[] = new FileResource($file);
        }
        return $this->success($data);

    }
    public function store(FileRequest $request)
    {
        $group   = Group::where("group_key", $request->group_key)->first();
        $desc_id = 0;
        if ($this->checkFilesNames($request->files_array)) {
            foreach ($request->files_array as $file) {
                if ($path = $this->storeFile($file, "/groups/" . $group->id . "/Files", Config::get("custom.private_path")))
                    ;
                $fileCreated[] = [
                    "name"        => $file->getClientOriginalName(),
                    "description" => $request->files_desc[$desc_id] ?? null,
                    "mime"        => $file->getClientOriginalExtension(),
                    "size"        => (float) $file->getSize() / 1024, //save in KB
                    "reserved_by" => null,
                    "path"        => $path,
                    "file_key"    => $this->generateUniqeStringKey(File::class, "file_key", Config::get("custom.file_key_length")),
                    "group_id"    => $group->id,
                    "created_by"  => auth()->id(),
                    "created_at"  => Carbon::now()->format("Y-m-d H:i:s"),
                    "updated_at"  => Carbon::now()->format("Y-m-d H:i:s"),
                ];

                $desc_id++;
            }

            $files = File::insert($fileCreated);
            //Omar
            //Add Log
            if ($files) {
                foreach ($fileCreated as $file) {
                    $user       = User::find(auth()->id())->first();
                    $fileObject = File::where('file_key', $file['file_key'])->first();
                    $this->createFileLog(
                        $fileObject->id,
                        auth()->id(),
                        "Create",
                        5,
                        "File '" . $file['name'] . "' created by:'" . $user->account_name . "'",
                    );
                }
            }

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

    public function getFilesByGroupID(string $id, Request $request)
    {
        //Omar

        $group = Group::where(['group_key' => $id])->with('contributers')->first();
        if (!$group)
            return $this->fail("Group with key '" . $id . "' not found.", 404);

        $contributers = [];
        foreach ($group->contributers as $cont) {
            $contributers[] = $cont->user_id;
        }
        if ($group->created_by != auth()->id() || !in_array(auth()->id(), $contributers)) {
            return $this->fail("You don't have an access to this group files.", 403);
        }
        $order = $request->orderBy ?? "name";
        $desc  = $request->desc ?? "desc";
        $limit = $request->limit ?? 20;
        $files = File::where('group_id', $group->id) 
            ->where("name", "LIKE", "%" . $request->name . "%")
            ->orderBy($order, $desc)
            ->paginate($limit);


        $data = [];
        foreach ($files as $file) {
            $data[] = new FileResource($file);
        }
        return $this->success($data);
    }
    public function getMyFiles(Request $request)
    {
        //Omar

        $order = $request->orderBy ?? "name";
        $desc  = $request->desc ?? "desc";
        $limit = $request->limit ?? 20;
        $files = File::where(['created_by' => auth()->id()])
            ->where("name", "LIKE", "%" . $request->name . "%")
            ->orderBy($order, $desc)->paginate($limit);
        ;
        $data = [];
        foreach ($files as $file) {
            $data[] = new FileResource($file);
        }
        return $this->success($data);
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
    public function destroy(Request $request)
    {
        // Omar
        DB::beginTransaction();
        $file = File::where("file_key",$request->id)->first();
        if (!$file)
            return $this->fail("File not found.", 404); 

       if(File::query()->where(["file_key"=>$request->id,'created_by'=>auth()->id()])->whereNull('reserved_by')->delete()){
            Storage::disk("private")->delete($file->path);
            DB::commit();
            return $this->success([], "File deleted successfully", 200);
       }
        DB::rollBack();
        return $this->fail("File is reserved", 403);
    }
}
