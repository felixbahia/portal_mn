<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\TransportadorNasajon;
use App\ClienteNasajon;
use App\CondicoesPagamentoWeb;

use Illuminate\Support\Facades\DB;

class PedidoPortalEdicaoFuturaRequest extends FormRequest
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
            'id' => [
                'nullable',
                'exists:pedido'
            ],
            'transportadora' => [
                'required', 
                function($attribute, $value, $fail) {
                    if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                        return $fail("Transportadora não encontrado.");
                    }
                }
            ],
            'transportadora_redespacho' => [
                'nullable', 
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                            return $fail("Transportadora não encontrado.");
                        }
                    }
                },
                'different:transportadora',
            ],
            'nome_contato' => 'nullable',
            'email_contato' => 'nullable',
            'observacao' => 'nullable',
            'cliente_nome' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $query = ClienteNasajon::select();
                        $query->where(DB::raw('TRIM(CONCAT(nome,\' - \', cpf_cnpj))'), 'ILIKE', trim($value));
                        $query->where('bloqueado', false);
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Cliente']));
                        }
                    }
                }
            ],
            'condicao_pagamento' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->condicao_pagamento)){
                        $query = CondicoesPagamentoWeb::select();
                        $query->where('descricao', 'ilike', $this->condicao_pagamento);
                        $query->where('nasajon', true);
                        $query->where('ativo', true);
                        $result = $query->first();

                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Condição de Pagamento']));
                        }
                    }
                }
            ],
        ];
    }
    
    public function messages()
    {
        return array(
            'transportadora.required' => __('validation.required', ['attribute' => 'transportadora']),
            'transportadora.exists' => __('validation.exists', ['attribute' => 'transportadora']),
            'transportadora_redespacho.exists' => __('validation.exists', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho.different' => __('validation.different', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho.required_with' => __('validation.exists', ['attribute' => 'transportadora de redespacho']),
            'cliente_nome.required' => __('validation.required', ['attribute' => 'Cliente']),
            'condicao_pagamento.required' => __('validation.required', ['attribute' => 'Condição de Pagamento']),
        );
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
