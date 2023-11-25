<?php

namespace App\Http\Requests;

use App\Models\Group\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Traits\GeneralTrait;

class GroupRequest extends FormRequest
{
    use GeneralTrait;
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        return $this->merge([
            "group_key" => $this->group_key,
        ]);
    }

    public function rules(): array
    {
        if ($this->user() && $this->method() == "PUT" && str_contains($this->path(), "api/groups/")) return $this->updateRule();
        return $this->storeRules();
    }

    public function storeRules(): array
    {
        return [
            "name" => ["required", "string", "between:2,50"],
            "desc" => ["nullable", "string", "max:255"],
            "users_list" => ["nullable", "array"],
            "users_list.*" => ["required", "exists:users,id"],
        ];
    }

    public function updateRule(): array
    {
        $group = Group::where("group_key", $this->group_key)->first();
        $id = $group->id ?? null;
        return [
            "group_key" => ["required", "string", "exists:groups,group_key,created_by," . auth()->id()],
            "name" => ["required", "string", "between:2,50"],
            "desc" => ["nullable", "string", "max:255"],
            "users_list" => ["nullable", "array"],
            "users_list.*" => ["required", "exists:users,id"],
            "deleted_users_list" => ["nullable", "array"],
            "deleted_users_list.*" => ["required", "exists:group_users,user_id,group_id," . $id],
        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->fail($validator->errors()->first()));
    }

    public function messages()
    {
        return [
            "group_key.exists" => "Not found",
            "deleted_users_list.*.exists" => "User not found",
            "users_list.*.exists" => "User not found"
        ];
    }
}