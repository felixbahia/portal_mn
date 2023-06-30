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

use App\Http\Controllers\NecessidadeComprasController;

class AcompanhamentoProjetoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AcompanhamentoProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AcompanhamentoProjeto');

        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
        $representantes = [];
        foreach ($representantes_busca as $key => $value) {
        	$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }

        return view('programs.acompanhamento_projeto.index')->with(['representantes' => $representantes]);
    }

    public function filtro(Request $request){
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

        $dados = [
            'digitacao' => [
                'id' => encrypt([0,9]),
                'natureza' => 'digitacao',
                'descricao' => 'Digitação',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'conferencia' => [
                'id' => encrypt([1,2]),
                'natureza' => 'conferencia',
                'descricao' => 'Conferência',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'aprovacao' => [
                'id' => encrypt([12,13,14]),
                'natureza' => 'aprovacao',
                'descricao' => 'Aprovação',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'compras' => [
                'id' => encrypt([4,5]),
                'natureza' => 'compras',
                'descricao' => 'Compras Matéria-Prima',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'remessa' => [
                'id' => encrypt([4,5]),
                'natureza' => 'remessa',
                'descricao' => 'Remessa',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'industrializacao' => [
                'id' => encrypt([6]),
                'natureza' => 'industrializacao',
                'descricao' => 'Industrialização',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'entrada_mercadoria' => [
                'id' => encrypt([8]),
                'natureza' => 'entrada_mercadoria',
                'descricao' => 'Entrada mercadoria',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'projeto_finalizado' => [
                'id' => encrypt([8]),
                'natureza' => 'projeto_finalizado',
                'descricao' => 'Projeto Finalizado',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
            'entrega' => [
                'id' => encrypt([8]),
                'natureza' => 'entrega',
                'descricao' => 'Entrega Cliente',
                'prazo' => 0,
                'atraso' => 0,
                'total' => 0,
            ],
        ];

        $data_atual = Carbon::now()->setTime(0,0,0);
        $necessidadeComprasControllerObj = new NecessidadeComprasController;

        foreach($result as $projeto){
            switch($projeto->status){
                case 0:
                case 9:
                    $dados['digitacao']['prazo']++;
                    $dados['digitacao']['total']++;
                    $total['prazo']++;
                    $total['total']++;
                    break;
                case 1:
                case 2:
                    $historicos = $projeto->historico->whereIn('natureza', ['finalizado_representante', 'em_revisao']);
                    $historico_data = '';
                    foreach($historicos as $historico){
                        $aux_historico_data = '';
                        if(empty($historico_data)){
                            $historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                        }else{
                            $aux_historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                            if($historico_data->lt($aux_historico_data)){
                                $historico_data = $aux_historico_data;
                            }
                        }
                    }

                    $diferenca_datas = $data_atual->diffInDays($historico_data);
                    
                    if($diferenca_datas > 1){
                        $dados['conferencia']['atraso']++;
                        $total['atraso']++;
                    }else{
                        $dados['conferencia']['prazo']++;
                        $total['prazo']++;
                    }
                    $dados['conferencia']['total']++;
                    $total['total']++;
                    break;
                case 12:
                case 13:
                case 14:
                    $projeto_data_modificacao = Carbon::parse($projeto->updated_at)->setTime(0,0,0);

                    $diferenca_datas = $data_atual->diffInDays($projeto_data_modificacao);
                    
                    if($diferenca_datas > 1){
                        $dados['aprovacao']['atraso']++;
                        $total['atraso']++;
                    }else{
                        $dados['aprovacao']['prazo']++;
                        $total['prazo']++;
                    }
                    $dados['aprovacao']['total']++;
                    $total['total']++;
                    break;
                case 6:
                    $projeto_data_modificacao = Carbon::parse($projeto->updated_at)->setTime(0,0,0);

                    $diferenca_datas = $data_atual->diffInDays($projeto_data_modificacao);
                    
                    if($diferenca_datas > 25){
                        $dados['industrializacao']['atraso']++;
                        $total['atraso']++;
                    }else{
                        $dados['industrializacao']['prazo']++;
                        $total['prazo']++;
                    }
                    $dados['industrializacao']['total']++;
                    $total['total']++;
                    break;
                case 8:
                    $finalizacao_projeto = Carbon::createFromFormat('Y-m-d', $projeto->updated_at->format('Y-m-d'))->setTime(0,0,0);
                    $data_previsao_entrega_projeto = Carbon::createFromFormat('Y-m-d', $projeto->data_previsao_entrega)->setTime(0,0,0);

                    if($data_previsao_entrega_projeto->gt($finalizacao_projeto)){
                        $dados['projeto_finalizado']['prazo']++;
                        $total['prazo']++;
                    }else{
                        $dados['projeto_finalizado']['atraso']++;
                        $total['atraso']++;
                    }
                    $dados['projeto_finalizado']['total']++;

                    $projeto_data_modificacao = Carbon::parse($projeto->updated_at)->setTime(0,0,0);

                    $diferenca_datas = $data_atual->diffInDays($projeto_data_modificacao);
                    
                    if(!empty($projeto->detalhes_pedido)){
                        if($projeto->detalhes_pedido->status_pedido != 3){
                            if($diferenca_datas > 2){
                                $dados['entrada_mercadoria']['atraso']++;
                                $total['atraso']++;
                            }else{
                                $dados['entrada_mercadoria']['prazo']++;
                                $total['prazo']++;
                            }
                            $dados['entrada_mercadoria']['total']++;
                            $total['total']++;
                        }else{
                            if($projeto->detalhes_pedido->pedidoNasajon != 'Faturado'){
                                $pedido_data_modificacao = Carbon::parse($projeto->detalhes_pedido->updated_at)->setTime(0,0,0);

                                $diferenca_datas = $data_atual->diffInDays($pedido_data_modificacao);

                                if($diferenca_datas > 3){
                                    $dados['entrega']['atraso']++;
                                    $total['atraso']++;
                                }else{
                                    $dados['entrega']['prazo']++;
                                    $total['prazo']++;
                                }
                                $dados['entrega']['total']++;
                                $total['total']++;
                            }
                        }
                    }
                    
                    break;
                case 4:
                case 7:
                case 5:
                    $arr = [];
                    $arr['num_projeto'] = $projeto->id; 
                    $arr['nome_projeto'] = ''; 
                    $arr['cliente'] = ''; 
                    $arr['fornecedor'] = '';
                    $arr['codigo_produto'] = ''; 
                    $arr['nome_produto'] = '';
                    $arr['estabelecimento'] = ''; 
                    $arr['tipo'] = 'materia_prima';  
                    
                    $request_necessidade_compras = new Request($arr);

                    $retorno_necessidade_compras = $necessidadeComprasControllerObj->filter($request_necessidade_compras, true);
                    
                    $historicos = $projeto->historico->whereIn('natureza', ['aprovado_pedido']);
                    $historico_data = '';
                    foreach($historicos as $historico){
                        $aux_historico_data = '';
                        if(empty($historico_data)){
                            $historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                        }else{
                            $aux_historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                            if($historico_data->lt($aux_historico_data)){
                                $historico_data = $aux_historico_data;
                            }
                        }
                    }

                    $diferenca_datas = $data_atual->diffInDays($historico_data);

                    if(empty($retorno_necessidade_compras)){
                        if($diferenca_datas > 15){
                            $dados['compras']['atraso']++;
                            $total['atraso']++;
                        }else{
                            $dados['compras']['prazo']++;
                            $total['prazo']++;
                        }
                        $dados['compras']['total']++;
                        $total['total']++;
                    }else{                 
                        if($diferenca_datas > 13){
                            $dados['compras']['atraso']++;
                            $total['atraso']++;
                        }else{
                            $dados['compras']['prazo']++;
                            $total['prazo']++;
                        }
                        $dados['compras']['total']++;
                        $total['total']++;
                    }
                    break;
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

    public function queryBusca($filtros){
        $query = LancamentoProjeto::select();

        $query->with(['historico', 'detalhes_pedido.pedidoNasajon']);

        if(!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $filtros['data_inicio'])->setTime(0,0,0);
            $data_final = Carbon::CreateFromFormat("d/m/Y", $filtros['data_fim'])->setTime(23,59,59);
            $query->whereBetween('created_at', [$data_inicial, $data_final]);
        }else if(!empty($filtros['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $filtros['data_inicio'])->setTime(0,0,0);
            $query->where('created_at', '>=', $data_inicial);
        }else if(!empty($filtros['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $filtros['data_fim'])->setTime(23,59,59);
            $query->where('created_at', '<=', $data_final);
        }

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
        
        if(!empty($filtros['faccao'])){
            $query->whereHas('faccoes', function($query) use($filtros){
                $query->whereHas('faccao', function($query) use($filtros){
                    $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf');
                    $fornecedor_busca->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($filtros['faccao']).'%\'');
                    $fornecedor_busca = $fornecedor_busca->get();

                    $query->whereIn('cod_fornecedor', $fornecedor_busca->pluck('cnpj_cpf')); 
                });
            });
        }

        if(!empty($filtros['id_status'] )){
            $query->whereIn('status', $filtros['id_status']); 
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

    public function dialog(Request $request){
        $fields = $request->only('id', 'tipo', 'natureza', 'filtro');

        try{
            $id_status = decrypt($fields['id']);
            $filtro = decrypt($fields['filtro']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $filtro['id_status'] = $id_status;
        $filtro['tipo'] = $fields['tipo'];
        $filtro['natureza'] = $fields['natureza'];

        $result = $this->queryBusca($filtro);

        $necessidadeComprasControllerObj = new NecessidadeComprasController;
        $data_atual = Carbon::now()->setTime(0,0,0);
        $linhas = [];
        $coluna_producao = false;
        foreach($result as $projeto){
            $liberado = false;
            switch($projeto->status){
                case 0:
                case 9:
                    if(in_array($filtro['tipo'], ['prazo', 'total'])){
                        $liberado = true;
                    }
                    break;
                case 1:
                case 2:
                    $historicos = $projeto->historico->whereIn('natureza', ['finalizado_representante', 'em_revisao']);
                    $historico_data = '';
                    foreach($historicos as $historico){
                        $aux_historico_data = '';
                        if(empty($historico_data)){
                            $historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                        }else{
                            $aux_historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                            if($historico_data->lt($aux_historico_data)){
                                $historico_data = $aux_historico_data;
                            }
                        }
                    }

                    $diferenca_datas = $data_atual->diffInDays($historico_data);
                    
                    if($diferenca_datas > 1){
                        if(in_array($filtro['tipo'], ['atraso', 'total'])){
                            $liberado = true;
                        }
                    }else{
                        if(in_array($filtro['tipo'], ['prazo', 'total'])){
                            $liberado = true;
                        }
                    }
                    break;
                case 12:
                case 13:
                case 14:
                    $projeto_data_modificacao = Carbon::parse($projeto->updated_at)->setTime(0,0,0);

                    $diferenca_datas = $data_atual->diffInDays($projeto_data_modificacao);
                    
                    if($diferenca_datas > 1){
                        if(in_array($filtro['tipo'], ['atraso', 'total'])){
                            $liberado = true;
                        }
                    }else{
                        if(in_array($filtro['tipo'], ['prazo', 'total'])){
                            $liberado = true;
                        }
                    }
                    break;
                case 6:
                    $projeto_data_modificacao = Carbon::parse($projeto->updated_at)->setTime(0,0,0);

                    $diferenca_datas = $data_atual->diffInDays($projeto_data_modificacao);
                    
                    if($diferenca_datas > 25){
                        if(in_array($filtro['tipo'], ['atraso', 'total'])){
                            $liberado = true;
                        }
                    }else{
                        if(in_array($filtro['tipo'], ['prazo', 'total'])){
                            $liberado = true;
                        }
                    }
                    break;
                case 8:
                    if($filtro['natureza'] == 'projeto_finalizado'){
                        $finalizacao_projeto = Carbon::createFromFormat('Y-m-d', $projeto->updated_at->format('Y-m-d'))->setTime(0,0,0);
                        $data_previsao_entrega_projeto = Carbon::createFromFormat('Y-m-d', $projeto->data_previsao_entrega)->setTime(0,0,0);
    
                        if($data_previsao_entrega_projeto->gt($finalizacao_projeto)){
                            if(in_array($filtro['tipo'], ['prazo', 'total'])){
                                $liberado = true;
                            }
                        }else{
                            if(in_array($filtro['tipo'], ['atraso', 'total'])){
                                $liberado = true;
                            }
                        }
                    }else{
                        $projeto_data_modificacao = Carbon::parse($projeto->updated_at)->setTime(0,0,0);

                        $diferenca_datas = $data_atual->diffInDays($projeto_data_modificacao);
                        
                        if(!empty($projeto->detalhes_pedido)){
                            if($projeto->detalhes_pedido->status_pedido != 3){
                                if($diferenca_datas > 2){
                                    if(in_array($filtro['tipo'], ['atraso', 'total'])){
                                        $liberado = true;
                                    }
                                }else{
                                    if(in_array($filtro['tipo'], ['prazo', 'total'])){
                                        $liberado = true;
                                    }
                                }
                            }else{
                                if($projeto->detalhes_pedido->pedidoNasajon != 'Faturado'){
                                    $pedido_data_modificacao = Carbon::parse($projeto->detalhes_pedido->updated_at)->setTime(0,0,0);
    
                                    $diferenca_datas = $data_atual->diffInDays($pedido_data_modificacao);
    
                                    if($diferenca_datas > 3){
                                        if(in_array($filtro['tipo'], ['atraso', 'total'])){
                                            $liberado = true;
                                        }
                                    }else{
                                        if(in_array($filtro['tipo'], ['prazo', 'total'])){
                                            $liberado = true;
                                        }
                                    }
                                }
                            }
                        }
                        
                    }
                    break;
                case 4:
                case 7:
                case 5:
                    $arr = [];
                    $arr['num_projeto'] = $projeto->id; 
                    $arr['nome_projeto'] = ''; 
                    $arr['cliente'] = ''; 
                    $arr['fornecedor'] = '';
                    $arr['codigo_produto'] = ''; 
                    $arr['nome_produto'] = '';
                    $arr['estabelecimento'] = ''; 
                    $arr['tipo'] = 'materia_prima';  
                    
                    $request_necessidade_compras = new Request($arr);

                    $retorno_necessidade_compras = $necessidadeComprasControllerObj->filter($request_necessidade_compras, true);
                    
                    $historicos = $projeto->historico->whereIn('natureza', ['aprovado_pedido']);
                    $historico_data = '';
                    foreach($historicos as $historico){
                        $aux_historico_data = '';
                        if(empty($historico_data)){
                            $historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                        }else{
                            $aux_historico_data = Carbon::parse($historico->created_at)->setTime(0,0,0);
                            if($historico_data->lt($aux_historico_data)){
                                $historico_data = $aux_historico_data;
                            }
                        }
                    }

                    $diferenca_datas = $data_atual->diffInDays($historico_data);

                    if(empty($retorno_necessidade_compras)){
                        if($diferenca_datas > 15){
                            if(in_array($filtro['tipo'], ['atraso', 'total'])){
                                $liberado = true;
                            }
                        }else{
                            if(in_array($filtro['tipo'], ['prazo', 'total'])){
                                $liberado = true;
                            }
                        }
                    }else{                 
                        if($diferenca_datas > 13){
                            if(in_array($filtro['tipo'], ['atraso', 'total'])){
                                $liberado = true;
                            }
                        }else{
                            if(in_array($filtro['tipo'], ['prazo', 'total'])){
                                $liberado = true;
                            }
                        }
                    }
                    break;
            }

            if($liberado){
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
                    'prorrogacao' => empty($result_prorrogacao->data_previsao_entrega)? '' : (($result_prorrogacao->data_previsao_entrega > $projeto->data_previsao_entrega)? parserData($result_prorrogacao->data_previsao_entrega) : ''),
                    'status' => $projeto->status,
                ];
            }
        }
        asort($linhas);
        return view('programs.acompanhamento_projeto.modal.dialog')->with(['linhas' => $linhas]);
    }
}
