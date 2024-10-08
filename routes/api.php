<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\Files\FileLogController;
use App\Http\Controllers\Groups\GroupLogController;
use App\Http\Controllers\Groups\GroupController;
use Illuminate\Support\Facades\Route;

// No Auth Needed
Route::group(['middleware' => ['bots']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post("/register", "register");
        Route::post("/login", "login");
    });
});

// Auth Needed
Route::group(['middleware' => ['auth:sanctum', 'lastseen']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get("/logout", "logout");
        Route::get("/logoutAll", "logoutAllDevices");
        Route::get("/users", "index");
        Route::get("/profile/{id?}", "show");
        Route::put("/update_profile", "update");
    });
    Route::prefix("groups")->controller(GroupController::class)->group(function () {
        Route::get("/", "index")->middleware('admin');
        Route::get("/user_groups/{id?}", "getGroupsByID"); //user id
        Route::get("/group_contributers/{id}", "getGroupContributers"); //group key
        Route::get("/{group_key}", "show"); // group key
        Route::post("/", "store");
        Route::put("/{group_key}", "update");
        Route::delete("/{group_key}", "destroy");
        Route::get("/clone/{group_key}", "cloneGroup");
    });
    Route::prefix("files")->controller(FileController::class)->group(function () {
        Route::get("/", "index")->middleware('admin');
        Route::post("/", "store")->middleware('max_files');
        Route::delete("/{id}", "destroy");
        Route::get("/group_files/{id}", "getFilesByGroupID");
        Route::get("/user_files/{id?}", "getUserFiles");
        Route::post("/check", "checkIn");
        Route::post("/replace", "replaceFile");
        Route::get("/checkout/{file_key}", "checkout");
        Route::post("/download", "downloadFiles");
    });
    Route::prefix("groups_log")->controller(GroupLogController::class)->group(function () {
        Route::get("/", "index")->middleware('admin');
    });
    Route::prefix("files_log")->controller(FileLogController::class)->group(function () {
        Route::get("/", "index")->middleware('admin');
    });
});
