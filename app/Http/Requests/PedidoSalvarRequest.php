<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

use App\Cliente;
use App\ClienteNasajon;
use App\Transportador;
use App\TransportadorNasajon;
use App\CepEndereco;
use App\User;
use App\CondicoesPagamentoWeb;
use App\ClientePrePago;
use App\NasajonEstabelecimento;
use App\PedidoPortal;
use App\ClienteNovo;
use App\ParametrosPedido;
use App\GrupoEmpresarial;

use Auth;
use Carbon\Carbon;

class PedidoSalvarRequest extends FormRequest
{
	private $estabelecimento_prologos = [];
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
		if(!empty(trim($this->nome_cliente))){
			$clienteObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($this->nome_cliente))->where('bloqueado', 'false')->first();
		}
        return [
            'id' => [
                'nullable',
                'required_if:completo,true',
                'exists:pedido'
            ],
            'tipo_venda' => [
                'required',
                Rule::in(["pronta_entrega_venda", "pronta_entrega_triangular", "pedido_futuro_venda", "pedido_futuro_triangular", "pre_pago", "producao", "pedido_orgaopublico", 'pre_pago_triangular', 'pre_pago_futuro', 'pedido_pilotagem', 'remessa_faturamento', 'rj_x_sp', 'rj_x_sp_triangular','pre_pago_rj_x_sp', 'producao_triangular', 'rj_x_sp_futuro', 'rj_x_sp_triangular_futuro', 'pre_pago_rj_x_sp_futuro', 'pre_pago_producao', 'pre_pago_producao_triangular']),
                function($attribute, $value, $fail) use ($clienteObj){
                    if(!empty($this->tipo_venda) && !empty($this->nome_cliente)){
                        if($this->tipo_venda === 'pronta_entrega_triangular' ||  $this->tipo_venda === 'pedido_futuro_triangular'){
							if(!is_null($clienteObj)){
								if ($clienteObj->inscricaoestadual == 'ISENTO' || intval($clienteObj->indicadorinscricaoestadual) == 2 || intval($clienteObj->indicadorinscricaoestadual) == 9){
									return $fail("Cliente Isento, não é permitda venda triangular.");
								}
							}
                        }
                    }
                }
            ],
            'estabelecimento' =>[
                'required',
                Rule::in(['1', '2', '3', '4', '5', '6', '7', '8'])
            ],
            'data_previsao_entrega' => [
                function($attribute, $value, $fail) {
                    if(empty($value) && in_array($this->tipo_venda, ['pedido_futuro_venda','pedido_futuro_triangular', 'producao', 'pre_pago_futuro'])){
                        return $fail("O campo data de previsão de entrega é obrigatório.");
                    }
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
                        if (strtotime(str_replace("/", '-', $value)) > strtotime("+{$dias_integracao} days") && ($this->tipo_venda != 'pedido_futuro_venda' && $this->tipo_venda != 'pedido_futuro_triangular' && $this->tipo_venda != 'pre_pago_futuro' && $this->tipo_venda != 'producao' && $this->tipo_venda != 'producao_triangular' && $this->tipo_venda != 'rj_x_sp_futuro' && $this->tipo_venda != 'rj_x_sp_triangular_futuro' && $this->tipo_venda != 'pre_pago_rj_x_sp_futuro' && $this->tipo_venda != 'pre_pago_producao' && $this->tipo_venda != 'pre_pago_producao_triangular')){
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
                function($attribute, $value, $fail) use ($clienteObj){

                    $codigo_cliente_balcao = ['0000010069999']; 
                    $codigo_grupo_textil_mn = ['0716668790001', '0050758840001', '0050758840002', '0050758840003', '0063112740004', '0063112740005', '0063112740001', '0063112740002', '0063112740003', '06311274000420'];
                    
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

                    if(!empty(trim($clienteObj->vendedor_codigo)) && !in_array($value, $codigo_cliente_balcao) && !in_array($value, $codigo_grupo_textil_mn)){
                        if($clienteObj->vendedor_codigo == '001'){
                            return $fail("Ajustar código do vendedor no cliente");
                        }
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

                    if ($this->tipo_venda == 'pre_pago'){
                        $ClientePrePago = ClientePrePago::where('cpf_cnpj', $clienteObj->cpf_cnpj)->first();
                        if(empty($ClientePrePago)){
                            return $fail("Este cliente não está liberado tipo de venda Pré-pago.");
                        }
                    }

                    if ($this->tipo_venda == 'pedido_orgaopublico'){
                        if(!in_array($clienteObj->qualificacao, [1, 2, 3])){
                            return $fail("Qualificação de cliente não é de orgão publico");
                        }
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
                    
                    $PedidoPortal = PedidoPortal::with(['cliente'])->find($this->id);

                    if(!empty($PedidoPortal)){
                        if(Auth::user()->tipo_usuario_id === 12 && $PedidoPortal->cliente->vendedor_codigo != Auth::user()->codigo_representante){
                            return $fail("Ajustar código do vendedor no cliente.");
                        }

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
                                    $cpf_cnpj = $clienteObj->cpf_cnpj;

                                    if(strlen(trim($cpf_cnpj)) == 18){
                                        $cpf_cnpj = substr($cpf_cnpj, 0, 10);
                                    }
                            
                                    $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                                    ->where('raiz_cnpj', $cpf_cnpj)
                                    ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                                        $query->where('raiz_cnpj', $cpf_cnpj);
                                    })
                                    ->first();

                                    $dataHoje = Carbon::today()->subMonths(6);
                                    $cliente_desde = Carbon::parse($clienteObj->cliente_desde)->setTime(0,0,0);
                                    if($cliente_desde->gt($dataHoje)){
                                        $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

                                        if (!is_null($grupoEmpresarialObj)){
                                            $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                                                if(isset($grupoEmpresarialObj->participantes)){
                                                    foreach($grupoEmpresarialObj->participantes as $participante){
                                                        $query->orWhere('cpf_cnpj', 'ilike', $participante->raiz_cnpj . '%');
                                                    }
                                                    $grupo[] = $participante->raiz_cnpj;
                                                }
                                                $query->orWhere('cpf_cnpj', 'ilike', $grupoEmpresarialObj->raiz_cnpj . '%');
                                            });
                                        }

                                        $cliente_desde = Carbon::parse($clientesNasajonQuery->min('cliente_desde'))->setTime(0,0,0);

                                        
                                    }
                                }
                                if (!is_null($grupoEmpresarialObj)){
                                    $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');
                                    $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                                        if(isset($grupoEmpresarialObj->participantes)){
                                            foreach($grupoEmpresarialObj->participantes as $participante){
                                                $query->orWhere('cpf_cnpj', 'ilike', $participante->raiz_cnpj . '%');
                                            }
                                            $grupo[] = $participante->raiz_cnpj;
                                        }
                                        $query->orWhere('cpf_cnpj', 'ilike', $grupoEmpresarialObj->raiz_cnpj . '%');
                                    });
                                    $clientesNasajon = $clientesNasajonQuery->get();

                                    $PedidoPortal = PedidoPortal::whereIn('cod_cliente',  $clientesNasajon->pluck('codigo'))->where('status_pedido', 3)->get();
                                }else{
                                    $PedidoPortal = PedidoPortal::where('cod_cliente', $clienteObj->codigo)->where('status_pedido', 3)->get();
                                }

                                if(strlen($clienteObj->cpf_cnpj) == 14){
                                    return $fail("Pedido a distância não permitida para pessoa física.");
                                }
                            }
                        }
                    }

                    if (!empty($clienteObj->suframa_codigo) && !in_array(intval($this->estabelecimento), [3, 4]) && in_array($clienteObj->uf, ['RO', 'RR', 'AP', 'AC', 'AM'])){
                        return $fail("Não é permitido venda para cliente com código Suframa nos estabalecimentos 5, 7 e 8.");
                    }
                }

            ],
            'condicao_pagamento' => [
                function($attribute, $value, $fail) use ($clienteObj){
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
                        !in_array($this->tipo_venda,['pedido_pilotagem', 'remessa_faturamento']) &&
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

                    $PedidoPortal = PedidoPortal::find($this->id);
                    if(!empty($PedidoPortal)){
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

                    // if(!empty($this->codigo_cliente)){
                    //     $codigo_cliente_balcao = ['0000010069999'];
                    //     if (!in_array($this->codigo_cliente, $codigo_cliente_balcao)){
                    //         $username = str_replace("/", "", str_replace("-", "", str_replace(".", "", $clienteObj->cpf_cnpj)));
        
                    //         $data_atual = Carbon::now();
                    //         $data_valendo_cliente_novo = Carbon::parse('2020-08-01');
                    //         $data_valendo_todos_clientes = Carbon::parse('2020-10-01');
        
                    //         $liberado = false;
                    //         if($data_atual->gte($data_valendo_todos_clientes)){
                    //             $liberado = true;
                    //         }else if($data_atual->gte($data_valendo_cliente_novo)){
                    //             $query_cliente_novo = ClienteNovo::select();
                    //             $query_cliente_novo->where('cpf_cnpj', $this->codigo_cliente);
                    //             $query_cliente_novo->where('created_at', '>=', $data_valendo_cliente_novo);
                    //             $result_cliente_novo = $query_cliente_novo->first();
                    //             if(!empty($result_cliente_novo)){
                    //                 $liberado = true;
                    //             }
                    //         }
        
                    //         if($liberado){
                    //             $query = User::select();
                    //             $query->where('username', $username);
                    //             $result = $query->first();
        
                    //             if(empty($result)){
                    //                 if($CondicaoPagamento->media > 0){
                    //                     return $fail('O cliente não assinou o contrato de uso, só é permitido fazer venda sem prazo.');
                    //                 }
                    //             }
        
                    //             if(empty($result->contrato)){
                    //                 if($CondicaoPagamento->media > 0){
                    //                     return $fail('O cliente não assinou o contrato de uso, só é permitido fazer venda sem prazo.');
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }
                }
            ],
            'transportadora' => [
                'required', 
                function($attribute, $value, $fail) {

                    if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                        return $fail("Transportadora não encontrado.");
                    }

                    $estabelecimento = isset($this->estabelecimento) ? intval($this->estabelecimento) : 0;

                    if(!empty($estabelecimento)){
                        switch ($estabelecimento) {
                            case '3':
                                $origem = 'RO';
                                break;
                            case '4':
                                $origem = 'TO';
                                break;            
                            default:
                                $origem = 'SP';
                                break;
                        }
                    }else{
                        $origem = '';
                    }

                    if(empty($this->nome_cliente_conta_e_ordem)){
                        $cliente_busca = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($this->nome_cliente).'%')->first();
                    }else{
                        $cliente_busca = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($this->nome_cliente_conta_e_ordem).'%')->first();
                    }    
                    $cliente_uf = empty($cliente_busca)? '' : $cliente_busca->uf;

                    if($estabelecimento == 5 || $estabelecimento == 8){
                        $query = TransportadorNasajon::select('codigo','nome','cnpj')
                            ->with(['transportadoraEstabelecimento' => function($query) use ($estabelecimento, $cliente_uf){
                                $query->where('estabelecimento', str_pad($estabelecimento, 2, 0, STR_PAD_LEFT));
                                if(!empty($cliente_uf)){
                                    $query->where('uf_destino', $cliente_uf);
                                }
                            }])
                            ->whereIn('codigo', [$value, '0001', '1003', '1002'])
                            ->where('bloqueado', false)
                            ->get();
                        
                        $existe = false;
                        foreach ($query as $value){
                            $existe = true;	
                        }
                
                        if($existe == false){
                            return $fail("Transportadora não encontrada neste estabelecimento.");
                        }                            
                    }else {
                        $query = TransportadorNasajon::select()
                            ->with(['transportadoraEstabelecimento' => function($query) use ($estabelecimento, $cliente_uf){
                                $query->where('estabelecimento', str_pad($estabelecimento, 2, 0, STR_PAD_LEFT));
                                if(!empty($cliente_uf)){
                                    $query->where('uf_destino', $cliente_uf);
                                }
                            }])
                            ->whereIn('codigo', [$value, '0001', '1003', '1002'])
                            ->where('bloqueado', false)
                            ->get();

                        $existe = false;
                        foreach ($query as $value){
                            if(!empty($value->transportadora_estabelecimento)){
                                $existe = true;
                            }else if($value->estado == 'RJ'){
                                $existe = true;
                            }else if(in_array($value->codigo, ['0001', '1003', '1002'])){
                                $existe = true;
                            }		
                        }
                
                        if($existe == false){
                            return $fail("Transportadora não encontrada neste estabelecimento.");
                        }   
                    }
                }
            ],
            'transportadora_tipo_frete' => [
                'required',
                Rule::in(['P', 'A', 'C', 'T', 'S']),
                function($attribute, $value, $fail) {
                    if($this->transportadora_tipo_frete == 'S' && $this->transportadora != '0001'){
                        return $fail("Tipo de Frete inválido para transportadora ".$this->transportadora_nome.".</br>");
                    }
                },
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
                            return $fail("Transportadora não encontrado.");
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
            'nome_contato' => 'required',
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

                        if(substr_count($value, "tecidosmn.com.br") !== 0){
                            return $fail("O e-mail não pode ser da Tecidos MN.");
                        }
                    }
                }
            ],
            'observacao' => 'nullable',
            'codigo_cliente_conta_e_ordem' => [
                'nullable',
                'required_if:tipo_venda,pronta_entrega_triangular,pedido_futuro_triangular,pre_pago_triangular,rj_x_sp_triangular,rj_x_sp_triangular_futuro',
                function($attribute, $value, $fail) {

                    $codigo_cliente_balcao = ['0000010069999'];
                    if($value === $this->codigo_cliente){
                        return $fail("Cliente por conta e ordem não pode ser o mesmo que o comprador.");
                    }

                    $cliente = ClienteNasajon::where('codigo', $value)->first();
                    if(empty($cliente)){
                        return $fail("Cliente por conta e ordem não encontrado.");
                    }

                    if(in_array($value, $codigo_cliente_balcao) && Auth::user()->tipo_usuario_id == 12){
                        return $fail("Representante não pode fazer venda de balcão.");
                    }
                },
            ],
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
            ]
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
            'transportadora_redespacho.different' => __('validation.different', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho.required_with' => __('validation.exists', ['attribute' => 'transportadora de redespacho']),
            'transportadora_redespacho_tipo_frete.required_with' => "É necessário selecionar um tipo de frete para o redespacho",
            'transportadora_redespacho_tipo_frete.in' => __('validation.in', ['attribute' => 'tipo de frete de transportadora de redespacho']),
            'valor_frete.required_if' => __('validation.required', ['attribute' => 'valor de frete']),
            'valor_frete_redespacho.required_if' => __('validation.required', ['attribute' => 'valor de frete de redespacho']),
            'codigo_cliente_conta_e_ordem.required_if' => __('validation.required', ['attribute' => 'cliente por conta e ordem']),
            'codigo_cliente_conta_e_ordem.exists' => __('validation.exists', ['attribute' => 'cliente por conta e ordem']),
        );
    }

}
