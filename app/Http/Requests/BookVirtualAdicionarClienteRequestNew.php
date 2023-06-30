<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookVirtualAdicionarClienteRequestNew extends FormRequest
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
            'codigo_cliente' => [
                'required'
            ],
            'tipo_venda' => [
                'required'
            ],
            'transportadora_tipo_frete' => [
                'required',
            ],
            'condicao_pagamento' => [
                'required_without_all:condicao_pagamento_descr'
            ],
            'data_previsao_entrega' => [
                'required'
            ]
        ];
    }

    public function messages() {
        return [
            'codigo_cliente.required' => __('validation.required', ['attribute' => 'Cliente']),
            'transportadora_tipo_frete.required' => __('validation.required', ['attribute' => 'Tipo de Frete']),
            'condicao_pagamento.required_without_all' => __('validation.required', ['attribute' => 'Condição de pagamento']),
            'data_previsao_entrega.required' => __('validation.required', ['attribute' => 'Previsão de entrega']),
            'tipo_venda.required' => __('validation.required', ['attribute' => 'Tipo de venda'])
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
