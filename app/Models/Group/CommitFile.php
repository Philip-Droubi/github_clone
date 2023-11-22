<?php

namespace App\Models\Group;

use App\Models\File\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommitFile extends Model
{
    use HasFactory;
    protected $table = "commit_files";
    protected $primaryKey = "id";
    protected $fillable = [
        'commit_id',
        'file_id'
    ];

    //relations
    public function commit()
    {
        return $this->belongsTo(Commit::class, 'commit_id');
    }

    public function files()
    {
        return $this->belongsTo(File::class, "file_id");
    }
}
