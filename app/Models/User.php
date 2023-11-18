<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\File\File;
use App\Models\File\FileLog;
use App\Models\Group\Group;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role',
        'first_name',
        'last_name',
        'account_name',
        'email',
        'password',
        'last_seen',
        'img'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst(strtolower(trim($value)));
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucfirst(strtolower(trim($value)));
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function setAccountNameAttribute($value)
    {
        $this->attributes['account_name'] = strtolower(str_replace(['@', ' '], ['', '_'], trim($value)));
    }

    public function getFullName()
    {
        return $this->first_name . " " . $this->last_name;
    }

    //relations
    public function groups()
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'created_by');
    }

    public function filesReserved()
    {
        return $this->hasMany(File::class, 'reserved_by');
    }

    public function filesLog()
    {
        return $this->hasMany(FileLog::class, 'user_id');
    }
}
