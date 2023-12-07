<?php

namespace App\Http\Resources;

use App\Models\Group\Commit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastCommit = Commit::where("group_id", $this->id)->orderBy("created_at", "Desc")->first();
        return [
            "id"           => $this->id,
            "name"         => $this->name,
            "desc"         => $this->description,
            "group_key"    => $this->group_key,
            "created_at"   => Carbon::parse($this->created_at)->format("Y-m-d H:i"),
            "created_by"   => $this->owner->getFullName(),
            "files"        => count($this->files),
            "contributers_count" => count($this->contributers),
            "commits"      => count($this->commits),
            "last_commit"  => $lastCommit ? Carbon::parse(($lastCommit->created_at))->format("Y-m-d H:i:s") : "",
            "last_commit_By"  => $lastCommit ? $lastCommit->commiter->getFullName() : "",
        ];
    }
}
