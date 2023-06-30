<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ClienteNasajon;
use App\User;
use App\ClienteNovo;
use Carbon\Carbon;
use App\CondicoesPagamentoWeb;

use Illuminate\Support\Facades\DB;

class LancamentoProjetoAdicionarOnChangeRequest extends FormRequest
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
                function($attribute, $value, $fail){
                    if(!empty($this->codigo_cliente)){
                        $cliente = ClienteNasajon::where('cpf_cnpj', $this->codigo_cliente)->where('bloqueado', false)->first();
                        if(empty($cliente->vendedor_codigo)){
                            return $fail('Ajustar código do vendedor no cliente');
                        }
        
                        if(empty($cliente->uf)){
                            return $fail('O cadastro deste cliente está incompleto e não possui UF. Favor verificar com o setor responsável.');
                        }

                        if(empty($cliente->cidade)){
                            return $fail('O cadastro deste cliente está incompleto e não possui Cidade. Favor verificar com o setor responsável.');
                        }
                    }
                }     
            ],
            'condicao_pagamento' => [
                function($attribute, $value, $fail){
                    if(!empty($this->nome_cliente) && !empty($this->condicao_pagamento)){
                        $clienteObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($this->nome_cliente))->where('bloqueado', 'false')->first();
                        $CondicaoPagamento = CondicoesPagamentoWeb::with('clientes')->where('id', $value)->where('ativo', true)->first();
        
                        if(empty($CondicaoPagamento)){
                            return $fail("O campo condição de pagamento selecionado é inválido.");
                        }

                        $CondicaoPagamento2 = CondicoesPagamentoWeb::with('clientes')->where('id', $value)->where('ativo', true);
                        
                        if($CondicaoPagamento->has('clientes')){
                            if($CondicaoPagamento->clientes->isEmpty() === false){
                                $CondicaoPagamento2->whereHas('clientes', function($query) use ($clienteObj){
                                    $query->where('cliente', $clienteObj->codigo);
                                });
                                $value = $CondicaoPagamento2->first();
                                if(empty($value)){
                                    return $fail("O campo condição de pagamento selecionado é inválido.");
                                }
                            }
                        }
                    }

                    //     if(!empty($this->codigo_cliente)){
                    //         $codigo_cliente_balcao = ['0000010069999'];
                    //         if (!in_array($this->codigo_cliente, $codigo_cliente_balcao)){
                    //             $username = str_replace("/", "", str_replace("-", "", str_replace(".", "", $clienteObj->cpf_cnpj)));
            
                    //             $data_atual = Carbon::now();
                    //             $data_valendo_cliente_novo = Carbon::parse('2020-08-01');
                    //             $data_valendo_todos_clientes = Carbon::parse('2020-10-01');
            
                    //             $liberado = false;
                    //             if($data_atual->gte($data_valendo_todos_clientes)){
                    //                 $liberado = true;
                    //             }else if($data_atual->gte($data_valendo_cliente_novo)){
                    //                 $query_cliente_novo = ClienteNovo::select();
                    //                 $query_cliente_novo->where('cpf_cnpj', $this->codigo_cliente);
                    //                 $query_cliente_novo->where('created_at', '>=', $data_valendo_cliente_novo);
                    //                 $result_cliente_novo = $query_cliente_novo->first();
                    //                 if(!empty($result_cliente_novo)){
                    //                     $liberado = true;
                    //                 }
                    //             }
            
                    //             if($liberado){
                    //                 $query = User::select();
                    //                 $query->where('username', $username);
                    //                 $result = $query->first();
            
                    //                 if(empty($result)){
                    //                     if($CondicaoPagamento->media > 0){
                    //                         return $fail('O cliente não assinou o contrato de uso, só é permitido fazer venda sem prazo.');
                    //                     }
                    //                 }
            
                    //                 if(empty($result->contrato)){
                    //                     if($CondicaoPagamento->media > 0){
                    //                         return $fail('O cliente não assinou o contrato de uso, só é permitido fazer venda sem prazo.');
                    //                     }
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }
                }
            ],
            'nome_contato' => [
                'max:250'
            ],
            'email_contato' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        if(!filter_var($value, FILTER_VALIDATE_EMAIL)){ 
                            return $fail('O campo Email Contato deve ser um endereço de e-mail válido.');
                        }
                    }
                }
            ],
            'num_pedido' => [
                'max:30',
            ],
            'nome_projeto' => [
                'max:60',
            ],
        ];
    }

    public function messages()
    {
        return [
            'nome_contato.max' => __('validation.max', ['attribute' => 'Nome Contato']),
            'email_contato.max' => __('validation.max', ['attribute' => 'Email Contato']),
            'nome_projeto.max' => __('validation.max', ['attribute' => 'Nome do Projeto']),
            
            'email_contato.rfc' => __('validation.rfc', ['attribute' => 'Email Contato']),
            'email_contato.dns' => __('validation.dns', ['attribute' => 'Email Contato']),
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
