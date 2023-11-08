<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Traits\GeneralTrait;

class UserRegisterRequest extends FormRequest
{
    use GeneralTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "first_name" => ["required", "string", "between:2,50"],
            "last_name" => ["required", "string", "between:2,50"],
            "account_name" => ["required", "string", "between:6,100", "unique:users,account_name"],
            "password" => ["required", "string", "max:255", Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(), "confirmed"],
            "email" => ["required", "string", "email", "unique:users,email"],
            "img" => ["nullable", "file", "image", "max:1024", "dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000", "mimes:png,jpg,jpeg,gif"]
        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->fail($validator->errors()->first()));
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'account_name' => str_replace('@', '', $this->account_name),
        ]);
    }

    public function messages(): array
    {
        return [
            "account_name.unique" => "The account name is already in use."
        ];
    }
}
