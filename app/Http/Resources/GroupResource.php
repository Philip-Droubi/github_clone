<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id"           => $this->id,
            "name"         => $this->name,
            "desc"         => $this->description,
            "group_key"    => $this->group_key,
            "created_at"   => Carbon::parse($this->created_at)->format("Y-m-d H:i"),
            "files"        => $this->files->count(),
            "contributers" => $this->contributers->count(),
        ];
    }
}
