<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


use App\ClienteNasajon;

use App\TransportadorNasajon;
use App\CepEndereco;
use App\CondicoesPagamentoWeb;
use App\NasajonEstabelecimento;
use App\PedidoPortal;
use App\ParametrosPedido;
use App\ProdutoEspecificacao;
use Auth;
use Carbon\Carbon;

class BookVirtualCarrinhoAdicionarRequest extends FormRequest
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
        $clienteObj = [];
		if(!empty($this->codigo_cliente)){
			$clienteObj = ClienteNasajon::where('codigo', $this->codigo_cliente)->where('bloqueado', 'false')->first();
		}
        if(isset($this->pedido)){
            $PedidoPortal = PedidoPortal::find($this->pedido);
            $pedido = $this->pedido;
        }else{
            $PedidoPortal = null;
            $pedido = 0;
        }

        
        
        return [
            'id' => [
                'nullable',
                'required_if:completo,true',
                'exists:pedido'
            ],
            'estabelecimento' =>[
                'required',
                Rule::in(['01', '02', '03', '04', '05', '06', '07', '08'])
            ],
            'tipo_venda' => [
                'required',
                Rule::in(["pronta_entrega_venda", "pronta_entrega_triangular"]),
                function($attribute, $value, $fail) use ($clienteObj){
                    if(!empty($this->tipo_venda) && !empty($this->nome_cliente)){
                        if($this->tipo_venda === 'pronta_entrega_triangular'){
							if(!is_null($clienteObj)){
								if ($clienteObj->inscricaoestadual == 'ISENTO' || intval($clienteObj->indicadorinscricaoestadual) == 2 || intval($clienteObj->indicadorinscricaoestadual) == 9){
									return $fail("Cliente Isento, não é permitda venda triangular.");
								}
							}
                        }
                    }
                }
            ],
            'data_previsao_entrega' => [
                function($attribute, $value, $fail) {
                    $dias_integracao = 5;
                    $ParametrosPedidoObj = ParametrosPedido::where('estabelecimento', str_pad($this->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
                    if(!empty($ParametrosPedidoObj)){
                        $dias_integracao = $ParametrosPedidoObj->dias_integracao;
                    }
                    if(!empty($value)){
                        try{
                            $data_precisa = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                        }catch (\Exception $e){
                            return $fail(__('validation.date_format', ['attribute' => 'Data', 'format' => 'DD/MM/YYYY']));
                        }
                        $data_hoje = Carbon::today()->setTime(0,0,0);
                        
                        if ($this->cartao == 'true' && $data_hoje->lt($data_precisa)){
                            return $fail("Pedidos cartão devem ser pronta entrega");
                        }
                        if (strtotime(str_replace("/", '-', $value)) > strtotime("+{$dias_integracao} days")){
                            return $fail("Pedidos de pronta entrega devem ter a entrega prevista em até {$dias_integracao} dias.");
                        }
                    }
                    if (!empty($value) && strtotime(str_replace("/", '-', $value)) < strtotime(date('Y-m-d'))){
                        return $fail("Pedidos devem ter uma data de previsão de entrega igual ou maior que hoje.");
                    }
                }
            ],
            'codigo_cliente' => [
                'required', 
                function($attribute, $value, $fail) use ($clienteObj, $PedidoPortal){

                    $codigo_cliente_balcao = ['0000010069999'];

                    if (!in_array(intval($this->estabelecimento), [5, 7, 8]) && in_array($value, $codigo_cliente_balcao)){
                        return $fail("Não é permitido venda para cliente balcão");
                    }

                    if(empty($clienteObj)){
                        return $fail("Este cliente não está cadastrado, favor verificar com o setor responsável");
                    }

                    if (in_array(preg_replace('/[_\-\/\.]/','', $clienteObj->cpf_cnpj), ['05075884000167'])){
                        return $fail("Não é permitido venda para Tecidos MN.");
                    }

                    if(empty(Auth::user()->codigo_representante) && (empty(trim($clienteObj->vendedor_codigo)) || is_null($clienteObj->vendedor_codigo))){
                        return $fail("Ajustar código do vendedor no cliente");
                    }

                    if(
                        !in_array(str_replace('.', '', explode('/', $clienteObj->cpf_cnpj)[0]), ['05075884', '06311274']) &&
                        empty(Auth::user()->codigo_representante) &&
                        (
                            !empty(trim($clienteObj->vendedor_codigo)) &&
                            $clienteObj->vendedor_codigo === '001'
                        )
                    ){
                        return $fail("Cliente sem vendedor valido para venda.<br />Ajuste o cadastro de cliente");
                    }

                    if(in_array(intval($this->estabelecimento), [3, 4]) && strlen($clienteObj->cpf_cnpj) == 14){
                        return $fail("Não é permitido venda para pessoa física. Para Rondônia e Tocantins");
                    }
                    $razao_cnpj_textil = '06311274';
                    $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $clienteObj->cpf_cnpj);
                    $raiz_cnpj = substr($raiz_cnpj, 0, 8);
					if($raiz_cnpj == $razao_cnpj_textil){
                    	$NasajonEstabelecimentoObj = NasajonEstabelecimento::where('codigo', str_pad($this->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
						if($NasajonEstabelecimentoObj->raizcnpj.''.$NasajonEstabelecimentoObj->ordemcnpj == preg_replace('/[_\-\/\.]/','', $clienteObj->cpf_cnpj)){
							return $fail("Não pode fazer nota de transferência para si mesma");
						}

						if(!in_array(intval($this->estabelecimento), [3, 4]) && $clienteObj->uf == 'RO'){
							return $fail("Rondônia não aceita transferência de São Paulo<br>Consulte a área fiscal");

						}
					}

					if($this->estabelecimento == 2 && $clienteObj->cpf_cnpj == '05.075.884/0002-48'){
						return $fail("Botelho não pode fazer nota de transferência para si mesma");
					}

					if($this->estabelecimento == 1 && $clienteObj->cpf_cnpj == '05.075.884/0001-67'){
						return $fail("Almirante Barroso não pode fazer nota de transferência para si mesma");
					}

                    if($this->estabelecimento == 6 && strlen(str_replace([' ', '-', '/', '.'], '', $clienteObj->cpf_cnpj)) == 11){
                        return $fail("Não é permitido venda para pessoa física");
                    }

                    if (empty($clienteObj->uf) && !in_array($value, $codigo_cliente_balcao)){
                        return $fail("O cadastro deste cliente está incompleto e não possui UF. Favor verificar com o setor responsável.");
                    }
                    if(in_array($value, $codigo_cliente_balcao) && Auth::user()->tipo_usuario_id == 12){
                        return $fail("Representante não pode fazer venda de balcão.");
                    }

                    if (empty(preg_replace('/[_-]/','',$clienteObj->cep)) && !in_array($value, $codigo_cliente_balcao)){
                        return $fail("O cadastro deste cliente está incompleto e não possui CEP. Favor verificar.");
                    }
                    else if(!empty(preg_replace('/[_-]/','',$clienteObj->cep)) && is_null(CepEndereco::whereRaw("substring(LPAD(cep::text, 8, '0'), 0, 6) = '" . substr(str_pad(preg_replace('/[_-]/','',$clienteObj->cep). 8, '0', STR_PAD_LEFT), 0, 5) . "'")->get())){
                        return $fail("O CEP deste cliente é inválido. Favor verificar." . substr(str_pad(preg_replace('/[_-]/','',$cliente->cep). 8, '0', STR_PAD_LEFT), 0, 5));
                    }
                    
                    if(isset($PedidoPortal)){
                        if($PedidoPortal->status_pedido == 3){
                            return $fail("Pedido aprovado, não poder ser editado");
                        }
                        if($PedidoPortal->status_pedido == 11){
                            return $fail("Pedido aguardando liberação de pagamento, não poder ser editado");
                        }
                        if(
                            (
                                $this->cartao == 'true' && 
                                $PedidoPortal->condicao_pagamento == $this->condicao_pagamento
                            )
                        ){
                            if(
                                $this->presencial == 'false'
                            ){
                                if(!empty($clienteObj->cliente_desde)){
                                    $dataHoje = Carbon::today()->subMonths(6);
                                    $cliente_desde = Carbon::parse($clienteObj->cliente_desde)->setTime(0,0,0);
                                    if($cliente_desde->gt($dataHoje)){
                                        return $fail("Pedido a distância não permitido para clientes com menos de 6 meses.");
                                    }
                                }
                                $PedidoPortal = PedidoPortal::where('cod_cliente', $clienteObj->codigo)->where('status_pedido', 3)->get();
                                if($PedidoPortal->isEmpty()){
                                    return $fail("Pedido a distância não permitido por ser a primeira compra.");
                                }
                                if(strlen($clienteObj->cpf_cnpj) == 14){
                                    return $fail("Pedido a distância não permitida para pessoa física.");
                                }
                            }
                        }
                    }

                    if (!empty($clienteObj->suframa_codigo) && !in_array(intval($this->estabelecimento), [3, 4]) && in_array($clienteObj->uf , ['RO', 'RR', 'AP', 'AC', 'AM'])){
                        return $fail("Não é permitido venda para cliente com código Suframa nos estabalecimentos 5, 7 e 8.");
                    }
                }

            ],
            'condicao_pagamento' => [
                function($attribute, $value, $fail) use ($clienteObj, $PedidoPortal){
                    $razao_cnpj_textil = '06311274';
                    if(empty($clienteObj) || is_null($clienteObj)){
                        return $fail("");
                    }
                    $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $clienteObj->cpf_cnpj);
                    $raiz_cnpj = substr($raiz_cnpj, 0, 8);

                    if(
                        (
                            in_array(str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) ||
                            $razao_cnpj_textil !== $raiz_cnpj
                        ) &&
                        empty($value)
                    ){
                        return $fail("O campo condição de pagamento é obrigatório.");
                    }
                    if(!empty($value)){
                        $CondicaoPagamento = CondicoesPagamentoWeb::with('clientes')->where('id', $value)->where('ativo', true)->first();
                        $CondicaoPagamento2 = CondicoesPagamentoWeb::with('clientes')->where('id', $value)->where('ativo', true);
                        if(empty($CondicaoPagamento)){
                            return $fail("O campo condição de pagamento selecionado é inválido.");
                        }elseif($CondicaoPagamento->has('clientes')){
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

                    if(isset($PedidoPortal)){
                        if($PedidoPortal->status_pedido == 9){
                            if(!empty($PedidoPortal->cielo)){
                                foreach($PedidoPortal->cielo as $cielo){
                                    if($cielo->cielo_status_id == 4){
                                        return $fail("Pedido foi pago, não poder ser editado"); 
                                    }
                                }
                            }
                        }
                    }
                }
            ],
            'transportadora' => [
                'required_if:adicionar_transportadora,true',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
                        }
                    }
                }
            ],
            'transportadora_tipo_frete' => [
                'required',
                Rule::in(['P', 'A', 'C', 'T', 'S'])
            ],
            'valor_frete' => [
                'nullable', 
                'required_if:transportadora_tipo_frete,C',
                function($attribute, $value, $fail) {
                    if($this->transportadora_tipo_frete == 'C'){
                        $numero_tratado = str_replace(",", ".", str_replace(".", "replace", $value));
                        if ($numero_tratado <= 0){
                            return $fail("O valor do frete deve ser maior que zero.");
                        }
                    }
                },
            ],
            'transportadora_redespacho' => [
                'nullable', 
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
                        }
                    }
                },
                'different:transportadora',
            ],
            'transportadora_redespacho_tipo_frete' => [
                'nullable',
                Rule::in(['P', 'A', 'C', 'T', 'S']),
                'required_with:transportadora_redespacho'
            ],
            'valor_frete_redespacho' => 'nullable|required_if:transportadora_redespacho_tipo_frete,C',
            'nome_contato' => 'nullable',
            'email_contato' => [
                function($attribute, $value, $fail) use ($clienteObj) {
                    $codigo_cliente_balcao = ['0000010069999'];
                    if(!in_array($this->codigo_cliente, $codigo_cliente_balcao)){
						if(!empty($clienteObj)){
							if(!in_array(str_replace('.', '', explode('/', $clienteObj->cpf_cnpj)[0]), ['05075884', '06311274'])){
								if(empty($value)){
									return $fail(__('validation.required', ['attribute' => 'e-mail']));
								}else if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
									return $fail(__('validation.email', ['attribute' => 'e-mail']));
								}
							}
						}
                    }
                }
            ],
            'observacao' => 'nullable',
            'valor_desconto' => 'nullable',
            'completo' => [
                Rule::in(['true', 'false'])
			],
            'cliente_telefone' => [
                function($attribute, $value, $fail) {
					if(
						$this->cartao == 'true' &&
						$this->presencial == 'false' &&
						empty($value)
					){
						return $fail(__('validation.required', ['attribute' => 'telefone do cliente']));
					}
				}
            ],
            'produto_codigo' => [
                'required',
                Rule::unique('pedido_item', 'cod_produto')
                ->where('pedido', $pedido)
                ->whereNull("deleted_at")
                ->ignore($this->id, 'id'),
                function($attribute, $value, $fail) use($PedidoPortal) {
                    if(isset($PedidoPortal)){
                        if($PedidoPortal->status_pedido == 3){
                            return $fail("Pedido aprovado, não poder ser editado");
                        }
                        if($PedidoPortal->status_pedido == 11){
                            return $fail("Pedido aguardando liberação de pagamento, não poder ser editado");
                        }
                        if($PedidoPortal->status_pedido == 9){
                            if(!empty($PedidoPortal->cielo)){
                                foreach($PedidoPortal->cielo as $cielo){
                                    if($cielo->cielo_status_id == 4){
                                        return $fail("Pedido foi pago, não poder ser editado"); 
                                    }
                                }
                            }
                        }
                        $clienteObj = $PedidoPortal->cliente;
                        $razao_cnpj_textil = '06311274';
                        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $clienteObj->cpf_cnpj);
                        $raiz_cnpj = substr($raiz_cnpj, 0, 8);
                        if($raiz_cnpj == $razao_cnpj_textil){
                            $ProdutoEspecificacaoObj = ProdutoEspecificacao::with(['ficha_tecnica'])->where('ativo', true)->where('codigo_produto', $value)->first();
                            if($ProdutoEspecificacaoObj->industrializado == true && empty($ProdutoEspecificacaoObj->ficha_tecnica)){
                                return $fail("Produto industrializado sem ficha técnica para transferência. Entre em contato com o setor responsável pelo cadastro");
                            }
                        }
                    }
                }
            ],
            'quantidade' => [
                'required',
                function($attribute, $value, $fail){
                    if(!is_float(str_replace(",", ".", $value)) && floatval(str_replace(",", ".", str_replace(".", "", $value))) < 0.01){
                        return $fail("Valor deve ser um número maior que zero");
                    }
                }
            ],
            'preco_unitario' => [
                'required', 
                function($attribute, $value, $fail){
                    if(!is_float(str_replace(",", ".", $value)) && floatval(str_replace(",", ".", str_replace(".", "", $value))) < 0.01){
                        return $fail("Valor deve ser um número maior que zero");
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return array(
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'estabelecimento.in' => __('validation.in', ['attribute' => 'Estabelecimento']),
            'data_previsao_entrega.required_if' => __('validation.required', ['attribute' => 'data de previsão de entrega']),
            'data_previsao_entrega.date_format' => __('validation.date_format', ['attribute' => 'data de previsão de entrega', 'format' => 'DD/MM/YYYY']),
            'codigo_cliente.required' => __('validation.required', ['attribute' => 'cliente']),
            'codigo_cliente.exists' => __('validation.exists', ['attribute' => 'cliente']),
            'condicao_pagamento.required' => __('validation.required', ['attribute' => 'condição de pagamento']),
            'condicao_pagamento.exists' => __('validation.exists', ['attribute' => 'condição de pagamento']),
            'transportadora.required_if' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora_tipo_frete.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora.required_with' => __('validation.required', ['attribute' => 'tipo de frete de transportadora']),
            'transportadora_tipo_frete.in' => __('validation.in', ['attribute' => 'tipo de frete de transportadora']),
            'transportadora_redespacho.exists' => __('validation.exists', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho.different' => __('validation.different', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho.required_with' => __('validation.exists', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho_tipo_frete.required_with' => "É necessário selecionar um tipo de frete para o redespacho",
            'transportadora_redespacho_tipo_frete.in' => __('validation.in', ['attribute' => 'tipo de frete de transportadora de redespacho']),
            'valor_frete.required_if' => __('validation.required', ['attribute' => 'valor de frete']),
            'valor_frete_redespacho.required_if' => __('validation.required', ['attribute' => 'valor de frete de redespacho']),
            'produto_codigo.unique' => 'Este produto já está no pedido. Para modificar seus valores, edite-o.',
            'preco_unitario.required' =>  __('validation.required', ['attribute' => 'Preco Venda']),
            'quantidade.required' =>  __('validation.required', ['attribute' => 'Quantidade']),
            'preco_unitario.min' => __('validation.min.numeric', ['attribute' => 'Preco', 'min' => '0'])
        );
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'errors' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
