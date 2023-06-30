<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PrecoBaseCalculoRequest extends FormRequest
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
            'preco_final' => [
                'required'
            ],
            'origem' => [
                'required'
            ],
            'estado' => [
                'required'
            ],
            'frete' => [
                'required'
            ],
            'tipo_cliente' => [
                'required'
            ]
        ];
    }

    public function messages()
    {
        return [
            'preco_final.required' => __('validation.required', ['attribute' => 'Preço Final']),
            'origem.required' => __('validation.required', ['attribute' => 'Origem']),
            'estado.required' => __('validation.required', ['attribute' => 'Destino']),
            'prazo_medio.required' => __('validation.required', ['attribute' => 'Prazo Médio']),
            'frete.required' => __('validation.required', ['attribute' => 'Frete']),
            'tipo_cliente.required' => __('validation.required', ['attribute' => 'Tipo Cliente']),
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
