<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\User;
use App\TipoDeServico;
use App\FaccaoTipoDeServico;
use App\FornecedorNasajon;
use App\ClienteNasajon;
use App\LancamentoProjeto;
use App\LancamentoProjetoProduto;
use App\LancamentoProjetoTecido;
use App\LancamentoProjetoInsumo;
use App\LancamentoProjetoFaccao;
use App\Faccao;
use App\TipoUsuario;
use App\MotivoRecusaPedido;
use App\HistoricoProjeto;
use App\ProdutosEstoque;
use App\ParametroHospitalar;
use App\EstabelecimentoCidadeFob;
use App\AprovacaoDeProjeto;
use App\ParametrosAprovacaoPedido;
use App\GrupoEmpresarial;
use App\ClienteCredito;
use App\PedidosVendaNasajon;
use App\TitulosEmAbertoNasajon;
use App\ChequesEmAbertoNasajon;
use App\ChequesRecebido;
use App\OperacaoNasajon;
use App\ParametrosAprovacao;
use App\AliquotaPreco;
use App\TipoOperacao;
use App\ProdutoNovo;
use App\StatusProjeto;
use App\StatusProjetoExibicao;
use App\ProdutoEspecificacao;
use App\ProdutoNasajon;
use App\PedidoPortal;
use App\ParametrosPedido;
use App\ComprasNasajon;
use App\NecessidadeCompras;
use App\NecessidadeComprasXProjeto;
use App\HistoricoProjetoProduto;
use App\NasajonEstabelecimento;
use App\HistoricoPedidoCompra;
use App\RemessaProduto;
use App\EnvioProjetoFaccao;
use App\ProdutoGrupo;
use App\ProdutoMarca;
use App\ProdutoPromocional;
use App\LancamentoProjetoProdutoArquivo;
use App\Preco;
use App\PrecosLog;
use App\NotasCreditoReceberNasajon;
use App\PedidosPrePago;
use App\Cheque;
use App\FichaTecnicaProduto;

use Illuminate\Http\Request;
use App\Http\Requests\GetTipoDeServicoRequest;
use App\Http\Requests\LancamentoInsumoRequest;
use App\Http\Requests\LancamentoTecidoRequest;
use App\Http\Requests\LancamentoProdutoRequest;
use App\Http\Requests\FilterConsultaProjetoRequest;
use App\Http\Requests\AlteracaoDataProjetoFaccaoRequest;
use App\Http\Requests\AlteracaoStatusProjetoRequest;
use App\Http\Requests\ListaDePrecosRequest;
use App\Http\Requests\LancamentoProjetoAdicionarOnChangeRequest;
use App\Http\Requests\LancamentoProjetoAlteracaoEmMassaServicoRequest;
use App\Http\Requests\ProdutoNovoRequest;
use App\Http\Requests\ProjetoAlteracaoEmMassaProdutoRequest;
use App\Http\Requests\ProjetoAlteracaoProdutosRequest;
use App\Http\Requests\ProjetoValidarRequest;
use App\Http\Requests\ArquivoProdutoAdicionarRequest;
use App\Http\Requests\LancamentoProjetoSalvarDuplicadaRequest;

use App\Http\Controllers\ListagemDePrecosController;
use App\Http\Controllers\FichaTecnicaProdutoController;
use App\Http\Controllers\ProdutoNovoController;
use App\Http\Controllers\PedidosComprasNasajonController;
use App\Http\Controllers\PedidoPortalController;
use App\Http\Controllers\RemessaItensController;
use App\Http\Controllers\AprovacaoDePedidoController;
use App\Http\Controllers\ImportacaoPrecoController;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

use DateTime;

class LancamentoProjetoController extends Controller
{
    private $codigo_cliente_balcao = ['0000010069999'];

    private $condicao_de_pagamento_livre = ['CARTAO DE DEBITO', 'CARTAO DE CREDITO', 'CARTAO DE CREDITO 7 X', 'CARTAO DE CREDITO 6 X', 'CARTAO DE CREDITO 5 X', 'CARTAO DE CREDITO 4 X', 'CARTAO DE CREDITO 3 X', 'CARTAO DE CREDITO 2 X', 'CARTAO DE CREDITO 1 X', 'À VISTA','BNDS','DINHEIRO','Dinheiro','USAR CREDITO','Usar Cŕedito','Usar Credito','Debito','Débito','DEBITO','DÉBITO','Cartão BNDES'];

    private $servico_minimo_350 = ['MDO014350'];
    private $servico_minimo_500 = ['MDO014500'];

    private $margem_preco = 1.43;

    private $empresas_mn = ['05075884', '06311274'];

    private $unidades_permitida_insumo = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.', 'UN', 'Kg', 'KG', 'KGS'];

    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\LancamentoProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\LancamentoProjeto');


        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
        $representantes = [];
        foreach ($representantes_busca as $key => $value) {
        	$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }
        
        $vendedores = $this->dadosVendedor();
        $tipo_frete = $this->dadosTipoFrete();
        $estados = $this->getEstados();

        return view('programs.lancamento_de_projeto.representante.index')->with(['estados' => $estados, 'vendedor' => $vendedores, 'tipo_frete' => $tipo_frete, 'representantes' => $representantes]);
    }

    public function filter(Request $request){
        $fields = $request->only('num_projeto', 'nome_projeto', 'nome_cliente', 'representantes', 'data_inicio', 'data_fim', 'estados');

        $retorno = [];

        $query = LancamentoProjeto::select()->withTrashed();
        $query->with(['cliente','detalhes_representante']);

        if(!empty($fields['estados'])){
            if($fields['estados'] == 99){
                $query->whereNotNull('deleted_at');
            }else{
                $query->whereHas('detalhes_status', function($query) use($fields){          
                    $query->where('status_projeto_exibicao_id', $fields['estados']);
                });
                $query->whereNull('deleted_at');
            }
        }
		
        if(is_numeric($fields['num_projeto'])){
            $query->where('id', $fields['num_projeto']);
        }
        if(!empty($fields['nome_projeto'])){
            $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
        }
        if(!empty($fields['nome_cliente'])){
            $query->where(function($query) use($fields){
                $cliente_busca = ClienteNasajon::select('cpf_cnpj')->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($fields['nome_cliente']).'%')->get();
                $query->WhereIn('cliente_codigo', $cliente_busca->pluck('cpf_cnpj'));
            });
        }
        if(!empty($fields['representantes'])){
            $query->where('users_codigo_representante', $fields['representantes']);
        }
        if (Auth::user()->tipo_usuario_id == 12 || Auth::user()->tipo_usuario_id == 16){
            $query->where('users_codigo_representante', Auth::user()->codigo_representante);
        }

        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
		    $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $query->whereBetween('data', [$data_inicio, $data_fim]);
        }

        $result = $query->get();
        
        foreach($result as $projeto){
            $retorno [] = [
                'id' => encrypt($projeto->id),
                'num_projeto' => $projeto->id,
                'nome_projeto' => empty($projeto->nome_projeto)? '': $projeto->nome_projeto,
                'cliente' => empty($projeto->cliente)? '' : $projeto->cliente->nome,
                'data' => parserData($projeto->data),
                'valor_total_pedido' => empty($projeto->valor_total_pedido)? '':parserValor($projeto->valor_total_pedido),
                'status_codigo' => !empty($projeto->deleted_at)? 99 : $projeto->status,
                'status' => !empty($projeto->deleted_at)? ($projeto->status === 99? 'CANCELADO' : 'CANCELADO POR INATIVIDADE') : $projeto->detalhes_status->descricao,
                'vendedor' => empty($projeto->detalhes_representante)? '': $projeto->detalhes_representante->codigo_representante." - ".$projeto->detalhes_representante->name
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalAdicionar(){
        $user_info = User::find(Auth::id());
        $verificar = LancamentoProjeto::where('users_codigo_representante', $user_info->codigo_representante)->where('status', 0)->first();

        if(empty($verificar)){
           $mensagem = 'Já há um ou mais projetos sendo digitados. Favor verificar.';
        }else{
            $mensagem = ''; 
        }

        $representante = $this->getRepresentante();

        $lancamentoProjetoObj = new LancamentoProjeto;
        $lancamentoProjetoObj->status = 0;
        $lancamentoProjetoObj->estabelecimento = 4;
        $lancamentoProjetoObj->data = Carbon::now();
        $lancamentoProjetoObj->tipo_frete = "CIF";

        if(!empty($representante)){
            $lancamentoProjetoObj->users_codigo_representante = $representante['codigo'];
            $representante = encrypt($representante['codigo']);
        }

        $lancamentoProjetoObj->created_by = Auth::id();
        $lancamentoProjetoObj->save();

        $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'criacao_projeto', '', Auth::id());

        $numero_projeto = $lancamentoProjetoObj->id;
        $id_projeto = encrypt($lancamentoProjetoObj->id);

        $dados = [
            'id' => $id_projeto,
            'numero_projeto' => $lancamentoProjetoObj->id,
            'nome_projeto' => '',
            'cliente' => '',
            'cliente_descricao' => '',
            'pedido' => '',
            'pagamento' => '',
            'pagamento_descricao' => '',
            'tipo_frete' => '',
            'margem' => '',
            'representante'=> $representante,
            'motivo' => '',
            'desconto' => '',
            'revisor' => '',
            'estabelecimento' => '',
            'estado_destino' => '',
            'media_condicao_pagamento' => '',
            'preco_cif_fob' => '',
            'cif_fob' => '',
            'linha' => '',
            'mensagem' => $mensagem,
            'nome_contato' => '',
            'email_contato' => '',
            'descricao_vendedor' => '',
        ];

        $produto_total_ex = '';
        $total_custo_tecido = '';
        $total_custo_insumo = '';
        $total_custo_servico = '';
        $total_custo_unitario = '';
        $total_custo_total = '';

        $tipo_produto_producao = $this->tipoProdutoProjeto();

        return view('programs.lancamento_de_projeto.modal.projeto')->with(['dados' => $dados, 'intercompany' => false,'numero_projeto' => $numero_projeto, 'produto_total_ex' => $produto_total_ex, 'total_custo_tecido' => $total_custo_tecido, 'total_custo_insumo' => $total_custo_insumo, 'total_custo_servico' => $total_custo_servico, 'total_custo_unitario' => $total_custo_unitario, 'total_custo_total' => $total_custo_total, 'tipo_produto_producao' => $tipo_produto_producao]);
    }

    public function modalEditar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $query = LancamentoProjeto::select();
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $query->with(['cliente','condicoes_pagamento_web']);
        $result = $query->first();

        if(empty($result->estabelecimento)){
            $result->estabelecimento = 4;
            $result->save();

            $tecidos = $this->reajusteQuantidadeTecido($result->id, '', 0, true);
            $insumos = $this->reajusteQuantidadeInsumo($result->id, '', 0, true);
            $servicos = $this->reajusteQuantidadeServico($result->id, '', 0, true);
        }

        $motivo = '';
        if($result->status == 9){
            $query_reprovado = HistoricoProjeto::where('lancamento_projetos_id', $id)->orderBy('id', 'desc')->first();
            if(!empty($query_reprovado->motivo)){
                $motivo = $query_reprovado->motivo;
            }
        }

        $info = $this->getInformacaoPreco($result->id);
        
        if($result->bionexo){
            $linha = strval($result->produto_linhas_id)."_bionexo";
        }else if($result->mostruario){
            $linha = strval($result->produto_linhas_id)."_mostruario";
        }else if($result->licitacao){
            $linha = strval($result->produto_linhas_id)."_licitacao";
        }else{
            $linha = empty($result->produto_linhas_id)? '' : $result->produto_linhas_id;
        }

        if(empty($result->email_contato)){
            if(!empty($result->cliente)){
                $parametros = new Request ([
                    'cliente' => trim($result->cliente->nome).' - '.$result->cliente_cpf_cnpj
                ]);
                $contato = $this->getContato($parametros, true);
                $result->nome_contato = $contato['nome_contato'];
                $result->email_contato = $contato['email_contato'];
                $result->save();
            }  
        }
        
        $dados = [
            'id' => encrypt($result->id),
            'numero_projeto' => $result->id,
            'nome_projeto' => $result->nome_projeto,
            'cliente' => $result->cliente_codigo,
            'cliente_descricao' => empty($result->cliente)? '' : $result->cliente->nome.' - '.$result->cliente->cpf_cnpj,
            'pedido' => $result->pedido,
            'pagamento' => $result->condicoes_pagamento_web_id,
            'pagamento_descricao' => empty($result->condicoes_pagamento_web)? '' : $result->condicoes_pagamento_web->descricao,
            'tipo_frete' => $result->tipo_frete,
            'representante'=> empty($result->users_codigo_representante)? '' : encrypt($result->users_codigo_representante),
            'motivo' => $motivo,
            'desconto' => '',
            'revisor' => '',
            'estabelecimento' => $info['estabelecimento'],
            'estado_destino' => $info['estado_destino'],
            'media_condicao_pagamento' => $info['media_condicao_pagamento'],
            'preco_cif_fob' => $info['preco_cif_fob'],
            'cif_fob' => $info['cif_fob'],
            'linha' => $linha,
            'mensagem' => '',
            'nome_contato' => empty($result->nome_contato)? '' : $result->nome_contato,
            'email_contato' => empty($result->email_contato)? '' : $result->email_contato,
            'descricao_vendedor' => empty($result->detalhes_representante)? '': $result->detalhes_representante->codigo_representante." - ".$result->detalhes_representante->name,
        ];

        $intercompany = false;
        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', '=', $id);
        $query_produto->orderBy('descricao');
        $result_produtos = $query_produto->get();
        $raiz_cnpj = (empty($result->cliente))? '' : substr($result->cliente->cpf_cnpj,0,10);

        if(in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $intercompany = true;
        }

        $produtos_tabela = [];

        foreach($result_produtos as $produto){
            $custo = $produto->valor_total_tecido->total + $produto->valor_total_insumo->total + $produto->valor_total_servico->total;

            $desconto_acima_permitido = false;

            $produtos_tabela[] = [
                'id' => encrypt($produto->id),
                'indice' => $produto->indice,
                'codigo' => $produto->codigo_produto,
                'descricao' => $produto->descricao,
                'preco_venda' => parserValor($produto->preco_venda),
                'custo_unitario' => (empty($custo) && $produto->quantidade > 0) ? '' : parserValor($custo / $produto->quantidade),
                'quantidade' => parserQtd($produto->quantidade),
                'detalhe_producao' => $produto->detalhe_producao,
                'tecido_total' => empty($produto->valor_total_tecido->total)? '' : parserValor($produto->valor_total_tecido->total),
                'insumo_total' => empty($produto->valor_total_insumo->total)? '' : parserValor($produto->valor_total_insumo->total),
                'servico_total' => empty($produto->valor_total_servico->total)? '' : parserValor($produto->valor_total_servico->total),
                'custo_total' => empty($custo) ? '' : parserValor($custo),
                'desconto_acima_permitido' => $desconto_acima_permitido,
            ];
        }

        $produto_total_ex = parserValor($query_produto->sum('quantidade'));

        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $result->id)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $result->id)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $result->id)->sum('valor_total');

        $total_custo_total = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;

        $total_custo_tecido = empty($total_custo_tecido)? '' : parserQtd($total_custo_tecido);
        $total_custo_insumo = empty($total_custo_insumo)? '' : parserQtd($total_custo_insumo);
        $total_custo_servico = empty($total_custo_servico)? '' : parserQtd($total_custo_servico);
        
        $total_custo_unitario = empty($total_custo_total)? '' : parserQtd($total_custo_total / $query_produto->sum('quantidade'));
        $total_custo_total = empty($total_custo_total)? '' : parserQtd($total_custo_total);

        $tipo_produto_producao = $this->tipoProdutoProjeto();

        return view('programs.lancamento_de_projeto.modal.projeto')->with(['dados' => $dados, 'intercompany' => $intercompany,'produtos_tabela' => $produtos_tabela, 'produto_total_ex' => $produto_total_ex, 'total_custo_tecido' => $total_custo_tecido, 'total_custo_insumo' => $total_custo_insumo, 'total_custo_servico' => $total_custo_servico, 'total_custo_total' => $total_custo_total, 'total_custo_unitario' => $total_custo_unitario, 'tipo_produto_producao' => $tipo_produto_producao]);
    }

    public function modalDeletar(Request $request){
        $id = $request->only(['id'])['id'];
        
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $query = LancamentoProjeto::select();
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $query->with(['cliente']);
        $result = $query->first();

        $dados = [
            'id' => encrypt($result->id),
            'projeto' => empty($result->nome_projeto)? '':$result->nome_projeto,
            'cliente' => empty($result->cliente)?'':$result->cliente->nome
        ];

        return view('programs.lancamento_de_projeto.representante.modal.deletar')->with(['dados' => $dados]);
    }

    public function adicionarOnChange(LancamentoProjetoAdicionarOnChangeRequest $request){
        $fields = $request->only('id_projeto', 'id_representante','nome_projeto', 'codigo_cliente', 'num_pedido', 'condicao_pagamento', 'id_revisor', 'tipo_produto_producao', 'nome_contato', 'email_contato');
        
        $tecidos = '';
        $insumos = '';
        $servicos = '';
        $info = '';
        $intercompany = false;

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        
        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);

        if(!empty($lancamentoProjetoObj->cliente)){
            $cliente_nasajon = $lancamentoProjetoObj->cliente->cpf_cnpj;
        }else if(!empty($fields['codigo_cliente'])){
            $cliente_nasajon = ClienteNasajon::where('cpf_cnpj',$fields['codigo_cliente'])->first()->cpf_cnpj;
        }

        $raiz_cnpj = substr($cliente_nasajon,0,10);

        if(in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $intercompany = true;
        }
        
        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }


        $valor_anterior_licitacao = $lancamentoProjetoObj->licitacao;

        if($fields['codigo_cliente'] === $lancamentoProjetoObj->cliente_codigo && intval($lancamentoProjetoObj->condicoes_pagamento_web_id) === intval($fields['condicao_pagamento'])){
            $reajuste_de_preco = false;
        }else{
            $reajuste_de_preco = true;
        }
        
        if(!empty($fields['nome_projeto'])){
            $lancamentoProjetoObj->nome_projeto = strtoupper($fields['nome_projeto']);
        }
        if(!empty($fields['num_pedido'])){
            $lancamentoProjetoObj->pedido = strtoupper($fields['num_pedido']);
        }
        if(!empty($fields['codigo_cliente'])){
            if(in_array(preg_replace('/[_\-\/\.]/','', $fields['codigo_cliente']), ['05075884000248', '05075884000167'])){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Não é permitido gerar projeto para Tecidos MN',
                    'error' => ['nome_cliente' => 'Não é permitido gerar projeto para Tecidos MN.'],
                    'response' => []
                ],422);
            }
            if(empty($this->getRepresentante())){
                $cliente = ClienteNasajon::where('cpf_cnpj', $fields['codigo_cliente'])->where('bloqueado', false)->first();
                if(!empty($cliente->vendedor_codigo)){
                    
                    $raiz_cnpj = substr($cliente->cpf_cnpj,0,10);

                    $verificar_codigo_representate = User::select()->where('codigo_representante', $cliente->vendedor_codigo)->first();
                    if(!empty($verificar_codigo_representate)){
                        $lancamentoProjetoObj->cliente_codigo = $fields['codigo_cliente'];
                        $lancamentoProjetoObj->users_codigo_representante = $cliente->vendedor_codigo;
                    }else if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Ajustar código do vendedor no cliente',
                            'error' => ['nome_cliente' => 'Ajustar código do vendedor no cliente.'],
                            'response' => []
                        ],422);
                    }
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ajustar código do vendedor no cliente',
                        'error' => ['nome_cliente' => 'Ajustar código do vendedor no cliente'],
                        'response' => []
                    ],422);
                }

                if(empty($cliente->uf)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ajustar código do vendedor no cliente',
                        'error' => ['nome_cliente' => 'O cadastro deste cliente está incompleto e não possui UF. Favor verificar com o setor responsável.'],
                        'response' => []
                    ],422);
                }
            }else{
                $lancamentoProjetoObj->cliente_codigo = $fields['codigo_cliente'];
            }
        }
        if(!empty($fields['codigo_cliente'])){
            $lancamentoProjetoObj->cliente_cpf_cnpj = $fields['codigo_cliente'];
        }
        if(!empty($fields['condicao_pagamento'])){
            $lancamentoProjetoObj->condicoes_pagamento_web_id = $fields['condicao_pagamento'];
        }
        if(!empty($fields['tipo_produto_producao'])){
            if(is_numeric($fields['tipo_produto_producao'])){
                $lancamentoProjetoObj->produto_linhas_id = $fields['tipo_produto_producao'];
                $lancamentoProjetoObj->bionexo = false;
                $lancamentoProjetoObj->mostruario = false;
                $lancamentoProjetoObj->licitacao = false;
            }else{
                $lancamentoProjetoObj->produto_linhas_id = intval(explode("_", $fields['tipo_produto_producao'])[0]);

                if(explode("_", $fields['tipo_produto_producao'])[1] === "bionexo"){
                    $lancamentoProjetoObj->bionexo = true;
                    $lancamentoProjetoObj->mostruario = false;
                    $lancamentoProjetoObj->licitacao = false;
                }else if(explode("_", $fields['tipo_produto_producao'])[1] === "mostruario"){
                    $lancamentoProjetoObj->bionexo = false;
                    $lancamentoProjetoObj->mostruario = true;
                    $lancamentoProjetoObj->licitacao = false;
                }else{
                    $lancamentoProjetoObj->bionexo = false;
                    $lancamentoProjetoObj->mostruario = false;
                    $lancamentoProjetoObj->licitacao = true;
                }
                
            }
        }else{
            $lancamentoProjetoObj->produto_linhas_id = null;
            $lancamentoProjetoObj->bionexo = false;
            $lancamentoProjetoObj->mostruario = false;
            $lancamentoProjetoObj->licitacao = false;
        }
        if(!empty($fields['nome_contato'])){
            $lancamentoProjetoObj->nome_contato = $fields['nome_contato'];
        }
        if(!empty($fields['email_contato'])){
            $lancamentoProjetoObj->email_contato = $fields['email_contato'];
        }

        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $valor_atual_licitacao = $lancamentoProjetoObj->licitacao;

        if(!empty($fields['condicao_pagamento'] && $reajuste_de_preco === true)){
            $tecidos = $this->reajusteQuantidadeTecido($id_projeto, '', 0, true);
            $insumos = $this->reajusteQuantidadeInsumo($id_projeto, '', 0, true);
            $servicos = $this->reajusteQuantidadeServico($id_projeto, '', 0, true);
            $info = $this->getInformacaoPreco($id_projeto);
        }

        if($valor_anterior_licitacao !== $valor_atual_licitacao){
            $this->excluirServicoLicitacao($id_projeto, $valor_atual_licitacao);
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'tecidos' => $tecidos,
                'insumos' => $insumos,
                'servicos' => $servicos,
                'info' => $info,
                'intercompany' => $intercompany,
                'descricao_vendedor' => empty($lancamentoProjetoObj->detalhes_representante)? '': $lancamentoProjetoObj->detalhes_representante->codigo_representante." - ".$lancamentoProjetoObj->detalhes_representante->name,
            ]
            
        ];

        return response()->json($response);
    }

    public function adicionar(ProjetoValidarRequest $request){
        $fields = $request->only('id_projeto');
        try{    
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $parametros = new Request ([
            'id_projeto' => encrypt($id_projeto)
        ]);

        $this->resultado($parametros);

        $lancamentoProjetoObj = LancamentoProjeto::with('cliente')->find($id_projeto);

        $estabelecimento = $lancamentoProjetoObj->estabelecimento;

        $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem;
        $query_produto = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto);
        $produtos = $query_produto->get();

        $mensagem = $this->validarProjeto($lancamentoProjetoObj, $produtos);

        if(empty($mensagem)){
            $lancamentoProjetoObj->data_entrada = Carbon::now()->setTime(0,0,0);
            $lancamentoProjetoObj->data_previsao_entrega = Carbon::now()->setTime(0,0,0)->addDays(45);
            $lancamentoProjetoObj->status = 1;
            $lancamentoProjetoObj->updated_by = Auth::id();
            $lancamentoProjetoObj->save();

            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'finalizado_representante', '', Auth::id());

            $arr = [
                'id_projeto' => $id_projeto,
                'id_faccao' => '', 
                'data_entrega_cliente' => Carbon::now()->setTime(0,0,0)->addDays(45), 
                'data_previsao_entrega' => Carbon::now()->setTime(0,0,0)->addDays(45)
            ];

            $alteracao = new AlteracaoDataProjetoFaccaoRequest($arr);

            $this->alteracaoDataFaccao($alteracao);

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => []
            ];
            return response()->json($response, 200);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => $mensagem,
                'error' => '',
                'response' => ''
            ],422);
        }
    }

    public function deletar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $lancamentoProjetoObj = LancamentoProjeto::find($id);
        $lancamentoProjetoObj->deleted_by = Auth::id();
        $lancamentoProjetoObj->save();
        $lancamentoProjetoObj->delete();

        $produtos_novos = ProdutoNovo::select();
        $produtos_novos->where('lancamento_projetos_id', $id);
        $produtos_novos = $produtos_novos->get();

        foreach($produtos_novos as $produto_novo){
            $produtoNovoObj = ProdutoNovo::find($produto_novo->id);
            $produtoNovoObj->deleted_by = Auth::id();
            $produtoNovoObj->save();
            $produtoNovoObj->delete();
        }

        $aprovacaoDeProjetoObj = AprovacaoDeProjeto::where('projeto_id', $id)->first();
        if(!empty($aprovacaoDeProjetoObj)){
            $aprovacaoDeProjetoObj->deleted_by = Auth::id();
            $aprovacaoDeProjetoObj->save();
            $aprovacaoDeProjetoObj->delete();    
        }

        $this->gravarHistoricoProjeto($id, 'cancelamento_projeto', '', Auth::id());

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    private function dadosTipoFrete(){
        $lancamentos = [
            '' => 'Selecione',
            'CIF' => "CIF",
            'FOB' => "FOB",
        ];
        return $lancamentos;
    }

    private function dadosVendedor(){
        $dropdown_usuarios = ['' => 'Selecione o vendedor'];

        $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->where('codigo_representante', '!=', '')->orderBy('codigo_representante', 'asc')->get();

        foreach ($users as $user) {
            if(is_null($user->tipo_usuario)){
                continue;
            }
            if (!is_null($user->codigo_representante)){
                $dropdown_usuarios[$user->id] = $user->codigo_representante . ' - ' . strtoupper($user->name);
            }
        }

        return $dropdown_usuarios;
    }

    public function tipoDeServicos(){
        $query_tipo_de_servico = TipoDeServico::select();
        $result_tipo_de_servico = $query_tipo_de_servico->get();

        $tipo_de_servicos[''] = 'Selecione o Serviço';

        foreach($result_tipo_de_servico as $key => $tipo_de_servico){
            $tipo_de_servicos [$tipo_de_servico->id] = $tipo_de_servico->descricao;
        }

        return $tipo_de_servicos;
    }

    public function getEstados(){
        $query_status = StatusProjetoExibicao::select();
        $query_status->orderBy('id');
        $result = $query_status->get();

        foreach($result as $status){
            $status_arr[$status->id] = $status->descricao;
        }

        return $status_arr;
    }

    public function estabelecimentos(){
        $estabelecimentos[''] = 'Selecione';
        $estabelecimentos = array_merge($estabelecimentos, returnEmpresasNasajonView());

        return $estabelecimentos;
    }

    public function adicionarProduto(LancamentoProdutoRequest $request){
        $fields = $request->only('id_projeto', 'produto_codigo', 'produto_descricao', 'produto_descricao_hidden', 'produto_preco_venda', 'produto_quantidade', 'produto_detalhes', 'produto_ficha', 'id_codigo', 'id_revisor', 'produto_ncm', 'produto_peso');
        
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $descricao = '';
        if(empty($fields['produto_descricao'])){
            $descricao = strtoupper($fields['produto_descricao_hidden']);
        }else{
            $descricao = str_replace("'", " ", strtoupper(tirarAcentos($fields['produto_descricao'])));
        }

        $ncm = empty($fields['produto_ncm'])? '' : $fields['produto_ncm'];
        $peso = empty($fields['produto_peso'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $fields['produto_peso'])));

        if(!empty($fields['produto_preco_venda'])){
            $preco_venda = floatval(str_replace(",", ".", str_replace(".", "", $fields['produto_preco_venda'])));
        }else{
            $preco_venda = 0;
        }

        $quantidade = floatval(str_replace(",", ".", str_replace(".", "", $fields['produto_quantidade'])));

        if(!empty($fields['produto_codigo'])){
            $produto_nasajon = ProdutoNasajon::select()->where('codigo', $fields['produto_codigo'])->first();
            $ncm = $produto_nasajon->ncm;
            $peso = $produto_nasajon->pesoliquido;
        }

        $ultimo_indice = LancamentoProjetoProduto::select()->where('lancamento_projetos_id', $id_projeto)->max('indice');
        if(empty($ultimo_indice)){
            $indice = 1;
        }else{
            $indice = $ultimo_indice + 1;
        }

        $lancamentoProjetoProdutoObj = new LancamentoProjetoProduto;
        $lancamentoProjetoProdutoObj->lancamento_projetos_id = $id_projeto;
        $lancamentoProjetoProdutoObj->indice = $indice;
        $lancamentoProjetoProdutoObj->codigo_produto = $fields['produto_codigo'];
        $lancamentoProjetoProdutoObj->descricao = $descricao;
        $lancamentoProjetoProdutoObj->quantidade = $quantidade;
        $lancamentoProjetoProdutoObj->preco_venda = $preco_venda;
        $lancamentoProjetoProdutoObj->detalhe_producao = str_replace("'", " ", strtoupper(tirarAcentos($fields['produto_detalhes'])));
        if(!empty($ncm)){
            $lancamentoProjetoProdutoObj->ncm = $ncm;
        }
        if(!empty($peso)){
            $lancamentoProjetoProdutoObj->peso = $peso;
        }
        $lancamentoProjetoProdutoObj->created_by = Auth::id();
        $lancamentoProjetoProdutoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        if(!empty($fields['id_codigo'])){
            $ficha_tecnica = $this->getFichaTecnica($fields['produto_ficha'], $fields['id_codigo'], $id_projeto, $quantidade, $lancamentoProjetoProdutoObj->id);
            $this->atualizarCustoPrecoProduto($lancamentoProjetoProdutoObj->id);
        }else{
            $ficha_tecnica = [
                'tecidos' => '',
                'insumos' => '',
                'servicos' => ''
            ];
        }

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');
        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        
        $total_custo = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;
        
        $total_custo_unitario = $total_custo / $total_quantidade;

        $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $lancamentoProjetoProdutoObj->id);
        $result_faccao = $query_faccao->first();

        if(!empty($result_faccao)){       
            $query_faccao->whereNull('faccao_id');
            $result_faccao = $query_faccao->first();
            if(empty($result_faccao)){
                $produto_com_faccao = 1;
            }else{
                $produto_com_faccao = 0;  
            }
        }else{
            $produto_com_faccao = 0;
        }
        $custo = $lancamentoProjetoProdutoObj->valor_total_tecido->total + $lancamentoProjetoProdutoObj->valor_total_insumo->total + $lancamentoProjetoProdutoObj->valor_total_servico->total;
        $custo_unitario = $custo / $quantidade;
        $produto = [
            'id' => encrypt($lancamentoProjetoProdutoObj->id),
            'indice' => $lancamentoProjetoProdutoObj->indice,
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $fields['produto_codigo'],
            'descricao' => $descricao,
            'detalhes' => strtoupper($fields['produto_detalhes']),
            'preco_venda' => parserValor($preco_venda),
            'quantidade' => parserQtd($quantidade),
            'detalhe_producao' => strtoupper($fields['produto_detalhes']),
            'ficha_tecnica' => $ficha_tecnica,
            'ncm' => $ncm,
            'peso' => parserQtd($peso),
            'total_tecido' => empty($lancamentoProjetoProdutoObj->valor_total_tecido->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_tecido->total),
            'total_insumo' => empty($lancamentoProjetoProdutoObj->valor_total_insumo->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_insumo->total),
            'total_servico' => empty($lancamentoProjetoProdutoObj->valor_total_servico->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_servico->total),
            'produto_com_faccao' => $produto_com_faccao,
            'total_custo' => empty($custo)? '' : parserValor($custo),
            'custo_unitario' => empty($custo_unitario)? '' : parserValor($custo_unitario),
        ];

        $total = [
            'total_quantidade' => empty($total_quantidade)? '' : parserQtd($total_quantidade),
            'total_custo_tecido' => empty($total_custo_tecido)? '' : parserQtd($total_custo_tecido),
            'total_custo_insumo' => empty($total_custo_insumo)? '' : parserQtd($total_custo_insumo),
            'total_custo_servico' => empty($total_custo_servico)? '' : parserQtd($total_custo_servico),
            'total_custo_total' => empty($total_custo)? '' : parserQtd($total_custo),
            'total_custo_unitario' => empty($total_custo_unitario)? '' : parserQtd($total_custo_unitario),
        ];

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produto' => $produto,
                'total' => $total
            ]
        ];

        return response()->json($retorno);
    }

    public function getEditarProduto(Request $request){
        $fields = $request->only('id_produto', 'id_projeto');
        try{
            $id_produto = decrypt($fields['id_produto']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($id_produto);

        if(!empty($lancamentoProjetoProdutoObj->codigo_produto) && (empty($lancamentoProjetoProdutoObj->ncm) || empty($lancamentoProjetoProdutoObj->peso))){
            $produto_nasajon = ProdutoNasajon::select()->where('codigo', $lancamentoProjetoProdutoObj->codigo_produto)->first();
            if(!empty($produto_nasajon)){
                if(empty($lancamentoProjetoProdutoObj->ncm)){
                    $lancamentoProjetoProdutoObj->ncm = $produto_nasajon->ncm;
                }

                if(empty($lancamentoProjetoProdutoObj->peso)){
                    $lancamentoProjetoProdutoObj->peso = $produto_nasajon->pesoliquido;
                }

                $lancamentoProjetoProdutoObj->save();
            }
        }

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');
        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

        $produto_custo_tecido = LancamentoProjetoTecido::where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');
        $produto_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');
        $produto_custo_servico = LancamentoProjetoFaccao::where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $total_quantidade = $total_quantidade - $lancamentoProjetoProdutoObj->quantidade;
        $total_custo_tecido = $total_custo_tecido - $produto_custo_tecido;
        $total_custo_insumo = $total_custo_insumo - $produto_custo_insumo;
        $total_custo_servico = $total_custo_servico - $produto_custo_servico;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoProdutoObj->id),
                'projeto_id' => encrypt($id_projeto),
                'codigo' => $lancamentoProjetoProdutoObj->codigo_produto,
                'descricao' => $lancamentoProjetoProdutoObj->descricao,
                'preco_venda' => str_replace(".", "", parserValor($lancamentoProjetoProdutoObj->preco_venda)),
                'quantidade' => str_replace(".", "", parserQtd($lancamentoProjetoProdutoObj->quantidade)),
                'detalhe_producao' => $lancamentoProjetoProdutoObj->detalhe_producao,
                'total_exibicao' => empty($total_quantidade)?'':parserQtd($total_quantidade),
                'ncm' => $lancamentoProjetoProdutoObj->ncm,
                'peso' => empty($lancamentoProjetoProdutoObj->peso)? '': parserQtd($lancamentoProjetoProdutoObj->peso),
                'total_custo_tecido' => empty($total_custo_tecido)?'':parserQtd($total_custo_tecido),
                'total_custo_insumo' => empty($total_custo_insumo)?'':parserQtd($total_custo_insumo),
                'total_custo_servico' => empty($total_custo_servico)?'':parserQtd($total_custo_servico),
            ]
        ];

        return response()->json($retorno);
    }

    public function editarProduto(LancamentoProdutoRequest $request){
        $fields = $request->only('id_produto', 'id_projeto', 'produto_codigo', 'produto_descricao', 'produto_descricao_hidden', 'produto_preco_venda', 'produto_quantidade', 'produto_detalhes', 'id_revisor', 'produto_ncm', 'produto_peso');

        try{
            $id_produto = decrypt($fields['id_produto']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        switch($lancamentoProjetoObj->cliente->uf){
            case 'SP':
                $estabelecimento = 5;
                break;
            default:
                $estabelecimento = 4;
        }
        $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem;

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $ncm = empty($fields['produto_ncm'])? '' : $fields['produto_ncm'];
        $peso = empty($fields['produto_peso'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $fields['produto_peso'])));

        $descricao = '';
        if(empty($fields['produto_descricao'])){
            $descricao = $fields['produto_descricao_hidden'];
        }else{
            $descricao = $fields['produto_descricao'];
        }
        $preco_venda = floatval(str_replace(",", ".", str_replace(".", "", $fields['produto_preco_venda'])));
        $quantidade = floatval(str_replace(",", ".", str_replace(".", "", $fields['produto_quantidade'])));

        $ultimo_indice = LancamentoProjetoProduto::select()->where('lancamento_projetos_id', $id_projeto)->max('indice');
        if(empty($ultimo_indice)){
            $indice = 1;
        }else{
            $indice = $ultimo_indice + 1;
        }

        $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($id_produto);

        if(empty($lancamentoProjetoProdutoObj->indice)){
            $ultimo_indice = LancamentoProjetoProduto::select()->where('lancamento_projetos_id', $id_projeto)->max('indice');
            if(empty($ultimo_indice)){
                $indice = 1;
            }else{
                $indice = $ultimo_indice + 1;
            }
            $lancamentoProjetoProdutoObj->indice = $indice;
        }

        $lancamentoProjetoProdutoObj->codigo_produto = $fields['produto_codigo'];
        $lancamentoProjetoProdutoObj->descricao = $descricao;
        $lancamentoProjetoProdutoObj->detalhe_producao = strtoupper($fields['produto_detalhes']);
        $lancamentoProjetoProdutoObj->quantidade = $quantidade;
        $lancamentoProjetoProdutoObj->preco_venda = $preco_venda;
        if(!empty($fields['produto_ncm'])){
            $lancamentoProjetoProdutoObj->ncm = $ncm;
        }
        if(!empty($fields['produto_peso'])){
            $lancamentoProjetoProdutoObj->peso = $peso;
        }
        $lancamentoProjetoProdutoObj->updated_by = Auth::id();
        $lancamentoProjetoProdutoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');

        $tecidos = $this->reajusteQuantidadeTecido($id_projeto, $lancamentoProjetoProdutoObj->id, $lancamentoProjetoProdutoObj->quantidade, false);
        $insumos = $this->reajusteQuantidadeInsumo($id_projeto, $lancamentoProjetoProdutoObj->id, $lancamentoProjetoProdutoObj->quantidade, false);
        $servicos = $this->reajusteQuantidadeServico($id_projeto, $lancamentoProjetoProdutoObj->id, $lancamentoProjetoProdutoObj->quantidade, false);

        $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $lancamentoProjetoProdutoObj->id);
        $result_faccao = $query_faccao->first();

        if(!empty($result_faccao)){       
            $query_faccao->whereNull('faccao_id');
            $result_faccao = $query_faccao->first();
            if(empty($result_faccao)){
                $produto_com_faccao = 1;
            }else{
                $produto_com_faccao = 0;  
            }
        }else{
            $produto_com_faccao = 0;
        }

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');
        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

        $total_custo = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;
        
        $total_custo_unitario = $total_custo / $total_quantidade;

        $custo = $lancamentoProjetoProdutoObj->valor_total_tecido->total + $lancamentoProjetoProdutoObj->valor_total_insumo->total + $lancamentoProjetoProdutoObj->valor_total_servico->total;
        $custo_unitario = $custo / $quantidade;

        $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

        $desconto = (!empty($custo) && !in_array($raiz_cnpj,$this->cnpjIntercompany())) ? (($custo_unitario/$lancamentoProjetoProdutoObj->preco_venda)-1) * 100 : 0;

        if($desconto > $desconto_maximo){
            $desconto_acima_permitido = true;
        }else{
            $desconto_acima_permitido = false;
        }
        $this->atualizarCustoPrecoProduto($lancamentoProjetoProdutoObj->id);

        $produto = [
            'id' => encrypt($lancamentoProjetoProdutoObj->id),
            'indice' => $lancamentoProjetoProdutoObj->indice,
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $fields['produto_codigo'],
            'descricao' => $descricao,
            'detalhes' => strtoupper($fields['produto_detalhes']),
            'preco_venda' => parserValor($preco_venda),
            'quantidade' => parserQtd($quantidade),
            'ncm' => $ncm,
            'peso' => parserQtd($peso),
            'total_tecido' => empty($lancamentoProjetoProdutoObj->valor_total_tecido->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_tecido->total),
            'total_insumo' => empty($lancamentoProjetoProdutoObj->valor_total_insumo->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_insumo->total),
            'total_servico' => empty($lancamentoProjetoProdutoObj->valor_total_servico->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_servico->total),
            'produto_com_faccao' => $produto_com_faccao,
            'total_custo' => empty($custo)? '' : parserValor($custo),
            'custo_unitario' => empty($custo_unitario)? '' : parserValor($custo_unitario),
            'desconto_acima_permitido' => $desconto_acima_permitido
        ];

        $total = [
            'total_quantidade' => empty($total_quantidade)? '' : parserQtd($total_quantidade),
            'total_custo_tecido' => empty($total_custo_tecido)? '' : parserQtd($total_custo_tecido),
            'total_custo_insumo' => empty($total_custo_insumo)? '' : parserQtd($total_custo_insumo),
            'total_custo_servico' => empty($total_custo_servico)? '' : parserQtd($total_custo_servico),
            'total_custo_total' => empty($total_custo)? '' : parserQtd($total_custo),
            'total_custo_unitario' => empty($total_custo_unitario)? '' : parserQtd($total_custo_unitario),
        ];

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produto' => $produto,
                'total' => $total
            ]
        ];

        return response()->json($retorno);
    }

    public function deletarProduto(Request $request){
        $fields = $request->only('id_produto', 'id_projeto', 'id_revisor');
        try{
            $id_produto = decrypt($fields['id_produto']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $query_tecido = LancamentoProjetoTecido::where('lancamento_projeto_produtos_id', $id_produto)->where('lancamento_projetos_id', $id_projeto)->first();
        $query_insumo = LancamentoProjetoInsumo::where('lancamento_projeto_produtos_id', $id_produto)->where('lancamento_projetos_id', $id_projeto)->first();
        $query_faccao = LancamentoProjetoFaccao::where('lancamento_projeto_produtos_id', $id_produto)->where('lancamento_projetos_id', $id_projeto)->first();

        if(!empty($query_tecido) || !empty($query_insumo) || !empty($query_faccao)){
            return response()->json([
                'status' => 'error',
                'message' => 'Esse Produto tem alguma relação. Favor excluí-las para poder excluir o produto.',
                'error' => [],
                'response' => []
            ],422);
        }
        
        $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($id_produto);
        $lancamentoProjetoProdutoObj->deleted_by = Auth::id();
        $lancamentoProjetoProdutoObj->save();
        $lancamentoProjetoProdutoObj->delete();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'total_exibicao' => empty($total_quantidade)?'':parserQtd($total_quantidade)
            ]
        ];

        return response()->json($retorno);
    }

    public function cancelarEdicaoProduto(Request $request){
        $fields = $request->only('id_produto', 'id_projeto');
        try{
            $id_produto = decrypt($fields['id_produto']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($id_produto);

        $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $lancamentoProjetoProdutoObj->id);
        $result_faccao = $query_faccao->first();

        if(!empty($result_faccao)){       
            $query_faccao->whereNull('faccao_id');
            $result_faccao = $query_faccao->first();
            if(empty($result_faccao)){
                $produto_com_faccao = 1;
            }else{
                $produto_com_faccao = 0;  
            }
        }else{
            $produto_com_faccao = 0;
        }

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);

        $estabelecimento = $lancamentoProjetoObj->estabelecimento;

        $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem;

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');
        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

        $total_custo = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;

        $total_custo_produto = $lancamentoProjetoProdutoObj->composicao_tecidos->sum('valor_total') + $lancamentoProjetoProdutoObj->composicao_insumos->sum('valor_total') + $lancamentoProjetoProdutoObj->composicao_servicos->sum('valor_total');
            
        $produto = [
            'id' => encrypt($lancamentoProjetoProdutoObj->id),
            'indice' => $lancamentoProjetoProdutoObj->indice,
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $lancamentoProjetoProdutoObj->codigo_produto,
            'descricao' => $lancamentoProjetoProdutoObj->descricao,
            'detalhes' => $lancamentoProjetoProdutoObj->detalhe_producao,
            'preco_venda' => parserValor($lancamentoProjetoProdutoObj->preco_venda),
            'custo_unitario' => empty($total_custo) ? '' : parserValor($total_custo / $total_quantidade),
            'quantidade' => parserQtd($lancamentoProjetoProdutoObj->quantidade),
            'total_exibicao' => parserQtd($total_quantidade),
            'ncm' => empty($lancamentoProjetoProdutoObj->ncm)? '' : $lancamentoProjetoProdutoObj->ncm,
            'peso' => empty($lancamentoProjetoProdutoObj->peso)? '' : parserQtd($lancamentoProjetoProdutoObj->peso),
            'total_tecido' => empty($lancamentoProjetoProdutoObj->valor_total_tecido->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_tecido->total),
            'total_insumo' => empty($lancamentoProjetoProdutoObj->valor_total_insumo->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_insumo->total),
            'total_servico' => empty($lancamentoProjetoProdutoObj->valor_total_servico->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_servico->total),
            'produto_com_faccao' => $produto_com_faccao,
            'total_custo' => empty($total_custo_produto) ? '' : parserValor($total_custo_produto)
        ];

        $total = [
            'total_quantidade' => empty($total_quantidade)? '' : parserQtd($total_quantidade),
            'total_custo_tecido' => empty($total_custo_tecido)? '' : parserQtd($total_custo_tecido),
            'total_custo_insumo' => empty($total_custo_insumo)? '' : parserQtd($total_custo_insumo),
            'total_custo_servico' => empty($total_custo_servico)? '' : parserQtd($total_custo_servico),
            'total_custo_total' => empty($total_custo)? '' : parserQtd($total_custo),
            'total_custo_unitario' => empty($total_custo)? '' : parserQtd($total_custo / $total_quantidade),
        ];

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produto' => $produto,
                'total' => $total
            ]
        ];

        return response()->json($retorno);
    }

    public function adicionarTecido(LancamentoTecidoRequest $request){
        $fields = $request->only('id_projeto', 'codigo', 'descricao', 'consumo', 'preco_unitario', 'custo_total', 'quantidade', 'consumo_total', 'id_produto', 'id_revisor', 'servico_tecido', 'servico_fator_conversao');

        $servico = '';

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $codigo = strtoupper($fields['codigo']);
        $descricao = $fields['descricao'];
        $consumo = $this->formtFloat($fields['consumo']);
        $preco_unitario = empty($fields['preco_unitario'])?0.00:$this->formtFloat($fields['preco_unitario']);
        $quantidade = empty($fields['quantidade'])?0.00:$this->formtFloat($fields['quantidade']);
        $consumo_total = $quantidade * $consumo;
        $total_custo = $preco_unitario * $consumo_total;

        $lancamentoProjetoTecidoObj = new LancamentoProjetoTecido;
        $lancamentoProjetoTecidoObj->lancamento_projetos_id = $id_projeto;
        $lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id = $id_produto;
        $lancamentoProjetoTecidoObj->codigo_produto = $codigo;
        $lancamentoProjetoTecidoObj->consumo_unitario = $consumo;
        $lancamentoProjetoTecidoObj->quantidade = $quantidade;
        $lancamentoProjetoTecidoObj->consumo_total = $consumo_total;
        $lancamentoProjetoTecidoObj->custo_unitario = $preco_unitario;
        $lancamentoProjetoTecidoObj->valor_total = $total_custo;
        $lancamentoProjetoTecidoObj->enviado_total = false;
        $lancamentoProjetoTecidoObj->created_by = Auth::id();
        $lancamentoProjetoTecidoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_quantidade = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $tecido = [
            'id' => encrypt($lancamentoProjetoTecidoObj->id),
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $codigo,
            'descricao' => $descricao,
            'preco_unitario' => parserValor($preco_unitario),
            'consumo_unitario' => parserValor4CasasDecimais($consumo),
            'consumo_total' => parserValor4CasasDecimais($consumo_total),
            'total_custo' => parserValor($total_custo),
            'tecido_total' => parserValor($total_quantidade),
        ];

        if(!empty($fields['servico_tecido'])){
            $preco_unitario = $this->formtFloat($this->getPreco($fields['servico_tecido'], $id_projeto));
            $arr['id_projeto'] = encrypt($id_projeto);
            $arr['tipo_de_servico'] = $fields['servico_tecido'];
            $arr['custo_unitario'] = parserValor($preco_unitario);
            $consumo_total = $consumo_total * $this->formtFloat($fields['servico_fator_conversao']);
            $arr['servico_fator_conversao'] = $this->formtFloat($fields['servico_fator_conversao']);
            $arr['quantidade'] = parserValor($consumo_total);
            $arr['custo_total'] = parserValor($preco_unitario * $consumo_total);
            $arr['id_produto'] = encrypt($id_produto);
            $arr['servico_tecido'] = $lancamentoProjetoTecidoObj->id;

            $servico_request = new GetTipoDeServicoRequest($arr);

            $servico = $this->adicionarServico($servico_request, true);
        }

        $this->atualizarCustoPrecoProduto($id_produto);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tecido' => $tecido,
                'servico' => $servico
            ]
        ];

        return response()->json($retorno);
    }

    public function getEditarTecido(Request $request){
        $fields = $request->only('id_tecido', 'id_projeto');

        try{
            $id_tecido = decrypt($fields['id_tecido']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($id_tecido);

        $total_custo_total = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id)->sum('valor_total');
        $total_custo_total = $total_custo_total - $lancamentoProjetoTecidoObj->valor_total;

        $query_servico = LancamentoProjetoFaccao::where('lancamento_projeto_tecidos_id', $lancamentoProjetoTecidoObj->id);
        $result_servico = $query_servico->first();
        if(!empty($result_servico)){
            $servico = $result_servico->tipo_servico_id;
            $fator_conversao = empty($result_servico->valor_de_conversao)? "1,00" : parserValor($result_servico->valor_de_conversao);
        }else{
            $servico = 0;
            $fator_conversao = "1,00";
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoTecidoObj->id),
                'projeto_id' => encrypt($id_projeto),
                'estabelecimento' => $lancamentoProjetoTecidoObj->codigo_estabelecimento,
                'codigo' => $lancamentoProjetoTecidoObj->codigo_produto,
                'consumo_unitario' => str_replace(".", "", parserValor4CasasDecimais($lancamentoProjetoTecidoObj->consumo_unitario)),
                'custo_total' => parserValor($lancamentoProjetoTecidoObj->valor_total),
                'total_custo_total' => empty($total_custo_total)? '':parserValor($total_custo_total),
                'servico' => $servico,
                'fator_conversao' => $fator_conversao
            ]
        ];

        return response()->json($retorno);
    }

    public function editarTecido(LancamentoTecidoRequest $request){
        $fields = $request->only('id_tecido', 'id_projeto', 'estabelecimento', 'codigo', 'descricao', 'consumo', 'preco_unitario', 'custo_total', 'quantidade', 'consumo_total', 'id_produto', 'id_revisor', 'servico_tecido', 'servico_fator_conversao');

        $servicos = '';

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
            $id_tecido = decrypt($fields['id_tecido']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $codigo = strtoupper($fields['codigo']);
        $descricao = $fields['descricao'];
        $consumo = $this->formtFloat($fields['consumo']);
        $preco_unitario = empty($fields['preco_unitario'])?0.00:$this->formtFloat($fields['preco_unitario']);
        $quantidade = empty($fields['quantidade'])?0.00:$this->formtFloat($fields['quantidade']);
        $consumo_total = $quantidade * $consumo;
        $total_custo = $preco_unitario * $consumo_total;

        $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($id_tecido);
        $lancamentoProjetoTecidoObj->codigo_produto = $codigo;
        $lancamentoProjetoTecidoObj->consumo_unitario = $consumo;
        $lancamentoProjetoTecidoObj->quantidade = $quantidade;
        $lancamentoProjetoTecidoObj->consumo_total = $consumo_total;
        $lancamentoProjetoTecidoObj->custo_unitario = $preco_unitario;
        $lancamentoProjetoTecidoObj->valor_total = $total_custo;
        $lancamentoProjetoTecidoObj->updated_by = Auth::id();
        $lancamentoProjetoTecidoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_quantidade = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $tecido = [
            'id' => encrypt($lancamentoProjetoTecidoObj->id),
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $codigo,
            'descricao' => $descricao,
            'preco_unitario' => parserValor($preco_unitario),
            'consumo_unitario' => parserValor4CasasDecimais($consumo),
            'consumo_total' => parserValor4CasasDecimais($consumo_total),
            'total_custo' => parserValor($total_custo),
            'tecido_total' => parserValor($total_quantidade),
        ];

        $query_servico = LancamentoProjetoFaccao::select();
        $query_servico->where('lancamento_projeto_tecidos_id', $id_tecido);
        $result_servico = $query_servico->first();
        if(!empty($result_servico)){
            $id_servico = $result_servico->id;
            if(empty($fields['servico_tecido'])){
                $arr['id_servico'] = encrypt($id_servico);
                $arr['id_projeto'] = encrypt($id_projeto);

                $servico_request = new Request($arr);

                $this->deletarServico($servico_request);
            }else{
                $query_servico->where('tipo_servico_id', $fields['servico_tecido']);
                $result_servico = $query_servico->first();
    
                if(empty($result_servico)){
                    $arr['id_servico'] = encrypt($id_servico);
                    $arr['id_projeto'] = encrypt($id_projeto);
    
                    $servico_request = new Request($arr);
    
                    $this->deletarServico($servico_request);

                    $preco_unitario = $this->formtFloat($this->getPreco($fields['servico_tecido'], $id_projeto));
                    $arr['id_projeto'] = encrypt($id_projeto);
                    $arr['tipo_de_servico'] = $fields['servico_tecido'];
                    $arr['custo_unitario'] = parserValor($preco_unitario);
                    $consumo_total = $consumo_total * $this->formtFloat($fields['servico_fator_conversao']);
                    $arr['servico_fator_conversao'] = $this->formtFloat($fields['servico_fator_conversao']);
                    $arr['quantidade'] = parserValor($consumo_total);
                    $arr['custo_total'] = parserValor($preco_unitario * $consumo_total);
                    $arr['id_produto'] = encrypt($id_produto);
                    $arr['servico_tecido'] = $lancamentoProjetoTecidoObj->id;
        
                    $servico_request = new GetTipoDeServicoRequest($arr);
        
                    $servico = $this->adicionarServico($servico_request, true);
                }else{             
                    $preco_unitario = $this->formtFloat($this->getPreco($result_servico->tipo_servico_id, $id_projeto));
                    $consumo_total = $consumo_total * $this->formtFloat($fields['servico_fator_conversao']);
                    $total_custo = $preco_unitario * $consumo_total;

                    $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($result_servico->id);

                    if(array_search($lancamentoProjetoFaccaoObj->tipo_servico_id, $this->servico_minimo_350) !== false){
                        if($consumo_total < 350){
                            $total_custo = $preco_unitario * 350;
                        }
                    }else if(array_search($lancamentoProjetoFaccaoObj->tipo_servico_id, $this->servico_minimo_500) !== false){
                        if($consumo_total < 500){
                            $total_custo = $preco_unitario * 500;
                        }
                    }
					
                    $lancamentoProjetoFaccaoObj->custo_unitario = $preco_unitario;
                    $lancamentoProjetoFaccaoObj->quantidade = $consumo_total;
                    $lancamentoProjetoFaccaoObj->valor_total = $total_custo;
                    $lancamentoProjetoFaccaoObj->valor_de_conversao = $this->formtFloat($fields['servico_fator_conversao']);
                    $lancamentoProjetoFaccaoObj->updated_by = Auth::id();
                    $lancamentoProjetoFaccaoObj->save();
                }
            }
            $servicos = $this->getServicosPorProduto($id_projeto, $id_produto);
        }else if(!empty($fields['servico_tecido'])){
            $preco_unitario = $this->formtFloat($this->getPreco($fields['servico_tecido'], $id_projeto));
            $arr['id_projeto'] = encrypt($id_projeto);
            $arr['tipo_de_servico'] = $fields['servico_tecido'];
            $arr['custo_unitario'] = parserValor($preco_unitario);
            $consumo_total = $consumo_total * $this->formtFloat($fields['servico_fator_conversao']);
            $arr['servico_fator_conversao'] = $this->formtFloat($fields['servico_fator_conversao']);
            $arr['quantidade'] = parserValor($consumo_total);
            $arr['custo_total'] = parserValor($preco_unitario * $consumo_total);
            $arr['id_produto'] = encrypt($id_produto);
            $arr['servico_tecido'] = $lancamentoProjetoTecidoObj->id;

            $servico_request = new GetTipoDeServicoRequest($arr);

            $servico = $this->adicionarServico($servico_request, true);

            $servicos = $this->getServicosPorProduto($id_projeto, $id_produto);
        }

        $this->atualizarCustoPrecoProduto($id_produto);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tecido' => $tecido,
                'servicos' => $servicos
            ]
        ];
        return response()->json($retorno);
    }

    public function deletarTecido(Request $request){
        $fields = $request->only('id_tecido', 'id_projeto', 'id_revisor');
        try{
            $id_tecido = decrypt($fields['id_tecido']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $query_servico = LancamentoProjetoFaccao::select()->where('lancamento_projeto_tecidos_id', $id_tecido)->first();

        if(!empty($query_servico)){
            return response()->json([
                'status' => 'error',
                'message' => 'Esse Tecido tem serviço. Favor excluí-lo para poder excluir o tecido.',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($id_tecido);
        $id_produto = $lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id;
        $lancamentoProjetoTecidoObj->deleted_by = Auth::id();
        $lancamentoProjetoTecidoObj->save();
        $lancamentoProjetoTecidoObj->delete();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_custo_total = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $this->atualizarCustoPrecoProduto($id_produto);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'total_exibicao' => empty($total_custo_total)?'':parserQtd($total_custo_total)
            ]
        ];

        return response()->json($retorno);
    }

    public function cancelarEdicaoTecido(Request $request){
        $fields = $request->only('id_tecido', 'id_projeto', 'id_produto');

        try{
            $id_tecido = decrypt($fields['id_tecido']);
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($id_tecido);

        $total_custo_total = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');
        $total_custo_total = $total_custo_total - $lancamentoProjetoTecidoObj->valor_total;

        $estabelecimentos = $this->estabelecimentos();

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoTecidoObj->id),
                'projeto_id' => encrypt($id_projeto),
                'estabelecimento' => $estabelecimentos[$lancamentoProjetoTecidoObj->codigo_estabelecimento],
                'codigo' => $lancamentoProjetoTecidoObj->codigo_produto,
                'consumo_unitario' => parserValor4CasasDecimais($lancamentoProjetoTecidoObj->consumo_unitario),
                'consumo_total' => parserValor4CasasDecimais($lancamentoProjetoTecidoObj->consumo_total),
                'custo_total' => parserValor($lancamentoProjetoTecidoObj->valor_total),
                'total_custo_total' => empty($total_custo_total)? '':parserValor($total_custo_total),
                'descricao' => $lancamentoProjetoTecidoObj->produto->descricao,
                'preco_unitario' => parserValor($lancamentoProjetoTecidoObj->custo_unitario),
                'total_custo' => parserValor($lancamentoProjetoTecidoObj->valor_total),
            ]
        ];

        return response()->json($retorno);
    }

    public function adicionarInsumo(LancamentoInsumoRequest $request){
        $fields = $request->only('id_projeto', 'estabelecimento', 'codigo', 'descricao', 'consumo', 'preco_unitario', 'custo_total', 'quantidade', 'consumo_total', 'id_produto', 'id_revisor');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $codigo = strtoupper($fields['codigo']);
        $descricao = $fields['descricao'];
        $consumo_total = $this->formtFloat($fields['consumo']);
        $preco_unitario = empty($fields['preco_unitario'])?0.00:$this->formtFloat($fields['preco_unitario']);
        $quantidade = empty($fields['quantidade'])?0.00:$this->formtFloat($fields['quantidade']);
        $consumo = $consumo_total / $quantidade;
        $total_custo = $preco_unitario * $consumo_total;

        $lancamentoProjetoInsumoObj = new LancamentoProjetoInsumo;
        $lancamentoProjetoInsumoObj->lancamento_projetos_id = $id_projeto;
        $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id = $id_produto;
        $lancamentoProjetoInsumoObj->codigo_produto = $codigo;
        $lancamentoProjetoInsumoObj->consumo_unitario = $consumo;
        $lancamentoProjetoInsumoObj->quantidade = $quantidade;
        $lancamentoProjetoInsumoObj->consumo_total = $consumo_total;
        $lancamentoProjetoInsumoObj->custo_unitario = $preco_unitario;
        $lancamentoProjetoInsumoObj->valor_total = $total_custo;
        $lancamentoProjetoInsumoObj->enviado_total = false;
        $lancamentoProjetoInsumoObj->created_by = Auth::id();
        $lancamentoProjetoInsumoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_quantidade = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $insumo = [
            'id' => encrypt($lancamentoProjetoInsumoObj->id),
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $codigo,
            'descricao' => $descricao,
            'preco_unitario' => parserValor($preco_unitario),
            'consumo_unitario' => parserQtd($consumo),
            'consumo_total' => parserQtd($consumo_total),
            'total_custo' => parserValor($total_custo),
            'insumo_total' => parserValor($total_quantidade),
        ];

        $this->atualizarCustoPrecoProduto($id_produto);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $insumo
        ];
        return response()->json($retorno);
    }

    public function getEditarInsumo(Request $request){
        $fields = $request->only('id_insumo', 'id_projeto');

        try{
            $id_insumo = decrypt($fields['id_insumo']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($id_insumo);

        $total_custo_total = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id)->sum('valor_total');
        $total_custo_total = $total_custo_total - $lancamentoProjetoInsumoObj->valor_total;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoInsumoObj->id),
                'projeto_id' => encrypt($id_projeto),
                'produto_id' => $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id,
                'codigo' => $lancamentoProjetoInsumoObj->codigo_produto,
                'consumo_total' => str_replace(".", "", parserQtd($lancamentoProjetoInsumoObj->consumo_total)),
                'custo_total' => parserValor($lancamentoProjetoInsumoObj->valor_total),
                'total_custo_total' => empty($total_custo_total)? '':parserValor($total_custo_total)
            ]
        ];

        return response()->json($retorno);
    }

    public function editarInsumo(LancamentoInsumoRequest $request){
        $fields = $request->only('id_insumo', 'id_projeto', 'estabelecimento', 'codigo', 'descricao', 'consumo', 'preco_unitario', 'custo_total', 'quantidade', 'consumo_total', 'id_produto', 'id_revisor');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_insumo = decrypt($fields['id_insumo']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $codigo = strtoupper($fields['codigo']);
        $descricao = $fields['descricao'];
        $consumo_total = $this->formtFloat($fields['consumo']);
        $preco_unitario = empty($fields['preco_unitario'])?0.00:$this->formtFloat($fields['preco_unitario']);
        $quantidade = empty($fields['quantidade'])?0.00:$this->formtFloat($fields['quantidade']);
        $consumo = $consumo_total / $quantidade;
        $total_custo = $preco_unitario * $consumo_total;

        $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($id_insumo);
        $lancamentoProjetoInsumoObj->codigo_produto = $codigo;
        $lancamentoProjetoInsumoObj->consumo_unitario = $consumo;
        $lancamentoProjetoInsumoObj->quantidade = $quantidade;
        $lancamentoProjetoInsumoObj->consumo_total = $consumo_total;
        $lancamentoProjetoInsumoObj->custo_unitario = $preco_unitario;
        $lancamentoProjetoInsumoObj->valor_total = $total_custo;
        $lancamentoProjetoInsumoObj->updated_by = Auth::id();
        $lancamentoProjetoInsumoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $total_quantidade = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $insumo = [
            'id' => encrypt($lancamentoProjetoInsumoObj->id),
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $codigo,
            'descricao' => $descricao,
            'preco_unitario' => parserValor($preco_unitario),
            'consumo_unitario' => parserQtd($consumo),
            'consumo_total' => parserQtd($consumo_total),
            'total_custo' => parserValor($total_custo),
            'insumo_total' => parserValor($total_quantidade),
        ];

        $this->atualizarCustoPrecoProduto($id_produto);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $insumo
        ];
        return response()->json($retorno);
    }

    public function deletarInsumo(Request $request){
        $fields = $request->only('id_insumo', 'id_projeto', 'id_revisor');
        try{
            $id_insumo = decrypt($fields['id_insumo']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($id_insumo);
        if(!empty($lancamentoProjetoInsumoObj)){
            $id_produto = $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id;
            $lancamentoProjetoInsumoObj->deleted_by = Auth::id();
            $lancamentoProjetoInsumoObj->save();
            $lancamentoProjetoInsumoObj->delete();
    
            $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
            $lancamentoProjetoObj->updated_by = Auth::id();
            $lancamentoProjetoObj->save();
        }else{
            $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::withTrashed()->find($id_insumo);

            $id_produto = $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id;
        }
        
        $total_custo_total = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');
    
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'total_exibicao' => empty($total_custo_total)?'':parserQtd($total_custo_total)
            ]
        ];

        $this->atualizarCustoPrecoProduto($id_produto);

        return response()->json($retorno);
    }
    
    public function cancelarEdicaoInsumo(Request $request){
        $fields = $request->only('id_insumo', 'id_projeto');

        try{
            $id_insumo = decrypt($fields['id_insumo']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($id_insumo);

        $total_custo_total = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_total = $total_custo_total - $lancamentoProjetoInsumoObj->valor_total;

        $estabelecimentos = $this->estabelecimentos();

        $validar = true;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoInsumoObj->id),
                'projeto_id' => encrypt($id_projeto),
                'estabelecimento' => $estabelecimentos[$lancamentoProjetoInsumoObj->codigo_estabelecimento],
                'codigo' => $lancamentoProjetoInsumoObj->codigo_produto,
                'consumo_unitario' => parserQtd($lancamentoProjetoInsumoObj->consumo_unitario),
                'consumo_total' => parserQtd($lancamentoProjetoInsumoObj->consumo_total),
                'custo_total' => parserValor($lancamentoProjetoInsumoObj->valor_total),
                'total_custo_total' => empty($total_custo_total)? '':parserValor($total_custo_total),
                'descricao' => $lancamentoProjetoInsumoObj->insumo_detalhes->descricao,
                'preco_unitario' => parserValor($lancamentoProjetoInsumoObj->custo_unitario),
                'total_custo' => parserValor($lancamentoProjetoInsumoObj->valor_total),
                'produto' => $lancamentoProjetoInsumoObj->produto->descricao,
                'validar' => $validar,
            ]
        ];

        return response()->json($retorno);
    }

    public function adicionarServico(GetTipoDeServicoRequest $request, $array_retorno = false){
        $fields = $request->only('faccao','id_projeto','tipo_de_servico', 'custo_unitario', 'quantidade', 'custo_total', 'id_produto', 'id_revisor', 'servico_tecido', 'servico_fator_conversao');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $preco = !empty($fields['custo_unitario'])?$this->formtFloat($fields['custo_unitario']):0.00;
        $quantidade = !empty($fields['quantidade'])?$this->formtFloat($fields['quantidade']):0.00;

        $verificar_quantidade_no_projeto = false;
        $mensagem_quantidade = '';
        if(array_search($fields['tipo_de_servico'], $this->servico_minimo_350) !== false){
            if($quantidade < 350){
                $custo_total = $preco * 350;
            }
            $verificar_quantidade_no_projeto = true;
            $mensagem_quantidade = 'Cálculo feito baseado no valor mínimo 350';
        }else if(array_search($fields['tipo_de_servico'], $this->servico_minimo_500) !== false){
            if($quantidade < 500){
                $custo_total = $preco * 500;
            }
            $verificar_quantidade_no_projeto = true;
            $mensagem_quantidade = 'Cálculo feito baseado no valor mínimo 500';
        }
        
        if(empty($custo_total)){
            $custo_total = $preco * $quantidade;
        }

        $lancamentoProjetoFaccaoObj = new LancamentoProjetoFaccao;
        if(!empty($fields['faccao'])){
            $lancamentoProjetoFaccaoObj->faccao_id = $fields['faccao'];
        }
        $lancamentoProjetoFaccaoObj->lancamento_projetos_id = $id_projeto;
        $lancamentoProjetoFaccaoObj->tipo_servico_id = $fields['tipo_de_servico'];
        $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id = $id_produto;
        $lancamentoProjetoFaccaoObj->quantidade = $quantidade;
        $lancamentoProjetoFaccaoObj->custo_unitario = $preco;
        $lancamentoProjetoFaccaoObj->valor_total = $custo_total;
        if(!empty($fields['servico_tecido'])){
            $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id = $fields['servico_tecido'];
            $lancamentoProjetoFaccaoObj->valor_de_conversao = $fields['servico_fator_conversao'];
        }
        $lancamentoProjetoFaccaoObj->created_by = Auth::id();
        $lancamentoProjetoFaccaoObj->save();

        if(empty($fields['servico_tecido'])){
            $this->ajustePrecoServicoComQuantidadeMinima($id_projeto, $fields['tipo_de_servico'], $lancamentoProjetoFaccaoObj->produto->codigo_produto);
        }else{
            $this->ajustePrecoServicoComQuantidadeMinima($id_projeto, $fields['tipo_de_servico'], $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
        }
        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        if($verificar_quantidade_no_projeto){
            $ajuste_servico = $this->reajusteQuantidadeServico($id_projeto, '', 0, true);
            $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($lancamentoProjetoFaccaoObj->id);
        }

        $total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $faccao = [
            'id' => encrypt($lancamentoProjetoFaccaoObj->id),
            'id_projeto' => encrypt($id_projeto),
            'codigo_tipo_de_servico' => $fields['tipo_de_servico'],
            'cnpj' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
            'faccao' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->nome,
            'codigo_tipo_de_servico' => $fields['tipo_de_servico'],
            'tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_de_servico->descricao,
            'unidade' => '',
            'preco_unitario' => $fields['custo_unitario'],
            'quantidade' => $fields['quantidade'],
            'custo_total' => $verificar_quantidade_no_projeto? '<div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="'.$mensagem_quantidade.'">'.parserValor($lancamentoProjetoFaccaoObj->valor_total).'*</div>' : parserValor($lancamentoProjetoFaccaoObj->valor_total),
            'total' => parserValor($total_custo_total),
            'produto' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? $lancamentoProjetoFaccaoObj->produto->descricao : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
            'tecido' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id) ? '' : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
            'produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado) ? '' : $lancamentoProjetoFaccaoObj->produto_acabado->descricao,
            'tipo' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
        ];

        $this->atualizarCustoPrecoProduto($id_produto);

        if($array_retorno == true){
            return $faccao;
        }else{
            $retorno = [
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => $faccao
            ];
            return response()->json($retorno);
        }
        
    }

    public function getEditarServico(Request $request){
        $fields = $request->only('id_servico', 'id_projeto');

        try{
            $id_servico = decrypt($fields['id_servico']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select()->where('id', $id_servico)->with(['tipo_de_servico','faccao' => function($query){
            $query->with(['fornecedor']);
        }])->first();

        $total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id)->sum('valor_total');

        $total_custo_total = $total_custo_total - $lancamentoProjetoFaccaoObj->valor_total;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoFaccaoObj->id),
                'produto_id' => $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id,
                'cnpj' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->nome.' - '.$lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
                'codigo_faccao' => empty($lancamentoProjetoFaccaoObj->faccao_id)? '' : $lancamentoProjetoFaccaoObj->faccao_id,
                'projeto_id' => encrypt($id_projeto),
                'tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_servico_id,
                'custo_unitario' => parserQtd($lancamentoProjetoFaccaoObj->custo_unitario),
                'custo_total' => parserValor($lancamentoProjetoFaccaoObj->valor_total),
                'total' => empty($total_custo_total)? '':parserValor($total_custo_total),
                'tecido' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? 0 : $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id,
                'codigo_produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado)? '' : $lancamentoProjetoFaccaoObj->codigo_produto_acabado,
                'produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado)? '' : $lancamentoProjetoFaccaoObj->produto_acabado->descricao,
                'quantidade' => empty($lancamentoProjetoFaccaoObj->quantidade)? parserQtd($lancamentoProjetoFaccaoObj->produto->quantidade) : parserQtd($lancamentoProjetoFaccaoObj->quantidade)
            ]
        ];

        return response()->json($retorno);
    }

    public function editarServico(Request $request){
        $fields = $request->only('faccao', 'id_servico', 'id_projeto','tipo_de_servico', 'custo_unitario', 'quantidade', 'custo_total', 'id_produto', 'id_revisor', 'servico_tecido', 'servico_codigo_produto_acabado');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_servico = decrypt($fields['id_servico']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $preco = !empty($fields['custo_unitario'])?$this->formtFloat($fields['custo_unitario']):0.00;
        $quantidade = !empty($fields['quantidade'])?$this->formtFloat($fields['quantidade']):0.00;

        $verificar_quantidade_no_projeto = false;
        $mensagem_quantidade = '';
        if(array_search($fields['tipo_de_servico'], $this->servico_minimo_350) !== false){
            if($quantidade < 350){
                $custo_total = $preco * 350;
            }
            $verificar_quantidade_no_projeto = true;
            $mensagem_quantidade = 'Cálculo feito baseado no valor mínimo 350';
        }else if(array_search($fields['tipo_de_servico'], $this->servico_minimo_500) !== false){
            if($quantidade < 500){
                $custo_total = $preco * 500;
            }
            $verificar_quantidade_no_projeto = true;
            $mensagem_quantidade = 'Cálculo feito baseado no valor mínimo 500';
        }
        
        if(empty($custo_total)){
            $custo_total = $preco * $quantidade;
        }

        $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($id_servico);
        if(!empty($fields['faccao'])){
            $lancamentoProjetoFaccaoObj->faccao_id = $fields['faccao'];
        }
        $lancamentoProjetoFaccaoObj->tipo_servico_id = $fields['tipo_de_servico'];
        $lancamentoProjetoFaccaoObj->quantidade = $quantidade;
        $lancamentoProjetoFaccaoObj->custo_unitario = $preco;
        $lancamentoProjetoFaccaoObj->valor_total = $custo_total;
        if(!empty($fields['servico_tecido'])){
            $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id = $fields['servico_tecido'];
            $lancamentoProjetoFaccaoObj->codigo_produto_acabado = strtoupper($fields['servico_codigo_produto_acabado']);
        }
        $lancamentoProjetoFaccaoObj->updated_by = Auth::id();
        $lancamentoProjetoFaccaoObj->save();

        if(empty($fields['servico_tecido'])){
            $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj->lancamento_projetos_id, $fields['tipo_de_servico'], $lancamentoProjetoFaccaoObj->produto->codigo_produto);
        }else{
            $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj->lancamento_projetos_id, $fields['tipo_de_servico'], $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
        }
        $query_soma = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto);
        $total_custo_total = $query_soma->sum('valor_total');
        $custo_total =  $query_soma->where('id', $lancamentoProjetoFaccaoObj->id)->first()->valor_total;

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        if($verificar_quantidade_no_projeto){
            $ajuste_servico = $this->reajusteQuantidadeServico($id_projeto, '', 0, true);
            $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($lancamentoProjetoFaccaoObj->id);
        }

        $total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $quantidade_original = LancamentoProjetoProduto::where('id', $id_produto)->first()->quantidade;

        $faccao = [
            'id' => encrypt($lancamentoProjetoFaccaoObj->id),
            'id_projeto' => encrypt($id_projeto),
            'cnpj' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
            'faccao' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->nome,
            'codigo_tipo_de_servico' => $fields['tipo_de_servico'],
            'tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_de_servico->descricao,
            'unidade' => '',
            'preco_unitario' => $fields['custo_unitario'],
            'quantidade' => $fields['quantidade'],
            'custo_total' => $verificar_quantidade_no_projeto? '<div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="'.$mensagem_quantidade.'">'.parserValor($lancamentoProjetoFaccaoObj->valor_total).'*</div>' : parserValor($lancamentoProjetoFaccaoObj->valor_total),
            'total' => parserValor($total_custo_total),
            'produto' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? $lancamentoProjetoFaccaoObj->produto->descricao : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
            'tecido' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? '' : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
            'produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado)? '' : $lancamentoProjetoFaccaoObj->produto_acabado->descricao,
            'tipo' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO',
            'quantidade_original' => parserQtd($quantidade_original)
        ];

        $this->atualizarCustoPrecoProduto($id_produto);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $faccao
        ];
        return response()->json($retorno);
    }

    public function deletarServico(Request $request){
        $fields = $request->only('id_servico', 'id_projeto', 'id_revisor');

        try{
            $id_servico = decrypt($fields['id_servico']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($fields['id_revisor'])){
            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);
        }

        $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($id_servico);
        $id_produto = $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id;
        $quantidade = $lancamentoProjetoFaccaoObj->produto->quantidade;
        $lancamentoProjetoFaccaoObj->deleted_by = Auth::id();
        $lancamentoProjetoFaccaoObj->save();
        $lancamentoProjetoFaccaoObj->delete();

        if(empty($lancamentoProjetoFaccaoObj->tecido)){
            $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj->lancamento_projetos_id, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->produto->codigo_produto);
        }else{
            $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj->lancamento_projetos_id, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
        }
        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $ajuste_servico = $this->reajusteQuantidadeServico($id_projeto, '', 0, true);

        $total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $id_produto)->sum('valor_total');

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'total_exibicao' => empty($total_custo_total)?'':parserQtd($total_custo_total)
            ]
        ];

        $this->atualizarCustoPrecoProduto($id_produto);

        return response()->json($retorno);
    }

    public function cancelarEdicaoFaccao(Request $request){
        $fields = $request->only('id_servico', 'id_projeto', 'id_produto');

        try{
            $id_servico = decrypt($fields['id_servico']);
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select()->where('id', $id_servico)->with(['tipo_de_servico','faccao' => function($query){
            $query->with(['fornecedor']);
        }])->first();

        $total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->where('lancamento_projeto_produtos_id', $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id)->sum('valor_total');

        $total_custo_total = $lancamentoProjetoFaccaoObj->valor_total;

        $quantidade_original = LancamentoProjetoProduto::where('id', $id_produto)->first()->quantidade;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoFaccaoObj->id),
                'id_projeto' => encrypt($id_projeto),
                'cnpj' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->nome,
                'codigo_faccao' => empty($lancamentoProjetoFaccaoObj->faccao_id)? '' : $lancamentoProjetoFaccaoObj->faccao_id,
                'codigo_tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_servico_id,
                'tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_de_servico->descricao,
                'unidade' => '',
                'preco_unitario' => parserValor($lancamentoProjetoFaccaoObj->custo_unitario),
                'custo_total' => parserValor($lancamentoProjetoFaccaoObj->valor_total),
                'quantidade' => parserQtd($lancamentoProjetoFaccaoObj->quantidade),
                'total' => empty($total_custo_total)? '':parserValor($total_custo_total),
                'produto' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? $lancamentoProjetoFaccaoObj->produto->descricao : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
                'tecido' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id) ? '' : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
                'produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado) ? '' : $lancamentoProjetoFaccaoObj->produto_acabado->descricao,
                'tipo' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO',
                'quantidade_original' => parserQtd($quantidade_original)
            ]
        ];

        return response()->json($retorno);
    }

    function formtFloat($value){
        $value = str_replace(",", ".", str_replace(".", "", $value));
        $value = floatval($value);
        
        return $value;
    }

    function resultado(Request $request){
        $fields = $request->only('id_projeto');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $lancamentoProjetoObj = LancamentoProjeto::with('cliente')->withTrashed()->find($id_projeto);
        $descricao_projeto = $lancamentoProjetoObj->nome_projeto;

        $total_pedido = 0.0;
        $comissao_valor = 0.0;
        $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto);
        $quantidade_total = $lancamentoProjetoProdutoObj->sum('quantidade');

        if(!empty($quantidade_total)){
            $produtos = $lancamentoProjetoProdutoObj->get();
            
            foreach($produtos as $produto){
                $total_pedido = $total_pedido + ($produto->quantidade * $produto->preco_venda);
                $comissao_valor = $comissao_valor + (($produto->quantidade * $produto->preco_venda)*($produto->comissao/100)); 
            }
    
            $custo_tecido = 0.0;
            $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto);
            $custo_tecido = $lancamentoProjetoTecidoObj->sum('valor_total');
    
            $custo_insumo = 0.0;
            $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto);
            $custo_insumo = $lancamentoProjetoInsumoObj->sum('valor_total');
    
            $mao_de_obra = 0.0;
            $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto);
            $mao_de_obra = $lancamentoProjetoFaccaoObj->sum('valor_total');
            
            $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

            if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
                $comissao = ($total_pedido > 0) ? ($comissao_valor/$total_pedido)*100 : 0;
                $preco_venda = ($quantidade_total > 0) ? $total_pedido/$quantidade_total : 0;
            }else{
                $preco_venda = 0;
            }

            $custo_total = $custo_tecido + $custo_insumo + $mao_de_obra;

            $query_estabelecimento_cidade = EstabelecimentoCidadeFob::select();
            $query_estabelecimento_cidade->where('cidade', $lancamentoProjetoObj->cliente->cidade);
            $query_estabelecimento_cidade->where('uf', $lancamentoProjetoObj->cliente->uf);
            $result_estabelecimento_cidade = $query_estabelecimento_cidade->first();

            $query_parametro_hospitalar = ParametroHospitalar::select();
            if(empty($result_estabelecimento_cidade)){
                $query_parametro_hospitalar->where('estado', $lancamentoProjetoObj->cliente->uf);
            }else{
                $query_parametro_hospitalar->where('estado', "GSP");
            }
            $result_parametro_hospitalar = $query_parametro_hospitalar->first(); 

            switch($lancamentoProjetoObj->estabelecimento){
                case 5:
                    $aliquotaPrecoObj = AliquotaPreco::where('origem', "SP")->where('estado', $lancamentoProjetoObj->cliente->uf)->first();
                    break;
                default:
                    $aliquotaPrecoObj = AliquotaPreco::where('origem', "TO")->where('estado', $lancamentoProjetoObj->cliente->uf)->first();
                    break;
            }
            
            $frete_adicional = floatval($aliquotaPrecoObj->frete_adicional);
            $frete_parametro = floatval($result_parametro_hospitalar->frete);
            $frete = $frete_parametro - $frete_adicional;
            $frete = 3;

            $frete_valor = (($custo_total * $frete)/100);
            $custo_total = $custo_total + $frete_valor;

            $custo_unitario = empty($quantidade_total)? 0.0 : $custo_total / $quantidade_total;

            $acima_tabela = empty($custo_total)? 0.0 : $this->formtFloat(parserValor((($total_pedido/$custo_total)-1)*100));
 
            $comissao = 3 + (int) ($acima_tabela/3);

            if($comissao > 15){
                $comissao = 15;
            }else if($comissao < 3){
                $comissao = 3;
            }

            $desconto = 0;
            $total_desconto = 0;

            if($acima_tabela < 0){
                $acima_tabela = $acima_tabela * (-1);
                $desconto = $acima_tabela;
                if($acima_tabela >= 3.01){
                    $acima_tabela = $acima_tabela - 3;
                    $comissao = $comissao - (0.5 * (int)($acima_tabela/1.5));
                }
            }

            $data_atual = Carbon::Now();

            $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

            if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
                $excecao_comissao = ProdutoPromocional::select();
                $excecao_comissao->where('codigo_cliente', $lancamentoProjetoObj->cliente->codigo);
                $excecao_comissao->where('codigo_vendedor', $lancamentoProjetoObj->detalhes_representante->id);
                $excecao_comissao->where('tipo_promocional', 'projeto');
                $excecao_comissao->where('data_expiracao', '>=', $data_atual);
                $excecao_comissao = $excecao_comissao->first();

                if(empty($excecao_comissao)){
                    $excecao_comissao = ProdutoPromocional::select();
                    $excecao_comissao->whereNull('codigo_cliente');
                    $excecao_comissao->where('codigo_vendedor', $lancamentoProjetoObj->detalhes_representante->id);
                    $excecao_comissao->where('tipo_promocional', 'projeto');
                    $excecao_comissao->where('data_expiracao', '>=', $data_atual);
                    $excecao_comissao = $excecao_comissao->first();
                }

                if(!empty($excecao_comissao)){
                    $comissao = $excecao_comissao->comissao;
                }else if($comissao < 2){
                    $comissao = 2;
                }else if($lancamentoProjetoObj->bionexo){
                    if($comissao < 3){
                        $comissao = 2;
                    }else{
                        $comissao = $comissao -1;
                    }
                }

                $representante = $lancamentoProjetoObj->detalhes_representante; 
                if($representante->tipo_usuario_id == 16 || $representante->tipo_usuario_id == 13){
                    $comissao = $representante->comissao_a;
                }
            }

            $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

            if(in_array($raiz_cnpj,$this->cnpjIntercompany())){
                $comissao = 0;
                $frete_valor = 0;
                $frete = 0;
                $desconto = 0;
            }

            $lancamentoProjetoObj->preco_venda = $preco_venda;
            $lancamentoProjetoObj->subtotal = $total_pedido;
            $lancamentoProjetoObj->quantidade_total = $quantidade_total;
            $lancamentoProjetoObj->comissao = $comissao;
            $lancamentoProjetoObj->custo_tecido = $custo_tecido;
            $lancamentoProjetoObj->custo_insumo = $custo_insumo;
            $lancamentoProjetoObj->mao_obra = $mao_de_obra;
            $lancamentoProjetoObj->custo_total = $custo_total;
            $lancamentoProjetoObj->custo_unitario_mn = $custo_unitario;
            $lancamentoProjetoObj->desconto = $desconto;
            $lancamentoProjetoObj->frete = $frete_valor;

            $lancamentoProjetoObj->valor_total_pedido = $total_pedido;
            $lancamentoProjetoObj->updated_by = Auth::id();
            $lancamentoProjetoObj->save();

            $resultado = [
                'descricao_projeto' => strtoupper($descricao_projeto),
                'subtotal_pedido' => parserValor($lancamentoProjetoObj->subtotal),
                'total_pedido' => parserValor($total_pedido),
                'custo_tecido' => parserValor($custo_tecido),
                'quantidade_total' => parserValor($quantidade_total),
                'custo_insumo' => parserValor($custo_insumo),
                'comissao' => parserValor($comissao).' %',
                'mao_de_obra' => parserValor($mao_de_obra),
                'custo_total' => parserValor($custo_total),
                'custo_unitario' => parserValor($custo_unitario),
                'preco_venda' => parserValor($preco_venda),
                'desconto' => empty($lancamentoProjetoObj->desconto)? '0,00 %': parserValor($lancamentoProjetoObj->desconto).' %',
                'valor_desconto' => empty($lancamentoProjetoObj->desconto)? '0,00' : parserValor($total_desconto) ,
                'frete_valor' => parserValor($frete_valor),
                'frete_porcetagem' => parserValor($frete),
                'regiao' => $this->getRegiao($lancamentoProjetoObj->cliente->uf, $lancamentoProjetoObj->cliente->cidade),
                'bionexo' => $lancamentoProjetoObj->bionexo
            ];
    
            $retorno = [
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => [
                    'resultado' => $resultado, 
                ]
            ];
    
            return  response()->json($retorno);
            
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Vazio',
                'error' => [],
                'response' => []
            ],422);
        }
        
    }

    private function getClienteCnpjCodigo($cliente){
        $cnpj = substr( $cliente , (strlen($cliente)-18), 18);
        $codigo = ClienteNasajon::select('codigo')->where('cpf_cnpj', $cnpj)->where('bloqueado', false)->first();

        $cliente = ['cnpj' => $cnpj, 'codigo' => $codigo->codigo];

        return $cliente;
    }

    private function getFaccaoCodigo($faccao){
        $codigo = Faccao::select('id')->where('cod_fornecedor', $faccao)->first();

        return $codigo->id;
    }

    public function reajusteQuantidade(Request $request){
        $fields = $request->only('id_projeto', 'id_produto');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $quantidade = LancamentoProjetoProduto::find($id_produto)->quantidade;

        $tecidos = $this->reajusteQuantidadeTecido($id_projeto, $id_produto, $quantidade, false);
        $insumos = $this->reajusteQuantidadeInsumo($id_projeto, $id_produto, $quantidade, false);
        $servicos = $this->reajusteQuantidadeServico($id_projeto, $id_produto, $quantidade, false);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tecidos' => $tecidos,
                'insumos' => $insumos,
                'servicos' => $servicos
            ]
        ];

        return  response()->json($retorno);
    }
    
    private function reajusteQuantidadeTecido($id_projeto, $id_produto, $quantidade, $todos){  

        $query = LancamentoProjetoTecido::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $result = $query->get();
        
        if(empty($result)){
            return '';
        }else{
            $tecidos = [];
            foreach($result as $tecido){
                $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($tecido->id);
                if($lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id == $id_produto || $todos){
                    $consumo = floatval($tecido->consumo_unitario);
                    $preco_unitario = $this->formtFloat($this->getPreco($tecido->codigo_produto, $id_projeto));
                    
                    if(empty($quantidade)){
                        $quantidade_rec = empty($tecido->quantidade)?0.00:floatval($tecido->quantidade);
                    }else{
                        $quantidade_rec = empty($quantidade)?0.00:floatval($quantidade);
                    }
                    $consumo_total = $quantidade_rec * $consumo;
                    $total_custo = $preco_unitario * $consumo_total;

                    $lancamentoProjetoTecidoObj->custo_unitario = $preco_unitario;
                    $lancamentoProjetoTecidoObj->quantidade = $quantidade_rec;
                    $lancamentoProjetoTecidoObj->consumo_total = $consumo_total;
                    $lancamentoProjetoTecidoObj->valor_total = $total_custo;
                    $lancamentoProjetoTecidoObj->updated_by = Auth::id();
                    $lancamentoProjetoTecidoObj->save();
                }

                $this->atualizarCustoPrecoProduto($lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id);

                $tecidos[] = [
                    'id' => encrypt($lancamentoProjetoTecidoObj->id),
                    'projeto_id' => encrypt($lancamentoProjetoTecidoObj->lancamento_projetos_id),
                    'codigo' => $lancamentoProjetoTecidoObj->codigo_produto,
                    'descricao' => $lancamentoProjetoTecidoObj->tecido_detalhes->descricao,
                    'preco_unitario' => parserValor($lancamentoProjetoTecidoObj->custo_unitario),
                    'consumo_unitario' => parserValor4CasasDecimais($lancamentoProjetoTecidoObj->consumo_unitario),
                    'consumo_total' => parserValor4CasasDecimais($lancamentoProjetoTecidoObj->consumo_total),
                    'total_custo' => parserValor($lancamentoProjetoTecidoObj->valor_total),
                    'produto' => $lancamentoProjetoTecidoObj->produto->descricao
                ];
            }
            $total_custo_total = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

            $return = [
                'tabela' => $tecidos,
                'total' =>  empty($total_custo_total)? '' : parserValor($total_custo_total)
            ];

            return $return;
        }
    }

    private function reajusteQuantidadeInsumo($id_projeto, $id_produto, $quantidade, $todos){
        $query = LancamentoProjetoInsumo::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $result = $query->get();
        
        if(empty($result)){
            return '';
        }else{
            $insumos = [];
            foreach($result as $insumo){
                $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($insumo->id);
                if($lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id == $id_produto || $todos){
                    $consumo = $insumo->consumo_unitario;
                    $preco_unitario = $this->formtFloat($this->getPreco($insumo->codigo_produto, $id_projeto));
                    if(empty($quantidade)){
                        $quantidade_rec = floatval(empty($insumo->quantidade)?0.00:$insumo->quantidade);
                    }else{
                        $quantidade_rec = floatval(empty($quantidade)?0.00:$quantidade);
                    }
                    $consumo_total = ceil($consumo * $quantidade_rec);
                    $total_custo = $preco_unitario * $consumo_total;
                    
                    if(!empty($preco_unitario)){
                        $lancamentoProjetoInsumoObj->custo_unitario = $preco_unitario;
                    }
                    
                    $lancamentoProjetoInsumoObj->quantidade = $quantidade_rec;
                    $lancamentoProjetoInsumoObj->consumo_total = $consumo_total;
                    $lancamentoProjetoInsumoObj->valor_total = $total_custo;
                    $lancamentoProjetoInsumoObj->updated_by = Auth::id();
                    $lancamentoProjetoInsumoObj->save();
                }

                $this->atualizarCustoPrecoProduto($lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id);

                $insumos[] = [
                    'id' => encrypt($lancamentoProjetoInsumoObj->id),
                    'projeto_id' => encrypt($lancamentoProjetoInsumoObj->lancamento_projetos_id),
                    'codigo' => $lancamentoProjetoInsumoObj->codigo_produto,
                    'descricao' => $lancamentoProjetoInsumoObj->insumo_detalhes->descricao,
                    'preco_unitario' => parserValor($lancamentoProjetoInsumoObj->custo_unitario),
                    'consumo_unitario' => parserQtd($lancamentoProjetoInsumoObj->consumo_unitario),
                    'consumo_total' => parserQtd($lancamentoProjetoInsumoObj->consumo_total),
                    'total_custo' => parserValor($lancamentoProjetoInsumoObj->valor_total),
                    'produto' => $lancamentoProjetoInsumoObj->produto->descricao
                ];
            }
            $total_custo_total = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

            $return = [
                'tabela' => $insumos,
                'total' => empty($total_custo_total)? '' : parserValor($total_custo_total)
            ];

            return $return;
        }
    }

    private function reajusteQuantidadeServico($id_projeto, $id_produto, $quantidade, $todos){
        $query = LancamentoProjetoFaccao::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $result = $query->get();
        
        if(empty($result)){
            return '';
        }else{
            $servicos = [];
            foreach($result as $servico){
                $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($servico->id);

                if($lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id == $id_produto || $todos){
                    $preco_unitario = $this->formtFloat($this->getPreco($servico->tipo_servico_id, $id_projeto));
                    if(!empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)){
                        $query_tecido = LancamentoProjetoTecido::select();
                        $query_tecido->where('id', $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id);
                        $result_tecido = $query_tecido->first();
                        $valor_de_conversao = empty($lancamentoProjetoFaccaoObj->valor_de_conversao)? 1 : floatval($lancamentoProjetoFaccaoObj->valor_de_conversao);
                        $quantidade_rec = floatval($result_tecido->consumo_total) * $valor_de_conversao;
                    }else{
                        if(empty($quantidade)){
                            $quantidade_rec = floatval(empty($servico->quantidade)?0.00:$servico->quantidade);
                        }else{
                            $quantidade_rec = floatval(empty($quantidade)?0.00:$quantidade);
                        }
                    }

                    $total_custo = $preco_unitario * $quantidade_rec;
                    
                    if(array_search($lancamentoProjetoFaccaoObj->tipo_servico_id, $this->servico_minimo_350) !== false){
                        $verificar_quantidade_no_projeto = LancamentoProjetoFaccao::select();
                        $verificar_quantidade_no_projeto->where('tipo_servico_id', $lancamentoProjetoFaccaoObj->tipo_servico_id);
                        $verificar_quantidade_no_projeto = $verificar_quantidade_no_projeto->get();
                        $verificar_quantidade_no_projeto_total_quantidade = $verificar_quantidade_no_projeto->sum('quantidade');
                        $verificar_quantidade_no_projeto_servico = $verificar_quantidade_no_projeto->count();
                        
                        if($verificar_quantidade_no_projeto_total_quantidade < 350){
                            $porcetagem_no_projeto = $quantidade_rec/$verificar_quantidade_no_projeto_total_quantidade;
                            $total_custo = $preco_unitario * (350) * $porcetagem_no_projeto;
                        }
                    }else if(array_search($lancamentoProjetoFaccaoObj->tipo_servico_id, $this->servico_minimo_500) !== false){
                        $verificar_quantidade_no_projeto = LancamentoProjetoFaccao::select();
                        $verificar_quantidade_no_projeto->where('tipo_servico_id', $lancamentoProjetoFaccaoObj->tipo_servico_id);
                        $verificar_quantidade_no_projeto = $verificar_quantidade_no_projeto->get();
                        $verificar_quantidade_no_projeto_total_quantidade = $verificar_quantidade_no_projeto->sum('quantidade');
                        
                        if($verificar_quantidade_no_projeto_total_quantidade < 500){
                            $porcetagem_no_projeto = $quantidade_rec/$verificar_quantidade_no_projeto_total_quantidade;
                            $total_custo = $preco_unitario * (500) * $porcetagem_no_projeto;
                        }
                    }

                    $lancamentoProjetoFaccaoObj->quantidade = $quantidade_rec;
                    $lancamentoProjetoFaccaoObj->valor_total = $total_custo;
                    $lancamentoProjetoFaccaoObj->updated_by = Auth::id();
                    $lancamentoProjetoFaccaoObj->save();

                    if(empty($lancamentoProjetoFaccaoObj->tecido)){
                        $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj->lancamento_projetos_id, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->produto->codigo_produto);
                    }else{
                        $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj->lancamento_projetos_id, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
                    }
                }

                $this->atualizarCustoPrecoProduto($lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id);
                
                $servicos[] = [
                    'id' => encrypt($lancamentoProjetoFaccaoObj->id),
                    'id_projeto' => encrypt($lancamentoProjetoFaccaoObj->lancamento_projetos_id),
                    'cnpj' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
                    'faccao' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->nome,
                    'tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_de_servico->descricao,
                    'preco_unitario' => parserValor($lancamentoProjetoFaccaoObj->custo_unitario),
                    'quantidade' => parserQtd($lancamentoProjetoFaccaoObj->quantidade),
                    'total_custo' => parserValor($lancamentoProjetoFaccaoObj->valor_total),
                    'produto' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? $lancamentoProjetoFaccaoObj->produto->descricao : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
                    'tecido' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id) ? '' : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
                    'produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado) ? '' : $lancamentoProjetoFaccaoObj->produto_acabado->descricao,
                    'tipo' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
                ];
            }
            $total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

            $return = [
                'tabela' => $servicos,
                'total' => empty($total_custo_total)? '' : parserValor($total_custo_total)
            ];

            return $return;
        }
    }

    private function getRepresentante(){
        $representantes_busca = User::select('codigo_representante', 'name')
            ->where('id', Auth::id())
            ->first(); 
        if(empty($representantes_busca->codigo_representante)){
            $representante = '';
        }else{
            $representante = [
                'codigo' => $representantes_busca->codigo_representante,
                'nome' => $representantes_busca->name
            ];
        }
        return $representante;
    }

    private function getStatus($value){
        $query_status = StatusProjeto::select();
        $query_status->where('posicao', $value);
        $result = $query_status->first();
        
        return $result->descricao;
    }

    public function duplicar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        $query = LancamentoProjeto::select();
        $query->withTrashed();
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $query->with(['cliente','condicoes_pagamento_web']);
        $result = $query->first();
        
        $dados = [
            'id' => encrypt($result->id),
            'numero_projeto' => $result->id,
            'nome_projeto' => $result->nome_projeto,
            'cliente' => (!empty($result->cliente)) ? $result->cliente->codigo : '',
            'cliente_documento' => (!empty($result->cliente)) ? $result->cliente->cpf_cnpj : '',
            'cliente_descricao' => empty($result->cliente)? '' : $result->cliente->nome.' - '.$result->cliente->cpf_cnpj,
            'valor'=> empty($result->valor_total_pedido)? '' : parserValor($result->valor_total_pedido)
        ];


        return view('programs.lancamento_de_projeto.representante.modal.duplicar')->with(['dados' => $dados]);
    }

    public function salvarDuplicada(Request $request){
        $fields = $request->only('id_projeto', 'nome_projeto', 'nome_cliente_duplicar', 'codigo_cliente_duplicar','cliente_documento_duplicar');
        
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $lancamentoProjetoObj = LancamentoProjeto::withTrashed()->find($id_projeto);
        
        $lancamentoProjetoObj_duplicada = new LancamentoProjeto;
        $lancamentoProjetoObj_duplicada->data = Carbon::now();
        $lancamentoProjetoObj_duplicada->nome_projeto = $fields['nome_projeto'];
        $lancamentoProjetoObj_duplicada->cliente_codigo = $fields['codigo_cliente_duplicar'];
        $lancamentoProjetoObj_duplicada->cliente_cpf_cnpj = $fields['cliente_documento_duplicar'];
        $lancamentoProjetoObj_duplicada->preco_venda = $lancamentoProjetoObj->preco_venda;
        $lancamentoProjetoObj_duplicada->pedido = $lancamentoProjetoObj->pedido;
        $lancamentoProjetoObj_duplicada->condicoes_pagamento_web_id = $lancamentoProjetoObj->condicoes_pagamento_web_id;
        $lancamentoProjetoObj_duplicada->valor_total_pedido = $lancamentoProjetoObj->valor_total_pedido;
        $lancamentoProjetoObj_duplicada->quantidade_total = $lancamentoProjetoObj->quantidade_total;
        $lancamentoProjetoObj_duplicada->comissao = $lancamentoProjetoObj->comissao;
        $lancamentoProjetoObj_duplicada->custo_tecido = $lancamentoProjetoObj->custo_tecido;
        $lancamentoProjetoObj_duplicada->custo_insumo = $lancamentoProjetoObj->custo_insumo;
        $lancamentoProjetoObj_duplicada->mao_obra = $lancamentoProjetoObj->mao_obra;
        $lancamentoProjetoObj_duplicada->custo_total = $lancamentoProjetoObj->custo_total;
        $lancamentoProjetoObj_duplicada->custo_unitario_mn = $lancamentoProjetoObj->custo_unitario_mn;
        $lancamentoProjetoObj_duplicada->tipo_frete = $lancamentoProjetoObj->tipo_frete;
        $lancamentoProjetoObj_duplicada->status = 0;
        $lancamentoProjetoObj_duplicada->users_codigo_representante = $lancamentoProjetoObj->users_codigo_representante;
        $lancamentoProjetoObj_duplicada->desconto = $lancamentoProjetoObj->desconto;
        $lancamentoProjetoObj_duplicada->subtotal = $lancamentoProjetoObj->subtotal;
        $lancamentoProjetoObj_duplicada->frete = $lancamentoProjetoObj->frete;

        $lancamentoProjetoObj_duplicada->linha = $lancamentoProjetoObj->linha;
		$lancamentoProjetoObj_duplicada->mostruario = $lancamentoProjetoObj->mostruario;
		$lancamentoProjetoObj_duplicada->produto_linhas_id = $lancamentoProjetoObj->produto_linhas_id;
		$lancamentoProjetoObj_duplicada->bionexo = $lancamentoProjetoObj->bionexo;
		$lancamentoProjetoObj_duplicada->nome_contato = $lancamentoProjetoObj->nome_contato;
        $lancamentoProjetoObj_duplicada->email_contato = $lancamentoProjetoObj->email_contato;
        
        $lancamentoProjetoObj_duplicada->created_by = Auth::id();
        $lancamentoProjetoObj_duplicada->save();

        $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'duplicacao_projeto', 'Projeto gerado '.$lancamentoProjetoObj_duplicada->id, Auth::id());
        $this->gravarHistoricoProjeto($lancamentoProjetoObj_duplicada->id, 'duplicacao_projeto', 'Projeto duplicado '.$lancamentoProjetoObj->id, Auth::id());

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', '=', $id_projeto);
        $result_produtos = $query_produto->get();

        foreach($result_produtos as $produto){
            $lancamentoProjetoProdutoObj = new LancamentoProjetoProduto;
            $lancamentoProjetoProdutoObj->lancamento_projetos_id = $lancamentoProjetoObj_duplicada->id;
            $lancamentoProjetoProdutoObj->indice = $produto->indice;
            $lancamentoProjetoProdutoObj->codigo_produto = $produto->codigo_produto;
            $lancamentoProjetoProdutoObj->quantidade = $produto->quantidade;
            $lancamentoProjetoProdutoObj->descricao = $produto->descricao;
            $lancamentoProjetoProdutoObj->preco_venda = $produto->preco_venda;
            $lancamentoProjetoProdutoObj->ncm = $produto->ncm;
            $lancamentoProjetoProdutoObj->peso = $produto->peso;
            $lancamentoProjetoProdutoObj->detalhe_producao = $produto->detalhe_producao;
			$lancamentoProjetoProdutoObj->custo = $produto->custo;
            $lancamentoProjetoProdutoObj->created_by = Auth::id();
            $lancamentoProjetoProdutoObj->save();

            $produtos[$produto->id] = $lancamentoProjetoProdutoObj->id;
        }

        $query_tecido = LancamentoProjetoTecido::select();
        $query_tecido->where('lancamento_projetos_id', '=', $id_projeto);
        $result_tecidos = $query_tecido->get();

        foreach($result_tecidos as $tecido){
            $lancamentoProjetoTecidoObj = new LancamentoProjetoTecido;
            $lancamentoProjetoTecidoObj->lancamento_projetos_id = $lancamentoProjetoObj_duplicada->id;
            $lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id =  $produtos[$tecido->lancamento_projeto_produtos_id];
            $lancamentoProjetoTecidoObj->codigo_estabelecimento = $tecido->codigo_estabelecimento;
            $lancamentoProjetoTecidoObj->codigo_produto = $tecido->codigo_produto;
            $lancamentoProjetoTecidoObj->consumo_unitario = $tecido->consumo_unitario;
            $lancamentoProjetoTecidoObj->quantidade = $tecido->quantidade;
            $lancamentoProjetoTecidoObj->consumo_total = $tecido->consumo_total;

            $tecido_custo_unitario = $this->formtFloat($this->getPreco($tecido->codigo_produto, $lancamentoProjetoObj_duplicada->id));
            $tecido_custo_total = $tecido_custo_unitario * $tecido->quantidade;

            $lancamentoProjetoTecidoObj->custo_unitario = $tecido_custo_unitario;
            $lancamentoProjetoTecidoObj->valor_total = $tecido_custo_total;


            $lancamentoProjetoTecidoObj->created_by = Auth::id();
            $lancamentoProjetoTecidoObj->save();

            $tecidos[$tecido->id] = $lancamentoProjetoTecidoObj->id;
        }

        $query_insumo = LancamentoProjetoInsumo::select();
        $query_insumo->where('lancamento_projetos_id', '=', $id_projeto);
        $result_insumos = $query_insumo->get();
        foreach($result_insumos as $insumo){
            $lancamentoProjetoInsumoObj = new LancamentoProjetoInsumo;
            $lancamentoProjetoInsumoObj->lancamento_projetos_id = $lancamentoProjetoObj_duplicada->id;
            $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id =  $produtos[$insumo->lancamento_projeto_produtos_id];
            $lancamentoProjetoInsumoObj->codigo_estabelecimento = $insumo->codigo_estabelecimento;
            $lancamentoProjetoInsumoObj->codigo_produto = $insumo->codigo_produto;
            $lancamentoProjetoInsumoObj->consumo_unitario = $insumo->consumo_unitario;
            $lancamentoProjetoInsumoObj->quantidade = $insumo->quantidade;
            $lancamentoProjetoInsumoObj->consumo_total = $insumo->consumo_total;

            $insumo_custo_unitario = $this->formtFloat($this->getPreco($insumo->codigo_produto, $lancamentoProjetoObj_duplicada->id));
            $insumo_custo_total = $insumo_custo_unitario * $insumo->quantidade;

            $lancamentoProjetoInsumoObj->custo_unitario = $insumo_custo_unitario;
            $lancamentoProjetoInsumoObj->valor_total = $insumo_custo_total;

            $lancamentoProjetoInsumoObj->created_by = Auth::id();
            $lancamentoProjetoInsumoObj->save();
        }

        $query_faccao = LancamentoProjetoFaccao::where('lancamento_projetos_id', '=', $id_projeto);
        $result_faccoes = $query_faccao->get();

        foreach($result_faccoes as $faccao){
            $lancamentoProjetoFaccaoObj = new LancamentoProjetoFaccao;
            $lancamentoProjetoFaccaoObj->lancamento_projetos_id = $lancamentoProjetoObj_duplicada->id;
            $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id =  $produtos[$faccao->lancamento_projeto_produtos_id];
            $lancamentoProjetoFaccaoObj->tipo_servico_id = $faccao->tipo_servico_id;
            $lancamentoProjetoFaccaoObj->quantidade = $faccao->quantidade;
            $servico_custo_unitario = $this->formtFloat($this->getPreco($faccao->tipo_servico_id, $lancamentoProjetoObj_duplicada->id));
            $lancamentoProjetoFaccaoObj->custo_unitario = $servico_custo_unitario;
            $lancamentoProjetoFaccaoObj->valor_total = $servico_custo_unitario*$faccao->quantidade;
            if(!empty($faccao->lancamento_projeto_tecidos_id)){
                $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id = $tecidos[$faccao->lancamento_projeto_tecidos_id];
                $lancamentoProjetoFaccaoObj->codigo_produto_acabado = $faccao->codigo_produto_acabado;
            }
            $lancamentoProjetoFaccaoObj->created_by = Auth::id();
            $lancamentoProjetoFaccaoObj->save();
        }

        $parametros = new Request ([
            'id_projeto' => encrypt($lancamentoProjetoObj_duplicada->id)
        ]);

        $this->resultado($parametros);

        $retorno = [
            'status' => 'success',
            'message' => 'Projeto duplicado com sucesso. O número do novo Projeto é '.$lancamentoProjetoObj_duplicada->id,
            'error' => '',
            'response' => ''
        ];

        return  response()->json($retorno, 200);
    }

    public function indexRevisao(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\RevisaoProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RevisaoProjeto');


        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
        $representantes = [];
        foreach ($representantes_busca as $key => $value) {
        	$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }
        
        $vendedores = $this->dadosVendedor();
        $tipo_frete = $this->dadosTipoFrete();
        $estados = $this->getEstados();

        return view('programs.lancamento_de_projeto.revisor.index')->with(['estados' => $estados, 'vendedor' => $vendedores, 'tipo_frete' => $tipo_frete, 'representantes' => $representantes]);
    }

    public function filterRevisao(Request $request){
        $fields = $request->only('num_projeto', 'nome_projeto', 'nome_cliente', 'representantes', 'data_inicio', 'data_fim', 'estados');

        $retorno = [];

        $query = LancamentoProjeto::select();
        $query->with(['cliente','detalhes_representante']);
        $query->whereNotIn('status', [0]);

        if(!empty($fields['estados'])){
            if($fields['estados'] != 99){
                if($fields['estados'] == 2){
                    $query->where(function($query) use($fields){
                        $query->whereHas('detalhes_status', function($query) use($fields){  
                            $query->where('status_projeto_exibicao_id', $fields['estados']);
                        });
                        $query->orWhere('status', 1);
                    });
                }else{
                    $query->whereHas('detalhes_status', function($query) use($fields){  
                        $query->where('status_projeto_exibicao_id', $fields['estados']);
                    });
                }
            }else{
                $query->withTrashed()->whereNotNull('deleted_at'); 
            }
        }
        if(is_numeric($fields['num_projeto'])){
            $query->where('id', $fields['num_projeto']);
        }
        if(!empty($fields['nome_projeto'])){
            $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
        }
        if(!empty($fields['nome_cliente'])){
            $query->where(function($query) use($fields){
                $cliente_busca = ClienteNasajon::select('cpf_cnpj')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['nome_cliente']).'%\'')->get();
                $query->WhereIn('cliente_codigo', $cliente_busca->pluck('cpf_cnpj'));
            });
        }
        if(!empty($fields['representantes'])){
            $query->where('users_codigo_representante', $fields['representantes']);
        }
        if (Auth::user()->tipo_usuario_id == 12){
            $query->where('users_codigo_representante', Auth::user()->codigo_representante);
        }

        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
		    $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $query->whereBetween('data', [$data_inicio, $data_fim]);
        }

        $result = $query->get();

        foreach($result as $projeto){

            $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projetos_id', $projeto->id);
            $query_faccao->whereNull('faccao_id');
            $result_faccao = $query_faccao->first();

            if(!empty($result_faccao)){
                $aprovar = 0;
                $faccao_aprovar = 0;
            }else{
                $query_produto_null_ncm_peso = LancamentoProjetoProduto::where('lancamento_projetos_id', $projeto->id)->whereNull('ncm')->whereNull('peso');
                $result_produto_null_ncm_peso = $query_produto_null_ncm_peso->first();

                if(!empty($result_produto_null_ncm_peso)){
                    $aprovar = 0;
                }else{
                    $aprovar = 1;
                }

                $faccao_aprovar = 1;
            }

            $retorno [] = [
                'id' => encrypt($projeto->id),
                'num_projeto' => $projeto->id,
                'nome_projeto' => empty($projeto->nome_projeto)? '': $projeto->nome_projeto,
                'cliente' => empty($projeto->cliente)? '' : $projeto->cliente->nome,
                'data' => parserData($projeto->data),
                'valor_total_pedido' => empty($projeto->valor_total_pedido)? '':parserValor($projeto->valor_total_pedido),
                'status_codigo' => intval($fields['estados']) == 99? 99 : $projeto->status,
                'status' => intval($fields['estados']) == 99? 'CANCELADO' : $this->getStatus($projeto->status),
                'vendedor' => empty($projeto->detalhes_representante)? '': $projeto->detalhes_representante->codigo_representante." - ".$projeto->detalhes_representante->name,
                'faccao_aprovar' => $faccao_aprovar,
                'aprovar' => $aprovar
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalEditarRevisao(Request $request){
        $fields = $request->only(['id', 'mostrar_botao_aprovar']);
        $mostrar_botao_aprovar = boolval($fields['mostrar_botao_aprovar']);
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $lancamentoProjetoObj = LancamentoProjeto::find($id);
        if(is_null($lancamentoProjetoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        if(empty($lancamentoProjetoObj->estabelecimento)){
            $lancamentoProjetoObj->estabelecimento = 4;
            $lancamentoProjetoObj->save();

            $tecidos = $this->reajusteQuantidadeTecido($lancamentoProjetoObj->id, '', 0, true);
            $insumos = $this->reajusteQuantidadeInsumo($lancamentoProjetoObj->id, '', 0, true);
            $servicos = $this->reajusteQuantidadeServico($lancamentoProjetoObj->id, '', 0, true);
        }

        $motivo = '';
        if($lancamentoProjetoObj->status == 9){
            $query_reprovado = HistoricoProjeto::where('lancamento_projetos_id', $id)->orderBy('id', 'desc')->first();
            if(!empty($query_reprovado->motivo)){
                $motivo = $query_reprovado->motivo;
            }
        }else if($lancamentoProjetoObj->status == 4){
            return response()->json([
                'status' => 'error',
                'message' => 'Projeto já foi aprovado',
                'error' => [],
                'response' => []
            ],422);
        }

        if(empty($result->email_contato)){
            if(!empty($result->cliente)){
                $parametros = new Request ([
                    'cliente' => trim($result->cliente->nome).' - '.$result->cliente_cpf_cnpj
                ]);
                $contato = $this->getContato($parametros, true);
                $result->nome_contato = $contato['nome_contato'];
                $result->email_contato = $contato['email_contato'];
                $result->save();
            }  
        }

        $info = $this->getInformacaoPreco($lancamentoProjetoObj->id);

        if($lancamentoProjetoObj->bionexo){
            $linha = $lancamentoProjetoObj->produto_linhas_id."_bionexo";
        }else if($lancamentoProjetoObj->mostruario){
            $linha = $lancamentoProjetoObj->produto_linhas_id."_mostruario";
        }else if($lancamentoProjetoObj->licitacao){
            $linha = $lancamentoProjetoObj->produto_linhas_id."_licitacao";
        }else{
            $linha = $lancamentoProjetoObj->produto_linhas_id;
        }

        $dados = [
            'id' => encrypt($lancamentoProjetoObj->id),
            'numero_projeto' => $lancamentoProjetoObj->id,
            'nome_projeto' => $lancamentoProjetoObj->nome_projeto,
            'cliente' => $lancamentoProjetoObj->cliente_codigo,
            'cliente_descricao' => empty($lancamentoProjetoObj->cliente)? '' : $lancamentoProjetoObj->cliente->nome.' - '.$lancamentoProjetoObj->cliente->cpf_cnpj,
            'pedido' => empty($lancamentoProjetoObj->pedido)? '' : $lancamentoProjetoObj->pedido,
            'pagamento' => $lancamentoProjetoObj->condicoes_pagamento_web_id,
            'pagamento_descricao' => empty($lancamentoProjetoObj->condicoes_pagamento_web)? '' : $lancamentoProjetoObj->condicoes_pagamento_web->descricao,
            'tipo_frete' => $lancamentoProjetoObj->tipo_frete,
            'representante'=> empty($lancamentoProjetoObj->users_codigo_representante)? '' : encrypt($lancamentoProjetoObj->users_codigo_representante),
            'motivo' => $motivo,
            'desconto' => '',
            'revisor' => Auth::id(),
            'estabelecimento' => $info['estabelecimento'],
            'estado_destino' => $info['estado_destino'],
            'media_condicao_pagamento' => $info['media_condicao_pagamento'],
            'preco_cif_fob' => $info['preco_cif_fob'],
            'cif_fob' => $info['cif_fob'],
            'linha' => $linha,
            'mensagem' => '',
            'nome_contato' => empty($lancamentoProjetoObj->nome_contato)? '' : $lancamentoProjetoObj->nome_contato,
            'email_contato' => empty($lancamentoProjetoObj->email_contato)? '' : $lancamentoProjetoObj->email_contato,
            'descricao_vendedor' => empty($lancamentoProjetoObj->detalhes_representante)? '': $lancamentoProjetoObj->detalhes_representante->codigo_representante." - ".$lancamentoProjetoObj->detalhes_representante->name,
        ];

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', '=', $id);
        $result_produtos = $query_produto->get();

        $produtos_tabela = [];
        $produto_select[0] = 'Selecione o Produto';

        foreach($result_produtos as $produto){
            $mensagem_error = "";
            $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $produto->id);
            $result_faccao = $query_faccao->first();

            if(!empty($result_faccao)){       
                $query_faccao->whereNull('faccao_id');
                $result_faccao = $query_faccao->first();
                if(empty($result_faccao)){
                    $produto_ok = true;
                }else{
                    $produto_ok = false;
                    $mensagem_error = "Serviço sem Facção.";
                }
            }else{
                $produto_ok = false;
                $mensagem_error = "Serviço sem Facção.";
            }

            $index = 0;

            while($index < count($produto->composicao_insumos)){
                if(!in_array($produto->composicao_insumos[$index]->insumo_detalhes->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                    $produto_ok = false;
                    $string = implode(",", $this->unidades_permitida_insumo);
                    $mensagem_error = $mensagem_error." A unidade padrão do insumo está incorreta, a unidade padrão deve ser ".$string.". Favor verificar com o setor responsável.";
                }
                $index++;
            }

            $custo = $produto->valor_total_tecido->total + ($produto->valor_total_insumo->total) + $produto->valor_total_servico->total;
            $custo_unitario = $custo / $produto->quantidade;

            $desconto_acima_permitido = false;

            $produtos_tabela[] = [
                'id' => encrypt($produto->id),
                'indice' => $produto->indice,
                'codigo' => $produto->codigo_produto,
                'descricao' => $produto->descricao,
                'preco_venda' => parserValor($produto->preco_venda),
                'quantidade' => parserQtd($produto->quantidade),
                'detalhe_producao' => $produto->detalhe_producao,
                'ncm' =>  empty($produto->ncm)? '' : $produto->ncm,
                'peso' => empty($produto->peso)? '' : parserQtd($produto->peso),
                'tecido_total' => empty($produto->valor_total_tecido->total)? '' : parserValor($produto->valor_total_tecido->total),
                'insumo_total' => empty($produto->valor_total_insumo->total)? '' : parserValor($produto->valor_total_insumo->total),
                'servico_total' => empty($produto->valor_total_servico->total)? '' : parserValor($produto->valor_total_servico->total),
                'produto_com_faccao' => $produto_ok,
                'custo_total' => empty($custo)? '' : parserValor($custo),
                'custo_unitario' => empty($custo_unitario)? '' : parserValor($custo_unitario),
                'desconto_acima_permitido' => $desconto_acima_permitido,
                'mensagem_error' => $mensagem_error,
            ];
            
            $produto_select[$produto->id] = $produto->descricao;
        }

        $produto_total_ex = parserValor($query_produto->sum('quantidade'));

        $tipo_frete = $this->dadosTipoFrete();
        $tipo_de_servicos = $this->tipoDeServicos();
        $estabelecimentos = $this->estabelecimentos();
        
        $mostrar_botao_aprovar = true;
        $intercompany = false;

        $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

        if(in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $intercompany = true;
        }

        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $lancamentoProjetoObj->id)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $lancamentoProjetoObj->id)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $lancamentoProjetoObj->id)->sum('valor_total');

        $total_custo_total = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;

        $total_custo_tecido = empty($total_custo_tecido)? '' : parserQtd($total_custo_tecido);
        $total_custo_insumo = empty($total_custo_insumo)? '' : parserQtd($total_custo_insumo);
        $total_custo_servico = empty($total_custo_servico)? '' : parserQtd($total_custo_servico);

        $total_custo_unitario = empty($total_custo_total)? '' : parserQtd($total_custo_total / $query_produto->sum('quantidade'));
        $total_custo_total = empty($total_custo_total)? '' : parserQtd($total_custo_total);

        $tipo_produto_producao = $this->tipoProdutoProjeto();

        return view('programs.lancamento_de_projeto.modal.projeto')->with(['dados' => $dados,'intercompany' => $intercompany,'tipo_frete' => $tipo_frete, 'tipo_de_servicos' => $tipo_de_servicos, 'estabelecimentos' => $estabelecimentos, 'produtos_tabela' => $produtos_tabela, 'produto_total_ex' => $produto_total_ex, 'mostrar_botao_aprovar' => $mostrar_botao_aprovar,'total_custo_tecido' => $total_custo_tecido, 'total_custo_insumo' => $total_custo_insumo, 'total_custo_servico' => $total_custo_servico, 'total_custo_total' => $total_custo_total, 'total_custo_unitario' => $total_custo_unitario, 'tipo_produto_producao' => $tipo_produto_producao]);
    }

    public function salvarFaccao(Request $request){
        $fields = $request->only('id', 'id_faccao');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($id);
        $lancamentoProjetoFaccaoObj->faccao_id = $fields['id_faccao'];
        $lancamentoProjetoFaccaoObj->updated_by = Auth::id();
        $lancamentoProjetoFaccaoObj->save();

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => ''
        ];
        return response()->json($retorno);
    }

    public function recusarProjetoModal(Request $request){
        $fields = $request->only('id_projeto');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->with(['cliente','detalhes_representante']);

        $info = [
            'id' => encrypt($lancamentoProjetoObj->id),
            'num_projeto' => $lancamentoProjetoObj->id,
            'nome_projeto' => $lancamentoProjetoObj->nome_projeto,
            'cliente' =>  $lancamentoProjetoObj->cliente->nome.' - '.$lancamentoProjetoObj->cliente->cnpj,
            'valor_total' => parserValor($lancamentoProjetoObj->valor_total_pedido),
            'vendedor' => $lancamentoProjetoObj->detalhes_representante->codigo_representante." - ".$lancamentoProjetoObj->detalhes_representante->name
        ];

        $MotivoRecusaPedidoObj = MotivoRecusaPedido::all();
		$motivos = [''=>''];
		foreach ($MotivoRecusaPedidoObj as $key => $value) {
			$motivos[$value['motivo']] = $value['motivo'];
		}

		return view("programs.lancamento_de_projeto.modal.recusar")->with(['info' => $info, 'motivos' => $motivos]);

    }
    
    function recusarProjeto(Request $request){
        $fields = $request->only('id_projeto', 'motivo_rejeicao');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

		if (empty(trim($fields['motivo_rejeicao']))){
			$error = [
				'status' => 'error',
				'message' => 'Informações inválidas',
				'errors' => [
					'motivo_rejeicao' => 'Digite uma justificativa'
				]
			];

			return response()->json($error, 422);
        }

        $query_aprovacao = AprovacaoDeProjeto::select();
        $query_aprovacao->where('projeto_id', $id_projeto);
        $query_aprovacao = $query_aprovacao->first();

        if(!empty($query_aprovacao)){
            $AprovacaoDeProjetoObj = AprovacaoDeProjeto::find($query_aprovacao->id);
            $AprovacaoDeProjetoObj->deleted_by = Auth::id();    
            $AprovacaoDeProjetoObj->save();
            $AprovacaoDeProjetoObj->delete();
        }

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->status = 9;
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        if(!empty($query_aprovacao)){
            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'recusado_aprovacao', $fields['motivo_rejeicao'], Auth::id());
        }else{
            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'recusado_revisor', $fields['motivo_rejeicao'], Auth::id());
        }

        $this->enviarEmailReprovado($id_projeto, trim($fields['motivo_rejeicao']));

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    function revisaoOkProjeto(Request $request){
        $fields = $request->only('id_projeto', 'motivo_rejeicao');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projetos_id', $id_projeto);
        $query_faccao->whereNull('faccao_id');
        $result_faccao = $query_faccao->first();

        if(!empty($result_faccao)){
            return response()->json([
                'status' => 'error',
                'message' => 'Falta relação do Serviço com a Facção.',
                'error' => [],
                'response' => []
            ],422);
        }
        $parametros = new Request ([
            'id_projeto' => encrypt($id_projeto)
        ]);

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);

        if(empty($lancamentoProjetoObj->estabelecimento)){
            $lancamentoProjetoObj->estabelecimento = 4;
            $lancamentoProjetoObj->save();
        }

        $this->resultado($parametros);

        $mensagem = '';
	
        $query_produto = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto);
        $produtos = $query_produto->get();

        $mensagem = $this->validarProjeto($lancamentoProjetoObj, $produtos, true);

        if(!empty($mensagem)){
            return response()->json([
                'status' => 'error',
                'message' => $mensagem,
                'error' => '',
                'response' => ''
            ],422);
        }

        $cadastro_novo_produto = $this->cadastroNovoProduto($id_projeto);

        if(!empty($cadastro_novo_produto)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [
                    'msg' => [
                        'cadastro_produto_novo' => $cadastro_novo_produto
                    ]
                ],
                'response' => ''
            ],422);
        }

        $query_produto_codigo_null = LancamentoProjetoProduto::whereNull('codigo_produto');
        $query_produto_codigo_null->where('lancamento_projetos_id', $id_projeto);
        $produto_codigo_null = $query_produto_codigo_null->first();
        $query_servico_tecidos = LancamentoProjetoFaccao::select();
        $query_servico_tecidos->where('lancamento_projetos_id', $id_projeto);
        $query_servico_tecidos->whereNotNull('lancamento_projeto_tecidos_id');
        $query_servico_tecidos->whereNull('codigo_produto_acabado');
        $result_servico_tecidos = $query_servico_tecidos->first();
        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        
        
        if(empty($produto_codigo_null) && empty($result_servico_tecidos)){
            $this->inserirAprovacaoProjeto($id_projeto);
        }else{
            $lancamentoProjetoObj->status = 10;
        }
        
        $lancamentoProjetoObj->revisor_user_id = Auth::id();
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();
        
        $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'finalizado_revisor', '', Auth::id());

        $arr = [
            'id_projeto' => $id_projeto,
            'id_faccao' => '', 
            'data_entrega_cliente' => $lancamentoProjetoObj->data_previsao_entrega, 
            'data_previsao_entrega' => $lancamentoProjetoObj->data_previsao_entrega
        ];

        $alteracao = new AlteracaoDataProjetoFaccaoRequest($arr);

        $this->alteracaoDataFaccao($alteracao);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }


    function calculoFaccao(Request $request){
        $fields = $request->only('preco', 'quantidade');

        $preco = $this->formtFloat($fields['preco']);
        $quantidade = $this->formtFloat($fields['quantidade']);
        $total = $preco * $quantidade;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'total' => parserValor($total)
            ]
        ];

        return  response()->json($retorno);
    }

    function getQuantidadeProduto(Request $request, $array = false){
        $id = $request->only('id');

        $query_produto = LancamentoProjetoProduto::find($id['id']);

        if($array){
            return parserValor($query_produto->quantidade);
        }else{
            $retorno = [
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => [
                    'quantidade' => empty($query_produto) ? '' : parserValor($query_produto->quantidade)
                ]
            ];
            
            return  response()->json($retorno);
        }
        
    }

    function getQuantidadeTecido(Request $request){
        $id = $request->only('id');

        $query_tecido = LancamentoProjetoTecido::find($id['id']);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'quantidade' => empty($query_tecido)? '' : parserValor($query_tecido->consumo_total)
            ]
        ];
        
        return  response()->json($retorno);
    }

    private function getRegiao($estado, $cidade){
        switch(strtoupper($estado)){
            case "MG":
            case "ES":
                return "SUDESTE";
            case "RJ":
                $query = EstabelecimentoCidadeFob::where('uf', 'ilike', $estado)->where('cidade', 'ilike', $cidade);
                $result = $query->first();

                if(empty($result)){
                    return "SUDESTE";
                }else{
                    return "GRANDE RJ";
                }
            case "SP":
                $query = EstabelecimentoCidadeFob::where('uf', 'ilike', $estado)->where('cidade', 'ilike', $cidade);
                $result = $query->first();

                if(empty($result)){
                    return "SUDESTE";
                }else{
                    return "GRANDE SP";
                }
            case "PR":
            case "SC":
            case "RS":
                return "SUL";
            case "DF":
            case "GO":
            case "MT":
            case "MS":
                return "CENTRO OESTE";
            case "AL":
            case "BA":
            case "CE":
            case "MA":
            case "PB":
            case "PE":
            case "PI":
            case "RN":
            case "SE":
                return "NORDESTE";
            case "AC":
            case "AP":
            case "AM":
            case "PA":
            case "RO":
            case "RR":
            case "TO":
                return "NORTE";
        }
    }

    public function getProduto(Request $request){
        $id = $request->only(['id_projeto'])['id_projeto'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', $id);
        $query_produto->orderBy('descricao');
        $result_produtos = $query_produto->get();

        $produto_select[0] = 'Selecione o Produto';

        foreach($result_produtos as $produto){ 
            $produto_select[$produto->id] = $produto->descricao;
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produtos' => $produto_select
            ]
        ];

        return response()->json($retorno);
    }

    public function getTecido(Request $request){
        $id_produto = $request->only(['id_produto'])['id_produto'];

        $query_tecido = LancamentoProjetoTecido::select();
        $query_tecido->with(['tecido_detalhes']);
        $query_tecido->where('lancamento_projeto_produtos_id', '=', $id_produto);
        $result_tecidos = $query_tecido->get();

        $tecido_select[0] = 'Selecione o Tecido';

        foreach($result_tecidos as $tecido){ 
            $tecido_select[$tecido->id] = $tecido->tecido_detalhes->descricao;
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tecidos' => $tecido_select
            ]
        ];

        return response()->json($retorno);
    }

    public function modalView(Request $request){
        $fields = $request->only('id_projeto');

        $projeto = LancamentoProjeto::withTrashed()->find($fields['id_projeto']);

        $estabelecimentos = $this->estabelecimentos();

        $query_produtos = LancamentoProjetoProduto::with(['arquivos', 'fichaTecnica', 'produto_detalhes'])->where('lancamento_projetos_id', $fields['id_projeto'])->get();
        $produtos = [];
        $ha_arquivos = false;
        foreach($query_produtos as $produto){
            $arquivos = []; 
            foreach($produto->arquivos as $arquivo){
                $nome_completo = explode(".", $arquivo->nome);
                $arquivos [] = [
                    'caminho' => Storage::url($arquivo->caminho),
                    'nome' => $nome_completo[0],
                    'nome_resumido' => strlen($nome_completo[0]) > 10? substr($nome_completo[0], 0, 10)."..." : $nome_completo[0],
                    'extensao' => strtolower($nome_completo[1])
                ];
                $ha_arquivos = true;
            }

            $produtos[] = [
                'codigo' => $produto->codigo_produto,
                'nome' => $produto->produto_detalhes->descricao,
                'detalhes' => $produto->detalhe_producao,
                'preco_venda' => parserValor($produto->preco_venda),
                'quantidade' => parserValor($produto->quantidade),
                'arquivos' => $arquivos,
                'id_ficha_tecnica' => empty($produto->fichaTecnica)? '' : $produto->fichaTecnica->id,
            ];
        }

        $query_tecidos = LancamentoProjetoTecido::where('lancamento_projetos_id', $fields['id_projeto']);
        $total_tecido = $query_tecidos->sum('valor_total');
        $query_tecidos = $query_tecidos->get();
        $tecidos = [];
        foreach($query_tecidos as $tecido){
            $tecidos[] = [
                'estabelecimento' => $estabelecimentos[$tecido->codigo_estabelecimento],
                'codigo' => $tecido->codigo_produto,
                'descricao' => $tecido->tecido_detalhes->descricao,
                'referencia_produto' => empty($tecido->produto->descricao)? '' : $tecido->produto->descricao,
                'consumo_por_peca' => parserValor4CasasDecimais($tecido->consumo_unitario),
                'consumo_total' => parserValor4CasasDecimais($tecido->consumo_total),
                'custo_unitario' => parserValor($tecido->custo_unitario),
                'custo_total' => parserValor($tecido->valor_total)
            ];
        }

        $query_insumos = LancamentoProjetoInsumo::where('lancamento_projetos_id', $fields['id_projeto']);
        $total_insumo = $query_insumos->sum('valor_total');
        $query_insumos = $query_insumos->get();

        $insumos = [];
        foreach($query_insumos as $insumo){
            $insumos[] = [
                'estabelecimento' => $estabelecimentos[$insumo->codigo_estabelecimento],
                'codigo' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'referencia_produto' => empty($insumo->produto->descricao)? '' : $insumo->produto->descricao,
                'consumo_por_peca' => parserValor($insumo->consumo_unitario),
                'consumo_total' => parserValor($insumo->consumo_total),
                'custo_unitario' => parserValor($insumo->custo_unitario),
                'custo_total' => parserValor($insumo->valor_total)
            ];
        }

        $query_faccoes = LancamentoProjetoFaccao::where('lancamento_projetos_id', $fields['id_projeto']);
        $total_faccao = $query_faccoes->sum('valor_total');
        $query_faccoes =$query_faccoes->get();
        $faccoes = [];
        $com_faccao = false;
        $id_faccoes = [];
        $index = 0;
        foreach($query_faccoes as $faccao){
            $faccoes[] = [
                'referencia_produto' => empty($faccao->produto->descricao)? '' : $faccao->produto->descricao,
                'cnpj' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->nome,
                'tipo_de_servico' => $faccao->tipo_de_servico->descricao,
                'custo_unitario' => parserValor($faccao->custo_unitario),
                'quantidade' => parserValor($faccao->quantidade),
                'custo_total' => parserValor($faccao->valor_total),
                'tecido' => empty($faccao->lancamento_projeto_tecidos_id) ? '' : $faccao->tecido->tecido_detalhes->descricao,
                'produto_acabado' => empty($faccao->codigo_produto_acabado) ? '' : $faccao->produto_acabado->descricao,
                'tipo' => empty($faccao->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
            ];

            // if(!empty($faccao->pedido_compras_gerado_nasajon)){
                if(!empty($faccao->faccao_id)){
                    $id_faccoes[$faccao->faccao_id] = [
                        'id_faccao' => encrypt($faccao->faccao_id),
                        'nome' => $faccao->faccao->fornecedor->nome
                    ]; 
                }
            // }

            if(!empty($faccao->faccao)){
                $com_faccao = true;
            }
        }

        $query_historico = HistoricoProjeto::select();
        $query_historico->with(['detalhes_usuario']);
        $query_historico->where('lancamento_projetos_id', $projeto->id);
        $query_historico->orderBy('id');
        $result_historico = $query_historico->get();

        $timeline = [];

        foreach($result_historico as $historico){
            if($historico->detalhes_natureza->status_projeto_exibicao_id != 9){
                if($historico->detalhes_natureza->status_projeto_exibicao_id == 3 || $historico->detalhes_natureza->status_projeto_exibicao_id == 4 || $historico->detalhes_natureza->status_projeto_exibicao_id == 5){
                    $motivo = "";
                }else if($historico->detalhes_natureza->status_projeto_exibicao_id == 10){
                    $motivo = str_replace("Status Novo", "", str_replace("Status Antigo", "", explode(":", $historico->motivo)));
                    $status_antigo = StatusProjeto::select()->where('posicao', intval(trim($motivo[1])))->first();
                    $status_novo = StatusProjeto::select()->where('posicao', intval(trim($motivo[2])))->first();

                    $status_antigo = empty($status_antigo)? '':$status_antigo->descricao;
                    $status_novo = empty($status_novo)? 'Cancelado':$status_novo->descricao;

                    $motivo [0] = "<b>Status Antigo:</b> ".$status_antigo;
                    $motivo [1] = "<b>Status Novo:</b> ".$status_novo;
                }else if($historico->natureza == 'error_integracao_nasajon'){
                    $motivo = '';
                }else{
                    $motivo = $historico->motivo;
                }

                if($historico->detalhes_natureza->status_projeto_exibicao_id == 4){
                    switch($historico->motivo){
                        case "Crédito":
                            $natureza = "Envio para Aprovação Crédito";
                            break;
                        case "Preço":
                            $natureza = "Envio para Aprovação Comercial";
                            break;
                        default:
                            $natureza = "Envio para Aprovação Crédito/Comercial";
                            break;
                    }
                }else if($historico->natureza == 'aprovado'){
                    switch($historico->motivo){
                        case "Aprovação de pedido parado em crédito":
                            $natureza = "Aprovado Crédito";
                            break;
                        case "Aprovação de pedido parado em preço":
                            $natureza = "Aprovado Comercial";
                            break;
                        default:
                            $natureza = "Aprovado Crédito/Comercial";
                            break;
                    }
                }else{
                    $natureza = $historico->detalhes_natureza->status_exibicao->descricao;
                }
                if(count($timeline) != 0 && $historico->natureza != 'duplicacao_projeto' && $historico->natureza != 'cancelamento_projeto'){
                    if($historico->natureza == 'recusado_revisor' || $historico->natureza == 'recusado_aprovacao'){
                        list ($data, $hora) = preg_split('/ /', $historico->created_at);
                        $timeline [] = [
                            'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                            'natureza' => "Recusado",
                            'data' => parserData($data),
                            'hora' => $hora,
                            'usuario' => $historico->detalhes_usuario->name,
                            'motivo' => $motivo,
                        ];
                        $timeline [] = [
                            'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                            'natureza' => $historico->detalhes_natureza->status_exibicao->descricao,
                            'data' => parserData($data),
                            'hora' => $hora,
                            'usuario' => $historico->detalhes_usuario->name,
                            'motivo' => $motivo,
                        ];
                    }else if($timeline [count($timeline) - 1 ]['id_status_exibicao'] != $historico->detalhes_natureza->status_projeto_exibicao_id || $historico->detalhes_natureza->status_projeto_exibicao_id == 10 || $historico->natureza == 'aprovado'){
                        list ($data, $hora) = preg_split('/ /', $historico->created_at);
                        
                        if($historico->detalhes_natureza->status_projeto_exibicao_id == 4 && $timeline [count($timeline) - 1 ]['id_status_exibicao'] == 3){
                            $timeline [] = [
                                'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                                'natureza' => "Produto Cadastrado",
                                'data' => parserData($data),
                                'hora' => $hora,
                                'usuario' => $historico->detalhes_usuario->name,
                                'motivo' => $motivo,
                            ];
                            $timeline [] = [
                                'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                                'natureza' => $natureza,
                                'data' => parserData($data),
                                'hora' => $hora,
                                'usuario' => $historico->detalhes_usuario->name,
                                'motivo' => $motivo,
                            ];
                        }else{
                            $timeline [] = [
                                'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                                'natureza' => $natureza,
                                'data' => parserData($data),
                                'hora' => $hora,
                                'usuario' => $historico->detalhes_usuario->name,
                                'motivo' => $motivo,
                            ];
                        }
                        
                        if(is_array($motivo)){
                            if(intval(trim($motivo[2])) == 99){
                                $timeline [] = [
                                    'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                                    'natureza' => "Cancelado",
                                    'data' => parserData($data),
                                    'hora' => $hora,
                                    'usuario' => $historico->detalhes_usuario->name,
                                    'motivo' => "",
                                ];
                            }
                        }
                    }
                }else if($historico->natureza != 'cancelamento_projeto'){
                    list ($data, $hora) = preg_split('/ /', $historico->created_at);

                    $timeline [] = [
                        'id_status_exibicao' => $historico->detalhes_natureza->status_projeto_exibicao_id,
                        'natureza' => $historico->detalhes_natureza->status_exibicao->descricao,
                        'data' => parserData($data),
                        'hora' => $hora,
                        'usuario' => $historico->detalhes_usuario->name,
                        'motivo' => $motivo,
                    ];
                }
            }
        }

        $estabelecimentos = returnEmpresasNasajonView();

        if(!empty($projeto->estabelecimento)){
            $estabelecimento = $estabelecimentos[$projeto->estabelecimento];
        }else{
            if(!empty($projeto->cliente)){
                switch(strtoupper($projeto->cliente->uf)){
                    case "SP":
                        $codigo_estabelecimento = 5;
                        break;
                    default:
                        $codigo_estabelecimento = 4;
                        break;
                }

                $estabelecimento = $estabelecimentos[$codigo_estabelecimento];
            }else{
                $estabelecimento = "";
            }
        }

        $tipo_producao = $this->tipoProdutoProjeto();
        $condicao_pagamento = '';

        $raiz_cnpj = substr($projeto->cliente->cpf_cnpj,0,10);

        if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $condicao_pagamento = $projeto->condicoes_pagamento_web->descricao;
        }

        $dados= [
            "id" => $projeto->id,
            "id_projeto" => encrypt($projeto->id),
            "nome_projeto" => $projeto->nome_projeto,
            "estabelecimento" => $estabelecimento,
            "vendedor" => $projeto->users_codigo_representante." - ".$projeto->detalhes_representante->name,
            "cliente_cnpj" => $projeto->cliente->cpf_cnpj,
            "cliente_nome" => $projeto->cliente->nome,
            "pedido" => $projeto->pedido,
            "cliente_cidade_uf" => $projeto->cliente->cidade." - ".$projeto->cliente->uf,
            "codigo_status" => $projeto->status,
            "status" => $this->getStatus($projeto->status),
            "numero_status" => $projeto->status,
            "data" => parserData($projeto->data),
            "condicao_pagamento_descr" => $condicao_pagamento,
            "tipo_frete" => $projeto->tipo_frete,
            "created_by" => $projeto->created_by." - ".$projeto->criado_por->name,
            "created_at" => date('d/m/Y H:i:s', strtotime($projeto->created_at)),
            "updated_by" => empty($projeto->updated_by)? '' : $projeto->updated_by." - ".$projeto->criado_por->name,
            "updated_at" => date('d/m/Y H:i:s', strtotime($projeto->updated_at)),
            'total_do_pedido' => parserValor($projeto->valor_total_pedido),
            'custo_total' => parserValor($projeto->custo_total),
            'produtos' => $produtos,
            'tecidos' => $tecidos,
            'insumos' => $insumos,
            'faccoes' => $faccoes,
            'id_faccoes' => $id_faccoes,
            'com_faccao' => $com_faccao,
            'comissao' => parserValor($projeto->comissao).' %',
            'total_tecido' => parserValor($total_tecido),
            'total_insumo' => parserValor($total_insumo),
            'total_faccao' => parserValor($total_faccao),
            'frete_adicional'=> parserValor($projeto->frete),
            'custo_unitario_mn'=> parserValor($projeto->custo_unitario_mn),
            'quantidade_total' => parserQtd($projeto->quantidade_total),
            'desconto' => empty($projeto->desconto)? '': parserValor($projeto->desconto).' %',
            'preco_medio_venda' => parserValor($projeto->preco_venda),
            'tipo_producao' => empty($projeto->produto_linhas_id)? '' : $tipo_producao['todos'][$projeto->produto_linhas_id],
            'nome_contato' => empty($projeto->nome_contato)? '' : $projeto->nome_contato,
            'email_contato' => empty($projeto->email_contato)? '' : $projeto->email_contato,
        ];

        return view('programs.lancamento_de_projeto.modal.view')->with(['dados' => $dados, 'timeline' => $timeline, 'ha_arquivos' => $ha_arquivos]);
    }

    public function getFichaTecnica($produto_ficha, $id_codigo, $id_projeto, $quantidade, $id_produto){
        $query_projeto_produto = LancamentoProjetoProduto::select();

        $query_projeto_produto->where('codigo_produto', 'ilike', $produto_ficha);
        
        $query_projeto_produto->whereHas('projeto_detalhes', function($query){
            $query->where('status', '>', 1);
        });
        $query_projeto_produto->orderBy('id', 'desc');
        $projeto_produto = $query_projeto_produto->first();

        $return_tecido = '';
        $return_insumos = '';
        $return_servico = '';

        if(!empty($projeto_produto)){
            $query_projeto_tecido = LancamentoProjetoTecido::where('lancamento_projeto_produtos_id', $projeto_produto->id);
            $projeto_tecido = $query_projeto_tecido->get();
        
            if(empty($projeto_tecido)){
                $return_tecido = '';
            }else{
                $tecidos = [];
                $estabelecimentos = $this->estabelecimentos();
                foreach($projeto_tecido as $tecido){
                    $estabelecimento = $estabelecimentos[$tecido->codigo_estabelecimento];
                    $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($tecido->id);

                    $consumo = (float)($tecido->consumo_unitario);
                    $preco_unitario = $this->formtFloat($this->getPreco($tecido->codigo_produto, $id_projeto));
                    $quantidade = empty($quantidade)?0.00:$quantidade;
                    
                    $consumo_total = $quantidade * $consumo;

                    $total_custo = $preco_unitario * $consumo_total;
    
                    
                    $lancamentoProjetoTecidoObj_duplicada = new LancamentoProjetoTecido;
                    $lancamentoProjetoTecidoObj_duplicada->lancamento_projetos_id = $id_projeto;
                    $lancamentoProjetoTecidoObj_duplicada->lancamento_projeto_produtos_id = $id_produto;
                    $lancamentoProjetoTecidoObj_duplicada->codigo_estabelecimento = $tecido->codigo_estabelecimento;
                    $lancamentoProjetoTecidoObj_duplicada->codigo_produto = $tecido->codigo_produto;
                    $lancamentoProjetoTecidoObj_duplicada->consumo_unitario = $consumo;
                    $lancamentoProjetoTecidoObj_duplicada->quantidade = $quantidade;
                    $lancamentoProjetoTecidoObj_duplicada->consumo_total = $consumo_total;
                    $lancamentoProjetoTecidoObj_duplicada->custo_unitario = $preco_unitario;
                    $lancamentoProjetoTecidoObj_duplicada->valor_total = $total_custo;
                    $lancamentoProjetoTecidoObj_duplicada->created_by = Auth::id();
                    $lancamentoProjetoTecidoObj_duplicada->save();

                    $tecidos[] = [
                        'id' => encrypt($lancamentoProjetoTecidoObj_duplicada->id),
                        'projeto_id' => encrypt($lancamentoProjetoTecidoObj_duplicada->lancamento_projetos_id),
                        'estabelecimento' => $estabelecimento,
                        'codigo' => $lancamentoProjetoTecidoObj_duplicada->codigo_produto,
                        'descricao' => $lancamentoProjetoTecidoObj_duplicada->tecido_detalhes->descricao,
                        'preco_unitario' => parserValor($lancamentoProjetoTecidoObj_duplicada->custo_unitario),
                        'consumo_unitario' => parserQtd($lancamentoProjetoTecidoObj_duplicada->consumo_unitario),
                        'consumo_total' => parserQtd($lancamentoProjetoTecidoObj_duplicada->consumo_total),
                        'total_custo' => parserValor($lancamentoProjetoTecidoObj_duplicada->valor_total),
                        'produto' => $lancamentoProjetoTecidoObj_duplicada->produto->descricao
                    ];
                }
                $total_custo_total = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

                $return_tecido = [
                    'tabela' => $tecidos,
                    'total' =>  empty($total_custo_total)? '' : parserValor($total_custo_total)
                ];
            }

            $query_projeto_insumo = LancamentoProjetoInsumo::where('lancamento_projeto_produtos_id', $projeto_produto->id);
            $projeto_insumo = $query_projeto_insumo->get();

            if(empty($projeto_insumo)){
                $return_insumos = '';
            }else{
                $insumos = [];
                $estabelecimentos = $this->estabelecimentos();
                foreach($projeto_insumo as $insumo){
                    $estabelecimento = $estabelecimentos[$insumo->codigo_estabelecimento];
                    $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($insumo->id);

                    $consumo = $insumo->consumo_unitario;
                    $preco_unitario = $this->formtFloat($this->getPreco($insumo->codigo_produto, $id_projeto));
                    $quantidade = empty($quantidade)?0.00:$quantidade;
                    $consumo_total = ceil($quantidade * $consumo);
                    $total_custo = $preco_unitario * $consumo_total;

                    $lancamentoProjetoInsumoObj_duplicada = new LancamentoProjetoInsumo;
                    $lancamentoProjetoInsumoObj_duplicada->lancamento_projetos_id = $id_projeto;
                    $lancamentoProjetoInsumoObj_duplicada->lancamento_projeto_produtos_id = $id_produto;
                    $lancamentoProjetoInsumoObj_duplicada->codigo_estabelecimento = $lancamentoProjetoInsumoObj->codigo_estabelecimento;
                    $lancamentoProjetoInsumoObj_duplicada->codigo_produto = $lancamentoProjetoInsumoObj->codigo_produto;
                    $lancamentoProjetoInsumoObj_duplicada->consumo_unitario = $consumo;
                    $lancamentoProjetoInsumoObj_duplicada->quantidade = $quantidade;
                    $lancamentoProjetoInsumoObj_duplicada->consumo_total = $consumo_total;
                    $lancamentoProjetoInsumoObj_duplicada->custo_unitario = $preco_unitario;
                    $lancamentoProjetoInsumoObj_duplicada->valor_total = $total_custo;
                    $lancamentoProjetoInsumoObj_duplicada->created_by = Auth::id();
                    $lancamentoProjetoInsumoObj_duplicada->save();

                    $insumos[] = [
                        'id' => encrypt($lancamentoProjetoInsumoObj_duplicada->id),
                        'projeto_id' => encrypt($lancamentoProjetoInsumoObj_duplicada->lancamento_projetos_id),
                        'estabelecimento' => $estabelecimento,
                        'codigo' => $lancamentoProjetoInsumoObj_duplicada->codigo_produto,
                        'descricao' => $lancamentoProjetoInsumoObj_duplicada->insumo_detalhes->descricao,
                        'preco_unitario' => parserValor($lancamentoProjetoInsumoObj_duplicada->custo_unitario),
                        'consumo_unitario' => parserQtd($lancamentoProjetoInsumoObj_duplicada->consumo_unitario),
                        'consumo_total' => parserQtd($lancamentoProjetoInsumoObj_duplicada->consumo_total),
                        'total_custo' => parserValor($lancamentoProjetoInsumoObj_duplicada->valor_total),
                        'produto' => $lancamentoProjetoInsumoObj_duplicada->produto->descricao
                    ];
                }
                $total_custo_total = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
    
                $return_insumos = [
                    'tabela' => $insumos,
                    'total' => empty($total_custo_total)? '' : parserValor($total_custo_total)
                ];
            }

            $query_projeto_servico = LancamentoProjetoFaccao::where('lancamento_projeto_produtos_id', $projeto_produto->id);
            $projeto_servico = $query_projeto_servico->get();

            if(empty($projeto_servico)){
				$return_servico = '';
			}else{
				$servicos = [];
				foreach($projeto_servico as $servico){
                    $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($servico->id);
                    
                    $preco_unitario = $this->formtFloat($this->getPreco($servico->tipo_servico_id, $id_projeto));
                    $quantidade = empty($quantidade)?0.00:$quantidade;
                    $custo_total = $preco_unitario * $quantidade;

                    $lancamentoProjetoFaccaoObj_duplicada = new LancamentoProjetoFaccao;
                    $lancamentoProjetoFaccaoObj_duplicada->lancamento_projetos_id = $id_projeto;
                    $lancamentoProjetoFaccaoObj_duplicada->tipo_servico_id = $lancamentoProjetoFaccaoObj->tipo_servico_id;
                    $lancamentoProjetoFaccaoObj_duplicada->lancamento_projeto_produtos_id = $id_produto;
                    $lancamentoProjetoFaccaoObj_duplicada->quantidade = $quantidade;
                    $lancamentoProjetoFaccaoObj_duplicada->custo_unitario = $preco_unitario;
                    $lancamentoProjetoFaccaoObj_duplicada->valor_total = $custo_total;
                    $lancamentoProjetoFaccaoObj_duplicada->lancamento_projeto_tecidos_id = $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id;;
                    $lancamentoProjetoFaccaoObj_duplicada->codigo_produto_acabado = $lancamentoProjetoFaccaoObj->codigo_produto_acabado;
                    $lancamentoProjetoFaccaoObj_duplicada->valor_de_conversao = $lancamentoProjetoFaccaoObj->valor_de_conversao;
                    $lancamentoProjetoFaccaoObj_duplicada->created_by = Auth::id();
                    $lancamentoProjetoFaccaoObj_duplicada->save();

                    if(empty($lancamentoProjetoFaccaoObj_duplicada->tecido)){
                        $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj_duplicada->lancamento_projetos_id, $lancamentoProjetoFaccaoObj_duplicada->tipo_servico_id, $lancamentoProjetoFaccaoObj->produto->codigo_produto);
                    }else{
                        $this->ajustePrecoServicoComQuantidadeMinima($lancamentoProjetoFaccaoObj_duplicada->lancamento_projetos_id, $lancamentoProjetoFaccaoObj_duplicada->tipo_servico_id, $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
                    }

					$especificacao = $lancamentoProjetoFaccaoObj_duplicada->with(['tipo_de_servico','faccao' => function($query){
						$query->with(['fornecedor']);
					}]);
					$servicos[] = [
						'id' => encrypt($lancamentoProjetoFaccaoObj->id),
						'id_projeto' => encrypt($lancamentoProjetoFaccaoObj->lancamento_projetos_id),
						'cnpj' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->cnpj_cpf,
						'faccao' => empty($lancamentoProjetoFaccaoObj->faccao)? '' : $lancamentoProjetoFaccaoObj->faccao->fornecedor->nome,
						'tipo_de_servico' => $lancamentoProjetoFaccaoObj->tipo_de_servico->descricao,
						'preco_unitario' => parserValor($lancamentoProjetoFaccaoObj->custo_unitario),
						'quantidade' => parserQtd($lancamentoProjetoFaccaoObj->quantidade),
						'total_custo' => parserValor($lancamentoProjetoFaccaoObj->valor_total),
                        'produto' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? $lancamentoProjetoFaccaoObj->produto->descricao : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
                        'tecido' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id) ? '' : $lancamentoProjetoFaccaoObj->tecido->tecido_detalhes->descricao,
                        'produto_acabado' => empty($lancamentoProjetoFaccaoObj->codigo_produto_acabado) ? '' : $lancamentoProjetoFaccaoObj->produto_acabado->descricao,
                        'tipo' => empty($lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
					];
				}
				$total_custo_total = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

				$return_servico = [
					'tabela' => $servicos,
					'total' => empty($total_custo_total)? '' : parserValor($total_custo_total)
				];

			}
        }
        
        $retorno = [
            'tecidos' => $return_tecido,
            'insumos' => $return_insumos,
            'servicos' => $return_servico
        ];

        return $retorno;
    }

    private function getPreco($codigo_produto, $id_projeto){
        $lancamentoProjetoObj = LancamentoProjeto::with('cliente')->find($id_projeto);

        switch(strtoupper($lancamentoProjetoObj->estabelecimento)){
            case 5:
                $origem = "SP";
                break;
            default:
                $origem = "TO";
                break;
        }

        $tipo_cliente  = (
            $lancamentoProjetoObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($lancamentoProjetoObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($lancamentoProjetoObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';

        $precoObj = new ListagemDePrecosController;
        $coluna = '';
        $raiz_cnpj = (!empty($lancamentoProjetoObj->cliente)) ? substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10) : '';

        if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){

            if ($lancamentoProjetoObj->condicoes_pagamento_web->media < 15){
                $coluna = 'prazo_vista';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 15 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 30){
                $coluna = 'prazo_15';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 30 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 45){
                $coluna = 'prazo_30';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 45 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 60){
                $coluna = 'prazo_45';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 60 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 75){
                $coluna = 'prazo_60';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 75 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 90){
                $coluna = 'prazo_75';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 90){
                $coluna = 'prazo_90';
            }

        }

        unset($arr);

        $arr['origem'] = $origem;
        $arr['produto'] = $codigo_produto;
        $arr['moeda'] = 'real';
        $arr['prazo_medio'] = (!in_array($raiz_cnpj,$this->cnpjIntercompany()) && !empty($lancamentoProjetoObj->condicoes_pagamento_web)) ? $lancamentoProjetoObj->condicoes_pagamento_web->media : '';
        $arr['frete'] = strtolower($lancamentoProjetoObj->tipo_frete);
        $arr['estado'] = $lancamentoProjetoObj->cliente->uf;
        $arr['tipo_cliente'] = $tipo_cliente;
        $arr['coluna'] = $coluna;

        $items = new ListaDePrecosRequest($arr);

        $precos = $precoObj->filter($items, false, false, true, true, false, false, false);

        $produtoQuery = ProdutoEspecificacao::with(['preco']);
        $produtoQuery->where('codigo_produto', $codigo_produto);

        $produto = $produtoQuery->first();

        return empty($precos[0]['coluna_a'])? empty($produto->preco->preco_real)? 0.0 : parserValor($produto->preco->preco_real) : $precos[0]['coluna_a'];
    }

    public function modalBuscarProjetoProduto(){
        return view('programs.lancamento_de_projeto.modal.buscar_projeto_produto');  
    }

    public function modalDesconto(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $query_projeto = LancamentoProjeto::find($id);
        $info = [
            'id' => encrypt($id),
            'desconto' => empty($query_projeto->desconto)? '' : parserValor($query_projeto->desconto),
        ];
        return view('programs.lancamento_de_projeto.modal.mudar_desconto')->with(['info' => $info]);  
    }

    private function checkCreditoProjeto(ClienteNasajon $cliente_nasajon, $estabelecimento, $condicao_pagamento_descricao, $valor_total){
		$Cliente = $cliente_nasajon;
		$parametros = ParametrosAprovacaoPedido::select('maximo_tempo_inativo', 'maximo_atraso_medio', 'maximo_ultimo_atraso', 'maximo_maior_atraso', 'maximo_duplicatas_vencidas', 'maximo_de_limite_credito')->where('estabelecimento', str_pad($estabelecimento, 2, '0', STR_PAD_LEFT))->first();

		$parametro_inativo_maisde 					= intval($parametros->maximo_tempo_inativo);
		$parametro_atraso_medio 					= intval($parametros->maximo_atraso_medio);
		$parametro_ultimo_atraso 					= intval($parametros->maximo_ultimo_atraso);
		$parametro_maior_atraso 					= intval($parametros->maximo_maior_atraso);
		$parametro_duplicatas_vencidas_a_mais 		= intval($parametros->maximo_duplicatas_vencidas);
        $data_hoje = Carbon::now()->setTime(0,0,0);

		if(in_array($Cliente->codigo, $this->codigo_cliente_balcao)){
			return true;
		}

        $razao_cnpj_textil = '06311274';

		$cpf_cnpj = $Cliente->cpf_cnpj;
		$codcads = [ $Cliente->codigo ];
		$ids = [ $Cliente->id ];

		$cnpjs = [ $cpf_cnpj ];

		$check_limite_credito = true;
		$check_data_limite_credito = true;

		$raiz_cnpj = $cpf_cnpj;
		
		if(strlen(trim($cpf_cnpj)) == 18){
			$cpf_cnpj = substr($cpf_cnpj, 0, 10);
		}

		$raiz_cnpj = $cpf_cnpj;
        if(
            $razao_cnpj_textil === str_replace('.', '', $raiz_cnpj)
        ){
			return true;
        }

		$grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
			->where('raiz_cnpj', $raiz_cnpj)
			->orWhereHas('participantes', function ($query) use ($raiz_cnpj){
				$query->where('raiz_cnpj', $raiz_cnpj);
			})->first();

		$clientesNasajonQuery = ClienteNasajon::select('*');
		$clientesNasajonQuery->where("codigo", "!=" , $Cliente->codigo);
		
		if(!is_null($grupoEmpresarialObj)){
			$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
				if(isset($grupoEmpresarialObj->participantes)){
					foreach($grupoEmpresarialObj->participantes as $participante){
						$query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
					}
					$grupo[] = $participante->raiz_cnpj;
				}

				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});

			$grupo = array_merge([$grupoEmpresarialObj->raiz_cnpj], $grupoEmpresarialObj->participantes->pluck('raiz_cnpj')->toArray());
		}
		else{
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $raiz_cnpj . '%');
			$grupo = [$raiz_cnpj];
		}

		$clientesNasajon = $clientesNasajonQuery->get()->toArray();
		unset($clientesNasajonQuery);

		foreach ($clientesNasajon as $key => $value) {
			$codcads[] = $value["codigo"];
			$cnpjs[] = $value["cpf_cnpj"];
			$ids[] = $value['id'];
		}
		unset($clientesNasajon);

		$limite_credito = 0;
		$ClienteCreditoObj = ClienteCredito::whereIn('raiz_cnpj', $grupo)->get();
		if(is_null($ClienteCreditoObj)){
			$check_limite_credito = false;
		}
		else{
			$limite_credito = (float) $ClienteCreditoObj->sum('valor');

			$data_atulizacaco_limite =  Carbon::parse($ClienteCreditoObj->min('data_atualizacao'))->setTime(0,0,0);
			$maximo_de_limite_credito = $parametros->maximo_de_limite_credito;
			$data_atulizacaco_limite->addMonths($maximo_de_limite_credito);
			if($data_hoje->gt($data_atulizacaco_limite)){
				$check_data_limite_credito = false;
			}
		}
		$nota_credito = 0;
		$notasCredito = NotasCreditoReceberNasajon::
			whereIn('cod_cliente', $codcads)
			->get();

		$nota_credito += $notasCredito->sum('valor');
		
		$limite_credito += $nota_credito;

		$cliente_duplicata_aberta 				= [];
		$cliente_cheques_aberta 				= [];
		$cliente_cheque_sem_fundo_lucros_perdas = false;
		$cliente_cheque_sem_fundo_negociacao 	= false;


		$valor_pedido = floatval($valor_total);
		$pedidos_em_aberto_nasjon = PedidosVendaNasajon::select('*')
			->whereIn('situacao_descricao', ['Em Faturamento', 'Em separação', 'Aberto', 'Aguardando Documento'])
			->where(function ($query){
				$query->where('grupodeoperacao', 'VENDA')
				->orWhereNull('grupodeoperacao');
			})
			->where('rascunho', false)
			->whereIn('cliente', $ids)->sum('valor');
		$pedidos_em_aberto = (float) $pedidos_em_aberto_nasjon;
		
		$titulosNasajon = TitulosEmAbertoNasajon::whereIn('cod_cliente', $codcads)->get();
		$titulos_faturados = ['' => 0];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados){
            $key = $item->numero . '' . $item->codigo;
            if(!isset($titulos_faturados[$key])){
                $titulos_faturados[$key] = floatval($item->saldotitulo);
            }
		});
		
		$cliente_duplicata_aberta['data'] = $titulosNasajon->min('vencimento');
		$cliente_duplicata_aberta['total'] = (float) array_sum($titulos_faturados);
		if(is_null($cliente_duplicata_aberta['data'])){
			$cliente_duplicata_aberta['data'] = date('Y-m-d 00:00:00');
		}

		$ChequesRecebidoObj = ChequesEmAbertoNasajon::whereIn('cod_cliente', $codcads);
		$cliente_cheques_aberta['data'] = $ChequesRecebidoObj->min('data_vencimento');
		$cliente_duplicata_aberta['total'] += (float) $ChequesRecebidoObj->sum('valor');

		$check_prepago = false;

		$pedidosPrePagosObj = PedidosPrePago::with(['pedidoNasajon' => function($query){ $query->where('grupodeoperacao', 'VENDA'); }, 'pedidoNasajon.nota'])
		->whereHas('pedido', function($query) use ($codcads){
			$query->whereIn('cod_cliente', $codcads);
		})
		->get();

		$pedidosPrePagosObj = $pedidosPrePagosObj->filter( function ($value){
			return isset($value->pedidoNasajon) && isset($value->pedidoNasajon->nota);
		});
		
		$valor_total_prepago = $pedidosPrePagosObj->sum('valor')??0;
		$valor_baixado_prepago = $pedidosPrePagosObj->sum('valor_pago')??0;
		$total_pre_pagos = $valor_total_prepago - $valor_baixado_prepago;

		if($total_pre_pagos > 0){
			$check_prepago = true;
		}

		$ChequesObj = Cheque::whereIn('cliente_cpf_cnpj', $cnpjs)->whereHas('pedidos_prepagos')->where('bom_para', '>', Carbon::Now()->format('Y-m-d'))->where('tipo', 'cheque')->get();

		$chque_pre = $ChequesObj->sum('valor');

		$total_pre_pagos += $chque_pre;

		if(!is_null($cliente_cheques_aberta['data'])){
			if(strtotime($cliente_duplicata_aberta['data']) > strtotime($cliente_cheques_aberta['data'])){
				$cliente_duplicata_aberta['data'] = $cliente_cheques_aberta['data'];
			}
		}

        $cliente_duplicata_aberta['data'] = Carbon::parse($cliente_duplicata_aberta['data'])->setTime(0,0,0);

		if(
			in_array($condicao_pagamento_descricao, $this->condicao_de_pagamento_livre)
		){
			if($parametro_duplicatas_vencidas_a_mais > ((strtotime(date('Y-m-d 00:00:00')) - strtotime($cliente_duplicata_aberta['data'])) / 86400)){
				return true;
			}
			else {
				return false;
			}
		}

		$check_prorrogacao = false;
		if($titulosNasajon->where('tem_prorrogacao', true)->isNotEmpty()){
			$check_prorrogacao = true;
		}


		if(
			$check_limite_credito === false ||
			$check_data_limite_credito === false ||
			$limite_credito <= 0 ||
			$check_prorrogacao === true ||
			$limite_credito <= ($valor_pedido + $pedidos_em_aberto + floatval($cliente_duplicata_aberta['total']) + $total_pre_pagos) ||
			$cliente_duplicata_aberta['data']->lt($data_hoje) ||
			$check_prepago === true
		){
			return [
				'prorrogacao' => $check_prorrogacao,
				'existe_limite_credito' => $check_limite_credito,
				'data_limite_credito' => $check_data_limite_credito,
				'limite_credito_disponivel' <= ($limite_credito <= 0 || $limite_credito <= ($valor_pedido + $pedidos_em_aberto + floatval($cliente_duplicata_aberta['total']))),
				'duplicatas_vencidas_a_mais' => $cliente_duplicata_aberta['data']->lt($data_hoje)
			];
		}else{
			return true;
		}
	}

    public function aprovacao(Request $request){
        $fields = $request->only('id_aprovacao');

        $id_aprovacao = $fields['id_aprovacao'];

        $AprovacaoDeProjetoObj = AprovacaoDeProjeto::find($id_aprovacao);
        $AprovacaoDeProjetoObj->deleted_by = Auth::id();

        $id_projeto = $AprovacaoDeProjetoObj->projeto_id;

        $AprovacaoDeProjetoObj->save();
        $AprovacaoDeProjetoObj->delete();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->status = 4;
        $lancamentoProjetoObj->linha = $lancamentoProjetoObj->itens[0]->produto_detalhes->linha;
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $remessaProjetoObj = new EnvioProjetoFaccao;
        $remessaProjetoObj->lancamento_projetos_id = $lancamentoProjetoObj->id;
        $remessaProjetoObj->created_by = Auth::id();
        $remessaProjetoObj->save();
        
        $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'aprovado_pedido', 'Projeto Aprovado Automático', Auth::id());

        $fichaTecnicaObj = new FichaTecnicaProdutoController;
        $fichaTecnicaObj->adicionarAtravesProjeto($lancamentoProjetoObj->id);
        $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'ficha_tecnica', '', Auth::id());

        $necessidadeComprasControllerObj = new NecessidadeComprasController;
        $necessidadeComprasControllerObj->adicionarAtravesProjeto($lancamentoProjetoObj);

        if($lancamentoProjetoObj->mostruario === false && !in_array(str_replace('.', '', explode('/', $lancamentoProjetoObj->cliente_cpf_cnpj)[0]), $this->empresas_mn)){
            $pedidoPortalControllerObj = new PedidoPortalController;
            $pedidoPortalControllerObj->gerarPedidoProgramadoAtravesProjeto($lancamentoProjetoObj);
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modal(Request $request){

		$fields = $request->only('projeto_id');

		$AprovacaoDeProjetoObj =  AprovacaoDeProjeto::with(['projeto', 'projeto.cliente', 'projeto.condicoes_pagamento_web', 'nivelaprovacao', 'nivelaprovacaoPreco'])->findOrFail($fields['projeto_id']);

		$projetoObj = $AprovacaoDeProjetoObj->projeto;

		$parametrosObj = ParametrosAprovacaoPedido::where('estabelecimento', str_pad($projetoObj->estabelecimento, 2, '0', STR_PAD_LEFT))
			->first();

		$precos_aprovados = 'Todos aprovados';
		
		$mostrar_botao = false;


        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $projetoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            !in_array(str_pad($projetoObj->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $preco_custo = true;
        }

		$condicao_pagamento = ($projetoObj->condicao_pagamento_detalhes->descricao) ?? '';

		if(
			$AprovacaoDeProjetoObj->credito === true
		){
			if(
				empty($AprovacaoDeProjetoObj->aprovacao_credito_user_id) && 
				strtolower(Auth::user()->tipo_usuario->nome) === 'credito'
			){
				$mostrar_botao = true;
			}
			if(
				$AprovacaoDeProjetoObj->preco === true && 
				empty($AprovacaoDeProjetoObj->aprovacao_preco_user_id) &&
				(
					Auth::user()->tipo_usuario->nivel < $AprovacaoDeProjetoObj->nivelaprovacaoPreco->id && 
					strtolower(Auth::user()->tipo_usuario->nome) !== 'credito'
				)
			){
				$mostrar_botao = true;
			}
		}else{
			if(Auth::user()->tipo_usuario->nivel < $AprovacaoDeProjetoObj->nivelaprovacao->id){
				$mostrar_botao = true;
			}
		}
		
		if(strtolower(Auth::user()->tipo_usuario->nome) == 'administrador'){
			$mostrar_botao = true;
		}

		$check_limite_credito = true;
		$check_data_limite_credito = true;
		$data_limite_credito = '';
		
		$cpf_cnpj = $projetoObj->cliente->cpf_cnpj;
		$raiz_cnpj = $cpf_cnpj;

		if(
			!in_array($projetoObj->cliente->codigo, $this->codigo_cliente_balcao)
		){

			if(strlen($raiz_cnpj) == 18){
				$raiz_cnpj = substr($raiz_cnpj, 0, 10);
			}

			$grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
			->where('raiz_cnpj', $raiz_cnpj)
			->orWhereHas('participantes', function($query) use ($raiz_cnpj){
				$query->where('raiz_cnpj', $raiz_cnpj);
			})->first();

			$clientesNasajonQuery = ClienteNasajon::query();
			
			if(!is_null($grupoEmpresarialObj)){
				$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
					if(isset($grupoEmpresarialObj->participantes)){
						foreach($grupoEmpresarialObj->participantes as $participante){
							$query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
						}
						$grupo[] = $participante->raiz_cnpj;
					}

					$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
				});

				$grupo = array_merge([$grupoEmpresarialObj->raiz_cnpj], $grupoEmpresarialObj->participantes->pluck('raiz_cnpj')->toArray());
			}
			else{
				$clientesNasajonQuery->where('cpf_cnpj', 'like', $raiz_cnpj . '%');
				$grupo = [$raiz_cnpj];
			}

			$clientesNasajon = $clientesNasajonQuery->get()->toArray();
			unset($clientesNasajonQuery);
			$ids = [];
			$codcads = [];
			foreach ($clientesNasajon as $key => $value) {
				$codcads[] = $value["codigo"];
				$cnpjs[] = $value["cpf_cnpj"];
				$ids[] = $value['id'];
			}
			unset($clientesNasajon);

			$limite_credito = 0;

			$titulosNasajon = TitulosEmAbertoNasajon::whereIn('cod_cliente', $codcads)->get();
			$data_duplicatas = $titulosNasajon->min('vencimento');

			if(is_null($data_duplicatas)){
				$data_duplicatas = date('Y-m-d 00:00:00');
			}

			$ChequesRecebidoObj = ChequesEmAbertoNasajon::whereIn('cod_cliente', $codcads)->get();
			$cheques_aberta_data = $ChequesRecebidoObj->min('data_vencimento');
			if(!is_null($cheques_aberta_data)){
				if(strtotime($data_duplicatas) > strtotime($cheques_aberta_data)){
					$data_duplicatas = $cheques_aberta_data;
				}
			}

			$parametro_duplicatas_vencidas_a_mais = intval($parametrosObj->maximo_duplicatas_vencidas);

			$duplicatas_vencidas = [
				"dias" => 0,
				"valor" => 0,
			];


			$menor_data = Carbon::createFromFormat('Y-m-d', substr($data_duplicatas, 0, 10))->setTime(0,0,0);
			$data_hoje = Carbon::now()->setTime(0,0,0);
			if($menor_data->lt($data_hoje)){
				$duplicatas_vencidas['dias'] = Carbon::Now()->diffInDays($menor_data);
				
				if($parametro_duplicatas_vencidas_a_mais <= $duplicatas_vencidas['dias']){
					$duplicatas_vencidas['dias'] = "<div class='falha-validacao'>" . (int) $duplicatas_vencidas['dias'] . '</div>';
				}
			}

			$ClienteCreditoObj = ClienteCredito::whereIn('raiz_cnpj', $grupo)->get();

			if(is_null($ClienteCreditoObj)){
				$check_limite_credito = false;
				$data_limite_credito = 'Em analise';
			}
			else{

				$limite_credito = (float) $ClienteCreditoObj->sum('valor');

				$data_atulizacaco_limite = new Carbon($ClienteCreditoObj->min('data_atualizacao'));
				$maximo_de_limite_credito = $parametrosObj->maximo_de_limite_credito;
				$data_atulizacaco_limite->addMonths($maximo_de_limite_credito);
				$data_limite_credito = $data_atulizacaco_limite->format('d/m/Y');
				if(strtotime($data_hoje) > strtotime($data_atulizacaco_limite)){
					$check_data_limite_credito = false;
				}
			}

			$limite_disponivel = $limite_credito;

			$sql_dados_financeiro = sprintf("SELECT * FROM ns.fn_dadosfinanceiroscliente_portal('%s');", $projetoObj->cliente->id);
			$dados_financeiro = DB::connection('nasajon')->select($sql_dados_financeiro);
			$dados_financeiro = (array) reset($dados_financeiro);
			
			$maior_atraso = $dados_financeiro['maioratraso'];
			$ultimo_atraso = $dados_financeiro['diasultimoatraso'];
			$media_atraso = $dados_financeiro['mediaatraso'];


			if (!empty($media_atraso)){
				$media_atraso_exibicao = $media_atraso . " dias";
			}
			else{
				$media_atraso_exibicao = '';
			}
			
			$pedidos_em_aberto_nasjon = PedidosVendaNasajon::query()
				->whereIn('situacao_descricao', ['Em Faturamento', 'Em separação', 'Aberto'])
				->where(function ($query){
					$query->where('grupodeoperacao', 'VENDA')
					->orWhereNull('grupodeoperacao');
				})
				->where('rascunho', false)
				->whereIn('cliente', $ids)
				->sum('valor');
			$pedidos_em_aberto = (float) $pedidos_em_aberto_nasjon;
			unset($pedidos_em_aberto_nasjon);

			$PedidoPortalEmAberto = PedidoPortal::select('*')
				->whereNotIn('status_pedido', [1, 3, 5, 7])
				->whereIn('cod_cliente', $codcads)
				->get();
			$total_pedidos_em_aberto = 0;
			$PedidoPortalEmAberto->each(function($pedido) use (&$total_pedidos_em_aberto){
				if(isset($pedido->valor_total->total)){
					$total_pedidos_em_aberto += $pedido->valor_total->total;
				}
			});
			unset($PedidoPortalEmAberto);
			$pedidos_em_aberto += (float) $total_pedidos_em_aberto;
			$limite_disponivel -= $pedidos_em_aberto;

			$titulos_faturados = ['' => 0];
			$titulosNasajon->each(function ($item) use (&$titulos_faturados){
				$key = $item->numero . '' . $item->codigo;
				if(!isset($titulos_faturados[$key])){
					$titulos_faturados[$key] = floatval($item->saldotitulo);
				}
			});
			$duplicatas_em_aberto = (float) array_sum($titulos_faturados);

			$limite_disponivel	-= $duplicatas_em_aberto;
			
			$nota_credito = 0;
			$notasCredito = NotasCreditoReceberNasajon::
				whereIn('cod_cliente', $codcads)
				->get();

			$nota_credito += $notasCredito->sum('valor');
			
			$limite_disponivel += $nota_credito;

			if(!is_null($ChequesRecebidoObj)){
				$limite_disponivel -= $ChequesRecebidoObj->sum('valor');
			}
			
			$pedidosPrePagosObj = PedidosPrePago::with(['pedidoNasajon' => function($query){ $query->where('grupodeoperacao', 'VENDA'); }, 'pedidoNasajon.nota'])
			->whereHas('pedido', function($query) use ($codcads){
				$query->whereIn('cod_cliente', $codcads);
			})
			->get();

            $pedidosPrePagosObj = $pedidosPrePagosObj->filter( function ($value){
                return !is_null($value->pedidoNasajon) && !is_null($value->pedidoNasajon->nota);
            });
            
            $valor_total_prepago = $pedidosPrePagosObj->sum('valor')??0;
            $valor_baixado_prepago = $pedidosPrePagosObj->sum('valor_pago')??0;
			$total_pre_pagos = $valor_total_prepago - $valor_baixado_prepago;
			
            $ChequesObj = Cheque::whereIn('cliente_cpf_cnpj', $cnpjs)->whereHas('pedidos_prepagos')->where('bom_para', '>', Carbon::Now()->format('Y-m-d'))->where('tipo', 'cheque')->get();

            $chque_pre = $ChequesObj->sum('valor');

			$limite_disponivel -= $total_pre_pagos + $chque_pre;

			$cheques_sem_fundo_lucros_perdas = [];
			$cliente_cheque_sem_fundo_lucros_perdas = count($cheques_sem_fundo_lucros_perdas) > 0 ? 'Sim' : 'Não';
			$cheques_sem_fundo_lucros_negociacao = [];
			$cliente_cheque_sem_fundo_negociacao = count($cheques_sem_fundo_lucros_negociacao) > 0 ? 'Sim' : 'Não';

		}else{
			$limite_credito = 0;
			$maior_atraso = '';
			$ultimo_atraso = '';
			$media_atraso = '';
			$pedidos_em_aberto = 0;
			$media_atraso_exibicao = '';			
			$pedidosEmAberto = [];
			$limite_disponivel = 0;
			$cnpjs = [];
			$cliente_cheque_sem_fundo_lucros_perdas = 'Não';
			$cliente_cheque_sem_fundo_negociacao = 'Não';
			$nota_credito = '';
			$total_pre_pagos = 0;
			$duplicatas_vencidas = [
				"dias" => 0,
				"valor" => 0,
			];

		}
		$natop = OperacaoNasajon::where('codigo', $projetoObj->codigo_operacao)->first();
        switch ($projetoObj->estabelecimento) {
        	case '3':
        		$origem = 'RO';
        		break;
        	case '4':
        		$origem = 'TO';
        		break;        	
        	default:
				$origem  = 'SP';
        		break;
        }

       	$precoObj = new ListagemDePrecosController;
		$parametrosAprovacaoObj = ParametrosAprovacao::where('estabelecimento', str_pad($projetoObj->estabelecimento, 2, '0', STR_PAD_LEFT))
			->orderBy('tipo_usuario_id', 'asc')
			->first();

		$itens = [];
		
		$aliquotaObj = AliquotaPreco::where('origem', $origem)->where('estado', $projetoObj->cliente->uf)->get();

       	foreach ($aliquotaObj as $value){
       		if($value->internacional === true){
       			$aliquota['internacional'] = $value->icms_venda;
       		}
       		else if($value->internacional === false){
       			$aliquota['nacional'] = $value->icms_venda;
       		}
       	}

        if ($projetoObj->tipo_frete == 'P' && is_null($estabelecimentoCidadeFobObj) && is_null($projetoObj->transportadora_redespacho)){
            $frete = 'cif';
        }
        else{
            $frete = 'fob';
        }

		$valor_total_itens = 0;
		$erro_em_preco = false;

        $tipo_frete = $projetoObj->tipo_frete;
        $usuario = $projetoObj->usuario_detalhes['name'];
        $data_entrega = date("d/m/Y", strtotime($projetoObj->data_previsao_entrega??$projetoObj->data_pedido));

		$ultima_venda = new DateTime($projetoObj->cliente['DTULTVND']);

		$inatividade = "desde " .  $ultima_venda->format('d/m/Y');

		if (
			$check_limite_credito === false ||
			$check_data_limite_credito === false
		){
			$data_limite_credito = "<div class='falha-validacao'>" . $data_limite_credito;
			$data_limite_credito .= "</div>";
		}else{
			$data_limite_credito = $data_limite_credito;
		}

		if(
			(
				strtolower(Auth::user()->tipo_usuario->nome) === "credito" ||
				strtolower(Auth::user()->tipo_usuario->nome) === "administrador"
			)
		){
			$data_limite_credito .= '&nbsp;<input type="button" value="Revisar crédito" class="btn btn-primary btn-xs" id="btn_add_credito" onclick="addCredito(this)" />';
		}


		if (
			$limite_credito <= 0 &&
			(
				(isset($projetoObj->cliente->codigo) && !in_array($projetoObj->cliente->codigo, $this->codigo_cliente_balcao))
			)
		){
			$limite_credito = "<div class='falha-validacao'>" . parserValor($limite_credito) . "</div>";
		}else{
			$limite_credito = parserValor($limite_credito);
		}

		if ($erro_em_preco == true){
			$precos_aprovados = "<div class='falha-validacao'>Há pendências</div>";
		}

		if(
			$limite_disponivel < $projetoObj->valor_total_pedido &&
			(
				(isset($projetoObj->cliente->codigo) && !in_array($projetoObj->cliente->codigo, $this->codigo_cliente_balcao))
			)
		){
			$limite_disponivel = "<div class='falha-validacao'>" . parserValor($limite_disponivel) . "</div>";
		}
		else{
			$limite_disponivel = parserValor($limite_disponivel);
		}
		$erro_integracao = '';
		if ($projetoObj->status_pedido == 6 && !empty($projetoObj->erro_integracao)){
			$erro_integracao = "<div class='falha-validacao'>" . $projetoObj->erro_integracao . "</div>";
		}

		if($projetoObj->desconto > 0){
	        $desconto_em_nota = "<div class='falha-validacao'>" . parserValor($projetoObj->desconto) . '</div>';
		}
		else {
			$desconto_em_nota = null;
		}
		
		$prazo_pedido = $projetoObj->condicoes_pagamento_web['media'] ?? 0;
		if(intval($prazo_pedido) > 120 ){
	        $prazo_pedido = "<div class='falha-validacao'>" . $prazo_pedido . '</div>';
		}

		$duplicatas_em_aberto_return = 0;
		if(!empty($duplicatas_em_aberto)){
			$duplicatas_em_aberto_return = $duplicatas_em_aberto;
		}

		$show_btn_limite_credito = false;
		if(
			(
				$check_limite_credito !== true ||
				$check_data_limite_credito !== true
			) &&
			(
				strtolower(Auth::user()->tipo_usuario->nome) === "diretor" ||
				strtolower(Auth::user()->tipo_usuario->nome) === "credito" ||
				strtolower(Auth::user()->tipo_usuario->nome) === "administrador"
			)
		){
			$show_btn_limite_credito = true;
		}

		$cliente_nome = '';
		$cliente_nome = $projetoObj->cliente['nome'];

		if(boolval($projetoObj->pedido_futuro) == true){
			$tipo_venda = 'Pedido Futuro';
		}else{
			$tipo_venda = 'Pronta Entrega';
		}

        $query_produtos = LancamentoProjetoProduto::where('lancamento_projetos_id', $projetoObj->id)->get();
        $produtos = [];
        foreach($query_produtos as $produto){
            $produtos[] = [
                'nome' => $produto->descricao,
                'detalhes' => $produto->detalhe_producao,
                'preco_venda' => parserValor($produto->preco_venda),
                'quantidade' => parserValor($produto->quantidade)
            ];
        }

        $query_tecidos = LancamentoProjetoTecido::where('lancamento_projetos_id', $projetoObj->id)->get();
        $total_tecido = $query_tecidos->sum('valor_total');
        $tecidos = [];
        foreach($query_tecidos as $tecido){
            $tecidos[] = [
                'codigo' => $tecido->codigo_produto,
                'descricao' => $tecido->tecido_detalhes->descricao,
                'referencia_produto' => $tecido->produto->descricao,
                'consumo_por_peca' => parserValor($tecido->consumo_unitario),
                'consumo_total' => parserValor($tecido->consumo_total),
                'custo_unitario' => parserValor($tecido->custo_unitario),
                'custo_total' => parserValor($tecido->valor_total)
            ];
        }

        $query_insumos = LancamentoProjetoInsumo::where('lancamento_projetos_id', $projetoObj->id)->get();
        $total_insumo = $query_insumos->sum('valor_total');
        $insumos = [];
        foreach($query_insumos as $insumo){
            $insumos[] = [
                'codigo' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'referencia_produto' => $insumo->produto->descricao,
                'consumo_por_peca' => parserValor($insumo->consumo_unitario),
                'consumo_total' => parserValor($insumo->consumo_total),
                'custo_unitario' => parserValor($insumo->custo_unitario),
                'custo_total' => parserValor($insumo->valor_total)
            ];
        }

        $query_faccoes = LancamentoProjetoFaccao::where('lancamento_projetos_id', $projetoObj->id)->get();
        $total_faccao = $query_faccoes->sum('valor_total');
        $faccoes = [];
        $com_faccao = false;
        foreach($query_faccoes as $faccao){
            $faccoes[] = [
                'referencia_produto' => $faccao->produto->descricao,
                'cnpj' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->nome,
                'tipo_de_servico' => $faccao->tipo_de_servico->descricao,
                'custo_unitario' => parserValor($faccao->custo_unitario),
                'quantidade' => parserValor($faccao->quantidade),
                'custo_total' => parserValor($faccao->valor_total),
            ];

            if(!empty($faccao->faccao)){
                $com_faccao = true;
            }
        }

		$prorrogacao = 'Não';
		if($AprovacaoDeProjetoObj->prorrogacao == true){
	        $prorrogacao = '<div class="falha-validacao">Sim</div>';
		}
		$titulos_prepago = '';
		if($total_pre_pagos > 0){
			$titulos_prepago = '<div class="falha-validacao">'.parserValor($total_pre_pagos).'</div>';
		}

		$return = [
			'cpf_cnpj' => $cpf_cnpj??'',
            'projeto' => $projetoObj->id,
            'id_aprovacao' => $AprovacaoDeProjetoObj->id,
            'id_encriptada' => Crypt::encrypt($projetoObj->cliente->codigo),
            'cliente' => $cliente_nome,
            'valor' => parserValor($projetoObj->valor_total_pedido),
            'custo_total' => parserValor($projetoObj->custo_total),
            'desconto' => parserValor($projetoObj->desconto).'%',
            'vendedor' => $projetoObj->detalhes_representante['codigo_representante'],
            'comissao' => parserValor($projetoObj->comissao).' %',
            'preco_medio_venda' => parserValor($projetoObj->preco_venda),

            'frete_adicional'=> parserValor($projetoObj->frete),
            'total_tecido' => parserValor($total_tecido),
            'total_insumo' => parserValor($total_insumo),
            'total_faccao' => parserValor($total_faccao),

			'media_atraso' => $media_atraso_exibicao,
			'maior_atraso' => $maior_atraso,
			'inatividade' => $inatividade,
			'limite_credito' => $limite_credito,
			'limite_disponivel' => $limite_disponivel,
			'ultimo_atraso' => $ultimo_atraso,
			'lucros_e_perdas' => $cliente_cheque_sem_fundo_lucros_perdas,
			'negociacao' => $cliente_cheque_sem_fundo_negociacao,
			'notas_credito' => $nota_credito,

			'condicao_pagamento' => $condicao_pagamento,
			'precos' => $precos_aprovados,
			'natureza_operacao' => $projetoObj->codigo_operacao . ' - ',
			'desconto_em_nota' => $desconto_em_nota,

			'tipo_frete' => parserFrete($tipo_frete),
			'usuario' => $usuario,
			'data_entrega' => $data_entrega,

			'pedidos_em_aberto' => parserValor($pedidos_em_aberto),
			'duplicatas_em_aberto' => parserValor($duplicatas_em_aberto_return + $total_pre_pagos),
			'titulos_prepago' => $titulos_prepago,

			'prazo_adicional' => $parametrosAprovacaoObj->prazo_adicional,
			'prazo_pedido' => $prazo_pedido,
			'produtos' => $produtos,
            'tecidos' => $tecidos,
            'insumos' => $insumos,
            'faccoes' => $faccoes,
            'com_faccao' => $com_faccao,

			'alerta' => '',

			'criterio_preco' => $projetoObj->aprovacao['preco'],

			'erro_integracao' => $erro_integracao,

			'duplicatas_vencidas' => $duplicatas_vencidas,

			'preco' => $frete,
			'cidade_estado' => !empty($cepEnderecoObj) ? $cepEnderecoObj->cidadeBusca->cidade . ' - ' . $projetoObj->cliente->uf : $projetoObj->cliente->uf,

			'data_limite_credito' => $data_limite_credito,
			'check_limite_credito' => $check_limite_credito,
			'check_data_limite_credito' => $check_data_limite_credito,
			'show_btn_limite_credito' => $show_btn_limite_credito,
			'mostrar_botao' => $mostrar_botao,
			'tipo_venda' => $tipo_venda,

			'prorrogacao' => $prorrogacao
		];

		return view('programs.aprovacao_projeto.modal')->with(['return' => $return]);

	}
    
    public function aprovaProjeto(Request $request){
        $fields = $request->only('id');
        $projeto = $fields['id'];
        $projetoObj = LancamentoProjeto::findOrFail($projeto);

		$AprovacaoDeProjetoObj = AprovacaoDeProjeto::where('projeto_id', $projeto)->first();
		
		if(!is_null($AprovacaoDeProjetoObj)){

			if(
				$AprovacaoDeProjetoObj->credito == true &&
				$AprovacaoDeProjetoObj->preco == true &&
				empty($AprovacaoDeProjetoObj->aprovacao_credito_user_id) &&
				empty($AprovacaoDeProjetoObj->aprovacao_preco_user_id)
			){
				if(strtolower(Auth::user()->tipo_usuario->nome) == 'credito'){
                    $AprovacaoDeProjetoObj->aprovacao_credito_user_id = Auth::id();
                    $this->gravarHistoricoProjeto($projeto, 'aprovado', 'Aprovação de pedido parado em crédito', Auth::id());
				}else{
                    $AprovacaoDeProjetoObj->aprovacao_preco_user_id = Auth::id();
                    $this->gravarHistoricoProjeto($projeto, 'aprovado', 'Aprovação de pedido parado em preço', Auth::id());
                }

                $AprovacaoDeProjetoObj->save();

				return response()
					->json(
						[
							'status' => 'success', 
							'message' => 'O projeto foi aprovado.',
							'error' => [],
							'response' => []
						],
					200);
			}
			elseif(
				$AprovacaoDeProjetoObj->credito == true &&
				$AprovacaoDeProjetoObj->preco == true &&
				(
					!empty($AprovacaoDeProjetoObj->aprovacao_credito_user_id) ||
					!empty($AprovacaoDeProjetoObj->aprovacao_preco_user_id)
				)
			){
				if(strtolower(Auth::user()->tipo_usuario->nome) == 'credito'){	
                    $AprovacaoDeProjetoObj->aprovacao_credito_user_id = Auth::id();
                    $this->gravarHistoricoProjeto($projeto, 'aprovado', 'Aprovação de pedido parado em crédito', Auth::id());
				}else{
                    $AprovacaoDeProjetoObj->aprovacao_preco_user_id = Auth::id();
                    $this->gravarHistoricoProjeto($projeto, 'aprovado', 'Aprovação de pedido parado em preço', Auth::id());
				}
				$AprovacaoDeProjetoObj->save();
            }
		}else{
			$AprovacaoDeProjetoObj = new AprovacaoDeProjeto;
			
			$AprovacaoDeProjetoObj->projeto_id = $projetoObj->id;
			$AprovacaoDeProjetoObj->nivel_aprovacao = 1;
			$AprovacaoDeProjetoObj->aprovador_id = 1;
			$AprovacaoDeProjetoObj->save();

        }
        $AprovacaoDeProjetoObj->deleted_by = Auth::id();

        $id_projeto = $AprovacaoDeProjetoObj->projeto_id;

        $AprovacaoDeProjetoObj->save();
        $AprovacaoDeProjetoObj->delete();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        $lancamentoProjetoObj->status = 4;
        $lancamentoProjetoObj->linha = empty($lancamentoProjetoObj->itens[0]->produto_detalhes->linha)? 'HOSPITALAR':$lancamentoProjetoObj->itens[0]->produto_detalhes->linha;
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $remessaProjetoObj = new EnvioProjetoFaccao;
        $remessaProjetoObj->lancamento_projetos_id = $projeto;
        $remessaProjetoObj->created_by = Auth::id();
        $remessaProjetoObj->save();

        $this->gravarHistoricoProjeto($projeto, 'aprovado_pedido', '', Auth::id());

        $fichaTecnicaObj = new FichaTecnicaProdutoController;
        $fichaTecnicaObj->adicionarAtravesProjeto($fields['id']);
        $this->gravarHistoricoProjeto($id_projeto, 'ficha_tecnica', '', Auth::id());
        $this->enviarEmail($fields['id']);


        // $verificar_enviado_tecido = LancamentoProjetoTecido::select()->where('enviado_total', false)->where('lancamento_projetos_id', $id_projeto)->first();
        // $verificar_enviado_insumo = LancamentoProjetoInsumo::select()->where('enviado_total', false)->where('lancamento_projetos_id', $id_projeto)->first();
        // if(!empty($verificar_enviado_tecido ) || !empty($verificar_enviado_insumo)){
        //     // $pedidosComprasNasajonObj = new PedidosComprasNasajonController;
        //     // $geracao_pedido_compras = $pedidosComprasNasajonObj->processoGeracaoPedidoComprasNasajon($lancamentoProjetoObj, $id_projeto);
        //     // if(!empty($geracao_pedido_compras)){
        //     //     return response()->json($geracao_pedido_compras, 422);
        //     // }
        // }

        $necessidadeComprasControllerObj = new NecessidadeComprasController;
        $necessidadeComprasControllerObj->adicionarAtravesProjeto($projetoObj);

        if($lancamentoProjetoObj->mostruario === false && !in_array(str_replace('.', '', explode('/', $lancamentoProjetoObj->cliente_cpf_cnpj)[0]), $this->empresas_mn)){
            $pedidoPortalControllerObj = new PedidoPortalController;
            $pedidoPortalControllerObj->gerarPedidoProgramadoAtravesProjeto($lancamentoProjetoObj);
        }

        return response()
            ->json(
                [
                    'status' => 'success', 
                    'message' => 'O projeto foi aprovado e aguarda processamento.',
                    'error' => [],
                    'response' => []
                ],
            200);
    }
    
    private function cadastroNovoProduto($id_projeto){
        $dados_email = [];

        $query_projeto = LancamentoProjeto::with('cliente')->find($id_projeto);

        switch(strtoupper($query_projeto->estabelecimento)){
            case 5:
                $origem = "SP";
                $estado = $query_projeto->cliente->uf;
                $estabelecimento = 5;
                break;
            default:
                $origem = "TO";
                $estado = $query_projeto->cliente->uf;
                $estabelecimento = 4;
                break;
        }

        $tipo_cliente  = (
            $query_projeto->cliente->inscricaoestadual == 'ISENTO' ||
            intval($query_projeto->cliente->indicadorinscricaoestadual) == 2 ||
            intval($query_projeto->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';

        $tipo_frete = $query_projeto->tipo_frete;
        $valor_ipi = 0;

        $raiz_cnpj = substr($query_projeto->cliente->cpf_cnpj,0,10);

        if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $prazo_medio = $query_projeto->condicoes_pagamento_web->media;
        }else{
            $prazo_medio = null;
        }

        $query_projeto_produto = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto);
        $query_projeto_produto->with(['produto_detalhes']);
        $produtos = $query_projeto_produto->get();
        $dados_email = "";
        $produtoNovoController = new ProdutoNovoController;
        foreach($produtos as $produto){

            if(empty($produto->custo)){
                $custo = $this->atualizarCustoPrecoProduto($produto->id);
            }else{
                $custo = $produto->custo;
            }

            $procedencia = "nacional";

            $raiz_cnpj = substr($query_projeto->cliente->cpf_cnpj,0,10);

            if(in_array($raiz_cnpj,$this->cnpjIntercompany())){
                $preco_base = 0;
            }else{
                $preco_base = $produtoNovoController->precoBase($custo, $tipo_frete, $origem, $estado, $valor_ipi, $prazo_medio, $tipo_cliente, 0, $procedencia);
            }

            if(!empty($produto->codigo_produto)){
                $precoObj = Preco::find($produto->codigo_produto);
                if(empty($precoObj)){
					
                    $precoObj = new Preco;
                    $precoObj->codigo_produto = $produto->codigo_produto;
                    $precoObj->preco_real = $preco_base;
                    $precoObj->created_by = 1;
                    $precoObj->save();

					$PrecosLogObj = new PrecosLog();
					$PrecosLogObj->codigo_produto = $produto->codigo_produto;
					$PrecosLogObj->preco_real_antigo = 0;
					$PrecosLogObj->preco_real_novo = (float) $preco_base;
                    $PrecosLogObj->created_by = 1;
                    $PrecosLogObj->save();

                    $this->gravarHistoricoProjeto($id_projeto, 'cadastro_produto', 'Produto no Projeto: '. $produto->id.' Descricao: '.$produto->produto_detalhes->descricao.' Preço Atual: '.$preco_base.' Preço Anterior: '."", Auth::id());
                }else if(parserFloat10($preco_base) > parserFloat10($precoObj->preco_real)){
                    $arr = [];
                    $arr["hash"] = encrypt([
                        "grupo" => $produto->produto_detalhes->grupo,
                        "subgrupo" => $produto->produto_detalhes->subgrupo,
                        "marca" => $produto->produto_detalhes->marca,
                        "linha" => $produto->produto_detalhes->linha,
                        "ativo" => $produto->produto_detalhes->ativo,
                        "codigo_produto" => $produto->codigo_produto,
                        "descricao" => $produto->produto_detalhes->descricao,
                        "preco_real" => $precoObj->preco_real,
                        "preco_dolar" => $precoObj->preco_dolar,
                        "custo_gerencial" => $precoObj->compra_real,
                    ]);
                    $arr["preco_real"] = parserValor($preco_base);
                    $arr["preco_dolar"] = parserValor($precoObj->preco_dolar);
                    $arr["compra_real"] = parserValor($precoObj->compra_real);
                    $arr["atualiza_grupo_subgrupo"] = "agrupado";
                    $arr["origem"] = "projeto";
                    $arr["campo"] = "real";
                    $request = new Request($arr);

                    $this->gravarHistoricoProjeto($id_projeto, 'cadastro_produto', 'Produto no Projeto: '. $produto->id.' Descricao: '.$produto->produto_detalhes->descricao.' Preço Atual: '.$preco_base.' Preço Anterior: '.$precoObj->preco_real, Auth::id());
                    $importacaoPrecoControllerObj = new ImportacaoPrecoController;
                    $importacaoPrecoControllerObj->atualizaPreco($request);
                }
            }else{
                switch($query_projeto->produto_linhas_id){
                    case 96:
                        $prefixo = "UN";
                        $tamanho = 11;
                        $unidade = "UN";
                        $linha = "CONFECCAO NACIONAL";
                        $valor_maximo = 99999999999;
                        $marca = "TEXTIL MN";
                        break;
                    case 104:
                        $prefixo = "TP";
                        $tamanho = 11;
                        $unidade = "M";
                        $linha = "A CADASTRAR";
                        $valor_maximo = 99999999999;
                        $marca = "TEXTIL MN";
                        break;
                    case 77:
                        $prefixo = "H";
                        $tamanho = 12;
                        $unidade = "UN";
                        $linha = $query_projeto->detalhes_linha->descricao;
                        $valor_maximo = 999999999999;
                        $marca = $query_projeto->cliente->nome;
                        break;
                    case 83:
                        $prefixo = "M";
                        $tamanho = 12;
                        $unidade = "UN";
                        $linha = $query_projeto->detalhes_linha->descricao;
                        $valor_maximo = 999999999999;
                        $marca = $query_projeto->cliente->nome;
                        break;
                }
    
                $query_produto_novo = ProdutoNovo::selectRaw('case when regexp_replace(codigo_produto,\'[[:alpha:]]\',\'\',\'g\') = \'\' then 0
                    when cast(replace(regexp_replace(codigo_produto,\'[[:alpha:]]\',\'\',\'g\'), \' \', \'\') as float) > '.$valor_maximo.' then 0 
                    else cast(replace(regexp_replace(codigo_produto,\'[[:alpha:]]\',\'\',\'g\'), \' \', \'\') as float)
                    end as codigo_produto');
                $query_produto_novo->where('codigo_produto', 'ilike', $prefixo.'%');
                $query_produto_novo->orderBy('codigo_produto', 'desc');
                $produto_novo = $query_produto_novo->first();
    
                if(empty($produto_novo)){
                    $codigo_produto_novo = str_pad(1, 12, '0', STR_PAD_LEFT);
                }else{
                    $codigo_produto_novo = str_pad(($produto_novo->codigo_produto+1), 12, '0', STR_PAD_LEFT);
                }
    
                $codigo_valido = false;
    
                while($codigo_valido === false){
                    $query_verificar_codigo = ProdutoEspecificacao::select()->where('codigo_produto', 'ilike', $prefixo.$codigo_produto_novo);
                    $result_verificar_codigo = $query_verificar_codigo->first();
    
                    if(!empty($result_verificar_codigo)){
                        $codigo_produto_novo = str_pad((floatval($codigo_produto_novo)+1), $tamanho, '0', STR_PAD_LEFT);;
                    }else{
                        $codigo_valido = true;
                        $codigo_produto_novo = $prefixo.$codigo_produto_novo;
                    }
                }
    
                $this->verificarProdutoGrupo(explode(" ", $produto->descricao)[0]);
                $this->verificarProdutoMarca($marca);
    
                $produtoNovoObj = new ProdutoNovo;
                $produtoNovoObj->codigo_produto = $codigo_produto_novo;
                $produtoNovoObj->lancamento_projetos_id = $produto->lancamento_projetos_id;
                $produtoNovoObj->descricao = $produto->descricao;
                $produtoNovoObj->preco_venda = $preco_base;
                $produtoNovoObj->lancamento_projeto_produtos_id = $produto->id;
                $produtoNovoObj->ncm = $produto->ncm;
                $produtoNovoObj->peso = $produto->peso;
                $produtoNovoObj->data_requisicao = Carbon::now();
                $produtoNovoObj->created_by = Auth::id();
                $produtoNovoObj->save();
    
                $arr['id'] = encrypt($produtoNovoObj->id);
                $arr['cod_produto'] = $codigo_produto_novo;
                $arr['composicao'] = "";
                $arr['marca'] = $marca;
                $arr['linha'] = $linha;
                $arr['grupo'] = explode(" ", $produto->descricao)[0];
                $arr['subgrupo'] = "";
                $arr['descricao'] = $produto->descricao;
                $arr['unidade'] = $unidade;
                $arr['largura'] = "";
                $arr['gramatura'] = "";
                $arr['preco_venda'] = parserValor($preco_base);
                $arr['origem_mercadoria'] = 4;
                $arr['grupo_de_inventario'] = 0;
                $arr['peso'] = parserValor($produto->peso);
                $arr['ncm'] = $produto->ncm;
                $arr['rendimento'] = '';
    
                $produto_novo_request = new ProdutoNovoRequest($arr);
    
                if($produtoNovoController->editar($produto_novo_request)->getData()->status !== "success"){
                    return "Error ao cadastrar de Produto Novo.";
                }
    
                $this->gravarHistoricoProjeto($id_projeto, 'cadastro_produto', 'Produto no Projeto: '. $produto->id.' Descricao: '.$produto->descricao.' Preço Venda: '.$preco_base, Auth::id());
    
                $dados_email = $dados_email."Descrição : ".$produto->descricao."<br>Preço Venda : ".parserValor($preco_base)."<br><br>";
            }
        }

        $query_projeto_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto);
        $query_projeto_servico->whereNotNull('lancamento_projeto_tecidos_id');
        $query_projeto_servico->whereNull('codigo_produto_acabado');
        $servicos = $query_projeto_servico->get();

        foreach($servicos as $servico){
            switch($query_projeto->produto_linhas_id){
                case 96:
                    $prefixo = "UN";
                    $tamanho = 11;
                    $unidade = "UN";
                    $linha = "CONFECCAO NACIONAL";
                    $valor_maximo = 99999999999;
                    $marca = "TEXTIL MN";
                    break;
                case 104:
                    $prefixo = "TP";
                    $tamanho = 11;
                    $unidade = "M";
                    $linha = "A CADASTRAR";
                    $valor_maximo = 99999999999;
                    $marca = "TEXTIL MN";
                    break;
                case 83:
                    $prefixo = "H";
                    $tamanho = 12;
                    $unidade = "M";
                    $linha = $query_projeto->detalhes_linha->descricao;
                    $valor_maximo = 999999999999;
                    $marca = $query_projeto->cliente->nome;
                    break;
                case 77:
                    $prefixo = "M";
                    $tamanho = 12;
                    $unidade = "M";
                    $linha = $query_projeto->detalhes_linha->descricao;
                    $valor_maximo = 999999999999;
                    $marca = $query_projeto->cliente->nome;
                    break;
            }

            $query_produto_novo = ProdutoNovo::selectRaw('case when regexp_replace(codigo_produto,\'[[:alpha:]]\',\'\',\'g\') = \'\' then 0
                when cast(replace(regexp_replace(codigo_produto,\'[[:alpha:]]\',\'\',\'g\'), \' \', \'\') as float) > '.$valor_maximo.' then 0 
                else cast(replace(regexp_replace(codigo_produto,\'[[:alpha:]]\',\'\',\'g\'), \' \', \'\') as float)
                end as codigo_produto');
            $query_produto_novo->where('codigo_produto', 'ilike', 'H%');
            $query_produto_novo->orderBy('codigo_produto', 'desc');
            $produto_novo = $query_produto_novo->first();

            if(empty($produto_novo)){
                $codigo_produto_novo = str_pad(1, 12, '0', STR_PAD_LEFT);
            }else{
                $codigo_produto_novo = str_pad(($produto_novo->codigo_produto+1), 12, '0', STR_PAD_LEFT);
            }

            $codigo_valido = false;

            while($codigo_valido === false){
                $query_verificar_codigo = ProdutoEspecificacao::select()->where('codigo_produto', 'ilike', $prefixo.$codigo_produto_novo);
                $result_verificar_codigo = $query_verificar_codigo->first();

                if(!empty($result_verificar_codigo)){
                    $codigo_produto_novo = str_pad((floatval($codigo_produto_novo)+1), $tamanho, '0', STR_PAD_LEFT);;
                }else{
                    $codigo_valido = true;
                    $codigo_produto_novo = $prefixo.$codigo_produto_novo;
                }
            }

            $descricao = $servico->tipo_de_servico->grupo." - ".$servico->tecido->tecido_detalhes->descricao;

            $this->verificarProdutoGrupo(explode(" ", $descricao)[0]);
            $this->verificarProdutoMarca($marca);

            $produtoNovoObj= new ProdutoNovo;
            $produtoNovoObj->codigo_produto = $codigo_produto_novo;
            $produtoNovoObj->lancamento_projetos_id = $servico->lancamento_projetos_id;
            $produtoNovoObj->descricao = $descricao;
            $produtoNovoObj->preco_venda = $servico->tecido->tecido_detalhes->preco->preco_real ;
            $produtoNovoObj->lancamento_projeto_tecidos_id = $servico->lancamento_projeto_tecidos_id;
            $produtoNovoObj->ncm = $servico->tecido->tecido_detalhes->produtoNasajon->ncm;
            $produtoNovoObj->peso = $servico->tecido->tecido_detalhes->produtoNasajon->pesobruto;
            $produtoNovoObj->data_requisicao = Carbon::now();
            $produtoNovoObj->created_by = Auth::id();
            $produtoNovoObj->save();

            $arr['id'] = encrypt($produtoNovoObj->id);
            $arr['cod_produto'] = $codigo_produto_novo;
            $arr['composicao'] = "";
            $arr['marca'] = $marca;
            $arr['linha'] = $linha;
            $arr['grupo'] = explode(" ", $servico->tipo_de_servico->grupo)[0];
            $arr['subgrupo'] = "";
            $arr['descricao'] = $descricao;
            $arr['unidade'] = $unidade;
            $arr['largura'] = "";
            $arr['gramatura'] = "";
            $arr['preco_venda'] = parserValor($servico->tecido->tecido_detalhes->preco->preco_real);
            $arr['origem_mercadoria'] = 4;
            $arr['grupo_de_inventario'] = 0;
            $arr['peso'] = parserValor($servico->tecido->tecido_detalhes->produtoNasajon->pesobruto);
            $arr['ncm'] = $servico->tecido->tecido_detalhes->produtoNasajon->ncm;
            $arr['rendimento'] = '';

            $produto_novo_request = new ProdutoNovoRequest($arr);

            if($produtoNovoController->editar($produto_novo_request)->getData()->status !== "success"){
                return "Error ao cadastrar de Produto Novo.";
            }

            $this->gravarHistoricoProjeto($id_projeto, 'cadastro_produto', 'Tecido no Projeto: '. $servico->lancamento_projeto_tecidos_id.' Codigo: '.$servico->tecido->codigo_produto.' Descricao: '.$servico->tipo_de_servico->grupo." - ".$servico->tecido->tecido_detalhes->descricao.' Preço Venda: '.($servico->tecido->tecido_detalhes->preco->preco_real + $servico->tipo_de_servico->preco->preco_real), Auth::id());

            $dados_email = $dados_email."Descrição : ".$servico->tipo_de_servico->grupo." - ".$servico->tecido->tecido_detalhes->descricao."<br>Preço Venda : ".parserValor($servico->tecido->tecido_detalhes->preco->preco_real)."<br><br>";
        }

        $this->enviarEmailProdutoNovo($dados_email, $estabelecimento, $id_projeto);

        return "";
    }

    private function enviarEmailProdutoNovo($produto, $estabelecimento, $numero_projeto){
		try{

			$EmailObj = new EmailController();

            $email_send = [];

            $variaveis = [
                'numero_projeto' => $numero_projeto,
                'produtos' => $produto,
                'link_cadastro' => route('produto.novo.index')
            ];
            $EmailObj->sendEmailToken($estabelecimento, "cadastro_produto_novo", $email_send, $variaveis);
		}catch(\Exception $e){

		}

    }

    private function enviarEmail($id_projeto){
		try{

            $query_projeto = LancamentoProjeto::find($id_projeto);

            $estabelecimento = $query_projeto->estabelecimento;

			$EmailObj = new EmailController();

            $email_send_cliente = [];
            $email_send_representante = [];
            $email_send_tecnica = [];

            if(!empty($query_projeto->email_contato)){
                $email_send_cliente[] = $query_projeto->email_contato;
            }else if(!empty($query_projeto->cliente->email)){
                $email_send_cliente[] = $query_projeto->cliente->email;
            }else if($query_projeto->cliente->emailcobranca){
                $email_send_cliente[] = $query_projeto->cliente->emailcobranca;
            }

            if(!empty($query_projeto->detalhes_representante->email)){
                $email_send_representante[] = $query_projeto->detalhes_representante->email;
            }

            $variaveis_cliente = [
                'numero_projeto' => $id_projeto,
                'nome_cliente' => $query_projeto->cliente->nome,
            ];

            $variaveis_representante = [
                'numero_projeto' => $id_projeto,
                'nome_representante' => $query_projeto->detalhes_representante->name,
            ];

            $variaveis_tecnica = [
                'numero_projeto' => $id_projeto
            ];
            
            if(count($email_send_cliente) > 0){
                $EmailObj->sendEmailToken($estabelecimento, "aprovacao_projeto_cliente", $email_send_cliente, $variaveis_cliente);
            }
            if(count($email_send_representante) > 0){
                $EmailObj->sendEmailToken($estabelecimento, "aprovacao_projeto_representante", $email_send_representante, $variaveis_representante);
            }
            $EmailObj->sendEmailToken($estabelecimento, "aprovacao_projeto_tecnica", $email_send_tecnica, $variaveis_tecnica);
		}catch(\Exception $e){

		}

    }

    private function enviarEmailReprovado($id_projeto, $motivo){
		try{

            $query_projeto = LancamentoProjeto::find($id_projeto);

            $estabelecimento = $query_projeto->estabelecimento;

			$EmailObj = new EmailController();

            $email_send_cliente = [];
            $email_send_representante = [];
            $email_send_tecnica = [];


            if(!empty($query_projeto->cliente->emailcobranca)){
                $email_send_cliente[] = $query_projeto->cliente->emailcobranca;
            }else if($query_projeto->cliente->email){
                $email_send_cliente[] = $query_projeto->cliente->email;
            }

            if(!empty($query_projeto->detalhes_representante->email)){
                $email_send_representante[] = $query_projeto->detalhes_representante->email;
            }

            $variaveis_cliente = [
                'numero_projeto' => $id_projeto,
                'nome_cliente' => $query_projeto->cliente->nome,
                'motivo_recusa' => $motivo
            ];

            $variaveis_representante = [
                'numero_projeto' => $id_projeto,
                'representante' => $query_projeto->detalhes_representante->name,
                'motivo_recusa' => $motivo
            ];

            $variaveis_tecnica = [
                'numero_projeto' => $id_projeto,
                'motivo_recusa' => $motivo
            ];
            
            if(count($email_send_representante) > 0){
                $EmailObj->sendEmailToken($estabelecimento, "projeto_reprovado_representante", $email_send_representante, $variaveis_representante);
            }
            $EmailObj->sendEmailToken($estabelecimento, "projeto_reprovado_equipe_suporte", $email_send_tecnica, $variaveis_tecnica);
		}catch(\Exception $e){

		}

    }

    private function setStatusEmRevisao($id_projeto, $id_revisor){
        $AprovacaoDeProjetoObj = AprovacaoDeProjeto::select();
        $AprovacaoDeProjetoObj->where('projeto_id',$id_projeto);
        $AprovacaoDeProjetoObj->deleted_by = Auth::id();
        $AprovacaoDeProjetoObj->delete();

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);

        if($lancamentoProjetoObj->status < 2){
            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'em_revisao', '', Auth::id());
        }

        if($lancamentoProjetoObj->status == 4){
            return response()->json([
                'status' => 'error',
                'message' => 'Projeto já foi aprovado',
                'error' => [],
                'response' => []
            ],422);
        }
        $lancamentoProjetoObj->status = 2;
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();
    }

    public function getInformacaoPreco($id_projeto){
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = '';

        $projeto = LancamentoProjeto::find($id_projeto);

        $estabelecimento = $estabelecimentos[$projeto->estabelecimento];

        $info = [
            'estabelecimento' => empty($estabelecimento)? '' : $estabelecimento,
            'estado_destino' => empty($projeto->cliente)? '' : $projeto->cliente->uf,
            'media_condicao_pagamento' => empty($projeto->condicoes_pagamento_web)? '' : $projeto->condicoes_pagamento_web->media,
            'preco_cif_fob' => empty($projeto->tipo_frete)? '' : $projeto->tipo_frete,
            'cif_fob' => empty($projeto->tipo_frete)? '' : $projeto->tipo_frete
        ];

        return $info;
    }

    public function liberarTabs(Request $request){
        $id = $request->only(['id_projeto'])['id_projeto'];

        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $projeto = LancamentoProjeto::with('cliente')->find($id);

        $raiz_cnpj = (!empty($projeto->cliente)) ? substr($projeto->cliente->cpf_cnpj,0,10) : '';

        if(empty($projeto->condicoes_pagamento_web_id) && !in_array($raiz_cnpj,$this->cnpjIntercompany()) || empty($projeto->cliente_cpf_cnpj) || empty($projeto->tipo_frete)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => []
            ]);
        }
    }  
    public function indexConsulta(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\ConsultaProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaProjeto');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[1]);
        unset($estabelecimentos[2]);
        unset($estabelecimentos[3]);
        unset($estabelecimentos[7]);
        unset($estabelecimentos[8]);
        unset($estabelecimentos[20]);

        if(in_array(Auth::user()->tipo_usuario_id, [12,16])){
            $representantes = Auth::user()->codigo_representante;
        }else{
            $representantes_busca = User::select()->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get();
        
            $representantes = [];
            foreach ($representantes_busca as $key => $value) {
                $userRole = $value->roles->pluck('id')->all();
                if(!empty($userRole)){
                    if($userRole[0] == 35){
                        $representantes[$value->codigo_representante] = $value->codigo_representante." - ".strtoupper($value->name);
                    }
                }
            }
        }
        
        $estados = $this->getEstados();

        unset($estados[9]);
        unset($estados[10]);

        return view('programs.lancamento_de_projeto.consulta.index')->with(['estabelecimentos' => $estabelecimentos, 'representantes' => $representantes, 'estados' => $estados]);
    }

    public function filterConsulta(FilterConsultaProjetoRequest $request){
        $fields = $request->only('estabelecimento', 'representante', 'faccao', 'atrasado','data_inicio','data_fim', 'estado', 'num_projeto', 'nome_projeto', 'linha', 'cliente');

        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

        $query = LancamentoProjeto::select();
        $query->with(['detalhes_status.status_exibicao']);

        if(is_numeric($fields['num_projeto'])){
            $query->where('id', $fields['num_projeto']);
        }
        if(!empty($fields['nome_projeto'])){
            $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
        }

        if(!empty($fields['representante'])){
            $query->where('users_codigo_representante', $fields['representante']);
        }

        if(!empty($fields['cliente'])){
            $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                ->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'');
            $cliente_busca = $cliente_busca->get();

            $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
        }

        if(!empty($fields['linha'])){
            $query->where('linha', 'ilike', $fields['linha']);
        }

        if(!empty($fields['estado'])){
            if($fields['estado'] == 99){
                $query->whereNotNull('deleted_at');
            }else{
                $query->whereHas('detalhes_status', function($query) use($fields){          
                    $query->where('status_projeto_exibicao_id', $fields['estado']);
                });
                $query->whereNull('deleted_at');
            }
        }

        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio'])->setTime(0,0,0);
            $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim'])->setTime(23,59,59);
            $query->whereBetween('created_at', [$data_inicial, $data_final]);
        }else if(!empty($fields['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio'])->setTime(0,0,0);
            $query->where('created_at', '>=', $data_inicial);
        }else if(!empty($fields['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim'])->setTime(23,59,59);
            $query->where('created_at', '<=', $data_final);
        }

        if(!empty($fields['estabelecimento'])){
            $query->where('estabelecimento',$fields['estabelecimento']);
        }
        
        $result = $query->get();
        foreach($result as $projeto){
            $query_faccao = LancamentoProjetoFaccao::select('faccao_id', 'data_previsao_entrega');
            $query_faccao->with(['faccao.fornecedor']);
            $query_faccao->whereNotNull('faccao_id');
            $query_faccao->distinct('faccao_id');
            $query_faccao->where('lancamento_projetos_id', $projeto->id);
            if(!empty($fields['faccao'])){
                $query_faccao->whereHas('faccao', function($query) use($fields){
                    $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf');
                    $fornecedor_busca->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($fields['faccao']).'%\'');
                    $fornecedor_busca = $fornecedor_busca->get();
                    $query->whereIn('cod_fornecedor', $fornecedor_busca->pluck('cnpj_cpf'));
                });
            }
    
            if(!empty($fields['atrasado'])){
                $agora_carbon = Carbon::now()->setTime(0, 0, 0);
                $query_faccao->where('data_previsao_entrega', '<', $agora_carbon);
            }

            $result_faccoes = $query_faccao->get();

            foreach($result_faccoes as $faccao){
                $data_previsao_entrega_carbon = '';
                $impressao_resumo_compra = false;

                if(!empty($faccao->data_previsao_entrega)){
                    $data_previsao_entrega_carbon = Carbon::CreateFromFormat("Y-m-d", $faccao->data_previsao_entrega)->setTime(0,0,0)->format('Y-m-d');
                }else if(!empty($projeto->data_previsao_entrega)){
                    $data_previsao_entrega_carbon = Carbon::CreateFromFormat("Y-m-d", $projeto->data_previsao_entrega)->setTime(0,0,0)->format('Y-m-d');
                }

                if($projeto->status >= 4){
                    // $verificar_pedido_compra = LancamentoProjetoFaccao::select();
                    // $verificar_pedido_compra->where('lancamento_projetos_id', $projeto->id);
                    // $verificar_pedido_compra->where('faccao_id', $faccao->faccao_id);
                    // $verificar_pedido_compra->whereNotNull('pedido_compras_gerado_nasajon');
                    // $verificar_pedido_compra = $verificar_pedido_compra->first();

                    // if(!empty($verificar_pedido_compra)){
                    //     $impressao_resumo_compra = true;
                    // }

                    $impressao_resumo_compra = true;
                }

                $retorno [] =[
                    'num_projeto' => $projeto->id,
                    'nome_projeto' => $projeto->nome_projeto,
                    'cliente' => empty($projeto->cliente)? '' : $projeto->cliente->nome,
                    'faccao' => $faccao->faccao->fornecedor->nome,
                    'data_entrada' => empty($projeto->data_entrada)? '' : parserData($projeto->data_entrada),
                    'data_previsao_entrega' => empty($data_previsao_entrega_carbon)? '' : parserData($data_previsao_entrega_carbon),
                    'status' => !empty($projeto->deleted_at)? 'CANCELADO' : $projeto->detalhes_status->status_exibicao->descricao,
                    'id_projeto' => encrypt($projeto->id), 
                    'id_faccao' => encrypt($faccao->faccao_id),
                    'linha' => empty($projeto->detalhes_linha)? '' : $projeto->detalhes_linha->descricao,
                    'numero_status' => $projeto->status,
                    'impressao_resumo_compra' => $impressao_resumo_compra,
                    'estabelecimento' => empty($projeto->estabelecimento)? '' : $estabelecimentos[$projeto->estabelecimento]
                ];
            }
        }
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function inserirAprovacaoProjeto($id_projeto){
        $query_projeto = AprovacaoDeProjeto::select();
        $query_projeto->where('projeto_id', $id_projeto);
        $projeto = $query_projeto->first();

        if(empty($projeto)){
            $aprovacaoDeProjetoObj = new AprovacaoDeProjeto;
        }else{
            $aprovacaoDeProjetoObj = AprovacaoDeProjeto::find($projeto->id);
        }
        
        $aprovacaoDeProjetoObj->projeto_id = $id_projeto;
        $aprovacaoDeProjetoObj->integracao = false;
        
        $lancamentoProjetoObj = LancamentoProjeto::with('cliente')->find($id_projeto);

        $origem = $lancamentoProjetoObj->estabelecimento;
        
        if($lancamentoProjetoObj->desconto == 0){
            $aprovacaoDeProjetoObj->preco = false;
            $aprovacaoDeProjetoObj->nivel_aprovacao = 2;
        // }else if($lancamentoProjetoObj->desconto <= 3){
        //     $aprovacaoDeProjetoObj->preco = true;
        //     $aprovacaoDeProjetoObj->nivel_aprovacao = 2;
        //     $aprovacaoDeProjetoObj->nivel_aprovacao_preco = 2;
        }else{
            $aprovacaoDeProjetoObj->preco = true;
            $aprovacaoDeProjetoObj->nivel_aprovacao = 1;
            $aprovacaoDeProjetoObj->nivel_aprovacao_preco = 1;
        }

        $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

        if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $credito = $this->checkCreditoProjeto($lancamentoProjetoObj->cliente, $origem, $lancamentoProjetoObj->condicoes_pagamento_web->descricao, $lancamentoProjetoObj->valor_total_pedido);
        
            $check_credito = ($credito === true) ? false : true;

            $nivel_aprovacao_credito = 0;
            if($credito !== true){
                $nivel_aprovacao_credito = 1;
            }
        }else{
            $check_credito = null;
            $nivel_aprovacao_credito = null;
        }

        $aprovacaoDeProjetoObj->credito = $check_credito;
        $aprovacaoDeProjetoObj->nivel_aprovacao_credito = $nivel_aprovacao_credito;

        $aprovacaoDeProjetoObj->created_by = Auth::id();
        $aprovacaoDeProjetoObj->save();

        if($aprovacaoDeProjetoObj->credito && $aprovacaoDeProjetoObj->preco){
            $lancamentoProjetoObj->status = 12;
            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'em_aprovacao', 'Crédito/Preço', Auth::id());
        }else if($aprovacaoDeProjetoObj->credito){
            $lancamentoProjetoObj->status = 13;
            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'em_aprovacao', 'Crédito', Auth::id());
        }else if($aprovacaoDeProjetoObj->preco){
            $lancamentoProjetoObj->status = 14;
            $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'em_aprovacao', 'Preço', Auth::id());
        }else{
            $arr['id_aprovacao'] = $aprovacaoDeProjetoObj->id;
            $aprovacao = new Request($arr);
            $this->aprovacao($aprovacao);
            $lancamentoProjetoObj->status = 4;
            $lancamentoProjetoObj->linha = $lancamentoProjetoObj->itens[0]->produto_detalhes->linha;
        }

        $lancamentoProjetoObj->save();
    }

    private function getDescontoDoProduto($id_produto){
        $query_tecido = LancamentoProjetoTecido::select()->where('lancamento_projeto_produtos_id', $id_produto);
        $total_tecido = $query_tecido->sum('valor_total');
        $query_insumo = LancamentoProjetoInsumo::select()->where('lancamento_projeto_produtos_id', $id_produto);
        $total_insumo = $query_insumo->sum('valor_total');
        $query_servico = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $id_produto);
        $total_servico = $query_servico->sum('valor_total');

        $total_custo = (empty($total_tecido)? 0 : $total_tecido) + (empty($total_insumo)? 0 : $total_insumo) + (empty($total_servico)? 0 : $total_servico);

        $query_produto = LancamentoProjetoProduto::select()->where('id', $id_produto);
        $result_produto = $query_produto->first();

        $total_venda = floatval($result_produto->quantidade) * floatval($result_produto->preco_venda);

        if($total_custo > $total_venda){
            $desconto = (1 - ($total_venda/$total_custo))*100;
        }else{
            $desconto = 0;
        }  

        return $desconto;
    }

    public function modalEditarData(Request $request){
        $fields = $request->only('id_projeto', 'id_faccao');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_faccao = decrypt($fields['id_faccao']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_faccao = LancamentoProjetoFaccao::select();
        $query_faccao->where('lancamento_projetos_id', $id_projeto);
        $query_faccao->where('faccao_id', $id_faccao);
        $result_faccao = $query_faccao->first();

        if(empty($result_faccao->data_previsao_entrega)){
            $query_projeto = LancamentoProjeto::select();
            $query_projeto->where('id', $id_projeto);
            $result_projeto = $query_projeto->first();

            $data_entrega_cliente = parserData($result_projeto->data_previsao_entrega);
            $data_previsao_entrega = parserData($result_projeto->data_previsao_entrega);
        }else{
            $data_entrega_cliente = parserData($result_faccao->data_entrega_cliente);
            $data_previsao_entrega = parserData($result_faccao->data_previsao_entrega);
        }

        $id_projeto = encrypt($id_projeto);
        $id_faccao = encrypt($id_faccao);
        
        return view('programs.lancamento_de_projeto.consulta.modal.alteracao_data')->with(['id_projeto' => $id_projeto, 'id_faccao' => $id_faccao, 'data_entrega_cliente' => $data_entrega_cliente, 'data_previsao_entrega' => $data_previsao_entrega]);
    }

    public function alteracaoDataFaccao(AlteracaoDataProjetoFaccaoRequest $request){
        $fields = $request->only('id_projeto', 'id_faccao', 'data_entrega_cliente', 'data_previsao_entrega');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            if(!empty($fields['id_faccao'])){
                $id_faccao = decrypt($fields['id_faccao']);
            }else{
                $id_faccao = '';
            }
            
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $data_entrega_cliente = Carbon::createFromFormat('d/m/Y', $fields["data_entrega_cliente"]);
        $data_previsao_entrega = Carbon::createFromFormat('d/m/Y', $fields["data_previsao_entrega"]);

        $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select(); 
        $lancamentoProjetoFaccaoObj->where('lancamento_projetos_id', $id_projeto);
        if(!empty($id_faccao)){
            $lancamentoProjetoFaccaoObj->where('faccao_id', $id_faccao);
        }
        $lancamentoProjetoFaccaoObj->update(['data_entrega_cliente' => $data_entrega_cliente->format('Y-m-d'), 'data_previsao_entrega' => $data_previsao_entrega->format('Y-m-d')]);

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);

        $data_previsao_entrega_projeto = Carbon::createFromFormat('Y-m-d', $lancamentoProjetoObj->data_previsao_entrega);

        if(!empty($lancamentoProjetoObj->pedido_id)){
            $pedidoObj = PedidoPortal::find($lancamentoProjetoObj->pedido_id);
            if(!empty($pedidoObj)){
                $pedidoObj->data_previsao_entrega = $lancamentoProjetoObj->data_previsao_entrega;
                $pedidoObj->updated_by = Auth::id();
                $pedidoObj->save();
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ''
        ]);
    }

    public function gravarHistoricoProjeto($projeto, $natureza, $motivo, $user){
        $historicoProjetoObj = new HistoricoProjeto;
        $historicoProjetoObj->lancamento_projetos_id = $projeto;
        $historicoProjetoObj->natureza = $natureza;
        $historicoProjetoObj->motivo = $motivo;
        $historicoProjetoObj->users_id = $user;
        $historicoProjetoObj->created_by = $user;
        $historicoProjetoObj->save();
    }

    public function modalEditarStatus(Request $request){
        $fields = $request->only('id_projeto');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query = LancamentoProjeto::select()->withTrashed()->where('id', $id_projeto);
        $result = $query->first();

        if(empty($result)){
            $status_atual = 'Cancelado';
            $codigo_status_atual = 99;
        }else{
            $status_atual = $result->detalhes_status->status_exibicao->descricao;
            $codigo_status_atual = $result->detalhes_status->status_exibicao->id;
        }

        $status = $this->getEstados();
        unset($status[1]);
        unset($status[3]);
        unset($status[4]);
        unset($status[5]);
        unset($status[9]);
        unset($status[10]);
        unset($status[11]);
        unset($status[12]);

        if($result->status <= 2 || $result->status == 9 || $result->status == 10 || !empty($result->deleted_at)){
            unset($status[7]);
            unset($status[8]);
        }else if(in_array($result->status, [6])){
            unset($status[2]);
            unset($status[7]);
        }

        $id_projeto = encrypt($id_projeto);
        
        return view('programs.lancamento_de_projeto.consulta.modal.alteracao_status')->with(['id_projeto' => $id_projeto, 'status_atual' => $status_atual, 'status' => $status, 'codigo_status_atual' => $codigo_status_atual]);
    }

    public function alteracaoStatus(AlteracaoStatusProjetoRequest $request){
        $fields = $request->only('id_projeto', 'status_novo');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $lancamentoProjetoObj = LancamentoProjeto::withTrashed()->find($id_projeto);

        $status = intval($fields['status_novo']);
        if($status == 6){
            $status = 5;
        }
        $status_antigo = $lancamentoProjetoObj->status;
        $lancamentoProjetoObj->status = $status;

        if($status != 2 && $status != 99 && ($status_antigo == 0 || $status_antigo == 1 || $status_antigo == 2)){
            $mensagem = $this->validacaoProjeto($id_projeto);
        }

        if(!empty($mensagem)){
            return response()->json([
                'status' => 'error',
                'message' => $mensagem,
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($lancamentoProjetoObj->pedido_id) && ($status == 2 || $status == 99)){
            $pedidoPortalObj = PedidoPortal::find($lancamentoProjetoObj->pedido_id);
            if(!empty($pedidoPortalObj)){
                $pedidoPortalControllerObj = new PedidoPortalController;
                $arr['id'] = $lancamentoProjetoObj->pedido_id;
                $id_pedido = new Request($arr);
                $pedidoPortalControllerObj->excluir($id_pedido);
    
                $lancamentoProjetoObj->pedido_id = NULL;
                $lancamentoProjetoObj->save();
    
                $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'cancelamento_pedido_programado', 'Cancelamento do Pedido: '.$arr['id'], Auth::id());
            }
        }
        //status 2 revisão
        //status 99 cancelamento
        if($status == 2 || $status == 99){
            //CANCELAMENTO DO PEDIDO COMPRAS DO NASAJON
            $query_historico_pedido_compra = HistoricoPedidoCompra::select('pedido_compra_uuid');
            $query_historico_pedido_compra->where('lancamento_projetos_id', $id_projeto);
            $query_historico_pedido_compra->distinct();
            $result_historico_pedido_compra = $query_historico_pedido_compra->get();

            foreach($result_historico_pedido_compra as $pedido_compra){
                $this->cancelamentoPedidoNasajon($pedido_compra->pedido_compra_uuid);
            }

            //CANCELAMENTO DA REMESSA E TRANSFERÊNCIA
            $query_remessa = RemessaProduto::select();
            $query_remessa->where('lancamento_projetos_id', $id_projeto);
            $query_remessa->distinct();
            $result_remessa = $query_remessa->get();

            foreach($result_remessa as $remessa){
                $this->cancelamentoPedidoNasajon($remessa->pedido_compra_numero_uuid);
    
                if(!empty($remessa->pedido_transferencia)){
                    if(empty($remessa->pedido_transferencia->deleted_at)){
                        $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;
    
                        $codigo_operacao = $aprovacaoDePedidoControllerObj->getCodigoOperacao($remessa->pedido_transferencia);
        
                        $query_pedido_nasajon = PedidosVendaNasajon::select();
                        $query_pedido_nasajon->where('numero', $remessa->pedido_transferencia->pedido_gerado);
                        $query_pedido_nasajon->where('estabelecimento_codigo', $remessa->pedido_transferencia->estabelecimento_pad);
                        $query_pedido_nasajon->where('operacao_codigo', $codigo_operacao);
                        $result_pedido_nasajon = $query_pedido_nasajon->first();
    
                        $this->cancelamentoPedidoNasajon($result_pedido_nasajon->id);
    
                        $pedidoPortalControllerObj = new PedidoPortalController;
                        $arr['id'] = $remessa->pedido_id;
                        $id_pedido = new Request($arr);
                        $pedidoPortalControllerObj->excluir($id_pedido);
                    }
                }
            }
        }

        if($status != 99){
            $lancamentoProjetoObj->deleted_at = NULL;
            $lancamentoProjetoObj->save();
        }else{
            $arr['id'] = encrypt($lancamentoProjetoObj->id);
            $request = new Request($arr);
            $this->deletar($request);
        }

        $this->remocaoNecessidadeCompras($lancamentoProjetoObj->id);
        $this->gravarHistoricoProjeto($lancamentoProjetoObj->id, 'modificacao_status', 'Status Antigo: '.$status_antigo.' Status Novo: '.$status, Auth::id());
        $this->emailAlteracaoStatus($status_antigo, $status, $lancamentoProjetoObj->id);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ''
        ]);
    }
    
    public function emailAlteracaoStatus($status_antigo, $status_atual, $id_projeto){
        try{
            $EmailObj = new EmailController();

            $status = $this->getEstados();

            $variaveis = [
                'numero_projeto' => $id_projeto,
                'status_antigo' => $status[$status_antigo],
                'status_atual' => $status[$status_atual],
                'usuario' => Auth::user()->name,
                'data_hora' => date('d/m/Y H:i:s')
            ];
            
            $EmailObj->sendEmailToken('01', 'alteracao_status_projeto', [], $variaveis);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [ 'mensagem' => $e],
                'response' => []
            ], 422);
		}
    }

    private function validacaoProjeto($id_projeto){
        $parametros = new Request ([
            'id_projeto' => encrypt($id_projeto)
        ]);

        $this->resultado($parametros);
        
        $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projetos_id', $id_projeto);
        $query_faccao->whereNull('faccao_id');
        $result_faccao = $query_faccao->first();

        $mensagem = '';

        if(!empty($result_faccao)){
            $mensagem = "Falta relação do Serviço com a Facção.";
            return $mensagem;
        }
        $parametros = new Request ([
            'id_projeto' => encrypt($id_projeto)
        ]);

        $this->resultado($parametros);

        $mensagem = '';

        $lancamentoProjetoObj = LancamentoProjeto::withTrashed()->find($id_projeto);

        $estabelecimento = $lancamentoProjetoObj->estabelecimento; 
	
	    $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem;
        $contador_produto = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->count();
        $contador_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->count();
        $contador_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->count();
        $contador_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->count();

        $verificacao_produto = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)
            ->whereNull('codigo_produto')
            ->where(function ($query){
                $query->whereNull('ncm')
                ->whereNull('peso');
            })->first();
        $verificacao_codigo_produto = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->whereNull('codigo_produto')->first();

        if(empty($lancamentoProjetoObj->nome_projeto)){
            $mensagem = $mensagem.'Obrigatório preencher o Nome do Projeto. ';
        }
        if(empty($lancamentoProjetoObj->cliente_codigo) || empty($lancamentoProjetoObj->users_codigo_representante)){
            $mensagem = $mensagem.'Obrigatório preencher o cliente. ';
        }
        if($lancamentoProjetoObj->desconto > $desconto_maximo){   
            $mensagem = $mensagem.'Desconto está acima do máximo permitido. Favor verificar. ';
        }
        if(empty($lancamentoProjetoObj->condicoes_pagamento_web_id)){
            $mensagem = $mensagem.'Obrigatório preencher a Condição de Pagamento. ';
        }
        if($contador_produto == 0){
            $mensagem = $mensagem.'Obrigatório ter produto. ';
        }
        if($contador_servico == 0){
            $mensagem = $mensagem.'Obrigatório ter serviço. ';
        }
        if(!empty($verificacao_produto)){
            $mensagem = $mensagem.'Há produto(s) sem NCM ou Peso. ';
        }
        if(!empty($verificacao_codigo_produto)){
            $mensagem = $mensagem.'Há produto(s) sem Código. ';
        }

        if(!empty($mensagem)){
            return $mensagem;
        }
        
        $lancamentoProjetoObj->estabelecimento = $estabelecimento;
        if(empty($lancamentoProjetoObj->revisor_user_id)){
            $lancamentoProjetoObj->revisor_user_id = Auth::id();
        }
        if(empty($lancamentoProjetoObj->linha)){
            $lancamentoProjetoObj->linha = $lancamentoProjetoObj->itens[0]->produto_detalhes->linha;
        }
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        return $mensagem;
    }

    function modalComposicao(Request $request){
        $fields = $request->only('id_produto', 'id_projeto', 'id_revisor', 'posicao');

        try{
            $id_produto = decrypt($fields['id_produto']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $info = $this->getInformacaoPreco($id_projeto);

        $posicao = $fields['posicao'];

        $produto = LancamentoProjetoProduto::find($id_produto);

        $dados = [
            'id' => encrypt($id_projeto),
            'id_produto' => encrypt($id_produto),
            'produto_descricao' => $produto->descricao,
            'numero_projeto' => $id_projeto,
            'estabelecimento' => $info['estabelecimento'],
            'estado_destino' => $info['estado_destino'],
            'media_condicao_pagamento' => $info['media_condicao_pagamento'],
            'preco_cif_fob' => $info['preco_cif_fob'],
            'cif_fob' => $info['cif_fob'],
            'revisor' => empty($fields['id_revisor'])? '' : $fields['id_revisor']
        ];

        $arr['id'] = $id_produto;

        $request = new Request($arr);

        $quantidade_produto = $this->getQuantidadeProduto($request, true);

        $query_tecido = LancamentoProjetoTecido::with(['tecido_detalhes']);
        $query_tecido->where('lancamento_projeto_produtos_id', '=', $id_produto);
        $result_tecidos = $query_tecido->get();

        $tecidos_tabela = [];

        foreach($result_tecidos as $tecido){
            $tecidos_tabela [] = [
                'id' => encrypt($tecido->id),
                'codigo' => $tecido->codigo_produto,
                'descricao' => $tecido->tecido_detalhes->descricao,
                'consumo_unitario' => parserValor4CasasDecimais($tecido->consumo_unitario),
                'preco_unitario' => parserValor($tecido->custo_unitario),
                'total_custo' => parserValor($tecido->valor_total),
                'consumo_total' => parserValor4CasasDecimais($tecido->consumo_total),
                'produto' => $tecido->produto->descricao
            ];

        }
        $tecido_total_ex = parserValor($query_tecido->sum('valor_total'));

        $query_insumo = LancamentoProjetoInsumo::with(['insumo_detalhes']);
        $query_insumo->where('lancamento_projeto_produtos_id', '=', $id_produto);
        $result_insumos = $query_insumo->get();

        $insumos_tabela = [];
        foreach($result_insumos as $insumo){
            if(isset($insumo->insumo_detalhes->produtoNasajon->unidade) && in_array($insumo->insumo_detalhes->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                $validar = true;
            }else{
                $validar = false;
            }
            $insumos_tabela [] = [
                'id' => encrypt($insumo->id),
                'codigo' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'consumo_unitario' => parserQtd($insumo->consumo_unitario),
                'preco_unitario' => parserValor($insumo->custo_unitario),
                'total_custo' => parserValor($insumo->valor_total),
                'consumo_total' => parserQtd($insumo->consumo_total),
                'validar' => $validar,
            ];
        }

        $insumo_total_ex = parserValor($query_insumo->sum('valor_total'));

        $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', '=', $id_produto);
        $query_faccao->with(['tecido']);
        $result_faccoes = $query_faccao->get();

        $faccoes_tabela = [];

        foreach($result_faccoes as $faccao){
            $verificar_quantidade_no_projeto = false;
            $mensagem_quantidade = '';
            if(array_search($faccao->tipo_servico_id, $this->servico_minimo_350) !== false){
                $verificar_quantidade_no_projeto = true;
                $mensagem_quantidade = 'Cálculo feito baseado no valor mínimo 350';
            }else if(array_search($faccao->tipo_servico_id, $this->servico_minimo_500) !== false){
                $verificar_quantidade_no_projeto = true;
                $mensagem_quantidade = 'Cálculo feito baseado no valor mínimo 500';
            }

            $faccoes_tabela [] = [
                'id' => encrypt($faccao->id),
                'cnpj' => empty($faccao->faccao->fornecedor)? '' : $faccao->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($faccao->faccao->fornecedor)? '' : $faccao->faccao->fornecedor->nome,
                'codigo_tipo_de_servico' => $faccao->tipo_servico_id,
                'tipo_de_servico' => $faccao->tipo_de_servico->descricao,
                'unidade' => empty($result->unidade)? '':$result->unidade->descricao,
                'preco_unitario' => parserValor($faccao->custo_unitario),
                'quantidade' => parserValor($faccao->quantidade),
                'custo_total' => $verificar_quantidade_no_projeto? '<div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="'.$mensagem_quantidade.'">'.parserValor($faccao->valor_total).'*</div>' : parserValor($faccao->valor_total),
                'produto' => $faccao->produto->descricao,
                'tecido' => empty($faccao->tecido)? '' : $faccao->tecido->tecido_detalhes->descricao,
                'produto_acabado' => empty($faccao->codigo_produto_acabado)? '' : $faccao->produto_acabado->descricao,
            ];
        }
        $faccao_total_ex = parserValor($query_faccao->sum('valor_total'));

        $servicos = $this->getServicoTecido();

        $arquivos = $this->carregarArquivoPorProduto($id_produto);

        $projeto = LancamentoProjeto::find($id_projeto);

        $licitacao = $projeto->licitacao;

        return view('programs.lancamento_de_projeto.modal.composicao_produto_final')->with(['dados' => $dados, 'tecidos_tabela' => $tecidos_tabela, 'tecido_total_ex' => $tecido_total_ex, 'quantidade_produto' => $quantidade_produto, 'insumos_tabela' => $insumos_tabela, 'insumo_total_ex' => $insumo_total_ex, 'faccoes_tabela' => $faccoes_tabela, 'faccao_total_ex' => $faccao_total_ex, 'servicos' => $servicos, 'posicao' => $posicao, 'arquivos' => $arquivos, 'licitacao' => $licitacao]);
    }

    function carregarProdutos(Request $request){
        $id_projeto = $request->only('id_projeto')['id_projeto'];
        try{
            $id_projeto = decrypt($id_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->with('projeto_detalhes.cliente');
        $query_produto->where('lancamento_projetos_id', $id_projeto);
        $query_produto->orderBy('descricao');
        $result_produtos = $query_produto->get();

        $produtos_tabela = [];

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        switch($lancamentoProjetoObj->cliente->uf){
            case 'SP':
                $estabelecimento = 5;
                break;
            default:
                $estabelecimento = 4;
        }    
	
	    $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem;

        foreach($result_produtos as $produto){
            $mensagem_error = "";
            $index = 0;

            $query_faccao = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $produto->id);
            $result_faccao = $query_faccao->first();
    
            if(!empty($result_faccao)){       
                $query_faccao->whereNull('faccao_id');
                $result_faccao = $query_faccao->first();
                if(empty($result_faccao)){
                    $produto_ok = true;
                }else{
                    $produto_ok = false;
                    $mensagem_error = "Serviço sem Facção.";
                }
            }else{
                $produto_ok = false;
                $mensagem_error = "Serviço sem Facção.";
            }

            while($produto_ok == true && $index < count($produto->composicao_insumos)){
                if(!in_array($produto->composicao_insumos[$index]->insumo_detalhes->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                    $produto_ok = false;
                    $string = implode(",", $this->unidades_permitida_insumo);
                    $mensagem_error = "A unidade padrão do insumo está incorreta, a unidade padrão deve ser ".$string.". Favor verificar com o setor responsável.";
                }
                $index++;
            }

            $total_custo = $produto->valor_total_tecido->total + $produto->valor_total_insumo->total + $produto->valor_total_servico->total;
            $total_custo_unitario = $total_custo / $produto->quantidade; 

            $desconto = (!empty($total_custo_unitario) && $produto->preco_venda > 0) ? (($total_custo_unitario/$produto->preco_venda)-1) * 100 : 0; 

            if($desconto > $desconto_maximo){
                $desconto_acima_permitido = true;
            }else{
                $desconto_acima_permitido = false;
            }

            $raiz_cnpj = (empty($produto->projeto_detalhes->cliente))? '' : substr($produto->projeto_detalhes->cliente->cpf_cnpj,0,10);
            $preco_venda = '';

            if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
                $preco_venda = parserValor($produto->preco_venda);
            }

            $produtos_tabela[] = [
                'id' => encrypt($produto->id),
                'produto' => $produto->id,
                'indice' => $produto->indice,
                'projeto_id' =>  encrypt($id_projeto),
                'codigo' => $produto->codigo_produto,
                'descricao' => $produto->descricao,
                'preco_venda' => $preco_venda,
                'custo_unitario' => empty($total_custo_unitario) ? '' : parserValor($total_custo_unitario),
                'quantidade' => parserQtd($produto->quantidade),
                'detalhes' => $produto->detalhe_producao,
                'ncm' =>  empty($produto->ncm) ? '' : $produto->ncm,
                'peso' => empty($produto->peso) ? '' : parserQtd($produto->peso),
                'total_tecido' => empty($produto->valor_total_tecido->total) ? '' : parserValor($produto->valor_total_tecido->total),
                'total_insumo' => empty($produto->valor_total_insumo->total) ? '' : parserValor($produto->valor_total_insumo->total),
                'total_servico' => empty($produto->valor_total_servico->total) ? '' : parserValor($produto->valor_total_servico->total),
                'total_custo' => empty($total_custo) ? '' : parserValor($total_custo),
                'desconto_acima_permitido' => $desconto_acima_permitido,
                'desconto' => $desconto,
                'produto_com_faccao' => $produto_ok,
                'mensagem_error' => $mensagem_error,
            ];
            
        }

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');
        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');

        $total_custo = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;
        
        $total_custo_unitario = $total_custo / $total_quantidade;


        $total = [
            'total_quantidade' => empty($total_quantidade)? '' : parserQtd($total_quantidade),
            'total_custo_tecido' => empty($total_custo_tecido)? '' : parserValor($total_custo_tecido),
            'total_custo_insumo' => empty($total_custo_insumo)? '' : parserValor($total_custo_insumo),
            'total_custo_servico' => empty($total_custo_servico)? '' : parserValor($total_custo_servico),
            'total_custo_total' => empty($total_custo)? '' : parserQtd($total_custo),
            'total_custo_unitario' => empty($total_custo_unitario)? '' : parserQtd($total_custo_unitario),
        ];

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produtos' => $produtos_tabela,
                'total' => $total
            ]
        ]);
    }

    public function getServicoTecido(){
        $query = ProdutoEspecificacao::select();
        $query->where('linha', 'ilike', 'MAO DE OBRA');
        $query->where(function($query){
            $query->where('grupo', 'ilike', '%ESTAMP%');
            $query->Orwhere('grupo', 'ilike', '%MATELASSE%');
        });
        $query->where('ativo', true);
        $query->orderBy('descricao');
        $result = $query->get();

        $servicos[0] = 'Selecione o Serviço';

        foreach($result as $servico){
            $servicos[$servico->codigo_produto] = $servico->descricao;
        }

        return $servicos;
    }

    public function getServicosPorProduto($id_projeto, $id_produto){
        $query = LancamentoProjetoFaccao::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $query->where('lancamento_projeto_produtos_id', $id_produto);
        $result = $query->get();

        foreach($result as $servico){
            $servicos ['tabela'][] = [
                'id' => encrypt($servico->id),
                'id_projeto' => encrypt($servico->lancamento_projetos_id),
                'codigo_tipo_de_servico' => $servico->tipo_servico_id,
                'cnpj' => empty($servico->faccao)? '' : $servico->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($servico->faccao)? '' : $servico->faccao->fornecedor->nome,
                'codigo_tipo_de_servico' => $servico->tipo_servico_id,
                'tipo_de_servico' => $servico->tipo_de_servico->descricao,
                'unidade' => '',
                'preco_unitario' => parserValor($servico->custo_unitario),
                'quantidade' => parserValor($servico->quantidade),
                'custo_total' => parserValor($servico->valor_total),
                'total' => parserValor($servico->valor_total),
                'produto' => empty($servico->lancamento_projeto_tecidos_id)? $servico->produto->descricao : $servico->tecido->tecido_detalhes->descricao,
                'tecido' => empty($servico->lancamento_projeto_tecidos_id) ? '' : $servico->tecido->tecido_detalhes->descricao,
                'produto_acabado' => empty($servico->codigo_produto_acabado) ? '' : $servico->produto_acabado->descricao,
                'tipo' => empty($servico->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
            ];
        }
        $servicos['total'] = parserValor($query->sum('valor_total'));

        return $servicos;
    }
    
    public function remocaoNecessidadeCompras($id_projeto){
        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', $id_projeto);
        $result_produto = $query_produto->get();

        foreach($result_produto as $produto){
            $query_necessidade_compras_x_projeto = NecessidadeComprasXProjeto::select();
            $query_necessidade_compras_x_projeto->where('lancamento_projeto_produtos_id', $produto->id);
            $necessidadeComprasXProjetoObj = $query_necessidade_compras_x_projeto->get();

            foreach($necessidadeComprasXProjetoObj as $necessidade_compra){
                $necessidadeComprasObj = NecessidadeCompras::find($necessidade_compra->necessidades_compras_id);

                if(!empty($necessidadeComprasObj)){
                    $necessidadeComprasObj->deleted_by = Auth::id();
                    $necessidadeComprasObj->save();
                    $necessidadeComprasObj->delete();
                }

                $necessidade_compra->deleted_by = Auth::id();
                $necessidade_compra->save();
                $necessidade_compra->delete();
            }
        }

        $query_produto_acabado = LancamentoProjetoFaccao::select();
        $query_produto_acabado->where('lancamento_projetos_id', $id_projeto);
        $result_produto_acabado = $query_produto_acabado->get();

        foreach($result_produto_acabado as $produto_acabado){
            $query_necessidade_compras_x_projeto = NecessidadeComprasXProjeto::select();
            $query_necessidade_compras_x_projeto->where('lancamento_projeto_faccoes_id', $produto_acabado->id);
            $necessidadeComprasXProjetoObj = $query_necessidade_compras_x_projeto->get();

            foreach($necessidadeComprasXProjetoObj as $necessidade_compra){
                $necessidadeComprasObj = NecessidadeCompras::find($necessidade_compra->necessidades_compras_id);

                if(!empty($necessidadeComprasObj)){
                    $necessidadeComprasObj->deleted_by = Auth::id();
                    $necessidadeComprasObj->save();
                    $necessidadeComprasObj->delete();
                }

                $necessidade_compra->deleted_by = Auth::id();
                $necessidade_compra->save();
                $necessidade_compra->delete();
            }
        }

        $query_tecido = LancamentoProjetoTecido::select();
        $query_tecido->where('lancamento_projetos_id', $id_projeto);
        $result_tecido = $query_tecido->get();

        foreach($result_tecido as $tecido){
            $query_necessidade_compras_x_projeto = NecessidadeComprasXProjeto::select();
            $query_necessidade_compras_x_projeto->where('lancamento_projeto_tecidos_id', $tecido->id);
            $necessidadeComprasXProjetoObj = $query_necessidade_compras_x_projeto->get();

            foreach($necessidadeComprasXProjetoObj as $necessidade_compra){
                $necessidadeComprasObj = NecessidadeCompras::find($necessidade_compra->necessidades_compras_id);

                if(!empty($necessidadeComprasObj)){
                    $quantidade = $necessidadeComprasObj->quantidade - $tecido->consumo_total;
                    $saldo = $necessidadeComprasObj->saldo - $tecido->consumo_total;

                    if($quantidade < 0){
                        $quantidade = 0;
                        $saldo = 0;
                    }

                    if($saldo < 0){
                        $saldo = 0;
                    }
    
                    $necessidadeComprasObj->quantidade = $quantidade;
                    $necessidadeComprasObj->saldo = $saldo;
                    $necessidadeComprasObj->updated_by = Auth::id();
                    $necessidadeComprasObj->save();
                }

                $necessidade_compra->deleted_by = Auth::id();
                $necessidade_compra->save();
                $necessidade_compra->delete();
            }
        }

        $query_insumo = LancamentoProjetoInsumo::select();
        $query_insumo->where('lancamento_projetos_id', $id_projeto);
        $result_insumo = $query_insumo->get();

        foreach($result_insumo as $insumo){
            $query_necessidade_compras_x_projeto = NecessidadeComprasXProjeto::select();
            $query_necessidade_compras_x_projeto->where('lancamento_projeto_insumos_id', $insumo->id);
            $necessidadeComprasXProjetoObj = $query_necessidade_compras_x_projeto->get();

            foreach($necessidadeComprasXProjetoObj as $necessidade_compra){
                $necessidadeComprasObj = NecessidadeCompras::find($necessidade_compra->necessidades_compras_id);

                if(!empty($necessidadeComprasObj)){
                    $quantidade = $necessidadeComprasObj->quantidade - $insumo->consumo_total;
                    $saldo = $necessidadeComprasObj->saldo - $insumo->consumo_total;

                    if($quantidade < 0){
                        $quantidade = 0;
                        $saldo = 0;
                    }

                    if($saldo < 0){
                        $quantidade = 0;
                    }
                    
                    $necessidadeComprasObj->quantidade = $quantidade;
                    $necessidadeComprasObj->saldo = $saldo;
                    $necessidadeComprasObj->updated_by = Auth::id();
                    $necessidadeComprasObj->save();
                }

                $necessidade_compra->deleted_by = Auth::id();
                $necessidade_compra->save();
                $necessidade_compra->delete();
            }
        }


    }

    public function imprimir(Request $request){
        $fields = $request->only('id_projeto');

        $projeto = LancamentoProjeto::withTrashed()->find($fields['id_projeto']);

        $estabelecimentos = $this->estabelecimentos();

        $query_produtos = LancamentoProjetoProduto::where('lancamento_projetos_id', $fields['id_projeto'])->get();
        $produtos = [];
        $total_produto = 0;
        foreach($query_produtos as $produto){
            $valor_total = $produto->preco_venda * $produto->quantidade;
            $produtos[] = [
                'codigo' => $produto->codigo_produto,
                'nome' => $produto->descricao,
                'detalhes' => $produto->detalhe_producao,
                'preco_venda' => parserValor($produto->preco_venda),
                'quantidade' => parserValor($produto->quantidade),
                'valor_total' => parserValor($valor_total)
            ];
            $total_produto = $total_produto + $valor_total;
        }

        $query_tecidos = LancamentoProjetoTecido::where('lancamento_projetos_id', $fields['id_projeto']);
        $total_tecido = $query_tecidos->sum('valor_total');
        $query_tecidos = $query_tecidos->get();
        $tecidos = [];
        foreach($query_tecidos as $tecido){
            $tecidos[] = [
                'estabelecimento' => $estabelecimentos[$tecido->codigo_estabelecimento],
                'codigo' => $tecido->codigo_produto,
                'descricao' => $tecido->tecido_detalhes->descricao,
                'referencia_produto' => empty($tecido->produto->descricao)? '' : $tecido->produto->descricao,
                'consumo_por_peca' => parserValor($tecido->consumo_unitario),
                'consumo_total' => parserValor($tecido->consumo_total),
                'custo_unitario' => parserValor($tecido->custo_unitario),
                'custo_total' => parserValor($tecido->valor_total)
            ];
        }

        $query_insumos = LancamentoProjetoInsumo::where('lancamento_projetos_id', $fields['id_projeto']);
        $total_insumo = $query_insumos->sum('valor_total');
        $query_insumos = $query_insumos->get();

        $insumos = [];
        foreach($query_insumos as $insumo){
            $insumos[] = [
                'estabelecimento' => $estabelecimentos[$insumo->codigo_estabelecimento],
                'codigo' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'referencia_produto' => empty($insumo->produto->descricao)? '' : $insumo->produto->descricao,
                'consumo_por_peca' => parserValor($insumo->consumo_unitario),
                'consumo_total' => parserValor($insumo->consumo_total),
                'custo_unitario' => parserValor($insumo->custo_unitario),
                'custo_total' => parserValor($insumo->valor_total)
            ];
        }

        $query_faccoes = LancamentoProjetoFaccao::where('lancamento_projetos_id', $fields['id_projeto']);
        $total_faccao = $query_faccoes->sum('valor_total');
        $query_faccoes =$query_faccoes->get();
        $faccoes = [];
        $com_faccao = false;
        foreach($query_faccoes as $faccao){
            $faccoes[] = [
                'referencia_produto' => empty($faccao->produto->descricao)? '' : $faccao->produto->descricao,
                'cnpj' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->cnpj_cpf,
                'faccao' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->nome,
                'tipo_de_servico' => $faccao->tipo_de_servico->descricao,
                'custo_unitario' => parserValor($faccao->custo_unitario),
                'quantidade' => parserValor($faccao->quantidade),
                'custo_total' => parserValor($faccao->valor_total),
                'tecido' => empty($faccao->lancamento_projeto_tecidos_id) ? '' : $faccao->tecido->tecido_detalhes->descricao,
                'produto_acabado' => empty($faccao->codigo_produto_acabado) ? '' : $faccao->produto_acabado->descricao,
                'tipo' => empty($faccao->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
            ];

            if(!empty($faccao->faccao)){
                $com_faccao = true;
            }
        }

        $dados= [
            "id" => $projeto->id,
            "nome_projeto" => $projeto->nome_projeto,
            "vendedor" => $projeto->users_codigo_representante." - ".$projeto->detalhes_representante->name,
            "cliente_cnpj" => $projeto->cliente->cpf_cnpj,
            "cliente_nome" => $projeto->cliente->nome,
            "pedido" => $projeto->pedido,
            "cliente_cidade_uf" => $projeto->cliente->cidade." - ".$projeto->cliente->uf,
            "status" => $this->getStatus($projeto->status),
            "data" => parserData($projeto->data_entrada),
            "condicao_pagamento_descr" => $projeto->condicoes_pagamento_web->descricao,
            "tipo_frete" => $projeto->tipo_frete,
            "created_by" => $projeto->created_by." - ".$projeto->criado_por->name,
            "created_at" => date('d/m/Y H:i:s', strtotime($projeto->created_at)),
            "updated_by" => empty($projeto->updated_by)? '' : $projeto->updated_by." - ".$projeto->criado_por->name,
            "updated_at" => date('d/m/Y H:i:s', strtotime($projeto->updated_at)),
            'total_do_pedido' => parserValor($projeto->valor_total_pedido),
            'custo_total' => parserValor($projeto->custo_total),
            'produtos' => $produtos,
            'tecidos' => $tecidos,
            'insumos' => $insumos,
            'faccoes' => $faccoes,
            'com_faccao' => $com_faccao,
            'comissao' => parserValor($projeto->comissao).' %',
            'total_produto' => parserValor($total_produto),
            'total_tecido' => parserValor($total_tecido),
            'total_insumo' => parserValor($total_insumo),
            'total_faccao' => parserValor($total_faccao),
            'frete_adicional'=> parserValor($projeto->frete),
            'custo_unitario_mn'=> parserValor($projeto->custo_unitario_mn),
            'quantidade_total' => parserQtd($projeto->quantidade_total),
            'desconto' => empty($projeto->desconto)? '': parserValor($projeto->desconto).' %',
            'preco_medio_venda' => parserValor($projeto->preco_venda),
            'estabelecimento' => empty($projeto->estabelecimentoDetalhes)? '': $projeto->estabelecimentoDetalhes->codigo." - ".$projeto->estabelecimentoDetalhes->nomefantasia,
            'data_previsao_entrega' => empty($projeto->data_previsao_entrega)? '' : parserData($projeto->data_previsao_entrega),
            'email_comprador' => empty($projeto->cliente->emailcobranca)? $projeto->cliente->email : $projeto->cliente->emailcobranca,
            'pedido_venda' => empty($projeto->pedido_id)? '' : $projeto->pedido_id,
        ];

        return view("programs.lancamento_de_projeto.imprimir")->with(['dados' => $dados]);
    }

    public function imprimirResumoFaccao(Request $request){
        $fields = $request->only('id_projeto', 'id_faccao');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
            $id_faccao = decrypt($fields['id_faccao']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $projeto = LancamentoProjeto::withTrashed()->find($id_projeto);

        $estabelecimentos = $this->estabelecimentos();

        $query_produtos = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto);
        $query_produtos->whereHas('servico_detalhes', function($query) use($id_faccao){
            $query->where('faccao_id', $id_faccao);
            $query->whereNull('codigo_produto_acabado');
            // $query->whereNotNull('pedido_compras_gerado_nasajon');
        });

        $result_produtos = $query_produtos->get(); 
        $produtos = [];
        $quantidade_total_produto = 0;
        $projeto_produtos = [];
        foreach($result_produtos as $produto){
            $produtos[] = [
                'nome' => isset($produto->produto_detalhes) ? $produto->produto_detalhes->descricao : $produto->descricao,
                'codigo' => $produto->codigo_produto,
                'detalhes' => $produto->detalhe_producao,
                'quantidade' => parserValor($produto->quantidade),
            ];
            $quantidade_total_produto = $quantidade_total_produto + $produto->quantidade;
            $projeto_produtos[] = $produto->id;
        }

        $query_faccoes = LancamentoProjetoFaccao::with(['preco', 'produto.produto_detalhes']);
        $query_faccoes->where('lancamento_projetos_id', $id_projeto);
        $query_faccoes->where('faccao_id', $id_faccao);
        $query_faccoes = $query_faccoes->get();

        $faccoes = [];
        $quantidade_total_tecido = 0;
        $tecidos = [];
        $numeros_pedidos_compras = [];
        $pedido_compra = '';
        $total_faccao = 0;
        $fornecedor = '';
        $custo_total_servico = 0;
        $projeto_tecidos_consumo = [];

        $data_previsao_entrega = "";

        foreach($query_faccoes as $faccao){
            // if(!empty($faccao->pedido_compras_gerado_nasajon)){
                $numeros_pedidos_compras[$faccao->pedido_compras_gerado_nasajon]= $faccao->pedido_compras_gerado_nasajon;

                $codigo_produto = empty($faccao->tecido)? $faccao->produto->codigo_produto : $faccao->tecido->codigo_produto;

                $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                $fichaTecnicaObjeto->with(['servicos' => function ($query) use($faccao){
                    $query->where('codigo_produto', $faccao->tipo_servico_id);
                }]);
                $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();

                if(empty($fichaTecnicaObjeto)){
                    $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                    $fichaTecnicaObjeto->where('codigo_produto',$faccao->produto->codigo_produto);
                    $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
                }
                       
                $custo_gerencial_servicos_total = 0;
                $custo_gerencial = 0;
                $servicos = [];
                        
                $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$custo_unitario){        
    
                    $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                    $custo_gerencial_servicos_total += $custo_gerencial;
                    $custo_unitario = $custo_gerencial_servicos_total;     
                });          
                    $custo_total = $custo_unitario * $faccao->quantidade;

                //$custo_unitario = round($faccao->tipo_de_servico->preco->preco_real / $this->margem_preco, 2);

                //$custo_total = $faccao->quantidade * $custo_unitario;

                if(empty($fornecedor)){
                    $fornecedor = $faccao->faccao->fornecedor->nome. ' - ' .$faccao->faccao->fornecedor->cnpj_cpf;
                }

                if(empty($faccao->tecido)){
                    if(isset($faccao->produto->produto_detalhes)){
                        $referencia_produto = $faccao->produto->produto_detalhes->descricao;
                    }else{
                        $referencia_produto = $faccao->produto->descricao;
                    }
                }

                $faccoes[] = [
                    'referencia_produto' => $referencia_produto,
                    'cnpj' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->cnpj_cpf,
                    'faccao' => empty($faccao->faccao)? '' : $faccao->faccao->fornecedor->nome,
                    'tipo_de_servico' => $faccao->tipo_de_servico->descricao,
                    'custo_unitario' => parserValor($custo_unitario),
                    'quantidade' => parserValor($faccao->quantidade),
                    'custo_total' => parserValor($custo_total),
                    'tecido' => empty($faccao->lancamento_projeto_tecidos_id) ? '' : $faccao->produto_acabado->descricao,
                    'produto_acabado' => empty($faccao->codigo_produto_acabado) ? '' : $faccao->produto_acabado->descricao,
                    'tipo' => empty($faccao->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO'
                ];
                $data_previsao_entrega = empty($faccao->$data_previsao_entrega)? '' : $faccao->$data_previsao_entrega;
                $custo_total_servico = $custo_total_servico + $custo_total;
                $total_faccao = $total_faccao + $faccao->quantidade;
    
                if($faccao->codigo_produto_acabado){
                    $tecidos[] = [
                        'codigo' => $faccao->codigo_produto_acabado,
                        'nome' => $faccao->produto_acabado->descricao,
                        'detalhes' => '',
                        'quantidade' => parserValor($faccao->tecido->consumo_total),
                    ];
                    $quantidade_total_tecido = $quantidade_total_tecido + $faccao->tecido->consumo_total;
                    $projeto_tecidos_consumo [] = [
                        'tecido' => $faccao->produto_acabado->descricao,
                        'quantidade' => parserValor($faccao->quantidade),
                        'codigo_tecido_consumo' => $faccao->tecido->codigo_produto,
                        'descricao_tecido_consumo' => $faccao->tecido->tecido_detalhes->descricao,
                        'consumo_unitario' => parserValor($faccao->tecido->consumo_unitario),
                        'consumo_total' => parserValor($faccao->tecido->consumo_total),
                    ];
                }
            // }            
            
        }

        foreach($numeros_pedidos_compras as $numero_pedido_compra){
            if(!empty($pedido_compra)){
                $pedido_compra = $pedido_compra.', '.$numero_pedido_compra;
            }else{
                $pedido_compra = $numero_pedido_compra;
            }
        }
        asort($produtos);
        asort($faccoes);
        asort($tecidos);
        $dados= [
            "id" => $projeto->id,
            "nome_projeto" => $projeto->nome_projeto,
            "cliente_cnpj" => $projeto->cliente->cpf_cnpj,
            "cliente_nome" => $projeto->cliente->nome,
            "data" => parserData($projeto->data_entrada),
            'produtos' => $produtos,
            'tecidos' => $tecidos,
            'faccoes' => $faccoes,
            'comissao' => parserValor($projeto->comissao).' %',
            'quantidade_total_produto' => parserValor($quantidade_total_produto),
            'quantidade_total_tecido' => parserValor($quantidade_total_tecido),
            'total_faccao' => parserValor($total_faccao),
            'estabelecimento' => empty($projeto->estabelecimentoDetalhes)? '': $projeto->estabelecimentoDetalhes->codigo." - ".$projeto->estabelecimentoDetalhes->nomefantasia,
            'data_previsao_entrega' => empty($data_previsao_entrega)? parserData($projeto->data_previsao_entrega) : parserData($data_previsao_entrega),
            'pedido_venda' => empty($projeto->pedido_id)? '' : $projeto->pedido_id,
            'pedido_compra' => $pedido_compra,
            'fornecedor' => $fornecedor,
            'custo_total_servico' => parserValor($custo_total_servico),
        ];

        $query_tecidos = LancamentoProjetoTecido::with(['tecido_detalhes']);
        $query_tecidos->whereIn('lancamento_projeto_produtos_id', $projeto_produtos);
        $result_tecidos = $query_tecidos->get();

        $consumo_tecidos = [];

        foreach($result_tecidos as $tecido){
            $consumo_tecidos[$tecido->lancamento_projeto_produtos_id][] = [
                'codigo_produto' => $tecido->codigo_produto,
                'descricao' => empty($tecido->servico_detalhes)? $tecido->tecido_detalhes->descricao : $tecido->servico_detalhes->produto_acabado->descricao,
                'consumo_unitario' => parserValor($tecido->consumo_unitario),
                'consumo_total' => parserValor($tecido->consumo_total),
            ]; 
        }

        $query_insumos = LancamentoProjetoInsumo::with(['insumo_detalhes']);
        $query_insumos->whereIn('lancamento_projeto_produtos_id', $projeto_produtos);
        $result_insumos = $query_insumos->get();

        $consumo_insumos = [];

        foreach($result_insumos as $insumo){
            $consumo_insumos[$insumo->lancamento_projeto_produtos_id][] = [
                'codigo_produto' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'consumo_unitario' => parserValor($insumo->consumo_unitario),
                'consumo_total' => parserValor($insumo->consumo_total),
            ]; 
        }

        $query_produtos = LancamentoProjetoProduto::with(['produto_detalhes']);
        $query_produtos->whereIn('id', $projeto_produtos);
        $result_produtos = $query_produtos->get();

        $produtos_consumo = [];

        foreach($result_produtos as $produto){
            if(empty($consumo_insumos[$produto->id])){
                $consumo_insumos_produto = [];
            }else{
                $consumo_insumos_produto = $consumo_insumos[$produto->id];
            }

            if(empty($consumo_tecidos[$produto->id])){
                $consumo_tecidos_produto = [];
            }else{
                $consumo_tecidos_produto = $consumo_tecidos[$produto->id];
            }

            $produtos_consumo [] = [
                'produto' => $produto->codigo_produto." - ".$produto->produto_detalhes->descricao,
                'quantidade' => $produto->quantidade,
                'tecido' => $consumo_tecidos_produto,
                'insumo' => $consumo_insumos_produto,
            ];
        }

        $faccao = Faccao::find($id_faccao);

        $id_fornecedor_nasajon = $faccao->fornecedor->id;

        $pedidos_compras = $this->consultaPedidosCompras($projeto->id, $projeto->estabelecimento, $id_fornecedor_nasajon);

        $primeira_compra = true;
        $str_pedidos_compra = "";

        foreach($pedidos_compras as $pedido_compras){
            if($primeira_compra){
                $str_pedidos_compra = $pedido_compras['numero_pedido'];
            }else{
                $str_pedidos_compra = $str_pedidos_compra."/ ".$pedido_compras['numero_pedido'];
            }
            $primeira_compra = false;
        }

        $pedidos_remessa = $this->consultaPedidoRemessa($projeto->id, $id_fornecedor_nasajon);

        $primeira_remessa = true;
        $str_pedidos_remessa = "";

        foreach($pedidos_remessa as $pedido_remessa){
            if($primeira_remessa){
                $str_pedidos_remessa = $pedido_remessa['numero_pedido'];
            }else{
                $str_pedidos_remessa = $str_pedidos_remessa."/ ".$pedido_remessa['numero_pedido'];
            }
            $primeira_remessa = false;
        }

        return view("programs.lancamento_de_projeto.imprimir_resumo_faccao")->with(['dados' => $dados, 'produtos_consumo' => $produtos_consumo, 'str_pedidos_compra' => $str_pedidos_compra, 'str_pedidos_remessa' => $str_pedidos_remessa, 'projeto_tecidos_consumo' => $projeto_tecidos_consumo]);
    }

    function modalDuplicarProduto(Request $request){
        $id_produto = $request->only('id_produto')['id_produto'];

        try{
            $id_produto = decrypt($id_produto);
		}catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
		$produto_projeto = LancamentoProjetoProduto::with('projeto_detalhes.cliente')->find($id_produto);

        $intercompany = false;

        $raiz_cnpj = (empty($produto_projeto->projeto_detalhes->cliente))? '' : substr($produto_projeto->projeto_detalhes->cliente->cpf_cnpj,0,10);

        if(in_array($raiz_cnpj,$this->cnpjIntercompany())){
            $intercompany = true;
        }

        $produto = [
            'id' => encrypt($produto_projeto->id),
            'id_projeto' => encrypt($produto_projeto->lancamento_projetos_id),
            'codigo' => empty($produto_projeto->codigo_produto)? '': $produto_projeto->codigo_produto,
            'descricao' => $produto_projeto->descricao,
            'preco_venda' => parserValor($produto_projeto->preco_venda),
            'quantidade' => parserValor($produto_projeto->quantidade),
            'detalhes' => $produto_projeto->detalhe_producao,
            'ncm' => empty($produto_projeto->ncm)? '' : $produto_projeto->ncm,
            'peso' => empty($produto_projeto->peso)? '' : $produto_projeto->peso,
        ];

        return view("programs.lancamento_de_projeto.modal.duplicar_produto")->with(['produto' => $produto,'intercompany' => $intercompany]);
    }

    function duplicarProduto(LancamentoProdutoRequest $request){
        $fields = $request->only('id_projeto', 'id_produto_duplicar', 'produto_codigo', 'produto_descricao', 'produto_preco_venda', 'produto_quantidade','produto_detalhes', 'produto_ncm', 'produto_peso');
        
        try{
            $id_produto_duplicar = decrypt($fields['id_produto_duplicar']);
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        $descricao = '';
        if(empty($fields['produto_descricao'])){
            $descricao = strtoupper($fields['produto_descricao_hidden']);
        }else{
            $descricao = str_replace("'", " ", strtoupper(tirarAcentos($fields['produto_descricao'])));
        }

        $ncm = empty($fields['produto_ncm'])? '' : $fields['produto_ncm'];
        $peso = empty($fields['produto_peso'])? '' :  $fields['produto_peso'];

        $preco_venda = (!empty($fields['produto_preco_venda'])) ? parserNumber($fields['produto_preco_venda']) : 0;
        $quantidade = parserNumber($fields['produto_quantidade']);

        if(!empty($fields['produto_codigo'])){
            $produto_nasajon = ProdutoNasajon::select()->where('codigo', $fields['produto_codigo'])->first();
            if(!empty($produto_nasajon)){
                $ncm = empty($produto_nasajon->ncm)? $ncm : $produto_nasajon->ncm;
                $peso =  empty($produto_nasajon->pesobruto)? $peso : $produto_nasajon->pesobruto;
            }
        }

        $ultimo_indice = LancamentoProjetoProduto::select()->where('lancamento_projetos_id', $id_projeto)->max('indice');
        if(empty($ultimo_indice)){
            $indice = 1;
        }else{
            $indice = $ultimo_indice + 1;
        }

        $lancamentoProjetoProdutoObj = new LancamentoProjetoProduto;
        $lancamentoProjetoProdutoObj->lancamento_projetos_id = $id_projeto;
        $lancamentoProjetoProdutoObj->indice = $indice;
        $lancamentoProjetoProdutoObj->codigo_produto = $fields['produto_codigo'];
        $lancamentoProjetoProdutoObj->descricao = $descricao;
        $lancamentoProjetoProdutoObj->quantidade = $quantidade;
        $lancamentoProjetoProdutoObj->preco_venda = $preco_venda;
        $lancamentoProjetoProdutoObj->detalhe_producao = str_replace("'", " ", strtoupper(tirarAcentos($fields['produto_detalhes'])));
        if(!empty($ncm)){
            $lancamentoProjetoProdutoObj->ncm = $ncm;
        }
        if(!empty($peso)){
            $lancamentoProjetoProdutoObj->peso = $peso;
        }
        $lancamentoProjetoProdutoObj->created_by = Auth::id();
        $lancamentoProjetoProdutoObj->save();

        $id_produto = $lancamentoProjetoProdutoObj->id;

        $this->gravarHistoricoProjetoProduto($id_produto, "duplicar", "ID Produto Duplicado: ".$id_produto_duplicar." - ID Produto Gerado: ".$id_produto, Auth::id());

        $query_tecidos = LancamentoProjetoTecido::select();
        $query_tecidos->where('lancamento_projeto_produtos_id', $id_produto_duplicar);
        $result_tecidos = $query_tecidos->get();

        foreach($result_tecidos as $tecido){
            $lancamentoProjetoTecidoObj = new LancamentoProjetoTecido;
            $lancamentoProjetoTecidoObj->lancamento_projetos_id = $id_projeto;
            $lancamentoProjetoTecidoObj->lancamento_projeto_produtos_id = $id_produto;

            $consumo_total = $tecido->consumo_unitario * $quantidade;
            $valor_total = $tecido->custo_unitario * $consumo_total;

            $lancamentoProjetoTecidoObj->codigo_produto = $tecido->codigo_produto;
            $lancamentoProjetoTecidoObj->consumo_unitario = $tecido->consumo_unitario;
            $lancamentoProjetoTecidoObj->quantidade = $quantidade;
            $lancamentoProjetoTecidoObj->consumo_total = $consumo_total;
            $lancamentoProjetoTecidoObj->custo_unitario = $tecido->custo_unitario;
            $lancamentoProjetoTecidoObj->valor_total = $valor_total;
            $lancamentoProjetoTecidoObj->enviado_total = false;

            $lancamentoProjetoTecidoObj->created_by = Auth::id();
            $lancamentoProjetoTecidoObj->save();

            if($tecido->servico_detalhes){

                $quantidade_servico_tecido = $lancamentoProjetoTecidoObj->consumo_total * $tecido->servico_detalhes->valor_de_conversao;
                $custo_total_servico_tecido = $quantidade_servico_tecido * $tecido->servico_detalhes->custo_unitario;

                $lancamentoProjetoFaccaoObj = new LancamentoProjetoFaccao;
                if(!empty($tecido->servico_detalhes->faccao_id)){
                    $lancamentoProjetoFaccaoObj->faccao_id = $tecido->servico_detalhes->faccao_id;
                }
                $lancamentoProjetoFaccaoObj->lancamento_projetos_id = $id_projeto;
                $lancamentoProjetoFaccaoObj->tipo_servico_id = $tecido->servico_detalhes->tipo_servico_id;
                $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id = $id_produto;
                $lancamentoProjetoFaccaoObj->quantidade = $quantidade_servico_tecido;
                $lancamentoProjetoFaccaoObj->custo_unitario = $tecido->servico_detalhes->custo_unitario;
                $lancamentoProjetoFaccaoObj->valor_total = $custo_total_servico_tecido;

                $lancamentoProjetoFaccaoObj->lancamento_projeto_tecidos_id = $lancamentoProjetoTecidoObj->id;
                $lancamentoProjetoFaccaoObj->valor_de_conversao = $tecido->servico_detalhes->valor_de_conversao;

                $lancamentoProjetoFaccaoObj->created_by = Auth::id();
                $lancamentoProjetoFaccaoObj->save();

                if(empty($lancamentoProjetoFaccaoObj->tecido)){
                    $this->ajustePrecoServicoComQuantidadeMinima($id_projeto, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->produto->codigo_produto);
                }else{
                    $this->ajustePrecoServicoComQuantidadeMinima($id_projeto, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
                }
            }
        }

        $query_insumos = LancamentoProjetoInsumo::select();
        $query_insumos->where('lancamento_projeto_produtos_id', $id_produto_duplicar);
        $result_insumos = $query_insumos->get();

        foreach($result_insumos as $insumo){

            $consumo_total = ceil($insumo->consumo_unitario * $quantidade);
            $valor_total = $insumo->custo_unitario * $consumo_total;

            $lancamentoProjetoInsumoObj = new LancamentoProjetoInsumo;
            $lancamentoProjetoInsumoObj->lancamento_projetos_id = $id_projeto;
            $lancamentoProjetoInsumoObj->lancamento_projeto_produtos_id = $id_produto;
            $lancamentoProjetoInsumoObj->codigo_produto = $insumo->codigo_produto;
            $lancamentoProjetoInsumoObj->consumo_unitario = $insumo->consumo_unitario;
            $lancamentoProjetoInsumoObj->quantidade = $quantidade;
            $lancamentoProjetoInsumoObj->consumo_total = $consumo_total;
            $lancamentoProjetoInsumoObj->custo_unitario = $insumo->custo_unitario;
            $lancamentoProjetoInsumoObj->valor_total = $valor_total;
            $lancamentoProjetoInsumoObj->enviado_total = false;
            $lancamentoProjetoInsumoObj->created_by = Auth::id();
            $lancamentoProjetoInsumoObj->save();
        }

        $query_servicos = LancamentoProjetoFaccao::select();
        $query_servicos->where('lancamento_projeto_produtos_id', $id_produto_duplicar);
        $query_servicos->whereNull('lancamento_projeto_tecidos_id');
        $result_servicos = $query_servicos->get();

        foreach($result_servicos as $servico){
            $valor_total = $quantidade * $servico->custo_unitario;

            $lancamentoProjetoFaccaoObj = new LancamentoProjetoFaccao;
            if(!empty($servico->faccao_id)){
                $lancamentoProjetoFaccaoObj->faccao_id = $servico->faccao_id;
            }
            $lancamentoProjetoFaccaoObj->lancamento_projetos_id = $id_projeto;
            $lancamentoProjetoFaccaoObj->tipo_servico_id = $servico->tipo_servico_id;
            $lancamentoProjetoFaccaoObj->lancamento_projeto_produtos_id = $id_produto;
            $lancamentoProjetoFaccaoObj->quantidade = $quantidade;
            $lancamentoProjetoFaccaoObj->custo_unitario = $servico->custo_unitario;
            $lancamentoProjetoFaccaoObj->valor_total = $valor_total;

            $lancamentoProjetoFaccaoObj->created_by = Auth::id();
            $lancamentoProjetoFaccaoObj->save();

            if(empty($lancamentoProjetoFaccaoObj->tecido)){
                $this->ajustePrecoServicoComQuantidadeMinima($id_projeto, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->produto->codigo_produto);
            }else{
                $this->ajustePrecoServicoComQuantidadeMinima($id_projeto, $lancamentoProjetoFaccaoObj->tipo_servico_id, $lancamentoProjetoFaccaoObj->tecido->codigo_produto);
            }
        }

        $custo = $lancamentoProjetoProdutoObj->valor_total_tecido->total + $lancamentoProjetoProdutoObj->valor_total_insumo->total + $lancamentoProjetoProdutoObj->valor_total_servico->total;
        $custo_unitario = $custo / $quantidade;
        
        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
        switch($lancamentoProjetoObj->cliente->uf){
            case 'SP':
                $estabelecimento = 5;
                break;
            default:
                $estabelecimento = 4;
        } 
        $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem; 


        $raiz_cnpj = (empty($lancamentoProjetoProdutoObj->projeto_detalhes->cliente))? '' : substr($lancamentoProjetoProdutoObj->projeto_detalhes->cliente->cpf_cnpj,0,10);

        $desconto = (!empty($custo) && !in_array($raiz_cnpj,$this->cnpjIntercompany()))  ? (($custo_unitario/$lancamentoProjetoProdutoObj->preco_venda)-1) * 100 : 0; 

        if($desconto > $desconto_maximo){
            $desconto_acima_permitido = true;
        }else{
            $desconto_acima_permitido = false;
        }

        $produto = [
            'id' => encrypt($lancamentoProjetoProdutoObj->id),
            'indice' => $lancamentoProjetoProdutoObj->indice,
            'projeto_id' => encrypt($id_projeto),
            'codigo' => $lancamentoProjetoProdutoObj->codigo_produto,
            'descricao' => $lancamentoProjetoProdutoObj->descricao,
            'detalhes' => strtoupper($lancamentoProjetoProdutoObj->detalhe_producao),
            'preco_venda' => parserValor($lancamentoProjetoProdutoObj->preco_venda),
            'quantidade' => parserQtd($quantidade),
            'detalhe_producao' => strtoupper($lancamentoProjetoProdutoObj->detalhe_producao),
            'ficha_tecnica' => '',
            'ncm' => $lancamentoProjetoProdutoObj->ncm,
            'peso' => parserQtd($lancamentoProjetoProdutoObj->peso),
            'total_tecido' => empty($lancamentoProjetoProdutoObj->valor_total_tecido->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_tecido->total),
            'total_insumo' => empty($lancamentoProjetoProdutoObj->valor_total_insumo->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_insumo->total),
            'total_servico' => empty($lancamentoProjetoProdutoObj->valor_total_servico->total)? '' : parserValor($lancamentoProjetoProdutoObj->valor_total_servico->total),
            'produto_com_faccao' => 0,
            'total_custo' => empty($custo)? '' : parserValor($custo),
            'custo_unitario' => empty($custo_unitario)? '' : parserValor($custo_unitario),
            'desconto_acima_permitido' => $desconto_acima_permitido
        ];

        $total_quantidade = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->sum('quantidade');
        $total_custo_tecido = LancamentoProjetoTecido::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_insumo = LancamentoProjetoInsumo::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        $total_custo_servico = LancamentoProjetoFaccao::where('lancamento_projetos_id', $id_projeto)->sum('valor_total');
        
        $total_custo = $total_custo_tecido + $total_custo_insumo + $total_custo_servico;
        
        $total_custo_unitario = $total_custo / $total_quantidade;

        $total = [
            'total_quantidade' => empty($total_quantidade)? '' : parserQtd($total_quantidade),
            'total_custo_tecido' => empty($total_custo_tecido)? '' : parserQtd($total_custo_tecido),
            'total_custo_insumo' => empty($total_custo_insumo)? '' : parserQtd($total_custo_insumo),
            'total_custo_servico' => empty($total_custo_servico)? '' : parserQtd($total_custo_servico),
            'total_custo_total' => empty($total_custo)? '' : parserQtd($total_custo),
            'total_custo_unitario' => empty($total_custo_unitario)? '' : parserQtd($total_custo_unitario),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Produto duplicado com sucesso.',
            'error' => [],
            'response' => [
                'produto' => $produto,
                'total' => $total
            ]
        ]);
    }

    public function gravarHistoricoProjetoProduto($id_produto, $natureza, $motivo, $user){
        $historicoProjetoProdutoObj = new HistoricoProjetoProduto;
        $historicoProjetoProdutoObj->lancamento_projeto_produtos_id = $id_produto;
        $historicoProjetoProdutoObj->natureza = $natureza;
        $historicoProjetoProdutoObj->motivo = $motivo;
        $historicoProjetoProdutoObj->created_by = $user;
        $historicoProjetoProdutoObj->save();
    }

    public function alteracaoEmMassaProduto(ProjetoAlteracaoEmMassaProdutoRequest $request){
        $fields = $request->only('ncm', 'peso', 'id_projeto', 'sobrescrever', 'id_revisor');

        if(!empty($fields['ncm']) || !empty($fields['peso'])){
            try{
                $id_projeto = decrypt($fields['id_projeto']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ], 422);
            }

            $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);

            if(!empty($fields['ncm'])){
                $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::select(); 
                $lancamentoProjetoProdutoObj->where('lancamento_projetos_id', $id_projeto);
                if($fields['sobrescrever'] === "false"){
                    $lancamentoProjetoProdutoObj->whereNull('ncm');
                }
                $lancamentoProjetoProdutoObj->update(['ncm' => $fields['ncm'], 'updated_by' => Auth::id()]);
            }
            if(!empty($fields['peso'])){
                $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::select(); 
                $lancamentoProjetoProdutoObj->where('lancamento_projetos_id', $id_projeto);
                if($fields['sobrescrever'] === "false"){
                    $lancamentoProjetoProdutoObj->whereNull('peso');
                    $lancamentoProjetoProdutoObj->orWhere('peso', 0.0);
                }
                $lancamentoProjetoProdutoObj->update(['peso' => parserNumber($fields['peso']), 'updated_by' => Auth::id(), 'updated_at' => Carbon::now()]);
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Alteração em Massa de Produto com Sucesso.',
            'error' => [],
            'response' => []
        ]);
    }

    public function aplicarAlteracaoProduto(ProjetoAlteracaoProdutosRequest $request){
        $fields = $request->only('id_projeto', 'var_itens', 'id_revisor');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);

        foreach($fields['var_itens'] as $produto){
            $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($produto[0]);
            $lancamentoProjetoProdutoObj->ncm = $produto[1];
            $lancamentoProjetoProdutoObj->peso = parserNumber($produto[2]);
            $lancamentoProjetoProdutoObj->updated_by = Auth::id();
            $lancamentoProjetoProdutoObj->save();
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Aplicado Alteração com Sucesso.',
            'error' => [],
            'response' => []
        ]);
    }

    public function carregarTecidos(Request $request){
        $id_projeto = $request->only('id_projeto')['id_projeto'];
        try{
            $id_projeto = decrypt($id_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', $id_projeto);
        $query_produto->orderBy('indice');
        $result_produtos = $query_produto->get();

        $tecidos_tabela = [];

        $produtos = [];
        $indice = 1;

        foreach($result_produtos as $produto){ 
            $tecidos_tabela  = [];
            $indice = 1;
            foreach($produto->composicao_tecidos as $tecido){
                $tecidos_tabela [] = [
                    'indice' => $indice,
                    'tecido' => $tecido->id,
                    'codigo' => $tecido->codigo_produto,
                    'descricao' => $tecido->tecido_detalhes->descricao,
                    'consumo' => parserValor4CasasDecimais($tecido->consumo_unitario),
                    'consumo_total' => parserValor4CasasDecimais($tecido->consumo_total),
                    'quantidade' => $produto->quantidade,
                ];
                $indice++;
            }
            $produtos [] = [
                'indice' => $produto->indice,
                'descricao' => $produto->descricao,
                'quantidade' => parserQtd($produto->quantidade),
                'tecidos' => $tecidos_tabela
            ];
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produtos
        ]);
    }

    public function aplicarAlteracaoTecido(Request $request){
        $fields = $request->only('id_projeto', 'var_itens', 'id_revisor');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);

        foreach($fields['var_itens'] as $tecido){
            if(!empty($tecido[1])){
                $consumo_total = parserNumber($tecido[1]) * parserNumber($tecido[2]);

                $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($tecido[0]);
    
                $valor_total = $lancamentoProjetoTecidoObj->custo_unitario * (parserNumber($tecido[1]) * $lancamentoProjetoTecidoObj->quantidade);
    
                $lancamentoProjetoTecidoObj->consumo_unitario = parserNumber($tecido[1]);
                $lancamentoProjetoTecidoObj->consumo_total = parserNumber($tecido[1]) * $lancamentoProjetoTecidoObj->quantidade;
                $lancamentoProjetoTecidoObj->valor_total = $valor_total;
                $lancamentoProjetoTecidoObj->updated_by = Auth::id();
                $lancamentoProjetoTecidoObj->save();
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Aplicado Alteração com Sucesso.',
            'error' => [],
            'response' => []
        ]);
    }

    public function carregarInsumos(Request $request){
        $id_projeto = $request->only('id_projeto')['id_projeto'];
        try{
            $id_projeto = decrypt($id_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', $id_projeto);
        $query_produto->orderBy('indice');
        $result_produtos = $query_produto->get();

        $insumos_tabela = [];

        $produtos = [];
        $indice = 1;

        foreach($result_produtos as $produto){ 
            $insumos_tabela  = [];
            $indice = 1;
            foreach($produto->composicao_insumos as $insumo){
                if(in_array($insumo->insumo_detalhes->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                    $validar = true;
                }else{
                    $validar = false;
                }
                $string = implode(",", $this->unidades_permitida_insumo);
                $insumos_tabela [] = [
                    'indice' => $indice,
                    'insumo' => $insumo->id,
                    'codigo' => $insumo->codigo_produto,
                    'descricao' => $insumo->insumo_detalhes->descricao,
                    'consumo_total' => parserQtd($insumo->consumo_total),
                    'quantidade' => $produto->quantidade,
                    'validar' => $validar,
                    'unidade' => $insumo->insumo_detalhes->produtoNasajon->unidade,
                    'unidade_requisitada' => $string,
                ];
                $indice++;
            }
            $produtos [] = [
                'indice' => $produto->indice,
                'descricao' => $produto->descricao,
                'quantidade' => parserQtd($produto->quantidade),
                'insumos' => $insumos_tabela
            ];
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produtos
        ]);
    }

    public function aplicarAlteracaoInsumo(Request $request){
        $fields = $request->only('id_projeto', 'var_itens', 'id_revisor');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);

        foreach($fields['var_itens'] as $insumo){
            if(!empty($insumo[1])){
                $consumo_unitario = parserNumber($insumo[1]) / parserNumber($insumo[2]);

                $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($insumo[0]);
    
                $valor_total = $lancamentoProjetoInsumoObj->custo_unitario * parserNumber($insumo[1]);
    
                $lancamentoProjetoInsumoObj->consumo_unitario = $consumo_unitario;
                $lancamentoProjetoInsumoObj->consumo_total = parserNumber($insumo[1]);
                $lancamentoProjetoInsumoObj->valor_total = $valor_total;
                $lancamentoProjetoInsumoObj->updated_by = Auth::id();
                $lancamentoProjetoInsumoObj->save();
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Aplicado Alteração com Sucesso.',
            'error' => [],
            'response' => []
        ]);
    }

    public function carregarServicos(Request $request){
        $fields = $request->only('id_projeto', 'tipo');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', $id_projeto);
        $query_produto->orderBy('indice');
        $result_produtos = $query_produto->get();

        $servicos_tabela = [];

        $produtos = [];
        $indice = 1;

        foreach($result_produtos as $produto){ 
            $servicos_tabela  = [];
            $indice = 1;

            if($fields['tipo'] === "produto"){
                $array_servico = $produto->composicao_servicos_produto;
            }else if($fields['tipo'] === "tecido"){
                $array_servico = $produto->composicao_servicos_tecido;
            }else{
                $array_servico = $produto->composicao_servicos;
            }

            foreach($array_servico as $servico){
                $servicos_tabela [] = [
                    'indice' => $indice,
                    'tipo' => empty($servico->lancamento_projeto_tecidos_id)? 'PRODUTO' : 'TECIDO',
                    'servico' => $servico->id,
                    'codigo' => $servico->tipo_servico_id,
                    'descricao' => $servico->tipo_de_servico->descricao,
                    'faccao' => empty($servico->faccao)? '' : $servico->faccao->fornecedor->nome." - ".$servico->faccao->fornecedor->cnpj_cpf,
                ];
                $indice++;
            }
            $produtos [] = [
                'indice' => $produto->indice,
                'descricao' => $produto->descricao,
                'quantidade' => parserQtd($produto->quantidade),
                'servicos' => $servicos_tabela
            ];
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produtos
        ]);
    }

    public function aplicarAlteracaoServico(Request $request){
        $fields = $request->only('id_projeto', 'var_itens', 'id_revisor');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);

        $mensagem_erro = '';

        foreach($fields['var_itens'] as $key => $servico){
            if(!empty($servico[1])){
                $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf')
                    ->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \''.($servico[1]).'\'');
                $fornecedor_busca = $fornecedor_busca->first();

                if(empty($fornecedor_busca)){
                    $servico_detalhes = LancamentoProjetoFaccao::find($servico[0]);

                    $mensagem_erro = $mensagem_erro."O Produto ".$servico_detalhes->produto->indice." no serviço ".$servico_detalhes->tipo_servico_id." está com Facção incorreta. "; 
                }else{
                    $query_faccao = Faccao::select();
                    $query_faccao->where('cod_fornecedor', $fornecedor_busca->cnpj_cpf);
                    $result_faccao = $query_faccao->first();
    
                    if(empty($result_faccao)){
                        $servico_detalhes = LancamentoProjetoFaccao::find($servico[0]);
    
                        $mensagem_erro = $mensagem_erro."O Produto ".$servico_detalhes->produto->indice." no serviço ".$servico_detalhes->tipo_servico_id." está com Facção incorreta. "; 
                    }else{
                        $fields['var_itens'][$key][1] = $result_faccao->id;
                    }
                }
            }
        }

        if(empty($mensagem_erro)){
            foreach($fields['var_itens'] as $servico){
                $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::find($servico[0]);    
                $lancamentoProjetoFaccaoObj->faccao_id = $servico[1];
                $lancamentoProjetoFaccaoObj->updated_by = Auth::id();
                $lancamentoProjetoFaccaoObj->save();
            }

            return response()->json([
                'status' => 'sucess',
                'message' => 'Aplicado Alteração com Sucesso.',
                'error' => [],
                'response' => []
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => $mensagem_erro,
                'error' => [],
                'response' => []
            ], 422);
        }
    }

    public function alteracaoEmMassaServico(LancamentoProjetoAlteracaoEmMassaServicoRequest $request){
        $fields = $request->only('id_projeto', 'faccao', 'tipo', 'sobrescrever', 'id_revisor');
        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $this->setStatusEmRevisao($id_projeto, $fields['id_revisor']);

        $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf')
            ->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \''.($fields['faccao']).'\'');
        $fornecedor_busca = $fornecedor_busca->first();

        if(!empty($fornecedor_busca)){
            $query_faccao = Faccao::select();
            $query_faccao->where('cod_fornecedor', $fornecedor_busca->cnpj_cpf);
            $result_faccao = $query_faccao->first();

            if(!empty($result_faccao)){
                $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select(); 
                $lancamentoProjetoFaccaoObj->where('lancamento_projetos_id', $id_projeto);
                if($fields['sobrescrever'] === "false"){
                    $lancamentoProjetoFaccaoObj->whereNull('faccao_id');
                }
                if($fields['tipo'] === "produto"){
                    $lancamentoProjetoFaccaoObj->whereNull('lancamento_projeto_tecidos_id');
                }else if($fields['tipo'] === "tecido"){
                    $lancamentoProjetoFaccaoObj->whereNotNull('lancamento_projeto_tecidos_id');
                }

                $lancamentoProjetoFaccaoObj->update(['faccao_id' => $result_faccao->id, 'updated_by' => Auth::id(), 'updated_at' => Carbon::now()]);
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Aplicado Alteração com Sucesso.',
            'error' => [],
            'response' => []
        ]);
    }

    public function consultaPedidosCompras($id_projeto, $estabelecimento, $id_fornecedor_nasajon = ''){
        $query = HistoricoPedidoCompra::select('pedido_compra_uuid');
        $query->where('lancamento_projetos_id', $id_projeto);
        $query->distinct();
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $pedidos = [];

        foreach($result as $faccao_pedido_compra){
            $query_pedido_compra = ComprasNasajon::select('estabelecimento', 'numero_pedido', 'data_compra', 'previsao_entrega', 'situacao', 'fornecedor_cnpj', 'fornecedor_nome', 'id_nota');
            $query_pedido_compra->where('id_nota', $faccao_pedido_compra->pedido_compra_uuid);
            if(!empty($id_fornecedor_nasajon)){
                $query_pedido_compra->where('fornecedor_id', $id_fornecedor_nasajon);
            }
            $query_pedido_compra->distinct();
            $result_pedido_compra = $query_pedido_compra->first();

            if(!empty($result_pedido_compra)){
                $pedidos [] = [
                    'id' => encrypt($result_pedido_compra->id_nota),
                    'estabelecimento' => $estabelecimentos[intval($result_pedido_compra->estabelecimento)],
                    'numero_pedido' => $result_pedido_compra->numero_pedido,
                    'data_compra' => parserData($result_pedido_compra->data_compra),
                    'previsao_entrega' => parserData($result_pedido_compra->previsao_entrega),
                    'situacao' => $result_pedido_compra->situacao,
                    'fornecedor' => $result_pedido_compra->fornecedor_nome." - ".$result_pedido_compra->fornecedor_cnpj
                ];
            }
        }

        return $pedidos;
    }

    public function consultaPedidoRemessa($id_projeto, $id_fornecedor_nasajon = ''){
        $query = RemessaProduto::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $query->distinct();
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $pedidos = [];

        $query_pedido_remessa = PedidosVendaNasajon::select();
        $query_pedido_remessa->whereIn('id', $result->pluck('pedido_compra_numero_uuid'));
        if(!empty($id_fornecedor_nasajon)){
            $query_pedido_remessa->where('cliente', $id_fornecedor_nasajon);
        }
        $result_pedido_remessa = $query_pedido_remessa->get();

        foreach($result_pedido_remessa as $pedido_remessa){
            $pedidos [] = [
                'id' => $pedido_remessa->id,
                'estabelecimento' => $estabelecimentos[intval($pedido_remessa->estabelecimento_codigo)],
                'numero_pedido' => $pedido_remessa->numero,
                'nota' => empty($pedido_remessa->notafiscal_numero)? '' : $pedido_remessa->notafiscal_numero,
                'data_emissao' => parserData($pedido_remessa->emissao),
                'situacao' => $pedido_remessa->situacao_descricao,
                'fornecedor' => $pedido_remessa->cliente_detalhes->nome." - ".$pedido_remessa->cliente_detalhes->cpf_cnpj
            ];
        }

        return $pedidos;
    }

    public function verificarUnidadeDoInsumoDoProjeto($id_projeto){
        $retorno = "";

        $index = 0;

        $query = LancamentoProjetoInsumo::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $result = $query->get();

        $tamanho = count($result);
        while(empty($retorno) && $index < $tamanho){
            if(!in_array($result[$index]->insumo_detalhes->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                $string = implode(",", $this->unidades_permitida_insumo);
                $retorno = "A unidade padrão do(s) insumo(s) está(ão) incorreta, a unidade padrão deve ser ".$string.". Favor verificar com o setor responsável.";
            }
            $index++;
        }

        return $retorno;
    }

    public function verificarUnidadeDoInsumo($codigo_produto){
        $retorno = "";

        $query = ProdutoEspecificacao::select();
        $query->where('codigo_produto', $codigo_produto);
        $query->where('linha', 'ilike', 'INSUMO');
        $result = $query->first();

        if(!empty($result)){
            if(!in_array($result->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                $string = implode(",", $this->unidades_permitida_insumo);
                $retorno = "A unidade padrão do insumo está incorreta, a unidade padrão deve ser ".$string.". Favor verificar com o setor responsável.";
            }
        }

        return $retorno;
    }

    private function atualizarCustoPrecoProduto($id_produto){
        $produto = LancamentoProjetoProduto::find($id_produto);

        $total_custo = $produto->valor_total_tecido->total + $produto->valor_total_insumo->total + $produto->valor_total_servico->total;
        $total_custo_unitario = $total_custo / $produto->quantidade;

        $produto->custo = $total_custo_unitario;

        $produto->save();
        
        return $total_custo_unitario;
    }

    public function carregarDocumentos(Request $request){
        $id_projeto = $request->only('id_projeto')['id_projeto'];
        try{
            $id_projeto = decrypt($id_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $projeto = LancamentoProjeto::withTrashed()->where('id', $id_projeto)->first();

        $necessidadeComprasControllerObj = new NecessidadeComprasController;
        $necessidade_compras = $necessidadeComprasControllerObj->consultaNecessidadeCompraProjeto($projeto->id);

        $pedidos_compras = $this->consultaPedidosCompras($projeto->id, $projeto->estabelecimento);

        $pedidos_remessa = $this->consultaPedidoRemessa($projeto->id);
        
        $pedidos_transferencia = $this->consultaPedidoTransferencia($projeto->id);

        $pedido_venda = [];

        if(!empty($projeto->detalhes_pedido->pedido_gerado)){
            $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;

            $codigo_operacao = $aprovacaoDePedidoControllerObj->getCodigoOperacao($projeto->detalhes_pedido);
    
            $query_pedido_nasajon = PedidosVendaNasajon::select();
            $query_pedido_nasajon->where('numero', $projeto->detalhes_pedido->pedido_gerado);
            $query_pedido_nasajon->where('estabelecimento_codigo', $projeto->detalhes_pedido->estabelecimento_pad);
            $query_pedido_nasajon->where('operacao_codigo', $codigo_operacao);
            $result_pedido_nasajon = $query_pedido_nasajon->first();
        }

        if(!empty($projeto->pedido_id)){
            $pedido_venda [] = [
                'pedido' => $projeto->detalhes_pedido->id,
                'estabelecimento' => $estabelecimentos[$projeto->detalhes_pedido->estabelecimento],
                'cliente' => $projeto->detalhes_pedido->cliente->nome,
                'nota' => '',
                'pedido_gerado_id' => empty($result_pedido_nasajon)? '' : $result_pedido_nasajon->id,
                'pedido_gerado' => empty($projeto->detalhes_pedido->pedido_gerado)? '' : $projeto->detalhes_pedido->pedido_gerado,
                'status' => empty($projeto->detalhes_pedido->deleted_at)? $projeto->detalhes_pedido->status_pedido_detalhes->status : 'CANCELADO',
                'data_emissao' => parserData($projeto->detalhes_pedido->data_pedido),
                'data_previsao' => parserData($projeto->detalhes_pedido->data_previsao_entrega)
            ];
        }

        $retorno = [
            'pedidos_venda' => $pedido_venda,
            'necessidade_compras' => $necessidade_compras,
            'pedidos_compras' => [
                'pedidos' => $pedidos_compras,
            ],
            'pedidos_remessas' => [
                'pedidos' => $pedidos_remessa,
            ],
            'pedidos_transferencia' => $pedidos_transferencia
        ];

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno
        ]);
    }

    public function consultaPedidoTransferencia($id_projeto){
        $query_remessa = RemessaProduto::select('pedido_id');
        $query_remessa->where('lancamento_projetos_id', $id_projeto);
        $query_remessa->whereNotNull('pedido_id');
        $query_remessa->distinct();
        $result_remessa = $query_remessa->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [
            'pedidos' => [],
            'verificar' => false
        ];

        $pedidos = [];

        foreach($result_remessa as $remessa){
            $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;

            $codigo_operacao = $aprovacaoDePedidoControllerObj->getCodigoOperacao($remessa->pedido_transferencia);

            $query_pedido_nasajon = PedidosVendaNasajon::select();
            $query_pedido_nasajon->where('numero', $remessa->pedido_transferencia->pedido_gerado);
            $query_pedido_nasajon->where('estabelecimento_codigo', $remessa->pedido_transferencia->estabelecimento_pad);
            $query_pedido_nasajon->where('operacao_codigo', $codigo_operacao);
            $result_pedido_nasajon = $query_pedido_nasajon->first();

            if(!empty($result_pedido_nasajon)){
                $pedidos[] = [
                    'id' => $result_pedido_nasajon->id,
                    'estabelecimento' => $estabelecimentos[intval($result_pedido_nasajon->estabelecimento_codigo)],
                    'numero_pedido' => $result_pedido_nasajon->numero,
                    'nota' => empty($result_pedido_nasajon->notafiscal_numero)? '' : $result_pedido_nasajon->notafiscal_numero,
                    'data_emissao' => parserData($result_pedido_nasajon->emissao),
                    'situacao' => $result_pedido_nasajon->situacao_descricao,
                    'fornecedor' => $result_pedido_nasajon->cliente_detalhes->nome." - ".$result_pedido_nasajon->cliente_detalhes->cpf_cnpj
                ];
            }
            

            $retorno['verificar'] = true;
        }

        $retorno['pedidos'] = $pedidos;

        return $retorno; 
    }
    
    function tipoProdutoProjeto(){
        $retorno = [
            "todos" => [
                83 => "Hospitalar",
                "83_bionexo" => "Hospitalar Bionexo",
                "83_licitacao" => "Licitação Hospitalar",
                "104_licitacao" => "Licitação Tecido",
                77 => "Moda",
                "83_mostruario" => "Mostruário Hospitalar",
                "77_mostruario" => "Mostruário Moda",
                "104_mostruario" => "Mostruário Tecido",
                104 => "Tecido",
                96 => "Profissional"
            ],
            "principal" => 83
        ];

        if(Auth::user()->tipo_usuario_id === 12 || Auth::user()->tipo_usuario_id === 16){
            unset($retorno['todos'][77]);
            unset($retorno['todos'][104]);
        }

        return $retorno;
    }

    public function verificarProdutoGrupo($grupo){
        $query = ProdutoGrupo::select();
        $query->where('descricao', 'ilike', $grupo);
        $result = $query->first();

        if(empty($result)){
            $grupoObj = new ProdutoGrupo;
            $grupoObj->descricao = str_replace("'", " ", strtoupper(tirarAcentos($grupo)));
            $grupoObj->created_by = Auth::id();
            $grupoObj->save();
        }
    }

    public function verificarProdutoMarca($marca){
        $query = ProdutoMarca::select();
        $query->where('descricao', 'ilike', $marca);
        $result = $query->first();

        if(empty($result)){
            $marcaObj = new ProdutoMarca;
            $marcaObj->descricao = str_replace("'", " ", strtoupper(tirarAcentos($marca)));
            $marcaObj->created_by = Auth::id();
            $marcaObj->save();
        }
    }

    public function getContato(Request $request, $array = false){
        $cliente_cpf_cnpj = $request->only('cliente')['cliente'];

        $clienteNasajonObj = ClienteNasajon::select()->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($cliente_cpf_cnpj).'%\'')->first();
        if(empty($clienteNasajonObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não encontrado',
                'error' => [],
                'response' => []
            ], 422);
        }

        $query_projeto = LancamentoProjeto::select();
        $query_projeto->where('cliente_cpf_cnpj', $clienteNasajonObj->cpf_cnpj);
        $query_projeto->whereNotNull('email_contato');
        $query_projeto->orderBy('id', 'desc');
        $projeto = $query_projeto->first();

        if(!empty($projeto)){
            $nome_contato = $projeto->nome_contato;
            $email_contato = $projeto->email_contato;
        }else{
            $nome_contato = '';
            $email_contato = empty($clienteNasajonObj->email)? '' : $clienteNasajonObj->email;
        }

        $retorno = [
            'nome_contato' => $nome_contato,
            'email_contato' => $email_contato
        ];

        if($array){
            return $retorno;
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => $retorno
            ]);
        }
        
    }

    private function validarProjeto($lancamentoProjetoObj, $produtos, $revisao = false){
        $achado_produto_sem_tecido = false;
        $achado_produto_sem_servico = false;
        $index = 0;
        $mensagem = '';
        $desconto_maximo = 0;
        $estabelecimento = $lancamentoProjetoObj->estabelecimento; 

        $desconto_maximo = ParametrosPedido::select()->where('estabelecimento', $estabelecimento)->first()->valor_minimo_porcentagem;

        while($index < count($produtos) && ($achado_produto_sem_tecido == false || $achado_produto_sem_servico == false)){
            $tecido = LancamentoProjetoTecido::select()->where('lancamento_projeto_produtos_id', $produtos[$index]->id)->first();
            $servico = LancamentoProjetoFaccao::select()->where('lancamento_projeto_produtos_id', $produtos[$index]->id)->first();

            if(empty($tecido) && $achado_produto_sem_tecido === false){
                $achado_produto_sem_tecido = true;
            }

            if(empty($servico) && $achado_produto_sem_servico === false){
                $achado_produto_sem_servico = true;
            }
            $index++;
        }

        if($revisao){
            $verificacao_ncm = LancamentoProjetoProduto::where('lancamento_projetos_id', $lancamentoProjetoObj->id)->whereNull('ncm')->first();
            $verificacao_peso = LancamentoProjetoProduto::where('lancamento_projetos_id', $lancamentoProjetoObj->id)->whereNull('peso')->first();
            $verificar_insumo = $this->verificarUnidadeDoInsumoDoProjeto($lancamentoProjetoObj->id);
            $erro_no_produtos = "";

            if($achado_produto_sem_tecido){
                $erro_no_produtos = "Há produto(s) sem Tecido";
            }
            if($achado_produto_sem_servico){
                if(empty($erro_no_produtos)){
                    $erro_no_produtos = "Há produto(s) sem Serviço";
                }else{
                    $erro_no_produtos = $erro_no_produtos." e sem Serviço";
                }
            }
            if(!empty($verificacao_ncm)){
                if(empty($erro_no_produtos)){
                    $erro_no_produtos = "Há produto(s) sem NCM";
                }else{
                    $erro_no_produtos = $erro_no_produtos." e sem NCM";
                }
            }
            if(!empty($verificacao_peso)){
                if(empty($erro_no_produtos)){
                    $erro_no_produtos = "Há produto(s) sem Peso";
                }else{
                    $erro_no_produtos = $erro_no_produtos." e sem Peso";
                }
            }

            if(!empty($erro_no_produtos)){
                $erro_no_produtos = $erro_no_produtos.".";
                $mensagem = $mensagem.$erro_no_produtos;
            }

            $mensagem = $mensagem.$verificar_insumo;
        }else{
            if($achado_produto_sem_servico && $achado_produto_sem_tecido){
                $mensagem = $mensagem.'Há produto(s) sem Tecido e sem Serviço. ';
            }else if($achado_produto_sem_tecido){
                $mensagem = $mensagem.'Há produto(s) sem Tecido. ';
            }else if($achado_produto_sem_servico){
                $mensagem = $mensagem.'Há produto(s) sem Serviço. ';
            }
        }
        $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

        if(empty($index)){
            $mensagem = $mensagem.'Não há produto.';
        }else if(!$achado_produto_sem_tecido && !$achado_produto_sem_servico){
            if($lancamentoProjetoObj->valor_total_pedido < 700 && $lancamentoProjetoObj->mostruario === false && !in_array($raiz_cnpj,$this->cnpjIntercompany()) && $lancamentoProjetoObj->id != 3261){
                $mensagem = $mensagem.'O valor mínimo de venda é 700,00.';
            }else if($lancamentoProjetoObj->desconto > $desconto_maximo && !in_array($raiz_cnpj,$this->cnpjIntercompany())){   
                $mensagem = $mensagem.'Desconto está acima do máximo permitido. Favor verificar.';
            }
        }

        return $mensagem;
    }

    public function adicionarArquivoProduto(ArquivoProdutoAdicionarRequest $request){
        $fields = $request->only('arquivo', 'tipo_arquivo', 'id_produto');

        try{
            $id_produto = decrypt($fields['id_produto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($id_produto);
        $arquivo = $fields['arquivo'];   
        
        $query_verificacao_nome_arquivo = LancamentoProjetoProdutoArquivo::select();
        $query_verificacao_nome_arquivo->where(function ($query) use($arquivo){
            $query->where('nome', 'ilike', $arquivo->getClientOriginalName())
            ->orWhere('nome', 'ilike', str_replace(" ", "_",explode('.', $arquivo->getClientOriginalName())[0]).'_(%).'.$arquivo->getClientOriginalExtension());
        });
        $query_verificacao_nome_arquivo->where('lancamento_projeto_produtos_id', $id_produto);
        $query_verificacao_nome_arquivo->orderBy('id', 'desc');
        $query_verificacao_nome_arquivo = $query_verificacao_nome_arquivo->first();

        if(empty($query_verificacao_nome_arquivo)){
            $name_arquivo = str_replace(" ", "_", $arquivo->getClientOriginalName());
        }else{
            if(substr_count($query_verificacao_nome_arquivo->nome, '_(') > 0 ){
                $numero = intval(explode(')', explode('_(', $query_verificacao_nome_arquivo->nome)[1])[0]) + 1;
                $name_arquivo = str_replace(" ", "_", explode('.', $arquivo->getClientOriginalName())[0].'_('.$numero.').'.$arquivo->getClientOriginalExtension());
            }else{
                $name_arquivo = str_replace(" ", "_", explode('.', $arquivo->getClientOriginalName())[0].'_(1).'.$arquivo->getClientOriginalExtension());
            }
            
        }

        $path_file = $arquivo->storeAs($lancamentoProjetoProdutoObj->getUrlAramazenamentoArquivo().$lancamentoProjetoProdutoObj->lancamento_projetos_id."/".$lancamentoProjetoProdutoObj->id, $name_arquivo);
        
        $lancamentoProjetoProdutoArquivoObj = new LancamentoProjetoProdutoArquivo;
        $lancamentoProjetoProdutoArquivoObj->lancamento_projeto_produtos_id = $id_produto;
        $lancamentoProjetoProdutoArquivoObj->caminho = $path_file;
        $lancamentoProjetoProdutoArquivoObj->nome = $name_arquivo;
        $lancamentoProjetoProdutoArquivoObj->tipo = $fields['tipo_arquivo'];
        $lancamentoProjetoProdutoArquivoObj->created_by = Auth::id();
        $lancamentoProjetoProdutoArquivoObj->save();

        $lancamentoProjetoObj = LancamentoProjeto::find($lancamentoProjetoProdutoObj->lancamento_projetos_id);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();
        
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => encrypt($lancamentoProjetoProdutoArquivoObj->id),
                'nome' => $lancamentoProjetoProdutoArquivoObj->nome,
                'tipo' => $lancamentoProjetoProdutoArquivoObj->tipo,
                'arquivo' => Storage::url($lancamentoProjetoProdutoArquivoObj->caminho),
            ]
        ];

        return response()->json($retorno);
    }

    private function carregarArquivoPorProduto($id_produto){
        $retorno = [];

        $query = LancamentoProjetoProdutoArquivo::select();
        $query->where('lancamento_projeto_produtos_id', $id_produto);
        $result = $query->get();

        foreach($result as $arquivo){
            $retorno [] = [
                'id' => encrypt($arquivo->id),
                'nome' => $arquivo->nome,
                'tipo' => $arquivo->tipo,
                'arquivo' => Storage::url($arquivo->caminho)
            ];
        }

        return $retorno;
    }

    public function deletarArquivoProduto(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $lancamentoProjetoProdutoArquivoObj = LancamentoProjetoProdutoArquivo::find($id);
        $path_file = $lancamentoProjetoProdutoArquivoObj->caminho;
        Storage::delete($path_file);
        $lancamentoProjetoProdutoArquivoObj->deleted_by = Auth::id();
        $lancamentoProjetoProdutoArquivoObj->save();
        $lancamentoProjetoProdutoArquivoObj->delete();

        $lancamentoProjetoObj = LancamentoProjeto::find($lancamentoProjetoProdutoArquivoObj->produto->lancamento_projetos_id);
        $lancamentoProjetoObj->updated_by = Auth::id();
        $lancamentoProjetoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
    private function ajustePrecoServicoComQuantidadeMinima($id_projeto, $tipo_servico_id, $codigo_produto){
        if(in_array($tipo_servico_id, $this->servico_minimo_500)){
            $query = LancamentoProjetoFaccao::select();
            $query->where('lancamento_projetos_id', $id_projeto);
            $query->where('tipo_servico_id', $tipo_servico_id);
            $query->where(function($query)use($codigo_produto){
                $query->whereHas('produto', function($query) use($codigo_produto){
                    $query->where('codigo_produto', $codigo_produto);
                });
                $query->orWhereHas('tecido', function($query)use($codigo_produto){
                    $query->where('codigo_produto', $codigo_produto);
                });
            });

            $quantidade_total = $query->sum('quantidade');
            $volume_total = $query->count();

            $result = $query->get();

            if($quantidade_total >= 500){
                foreach($result as $servico){
                    $custo_total = $servico->custo_unitario * $servico->quantidade;
                    $servico->valor_total = $custo_total;
                    $servico->save();
                }
            }else{
                $custo_total = ($result[0]->custo_unitario * 500)/$volume_total;
                foreach($result as $servico){
                    $servico->valor_total = $custo_total;
                    $servico->save();
                }
            }
        }
    }


    private function cancelamentoPedidoNasajon($pedido_uuid){
        try {
            $result = DB::connection('nasajon')->select("SELECT * from integracoes.api_pedido_cancelar('" . $pedido_uuid."')");

            if(!is_array($result) || sizeof($result) == 0){
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                    "error" => [
                    ],
                    "response" => []
                ];
        
                return response()->json($response, 422);
            }


            $result = $result[0]->mensagem;
            $result = json_decode($result, true);
            if(!isset($result['codigo']) || $result['codigo'] !== 'OK'){
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                    "error" => [
                    ],
                    "response" => []
                ];
        
                return response()->json($response, 422);
            }
        } catch (\Illuminate\Database\QueryException $th) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                "error" => [
                ],
                "response" => []
            ];
    
            return response()->json($response, 422);
    
        }
    }

    public static function cancelarProjetoInativo(){

		return $projetos = DB::transaction(function(){

            $data_atual = Carbon::Now();

			$projetos_cancelados = LancamentoProjeto::where('updated_at', "<", $data_atual)
				->where('status', 0)
				->get();

			$projetos = [];

			foreach ($projetos_cancelados as $value) {
                $value->status = 98;
				$value->save();
				$value->delete();
				$projetos[] = $value->id;
			}

			return $projetos;

		});		
    }
    
    private function excluirServicoLicitacao($id_projeto, $licitacao){
        $query = LancamentoProjetoFaccao::select();
        $query->where('lancamento_projetos_id', $id_projeto);
        $query->whereHas('tipo_de_servico', function($query) use($licitacao){
            if($licitacao){
                $query->where('marca', 'not ilike', 'MAO DE OBRA LICITACAO');
            }else{
                $query->where('marca', 'ilike', 'MAO DE OBRA LICITACAO');
            }
        });
        $query->delete();
    }

    public function indexEdicaoMateriaPrima(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\EdicaoMateriaPrima") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\EdicaoMateriaPrima');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[1]);
        unset($estabelecimentos[2]);
        unset($estabelecimentos[3]);
        unset($estabelecimentos[7]);
        unset($estabelecimentos[8]);
        unset($estabelecimentos[20]);

        $representantes_busca = User::select()->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get();
    
        $representantes = [];
        foreach ($representantes_busca as $key => $value) {
            $userRole = $value->roles->pluck('id')->all();
            if(!empty($userRole)){
                if($userRole[0] == 35){
                    $representantes[$value->codigo_representante] = $value->codigo_representante." - ".strtoupper($value->name);
                }
            }
        }
        
        $estados = $this->getEstados();

        unset($estados[1]);
        unset($estados[2]);
        unset($estados[3]);
        unset($estados[4]);
        unset($estados[8]);
        unset($estados[9]);
        unset($estados[10]);
        unset($estados[99]);
        return view('programs.lancamento_de_projeto.edicao_materia_prima.index')->with(['estabelecimentos' => $estabelecimentos, 'representantes' => $representantes, 'estados' => $estados]);
    }

    public function filterEdicaoMateriaPrima(Request $request){
        $fields = $request->only('estabelecimento', 'representante', 'faccao', 'estado', 'num_projeto', 'nome_projeto', 'linha', 'cliente');

        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

        $query = LancamentoProjeto::select();
        $query->with(['detalhes_status.status_exibicao']);

        if(is_numeric($fields['num_projeto'])){
            $query->where('id', $fields['num_projeto']);
        }

        if(!empty($fields['nome_projeto'])){
            $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
        }

        if(!empty($fields['representante'])){
            $query->where('users_codigo_representante', $fields['representante']);
        }

        if(!empty($fields['cliente'])){
            $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
            $cliente_busca = $cliente_busca->get();

            $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
        }

        if(!empty($fields['linha'])){
            $query->where('linha', 'ilike', $fields['linha']);
        }

        if(!empty($fields['estado'])){
            if($fields['estado'] == 99){
                $query->whereNotNull('deleted_at');
            }else{
                $query->whereHas('detalhes_status', function($query) use($fields){          
                    $query->where('status_projeto_exibicao_id', $fields['estado']);
                });
                $query->whereNull('deleted_at');
            }
        }else{
            $query->whereHas('detalhes_status', function($query) use($fields){          
                $query->whereIn('status_projeto_exibicao_id', [4,5,6]);
            });
        }

        if(!empty($fields['estabelecimento'])){
            $query->where('estabelecimento',$fields['estabelecimento']);
        }
        
        $result = $query->get();

        foreach($result as $projeto){
            $retorno [] =[
                'num_projeto' => $projeto->id,
                'nome_projeto' => $projeto->nome_projeto,
                'cliente' => empty($projeto->cliente)? '' : $projeto->cliente->nome,
                'status' => !empty($projeto->deleted_at)? 'CANCELADO' : $projeto->detalhes_status->status_exibicao->descricao,
                'id' => encrypt($projeto->id), 
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalEditarMateriaPrima(Request $request){
        $id_projeto = $request->only('id')['id'];
        try{
            $id_projeto = decrypt($id_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_remessa = RemessaProduto::select();
        $query_remessa->where('lancamento_projetos_id', $id_projeto);
        $result_remessa = $query_remessa->get();

        $produtos_remessa = [];
        foreach($result_remessa as $remessa){
            $produtos_remessa[] = $remessa->produto_codigo;
        }

        $query_produto = LancamentoProjetoProduto::select();
        $query_produto->where('lancamento_projetos_id', $id_projeto);
        $query_produto->orderBy('indice');
        $result_produtos = $query_produto->get();

        $tecidos_tabela = [];
        $insumos_tabela = [];

        $produtos = [];
        $indice = 1;

        foreach($result_produtos as $produto){ 
            $tecidos_tabela  = [];
            $indice = 1;
            foreach($produto->composicao_tecidos as $tecido){
                $tecidos_tabela [] = [
                    'indice' => $indice,
                    'tecido' => $tecido->id,
                    'codigo' => $tecido->codigo_produto,
                    'descricao' => $tecido->tecido_detalhes->descricao,
                    'consumo' => parserValor4CasasDecimais($tecido->consumo_unitario),
                    'consumo_total' => parserValor4CasasDecimais($tecido->consumo_total),
                    'quantidade' => $produto->quantidade,
                    'liberado' => in_array($tecido->codigo_produto, $produtos_remessa)? false : true,
                ];
                $indice++;
            }
            $insumos_tabela  = [];
            $indice = 1;
            foreach($produto->composicao_insumos as $insumo){
                $insumos_tabela [] = [
                    'indice' => $indice,
                    'insumo' => $insumo->id,
                    'codigo' => $insumo->codigo_produto,
                    'descricao' => $insumo->insumo_detalhes->descricao,
                    'consumo_total' => parserQtd($insumo->consumo_total),
                    'quantidade' => $produto->quantidade,
                    'liberado' => in_array($insumo->codigo_produto, $produtos_remessa)? false : true,
                ];
                $indice++;
            }
            if(!empty($tecidos_tabela) || !empty($insumos_tabela)){
                $produtos [] = [
                    'indice' => $produto->indice,
                    'descricao' => $produto->descricao,
                    'quantidade' => parserQtd($produto->quantidade),
                    'tecidos' => $tecidos_tabela,
                    'insumos' => $insumos_tabela
                ];
            }
        }

        $id_projeto = encrypt($id_projeto);

        return view('programs.lancamento_de_projeto.edicao_materia_prima.modal.editar_materia_prima')->with(['produtos' => $produtos, 'id_projeto' => $id_projeto]);
    }

    public function salvarEdicaoMateriaPrima(Request $request){
        $fields = $request->only('id_projeto', 'array_tecidos', 'array_insumos');

        try{
            $id_projeto = decrypt($fields['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $array_tecidos = isset($fields['array_tecidos'])? $fields['array_tecidos'] : [];
        $array_insumos = isset($fields['array_insumos'])? $fields['array_insumos'] : [];
        $dados_invalidos = [];
        foreach($array_tecidos as $tecido){
            if(empty($tecido[3])){
                $dados_invalidos['consumo-'.$tecido[0]] = 'Consumo deve ser maior que zero';
            }

            $query_verificar_item = ProdutoEspecificacao::find(strtoupper($tecido[1]));

            if(empty($query_verificar_item)){
                $dados_invalidos['codigo-'.$tecido[0]] = 'Código inválido';
            }
        }

        foreach($array_insumos as $insumo){
            if(empty($insumo[3])){
                $dados_invalidos['consumo_total-'.$insumo[0]] = 'Consumo deve ser maior que zero';
            }

            $query_verificar_item = ProdutoEspecificacao::find(strtoupper($insumo[1]));

            if(empty($query_verificar_item)){
                $dados_invalidos['codigo-'.$insumo[0]] = 'Código inválido';
            }
        }

        if(!empty($dados_invalidos)){
            return response()->json([
                'status' => 'error',
                'message' => 'Campos inválidos',
                'error' => $dados_invalidos,
                'response' => []
            ], 422);
        }

        $mensagem = ""; 
        foreach($array_tecidos as $tecido){
            $mensagem_tecido = "";
            $codigo_tecido_novo = "";
            $consumo_novo = 0;
            if($tecido[1] !== $tecido[2]){
                $codigo_tecido_novo = strtoupper($tecido[1]);
            }

            if($tecido[3] !== $tecido[4]){
                $consumo_novo = parserNumber($tecido[3]);
                $consumo_total = parserNumber($tecido[3]) * parserNumber($tecido[5]);
            }
    
            if(!empty($consumo_novo) || !empty($codigo_tecido_novo)){
                $lancamentoProjetoTecidoObj = LancamentoProjetoTecido::find($tecido[0]);

                if(!empty($codigo_tecido_novo)){
                    $ProdutoEspecificacaoObj = ProdutoEspecificacao::find(strtoupper($tecido[1]));

                    $mensagem_tecido = "O Tecido ".$lancamentoProjetoTecidoObj->codigo_produto." - ".$lancamentoProjetoTecidoObj->tecido_detalhes->descricao." foi alterado para ".$ProdutoEspecificacaoObj->codigo_produto. " - ".$ProdutoEspecificacaoObj->descricao;
                    
                    $lancamentoProjetoTecidoObj->codigo_produto = $codigo_tecido_novo;

                    $necessidadeComprasXProjetoObj = NecessidadeComprasXProjeto::select();
                    $necessidadeComprasXProjetoObj->where('lancamento_projeto_tecidos_id', $tecido[0]);
                    $necessidadeComprasXProjetoObj = $necessidadeComprasXProjetoObj->first();
                    if(!empty($necessidadeComprasXProjetoObj)){
                        $necessidadeComprasXProjetoObj->deleted_by = Auth::id();
                        $necessidadeComprasXProjetoObj->save();
                        $necessidadeComprasXProjetoObj->delete();
                    }
                }

                if(!empty($consumo_novo)){
                    if(empty($mensagem_tecido)){
                        $mensagem_tecido = "O Tecido ".$lancamentoProjetoTecidoObj->codigo_produto." - ".$lancamentoProjetoTecidoObj->tecido_detalhes->descricao." teve seu consumo alterado de ".$lancamentoProjetoTecidoObj->consumo_unitario. " por ".$consumo_novo;
                    }else{
                        $mensagem_tecido = $mensagem_tecido." e teve seu consumo alterado de ".$lancamentoProjetoTecidoObj->consumo_unitario. " por ".$consumo_novo;
                    }
                                       
                    $valor_total = $lancamentoProjetoTecidoObj->custo_unitario * $consumo_total;
                    $lancamentoProjetoTecidoObj->consumo_unitario = $consumo_novo;
                    $lancamentoProjetoTecidoObj->consumo_total = $consumo_total;
                    $lancamentoProjetoTecidoObj->valor_total = $valor_total;
                }

                
                $lancamentoProjetoTecidoObj->updated_by = Auth::id();
                $lancamentoProjetoTecidoObj->save();

                $mensagem_tecido = ".\n";

                $mensagem = $mensagem.$mensagem_tecido;
            }    
        }

        foreach($array_insumos as $insumo){
            $codigo_insumo_novo = "";
            $consumo_total = 0;
            $mensagem_insumo = "";
            if($insumo[1] !== $insumo[2]){
                $codigo_insumo_novo = strtoupper($insumo[1]);
            }

            if($insumo[3] !== $insumo[4]){
                $consumo_total = parserNumber($insumo[3]);
            }
    
            if(!empty($consumo_total) || !empty($codigo_insumo_novo)){
                $lancamentoProjetoInsumoObj = LancamentoProjetoInsumo::find($insumo[0]);

                if(!empty($codigo_insumo_novo)){
                    $ProdutoEspecificacaoObj = ProdutoEspecificacao::find(strtoupper($insumo[1]));

                    $mensagem_insumo = "O Insumo ".$lancamentoProjetoInsumoObj->codigo_produto." - ".$lancamentoProjetoInsumoObj->insumo_detalhes->descricao." foi alterado para ".$ProdutoEspecificacaoObj->codigo_produto. " - ".$ProdutoEspecificacaoObj->descricao;

                    $lancamentoProjetoInsumoObj->codigo_produto = $codigo_insumo_novo;

                    $necessidadeComprasXProjetoObj = NecessidadeComprasXProjeto::select();
                    $necessidadeComprasXProjetoObj->where('lancamento_projeto_insumos_id', $insumo[0]);
                    $necessidadeComprasXProjetoObj = $necessidadeComprasXProjetoObj->first();
                    if(!empty($necessidadeComprasXProjetoObj)){
                        $necessidadeComprasXProjetoObj->deleted_by = Auth::id();
                        $necessidadeComprasXProjetoObj->save();
                        $necessidadeComprasXProjetoObj->delete();
                    }
                }

                if(!empty($consumo_total)){
                    if(empty($mensagem_insumo)){
                        $mensagem_insumo = "O Insumo ".$lancamentoProjetoInsumoObj->codigo_produto." - ".$lancamentoProjetoInsumoObj->insumo_detalhes->descricao." teve seu consumo alterado de ".$lancamentoProjetoInsumoObj->consumo_unitario. " por ".$consumo_total;
                    }else{
                        $mensagem_insumo = $mensagem_insumo." e teve seu consumo alterado de ".$lancamentoProjetoInsumoObj->consumo_unitario. " por ".$consumo_total;
                    }

                    $valor_total = $lancamentoProjetoInsumoObj->custo_unitario * $consumo_total;
                    $lancamentoProjetoInsumoObj->consumo_total = $consumo_total;
                    $lancamentoProjetoInsumoObj->valor_total = $valor_total;
                }

                $lancamentoProjetoInsumoObj->updated_by = Auth::id();
                $lancamentoProjetoInsumoObj->save();

                $mensagem_insumo = ".\n";

                $mensagem = $mensagem.$mensagem_insumo;
            }  
        }

        $projetoObj = LancamentoProjeto::find($id_projeto);
        $necessidadeComprasControllerObj = new NecessidadeComprasController;
        $necessidadeComprasControllerObj->adicionarAtravesProjeto($projetoObj);

        if(!empty($mensagem)){
            $this->emailEdicaoProjeto($mensagem, $id_projeto);
        }
        
        return response()->json([
            'status' => 'sucess',
            'message' => 'Editado com Sucesso.',
            'error' => [],
            'response' => []
        ]);
    }

    public function emailEdicaoProjeto($alteracao, $id_projeto){
        try{
            $EmailObj = new EmailController();
            
            $variaveis = [
                'numero_projeto' => $id_projeto,
                'alteracao' => $alteracao,
                'usuario' => Auth::user()->name,
                'data_hora' => date('d/m/Y H:i:s')
            ];
            
            $EmailObj->sendEmailToken('00', 'alteracao_materia_prima_projeto', [], $variaveis);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [ 'mensagem' => $e],
                'response' => []
            ], 422);
		}
    }

    private function cnpjIntercompany(){
        $raiz[] = '05.075.884';
        $raiz[] = '06.311.274';
        return $raiz;
    }
}
