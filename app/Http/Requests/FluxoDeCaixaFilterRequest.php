<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FluxoDeCaixaFilterRequest extends FormRequest
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
            'data_inicio' => [
                'required',
                'date_format:d/m/Y'
            ],
            'data_fim' => [
                'required',
                'date_format:d/m/Y'
            ]

        ];
    }

    public function messages() {
        return [
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Início']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Início', 'format' => 'DD/MM/YYYY']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Fim']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Fim', 'format' => 'DD/MM/YYYY']),

        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => 'Campos inválidos', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
