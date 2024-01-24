<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Default User Profile Image
    |--------------------------------------------------------------------------
    | This value is the default user profile image of your application.
    |
    */

    "user_default_image" => "storage/assets/defaults/default_user.jpg",

    /*
    |--------------------------------------------------------------------------
    | Group Unique Key Length
    |--------------------------------------------------------------------------
    | This value is used to define the length of the random key genereted for each group.
    | Notice that changing the key length will not effect groups
    | generated before your edit.
    |
    */

    "group_key_length" => 32,

    /*
    |--------------------------------------------------------------------------
    | File Unique Key Length
    |--------------------------------------------------------------------------
    | This value is used to define the length of the random key genereted for each File.
    | Notice that changing the key length will not effect Files
    | generated before your edit.
    |
    */

    "file_key_length" => 32,

    /*
    |--------------------------------------------------------------------------
    | Private File Storage Path
    |--------------------------------------------------------------------------
    | This value is used to define the folder where files should be stored as private files.
    | Notice that changing the path will not effect files already stored
    | in the system.
    |
    */

    "private_path" => "private",


    /*
    |--------------------------------------------------------------------------
    | Max File Per User
    |--------------------------------------------------------------------------
    | This value is used to define the number of files user can upload on server.
    | Notice that changing the path will not delete files already stored
    | in the system.
    |
    */

    "max_files_per_user" => 500,
];