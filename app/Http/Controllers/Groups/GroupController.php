<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use App\Models\Group\GroupUser;
use Illuminate\Http\Request;
use App\Models\Group\Group;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            "name" => $request->name,
            "description" => $request->description,
            "group_key" => $this->generateUniqeStringKey(Group::class, 'group_key', Config::get('custom.group_key_length', 32)),
            "is_public" => false,
            "created_by" => auth()->user()->id,
        ]);
        GroupUser::create([
            "group_id" => $group->id,
            "user_id" => auth()->id(),
        ]);
        foreach ($request->users_list as $id)
            GroupUser::firstOrCreate([
                "group_id" => $group->id,
                "user_id" => $id,
            ]);
        DB::commit();
        return $this->success();
    }

    public function show(Request $requet)
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
}
