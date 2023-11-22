<?php

namespace App\Models\Group;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
    use HasFactory;
    protected $table = "commits";
    protected $primaryKey = "id";
    protected $fillable = [
        'action',
        'description',
        'group_id',
        'commiter_id'
    ];

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trim($value);
    }

    //relations
    public function commiter()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function files()
    {
        return $this->hasMany(CommitFile::class, "commit_id");
    }
}
