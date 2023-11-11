<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Groups\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// No Auth Needed
Route::group(['middleware' => ['try_catch', 'json', 'bots', 'apikey', 'xss']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post("/register", "register");
        Route::post("/login", "login");
    });
});

// Auth Needed
Route::group(['middleware' => ['try_catch', 'auth:sanctum', 'json', 'apikey', 'xss', 'lastseen']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get("/logout", "logout");
        Route::get("/logoutAll", "logoutAllDevices");
        Route::get("/users", "index");
        Route::get("/profile/{id?}", "show");
        Route::put("/update_profile", "update");
    });
    Route::prefix("groups")->controller(GroupController::class)->group(function () {
        Route::get("/", "index");
        Route::get("/my_groups/{id?}", "getGroupsByID"); //user id
        Route::get("/{id}", "show"); // group id or group key
        Route::post("/", "store");
        Route::put("/", "update");
        Route::delete("/", "destroy");
    });
});
