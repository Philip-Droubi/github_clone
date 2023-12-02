<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id"           => $this->id,
            "action"         => $this->action,
            "additional_info"         => $this->additional_info,
            "importance"    => $this->importance,
            "created_at"   => Carbon::parse($this->created_at)->format("Y-m-d H:i"),
            'user' => new UserResource($this->user),
        ];
    }
}
