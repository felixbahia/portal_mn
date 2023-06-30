<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PedidoSalvarRequest extends FormRequest
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
            'id' => 'nullable',
            'tipo_venda' => [
                'required',
                Rule::in(["venda", "triangular", "isento"])
            ],
            'estabelecimento' =>[
                'required',
                Rule::in(['01', '02', '03', '04'])
            ],
            'pedido_futuro' => [
                'required',
                Rule::in(['true', 'false'])
            ],
            'data_previsao_entrega' => 'required_if:pedido_futuro,true|nullable|date_format:d/m/Y',
            'codigo_cliente' => [
                'required', 
                'exists:srv_prologos.TBCAD1,CODCAD',
                function($attribute, $value, $fail) {
                    if($this->tipo_venda == 'isento'){

                        $cliente = Cliente::find($attribute);

                        if ($cliente->IEST != 'ISENTO'){
                            return $fail("O cadastro deste cliente não permite venda isenta. Favor verificar.");
                        }
                    }
                }
            ],
            'condicao_pagamento' => [
                'required',
                Rule::exists('condicoes_pagamento_web', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');
                })
            ],
            'transportadora' => 'required|exists:srv_prologos.TBTRA1,CODTRAN',
            'transportadora_tipo_frete' => [
                'required',
                Rule::in(['P', 'A', 'C', 'T', 'S'])
            ],
            'transportadora_redespacho' => 'nullable|exists:srv_prologos.TBTRA1,CODTRAN',
            'transportadora_redespacho_tipo_frete' => [
                'nullable',
                'required_with:transportadora_redespacho',
                Rule::in(['P', 'A', 'C', 'T', 'S'])
            ],
            'nome_contato' => 'nullable',
            'email_contato' => 'nullable',
            'valor_frete' => 'nullable|required_if:transportadora_tipo_frete,C',
            'valor_frete_redespacho' => 'nullable|required_if:transportadora_redespacho_tipo_frete,C',
            'observacao' => 'nullable',
            'codigo_cliente_conta_e_ordem' => [
                'nullable',
                'required_if:tipo_venda,triangular',
                function($attribute, $value, $fail) {
                    if($value === $this->codigo_cliente){
                        return $fail("Cliente por conta e ordem não pode ser o mesmo que o comprador.");
                    }
                }
            ],
            'valor_desconto' => 'nullable',
            'completo' => [
                'required',
                Rule::in(['true', 'false'])
            ],
        ];
    }
    
    public function messages()
    {
        return array(
            'tipo_venda.required' => __('validation.required', ['attribute' => 'tipo de venda']),
            'tipo_venda.in' => __('validation.in', ['attribute' => 'tipo de venda']),
            'estabelecimento.required' => __('validation.required', ['attribute' => 'estabelecimento']),
            'estabelecimento.in' => __('validation.in', ['attribute' => 'estabelecimento']),
            'pedido_futuro.required' => __('validation.required', ['attribute' => 'pedido futuro']),
            'pedido_futuro.in' => __('validation.in', ['attribute' => 'pedido futuro']),
            'data_previsao_entrega.required_if' => __('validation.required', ['attribute' => 'data de previsão de entrega']),
            'data_previsao_entrega.date_format' => __('validation.date_format', ['attribute' => 'data de previsão de entrega', 'format' => 'DD/MM/YYYY']),
            'codigo_cliente.required' => __('validation.required', ['attribute' => 'cliente']),
            'codigo_cliente.exists' => __('validation.exists', ['attribute' => 'cliente']),
            'condicao_pagamento.required' => __('validation.required', ['attribute' => 'condição de pagamento']),
            'condicao_pagamento.exists' => __('validation.exists', ['attribute' => 'condição de pagamento']),
            'transportadora.required' => __('validation.required', ['attribute' => 'transportadora']),
            'transportadora.exists' => __('validation.exists', ['attribute' => 'transportadora']),
            'transportadora_tipo_frete.required' => __('validation.required', ['attribute' => 'tipo de frete de transportadora']),
            'transportadora_tipo_frete.in' => __('validation.in', ['attribute' => 'tipo de frete de transportadora']),
            'transportadora_redespacho.exists' => __('validation.exists', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho_tipo_frete.required_with' => __('validation.required', ['attribute' => 'tipo de frete de transportadora de redespacho']),
            'transportadora_redespacho_tipo_frete.in' => __('validation.in', ['attribute' => 'tipo de frete de transportadora de redespacho']),
            'valor_frete.required_if' => __('validation.required', ['attribute' => 'valor de frete']),
            'valor_frete_redespacho.required_if' => __('validation.required', ['attribute' => 'valor de frete de redespacho']),
            'codigo_cliente_conta_e_ordem.required_if' => __('validation.required', ['attribute' => 'cliente por conta e ordem']),
        );
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
            "request" => $this->camposRequest(),
            "response" => new \stdClass()
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }

    private function camposRequest(){
        $campos = $this->all();
        $campos = $this->parserValueNull($campos);
        return $campos;
    }
    private function parserValueNull($campos){
        foreach ($campos as $key => $value) {
            if(is_array($value)){
                $campos[$key] = $this->parserValueNull($value);
            }else if(empty($value)){
                $campos[$key] = '';
            }
        }
        return $campos;
    }
}
