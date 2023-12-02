<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Traits\GeneralTrait;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\UserResource;
use App\Models\Group\GroupUser;

class AuthController extends Controller
{
    use GeneralTrait, HelperTrait;
    public function index(Request $request)
    {
        $limit = $request->limit ?? 25;
        $users = User::query();
        if ($text = $request->search) {
            $users->where(function ($query) use ($text) {
                $query->where('first_name', 'SOUNDS LIKE', '%' . strtolower($text) . '%')
                    ->orWhere('last_name', 'like', '%' . strtolower($text) . '%')
                    ->orWhere('account_name', 'like', '%' . strtolower($text) . '%')
                    ->orWhereRaw("concat(first_name,' ', last_name) SOUNDS LIKE '%$text%' ");
            });
        }
        $users = $users->paginate($limit);
        $data = [];
        $items = [];
        foreach ($users as $user) {
            $items[] = new UserResource($user);
        }
        $data["items"] = $items;
        $data = $this->setPaginationData($users, $data);
        return $this->success($data);
    }

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
            "user" => new UserResource($user),
        ];
        GroupUser::firstOrCreate([ // Add user to the public group
            "group_id" => 1,
            "user_id" => $user->id,
        ]);
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
                "user" => new UserResource($user),
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

    public function show(Request $request)
    {
        if (!$request->id) {
            $user = Cache::remember("user-" . auth()->id(), 3600 * 24, function () {
                return Auth::user();
            });
            return $this->success(new UserResource($user));
        }
        if (!$user = User::find($request->id)) return $this->fail("Not found!", 404);
        $user = Cache::remember("user-" . $request->id, 3600 * 24, function () use ($request) {
            return $user = User::find($request->id);
        });
        return $this->success(new UserResource($user));
    }

    public function update(UserRegisterRequest $request)
    {
        $user = $request->user();
        DB::beginTransaction();
        $request->first_name ? ($request->first_name != $user->first_name ? $user->first_name = $request->first_name : false) : false;
        $request->last_name ? ($request->last_name != $user->last_name ? $user->last_name = $request->last_name : false) : false;
        $request->account_name ? ($request->account_name != $user->account_name ? $user->account_name = $request->account_name : false) : false;
        $request->email ? ($request->email != $user->email ? $user->email = $request->email : false) : false;
        $request->password ? $user->password = Hash::make($request->password) : false;
        if ($request->hasFile('img')) {
            $path = $this->storeFile($request->img, 'users/' . $user->id . "/profile_images", "public/assets/", 'public/assets/' . $user->img);
            $user->img = $path;
        }
        $user->save();
        DB::commit();
        return $this->success(new UserResource($user), "Updated successfully!");
    }

    public function destroy(Request $request)
    {
        //
    }
}
