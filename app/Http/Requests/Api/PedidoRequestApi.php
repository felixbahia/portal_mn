<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PedidoRequestApi extends FormRequest
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
            //
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
            "request" => new \stdClass(),
            "response" => new \stdClass()
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
