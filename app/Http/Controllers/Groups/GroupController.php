<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use App\Http\Resources\FileResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\UserResource;
use App\Models\File\File;
use App\Models\Group\GroupUser;
use Illuminate\Http\Request;
use App\Models\Group\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use ZipArchive;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index(Request $requet)
    {
        //TODO: add admin gate

        $order  = $requet->orderBy ?? "name";
        $desc   = $requet->desc ?? "desc";
        $limit  = $requet->limit ?? 20;
        $groups = Group::where("created_by", auth()->id())
            ->where("name", "LIKE", "%" . $requet->name . "%")
            ->orderBy($order, $desc)->paginate($limit);
        $data   = [];
        foreach ($groups as $group) {
            $data[] = new GroupResource($group);
        }
        return $this->success($data);

    }

    public function store(GroupRequest $request)
    {
        DB::beginTransaction();
        $group = Group::create([
            "name"        => $request->name,
            "description" => $request->desc,
            "group_key"   => $this->generateUniqeStringKey(Group::class, 'group_key', Config::get('custom.group_key_length', 32)),
            "is_public"   => false,
            "created_by"  => auth()->id(),
        ]);
        GroupUser::create([ //Add group owner
            "group_id" => $group->id,
            "user_id"  => auth()->id(),
        ]);

        if ($request->users_list)
            foreach ($request->users_list as $id) // Add all group members
                GroupUser::firstOrCreate([
                    "group_id" => $group->id,
                    "user_id" => $id,
                ]);
        DB::commit();
        //Omar
        //Add Log
        $user = User::find(auth()->id())->first();
        $this->createGroupLog(
            $group->id,
            auth()->id(),
            "Create",
            "Group '" . $group->name . "' created by:'" . $user->account_name . "'",
            5
        );
        return $this->success(new GroupResource($group));
    }

    public function show(Request $request, string $id)
    {
        //Omar

        $group = Group::where(['group_key' => $id])->with('owner', 'contributers')->first();
        if (!$group)
            return $this->fail("Group with key '" . $request->id . "' not found.", 404);
        $contributers = [];
        foreach ($group->contributers as $cont) {
            $contributers[] = $cont->user_id;
        }
        if ($group->owner->id != auth()->id() || !in_array(auth()->id(), $contributers))
            return $this->fail("You don't have access to this group.", 403);

        return $this->success($group);
    }

    // public function getMyGroups(Request $requet)
    // {
    //     $order  = $requet->orderBy ?? "name";
    //     $desc   = $requet->desc ?? "desc";
    //     $limit  = $requet->limit ?? 20;
    //     $groups = Group::where("created_by", auth()->id())
    //         ->where("name", "LIKE", "%" . $requet->name . "%")
    //         ->orderBy($order, $desc)->paginate($limit);
    //     $data   = [];
    //     foreach ($groups as $group) {
    //         $data[] = new GroupResource($group);
    //     }
    //     return $this->success($data);
    // }
    public function getGroupsByID(Request $requet)
    {
        // Omar
        if (!$user = User::find($requet->id ?? auth()->id()))
            return $this->fail("User not found", 404);
        $order  = $requet->orderBy ?? "name";
        $desc   = $requet->desc ?? "desc";
        $limit  = $requet->limit ?? 20;
        $groups = Group::where("name", "LIKE", "%" . $requet->name . "%")
            ->with('contributers')
            ->orderBy($order, $desc)->paginate($limit);
        $data   = [];
        foreach ($groups as $group) {
            $contributers = [];
            foreach ($group->contributers as $cont) {
                $contributers[] = $cont->user_id;
            }
            if (in_array($user->id, $contributers))
                $data[] = new GroupResource($group);
        }
        return $this->success($data);
    }


    public function update(GroupRequest $request)
    {
        $actionUser = User::find(auth()->id())->first();
        DB::beginTransaction();
        $group        = Group::where("group_key", $request->group_key)->first();
        $oldGroupName = $group->name;
        $request->name ? ($request->name != $group->name ? $group->name = $request->name : false) : false;
        $request->exists('desc') ? ($request->desc != $group->description ? $group->description = $request->desc : false) : false;
        $group->save();
        //Omar
        //Add Log
        if ($oldGroupName != $group->name) {

            $this->createGroupLog(
                $group->id,
                auth()->id(),
                "Update",
                "Group '" . $oldGroupName . "' name changed to " . $group->name . "' by: ' " . $actionUser->account_name . " '",
                2
            );
        }
        // Delete users

        if ($request->deleted_users_list)
            foreach ($request->deleted_users_list as $id) {
                if (!File::query()->where(["group_id" => $group->id, "reserved_by" => $id])->first()) {
                    if ($id != $group->created_by) // To not delete group owner
                        GroupUser::where(['group_id' => $group->id, "user_id" => $id])->delete();
                    //Omar
                //Add Log
                $user = User::find($id)->first(); //TODO: check
                $this->createGroupLog(
                    $group->id,
                    auth()->id(),
                    "Update",
                    "User '" . $user->account_name . "' removed from group  " . $group->name . "' by: ' " . $actionUser->account_name . " '",
                    2
                );
                } else {
                    $user = User::find($id);
                    $userName = $user->first_name . " " . $user->last_name;
                    throw new Exception("The user '" . $userName . "' reserved a file within the group,the deletion operation could not be done");
                }
            }
        // Add new users

        if ($request->users_list)
            foreach ($request->users_list as $id) {
                GroupUser::firstOrCreate([
                    "group_id" => $group->id,
                    "user_id" => $id,
                ]);
              //Omar
            //Add Log
            $user = User::find($id)->first(); //TODO: check
            $this->createGroupLog(
                $group->id,
                auth()->id(),
                "Update",
                "User '" . $user->account_name . "' added to group  " . $group->name . "' by: ' " . $actionUser->account_name . " '",
                2
            );
            }

        DB::commit();


        return $this->success(new GroupResource($group), "updated");
    }

    public function getGroupContributers(string $id, Request $request)
    {
        //Omar

        $group = Group::where(['group_key' => $id])->first();
        if (!$group)
            return $this->fail("Group not found.", 404);

        $contributers = [];
        foreach ($group->contributers as $cont) {
            $contributers[] = $cont->user_id;
        }
        if ($group->created_by != auth()->id() || !in_array(auth()->id(), $contributers)) {
            return $this->fail("You don't have an access to this group.", 403);
        }
        $limit        = $request->limit ?? 20;
        $contributers = GroupUser::where('group_id', $group->id) ->paginate($limit);

        $data = [];
        foreach ($contributers as $cont) {
            $data[] = new UserResource($cont->user);
        }
        return $this->success($data);
    }


    public function destroy(Request $request) {
        DB::beginTransaction();
        if (!$group = Group::where(['group_key' => $request->group_key, "created_by" => auth()->id()])->first()) return $this->fail('Group not found!', 404);
        $name = $group->name;
        $id = $group->id;
         //Omar
        //Add Log
        $user = User::find(auth()->id())->first();
        $this->createGroupLog(
            $group->id,
            auth()->id(),
            "Delete",
            "Group '" . $name . "' deleted by:'" . $user->account_name . "'",
            5
        );
        if (
            Group::query()->where('id', $group->id)->whereDoesntHave('files', function ($query) use ($request, $group) {
                $query->where('group_id', $group->id)->whereNotNull('reserved_by');
            })->delete()
        ) {
            Storage::disk("private")->deleteDirectory("groups/" . $id);
            DB::commit();
            return $this->success([], "Group '" . $name . "' has been successfully deleted!");
        }
        DB::rollBack();
        return $this->fail('Cannot delete this group as one or more of its files are reserved', 400);
    }

    public function cloneGroup(Request $request)
    {
        if (!$group = Group::where('group_key', $request->group_key)->whereIn("id", GroupUser::where("user_id", auth()->id())->pluck("group_id")->toArray())->first()) return $this->fail('Group not found!', 404);
        $files = File::where("group_id", $group->id)->get(['name', 'path']);
        //TODO: Check if files reserved by users => then it could not be downloaded
        if (count($files) == 0) return $this->fail("This group has no files yet", 400);
        if ($zipFile = $this->createZipFile($group->name, $files))
            return response()->download($zipFile)->deleteFileAfterSend(true);
        return $this->fail("Failed to create the zip file.", 500);
    }
}

