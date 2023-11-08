<?php

use App\Http\Controllers\AuthController;
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
Route::group(['middleware' => ['try_catch', 'auth:sanctum', 'json', 'bots', 'apikey', 'xss', 'lastseen']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get("/logout", "logout");
        Route::get("/logoutAll", "logoutAllDevices");
        Route::get("/profile/{id?}", "Profile");
    });
});
