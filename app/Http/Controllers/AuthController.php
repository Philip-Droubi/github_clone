<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Carbon\Carbon;

class AuthController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function register(UserRegisterRequest $request)
    {
        DB::beginTransaction();
        $user = User::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "account_name" => $request->account_name,
            "email" => $request->email,
            "role" => User::count() == 0 ? 1 : 2,
            "password" => Hash::make($request->password),
        ]);
        if ($request->hasFile('img')) {
            $path = $this->storeFile($request->img, 'users/' . $user->id . "/profile_images");
            $user->img = $path;
        }
        $user->last_seen = Carbon::now()->format("Y-m-d H:i:s");
        $user->save();
        $data =  [
            "token" => $user->createToken($user->id . $user->first_name . Str::random(32))->plainTextToken,
            ...$this->getUserProfileData($user),
        ];
        DB::commit();
        return $this->success($data, "Welcome " . $user->first_name . "!", 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->only('account_name', 'password'), [
            'account_name' => ['required', 'string', 'exists:users,account_name', 'max:100'],
            'password' => ['required', 'min:8', 'max:255', 'string'],
        ]);
        if ($validator->fails())
            return $this->fail($validator->errors()->first(), 400);
        if (Auth::attempt(['account_name' => $request->account_name, 'password' => $request->password])) {
            $user = $request->user();
            $data = [
                "token" => $user->createToken($user->id . $user->first_name . Str::random(32))->plainTextToken,
                ...$this->getUserProfileData($user),
            ];
            return $this->success($data, "Welcome back " . $user->first_name . "!");
        }
        return $this->fail("Invalid credentials", 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success([], "logged out");
    }

    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->success([], "logged out");
    }

    public function profile(Request $request)
    {
        if (!$request->id)
            return $this->success($this->getUserProfileData(Auth::user()));
        if (!$user = User::find($request->id)) return $this->fail("Not found!", 404);
        return $this->success($this->getUserProfileData($user));
    }

    public function getUserProfileData($user)
    {
        return [
            "id" => $user->id,
            "role" => $user->role,
            "role_name" => $user->role == 1 ? "Admin" : "User",
            "account_name" => $user->account_name,
            "email" => $user->email,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "img" => "storage/assets/" . ($user->img ?? "defaults/default_user.jpg"),
        ];
    }

    public function update(Request $request)
    {
        //
    }

    public function destroy(Request $request)
    {
        //
    }
}
