<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "username" => 'required',
            "password" => 'required'
        ];
    }

    public function messages()
    {
        return [
            'username.required' => __('validation.required', ['attribute' => 'username']),
            'password.required' => __('validation.required', ['attribute' => 'parssword']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        $error = [
            "error" => [
                "error" => true,
                "msg" => [
                    "dev" => reset($errors)[0],
                    "user" => reset($errors)[0]
                ]
            ],
            "request" => $this->all(),
            "response" => new \stdClass()
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
