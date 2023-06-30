<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\User;
use App\LancamentoProjeto;
use App\LancamentoProjetoTecido;
use App\LancamentoProjetoInsumo;
use App\LancamentoProjetoFaccao;
use App\ProdutosEstoque;
use App\Faccao;
use App\ProdutoEspecificacao;
use App\RemessaProduto;
use App\NecessidadeCompras;
use App\PedidoPortal;
use App\PedidoItemPortal;
use App\ClienteNasajon;
use App\FornecedorNasajon;
use App\PessoasNasajon;
use App\UserNajason;
use App\OperacaoNasajon;
use App\PedidosVendaNasajon;
use App\NasajonEstabelecimento;
use App\Preco;
use App\TransportadorNasajon;
use App\PedidosReservaProdutoNasajon;
use App\EnvioProjetoFaccao;
use App\VendedorNasajon;
use App\ComprasNasajon;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\GerarRemessaRequest;
use App\Http\Requests\ListaDePrecosRequest;

use App\Http\Controllers\LancamentoProjetoController;

class RemessaItensController extends Controller
{

    private $unidades_permitida_insumo = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.', 'UN', 'Kg', 'KG', 'KGS'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\RemessaItens") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RemessaItens');

        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
        $representantes = [];
        foreach ($representantes_busca as $key => $value) {
        	$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }

        return view('programs.remessa_itens.index')->with(['representantes' => $representantes]);
    }

    public function filter(Request $request, $retorno_array = false){
        $fields = $request->only('cliente', 'faccao', 'representante', 'numero_projeto', 'nome_projeto', 'codigo_produto','nome_produto','representante', 'numero_pedido', 'tipo');
        $retorno = [];

        $fornecedor_busca = [];

        $query_remessa_projeto = EnvioProjetoFaccao::select();
        if(is_numeric($fields['numero_projeto'])){
            $query_remessa_projeto->where('lancamento_projetos_id', $fields['numero_projeto']);
        }
        if(isset($fields['numero_pedido'])){
            if(is_numeric($fields['numero_pedido'])){
                $query_remessa_projeto->where('pedido_id', $fields['numero_pedido']);
            }
        }
        if(!empty($fields['tipo'])){
            if($fields['tipo'] === 'projeto'){
                $query_remessa_projeto->whereNotNull('lancamento_projetos_id');
            }else if($fields['tipo'] === 'pedido'){
                $query_remessa_projeto->whereNotNull('pedido_id');
            }
        }
        $remessas_projetos = $query_remessa_projeto->get();
      
        if(!empty($remessas_projetos)){
            $query_tecido = LancamentoProjetoTecido::select();
            $query_tecido->with(['projeto_detalhes' => function($query){
                $query->with(['cliente']);
            }, 
            'produto' => function($query){
                $query->with(['servico_detalhes'  => function($query){
                    $query->with(['faccao' => function($query){
                        $query->with(['fornecedor']);
                    }]);
                }]);
            }, 
            'servico_detalhes' => function($query){
                $query->with(['faccao']);
            }]);
            $query_tecido->whereHas('projeto_detalhes', function($query) use($fields, $remessas_projetos){
                $query->whereIn('status', [4, 5]);
            
                if(!empty($fields['cliente'])){
                    $query->where(function($query) use($fields){
                        $cliente_busca = ClienteNasajon::select('cpf_cnpj')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'')->get();
                        $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                    });
                }
    
                $query->whereIn('id', $remessas_projetos->pluck('lancamento_projetos_id'));
        
                if(!empty($fields['nome_projeto'])){
                    $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
                }
        
                if(!empty($fields['representante'])){
                    $query->where('users_codigo_representante', $fields['representante']);
                }
            });
    
            if(!empty($fields['codigo_produto'])){
                $query_tecido->where('codigo_produto', 'ilike', $fields['codigo_produto']);
            }
    
            if(!empty($fields['faccao'])){
                $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf');
                $fornecedor_busca->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($fields['faccao']).'%\'');
                $fornecedor_busca = $fornecedor_busca->get();
            }
            
            if(!empty($fields['nome_produto'])){
                $query_tecido->whereHas('tecido_detalhes', function($query) use($fields){
                    $query->where('descricao', 'ilike', '%'.$fields['nome_produto'].'%');
                });
            }
    
            $result_tecido = $query_tecido->get();
    
            foreach($result_tecido as $tecido){
                if(!empty($tecido->servico_detalhes)){
                    if(empty($fields['faccao']) || ($tecido->servico_detalhes->faccao->cod_fornecedor === $fornecedor_busca->pluck('cnpj_cpf')[0])){
                        if(!empty($retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto])){
                            if(empty($retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id])){
                                $valor_remessa = 0;
                                $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                    ->where('faccaos_id', $tecido->servico_detalhes->faccao_id)
                                    ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                    ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                        $query->where('produto_codigo', $tecido->codigo_produto);
                                    }])
                                    ->get();

                                foreach($remessas as $remessa){
                                    if(!empty($remessa->pedidoRemessaNasajon)){
                                        if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                            if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                                $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                            }
                                        }
                                    }else{
                                        $valor_remessa += $remessa->quantidade_enviada;
                                    }
                                }
                                $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
    
                                $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'])); 
                                $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtd_pr']++;
                                $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['remessa'] = $valor_remessa;
                                $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total;
                            }else{
                                $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($tecido->consumo_total + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar']));
                                $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total + $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'];
                            }                
                        }else{
                            $estoque = $this->estoqueProduto($tecido->codigo_produto, "", true);
                            $botao_remessa = empty($estoque['estoque'])? false : true;
                            $valor_remessa = 0; 
                            $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                    $query->where('produto_codigo', $tecido->codigo_produto);
                                }])
                                ->where('faccaos_id', $tecido->servico_detalhes->faccao_id)
                                ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                ->get();

                            foreach($remessas as $remessa){
                                if(!empty($remessa->pedidoRemessaNasajon)){
                                    if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                        if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                            $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                        }
                                    }
                                }else{
                                    $valor_remessa += $remessa->quantidade_enviada;
                                }
                            }
                            $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;

                            $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto] =[
                                'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                                'numero_projeto' => $tecido->lancamento_projetos_id,
                                'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                                'pedido' => $tecido->projeto_detalhes->pedido,
                                'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                                'faccao' => $tecido->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                                'produto_a_enviar' => $tecido->tecido_detalhes->descricao,
                                'qtde_a_enviar' => parserValor($qtde_a_enviar),
                                'estoque' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                                'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                                'botao_remessa' => $botao_remessa,
                                'codigo_produto' => $tecido->codigo_produto,
                                'tipo' => 'tecido',
                                'faccao_id' => encrypt($tecido->servico_detalhes->faccao_id),
                                'qtd_pr' => 1,
                                $tecido->lancamento_projetos_id => [
                                    'remessa' => $valor_remessa,
                                    'quantidade' => $tecido->consumo_total
                                ],
                                'validar' => true
                            ];
                        }
                    }
                }else{
                    if(empty($fields['faccao']) || ($tecido->produto->servico_detalhes->faccao->cod_fornecedor === $fornecedor_busca->pluck('cnpj_cpf')[0])){
                        if(!empty($retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto])){
                            if(empty($retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id])){
                                $valor_remessa = 0; 
                                $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                    ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                        $query->where('produto_codigo', $tecido->codigo_produto);
                                    }])
                                    ->where('faccaos_id', $tecido->produto->servico_detalhes->faccao_id)
                                    ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                    ->get();

                                foreach($remessas as $remessa){
                                    if(!empty($remessa->pedidoRemessaNasajon)){
                                        if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                            if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                                $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                            }
                                        }
                                    }else{
                                        $valor_remessa += $remessa->quantidade_enviada;
                                    }
                                }
                                $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
    
                                $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar']));
                                $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtd_pr']++;
                                $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['remessa'] = $valor_remessa;
                                $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total;                            
                            }else{
                                $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($tecido->consumo_total + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar']));
                                $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total + $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'];
                            }
                        }else{
                            $estoque = $this->estoqueProduto($tecido->codigo_produto, "", true);
                            $botao_remessa = empty($estoque['estoque'])? false : true;
                            $valor_remessa = 0; 
                            $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                    $query->where('produto_codigo', $tecido->codigo_produto);
                                }])
                                ->where('faccaos_id', $tecido->produto->servico_detalhes->faccao_id)
                                ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                ->get();

                            foreach($remessas as $remessa){
                                if(!empty($remessa->pedidoRemessaNasajon)){
                                    if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                        if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                            $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                        }
                                    }
                                }else{
                                    $valor_remessa += $remessa->quantidade_enviada;
                                }
                            }
                            $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
    
                            $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto] =[
                                'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                                'numero_projeto' => $tecido->lancamento_projetos_id,
                                'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                                'pedido' => $tecido->projeto_detalhes->pedido,
                                'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                                'faccao' => $tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                                'produto_a_enviar' => empty($tecido->tecido_detalhes)? '' : $tecido->tecido_detalhes->descricao,
                                'qtde_a_enviar' => parserValor($qtde_a_enviar),
                                'estoque' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                                'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                                'botao_remessa' => $botao_remessa,
                                'codigo_produto' => $tecido->codigo_produto,
                                'tipo' => 'tecido',
                                'faccao_id' => encrypt($tecido->produto->servico_detalhes->faccao_id),
                                'qtd_pr' => 1,
                                $tecido->lancamento_projetos_id => [
                                    'remessa' => $valor_remessa,
                                    'quantidade' => $tecido->consumo_total
                                ],
                                'validar' => true
                            ];
                        }
                    }
                }
            }
    
            $query_insumo = LancamentoProjetoInsumo::select();
            $query_insumo->with(['projeto_detalhes' => function($query){
                $query->with(['cliente']);
            }, 
            'produto' => function($query){
                $query->with(['servico_detalhes' => function($query){
                    $query->with(['faccao' => function($query){
                        $query->with(['fornecedor']);
                    }]);
                }]);
            }]);
            $query_insumo->whereHas('projeto_detalhes', function($query) use($fields, $remessas_projetos){
                $query->whereIn('status', [4, 5]);
            
                if(!empty($fields['cliente'])){
                    $query->where(function($query) use($fields){
                        $cliente_busca = ClienteNasajon::select('cpf_cnpj')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'')->get();
                        $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                    });
                }
        
                $query->whereIn('id', $remessas_projetos->pluck('lancamento_projetos_id'));
        
                if(!empty($fields['nome_projeto'])){
                    $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
                }
        
                if(!empty($fields['representante'])){
                    $query->where('users_codigo_representante', $fields['representante']);
                }
            });
            if(!empty($fields['faccao'])){
                $query_insumo->whereHas('produto', function($query) use($fields){
                    $query->wherehas('servico_detalhes', function($query) use($fields){
                        $query->whereHas('faccao', function($query) use($fields){
                            $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf');
                            $fornecedor_busca->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($fields['faccao']).'%\'');
                            $fornecedor_busca = $fornecedor_busca->get();
    
                            $query->whereIn('cod_fornecedor', $fornecedor_busca->pluck('cnpj_cpf'));
                        });
                    });
                });
            }
    
            if(!empty($fields['codigo_produto'])){
                $query_insumo->where('codigo_produto', 'ilike', $fields['codigo_produto']);
            }
            
            if(!empty($fields['nome_produto'])){
                $query_insumo->whereHas('insumo_detalhes', function($query) use($fields){
                    $query->where('descricao', 'ilike', '%'.$fields['nome_produto'].'%');
                });
            }
    
            $result_insumo = $query_insumo->get();
    
            foreach($result_insumo as $insumo){
                if(!empty($retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto])){
                    if(empty($retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id])){
                        $valor_remessa = 0; 
                        $remessas = RemessaProduto::select()->where('produto_codigo', $insumo->codigo_produto)
                            ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($insumo){
                                $query->where('produto_codigo', $insumo->codigo_produto);
                            }])
                            ->where('faccaos_id', $insumo->produto->servico_detalhes->faccao_id)
                            ->where('lancamento_projetos_id', $insumo->lancamento_projetos_id)
                            ->get();

                        foreach($remessas as $remessa){
                            if(!empty($remessa->pedidoRemessaNasajon)){
                                if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                    if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                        $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                    }
                                }
                            }else{
                                $valor_remessa += $remessa->quantidade_enviada;
                            }
                        }
                        $qtde_a_enviar = $insumo->consumo_total - $valor_remessa;
    
                        $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar']));
                        $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtd_pr']++;
                        $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['remessa'] = $valor_remessa;
                        $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['quantidade'] = $insumo->consumo_total;
    
                    }else{
                        $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar'] =  parserValor($insumo->consumo_total + parserNumber($retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar']));
                        $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['quantidade'] = $insumo->consumo_total + $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['quantidade'];
                    }
                }else{
                    $estoque = $this->estoqueProduto($insumo->codigo_produto, "", true);

                    $botao_remessa = empty($estoque['estoque'])? false : true;
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $insumo->codigo_produto)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($insumo){
                            $query->where('produto_codigo', $insumo->codigo_produto);
                        }])
                        ->where('faccaos_id', $insumo->produto->servico_detalhes->faccao_id)
                        ->where('lancamento_projetos_id', $insumo->lancamento_projetos_id)
                        ->get();

                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $insumo->consumo_total - $valor_remessa;

                    $validar = true;
    
                    $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto] =[
                        'id_projeto' => encrypt($insumo->lancamento_projetos_id),
                        'numero_projeto' => $insumo->lancamento_projetos_id,
                        'nome_projeto' => $insumo->projeto_detalhes->nome_projeto,
                        'pedido' => $insumo->projeto_detalhes->pedido,
                        'cliente' => empty($insumo->projeto_detalhes->cliente)? '' : $insumo->projeto_detalhes->cliente->nome." - ".$insumo->projeto_detalhes->cliente->cpf_cnpj,
                        'faccao' => $insumo->produto->servico_detalhes->faccao->fornecedor->nome." - ".$insumo->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => empty($insumo->insumo_detalhes)? '' : $insumo->insumo_detalhes->descricao,
                        'qtde_a_enviar' => parserValor($qtde_a_enviar),
                        'estoque' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                        'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'botao_remessa' => $botao_remessa,
                        'codigo_produto' => $insumo->codigo_produto,
                        'tipo' => 'insumo',
                        'faccao_id' => encrypt($insumo->produto->servico_detalhes->faccao_id),
                        'qtd_pr' => 1,
                        $insumo->lancamento_projetos_id => [
                            'remessa' => $valor_remessa,
                            'quantidade' => $insumo->consumo_total
                        ],
                        'validar' => $validar
                    ];
                }
            }
    
            $query_servico = LancamentoProjetoFaccao::select();
            $query_servico->whereNotNull('codigo_produto_acabado');
            $query_servico->with(['projeto' => function($query){
                $query->with(['cliente']);
            }, 
            'tecido' => function($query){
                $query->with(['produto' => function($query){
                    $query->with(['servico_detalhes' => function($query){
                        $query->with(['faccao' => function($query){
                            $query->with(['fornecedor']);
                        }]);
                    }]);
                }]);
            }]);
            $query_servico->whereHas('projeto', function($query) use($fields, $remessas_projetos){
                $query->whereIn('status', [4, 5]);
            
                if(!empty($fields['cliente'])){
                    $query->where(function($query) use($fields){
                        $cliente_busca = ClienteNasajon::select('cpf_cnpj')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'')->get();
                        $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                    });
                }
        
                $query->whereIn('id', $remessas_projetos->pluck('lancamento_projetos_id'));
        
                if(!empty($fields['nome_projeto'])){
                    $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
                }
        
                if(!empty($fields['representante'])){
                    $query->where('users_codigo_representante', $fields['representante']);
                }
            });
    
            if(!empty($fields['faccao'])){
                $query_servico->whereHas('faccao', function($query) use($fields){
                    $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf');
                    $fornecedor_busca->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($fields['faccao']).'%\'');
                    $fornecedor_busca = $fornecedor_busca->get();
    
                    $query->whereIn('cod_fornecedor', $fornecedor_busca->pluck('cnpj_cpf'));
                });
            }
    
            if(!empty($fields['codigo_produto'])){
                $query_servico->where('codigo_produto_acabado', 'ilike', $fields['codigo_produto']);
            }
            
            if(!empty($fields['nome_produto'])){
                $query_servico->whereHas('produto_acabado', function($query) use($fields){
                    $query->where('descricao', 'ilike', '%'.$fields['nome_produto'].'%');
                });
            }
    
            $result_servico = $query_servico->get();

            foreach($result_servico as $servico){
                if(!empty($retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado])){
                    if(empty($retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id])){
                        $valor_remessa = 0; 
                        $remessas = RemessaProduto::select()->where('produto_codigo', $servico->codigo_produto)
                            ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($servico){
                                $query->where('produto_codigo', $servico->codigo_produto_acabado);
                            }])
                            ->where('faccaos_id', $servico->faccao_id)
                            ->where('lancamento_projetos_id', $servico->lancamento_projetos_id)
                            ->get();

                        foreach($remessas as $remessa){
                            if(!empty($remessa->pedidoRemessaNasajon)){
                                if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                    if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                        $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                    }
                                }
                            }else{
                                $valor_remessa += $remessa->quantidade_enviada;
                            }
                        }
                        $qtde_a_enviar = $servico->quantidade - $valor_remessa;
                        
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar']));
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtd_pr']++;
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['remessa'] = $valor_remessa;
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['quantidade'] = $servico->quantidade;
                    
                    }else{
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar'] =  parserValor($servico->quantidade + parserNumber($retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar']));
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['quantidade'] = $servico->quantidade + $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['quantidade'];
                    }
                }else{
                    $estoque = $this->estoqueProduto($servico->codigo_produto_acabado, "", true);
                    $botao_remessa = empty($estoque['estoque'])? false : true;
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $servico->codigo_produto_acabado)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($servico){
                            $query->where('produto_codigo', $servico->codigo_produto_acabado);
                        }])
                        ->where('faccaos_id', $servico->faccao_id)
                        ->where('lancamento_projetos_id', $servico->lancamento_projetos_id)
                        ->get();

                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $servico->quantidade - $valor_remessa;
                    if(empty($servico->tecido->produto->servico_detalhes->codigo_produto_acabado)){
                        $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado] =[
                            'id_projeto' => encrypt($servico->lancamento_projetos_id),
                            'numero_projeto' => $servico->lancamento_projetos_id,
                            'nome_projeto' => $servico->projeto->nome_projeto,
                            'pedido' => $servico->projeto->pedido,
                            'cliente' => empty($servico->projeto->cliente)? '' : $servico->projeto->cliente->nome." - ".$servico->projeto->cliente->cpfcnpj,
                            'faccao' => isset($servico->tecido->produto->servico_detalhes->faccao->fornecedor) ? $servico->tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$servico->tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf : '',
                            'produto_a_enviar' => empty($servico->produto_acabado)? '' : $servico->produto_acabado->descricao,
                            'qtde_a_enviar' => parserValor($qtde_a_enviar),
                            'estoque' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                            'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                            'botao_remessa' => $botao_remessa,
                            'codigo_produto' => $servico->codigo_produto_acabado,
                            'tipo' => 'servico',
                            'faccao_id' => encrypt($servico->tecido->produto->servico_detalhes->faccao_id),
                            'qtd_pr' => 1,
                            $servico->lancamento_projetos_id => [
                                'remessa' => $valor_remessa,
                                'quantidade' => $servico->quantidade
                            ],
                            'validar' => true
                        ];
                    }
                }
            }
    
            $query_pedido_item = PedidoItemPortal::select();
            $query_pedido_item->whereIn('pedido', $remessas_projetos->pluck('pedido_id'));
            $query_pedido_item->whereNotNull('faccaos_id');
            $result_pedido_item = $query_pedido_item->get();

            foreach($result_pedido_item  as $item){
                if(!empty($retorno[$item->pedido_id][$item->faccao_id][$item->codigo_tecidos_base])){
                    $retorno[$item->pedido_id][$item->faccao_id][$item->codigo_tecidos_base]['qtde_a_enviar'] =  parserValor($item->quantidade + parserNumber($retorno[$item->pedido_id][$item->faccao_id][$item->codigo_tecidos_base]['qtde_a_enviar']));
                    //$retorno[$item->pedido_id][$item->faccao_id][$item->codigo_tecidos_base][$item->pedido]['quantidade'] = $item->quantidade + $retorno[$item->pedido_id][$item->faccao_id][$item->codigo_tecidos_base][$item->pedido]['quantidade'];
                }else{
                    $estoque = $this->estoqueProduto($item->codigo_tecidos_base, "05", true, false, $item->pedido);

                    $botao_remessa = empty($estoque['estoque'])? false : true;
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $item->codigo_tecidos_base)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($item){
                            $query->where('produto_codigo', $item->codigo_tecidos_base);
                        }])
                        ->where('faccaos_id', $item->faccao_id)
                        ->where('pedido_id', $item->pedido_id)
                        ->get();

                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $item->quantidade - $valor_remessa;

                    $validar = true;

                    $retorno[$item->pedido_id][$item->faccao_id][$item->codigo_tecidos_base] =[
                        'id_projeto' => encrypt(''),
                        'numero_projeto' => $item->pedido,
                        'nome_projeto' => '',
                        'pedido' => $item->pedido,
                        'cliente' => empty($item->pedido_portal->cliente)? '' : $item->pedido_portal->cliente->nome." - ".$item->pedido_portal->cliente->cpf_cnpj,
                        'faccao' => $item->detalhesFaccao->fornecedor->nome." - ".$item->detalhesFaccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => empty($item->tecidosBase)? '' : $item->tecidosBase->descricao,
                        'qtde_a_enviar' => parserValor($qtde_a_enviar),
                        'estoque' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                        'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'botao_remessa' => $botao_remessa,
                        'codigo_produto' => $item->codigo_tecidos_base,
                        'tipo' => 'pedido',
                        'faccao_id' => encrypt($item->faccaos_id),
                        'qtd_pr' => 1,
                        $item->pedido => [
                            'remessa' => $valor_remessa,
                            'quantidade' => $item->quantidade
                        ],
                        'validar' => $validar
                    ];
                }
            }

            foreach($retorno as $key_projeto => $projeto){
                foreach($projeto as $key_faccao => $faccao){
                    foreach($faccao as $key_produto => $produto){
                        if(parserNumber($retorno[$key_projeto][$key_faccao][$key_produto]['qtde_a_enviar']) <= 0){
                            unset($retorno[$key_projeto][$key_faccao][$key_produto]);
                        }
                    }
                }
            }           
        }

        if($retorno_array){
            return $retorno;
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function estoqueProduto($codigo_produto, $estabelecimento, $todos = false, $separado = false, $pedido = ''){

        $estoque = 0.0;
        $estoque_compras = 0.0;
        $estabelecimentos = [
            "03",
            "04",
            "05",
            "06"
        ];
        
        if($separado){
            $estoque = [];

            $query_estoque = ProdutosEstoque::selectRaw('estabelecimento, cast(estoque as float), cast(compras_aberto as float)');
            $query_estoque->where('codigo_produto', $codigo_produto);
            $query_estoque->whereIn('estabelecimento', $estabelecimentos);
            $result_estoque = $query_estoque->get();

            foreach($result_estoque as $estoque_produto){
                $PedidosVendaNasajon = PedidosReservaProdutoNasajon::selectRaw('cast(sum(quantidade) as float) as quantidade');
                $PedidosVendaNasajon->where('codigo_produto', $codigo_produto);
                $PedidosVendaNasajon->where('codigo_estabelecimento', $estoque_produto->estabelecimento);
                $estoque_reserva = $PedidosVendaNasajon->first();

                $query_pedido_item = PedidoItemPortal::selectRaw('cast(sum(quantidade) as float) as quantidade ');
                $query_pedido_item->where('cod_produto', $codigo_produto);
                if(!empty($pedido)){
                    $query_pedido_item->where('pedido', '<>', $pedido);
                }
                $query_pedido_item->whereHas('pedido_portal', function($query) use($estoque_produto){
                    $query->whereNotIn('status_pedido', [3, 5, 7]);
                    $query->where('estabelecimento', $estoque_produto->estabelecimento);
                });
                $quantidade_pedido_item = $query_pedido_item->sum('quantidade');

                $compras_nasajon = ComprasNasajon::where('cod_produto', $codigo_produto)->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->get();
                $estoque_compras = 0;
                foreach($compras_nasajon as $compra_nasajon){
                    if($compra_nasajon->quantidade_restante > 0){
                        $estoque_compras += $compra_nasajon->quantidade_restante;
                    }else{
                        $estoque_compras += $compra_nasajon->quantidade;
                    }
                }

                $query_pedido_item_futuro = PedidoItemPortal::selectRaw('estabelecimento, cast(sum(quantidade) as float) as quantidade ');
                $query_pedido_item_futuro->leftJoin('pedido', 'pedido_item.pedido', '=', 'pedido.id');
                $query_pedido_item_futuro->whereNotIn('pedido.status_pedido', [8]);
                $query_pedido_item_futuro->where('pedido.estabelecimento', $estoque_produto->estabelecimento);
                $query_pedido_item_futuro->where('pedido_item.cod_produto', $codigo_produto);
                $query_pedido_item_futuro->whereNull('pedido.deleted_at');
                if(!empty($pedido)){
                    $query_pedido_item_futuro->where('pedido', '<>', $pedido);
                }
                $query_pedido_item_futuro->groupBy('pedido.estabelecimento');
                $result_pedidos_venda_futuro_portal = $query_pedido_item_futuro->get();

                foreach($result_pedidos_venda_futuro_portal as $pedidos_venda_futuro_portal){
                    $estoque_compras -= $pedidos_venda_futuro_portal->quantidade;
                }
                $estoque_compras = $estoque_compras < 0? 0 : $estoque_compras;

                $estoque[$estoque_produto->estabelecimento] = [
                    'estoque' => empty($estoque_reserva)? $estoque_produto->estoque: $estoque_produto->estoque - $estoque_reserva->quantidade - $quantidade_pedido_item,
                    'estoque_compras' => empty($estoque_compras)? 0 : $estoque_compras
                ];

                if($estoque[$estoque_produto->estabelecimento]['estoque'] < 0){
                    $estoque[$estoque_produto->estabelecimento]['estoque'] = 0;
                }
            }

            $retorno = $estoque;
        }else{
            if($todos){
                $estoque_produto = ProdutosEstoque::where('codigo_produto', $codigo_produto);
                $estoque_produto->whereIn('estabelecimento', $estabelecimentos);
            }else{
                $estoque_produto = ProdutosEstoque::where('codigo_produto', $codigo_produto);
                $estoque_produto->where('estabelecimento', $estabelecimento);
            }
    

            $result_produto_estoque = $estoque_produto->get();

            $compras_nasajon = ComprasNasajon::where('cod_produto', $codigo_produto)->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->get();
            $estoque_compras = 0;
            foreach($compras_nasajon as $compra_nasajon){
                if($compra_nasajon->quantidade_restante > 0){
                    $estoque_compras += $compra_nasajon->quantidade_restante;
                }else{
                    $estoque_compras += $compra_nasajon->quantidade;
                }
            }

            $query_pedido_item_futuro = PedidoItemPortal::selectRaw('estabelecimento, cast(sum(quantidade) as float) as quantidade ');
            $query_pedido_item_futuro->leftJoin('pedido', 'pedido_item.pedido', '=', 'pedido.id');
            $query_pedido_item_futuro->whereNotIn('pedido.status_pedido', [8]);
            $query_pedido_item_futuro->whereIn('pedido.estabelecimento', $result_produto_estoque->pluck('estabelecimento'));
            $query_pedido_item_futuro->where('pedido_item.cod_produto', $codigo_produto);
            $query_pedido_item_futuro->whereNull('pedido.deleted_at');
            if(!empty($pedido)){
                $query_pedido_item_futuro->where('pedido', '<>', $pedido);
            }
            $query_pedido_item_futuro->groupBy('pedido.estabelecimento');
            $result_pedidos_venda_futuro_portal = $query_pedido_item_futuro->get();

            foreach($result_pedidos_venda_futuro_portal as $pedidos_venda_futuro_portal){
                $estoque_compras -= $pedidos_venda_futuro_portal->quantidade;
            }
            $estoque_compras = $estoque_compras < 0? 0 : $estoque_compras;

            $PedidosVendaNasajon = PedidosReservaProdutoNasajon::selectRaw('codigo_estabelecimento, cast(sum(quantidade) as float) as quantidade')
                ->where('codigo_produto', $codigo_produto);
            $PedidosVendaNasajon->whereIn('codigo_estabelecimento', $result_produto_estoque->pluck('estabelecimento'));
            $PedidosVendaNasajon->groupBy('codigo_estabelecimento');
            $result_pedidos_venda_nasajon = $PedidosVendaNasajon->get();

            $reserva_nasajon = [];
            foreach($result_pedidos_venda_nasajon as $pedidos_venda_nasajon){
                $reserva_nasajon[intval($pedidos_venda_nasajon->codigo_estabelecimento)] = $pedidos_venda_nasajon->quantidade;
            }

            $query_pedido_item = PedidoItemPortal::selectRaw('estabelecimento, cast(sum(quantidade) as float) as quantidade ');
            $query_pedido_item->leftJoin('pedido', 'pedido_item.pedido', '=', 'pedido.id');
            $query_pedido_item->whereNotIn('pedido.status_pedido', [3, 5, 7]);
            $query_pedido_item->whereIn('pedido.estabelecimento', $result_produto_estoque->pluck('estabelecimento'));
            $query_pedido_item->where('pedido_item.cod_produto', $codigo_produto);
            $query_pedido_item->whereNull('pedido.deleted_at');
            if(!empty($pedido)){
                $query_pedido_item->where('pedido', '<>', $pedido);
            }
            $query_pedido_item->groupBy('pedido.estabelecimento');
            $result_pedidos_venda_portal = $query_pedido_item->get();

            $reserva_portal = [];
            foreach($result_pedidos_venda_portal as $pedidos_venda_portal){
                $reserva_portal[$pedidos_venda_portal->estabelecimento] = $pedidos_venda_portal->quantidade;
            }

            $estoque_estabelecimento = [];
            foreach($result_produto_estoque as $produto_estoque){
                $estoque_reserva = 0;
                if(empty($reserva_nasajon[intval($produto_estoque->estabelecimento)])){
                    $estoque_reserva_nasajon = 0;
                }else{
                    $estoque_reserva_nasajon = $reserva_nasajon[intval($produto_estoque->estabelecimento)];
                }

                if(empty($reserva_portal[intval($produto_estoque->estabelecimento)])){
                    $estoque_reserva_portal = 0;
                }else{
                    $estoque_reserva_portal = $reserva_portal[intval($produto_estoque->estabelecimento)];
                }

                $estoque_reserva = $estoque_reserva_nasajon + $estoque_reserva_portal;

                $estoque_estabelecimento[$produto_estoque->estabelecimento] = $produto_estoque->estoque - $estoque_reserva > 0? $produto_estoque->estoque - $estoque_reserva : 0;
            }

            $estoque = 0;
            foreach($estoque_estabelecimento as $quantidade){
                $estoque += $quantidade;
            }
    
            if($estoque < 0){
                $estoque = 0;
            }

            $retorno = [
                'estoque' => empty($estoque)? 0 : number_format(parserFloat10($estoque), 2, '.', ''),
                'estoque_compras' => parserFloat10($estoque_compras)
            ];
        }
 
        return $retorno;
    }

    function dialogGeracaoRemessa(Request $request){
        $fields = $request->only('tipo', 'codigo_produto', 'id_faccao', 'id_projeto', 'pedido');
        $itens = [];
        $estabelecimentos = returnEmpresasNasajonView();

        try{
            $id_faccao = decrypt($fields['id_faccao']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $faccao = Faccao::find($id_faccao);

        $fornecedor_cnpj = $faccao->fornecedor->cnpj_cpf;
        $clienteNasajonObj = ClienteNasajon::where('cpf_cnpj', $fornecedor_cnpj)->first();
        
        if(empty($clienteNasajonObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Há uma necessidade de cadastrar a Facção '.$faccao->fornecedor->nome." - ".$faccao->fornecedor->cnpj_cpf.' como cliente, para que seja possível gerar remessa. Favor, entrar em contato com setor responsável de cadastro.<br><br><br>',
                'error' => [],
                'response' => []
            ],422);
        }

        $estoque = $this->estoqueProduto($fields['codigo_produto'], "", true, false);

        $dados = [
            'id_projeto' => encrypt($id_projeto),
            'id_faccao' => encrypt($id_faccao),
            'faccao' => $faccao->fornecedor->nome." - ".$faccao->fornecedor->cnpj_cpf,
            'codigo_produto' => $fields['codigo_produto'],
            'pedido' => $fields['pedido']
        ];

        if(!empty($id_projeto)){
            $query_tecido = LancamentoProjetoTecido::select();
            $query_tecido->with(['projeto_detalhes', 'produto', 'servico_detalhes']);
            $query_tecido->whereHas('projeto_detalhes', function($query) use($fields){
                $query->whereIn('status', [4, 5]);
            });
    
            $query_tecido->where('codigo_produto', 'ilike', $fields['codigo_produto']);
            $query_tecido->where('lancamento_projetos_id', $id_projeto);
    
            $result_tecido = $query_tecido->get();
    
            foreach($result_tecido as $tecido){
                if(!empty($tecido->servico_detalhes)){
                    if($tecido->servico_detalhes->faccao_id === $id_faccao){
                        if(!empty($itens[$tecido->lancamento_projetos_id])){
                            $itens[$tecido->lancamento_projetos_id]['qtde_a_enviar'] =  parserValor($tecido->consumo_total + parserNumber($itens[$tecido->lancamento_projetos_id]['qtde_a_enviar'])); 
                        }else{
                            $valor_remessa = 0; 
                            $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                    $query->where('produto_codigo', $tecido->codigo_produto);
                                }])
                                ->where('faccaos_id', $tecido->servico_detalhes->faccao_id)
                                ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                ->get();
    
                            foreach($remessas as $remessa){
                                if(!empty($remessa->pedidoRemessaNasajon)){
                                    if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                        if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                            $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                        }
                                    }
                                }else{
                                    $valor_remessa += $remessa->quantidade_enviada;
                                }
                            }
                            $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
    
                            $estoque = $this->estoqueProduto($tecido->codigo_produto, "", false, true);
                            
                            $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                            $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                            $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                            $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                            $estoque_estabelecimento_origem = $estoque_matriz;
    
                            $estoque = [
                                'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                                'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                            ];
                            $itens [$tecido->lancamento_projetos_id] = [
                                'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                                'id_produto' => $tecido->id,
                                'numero_projeto' => $tecido->lancamento_projetos_id,
                                'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                                'pedido' => $tecido->projeto_detalhes->pedido,
                                'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                                'faccao' => $tecido->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                                'produto_a_enviar' => $tecido->tecido_detalhes->descricao,
                                'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                                'codigo_produto' => $tecido->codigo_produto,
                                'tipo' => 'tecido',
                                'faccao_id' => encrypt($tecido->produto->servico_detalhes->faccao_id),
                                'codigo_estabelecimento' => 5,
                                'estabelecimento' => $estabelecimentos[$tecido->projeto_detalhes->estabelecimento],
                                'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                                'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                                'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                                'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                                'estoque' => [
                                    'estoque_rondonia' => [
                                        'codigo' => 3,
                                        'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                        'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                    ],
                                    'estoque_tocantins' => [
                                        'codigo' => 4,
                                        'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                        'estabelecimento' => $estabelecimentos[4],
                                    ],
                                    'estoque_matriz' => [
                                        'codigo' => 5,
                                        'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                        'estabelecimento' => $estabelecimentos[5],
                                    ],
                                    'estoque_almirante_2' => [
                                        'codigo' => 6,
                                        'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                        'estabelecimento' => $estabelecimentos[6],
                                    ],
                                ],
                            ];
                        }
                    }
                }else{
                    if($tecido->produto->servico_detalhes->faccao_id === $id_faccao){
                        if(!empty($itens[$tecido->lancamento_projetos_id])){
                            $itens[$tecido->lancamento_projetos_id]['qtde_a_enviar'] =  parserValor($tecido->consumo_total + parserNumber($itens[$tecido->lancamento_projetos_id]['qtde_a_enviar']));
                        }else{
                            $valor_remessa = 0; 
                            $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                    $query->where('produto_codigo', $tecido->codigo_produto);
                                }])
                                ->where('faccaos_id', $tecido->produto->servico_detalhes->faccao_id)
                                ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                ->get();
    
                            foreach($remessas as $remessa){
                                if(!empty($remessa->pedidoRemessaNasajon)){
                                    if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                        if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                            $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                        }
                                    }
                                }else{
                                    $valor_remessa += $remessa->quantidade_enviada;
                                }
                            }
                            $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
                            $estoque = $this->estoqueProduto($tecido->codigo_produto, "", false, true);
                            
                            $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                            $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                            $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                            $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                            $estoque_estabelecimento_origem = $estoque_matriz;
    
                            $estoque = [
                                'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                                'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                            ];
                            $itens[$tecido->lancamento_projetos_id] = [
                                'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                                'id_produto' => $tecido->id,
                                'numero_projeto' => $tecido->lancamento_projetos_id,
                                'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                                'pedido' => $tecido->projeto_detalhes->pedido,
                                'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                                'faccao' => $tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                                'produto_a_enviar' => $tecido->tecido_detalhes->descricao,
                                'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                                'codigo_produto' => $tecido->codigo_produto,
                                'tipo' => 'tecido',
                                'faccao_id' => encrypt($tecido->produto->servico_detalhes->faccao_id),
                                'codigo_estabelecimento' => 5,
                                'estabelecimento' => $estabelecimentos[$tecido->projeto_detalhes->estabelecimento],
                                'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                                'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                                'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                                'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                                'estoque' => [
                                    'estoque_rondonia' => [
                                        'codigo' => 3,
                                        'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                        'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                    ],
                                    'estoque_tocantins' => [
                                        'codigo' => 4,
                                        'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                        'estabelecimento' => $estabelecimentos[4],
                                    ],
                                    'estoque_matriz' => [
                                        'codigo' => 5,
                                        'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                        'estabelecimento' => $estabelecimentos[5],
                                    ],
                                    'estoque_almirante_2' => [
                                        'codigo' => 6,
                                        'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                        'estabelecimento' => $estabelecimentos[6],
                                    ],
                                ],
                            ];
                        }
                    }
                }
            }
    
            $query_insumo = LancamentoProjetoInsumo::select();
            $query_insumo->with(['projeto_detalhes', 'produto']);
            $query_insumo->whereHas('projeto_detalhes', function($query) use($fields){
                $query->whereIn('status', [4, 5]);
            });
            $query_insumo->whereHas('produto', function($query) use($id_faccao){
                $query->wherehas('servico_detalhes', function($query) use($id_faccao){
                    $query->where('faccao_id', $id_faccao); 
                });
            });
            $query_insumo->where('codigo_produto', 'ilike', $fields['codigo_produto']);
            $query_insumo->where('lancamento_projetos_id', $id_projeto);
    
            $result_insumo = $query_insumo->get();
    
            foreach($result_insumo as $insumo){
                if(!empty($itens[$insumo->lancamento_projetos_id])){
                    $itens[$insumo->lancamento_projetos_id]['qtde_a_enviar'] =  parserValor($insumo->consumo_total + parserNumber($itens[$insumo->lancamento_projetos_id]['qtde_a_enviar']));
                }else{
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $insumo->codigo_produto)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($insumo){
                            $query->where('produto_codigo', $insumo->codigo_produto);
                        }])
                        ->where('faccaos_id', $insumo->produto->servico_detalhes->faccao_id)
                        ->where('lancamento_projetos_id', $insumo->lancamento_projetos_id)
                        ->get();
    
                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $insumo->consumo_total - $valor_remessa;
                    $estoque = $this->estoqueProduto($insumo->codigo_produto, "", false, true);
                            
                    $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                    $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                    $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                    $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                    $estoque_estabelecimento_origem = $estoque_matriz;
    
                    $estoque = [
                        'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                        'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                    ];
                    $itens [$insumo->lancamento_projetos_id] = [
                        'id_projeto' => encrypt($insumo->lancamento_projetos_id),
                        'id_produto' => $insumo->id,
                        'numero_projeto' => $insumo->lancamento_projetos_id,
                        'nome_projeto' => $insumo->projeto_detalhes->nome_projeto,
                        'pedido' => $insumo->projeto_detalhes->pedido,
                        'cliente' => empty($insumo->projeto_detalhes->cliente)? '' : $insumo->projeto_detalhes->cliente->nome." - ".$insumo->projeto_detalhes->cliente->cpf_cnpj,
                        'faccao' => $insumo->produto->servico_detalhes->faccao->fornecedor->nome." - ".$insumo->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => $insumo->insumo_detalhes->descricao,
                        'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                        'codigo_produto' => $insumo->codigo_produto,
                        'tipo' => 'insumo',
                        'faccao_id' => encrypt($insumo->produto->servico_detalhes->faccao_id),
                        'codigo_estabelecimento' => 5,
                        'estabelecimento' => $estabelecimentos[$insumo->projeto_detalhes->estabelecimento],
                        'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                        'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                        'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                        'estoque' => [
                            'estoque_rondonia' => [
                                'codigo' => 3,
                                'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                            ],
                            'estoque_tocantins' => [
                                'codigo' => 4,
                                'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                'estabelecimento' => $estabelecimentos[4]
                            ],
                            'estoque_matriz' => [
                                'codigo' => 5,
                                'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                'estabelecimento' => $estabelecimentos[5]
                            ],
                            'estoque_almirante_2' => [
                                'codigo' => 6,
                                'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                'estabelecimento' => $estabelecimentos[6]
                            ],
                        ],
                    ];
                }
            }
    
            $query_servico = LancamentoProjetoFaccao::select();
            $query_servico->whereNotNull('codigo_produto_acabado');
            $query_servico->with(['projeto']);
            $query_servico->whereHas('projeto', function($query) use($fields){
                $query->whereIn('status', [4, 5]);
            });
            $query_servico->whereHas('tecido.produto.servico_detalhes', function($query) use($id_faccao){
                $query->where('faccao_id', $id_faccao);
            });
            $query_servico->where('codigo_produto_acabado', 'ilike', $fields['codigo_produto']);
            $query_servico->where('lancamento_projetos_id', $id_projeto);
    
            $result_servico = $query_servico->get();
    
            foreach($result_servico as $servico){
                if(!empty($itens[$servico->lancamento_projetos_id])){
                    $itens[$servico->lancamento_projetos_id]['qtde_a_enviar'] =  parserValor($servico->quantidade + parserNumber($itens[$servico->lancamento_projetos_id]['qtde_a_enviar']));
                }else{
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $servico->codigo_produto_acabado)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($servico){
                            $query->where('produto_codigo', $servico->codigo_produto_acabado);
                        }])
                        ->where('faccaos_id', $servico->faccao_id)
                        ->where('lancamento_projetos_id', $servico->lancamento_projetos_id)
                        ->get();
    
                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $servico->quantidade - $valor_remessa;
    
                    $estoque = $this->estoqueProduto($servico->codigo_produto_acabado, "", false, true);
                            
                    $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                    $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                    $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                    $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                    $estoque_estabelecimento_origem = $estoque_matriz;
    
                    $estoque = [
                        'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                        'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                    ];
                    $itens[$servico->lancamento_projetos_id] = [
                        'id_projeto' => encrypt($servico->lancamento_projetos_id),
                        'id_produto' => $servico->id,
                        'numero_projeto' => $servico->lancamento_projetos_id,
                        'nome_projeto' => $servico->projeto->nome_projeto,
                        'pedido' => $servico->projeto->pedido,
                        'cliente' => empty($servico->projeto->cliente)? '' : $servico->projeto->cliente->nome." - ".$servico->projeto->cliente->cpf_cnpj,
                        'faccao' => $servico->tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$servico->tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => $servico->produto_acabado->descricao,
                        'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                        'codigo_produto' => $servico->codigo_produto_acabado,
                        'tipo' => 'servico',
                        'faccao_id' => encrypt($servico->tecido->produto->servico_detalhes->faccao_id),
                        'codigo_estabelecimento' => 5,
                        'estabelecimento' => $estabelecimentos[$servico->projeto->estabelecimento],
                        'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                        'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                        'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                        'estoque' => [
                            'estoque_rondonia' => [
                                'codigo' => 3,
                                'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                            ],
                            'estoque_tocantins' => [
                                'codigo' => 4,
                                'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                'estabelecimento' => $estabelecimentos[4],
                            ],
                            'estoque_matriz' => [
                                'codigo' => 5,
                                'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                'estabelecimento' => $estabelecimentos[5],
                            ],
                            'estoque_almirante_2' => [
                                'codigo' => 6,
                                'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                'estabelecimento' => $estabelecimentos[6],
                            ],
                        ],
                    ];
                }
            }
        }else{
            $query_pedido_item = PedidoItemPortal::select();
            $query_pedido_item->where('pedido', $fields['pedido']);
            $query_pedido_item->where('codigo_tecidos_base', $fields['codigo_produto']);
            $query_pedido_item->whereNotNull('faccaos_id');
            $result_pedido_item = $query_pedido_item->get();

            foreach($result_pedido_item as $item){
                if(!empty($itens[$item->pedido])){
                    $itens[$item->pedido]['qtde_a_enviar'] =  parserValor($item->quantidade + parserNumber($itens[$item->pedido]['qtde_a_enviar']));
                }else{
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $item->codigo_tecidos_base)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($item){
                            $query->where('produto_codigo', $item->codigo_tecidos_base);
                        }])
                        ->where('faccaos_id', $item->faccao_id)
                        ->where('pedido_id', $item->pedido)
                        ->get();
    
                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $item->quantidade - $valor_remessa;
                    $estoque = $this->estoqueProduto($item->codigo_tecidos_base, "", false, true, $item->pedido);

                    $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                    $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                    $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                    $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                    $estoque_estabelecimento_origem = $estoque_matriz;
    
                    $estoque = [
                        'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                        'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                    ];
                    $itens [$item->pedido] = [
                        'id_projeto' => encrypt(''),
                        'id_produto' => $item->id,
                        'numero_projeto' => $item->pedido,
                        'nome_projeto' => '',
                        'pedido' => $item->pedido,
                        'cliente' => empty($item->pedido_portal->cliente)? '' : $item->pedido_portal->cliente->nome." - ".$item->pedido_portal->cliente->cpf_cnpj,
                        'faccao' => $item->detalhesFaccao->fornecedor->nome." - ".$item->detalhesFaccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => $item->tecidosBase->descricao,
                        'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                        'codigo_produto' => $item->codigo_tecidos_base,
                        'tipo' => 'pedido',
                        'faccao_id' => encrypt($item->faccao_id),
                        'codigo_estabelecimento' => 5,
                        'estabelecimento' => $estabelecimentos[5],
                        'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                        'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                        'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                        'estoque' => [
                            'estoque_rondonia' => [
                                'codigo' => 3,
                                'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                            ],
                            'estoque_tocantins' => [
                                'codigo' => 4,
                                'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                'estabelecimento' => $estabelecimentos[4]
                            ],
                            'estoque_matriz' => [
                                'codigo' => 5,
                                'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                'estabelecimento' => $estabelecimentos[5]
                            ],
                            'estoque_almirante_2' => [
                                'codigo' => 6,
                                'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                'estabelecimento' => $estabelecimentos[6]
                            ],
                        ],
                    ];
                }
            }
        }
        

        return view('programs.remessa_itens.modal.geracao_remessa')->with(['dados' => $dados, 'itens' => $itens, 'estabelecimentos' => $estabelecimentos]);
    }

    public function getProdutoParaRemessa(Request $request){
        $fields = $request->only('id_faccao', 'todos_produtos', 'codigo_produto', 'id_projeto', 'pedido');
        $itens = [];
        $estabelecimentos = returnEmpresasNasajonView();

        try{
            $id_faccao = decrypt($fields['id_faccao']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $query_remessa_projeto = EnvioProjetoFaccao::select();
        if(is_numeric($fields['id_projeto'])){
            $query_remessa_projeto->where('lancamento_projetos_id', $fields['id_projeto']);
        }
        $remessas_projetos = $query_remessa_projeto->get();

        if(!empty($id_projeto)){
            $query_tecido = LancamentoProjetoTecido::select();
            $query_tecido->with(['projeto_detalhes', 'produto', 'servico_detalhes']);
            $query_tecido->whereHas('projeto_detalhes', function($query) use($remessas_projetos){
                $query->whereIn('status', [4, 5]);
    
                $query->whereIn('id', $remessas_projetos->pluck('lancamento_projetos_id'));
            });
    
            if(empty($fields['todos_produtos'])){
                $query_tecido->where('codigo_produto', 'ilike', $fields['codigo_produto']);
            }else if($fields['todos_produtos'] != "todos_produtos_projetos"){
                $query_tecido->where('lancamento_projetos_id', $id_projeto);
            }
    
    
            $result_tecido = $query_tecido->get();
    
            foreach($result_tecido as $tecido){
                if(!empty($tecido->servico_detalhes)){
                    if($tecido->servico_detalhes->faccao_id === $id_faccao){
                        if(!empty($itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto])){
                            $itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserQtd($tecido->consumo_total + parserNumber($itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto]['qtde_a_enviar'])); 
                        }else{
                            $valor_remessa = 0; 
                            $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                    $query->where('produto_codigo', $tecido->codigo_produto);
                                }])
                                ->where('faccaos_id', $tecido->servico_detalhes->faccao_id)
                                ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                ->get();
    
                            foreach($remessas as $remessa){
                                if(!empty($remessa->pedidoRemessaNasajon)){
                                    if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                        if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                            $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                        }
                                    }
                                }else{
                                    $valor_remessa += $remessa->quantidade_enviada;
                                }
                            }
                            $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
    
                            $estoque = $this->estoqueProduto($tecido->codigo_produto, "", false, true);
                            
                            $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                            $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                            $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                            $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                            $estoque_estabelecimento_origem = $estoque_matriz;
    
                            $estoque = [
                                'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                                'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                            ];
                            if(!empty($estoque['estoque'])){
                                $projetos [$tecido->lancamento_projetos_id] = [
                                    'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                                    'numero_projeto' => $tecido->lancamento_projetos_id,
                                    'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                                    'pedido' => $tecido->projeto_detalhes->pedido,
                                    'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                                    'estabelecimento' => $estabelecimentos[$tecido->projeto_detalhes->estabelecimento],
                                    'codigo_estabelecimento' => 5,
                                ];
                                $itens [$tecido->lancamento_projetos_id][$tecido->codigo_produto] = [
                                    'id_produto' => $tecido->id,
                                    'faccao' => $tecido->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                                    'produto_a_enviar' => $tecido->tecido_detalhes->descricao,
                                    'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                                    'codigo_produto' => $tecido->codigo_produto,
                                    'tipo' => 'tecido',
                                    'faccao_id' => encrypt($tecido->produto->servico_detalhes->faccao_id),
                                    'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                                    'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                                    'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                                    'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                                    'estoque' => [
                                        'estoque_rondonia' => [
                                            'codigo' => 3,
                                            'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                            'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                        ],
                                        'estoque_tocantins' => [
                                            'codigo' => 4,
                                            'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                            'estabelecimento' => $estabelecimentos[4],
                                        ],
                                        'estoque_matriz' => [
                                            'codigo' => 5,
                                            'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                            'estabelecimento' => $estabelecimentos[5],
                                        ],
                                        'estoque_almirante_2' => [
                                            'codigo' => 6,
                                            'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                            'estabelecimento' => $estabelecimentos[6],
                                        ],
                                    ],
                                ];
                            }
                        }
                    }
                }else{
                    if($tecido->produto->servico_detalhes->faccao_id === $id_faccao){
                        if(!empty($itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto])){
                            $itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserQtd($tecido->consumo_total + parserNumber($itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto]['qtde_a_enviar']));
                        }else{
                            $valor_remessa = 0; 
                            $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                                ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                    $query->where('produto_codigo', $tecido->codigo_produto);
                                }])
                                ->where('faccaos_id', $tecido->produto->servico_detalhes->faccao_id)
                                ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                                ->get();
    
                            foreach($remessas as $remessa){
                                if(!empty($remessa->pedidoRemessaNasajon)){
                                    if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                        if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                            $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                        }
                                    }
                                }else{
                                    $valor_remessa += $remessa->quantidade_enviada;
                                }
                            }
                            $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;
    
                            $estoque = $this->estoqueProduto($tecido->codigo_produto, "", false, true);
                            
                            $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                            $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                            $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                            $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                            $estoque_estabelecimento_origem = $estoque_matriz;
    
                            $estoque = [
                                'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                                'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                            ];
                            if(!empty($estoque['estoque'])){
                                $projetos [$tecido->lancamento_projetos_id] = [
                                    'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                                    'numero_projeto' => $tecido->lancamento_projetos_id,
                                    'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                                    'pedido' => $tecido->projeto_detalhes->pedido,
                                    'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                                    'estabelecimento' => $estabelecimentos[$tecido->projeto_detalhes->estabelecimento],
                                    'codigo_estabelecimento' => 5,
                                ];
                                $itens[$tecido->lancamento_projetos_id][$tecido->codigo_produto] = [
                                    'id_produto' => $tecido->id,
                                    'faccao' => $tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                                    'produto_a_enviar' => $tecido->tecido_detalhes->descricao,
                                    'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                                    'codigo_produto' => $tecido->codigo_produto,
                                    'tipo' => 'tecido',
                                    'faccao_id' => encrypt($tecido->produto->servico_detalhes->faccao_id),
                                    'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                                    'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                                    'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                                    'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                                    'estoque' => [
                                        'estoque_rondonia' => [
                                            'codigo' => 3,
                                            'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                            'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                        ],
                                        'estoque_tocantins' => [
                                            'codigo' => 4,
                                            'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                            'estabelecimento' => $estabelecimentos[4],
                                        ],
                                        'estoque_matriz' => [
                                            'codigo' => 5,
                                            'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                            'estabelecimento' => $estabelecimentos[5],
                                        ],
                                        'estoque_almirante_2' => [
                                            'codigo' => 6,
                                            'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                            'estabelecimento' => $estabelecimentos[6],
                                        ],
                                    ],
                                ];
                            }
                        }
                    }
                }
            }
    
            $query_insumo = LancamentoProjetoInsumo::select();
            $query_insumo->with(['projeto_detalhes', 'produto']);
            $query_insumo->whereHas('projeto_detalhes', function($query) use($remessas_projetos){
                $query->whereIn('status', [4, 5]);
                $query->whereIn('id', $remessas_projetos->pluck('lancamento_projetos_id'));
            });
            $query_insumo->whereHas('produto', function($query) use($id_faccao){
                $query->wherehas('servico_detalhes', function($query) use($id_faccao){
                    $query->where('faccao_id', $id_faccao); 
                });
            });
    
            if(empty($fields['todos_produtos'])){
                $query_insumo->where('codigo_produto', 'ilike', $fields['codigo_produto']);
            }else if($fields['todos_produtos'] != "todos_produtos_projetos"){
                $query_insumo->where('lancamento_projetos_id', $id_projeto);
            }
    
            $result_insumo = $query_insumo->get();
    
            foreach($result_insumo as $insumo){
                if(!empty($itens[$insumo->lancamento_projetos_id][$insumo->codigo_produto])){
                    $itens[$insumo->lancamento_projetos_id][$insumo->codigo_produto]['qtde_a_enviar'] =  parserQtd($insumo->consumo_total + parserNumber($itens[$insumo->lancamento_projetos_id][$insumo->codigo_produto]['qtde_a_enviar']));
                }else{
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $insumo->codigo_produto)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($insumo){
                            $query->where('produto_codigo', $insumo->codigo_produto);
                        }])
                        ->where('faccaos_id', $insumo->produto->servico_detalhes->faccao_id)
                        ->where('lancamento_projetos_id', $insumo->lancamento_projetos_id)
                        ->get();
    
                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $insumo->consumo_total - $valor_remessa;
    
                    $estoque = $this->estoqueProduto($insumo->codigo_produto, "", false, true);
                            
                    $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                    $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                    $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                    $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                    $estoque_estabelecimento_origem = $estoque_matriz;
    
                    $estoque = [
                        'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                        'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                    ];
                    if(!empty($estoque['estoque'])){
                        $projetos [$insumo->lancamento_projetos_id] = [
                            'id_projeto' => encrypt($insumo->lancamento_projetos_id),
                            'numero_projeto' => $insumo->lancamento_projetos_id,
                            'nome_projeto' => $insumo->projeto_detalhes->nome_projeto,
                            'pedido' => $insumo->projeto_detalhes->pedido,
                            'cliente' => empty($insumo->projeto_detalhes->cliente)? '' : $insumo->projeto_detalhes->cliente->nome." - ".$insumo->projeto_detalhes->cliente->cpf_cnpj,
                            'estabelecimento' => $estabelecimentos[$insumo->projeto_detalhes->estabelecimento],
                            'codigo_estabelecimento' => 5,
                        ];
                        $itens [$insumo->lancamento_projetos_id][$insumo->codigo_produto] = [
                            'id_produto' => $insumo->id,
                            'faccao' => $insumo->produto->servico_detalhes->faccao->fornecedor->nome." - ".$insumo->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                            'produto_a_enviar' => $insumo->insumo_detalhes->descricao,
                            'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                            'codigo_produto' => $insumo->codigo_produto,
                            'tipo' => 'insumo',
                            'faccao_id' => encrypt($insumo->produto->servico_detalhes->faccao_id),
                            'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                            'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                            'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                            'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                            'estoque' => [
                                'estoque_rondonia' => [
                                    'codigo' => 3,
                                    'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                    'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                ],
                                'estoque_tocantins' => [
                                    'codigo' => 4,
                                    'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                    'estabelecimento' => $estabelecimentos[4],
                                ],
                                'estoque_matriz' => [
                                    'codigo' => 5,
                                    'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                    'estabelecimento' => $estabelecimentos[5],
                                ],
                                'estoque_almirante_2' => [
                                    'codigo' => 6,
                                    'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                    'estabelecimento' => $estabelecimentos[6],
                                ],
                            ],
                        ];                   
                    }
                }
            }
    
            $query_servico = LancamentoProjetoFaccao::select();
            $query_servico->whereNotNull('codigo_produto_acabado');
            $query_servico->with(['projeto']);
            $query_servico->whereHas('projeto', function($query) use($remessas_projetos){
                $query->whereIn('status', [4, 5]);
                $query->whereIn('id', $remessas_projetos->pluck('lancamento_projetos_id'));
            });
            $query_servico->whereHas('tecido.produto.servico_detalhes', function($query) use($id_faccao){
                $query->where('faccao_id', $id_faccao);
            });
    
            if(empty($fields['todos_produtos'])){
                $query_servico->where('codigo_produto_acabado', 'ilike', $fields['codigo_produto']);
            }else if($fields['todos_produtos'] != "todos_produtos_projetos"){
                $query_servico->where('lancamento_projetos_id', $id_projeto);
            }
    
            $result_servico = $query_servico->get();
    
            foreach($result_servico as $servico){
                if(!empty($itens[$servico->lancamento_projetos_id][$servico->codigo_produto_acabado])){
                    $itens[$servico->lancamento_projetos_id][$servico->codigo_produto_acabado]['qtde_a_enviar'] =  parserQtd($servico->quantidade + parserNumber($itens[$servico->lancamento_projetos_id][$servico->codigo_produto_acabado]['qtde_a_enviar']));
                }else{
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $servico->codigo_produto_acabado)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($servico){
                            $query->where('produto_codigo', $servico->codigo_produto_acabado);
                        }])
                        ->where('faccaos_id', $servico->faccao_id)
                        ->where('lancamento_projetos_id', $servico->lancamento_projetos_id)
                        ->get();
    
                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $servico->quantidade - $valor_remessa;
    
                    $estoque = $this->estoqueProduto($servico->codigo_produto_acabado, "", false, true);
                            
                    $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                    $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                    $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                    $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                    $estoque_estabelecimento_origem = $estoque_matriz;
    
                    $estoque = [
                        'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                        'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                    ];
                    if(!empty($estoque['estoque']) && empty($servico->tecido->produto->servico_detalhes->codigo_produto_acabado)){
                        $projetos[$servico->lancamento_projetos_id] = [
                            'id_projeto' => encrypt($servico->lancamento_projetos_id),
                            'numero_projeto' => $servico->lancamento_projetos_id,
                            'nome_projeto' => $servico->projeto->nome_projeto,
                            'pedido' => $servico->projeto->pedido,
                            'cliente' => empty($servico->projeto->cliente)? '' : $servico->projeto->cliente->nome." - ".$servico->projeto->cliente->cpf_cnpj,
                            'estabelecimento' => $estabelecimentos[$servico->projeto->estabelecimento],
                            'codigo_estabelecimento' => 5,
                        ];
                        $itens[$servico->lancamento_projetos_id][$servico->codigo_produto_acabado] = [
                            'id_produto' => $servico->id,
                            'faccao' => $servico->tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$servico->tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                            'produto_a_enviar' => $servico->produto_acabado->descricao,
                            'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                            'codigo_produto' => $servico->codigo_produto_acabado,
                            'tipo' => 'servico',
                            'faccao_id' => encrypt($servico->tecido->produto->servico_detalhes->faccao_id),
                            'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                            'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                            'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                            'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                            'estoque' => [
                                'estoque_rondonia' => [
                                    'codigo' => 3,
                                    'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                    'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                ],
                                'estoque_tocantins' => [
                                    'codigo' => 4,
                                    'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                    'estabelecimento' => $estabelecimentos[4],
                                ],
                                'estoque_matriz' => [
                                    'codigo' => 5,
                                    'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                    'estabelecimento' => $estabelecimentos[5],
                                ],
                                'estoque_almirante_2' => [
                                    'codigo' => 6,
                                    'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                    'estabelecimento' => $estabelecimentos[6],
                                ],
                            ],
                        ];
    
                        
                    }
                }
            }
        }else{
            $query_pedido_item = PedidoItemPortal::select();
            $query_pedido_item->whereIn('pedido', $remessas_projetos->pluck('pedido_id'));
            $query_pedido_item->where('faccaos_id', $id_faccao); ;
    
            if(empty($fields['todos_produtos'])){
                $query_pedido_item->where('codigo_tecidos_base', 'ilike', $fields['codigo_produto']);
            }else if($fields['todos_produtos'] != "todos_produtos_projetos"){
                $query_pedido_item->where('pedido', $fields['pedido']);
            }
    
            $result_pedido_item = $query_pedido_item->get();
    
            foreach($result_pedido_item as $item){
                if(!empty($itens[$item->pedido][$item->codigo_tecidos_base])){
                    $itens[$item->pedido][$item->codigo_tecidos_base]['qtde_a_enviar'] =  parserQtd($item->quantidade + parserNumber($itens[$item->pedido][$item->codigo_tecidos_base]['qtde_a_enviar']));
                }else{
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $item->codigo_tecidos_base)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($item){
                            $query->where('produto_codigo', $item->codigo_tecidos_base);
                        }])
                        ->where('faccaos_id', $item->faccao_id)
                        ->where('pedido_id', $item->pedido)
                        ->get();
    
                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $item->quantidade - $valor_remessa;
    
                    $estoque = $this->estoqueProduto($item->codigo_tecidos_base, "", false, true, $item->pedido);
                            
                    $estoque_tocantins = empty($estoque["04"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["04"];
                    $estoque_rondonia = empty($estoque["03"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["03"];                        
                    $estoque_almirante_2 = empty($estoque["06"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["06"];
                    $estoque_matriz = empty($estoque["05"])? ['estoque' => 0, 'estoque_compras' => 0] : $estoque["05"];
    
                    $estoque_estabelecimento_origem = $estoque_matriz;
    
                    $estoque = [
                        'estoque' => $estoque_tocantins['estoque'] + $estoque_rondonia['estoque']+ $estoque_almirante_2['estoque'] + $estoque_matriz['estoque'],
                        'estoque_compras' => $estoque_tocantins['estoque_compras'] + $estoque_rondonia['estoque_compras']+ $estoque_almirante_2['estoque_compras'] + $estoque_matriz['estoque_compras'],
                    ];
                    if(!empty($estoque['estoque'])){
                        $projetos[$item->pedido] = [
                            'id_projeto' => encrypt(''),
                            'numero_projeto' => $item->pedido,
                            'nome_projeto' => '',
                            'pedido' => $item->pedido,
                            'cliente' => empty($item->pedido_portal->cliente)? '' : $item->pedido_portal->cliente->nome." - ".$item->pedido_portal->cliente->cpf_cnpj,
                            'estabelecimento' => $estabelecimentos[5],
                            'codigo_estabelecimento' => 5,
                        ];
                        $itens [$item->pedido][$item->codigo_tecidos_base] = [
                            'id_produto' => $item->id,
                            'faccao' => $item->detalhesFaccao->fornecedor->nome." - ".$item->detalhesFaccao->fornecedor->cnpj_cpf,
                            'produto_a_enviar' => $item->tecidosBase->descricao,
                            'qtde_a_enviar' => parserQtd($qtde_a_enviar),
                            'codigo_produto' => $item->codigo_tecidos_base,
                            'tipo' => 'pedido',
                            'faccao_id' => encrypt($item->faccao_id),
                            'estoque_geral' => empty($estoque['estoque'])? '' : parserQtd($estoque['estoque']),
                            'estoque_compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                            'estoque_estabelecimento_origem' => empty($estoque_estabelecimento_origem['estoque'])? 0 : $estoque_estabelecimento_origem['estoque'],
                            'estoque_estabelecimento_origem_descr' => empty($estoque_estabelecimento_origem['estoque'])? '' : parserQtd($estoque_estabelecimento_origem['estoque']),
                            'estoque' => [
                                'estoque_rondonia' => [
                                    'codigo' => 3,
                                    'valor' => empty($estoque_rondonia['estoque'])? '' : parserQtd($estoque_rondonia['estoque']),
                                    'estabelecimento' => empty($estoque_rondonia['estoque'])? '' : $estabelecimentos[3],
                                ],
                                'estoque_tocantins' => [
                                    'codigo' => 4,
                                    'valor' => empty($estoque_tocantins['estoque'])? '' : parserQtd($estoque_tocantins['estoque']),
                                    'estabelecimento' => $estabelecimentos[4],
                                ],
                                'estoque_matriz' => [
                                    'codigo' => 5,
                                    'valor' => empty($estoque_matriz['estoque'])? '' : parserQtd($estoque_matriz['estoque']),
                                    'estabelecimento' => $estabelecimentos[5],
                                ],
                                'estoque_almirante_2' => [
                                    'codigo' => 6,
                                    'valor' => empty($estoque_almirante_2['estoque'])? '' : parserQtd($estoque_almirante_2['estoque']),
                                    'estabelecimento' => $estabelecimentos[6],
                                ],
                            ],
                        ];                   
                    }
                }
            }
        }
 
        foreach($itens as $key_projeto => $projeto){
            foreach($projeto as $key_produto => $produto){
                if(parserNumber($itens[$key_projeto][$key_produto]['qtde_a_enviar']) <= 0){
                    unset($itens[$key_projeto][$key_produto]);
                }
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'projetos' => $projetos,
                'itens' => $itens,
                'estabelecimentos' => $estabelecimentos
            ]
        ];
        return response()->json($response);
    }

    public function gerarRemessa(GerarRemessaRequest $request){
        $fields = $request->only('itens', 'id_faccao', 'transf_itens', 'projetos_transportadoras', 'projetos_estabelecimentos_transferencia', 'pedido');

        $projetos_transportadoras = $fields['projetos_transportadoras'];
        $projetos = $fields['itens'];
        if(!empty($fields['transf_itens'])){
            $transf_itens = $fields['transf_itens'];
        }else{
            $transf_itens = [];
        }

        if(!empty($fields['projetos_estabelecimentos_transferencia'])){
            $projetos_estabelecimentos_transferencia = $fields['projetos_estabelecimentos_transferencia'];
        }else{
            $projetos_estabelecimentos_transferencia = [];
        }
        
        try{
            $id_faccao = decrypt($fields['id_faccao']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $mensagem = "";
        $errors = [];

        foreach($projetos_estabelecimentos_transferencia as $projeto => $estabelecimentos){
            foreach($estabelecimentos as $estabelecimento){
                switch($estabelecimento){
                    case 3:
                        if(empty($projetos_transportadoras[$projeto]['transportadora_rondonia'])){
                            $mensagem = "Campos inválidos";
                            $errors["transportadora_nome_rondonia-"] = [
                                "mensagem" => "O campo Transportadora Transferência Rondonia é obrigatório.",
                                "projeto" => $projeto
                            ];
                        }else{
                            $verificar_transportador = TransportadorNasajon::select('codigo')
                            ->whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj)) ilike '".trim($projetos_transportadoras[$projeto]['transportadora_rondonia'])."'")
                            ->where('bloqueado', false)
                            ->first();

                            if(empty($verificar_transportador)){
                                $mensagem = "Campos inválidos";
                                $errors["transportadora_nome_rondonia-"] = [
                                    "mensagem" => "Transportadora Transferência Rondonia não encontrada.",
                                    "projeto" => $projeto
                                ];
                            }else{
                                $projetos_transportadoras[$projeto]['transportadora_rondonia'] = $verificar_transportador->codigo;
                            }
                        }
                        break;
                    case 4:
                        if(empty($projetos_transportadoras[$projeto]['transportadora_tocantins'])){
                            $mensagem = "Campos inválidos";
                            $errors["transportadora_nome_tocantins-"] = [
                                "mensagem" => "O campo Transportadora Transferência Tocantins é obrigatório.",
                                "projeto" => $projeto
                            ];
                        }else{
                            $verificar_transportador = TransportadorNasajon::select('codigo')
                            ->whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj)) ilike '".trim($projetos_transportadoras[$projeto]['transportadora_tocantins'])."'")
                            ->where('bloqueado', false)
                            ->first();

                            if(empty($verificar_transportador)){
                                $mensagem = "Campos inválidos";
                                $errors["transportadora_nome_tocantins-"] = [
                                    "mensagem" => "Transportadora Transferência Tocantins não encontrada.",
                                    "projeto" => $projeto
                                ];
                            }else{
                                $projetos_transportadoras[$projeto]['transportadora_tocantins'] = $verificar_transportador->codigo;
                            }
                        }
                        break;
                    case 5:
                        if(empty($projetos_transportadoras[$projeto]['transportadora_matriz'])){
                            $mensagem = "Campos inválidos";
                            $errors["transportadora_nome_matriz-"] = [
                                "mensagem" => "O campo Transportadora Transferência Matriz é obrigatório.",
                                "projeto" => $projeto
                            ];
                        }else{
                            $verificar_transportador = TransportadorNasajon::select('codigo')
                            ->whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj)) ilike '".trim($projetos_transportadoras[$projeto]['transportadora_matriz'])."'")
                            ->where('bloqueado', false)
                            ->first();

                            if(empty($verificar_transportador)){
                                $mensagem = "Campos inválidos";
                                $errors["transportadora_nome_matriz-"] = [
                                    "mensagem" => "Transportadora Transferência Matriz não encontrada.",
                                    "projeto" => $projeto
                                ];
                            }else{
                                $projetos_transportadoras[$projeto]['transportadora_matriz'] = $verificar_transportador->codigo;
                            }
                        }
                        break;
                    case 6:
                        if(empty($projetos_transportadoras[$projeto]['transportadora_almirante_2'])){
                            $mensagem = "Campos inválidos";
                            $errors["transportadora_nome_almirante_2-"] = [
                                "mensagem" => "O campo Transportadora Transferência Almirante 2 é obrigatório.",
                                "projeto" => $projeto
                            ];
                        }else{
                            $verificar_transportador = TransportadorNasajon::select('codigo')
                            ->whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj)) ilike '".trim($projetos_transportadoras[$projeto]['transportadora_almirante_2'])."'")
                            ->where('bloqueado', false)
                            ->first();

                            if(empty($verificar_transportador)){
                                $mensagem = "Campos inválidos";
                                $errors["transportadora_nome_almirante_2-"] = [
                                    "mensagem" => "Transportadora Transferência Almirante 2 não encontrada.",
                                    "projeto" => $projeto
                                ];
                            }else{
                                $projetos_transportadoras[$projeto]['transportadora_almirante_2'] = $verificar_transportador->codigo;
                            }
                        }
                        break; 
                }
            }    
        }

        foreach($projetos as $projeto => $itens){
            if(empty($projetos_transportadoras[$projeto]['transportadora'])){
                $mensagem = "Campos inválidos";
                $errors["transportadora_nome-"] = [
                    "mensagem" => "O campo Transportadora é obrigatório.",
                    "projeto" => $projeto,
                ];
            }else{
                $verificar_transportador = TransportadorNasajon::select('codigo')
                ->whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj)) ilike '".trim($projetos_transportadoras[$projeto]['transportadora'])."'")
                ->where('bloqueado', false)
                ->first();

                if(empty($verificar_transportador)){
                    $mensagem = "Campos inválidos";
                    $errors["transportadora_nome-"] = [
                        "mensagem" => "Transportadora não encontrada.",
                        "projeto" => $projeto,
                    ];
                }else{
                    $projetos_transportadoras[$projeto]['transportadora'] = $verificar_transportador->codigo;
                }
            }
        }

        $verificar_transf_itens = [];
        $itens_transferencia = [];
        $pedidos = [];

        foreach($transf_itens as $projeto => $itens){
            foreach($itens as $item){
                if(strcasecmp($item['tipo'], 'tecido') == 0){
                    $valor_unitario = LancamentoProjetoTecido::select()->where('codigo_produto',$item['codigo_produto'])->where('lancamento_projetos_id', $projeto)->first()->custo_unitario;
                }else if(strcasecmp($item['tipo'], 'insumo') == 0){
                    $valor_unitario = LancamentoProjetoInsumo::select()->where('codigo_produto',$item['codigo_produto'])->where('lancamento_projetos_id', $projeto)->first()->custo_unitario;
                }else if(strcasecmp($item['tipo'], 'pedido') == 0){
                    $valor_unitario = 2.87248982644;
                }else{
                    $servico = LancamentoProjetoFaccao::select()->where('codigo_produto_acabado',$item['codigo_produto'])->where('lancamento_projetos_id', $projeto)->first();
                    $valor_unitario = $servico->custo_unitario + $servico->tecido->custo_unitario;
                }
                $valor_total = parserNumber($item['quantidade_a_enviar']) * $valor_unitario;

                if(empty($itens_transferencia[$projeto][$item['estabelecimento_transferencia']][$item['estabelecimento_origem']])){
                    $itens_transferencia[$projeto] = [
                        $item['estabelecimento_transferencia'] => [
                            $item['estabelecimento_origem'] => [
                                "total" => $valor_total,
                                [
                                'codigo_produto' => $item['codigo_produto'],
                                'quantidade' => parserNumber($item['quantidade_transferencia']),
                                'valor_unitario' => $valor_unitario,
                                'valor_total' => $valor_total
                                ]
                            ]
                        ],
                    ];
                    $pedidos[$projeto] = $item['pedido'];
                }else{
                    $itens_transferencia[$projeto][$item['estabelecimento_transferencia']][$item['estabelecimento_origem']][]=[
                        'codigo_produto' => $item['codigo_produto'],
                        'quantidade' => parserNumber($item['quantidade_transferencia']),
                        'valor_unitario' => $valor_unitario,
                        'valor_total' => $valor_total
                    ];
                    $itens_transferencia[$projeto][$item['estabelecimento_transferencia']][$item['estabelecimento_origem']]['total'] = $valor_total + $itens_transferencia[$projeto][$item['estabelecimento_transferencia']][$item['estabelecimento_origem']]['total'];
                }

                if(empty($verificar_transf_itens[$projeto][$item['codigo_produto']])){
                    $verificar_transf_itens[$projeto][$item['codigo_produto']] = [
                        'quantidade' => parserNumber($item['quantidade_transferencia']),
                    ];
                }
            }
        }

        foreach ($projetos as $projeto => $itens){
            foreach($itens as $item){
                $estoque = $this->estoqueProduto($item['codigo_produto'], str_pad($item['estabelecimento'], 2, '0', STR_PAD_LEFT));
                if(parserNumber($item['quantidade_a_enviar']) > $estoque['estoque']){
                    if(empty($verificar_transf_itens[$projeto][$item['codigo_produto']])){
                        $errors ["estabelecimento-"] =
                            [ 
                                "mensagem" => "O campo Estabelecimento é obrigatório.",
                                "projeto" => $projeto,
                                "codigo_produto" => $item['codigo_produto']
                            ];
                        $mensagem = 'Há produto(s) que precisa(m) de transferência.';
                    }else{
                        if($verificar_transf_itens[$projeto][$item['codigo_produto']]['quantidade'] <= 0){
                            $errors ["qtde_transf-"] =
                            [ 
                                "mensagem" => "O campo Quantidade Transferência é obrigatório.",
                                "projeto" => $projeto,
                                "codigo_produto" => $item['codigo_produto']
                            ];
                            $mensagem =  'Há produto(s) que quantidade de transferência esta(ão) zerado(s).';
                        }
                    }
                }
            }
        }

        if(!empty($mensagem)){
            return response()->json([
                'status' => 'error',
                'message' => $mensagem,
                'error' => $errors,
                'response' => []
            ],422);
        }

        $pedidos_remessas = [];
        $pedidos_remessas_projeto = [];
        foreach ($projetos as $projeto => $itens){
            if($itens[0]['tipo'] !== 'pedido'){
                $lancamentoProjetoObj = LancamentoProjeto::find($projeto);
                $faccaoObj = Faccao::find($id_faccao);

                $pedido_remessa_ok = $this->geracaoRemessaNasajon($lancamentoProjetoObj, $faccaoObj, $itens, $projetos_transportadoras);
                
                if(!is_numeric($pedido_remessa_ok[0]['pedido_remessa'])){
                    return response()->json($pedido_remessa_ok[0]['pedido_remessa'], 422);
                }

                $pedidos_remessas[] = $pedido_remessa_ok[0]['pedido_remessa'];

                $pedidos_remessas_projeto[$projeto] = [
                    'id' => $pedido_remessa_ok[0]['id_remessa'] ,
                    'pedido_remessa' => $pedido_remessa_ok[0]['pedido_remessa']
                ];

                $pedidos_remessas_habilitar[] = [
                    'pedido' => $pedido_remessa_ok[0]['pedido_remessa'],
                    'projeto' => $lancamentoProjetoObj->id, 
                    'estabelecimento' => str_pad($lancamentoProjetoObj->estabelecimento, 2, '0', STR_PAD_LEFT),
                    'itens' => $itens
                ]; 
            }else{
                $PedidoPortalObj = PedidoPortal::find($projeto);
                $faccaoObj = Faccao::find($id_faccao);

                $pedido_remessa_ok = $this->geracaoRemessaNasajonPedido($PedidoPortalObj, $faccaoObj, $itens, $projetos_transportadoras);
                
                if(!is_numeric($pedido_remessa_ok[0]['pedido_remessa'])){
                    return response()->json($pedido_remessa_ok[0]['pedido_remessa'], 422);
                }

                $pedidos_remessas[] = $pedido_remessa_ok[0]['pedido_remessa'];

                $pedidos_remessas_projeto[$projeto] = [
                    'id' => $pedido_remessa_ok[0]['id_remessa'] ,
                    'pedido_remessa' => $pedido_remessa_ok[0]['pedido_remessa']
                ];

                $pedidos_remessas_habilitar[] = [
                    'pedido' => $pedido_remessa_ok[0]['pedido_remessa'],
                    'projeto' => $PedidoPortalObj->id, 
                    'estabelecimento' => '05',
                    'itens' => $itens
                ]; 
            }
            
        }

        $pedidos_transferencia = [];
        if(!empty($itens_transferencia)){
            $pedidos_transferencia = $this->gerarPedidoTransferencia($itens_transferencia, $projetos_transportadoras, $pedidos_remessas_projeto, $pedidos);
        }

        $usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;

        foreach($pedidos_remessas_habilitar as $pedido_remessa){
            $pedidosVendaNasajonObj = PedidosVendaNasajon::select()
                ->where('numero', $pedido_remessa['pedido'])
                ->where('estabelecimento_codigo', '05')
                ->where('operacao_codigo', "PEDINDUSTRIA")
                ->first(); 
            $sql_api_validacao = "select * from integracoes.api_pedidovenda_processar('".$pedidosVendaNasajonObj->id."', '".$usuario_cadastro_uuid."')";
            try{
                $insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
            }catch(\Exception $e){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }

            if($itens[0]['tipo'] !== 'pedido'){
                $remessaProdutoObj = RemessaProduto::select()
                    ->where('pedido_compra_numero', $pedido_remessa['pedido'])
                    ->where('lancamento_projetos_id', $pedido_remessa['projeto'])
                    ->orderBy('id', 'desc')
                    ->first(); 

                $this->removerQuantidadeEnviadaNecessidadeCompras($pedido_remessa['itens'], $remessaProdutoObj->id);
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                "transferencia" => $pedidos_transferencia,
                "remessa" => $pedidos_remessas
            ]
        ];
        return response()->json($response);
    }

    public function geracaoRemessaNasajon(LancamentoProjeto $Projeto, Faccao $Faccao, $itens, $transportadoras){
        $estabelecimento = '05';

        $query_projeto_faccao = LancamentoProjetoFaccao::select('pedido_compras_gerado_nasajon');
        $query_projeto_faccao->where('lancamento_projetos_id', $Projeto->id);
        $query_projeto_faccao->where('faccao_id', $Faccao->id);
        $query_projeto_faccao->whereNotNull('pedido_compras_gerado_nasajon');
        $query_projeto_faccao->distinct();
        $result_projeto_faccao = $query_projeto_faccao->get();

        $ordem_de_compra = "";
        foreach($result_projeto_faccao as $projeto_faccao){
            if(!empty($ordem_de_compra)){
                $ordem_de_compra = $ordem_de_compra.", ";
            }
            $ordem_de_compra = $ordem_de_compra.$projeto_faccao->pedido_compras_gerado_nasajon;
        }

        $fornecedor_cnpj = $Faccao->fornecedor->cnpj_cpf;
        $clienteNasajonObj = ClienteNasajon::where('cpf_cnpj', $fornecedor_cnpj)->first();
        $cliente_uuid = "'".$clienteNasajonObj->id."'::uuid";

        $buscaNasajon = VendedorNasajon::select()->where('codigo', '001')->first();
        $vendedor = "'".$buscaNasajon->id."'";

        $observacao_nota = "";

        $observacao = "";
        if(!empty($ordem_de_compra)){
            $observacao_nota = "Ordem de Compra: ".$ordem_de_compra." ";
        }
        if(!empty($Projeto->pedido)){
            $observacao_nota = $observacao_nota."Pedido: ".$Projeto->pedido." ";
        }
        $observacao_nota = $observacao_nota."Projeto: ".$Projeto->id." - ".$Projeto->nome_projeto;

        $observacao = $observacao_nota;

        $formapagamento_uuid = "null";

        $parcelamento_uuid = "null";

        $valor_total = 0.0;

        $desconto = 0.0;

        
        $busca_transportadora = TransportadorNasajon::select()->where('codigo', $transportadoras[$Projeto->id]['transportadora'])->first();

        $transportadora_uuid = "'".$busca_transportadora->id."'";

        $transportadora_redespacho_uuid = 'NULL';

        $tipo_frete = "0";
		if(strtoupper($busca_transportadora->nome) == 'RETIRA'){
			$tipo_frete = '4';
		}

        $valor_frete = 0.0;

        $usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;

        $indicador_pagamento = '0';

        if(empty($Faccao->fornecedor->uf)){
            return response()->json([
                'status' => 'error',
                'message' => 'O cadastro desta facção está incompleto e não possui UF. Favor verificar com o setor responsável.',
                'error' => [],
                'response' => []
            ],422);
        }else if(strcasecmp($Faccao->fornecedor->uf, "SP") == 0 || strcasecmp($Faccao->fornecedor->uf, "TO") == 0){
            $cfop = "5901";
        }else{
            $cfop = "6901";
        }

        $tipooperacao = "23";

        $numero_pedido_cliente = $Projeto->pedido;

        $modo_compra = "0";

        $cliente_conta_e_ordem_uuid = 'null';

        $operacao = "";
        $codigo_operacao = "PEDINDUSTRIA";
        $operacao = OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao;
        $operacao = "'".$operacao."'";

        $porcentagem_comissao = 0;

        $data_pedido = date('Y-m-d');

        $sql_api_insercao = "select * from integracoes.api_pedidovendanovo (\n".
            "uuid_generate_v4(),\n".
            "current_date,\n".
            "(select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}'),\n".
            "{$cliente_uuid},\n".
            "{$vendedor},\n".
            "'{$observacao}',\n".
            "{$formapagamento_uuid},\n".
            "{$parcelamento_uuid},\n".
            "{$valor_total},\n".
            "{$desconto},\n".
            "{$transportadora_uuid},\n".
            "{$transportadora_redespacho_uuid},\n".
            "{$tipo_frete},\n".
            "{$valor_frete},\n".
            "'{$usuario_cadastro_uuid}',\n".
            "{$indicador_pagamento},\n".
            "'{$cfop}',\n".
            "{$tipooperacao},\n".
            "'{$numero_pedido_cliente}',\n".
            "{$modo_compra},\n".
            "{$cliente_conta_e_ordem_uuid},\n".
            "{$operacao},\n".
            "{$porcentagem_comissao},\n".
            "'{$data_pedido}',\n".
            "'{$observacao_nota}'\n".
        ");";

        try{
            $insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
        }catch(\Exception $e){

            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e, $sql_api_insercao],
                'response' => []
            ];
        }

        $mensagem_nasajon = $insert_nasajon[0]->mensagem;
        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
                'response' => []
            ];
        }
        $pedido_uuid = $mensagem_nasajon['mensagem'];

        foreach($itens as $key => $item){
            $produto = ProdutoEspecificacao::find($item['codigo_produto']);
            $produto_uuid = "'".$produto->produtoNasajon->produto."'";
            $quantidade = parserNumber($item['quantidade_a_enviar']);
            $unidade_uuid = DB::connection('nasajon')->select("select unidade_id from estoque.vwunidades where unidade_codigo = '{$produto->unidade}' and estabelecimento_id = (select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}')")[0]->unidade_id;
            $unidade_uuid = "'".$unidade_uuid."'";

            $produtosCustoObj = $produto->custos->where('estabelecimento', $estabelecimento)->first();

            $custoObj = $produto->estoque->where('estabelecimento', '05')->first();
            $custo_portal = $produto->custos->where('estabelecimento', '05')->first();
            if(empty($custoObj) && empty($custo_portal)){
                $custoObj = $produto->preco->preco_real / 1.43;
            }else if(!empty($custoObj) && !empty($custo_portal)){
                if($custoObj->custo > $custo_portal->custo_medio_contabil){
                    $valor_unitario = $custoObj->custo;
                }else if(empty($custoObj->custo) && empty($custo_portal->custo_medio_contabil)){
                    $valor_unitario = $produto->preco->preco_real / 1.43;
                }else{
                    $valor_unitario = $custo_portal->custo_medio_contabil;
                }
            }else if(!empty($custoObj) && empty($custo_portal)){
                $valor_unitario = empty($custoObj->custoObj)? $produto->preco->preco_real / 1.43 : $custoObj->custo;
            }else if(empty($custoObj) && !empty($custo_portal)){
                $valor_unitario = empty($custo_portal->custo_medio_contabil)? $produto->preco->preco_real / 1.43 : $custo_portal->custo_medio_contabil;
            }else{
                $valor_unitario = $custoObj->custo;
            }
            
            if($valor_unitario > $produto->preco->preco_real){
                if($custoObj->custo > $custo_portal->custo_medio_gerencial){
                    $valor_unitario = $custoObj->custo;
                }else if(empty($custoObj->custo) && empty($custo_portal->custo_medio_gerencial)){
                    $valor_unitario = $produto->preco->preco_real / 1.43;
                }else{
                    $valor_unitario = $custo_portal->custo_medio_gerencial;
                }
            }

            $itens[$key]['valor_unitario'] = $valor_unitario;

            $valor_desconto = floatval(0);
            $sql_api_insercao_itens = "select * from integracoes.api_pedidovenda_itemnovo (
                uuid_generate_v4(),
                '{$pedido_uuid}',
                {$produto_uuid},
                {$quantidade},
                {$unidade_uuid},
                {$valor_unitario},
                {$valor_desconto},
                '{$cfop}'
            );";
            try{
                $insercao_produto = DB::connection('nasajon')->select($sql_api_insercao_itens);
            }catch(\Exception $e){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }
            $mensagem_nasajon = json_decode($insercao_produto[0]->mensagem, true);
            if($mensagem_nasajon['codigo'] !== 'OK'){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [$mensagem_nasajon['mensagem']],
                    'response' => []
                ];
            }
        }

        $PedidosVendaNasajonObj = PedidosVendaNasajon::find($pedido_uuid);


        $mensagem_nasajon = json_decode($insert_nasajon[0]->mensagem, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem']],
                'response' => []
            ];
        }

        $itens = $this->gravarRemessa($Projeto, $Faccao, $itens, $PedidosVendaNasajonObj->numero, $pedido_uuid, $codigo_operacao, $cfop, $PedidosVendaNasajonObj->numero);
        
        return $itens;
    }

    public function geracaoRemessaNasajonPedido(PedidoPortal $Pedido, Faccao $Faccao, $itens, $transportadoras){
        $estabelecimento = '05';

        $ordem_de_compra = "";

        $fornecedor_cnpj = $Faccao->fornecedor->cnpj_cpf;
        $clienteNasajonObj = ClienteNasajon::where('cpf_cnpj', $fornecedor_cnpj)->first();
        $cliente_uuid = "'".$clienteNasajonObj->id."'::uuid";

        $buscaNasajon = VendedorNasajon::select()->where('codigo', '001')->first();
        $vendedor = "'".$buscaNasajon->id."'";

        $observacao_nota = "";

        $observacao = "";
        if(!empty($ordem_de_compra)){
            $observacao_nota = "Ordem de Compra: ".$ordem_de_compra." ";
        }

        $observacao_nota = $observacao_nota."Pedido: ".$Pedido->id;

        $observacao = $observacao_nota;

        $formapagamento_uuid = "null";

        $parcelamento_uuid = "null";

        $valor_total = 0.0;

        $desconto = 0.0;

        
        $busca_transportadora = TransportadorNasajon::select()->where('codigo', $transportadoras[$Pedido->id]['transportadora'])->first();

        $transportadora_uuid = "'".$busca_transportadora->id."'";

        $transportadora_redespacho_uuid = 'NULL';

        $tipo_frete = "0";
		if(strtoupper($busca_transportadora->nome) == 'RETIRA'){
			$tipo_frete = '4';
		}

        $valor_frete = 0.0;

        $usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;

        $indicador_pagamento = '0';

        if(empty($Faccao->fornecedor->uf)){
            return response()->json([
                'status' => 'error',
                'message' => 'O cadastro desta facção está incompleto e não possui UF. Favor verificar com o setor responsável.',
                'error' => [],
                'response' => []
            ],422);
        }else if(strcasecmp($Faccao->fornecedor->uf, "SP") == 0 || strcasecmp($Faccao->fornecedor->uf, "TO") == 0){
            $cfop = "5901";
        }else{
            $cfop = "6901";
        }

        $tipooperacao = "23";

        $numero_pedido_cliente = '';

        $modo_compra = "0";

        $cliente_conta_e_ordem_uuid = 'null';

        $operacao = "";
        $codigo_operacao = "PEDINDUSTRIA";
        $operacao = OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao;
        $operacao = "'".$operacao."'";

        $porcentagem_comissao = 0;

        $data_pedido = date('Y-m-d');

        $sql_api_insercao = "select * from integracoes.api_pedidovendanovo (\n".
            "uuid_generate_v4(),\n".
            "current_date,\n".
            "(select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}'),\n".
            "{$cliente_uuid},\n".
            "{$vendedor},\n".
            "'{$observacao}',\n".
            "{$formapagamento_uuid},\n".
            "{$parcelamento_uuid},\n".
            "{$valor_total},\n".
            "{$desconto},\n".
            "{$transportadora_uuid},\n".
            "{$transportadora_redespacho_uuid},\n".
            "{$tipo_frete},\n".
            "{$valor_frete},\n".
            "'{$usuario_cadastro_uuid}',\n".
            "{$indicador_pagamento},\n".
            "'{$cfop}',\n".
            "{$tipooperacao},\n".
            "'{$numero_pedido_cliente}',\n".
            "{$modo_compra},\n".
            "{$cliente_conta_e_ordem_uuid},\n".
            "{$operacao},\n".
            "{$porcentagem_comissao},\n".
            "'{$data_pedido}',\n".
            "'{$observacao_nota}'\n".
        ");";

        try{
            $insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
        }catch(\Exception $e){

            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e, $sql_api_insercao],
                'response' => []
            ];
        }

        $mensagem_nasajon = $insert_nasajon[0]->mensagem;
        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
                'response' => []
            ];
        }
        $pedido_uuid = $mensagem_nasajon['mensagem'];

        foreach($itens as $key => $item){
            $produto = ProdutoEspecificacao::find($item['codigo_produto']);
            $produto_uuid = "'".$produto->produtoNasajon->produto."'";
            $quantidade = parserNumber($item['quantidade_a_enviar']);
            $unidade_uuid = DB::connection('nasajon')->select("select unidade_id from estoque.vwunidades where unidade_codigo = '{$produto->unidade}' and estabelecimento_id = (select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}')")[0]->unidade_id;
            $unidade_uuid = "'".$unidade_uuid."'";

            $produtosCustoObj = $produto->custos->where('estabelecimento', $estabelecimento)->first();

            if(empty($produtosCustoObj)){
                $valor_unitario = $produto->preco->preco_real / 1.43;
            }else if(empty($produtosCustoObj->custo_medio_contabil)){
                $valor_unitario = $produto->preco->preco_real / 1.43;
            }else{
                $valor_unitario = $produtosCustoObj->custo_medio_contabil;
            }
            $itens[$key]['valor_unitario'] = $valor_unitario;

            $valor_desconto = floatval(0);
            $sql_api_insercao_itens = "select * from integracoes.api_pedidovenda_itemnovo (
                uuid_generate_v4(),
                '{$pedido_uuid}',
                {$produto_uuid},
                {$quantidade},
                {$unidade_uuid},
                {$valor_unitario},
                {$valor_desconto},
                '{$cfop}'
            );";
            try{
                $insercao_produto = DB::connection('nasajon')->select($sql_api_insercao_itens);
            }catch(\Exception $e){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }
            $mensagem_nasajon = json_decode($insercao_produto[0]->mensagem, true);
            if($mensagem_nasajon['codigo'] !== 'OK'){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [$mensagem_nasajon['mensagem']],
                    'response' => []
                ];
            }
        }

        $PedidosVendaNasajonObj = PedidosVendaNasajon::find($pedido_uuid);


        $mensagem_nasajon = json_decode($insert_nasajon[0]->mensagem, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem']],
                'response' => []
            ];
        }

        $itens = $this->gravarRemessaPedido($Pedido, $Faccao, $itens, $PedidosVendaNasajonObj->numero, $pedido_uuid, $codigo_operacao, $cfop, $PedidosVendaNasajonObj->numero);
        
        return $itens;
    }

    public function gravarRemessa(LancamentoProjeto $Projeto, Faccao $Faccao, $itens, $pedido, $pedido_uuid, $codigo_operacao, $cfop, $pedido_remessa){
        $lancamentoProjetoControllerObj = new LancamentoProjetoController;

        foreach($itens as $key => $item){
            $remessaProdutoObj = new RemessaProduto;
            $remessaProdutoObj->estabelecimento_codigo = '05'; 
            $remessaProdutoObj->produto_codigo = $item['codigo_produto'];
            $remessaProdutoObj->tipo = $item['tipo'];
            $remessaProdutoObj->pedido_compra_numero = $pedido;
            $remessaProdutoObj->pedido_compra_numero_uuid =  $pedido_uuid;
            $remessaProdutoObj->operacao = $codigo_operacao;
            $remessaProdutoObj->cfop = $cfop;
            $remessaProdutoObj->preco = $item['valor_unitario'];
            $remessaProdutoObj->quantidade_enviada = parserNumber($item['quantidade_a_enviar']);
            $remessaProdutoObj->quantidade_total = parserNumber($item['saldo']);
            $remessaProdutoObj->data_envio = date('Y-m-d');
            $remessaProdutoObj->faccaos_id = $Faccao->id;
            $remessaProdutoObj->lancamento_projetos_id = $Projeto->id;
            $remessaProdutoObj->created_by = Auth::id();
            $remessaProdutoObj->save();

            $itens[$key]['id_remessa'] = $remessaProdutoObj->id;
            $itens[$key]['pedido_remessa'] = $pedido_remessa;

            $lancamentoProjetoControllerObj->gravarHistoricoProjeto($Projeto->id, 'pedido_remessa', "Pedido da Remessa:".$remessaProdutoObj->id." Projeto:".$Projeto->id, Auth::id()); 
        }

        $status = $this->verificarStatusProjeto($Projeto->id);
        $lancamentoProjetoObj = LancamentoProjeto::find($Projeto->id);
        $lancamentoProjetoObj->status = $status;
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();
        
        if($status == 5){
            $lancamentoProjetoControllerObj->gravarHistoricoProjeto($Projeto->id, 'envio_faccao', 'Projeto: '.$Projeto->id, Auth::id());
        }else if($status == 6){
            $lancamentoProjetoControllerObj->gravarHistoricoProjeto($Projeto->id, 'em_producao', 'Projeto: '.$Projeto->id, Auth::id());
            
            $remessaProjetoObj = EnvioProjetoFaccao::select()->where('lancamento_projetos_id', $Projeto->id);
            $remessaProjetoObj->deleted_by = Auth::id();
            $remessaProjetoObj->delete();
        }
        
        
        return $itens;
    }

    public function gravarRemessaPedido(PedidoPortal $Pedido, Faccao $Faccao, $itens, $pedido, $pedido_uuid, $codigo_operacao, $cfop, $pedido_remessa){
        $lancamentoProjetoControllerObj = new LancamentoProjetoController;

        foreach($itens as $key => $item){
            $remessaProdutoObj = new RemessaProduto;
            $remessaProdutoObj->estabelecimento_codigo = '05'; 
            $remessaProdutoObj->produto_codigo = $item['codigo_produto'];
            $remessaProdutoObj->tipo = $item['tipo'];
            $remessaProdutoObj->pedido_compra_numero = $pedido;
            $remessaProdutoObj->pedido_compra_numero_uuid =  $pedido_uuid;
            $remessaProdutoObj->operacao = $codigo_operacao;
            $remessaProdutoObj->cfop = $cfop;
            $remessaProdutoObj->preco = $item['valor_unitario'];
            $remessaProdutoObj->quantidade_enviada = parserNumber($item['quantidade_a_enviar']);
            $remessaProdutoObj->quantidade_total = parserNumber($item['saldo']);
            $remessaProdutoObj->data_envio = date('Y-m-d');
            $remessaProdutoObj->faccaos_id = $Faccao->id;
            $remessaProdutoObj->producao_pedido_id = $Pedido->id;
            $remessaProdutoObj->created_by = Auth::id();
            $remessaProdutoObj->save();

            $pedidoItemPortalObj = PedidoItemPortal::select();
            $pedidoItemPortalObj->where('pedido', $Pedido->id);
            $pedidoItemPortalObj->where('codigo_tecidos_base', $item['codigo_produto']);
            $pedidoItemPortalObj->orderBy('updated_at', 'desc');
            $pedidoItemPortalObj = $pedidoItemPortalObj->first();
            $pedidoItemPortalObj->pedido_remessa_uuid_nasajon = $pedido_uuid; 
            $pedidoItemPortalObj->save();

            $itens[$key]['id_remessa'] = $remessaProdutoObj->id;
            $itens[$key]['pedido_remessa'] = $pedido_remessa;

        }

        $pedidoItemPortalObj = PedidoItemPortal::select('codigo_tecidos_base', DB::raw('sum(quantidade) as quantidade'));
        $pedidoItemPortalObj->where('pedido', $Pedido->id);
        $pedidoItemPortalObj->groupBy('codigo_tecidos_base');
        $pedidoItemPortalObj = $pedidoItemPortalObj->get();
        $vazio = true;
        foreach($pedidoItemPortalObj as $item){
            $remessaProdutoObj = RemessaProduto::select(DB::raw('sum(quantidade_enviada) as quantidade'));
            $remessaProdutoObj->where('producao_pedido_id', $Pedido->id);
            $remessaProdutoObj->where('produto_codigo', $item->codigo_tecidos_base);
            $remessaProdutoObj = $remessaProdutoObj->first();

            if($remessaProdutoObj->quantidade < $item->quantidade){
                $vazio = false;
            }
        }

        if($vazio){
            $remessaProjetoObj = EnvioProjetoFaccao::select()->where('pedido_id', $Pedido->id);
            $remessaProjetoObj->deleted_by = Auth::id();
            $remessaProjetoObj->delete(); 
        }
       
        return $itens;
    }

    public function removerQuantidadeEnviadaNecessidadeCompras($itens, $id_remessa){
        foreach($itens as $item){
            $necessidadeComprasObj = NecessidadeCompras::select()->where('produto_codigo', $item['codigo_produto'])->first();

            if(!empty($necessidadeComprasObj)){
                $saldo = $necessidadeComprasObj->saldo - parserNumber($item['quantidade_a_enviar']);

                $necessidadeComprasObj->quantidade_enviada = parserNumber($item['quantidade_a_enviar']);
                $necessidadeComprasObj->saldo = $saldo;
                $necessidadeComprasObj->updated_by = Auth::id();
                $necessidadeComprasObj->save();

                $remessaProdutoObj = RemessaProduto::find($id_remessa);

                $remessaProdutoObj->necessidades_compras_id = $necessidadeComprasObj->id;
                $remessaProdutoObj->updated_by = Auth::id();
                $remessaProdutoObj->save();
            }
        }
    }

    public function gerarPedidoTransferencia($array, $transportadoras, $pedidos_remessas, $pedidos){
        $usuario = 1;

        $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;
        $lancamentoProjetoControllerObj = new LancamentoProjetoController;

        $retorno = [];

        foreach($array as $id_projeto => $estabelecimentos_transferencia){
            foreach($estabelecimentos_transferencia as $codigo_estabelecimento_transferencia => $estabelecimentos){
                foreach($estabelecimentos as $codigo_estabelecimento => $itens){
                    $estabelecimento_detalhes = NasajonEstabelecimento::select()->where('codigo', str_pad($codigo_estabelecimento, 2, '0', STR_PAD_LEFT))->first();
                    $clienteNasajon = ClienteNasajon::select()
                        ->where(function($query) use($estabelecimento_detalhes){
                            $query->orWhere('cpf_cnpj', $estabelecimento_detalhes->pessoa->cnpj);
                            $query->orWhere('codigo', $estabelecimento_detalhes->pessoa->cnpj);
                        })                        
                        ->where('bloqueado', false)->first();

                    $pedido_anterior = PedidoPortal::select()
                        ->where('estabelecimento', $codigo_estabelecimento_transferencia)
                        ->where('cod_cliente', $clienteNasajon->codigo)
                        ->where('status_pedido', 3)
                        ->orderBy('id', 'desc')
                        ->first();
    
                    if(!empty($pedido_anterior)){
                        $nome_comprador = $pedido_anterior->nome_comprador;
                    
                        $email_comprador = $pedido_anterior->email_comprador;
                    }else{
                        $nome_comprador = '';
                    
                        $email_comprador = '';
                    }

                    switch($codigo_estabelecimento_transferencia){
                        case 3:
                            $transportadora = $transportadoras[$id_projeto]['transportadora_rondonia'];
                            break;
                        case 4:
                            $transportadora = $transportadoras[$id_projeto]['transportadora_tocantins'];
                            break;
                        case 5:
                            $transportadora = $transportadoras[$id_projeto]['transportadora_matriz'];
                            break;
                        case 6:
                            $transportadora = $transportadoras[$id_projeto]['transportadora_almirante_2'];
                            break; 
                    }

                    $tipo_frete = 'P';
                    
                    $status = 2;
    
                    $nome_projeto = LancamentoProjeto::find($id_projeto);
                    if(empty($nome_projeto)){
                        $observacao = "REMESSA ".$pedidos_remessas[$id_projeto]['pedido_remessa']." PARA ".$clienteNasajon->nome." - ".$clienteNasajon->cpf_cnpj." ATENDER Pedido Produção ".$id_projeto;
                    }else{
                        $nome_projeto = $nome_projeto->nome_projeto;

                        $observacao = "REMESSA ".$pedidos_remessas[$id_projeto]['pedido_remessa']." PARA ".$clienteNasajon->nome." - ".$clienteNasajon->cpf_cnpj." ATENDER PROJETO ".$id_projeto." - ".$nome_projeto;
                    }

                   

                    if(!empty($pedidos[$id_projeto])){
                        $observacao = $observacao." PEDIDO ".$pedidos[$id_projeto];
                    }

                    if(strlen($observacao) > 240){
                        $observacao = substr($observacao, 0, 239);
                    }
    
                    $valor_total_produtos = $itens['total'];
    
                    $valor_total_nota = $itens['total'];
    
                    $aprovacaoDePedidoObj = new AprovacaoDePedidoController;
                    $codigo_operacao = "";
    
                    $tipo_venda = "pronta_entrega_venda";
    
                    $nasajon = true;
                    
                    $pedidoPortalObj = new PedidoPortal;
    
                    $pedidoPortalObj->data_pedido = date('Y-m-d');
                    $pedidoPortalObj->usuario = $usuario;
                    $pedidoPortalObj->cod_cliente = $clienteNasajon->codigo;
                    $pedidoPortalObj->nome_comprador = $nome_comprador;
                    $pedidoPortalObj->email_comprador = $email_comprador;
                    $pedidoPortalObj->status_pedido = $status;
                    $pedidoPortalObj->estabelecimento = $codigo_estabelecimento_transferencia;
                    $pedidoPortalObj->pedido_futuro = false;
                    $pedidoPortalObj->data_previsao_entrega = date('Y-m-d');
                    $pedidoPortalObj->tipo_frete = $tipo_frete;
                    $pedidoPortalObj->observacao = $observacao;
                    $pedidoPortalObj->transportadora = $transportadora;
                    $pedidoPortalObj->comissao = 0;
                    $pedidoPortalObj->created_by = Auth::id();
                    $pedidoPortalObj->base_icms = 0;
                    $pedidoPortalObj->valor_icms = 0;
                    $pedidoPortalObj->base_icmsst = 0;
                    $pedidoPortalObj->valor_icmsst = 0;
                    $pedidoPortalObj->valor_frete = 0;
                    $pedidoPortalObj->valor_seguro = 0;
                    $pedidoPortalObj->valor_desconto = 0;
                    $pedidoPortalObj->outros_valores = 0;
                    $pedidoPortalObj->valor_ipi = 0;
                    $pedidoPortalObj->valor_total_produtos = $valor_total_produtos;
                    $pedidoPortalObj->valor_total_nota = $valor_total_nota;
                    $pedidoPortalObj->codigo_operacao = $codigo_operacao;
                    $pedidoPortalObj->tipo_venda = $tipo_venda;
                    $pedidoPortalObj->nasajon = $nasajon;
                    if(!empty($nome_projeto)){
                        $pedidoPortalObj->projeto_id = $id_projeto;
                    }
                    $pedidoPortalObj->pedido_remessa_gerado = $pedidos_remessas[$id_projeto]['pedido_remessa'];
                    $pedidoPortalObj->save();
    
                    $pedido = $pedidoPortalObj->id;
    
                    unset($itens['total']);
                    foreach($itens as $produto){
                        $produtoObj = ProdutoEspecificacao::select()->where('codigo_produto', $produto['codigo_produto'])->first();
    
                        $valor_unitario = $this->getPrecoTransferencia($pedidoPortalObj, $produtoObj);
                        $valor_total = $produto['quantidade'] * $valor_unitario;

                        $pedido_item = new PedidoItemPortal;
    
                        $pedido_item->pedido = $pedido;
                        $pedido_item->usuario = Auth::user()->id;
                        $pedido_item->cod_produto = strtoupper($produto['codigo_produto']);
                        $pedido_item->quantidade = $produto['quantidade'];
                        $pedido_item->preco_unitario = $valor_unitario;
                        $pedido_item->created_by = Auth::id();
                        $pedido_item->valor_icms = 0;
                        $pedido_item->base_calculo_icms = 0;
                        $pedido_item->valor_ipi = 0;
                        $pedido_item->aliquota_icms = 0;
                        $pedido_item->aliquota_ipi = 0;
                        $pedido_item->valor_frete = 0;
                        $pedido_item->valor_total = round($valor_total,2);
                        $pedido_item->coluna = '0';
                        $pedido_item->comissao = 0;
                        $pedido_item->ipi_produto = 0;
                        $pedido_item->preco_base = empty($produtoObj->preco)? 0 : $produtoObj->preco->preco_real;
                        $pedido_item->coluna_a = 0;
                        $pedido_item->coluna_b = 0;
                        $pedido_item->coluna_c = 0;
    
                        $pedido_item->save();
                    }

                    $remessaProdutoObj = RemessaProduto::find($pedidos_remessas[$id_projeto]['id']);
                    $remessaProdutoObj->pedido_id = $pedidoPortalObj->id;
                    $remessaProdutoObj->updated_by = Auth::id();
                    $remessaProdutoObj->save();

                    if(!empty($nome_projeto)){
                        $lancamentoProjetoControllerObj->gravarHistoricoProjeto($id_projeto, 'pedido_transferencia', 'Pedido Transferência: '.$pedidoPortalObj->id." Pedido da Remessa:".$pedidos_remessas[$id_projeto]['id']." Projeto:".$id_projeto, Auth::id());
                    }
                }
                $aprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($pedidoPortalObj, []);

                $retorno[] = $pedidoPortalObj->pedido_gerado;

            }
        }

        return $retorno;
    }

    public function viewDetalhesPorProjeto(Request $request){
        $projeto_id = $request->only('projeto_id')['projeto_id'];

        $query = RemessaProduto::select();
        $query->where('lancamento_projetos_id', $projeto_id);
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $pedidos = [];

        foreach($result as $remessa){
            $query_pedido_remessa = PedidosVendaNasajon::select();
            $query_pedido_remessa->where('numero', $remessa->pedido_compra_numero);
            $query_pedido_remessa->where('estabelecimento_codigo', $remessa->projeto_detalhes->estabelecimento_pad);
            $result_pedido_remessa = $query_pedido_remessa->first();

            $pedidos [] = [
                'estabelecimento' => $estabelecimentos[intval($result_pedido_remessa->estabelecimento_codigo)],
                'numero_pedido' => $result_pedido_remessa->numero,
                'data_emissao' => parserData($result_pedido_remessa->emissao),
                'situacao' => $result_pedido_remessa->situacao_descricao,
                'fornecedor' => $result_pedido_remessa->cliente_detalhes->nome." - ".$result_pedido_remessa->cliente_detalhes->cpf_cnpj
            ];
        }

        return view('programs.remessa_itens.modal.detalhes_por_projeto')->with(['pedidos' => $pedidos]);
    }

    public function viewDetalhesItensPorProjeto(Request $request){
        $projeto_id = $request->only('projeto_id')['projeto_id'];

        $fornecedor_busca = [];
        $itens_remessa = [];

        $query_tecido = LancamentoProjetoTecido::select();
        $query_tecido->with(['projeto_detalhes' => function($query){
            $query->with(['cliente']);
        }, 
        'produto' => function($query){
            $query->with(['servico_detalhes'  => function($query){
                $query->with(['faccao' => function($query){
                    $query->with(['fornecedor']);
                }]);
            }]);
        }, 
        'servico_detalhes' => function($query){
            $query->with(['faccao']);
        }]);
        $query_tecido->whereHas('projeto_detalhes', function($query) use($projeto_id){
            $query->whereIn('status', [4, 5]);

            $query->where('id', $projeto_id);
        });

        $result_tecido = $query_tecido->get();

        foreach($result_tecido as $tecido){
            if(!empty($tecido->servico_detalhes)){
                if(!empty($itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto])){
                    if(empty($itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id])){
                        $valor_remessa = 0; 
                        $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                            ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                $query->where('produto_codigo', $tecido->codigo_produto);
                            }])
                            ->where('faccaos_id', $tecido->servico_detalhes->faccao_id)
                            ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                            ->get();

                        foreach($remessas as $remessa){
                            if(!empty($remessa->pedidoRemessaNasajon)){
                                if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                    if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                        $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                    }
                                }
                            }else{
                                $valor_remessa += $remessa->quantidade_enviada;
                            }
                        }
                        $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;

                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'])); 
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtd_pr']++;
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['remessa'] = $valor_remessa;
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total;
                    }else{
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($tecido->consumo_total + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar']));
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total + $retorno[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'];
                    }                
                }else{
                    $estoque = $this->estoqueProduto($tecido->codigo_produto, "", true);
                    $botao_remessa = empty($estoque['estoque'])? false : true;
                    $valor_remessa = 0; 
                        $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                            ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                $query->where('produto_codigo', $tecido->codigo_produto);
                            }])
                            ->where('faccaos_id', $tecido->servico_detalhes->faccao_id)
                            ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                            ->get();

                        foreach($remessas as $remessa){
                            if(!empty($remessa->pedidoRemessaNasajon)){
                                if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                    if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                        $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                    }
                                }
                            }else{
                                $valor_remessa += $remessa->quantidade_enviada;
                            }
                        }
                        $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;

                    $itens_remessa[$tecido->lancamento_projetos_id][$tecido->servico_detalhes->faccao_id][$tecido->codigo_produto] =[
                        'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                        'numero_projeto' => $tecido->lancamento_projetos_id,
                        'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                        'pedido' => $tecido->projeto_detalhes->pedido,
                        'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                        'faccao' => $tecido->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => $tecido->tecido_detalhes->descricao,
                        'qtde_a_enviar' => parserValor($tecido->consumo_total),
                        'estoque' => empty($estoque['estoque'])? '' : parserValor($estoque['estoque']),
                        'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'botao_remessa' => $botao_remessa,
                        'codigo_produto' => $tecido->codigo_produto,
                        'tipo' => 'tecido',
                        'faccao_id' => encrypt($tecido->servico_detalhes->faccao_id),
                        'qtd_pr' => 1,
                        $tecido->lancamento_projetos_id => [
                            'remessa' => $valor_remessa,
                            'quantidade' => $tecido->consumo_total
                        ]
                    ];
                }
            }else{
                if(!empty($itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto])){
                    if(empty($itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id])){
                        $valor_remessa = 0; 
                        $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                            ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                                $query->where('produto_codigo', $tecido->codigo_produto);
                            }])
                            ->where('faccaos_id', $tecido->produto->servico_detalhes->faccao_id)
                            ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                            ->get();

                        foreach($remessas as $remessa){
                            if(!empty($remessa->pedidoRemessaNasajon)){
                                if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                    if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                        $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                    }
                                }
                            }else{
                                $valor_remessa += $remessa->quantidade_enviada;
                            }
                        }
                        $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;

                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar']));
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtd_pr']++;
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['remessa'] = $valor_remessa;
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total;                            
                    }else{
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar'] =  parserValor($tecido->consumo_total + parserNumber($retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto]['qtde_a_enviar']));
                        $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'] = $tecido->consumo_total + $retorno[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto][$tecido->lancamento_projetos_id]['quantidade'];
                    }
                }else{
                    $estoque = $this->estoqueProduto($tecido->codigo_produto, "", true);
                    $botao_remessa = empty($estoque['estoque'])? false : true;
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $tecido->codigo_produto)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($tecido){
                            $query->where('produto_codigo', $tecido->codigo_produto);
                        }])
                        ->where('faccaos_id', $tecido->produto->servico_detalhes->faccao_id)
                        ->where('lancamento_projetos_id', $tecido->lancamento_projetos_id)
                        ->get();

                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $tecido->consumo_total - $valor_remessa;

                    $itens_remessa[$tecido->lancamento_projetos_id][$tecido->produto->servico_detalhes->faccao_id][$tecido->codigo_produto] =[
                        'id_projeto' => encrypt($tecido->lancamento_projetos_id),
                        'numero_projeto' => $tecido->lancamento_projetos_id,
                        'nome_projeto' => $tecido->projeto_detalhes->nome_projeto,
                        'pedido' => $tecido->projeto_detalhes->pedido,
                        'cliente' => empty($tecido->projeto_detalhes->cliente)? '' : $tecido->projeto_detalhes->cliente->nome." - ".$tecido->projeto_detalhes->cliente->cpf_cnpj,
                        'faccao' => $tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                        'produto_a_enviar' => empty($tecido->tecido_detalhes)? '' : $tecido->tecido_detalhes->descricao,
                        'qtde_a_enviar' => parserValor($qtde_a_enviar),
                        'estoque' => empty($estoque['estoque'])? '' : parserValor($estoque['estoque']),
                        'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                        'botao_remessa' => $botao_remessa,
                        'codigo_produto' => $tecido->codigo_produto,
                        'tipo' => 'tecido',
                        'faccao_id' => encrypt($tecido->produto->servico_detalhes->faccao_id),
                        'qtd_pr' => 1,
                        $tecido->lancamento_projetos_id => [
                            'remessa' => $valor_remessa,
                            'quantidade' => $tecido->consumo_total
                        ]
                    ];
                }
            }
        }

        $query_insumo = LancamentoProjetoInsumo::select();
        $query_insumo->with(['projeto_detalhes' => function($query){
            $query->with(['cliente']);
        }, 
        'produto' => function($query){
            $query->with(['servico_detalhes' => function($query){
                $query->with(['faccao' => function($query){
                    $query->with(['fornecedor']);
                }]);
            }]);
        }]);
        $query_insumo->whereHas('projeto_detalhes', function($query) use($projeto_id){
            $query->whereIn('status', [4, 5]);
    
            $query->where('id', $projeto_id);
        });

        $result_insumo = $query_insumo->get();

        foreach($result_insumo as $insumo){
            if(!empty($itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto])){
                if(empty($itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id])){
                    $valor_remessa = 0; 
                    $remessas = RemessaProduto::select()->where('produto_codigo', $insumo->codigo_produto)
                        ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($insumo){
                            $query->where('produto_codigo', $insumo->codigo_produto);
                        }])
                        ->where('faccaos_id', $insumo->produto->servico_detalhes->faccao_id)
                        ->where('lancamento_projetos_id', $insumo->lancamento_projetos_id)
                        ->get();

                    foreach($remessas as $remessa){
                        if(!empty($remessa->pedidoRemessaNasajon)){
                            if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                                if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                    $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                                }
                            }
                        }else{
                            $valor_remessa += $remessa->quantidade_enviada;
                        }
                    }
                    $qtde_a_enviar = $insumo->consumo_total - $valor_remessa;

                    $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar']));
                    $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtd_pr']++;
                    $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['remessa'] = $valor_remessa;
                    $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['quantidade'] = $insumo->consumo_total;

                }else{
                    $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar'] =  parserValor($insumo->consumo_total + parserNumber($retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto]['qtde_a_enviar']));
                    $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['quantidade'] = $insumo->consumo_total + $retorno[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto][$insumo->lancamento_projetos_id]['quantidade'];
                }
            }else{
                $estoque = $this->estoqueProduto($insumo->codigo_produto, "", true);
                $botao_remessa = empty($estoque['estoque'])? false : true;
                $valor_remessa = 0; 
                $remessas = RemessaProduto::select()->where('produto_codigo', $insumo->codigo_produto)
                    ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($insumo){
                        $query->where('produto_codigo', $insumo->codigo_produto);
                    }])
                    ->where('faccaos_id', $insumo->produto->servico_detalhes->faccao_id)
                    ->where('lancamento_projetos_id', $insumo->lancamento_projetos_id)
                    ->get();

                foreach($remessas as $remessa){
                    if(!empty($remessa->pedidoRemessaNasajon)){
                        if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                            if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                            }
                        }
                    }else{
                        $valor_remessa += $remessa->quantidade_enviada;
                    }
                }
                $qtde_a_enviar = $insumo->consumo_total - $valor_remessa;

                $itens_remessa[$insumo->lancamento_projetos_id][$insumo->produto->servico_detalhes->faccao_id][$insumo->codigo_produto] =[
                    'id_projeto' => encrypt($insumo->lancamento_projetos_id),
                    'numero_projeto' => $insumo->lancamento_projetos_id,
                    'nome_projeto' => $insumo->projeto_detalhes->nome_projeto,
                    'pedido' => $insumo->projeto_detalhes->pedido,
                    'cliente' => empty($insumo->projeto_detalhes->cliente)? '' : $insumo->projeto_detalhes->cliente->nome." - ".$insumo->projeto_detalhes->cliente->cpf_cnpj,
                    'faccao' => $insumo->produto->servico_detalhes->faccao->fornecedor->nome." - ".$insumo->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                    'produto_a_enviar' => empty($insumo->insumo_detalhes)? '' : $insumo->insumo_detalhes->descricao,
                    'qtde_a_enviar' => parserValor($qtde_a_enviar),
                    'estoque' => empty($estoque['estoque'])? '' : parserValor($estoque['estoque']),
                    'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                    'botao_remessa' => $botao_remessa,
                    'codigo_produto' => $insumo->codigo_produto,
                    'tipo' => 'insumo',
                    'faccao_id' => encrypt($insumo->produto->servico_detalhes->faccao_id),
                    'qtd_pr' => 1,
                    $insumo->lancamento_projetos_id => [
                        'remessa' => $valor_remessa,
                        'quantidade' => $insumo->consumo_total
                    ]
                ];
            }
        }

        $query_servico = LancamentoProjetoFaccao::select();
        $query_servico->whereNotNull('codigo_produto_acabado');
        $query_servico->with(['projeto' => function($query){
            $query->with(['cliente']);
        }, 
        'tecido' => function($query){
            $query->with(['produto' => function($query){
                $query->with(['servico_detalhes' => function($query){
                    $query->with(['faccao' => function($query){
                        $query->with(['fornecedor']);
                    }]);
                }]);
            }]);
        }]);
        $query_servico->whereHas('projeto', function($query) use($projeto_id){
            $query->whereIn('status', [4, 5]);
            $query->where('id', $projeto_id);
        }); 

        $result_servico = $query_servico->get();

        foreach($result_servico as $servico){
            if(!empty($itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado])){
                if(empty($itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id])){
                    $valor_remessa = 0; 
                $remessas = RemessaProduto::select()->where('produto_codigo', $servico->codigo_produto_acabado)
                    ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($servico){
                        $query->where('produto_codigo', $servico->codigo_produto_acabado);
                    }])
                    ->where('faccaos_id', $servico->faccao_id)
                    ->where('lancamento_projetos_id', $servico->lancamento_projetos_id)
                    ->get();

                foreach($remessas as $remessa){
                    if(!empty($remessa->pedidoRemessaNasajon)){
                        if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                            if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                            }
                        }
                    }else{
                        $valor_remessa += $remessa->quantidade_enviada;
                    }
                }
                $qtde_a_enviar = $servico->quantidade - $valor_remessa;
                    
                    $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar'] =  parserValor($qtde_a_enviar + parserNumber($retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar']));
                    $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtd_pr']++;
                    $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['remessa'] = $valor_remessa;
                    $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['quantidade'] = $servico->quantidade;
                
                }else{
                    $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar'] =  parserValor($servico->quantidade + parserNumber($retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado]['qtde_a_enviar']));
                    $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['quantidade'] = $servico->quantidade + $retorno[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado][$servico->lancamento_projetos_id]['quantidade'];
                }
            }else{
                $estoque = $this->estoqueProduto($servico->codigo_produto_acabado, "", true);
                $botao_remessa = empty($estoque['estoque'])? false : true;
                $valor_remessa = 0; 
                $remessas = RemessaProduto::select()->where('produto_codigo', $servico->codigo_produto_acabado)
                    ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($servico){
                        $query->where('produto_codigo', $servico->codigo_produto_acabado);
                    }])
                    ->where('faccaos_id', $servico->faccao_id)
                    ->where('lancamento_projetos_id', $servico->lancamento_projetos_id)
                    ->get();

                foreach($remessas as $remessa){
                    if(!empty($remessa->pedidoRemessaNasajon)){
                        if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                            if(!empty($remessa->pedidoRemessaNasajon->itens_pedido[0])){
                                $valor_remessa += empty($remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[0]->quantidade_faturada;
                            }
                        }
                    }else{
                        $valor_remessa += $remessa->quantidade_enviada;
                    }
                }
                $qtde_a_enviar = $servico->quantidade - $valor_remessa;

                $itens_remessa[$servico->lancamento_projetos_id][$servico->faccao_id][$servico->codigo_produto_acabado] =[
                    'id_projeto' => encrypt($servico->lancamento_projetos_id),
                    'numero_projeto' => $servico->lancamento_projetos_id,
                    'nome_projeto' => $servico->projeto->nome_projeto,
                    'pedido' => $servico->projeto->pedido,
                    'cliente' => empty($servico->projeto->cliente)? '' : $servico->projeto->cliente->nome." - ".$servico->projeto->cliente->cpfcnpj,
                    'faccao' => $servico->tecido->produto->servico_detalhes->faccao->fornecedor->nome." - ".$servico->tecido->produto->servico_detalhes->faccao->fornecedor->cnpj_cpf,
                    'produto_a_enviar' => $servico->produto_acabado->descricao,
                    'qtde_a_enviar' => parserValor($qtde_a_enviar),
                    'estoque' => empty($estoque['estoque'])? '' : parserValor($estoque['estoque']),
                    'compras' => empty($estoque['estoque_compras'])? '' : parserValor($estoque['estoque_compras']),
                    'botao_remessa' => $botao_remessa,
                    'codigo_produto' => $servico->codigo_produto_acabado,
                    'tipo' => 'servico',
                    'faccao_id' => encrypt($servico->faccao_id),
                    'qtd_pr' => 1,
                    $servico->lancamento_projetos_id => [
                        'remessa' => $valor_remessa,
                        'quantidade' => $servico->quantidade
                    ]
                ];
            }
        }

        foreach($itens_remessa as $key_projeto => $projeto){
            foreach($projeto as $key_faccao => $faccao){
                foreach($faccao as $key_produto => $produto){
                    if(parserNumber($itens_remessa[$key_projeto][$key_faccao][$key_produto]['qtde_a_enviar']) <= 0){
                        unset($itens_remessa[$key_projeto][$key_faccao][$key_produto]);
                    }
                }
            }
        }

        return view('programs.remessa_itens.modal.detalhes_itens_por_projeto')->with(['itens_remessa' => $itens_remessa]);
    }

    public function verificarStatusProjeto($id_projeto){
        $status = 6;

        $arr = [];
        $arr['cliente'] = ''; 
        $arr['faccao'] = ''; 
        $arr['representante'] = ''; 
        $arr['numero_projeto'] = $id_projeto; 
        $arr['nome_projeto'] = ''; 
        $arr['codigo_produto'] = '';
        $arr['nome_produto'] = '';
        $arr['representante'] = '';
        $request = new Request($arr);

        $retorno_filtro = $this->filter($request, true);

        if(!empty($retorno_filtro)){
            foreach($retorno_filtro as $projeto){
                if(!empty($retorno_filtro)){
                    foreach($projeto as $servico){
                        if(!empty($servico)){
                            foreach($servico as $item){
                                if(parserNumber($item['qtde_a_enviar']) > 0){
                                    $status = 5;
                                    break;
                                }
                            }
                        }

                        if($status == 5){
                            break;
                        }
                    }
                }

                if($status == 5){
                    break;
                }
            }
        }

        return $status;          
    }

    private function getPrecoTransferencia($pedidoPortalObj, $produtoObj){
        //$custoObj = $produtoObj->custos->where('estabelecimento', str_pad($pedidoPortalObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
        //$custoObj = $produtoObj->estoque->where('estabelecimento', str_pad($pedidoPortalObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
        
        $custoObj = $produtoObj->estoque->where('estabelecimento', str_pad($pedidoPortalObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
        $custo_portal = $produtoObj->custos->where('estabelecimento', str_pad($pedidoPortalObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
        if(empty($custoObj) && empty($custo_portal)){
            $custoObj = $produtoObj->preco->preco_real / 1.43;
        }else if(!empty($custoObj) && !empty($custo_portal)){
            if($custoObj->custo > $custo_portal->custo_medio_contabil){
                $custo = $custoObj->custo;
            }else if(empty($custoObj->custo) && empty($custo_portal->custo_medio_contabil)){
                $custo = $produtoObj->preco->preco_real / 1.43;
            }else{
                $custo = $custo_portal->custo_medio_contabil;
            }
        }else if(!empty($custoObj) && empty($custo_portal)){
            $custo = empty($custoObj->custoObj)? $produtoObj->preco->preco_real / 1.43 : $custoObj->custo;
        }else if(empty($custoObj) && !empty($custo_portal)){
            $custo = empty($custo_portal->custo_medio_contabil)? $produtoObj->preco->preco_real / 1.43 : $custo_portal->custo_medio_contabil;
        }else{
            $custo = $custoObj->custo;
        }

        if($custo > $produtoObj->preco->preco_real){
            if($custoObj->custo > $custo_portal->custo_medio_gerencial){
                $custo = $custoObj->custo;
            }else if(empty($custoObj->custo) && empty($custo_portal->custo_medio_gerencial)){
                $custo = $produtoObj->preco->preco_real / 1.43;
            }else{
                $custo = $custo_portal->custo_medio_gerencial;
            }
        }
        
        /*else{
            $custo = $custoObj->custo_medio_contabil;
            if(empty($custo)){
                $custo = $produtoObj->preco->preco_real / 1.43;
            }
        }
        switch($pedidoPortalObj->estabelecimento){
            case '3':
                $custo = $custo / 0.96;
            break;
            case '4':
                if(in_array($produtoObj->procedencia, [0, 3, 4, 5])){
                    $custo = $custo / 0.88;
                }else{
                    $custo = $custo / 0.96;
                }
            break;
            default:
                switch($pedidoPortalObj->cliente->uf){
                    case 'TO':
                    case 'RO':
                        $custo = $custo / 0.93;
                    break;
                    default:
                        $custo = $custo / 0.82;
                    break;
                }
            break;
        }*/
        
        return $custo;
    }

}
