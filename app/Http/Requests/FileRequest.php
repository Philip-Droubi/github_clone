<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\File;
use App\Traits\GeneralTrait;

class FileRequest extends FormRequest
{
    use GeneralTrait;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->user() && $this->method() == "POST" && $this->path() == "api/files/check") return $this->checkInRule();
        // elseif ($this->user() && $this->method() == "POST" && $this->path() == "api/files") return $this->storeRules();
        return $this->storeRules();
    }

    public function storeRules(): array
    {
        return [
            "commit" => ["nullable", "string", "max:255"],
            "group_key" => ["required", "exists:groups,group_key"],
            "files_array" => ["required", "array", "max:20"],
            "files_array.*" => ["file", "max:10240"], //At most 10MB of data at once
            "files_desc" => ["nullable", "array", "max:20"],
            "files_desc.*" => ["nullable", "string", "max:100"],
        ];
    }

    public function checkInRule(): array
    {
        return [
            "files_keys" => ["required", "array", "max:40"],
            "files_keys.*" => ['required', 'string', 'exists:files,file_key'], //At most 10MB of data at once
        ];
    }

    // public function updateRule(): array
    // {
    //     $user = $this->user();
    //     return [
    //         "desc" => ["nullable", "string", "max:255"],
    //         "files" => ["nullable", "array"],
    //         "files.*" => ["file", File::atMost(10240)],
    //         "deleted_files" => ["nullable", "array"],
    //         "deleted_files.*" => ["required", "exists:files,file_key"],
    //     ];
    // }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->fail($validator->errors()->first()));
    }

    public function messages()
    {
        return [
            "files_keys.*.exists" => "File not found"
        ];
    }
}
