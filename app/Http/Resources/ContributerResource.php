<?php

namespace App\Http\Resources;

use App\Models\Group\Commit;
use App\Models\Group\Group;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ContributerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $lastContribution = Commit::where(["group_id" => $this->group_id, "commiter_id" => $user->id])->orderBy("created_at", "desc")->first();
        return [
            "id" => $user->id,
            "role" => $user->role,
            "role_name" => $user->role == 1 ? "Admin" : "User",
            "account_name" => $user->account_name,
            "email" => $user->email,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "full_name" => $user->getFullName(),
            "img" => is_null($user->img) ? Config::get('custom.user_default_image') : "storage/assets/" . $this->img,
            "created_at" => Carbon::parse($user->created_at)->format('Y-m-d H:i'),
            "number_of_contributions" => Commit::where(["group_id" => $this->group_id, "commiter_id" => $user->id])->count(),
            "last_contribution_at" => $lastContribution ? Carbon::parse($lastContribution->created_at)->format('Y-m-d H:i:s') : "No contributions yet",
        ];
    }
}
