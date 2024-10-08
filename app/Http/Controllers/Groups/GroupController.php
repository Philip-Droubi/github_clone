<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use App\Http\Resources\ContributerResource;
use App\Http\Resources\GroupResource;
use App\Models\File\File;
use App\Models\Group\GroupUser;
use Illuminate\Http\Request;
use App\Models\Group\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index(Request $requet)
    {
        $order  = $requet->orderBy ?? "name";
        $desc   = $requet->desc ?? "desc";
        $limit  = $requet->limit ?? 20;
        $groups = Group::where("name", "LIKE", "%" . $requet->name . "%")
            ->orderBy($order, $desc)->paginate($limit);
        $data = [];
        $items = [];
        foreach ($groups as $group) {
            $items[] = new GroupResource($group);
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($groups, $data);
        return $this->success($data);
    }

    public function store(GroupRequest $request)
    {
        if (!$this->checkGroupName($request->name, auth()->id())) return $this->fail("Can not use This name, name already used");
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
        //Add Log
        $user = User::find(auth()->id())->first();
        $this->createGroupLog(
            $group->id,
            auth()->id(),
            "Create",
            "Group '" . $group->name . "' created by: '@" . $user->account_name . " '(" . $user->getFullName() . ").",
            5
        );
        return $this->success(new GroupResource($group));
    }

    public function checkGroupName(string $name, int $id, array $exceptedIDs = []): bool
    {
        $usedNames = Group::where("created_by", $id);
        if ($exceptedIDs)
            $usedNames->whereNotIn("id", $exceptedIDs);
        $usedNames = $usedNames->pluck("name")->toArray();
        $mergedArray = array_merge(["public"], $usedNames);
        if (in_array($name, $mergedArray))
            return false;
        return true;
    }

    public function show(Request $request)
    {
        $group = Group::where(['group_key' => $request->group_key])->with('owner', 'contributers')->first();
        if (!$group)
            return $this->fail("Group not found.", 404);
        $contributers = [];
        foreach ($group->contributers as $cont) {
            $contributers[] = $cont->user_id;
        }
        if ($group->owner->id != auth()->id() || !in_array(auth()->id(), $contributers) || (auth()->user()->role != 1))
            return $this->fail("You don't have access to this group.", 403);
        $contributers = User::whereIn("id", GroupUser::where("group_id", $group->id)->limit(5)->pluck("user_id")->toArray())
            ->with("commits", function ($q) use ($group) {
                return $q->where(["commiter_id" => auth()->id(), "group_id" => $group->id])->orderBy("created_at", "Desc")->first();
            })->get();
        $contributersData = [];
        foreach ($contributers as $cont) {
            $contributersData[] = [
                "id" => $cont->id,
                "account_name" => "@" . $cont->account_name,
                "full_name" => $cont->getFullName(),
                "first_name" => $cont->first_name,
                "last_name" => $cont->last_name,
                "img" => is_null($cont->img) ? Config::get('custom.user_default_image') : "storage/assets/" . $cont->img,
                "last_commit" => $cont->commits->isNotEmpty()
                    ? (string)Carbon::parse($cont->commits[0]->created_at)->format("Y-m-d H:i:s")
                    : "Did not commit yet!",
            ];
        }
        $data[] = [
            ...(new GroupResource($group))->toArray(request()),
            "contributers" => $contributersData
        ];
        return $this->success($data);
    }

    public function getGroupsByID(Request $request) // user groups
    {
        if (!$user = User::find($request->id ?? auth()->id()))
            return $this->fail("User not found", 404);
        $order  = $request->orderBy ?? "name";
        $desc   = $request->desc ?? "desc";
        $limit  = $request->limit ?? 20;
        if (!$request->id)
            $groups = $this->getMyGroups($request);
        else {
            if ($request->user()->role == 1)
                $groups = $this->getGroupsForOthersAsAdmin($request, $user);
            else
                $groups = $this->getGroupsForOthers($request, $user);
        }
        $data   = [];
        $items   = [];
        foreach ($groups as $group) {
            $contributers = User::whereIn("id", GroupUser::where("group_id", $group->id)->limit(5)->pluck("user_id")->toArray())
                ->with("commits", function ($q) use ($group) {
                    return $q->where(["commiter_id" => auth()->id(), "group_id" => $group->id])->orderBy("created_at", "Desc")->first();
                })->get();
            $contributersData = [];
            foreach ($contributers as $cont) {
                $contributersData[] = [
                    "id" => $cont->id,
                    "account_name" => "@" . $cont->account_name,
                    "full_name" => $cont->getFullName(),
                    "first_name" => $cont->first_name,
                    "last_name" => $cont->last_name,
                    "img" => is_null($cont->img) ? Config::get('custom.user_default_image') : "storage/assets/" . $cont->img,
                ];
            }
            $items[] = [
                ...(new GroupResource($group))->toArray(request()),
                "contributers" => $contributersData
            ];
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($groups, $data);
        return $this->success($data);
    }

    public function getMyGroups($request)
    {
        $order  = $request->orderBy ?? "name";
        $desc   = $request->desc ?? "desc";
        $limit  = $request->limit ?? 20;
        return $groups = Group::query()->where("name", "LIKE", "%" . $request->name . "%")
            ->whereIn("id", GroupUser::where("user_id", auth()->id())->pluck("group_id")->toArray())
            ->orderBy($order, $desc)->paginate($limit);
    }

    public function getGroupsForOthersAsAdmin($request, $user)
    {
        $order  = $request->orderBy ?? "name";
        $desc   = $request->desc ?? "desc";
        $limit  = $request->limit ?? 20;
        return $groups = Group::query()->where("name", "LIKE", "%" . $request->name . "%")
            ->whereIn("id", GroupUser::where("user_id", $user->id)->pluck("group_id")->toArray())
            ->orderBy($order, $desc)->paginate($limit);
    }

    public function getGroupsForOthers($request, $user)
    {
        $order  = $request->orderBy ?? "name";
        $desc   = $request->desc ?? "desc";
        $limit  = $request->limit ?? 20;
        return $groups = Group::query()->where("name", "LIKE", "%" . $request->name . "%")
            ->whereIn("id", GroupUser::where("user_id", $user->id)->pluck("group_id")->toArray())
            ->whereHas("contributers", function ($q) {
                return $q->where("user_id", auth()->id());
            })
            ->orderBy($order, $desc)->paginate($limit);
    }

    public function update(GroupRequest $request)
    {
        if ($group = Group::where(["group_key" => $request->group_key, "created_by" => auth()->id()])->first()) return $this->fail("Group not found!");
        if ($group->is_public) return $this->fail("Can not update this group!");
        $actionUser   = User::find(auth()->id())->first();
        $oldGroupName = $group->name;
        if (!$this->checkGroupName($request->name, auth()->id(), [$group->id])) return $this->fail("Can not use This name, name already used");
        DB::beginTransaction();
        $request->name ? ($request->name != $group->name ? $group->name = $request->name : false) : false;
        $request->exists('desc') ? ($request->desc != $group->description ? $group->description = $request->desc : false) : false;
        $group->save();
        //Add Log
        if ($oldGroupName != $group->name) {
            $this->createGroupLog(
                $group->id,
                auth()->id(),
                "Update",
                "Group '" . $oldGroupName . "' name changed to " . $group->name . "' by: '@" . $actionUser->account_name . " '(" . $actionUser->getFullName() . ").",
                2
            );
        }
        // Delete users

        if ($request->deleted_users_list)
            foreach ($request->deleted_users_list as $id) {
                if (!File::query()->where(["group_id" => $group->id, "reserved_by" => $id])->first()) {
                    if ($id != $group->created_by) // To not delete group owner
                        GroupUser::where(['group_id' => $group->id, "user_id" => $id])->delete();
                    //Add Log
                    $user = User::find($id)->first(); //TODO: check
                    $this->createGroupLog(
                        $group->id,
                        auth()->id(),
                        "Update",
                        "User '@" . $user->account_name . "' removed from group  '" . $group->name . "' by: '@" . $actionUser->account_name . " '(" . $actionUser->getFullName() . ").",
                        2
                    );
                } else {
                    $user = User::find($id);
                    $userName = $user->first_name . " " . $user->last_name;
                    return $this->fail("The user '" . $userName . "' reserved a file within the group,the deletion operation could not be done");
                    // throw new Exception("The user '" . $userName . "' reserved a file within the group,the deletion operation could not be done");
                }
            }
        // Add new users

        if ($request->users_list)
            foreach ($request->users_list as $id) {
                GroupUser::firstOrCreate([
                    "group_id" => $group->id,
                    "user_id" => $id,
                ]);
                //Add Log
                $user = User::find($id)->first(); //TODO: check
                $this->createGroupLog(
                    $group->id,
                    auth()->id(),
                    "Update",
                    "User '@" . $user->account_name . "' added to group " . $group->name . "' by: '@" . $actionUser->account_name . " '(" . $actionUser->getFullName() . ").",
                    2
                );
            }
        DB::commit();
        return $this->success(new GroupResource($group), "updated");
    }

    public function getGroupContributers(string $id, Request $request)
    {
        $group = Group::where(['group_key' => $id])->first();
        if (!$group)
            return $this->fail("Group not found.", 404);
        $contributers = [];
        foreach ($group->contributers as $cont) {
            $contributers[] = $cont->user_id;
        }
        if ((auth()->user()->role != 1))
            if ($group->created_by != auth()->id() && !$group->is_public && !in_array(auth()->id(), $contributers)) {
                return $this->fail("You don't have an access to this group.", 403);
            }
        $limit        = $request->limit ?? 20;
        $contributers = GroupUser::where('group_id', $group->id)->paginate($limit);

        $data = [];
        $items = [];
        foreach ($contributers as $cont) {
            $items[] = new ContributerResource($cont);
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($contributers, $data);
        return $this->success($data);
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();
        if (!$group = Group::where(['group_key' => $request->group_key, "created_by" => auth()->id()])->first()) return $this->fail('Group not found!', 404);
        if ($group->is_public) return $this->fail("Can not delete this group!");
        $name = $group->name;
        $id = $group->id;
        //Add Log
        $user = User::find(auth()->id())->first();
        $this->createGroupLog(
            $group->id,
            auth()->id(),
            "Delete",
            "Group '" . $name . "' deleted by:@'" . $user->account_name . "'",
            5
        );
        //
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
        if (count($files) == 0) return $this->fail("This group has no files yet", 413);
        if ($zipFile = $this->createZipFile($group->name, $files))
            return response()->download($zipFile)->deleteFileAfterSend(true);
        return $this->fail("Failed to create the zip file.", 500);
    }
}
