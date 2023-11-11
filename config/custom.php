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
];
