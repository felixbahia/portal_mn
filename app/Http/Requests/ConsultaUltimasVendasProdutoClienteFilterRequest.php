<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ConsultaUltimasVendasProdutoClienteFilterRequest extends FormRequest
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
            'grupo' => [
                'max:250'
            ],
            'descricao' => [
                'max:250'
            ],
            'codigo' => [
                'max:250'
            ],
            'subgrupo' => [
                'max:250'
            ],
            'cliente_nome' => [
                'required',
                'max:250'
            ],
            'data_inicio' => [
                'date_format:d/m/Y',
                'max:20'
            ],
            'data_fim' => [
                'date_format:d/m/Y',
                'after_or_equal:data_inicio',
                'max:20'
            ],
        ];
    }

    public function messages(){

        return [
            'cliente_nome.required' => __('validation.required', ['attribute' => 'Cliente']),
            'cliente_nome.max' => __('validation.max', ['attribute' => 'Cliente']),

            'codigo.max' => __('validation.max', ['attribute' => 'Código']),

            'descricao.max' => __('validation.max', ['attribute' => 'Descrição']),

            'grupo.max' => __('validation.max', ['attribute' => 'Grupo']),

            'subgrupo.max' => __('validation.max', ['attribute' => 'Subgrupo']),

            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Inicio']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Inicio', 'format' => 'DD/MM/YYYY']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Fim']),
            'data_fim.date_format' => __('validation.date_format', ['attribute' => 'Data Fim', 'format' => 'DD/MM/YYYY']),
            'data_fim.after_or_equal' => __('validation.after_or_equal', ['attribute' => 'Data Fim', 'date' => 'Data Inicio'])
        ];

    }
}
