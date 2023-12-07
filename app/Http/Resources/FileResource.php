<?php // Omar


namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id"          => $this->id,
            "name"        => $this->name,
            "desc"        => $this->description,
            "file_key"    => $this->file_key,
            "group_id"    => $this->group_id,
            "group_name"  => $this->group->name,
            // "path"        => $this->path,
            "reserved_by" => $this->reserved_by,
            "reserved_by_name" => $this->reservedBy ?? "",
            "size"        => $this->size . ' KB',
            "type"        => $this->mime,
            "created_by"  => $this->owner->getFullName(),
            "created_at"  => Carbon::parse($this->created_at)->format("Y-m-d H:i"),
            "last_update" => Carbon::parse($this->updated_at)->format("Y-m-d H:i"),
        ];
    }
}
