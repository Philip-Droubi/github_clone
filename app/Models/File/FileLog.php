<?php

namespace App\Models\File;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileLog extends Model
{
    use HasFactory;
    protected $table = "files_log";
    protected $primaryKey = "id";
    protected $fillable = [
        'action',
        'additional_info',
        'importance',
        'file_id',
        'user_id',
    ];

    public function setDescriptionAttribute($value)
    {
        $this->attributes['additional_info'] = trim($value);
    }

    //relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function setActionAttribute($value)
    {
        $this->attributes['action'] = strtolower(trim($value));
    }


    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
