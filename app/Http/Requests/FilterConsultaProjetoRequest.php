<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FilterConsultaProjetoRequest extends FormRequest
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
            'num_projeto' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'nome_projeto' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'estabelecimento' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'representante' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'faccao' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'data_inicio' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'data_fim' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'estado' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'linha' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
            'cliente' =>[
                'required_without_all:num_projeto,nome_projeto,estabelecimento,representante,faccao,data_inicio,data_fim,estado,linha,cliente',
            ],
        ];
    }

    public function messages() {
        return [
            'num_projeto.required_without_all' => __('validation.required', ['attribute' => 'Número Projeto']),
            'nome_projeto.required_without_all' => __('validation.required', ['attribute' => 'Nome Projeto']),
            'estabelecimento.required_without_all' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'representante.required_without_all' => __('validation.required', ['attribute' => 'Representante']),
            'faccao.required_without_all' => __('validation.required', ['attribute' => 'Facção']),
            'data_inicio.required_without_all' => __('validation.required', ['attribute' => 'Data Início']),
            'data_fim.required_without_all' => __('validation.required', ['attribute' => 'Data Fim']),
            'estado.required_without_all' => __('validation.required', ['attribute' => 'Status']),
            'linha.required_without_all' => __('validation.required', ['attribute' => 'Linha']),
            'cliente.required_without_all' => __('validation.required', ['attribute' => 'Cliente']),
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
