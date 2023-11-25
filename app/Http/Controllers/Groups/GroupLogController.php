<?php

namespace App\Http\Controllers\Groups;

use App\Http\Resources\LogResource;
use App\Models\Group\GroupLog;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class GroupLogController extends Controller
{
    use HelperTrait,GeneralTrait;
    public function index(Request $requet)
    {
        //Omar
        $actions =["create","update","delete"];
        $order = $requet->orderBy ?? "importance";
        $desc  = $requet->desc ?? "desc";
        $limit = $requet->limit ?? 20;
        $logs = GroupLog::orderBy($order, $desc)->paginate($limit);
        if(in_array(strtolower($requet->action),$actions))
            $logs = $logs->where('action',strtolower($requet->action));
        $data  = [];
        foreach ($logs as $log) {
            $data[] = new LogResource($log);
        }
        return $this->success($data);
    }
}
