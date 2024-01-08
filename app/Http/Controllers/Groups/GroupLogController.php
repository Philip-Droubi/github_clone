<?php

namespace App\Http\Controllers\Groups;

use App\Http\Resources\LogResource;
use App\Models\Group\GroupLog;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Group\Group;

class GroupLogController extends Controller
{
    use HelperTrait, GeneralTrait;
    public function index(Request $request)
    {
        $actions = ["create", "update", "delete"];
        $order = $request->orderBy ?? "importance";
        $desc  = $request->desc ?? "desc";
        $limit = $request->limit ?? 25;
        if ($request->group_key)
            $logs = GroupLog::whereIn("group_id", Group::where("group_key", $request->group_key)->pluck("id")->toArray())
                ->orderBy($order, $desc);
        else $logs = GroupLog::whereIn("group_id", Group::pluck("id")->toArray())
            ->orderBy($order, $desc);
        if (in_array(strtolower($request->action), $actions))
            $logs = $logs->where('action', strtolower($request->action));
        $logs = $logs->paginate($limit);
        $data  = [];
        $items  = [];
        if ($logs)
            foreach ($logs as $log) {
                $items[] = new LogResource($log);
            }
        $data["items"] = $items;
        $data = $this->setPaginationData($logs, $data);
        return $this->success($data);
    }
}
