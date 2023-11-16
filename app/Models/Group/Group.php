<?php

namespace App\Models\Group;

use App\Models\File\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $table = "groups";
    protected $primaryKey = "id";
    protected $fillable = [
        'name',
        'description',
        'group_key',
        'is_public',
        'created_by'
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trim($value);
    }

    //relations
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'group_id');
    }

    public function contributers()
    {
        return $this->hasMany(GroupUser::class, 'group_id');
    }
}
