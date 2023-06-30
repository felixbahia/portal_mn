<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ComissaoApiRequest extends FormRequest
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
            "periodo_inicial" => 'required|date_format:d/m/Y',
            "periodo_final" => 'required|date_format:d/m/Y',
        ];
    }

    public function messages()
    {
        return [
            'periodo_inicial.required' => __('validation.required', ['attribute' => 'Periodo Inicial']),
            'periodo_inicial.date_format' => __('validation.date_format', ['attribute' => 'Periodo Inicial', 'format' => 'DD/MM/YYYY']),
            'periodo_final.required' => __('validation.required', ['attribute' => 'Periodo Final']),
            'periodo_final.date_format' => __('validation.date_format', ['attribute' => 'Periodo Final', 'format' => 'DD/MM/YYYY']),
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
