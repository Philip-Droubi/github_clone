<?php

namespace App\Models\Group;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLog extends Model
{
    use HasFactory;
    protected $table = "groups_log";
    protected $primaryKey = "id";
    protected $fillable = [
        'action',
        'additional_info',
        'importance',
        'group_id',
        'user_id',
    ];

    public function setDescriptionAttribute($value)
    {
        $this->attributes['additional_info'] = trim($value);
    }
    public function setActionAttribute($value)
    {
        $this->attributes['action'] = strtolower(trim($value));
    }

    //relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
