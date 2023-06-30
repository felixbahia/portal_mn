<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;

use Illuminate\Http\Request;

use App\User;
use App\LancamentoProjeto;
use App\ClienteNasajon;
use App\FornecedorNasajon;
use App\StatusProjetoExibicao;
use App\HistoricoProjeto;
use App\LancamentoProjetoFaccao;

class AnaliseSinteticaProjetoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseSinteticaProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnaliseSinteticaProjeto');

        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
        $representantes = [];
        foreach ($representantes_busca as $key => $value) {
        	$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }

        return view('programs.analise_sintetica_projeto.index')->with(['representantes' => $representantes]);
    }

    public function filter(Request $request){
        $fields = $request->only('linha', 'representantes','cliente','faccao', 'data_inicio', 'data_fim', 'chegada_dias');

        $filtro = encrypt($fields);
        
        $data_hoje = Carbon::now()->setTime(0,0,0);

        $dados = [];

        $total = [
            'prazo' => 0,
            'atraso' => 0,
            'total' => 0
        ];

        $result = $this->queryBusca($fields);

        foreach($result as $projeto){
            if(empty($dados[$projeto->detalhes_status->status_projeto_exibicao_id])){
                $dados [$projeto->detalhes_status->status_projeto_exibicao_id] = [
                    'id' => encrypt($projeto->detalhes_status->status_projeto_exibicao_id),
                    'descricao' => $projeto->detalhes_status->status_exibicao->descricao,
                    'prazo' => 0,
                    'atraso' => 0,
                    'total' => 0
                ];
            }
            if(!empty($projeto->data_previsao_entrega)){
                if($projeto->status === 8){
                    $finalizacao_projeto = Carbon::createFromFormat('Y-m-d', $projeto->updated_at->format('Y-m-d'))->setTime(0,0,0);
                }else{
                    $finalizacao_projeto = ""; 
                }
                $data_previsao_entrega_projeto = Carbon::createFromFormat('Y-m-d', $projeto->data_previsao_entrega)->setTime(0,0,0);
                if(empty($finalizacao_projeto)){
                    if($data_hoje->lte($data_previsao_entrega_projeto)){
                        $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['prazo']++;
                        $total['prazo']++;
                    }else{
                        $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['atraso']++;
                        $total['atraso']++;
                    }
                }else{
                    if($data_previsao_entrega_projeto->gt($finalizacao_projeto)){
                        $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['prazo']++;
                        $total['prazo']++;
                    }else{
                        $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['atraso']++;
                        $total['atraso']++;
                    }
                }
                
                $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['total']++;
            }else{
                $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['prazo']++; 
                $total['prazo']++;
                $dados[$projeto->detalhes_status->status_projeto_exibicao_id]['total']++;
            }
        }

        $dados =$this->arrayZeroParaVazio($dados);

        $total['total'] = $result->count();

        $total = $this->arrayZeroParaVazio($total);

        $retorno = [ 
            'dados' => $dados,
            'total' => $total,
            'filtro' => $filtro
        ];

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function dialog(Request $request){
        $fields = $request->only('id','tipo', 'filtro');

        try{
            $id_status_exibicao = decrypt($fields['id']);
            $filtro = decrypt($fields['filtro']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $filtro['id_status_exibicao'] = $id_status_exibicao;
        $filtro['tipo'] = $fields['tipo'];

        $result = $this->queryBusca($filtro);

        $linhas = [];
        $coluna_producao = false;
        foreach($result as $projeto){
            $query = HistoricoProjeto::select('created_at');
            $query->where('lancamento_projetos_id', $projeto->id);
            $query->whereIn('natureza', ['envio_faccao', 'em_producao', 'modificacao_status']);
            $query->whereRaw('case 
                                when natureza ilike \'modificacao_status\' then 
                                    (motivo ilike \'%Status Novo: 5%\' or motivo ilike \'%Status Novo: 6%\')
                                else
                                    natureza = natureza
                                end');
            $query->min('created_at');
            $result_data_producao = $query->first();
        
            $dias_em_producao = "";
            if(!empty($result_data_producao)){
                $coluna_producao = true;
                $dias_em_producao = Carbon::CreateFromFormat("Y-m-d", $result_data_producao->created_at->format('Y-m-d'))->setTime(0,0,0);
                if($projeto->status === 8){
                    $termino_da_producao = Carbon::CreateFromFormat("Y-m-d", $projeto->updated_at->format('Y-m-d'))->setTime(0,0,0);
                    $dias_em_producao = $dias_em_producao->diffInDays($termino_da_producao);
                }else{
                    $dia_atual = Carbon::now()->setTime(0,0,0);
                    $dias_em_producao = $dias_em_producao->diffInDays($dia_atual);
                }
            }

            $query_prorrogacao = LancamentoProjetoFaccao::select();
            $query_prorrogacao->where('lancamento_projetos_id', $projeto->id);
            $query_prorrogacao->max('data_previsao_entrega');
            $result_prorrogacao = $query_prorrogacao->first();

            $linhas[$projeto->id] = [
                'num_projeto' => $projeto->id,
                'projeto' => empty($projeto->nome_projeto)? '' : $projeto->nome_projeto,
                'cliente' => empty($projeto->cliente)? '' : $projeto->cliente->nome,
                'linha' => empty($projeto->linha)? '' : $projeto->linha,
                'valor' => empty($projeto->valor_total_pedido)? '' : parserValor($projeto->valor_total_pedido),
                'data_inicio_projeto' => parserData($projeto->created_at->format('Y-m-d')),
                'data_previsao_entrega' => empty($projeto->data_previsao_entrega)? '' : parserData($projeto->data_previsao_entrega),
                'dias_em_producao' => $dias_em_producao,
                'data_finalizacao' => parserData($projeto->updated_at->format('Y-m-d')),
                'prorrogacao' => empty($result_prorrogacao->data_previsao_entrega)? '' : ($result_prorrogacao->data_previsao_entrega > $projeto->data_previsao_entrega)? parserData($result_prorrogacao->data_previsao_entrega) : '',
                'status' => $projeto->status,
            ];
        }
        asort($linhas);
        return view('programs.analise_sintetica_projeto.modal.dialog')->with(['linhas' => $linhas, 'status' => $id_status_exibicao, 'coluna_producao' => $coluna_producao]);
    }

    public function queryBusca($filtros){
        $query = LancamentoProjeto::select();
        $query->whereNotIn('status', [9]);

        $query->with(['detalhes_status']);

        if (Auth::user()->tipo_usuario_id == 12 || Auth::user()->tipo_usuario_id == 16){
            $query->where('users_codigo_representante', Auth::user()->codigo_representante);
        }

        if(!empty($filtros['id_status_exibicao'])){
            $query->whereHas('detalhes_status', function($query) use($filtros){
                $query->where('status_projeto_exibicao_id', $filtros['id_status_exibicao']);
            });
        }
        
        if(!empty($filtros['linha'])){
            $query->where('linha', 'ilike', '%'.$filtros['linha'].'%');
        }
        if(!empty($filtros['representantes'])){
            $query->where('users_codigo_representante', 'ilike', $filtros['representantes']);
        }
        if(!empty($filtros['cliente'])){
            $query->where(function($query) use($filtros){
                $cliente_busca = ClienteNasajon::select('cpf_cnpj')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($filtros['cliente']).'%\'')->get();
                $query->WhereIn('cliente_codigo', $cliente_busca->pluck('cpf_cnpj'));
            });
        }
        
        $query->whereHas('faccoes', function($query) use($filtros){
            $query->whereHas('faccao', function($query) use($filtros){
                if(!empty($filtros['faccao'])){
                    $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf');
                    $fornecedor_busca->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($filtros['faccao']).'%\'');
                    $fornecedor_busca = $fornecedor_busca->get();

                    $query->whereIn('cod_fornecedor', $fornecedor_busca->pluck('cnpj_cpf'));
                }    
            });
        });

        if(!empty($filtros['tipo'])){
            $agora_carbon = Carbon::now()->setTime(0, 0, 0);
            switch ($filtros['tipo']) {
                case 'prazo':
                    if($filtros['id_status_exibicao'] === 8){
                        $query->where(function($query) use($agora_carbon){
                            $query->orWhereRaw('data_previsao_entrega >= updated_at');
                            $query->orWhereNull('data_previsao_entrega');
                        });
                    }else{
                        $query->where(function($query) use($agora_carbon){
                            $query->orWhere('data_previsao_entrega', '>=', $agora_carbon);
                            $query->orWhereNull('data_previsao_entrega');
                        });
                    }
                    break;
                case 'atraso':
                    if($filtros['id_status_exibicao'] === 8){
                        $query->whereRaw('data_previsao_entrega < updated_at');
                    }else{
                        $query->where('data_previsao_entrega', '<', $agora_carbon);
                    }
                    break;
            }
        } 

        if(!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $filtros['data_inicio']);
            $data_final = Carbon::CreateFromFormat("d/m/Y", $filtros['data_fim']);
            $query->whereBetween('data_previsao_entrega', [$data_inicial, $data_final]);
        }else if(!empty($filtros['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $filtros['data_inicio']);
            $query->where('data_previsao_entrega', '>=', $data_inicial);
        }else if(!empty($filtros['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $filtros['data_fim']);
            $query->where('data_previsao_entrega', '<=', $data_final);
        }

        if(!empty($filtros['chegada_dias'])){
            $dias = intval($filtros['chegada_dias']);
            $dia_final = Carbon::now()->setTime(0,0,0)->addDays($dias);
            $dia_atual = Carbon::now()->setTime(0,0,0);
            $query->whereBetween('data_previsao_entrega', [$dia_atual, $dia_final]);                               
        }

        $result = $query->get();

        return $result;
    }

    private function arrayZeroParaVazio($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->arrayZeroParaVazio($value);
                }else{
                    $array[$key] = empty($value)? '': $value;
                }
            }
        }
        return $array;
    }
}
