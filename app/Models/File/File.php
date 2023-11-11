<?php

namespace App\Models\File;

use App\Models\Group\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $table = "files";
    protected $primaryKey = "id";
    protected $fillable = [
        'name',
        'description',
        'type',
        'reserved_by',
        'path',
        'file_key',
        'group_id',
        'created_by'
    ];

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trim($value);
    }

    //relations
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function reservedBy()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }
}
