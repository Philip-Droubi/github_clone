<?php

namespace App\Http\Controllers\Files;

use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use App\Models\File\File;
use App\Models\File\FileLog;

class FileLogController extends Controller
{
    use HelperTrait, GeneralTrait;
    public function index(Request $request)
    {
        $actions = ["create", "update", "delete"];
        $order = $request->orderBy ?? "importance";
        $desc  = $request->desc ?? "desc";
        $limit = $request->limit ?? 25;
        if ($request->file_key)
            if (!$file = File::where("file_key", $request->file_key)->first())
                return $this->fail("File not found", 404);
        if ($request->file_key)
            $logs = FileLog::where("file_id", $file->id)
                ->orderBy($order, $desc);
        else $logs = FileLog::query()
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
