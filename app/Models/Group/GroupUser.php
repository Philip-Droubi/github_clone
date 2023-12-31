<?php

namespace App\Models\Group;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    use HasFactory;
    protected $table = "group_users";
    protected $primaryKey = "id";
    protected $fillable = [
        'group_id',
        'user_id',
        'removed_at',
    ];

    //relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group()
    {
        return $this->hasMany(Group::class, 'group_id');
    }
}
