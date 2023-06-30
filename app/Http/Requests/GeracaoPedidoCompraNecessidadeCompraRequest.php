<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\CondicoesPagamentoWeb;
use App\FornecedorNasajon;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class GeracaoPedidoCompraNecessidadeCompraRequest extends FormRequest
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
            'estabelecimento' => [
                'required',
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
                            return $fail('Condição de Pagamento não encontrada.');
                        }
                    }
                }
            ],
            'observacao_nota' => [
                'max:240',
            ],
            'data_previsao_entrega' =>[
                'date_format:d/m/Y',
                'max:20',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $data_atual = Carbon::now()->setTime(0,0,0);
                        $data_expiracao = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                        if($data_atual > $data_expiracao){
                            return $fail('Data informada menor que atual.');
                        }
                    }
                },
            ],
            'fornecedor_cnpj_cpf' =>[
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value) && $this->tipo === 'pedido'){
                        $query = FornecedorNasajon::select();
                        $query->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($value));
            
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Fornecedor']));
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'condicao_pagamento.required' => __('validation.required', ['attribute' => 'Condição de Pagamento']),
            'observacao_nota.max' => __('validation.max', ['attribute' => 'Observação']),
            'data_previsao_entrega.max' => __('validation.max', ['attribute' => 'Data de Expiração']),
            'data_previsao_entrega.date_format' => __('validation.date_format', ['attribute' => 'Data de Expiração', 'format' => 'DD/MM/AAAA']),
            'fornecedor_cnpj_cpf.required' => __('validation.required', ['attribute' => 'Fornecedor']),
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
        throw new HttpResponseException(response()->json($error, 403));
    }
}
