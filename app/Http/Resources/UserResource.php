<?php

namespace App\Http\Resources;

use App\Models\Group\Commit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "role" => $this->role,
            "role_name" => $this->role == 1 ? "Admin" : "User",
            "account_name" => $this->account_name,
            "email" => $this->email,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "img" => is_null($this->img) ? Config::get('custom.user_default_image') : "storage/assets/" . $this->img,
            "created_at" => Carbon::parse($this->created_at)->format('Y-m-d H:i'),
            "groups_count" => count($this->groups),
            "commits_count" => count($this->commits),
            "commits_this_year" => Commit::where("commiter_id", $this->id)->where("created_at", ">=", Carbon::parse((string)Carbon::now()->format('Y'))->format('Y-m-d'))->count(),
        ];
    }
}
