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
            // "path"        => $this->path,        
            "reserved_by" => $this->reserved_by, 
            "reserved_by_name" =>$this->reservedBy==null?"": $this->reservedBy->getFullName(), 
            "size"        => $this->size,
            "type"        => $this->mime,        
            "created_at"  => Carbon::parse($this->created_at)->format("Y-m-d H:i"),

        ];
    }
}

