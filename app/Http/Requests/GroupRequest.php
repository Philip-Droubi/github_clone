<?php

namespace App\Http\Requests;

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

    public function rules(): array
    {
        if ($this->user() && $this->method() == "PUT" && str_contains($this->path(), "api/groups/")) return $this->updateRule();
        if ($this->user() && $this->method() == "POST" && $this->path() == "api/groups") return $this->storeRules();
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
        $user = $this->user();
        return [
            "name" => ["required", "string", "between:2,50"],
            "desc" => ["nullable", "string", "max:255"],
            "users_list" => ["nullable", "array"],
            "users_list.*" => ["required", "exists:users,id"],
        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->fail($validator->errors()->first()));
    }
}
