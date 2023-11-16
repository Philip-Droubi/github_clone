<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use App\Http\Resources\GroupResource;
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
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;

class GroupController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index()
    {
        //
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
        foreach ($request->users_list as $id) // Add all group members
            GroupUser::firstOrCreate([
                "group_id" => $group->id,
                "user_id"  => $id,
            ]);
        DB::commit();
        return $this->success(new GroupResource($group));
    }

    public function show(Request $request)
    {
        //Omar

        $group = Group::where(['group_key' => $request->group_key])->first();
        if (is_null($group))
            return $this->fail("Group with key '" . $request->id . "' not found.", 404);
        if ($group->owner->id != auth()->id() || !in_array(auth()->id(), $group->contributers))
            return $this->fail("You don't have access to this group.", 403);

        return $this->success($group);
    }

    public function getMyGroups(Request $requet)
    {
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
    public function getGroupsByID(Request $requet)
    {
        // Omar
        $order  = $requet->orderBy ?? "name";
        $desc   = $requet->desc ?? "desc";
        $limit  = $requet->limit ?? 20;
        $groups = Group::where("name", "LIKE", "%" . $requet->name . "%")
            ->orderBy($order, $desc)->paginate($limit);
        $data   = [];
        foreach ($groups as $group) {
            if (in_array($requet->id, $group->contributers))
                $data[] = new GroupResource($group);
        }
        return $this->success($data);
    }

    public function update(GroupRequest $request)
    {
        DB::beginTransaction();
        $group = Group::where("group_key", $request->group_key)->first();
        $request->name ? ($request->name != $group->name ? $group->name = $request->name : false) : false;
        $request->exists('desc') ? ($request->desc != $group->description ? $group->description = $request->desc : false) : false;
        $group->save();
        // Delete users
        foreach ($request->deleted_users_list as $id) {
            if (!File::query()->where(["group_id" => $group->id, "reserved_by" => $id])->first()) {
                if ($id != $group->created_by) // To not delete group owner
                    GroupUser::where(['group_id' => $group->id, "user_id" => $id])->delete();
            } else {
                $user     = User::find($id);
                $userName = $user->first_name . " " . $user->last_name;
                throw new Exception("The user '" . $userName . "' reserved a file within the group,the deletion operation could not be done");
            }
        }
        // Add new users
        foreach ($request->users_list as $id) {
            GroupUser::firstOrCreate([
                "group_id" => $group->id,
                "user_id"  => $id,
            ]);
        }
        DB::commit();
        return $this->success(new GroupResource($group), "updated");
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();
        if (!$group = Group::where(['group_key' => $request->group_key, "created_by" => auth()->id()])->first())
            return $this->fail('Group not found!', 404);
        $name = $group->name;
        if (
            Group::query()->where('id', $group->id)->whereDoesntHave('files', function ($query) use ($request, $group) {
                $query->where('group_id', $group->id)->whereNotNull('reserved_by');
            })->delete()
        ) {
            DB::commit();
            return $this->success([], "Group '" . $name . "' has been successfully deleted!");
        }
        DB::rollBack();
        return $this->fail('Cannot delete this group as one or more of its files are reserved', 400);
    }
}
