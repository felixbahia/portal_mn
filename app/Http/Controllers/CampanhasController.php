<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use Auth;

use App\PedidoPortal;
use App\Campanha;
use App\CampanhasComissoesTipo;
use App\CampanhasApuracaoComissoe;
use App\CampanhasEstabelecimento;
use App\CampanhasAssociacaoPedido;
use App\CampanhasProduto;
use App\ProdutosEstoque;
use App\CampanhasLog;
use App\CampanhasComissaoCalculo;
use App\CampanhasComissaoCalculoIten;
use App\FaturamentoNotaNasajon;
use App\VendedorNasajon;
use App\User;
use App\ProdutoEspecificacao;
use App\CampanhasConsultaMetaVendedore;
use App\CampanhasConsultaMetaVendedoresProduto;
use App\CampanhasConsultaMetaVendedoresPeriodo;
use App\CampanhasConsultaMetaVendedoresContagemProduto;

use App\Exports\CampanhaExportarExcelExport;

use App\Http\Requests\CampanhaRegistrarRequest;
use App\Http\Requests\CampanhaConsultaRequest;
use App\Http\Requests\CampanhaAtualizarRequest;
use App\Http\Requests\CampanhaImportarProdutosArquivoResquest;

class CampanhasController extends Controller
{
    private $path = 'public/logo_campanha/';
    private $subordinado_supervisor_playstation_10046 = ['510', '517', '518', '519', '521']; 
    private $subordinado_supervisor_playstation_11194 = ['534', '525', '526', '527', '528', '529', '530']; 

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\CampanhaProdutos") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\CampanhaProdutos');
        
        $estabelecimentos = returnTodasEmpresasVendas();
		
        return view('programs.campanhas.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtro(CampanhaConsultaRequest $request){
        $campos = $request->only('estabelecimento', 'nome_campanha', 'data_inicio', 'data_fim');

        $inicio_campanha = Carbon::createFromFormat('d/m/Y',$campos['data_inicio'])->setTime(0,0,0);
        $fim_campanha = Carbon::createFromFormat('d/m/Y',$campos['data_fim'])->setTime(23,59,59);
        
        $campanhas = Campanha::where(function($query) use ($inicio_campanha,$fim_campanha){
            $query->where('inicio_campanha','>=',$inicio_campanha)
            ->orWhere('fim_campanha','<=',$fim_campanha);
        });

        if(!empty($campos['estabelecimento'])){
            $campanhas->whereHas('estabelecimentosCampanha',function($query) use ($campos){
                $query->where('estabelecimento_codigo',str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT));
            });
        }

        if(!empty($campos['nome_campanha'])){
            $campanhas->where('nome','ilike','%'.$campos['nome_campanha'].'%');
        }

        $campanhas = $campanhas->get();
        $retorno = [];

        foreach($campanhas as $campanha){
            $editar = null;
            $excluir = null;
            $hoje = Carbon::now();
            $data_final_campanha = Carbon::parse($campanha->fim_campanha);

            if($hoje->lte($data_final_campanha) && $campanha->ativo == true){
                $editar = true;
                $excluir = true;
            }

            $retorno[] = [
                'id' => encrypt($campanha->id),
                'nome' => $campanha->nome,
                'criacao' => parserData($campanha->created_at),
                'inicio_campanha' => parserData($campanha->inicio_campanha),
                'fim_campanha' => parserData($campanha->fim_campanha),
                'comissao_representante' => (!empty($campanha->comissao_representante)) ? parserValor($campanha->comissao_representante).'%' : '',
                'comissao_vendedor_interno' => (!empty($campanha->comissao_vendedor_interno)) ? parserValor($campanha->comissao_vendedor_interno).'%' : '',
                'comissao_gerente' => (!empty($campanha->comissao_gerente)) ? parserValor($campanha->comissao_gerente).'%' : '',
                'ativo' => $campanha->ativo,
                'editar' => $editar,
                'excluir' => $excluir,
            ];
        }
        
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno
            ]
        ];

        return response()->json($return,200);

    }

    public function modalInserirCampanha(){
        $estabelecimentos = returnTodasEmpresasVendas();


        $comissoes_tipos = CampanhasComissoesTipo::get();
        $array_tipo_comissao = [];

        foreach($comissoes_tipos as $comissoes){
            $array_tipo_comissao[$comissoes->id] = $comissoes->tipo_comissao;
        }
        
        return view('programs.campanhas.modal.inserir')->with(['estabelecimentos' => $estabelecimentos,'comissoes_tipo' => $array_tipo_comissao]);
    }

    public function modalPesquisaProduto(Request $request){
        $id = $request->only(['id_campanha']);

        return view('programs.campanhas.modal.produtos')->with(['id_campanha' => $id['id_campanha']]);
    }

    public function buscarProdutos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $fields = $request->only('grupo', 'codigo', 'nome', 'marca', 'linha', 'subgrupo', 'campanha_id', 'somente_diponivel', 'sem_estoque');

        if (empty($fields['grupo']) && empty($fields['codigo']) && empty($fields['nome']) && empty($fields['marca']) && empty($fields['linha'])) {

            $mensagem[] = "Informe pelo menos um campo para a busca!";

            $return = [
                "message" => '',
                'errors' => [
                    'grupo' => $mensagem,
                    'codigo' => $mensagem,
                    'nome' => $mensagem,
                    'marca' => $mensagem,
                    'linha' => $mensagem,
                    'subgrupo' => $mensagem,
                ]
            ];

            return response()->json($return, 422);
        }

        $produtos = ProdutoEspecificacao::with(['estoque' => function ($query) use ($fields) {
            if (!empty($fields['somente_diponivel'])) {
                $query->whereNull('campanha_id');
            } else {
                $query->whereNotNull('campanha_id');
            }
        }, 'reserva', 'segmento']);

        $produtos->whereHas('estoque', function ($query) use ($fields) {

            if (!empty($fields['somente_diponivel'])) {
                $query->whereNull('campanha_id');
            } else {
                $query->whereNotNull('campanha_id');
            }
        });
        $produtos->where('ativo', true);

        if (!empty($fields['nome'])) {
            $produtos->where('descricao', 'ilike', '%' . $fields['nome'] . '%');
        }

        if (!empty($fields['grupo'])) {
            $produtos->where('grupo', 'ilike', '%' . $fields['grupo'] . '%');
        }

        if (!empty($fields['codigo'])) {
            $produtos->where('codigo_produto', 'ilike', '%' . $fields['codigo'] . '%');
        }

        if (!empty($fields['marca'])) {
            $produtos->where('marca', 'ilike', '%' . $fields['marca'] . '%');
        }

        if (!empty($fields['linha'])) {
            $produtos->where('linha', 'ilike', '%' . $fields['linha'] . '%');
        }

        if (!empty($fields['subgrupo'])) {
            $produtos->where('subgrupo', 'ilike', '%' . $fields['subgrupo'] . '%');
        }

        $produtos = $produtos->distinct()->get();



        $produtos_codigos = $produtos->unique('codigo_produto')->pluck('codigo_produto')->toArray();

        $itens_portal = collect();

        $pedido_portal = PedidoPortal::with(['itens_pedido'])
            ->whereNotIn('status_pedido', [3, 5, 7])
            ->whereHas('itens_pedido', function ($query) use ($produtos_codigos) {
                $query->whereIn("cod_produto", $produtos_codigos);
            })
            ->get();

        $pedido_portal->each(function ($query) use (&$itens_portal) {
            foreach ($query->itens_pedido as $itens) {
                $itens_portal->push([
                    'codigo_produto' =>  $itens->cod_produto,
                    'quantidade' => $itens->quantidade,
                    'estabelecimento' => str_pad($query->estabelecimento, 2, "0", STR_PAD_LEFT),

                ]);
            }
        });

        unset($pedido_portal);

        $retorno = [];

        $produtos->each(function ($query) use (&$retorno, $itens_portal, $fields) {
            $total_estoque_empresa = [];
            $total_estoque_produto = [];

            $total_estoque = 0;
            $compras = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $disponivel = (floatval($query->estoque->sum('estoque')) + floatval($query->estoque->sum('compras'))) - floatval($query->estoque->sum('empenho'));

            $compras[$query->codigo_produto] = $query->estoque->sum('compras');


            if ($disponivel != 0.0) {
                $total_estoque_empresa[$query->codigo_produto] = $disponivel;
            }


            foreach ($compras as $estabel => $compra) {
                if (isset($total_estoque_empresa[$query->codigo_produto]) || $compra === 0) {
                    continue;
                }


                $total_estoque_empresa[$query->codigo_produto] = $compra;
            }

            unset($compras);

            $query_itens = $itens_portal->where('codigo_produto', $query->codigo_produto);

            if (!empty($query_itens)) {
                foreach ($query_itens as $item) {

                    $quantidade = !isset($item->quantidade) ? $item['quantidade'] : $item->quantidade;
                    if (isset($total_estoque_empresa[$query->codigo_produto])) {
                        $total_estoque_empresa[$query->codigo_produto] -= $quantidade;
                    }
                }
            }

            foreach ($query->reserva as $reservas) {
                if (isset($total_estoque_empresa[$query->codigo_produto])) {
                    $total_estoque_empresa[$query->codigo_produto] -= $reservas->quantidade > 0 ? $reservas->quantidade : 0;
                }
            }

            $total_empresa = [];

            foreach ($total_estoque_empresa as $key_empresa => $value_empresa) {
                if ($value_empresa == 0) {
                    continue;
                }
                $total_estoque += $value_empresa;
            }

            if ($total_estoque < 0) {
                $total_estoque = 0;
            }

            $campanha_ativa = '';
            $mesma_campanha = false;
            $campanha_id = $query->estoque->max('campanha_id');

            if (!empty($fields['campanha_id'])) {
                try {
                    $id_campanha = decrypt($fields['campanha_id']);
                } catch (\Exception $e) {
                    $id_campanha = '';
                }

                if (!empty($campanha_id) && $id_campanha == $campanha_id) {
                    $campanha_ativa = 'Atenção!<br>Produto já ativo nesta campanha.';
                    $mesma_campanha = true;
                } else if (!empty($campanha_id)) {
                    $campanha_ativa = 'Atenção!<br>Produto já ativo á campanha "' . $query->estoque[0]->campanha->nome . '", não será gravado com esta campanha ao menos que seja desativado da campanha vinculada.';
                }
            } else if (!empty($campanha_id)) {

                $campanha_ativa = 'Atenção!<br>Produto já ativo á campanha "' . $query->estoque[0]->campanha->nome . '", não será gravado com esta campanha ao menos que seja desativado da campanha vinculada.';
            }

            if (!empty($fields['sem_estoque']) && $total_estoque == 0) {
                $retorno[] = [
                    'codigo' => str_replace([',', '|'], '', $query->codigo_produto),
                    'grupo' => $query->grupo,
                    'marca' => $query->marca,
                    'linha' => $query->linha,
                    'subgrupo' => $query->subgrupo,
                    'descricao' => $query->descricao,
                    'title_modal' => $query->codigo_produto . " - " . $query->grupo . " - " . $query->descricao,
                    'ativo' => $campanha_ativa,
                    'mesma_campanha' => $mesma_campanha,
                    'total_popover' => $total_empresa,
                    'estabelecimento_codigo' => '',
                    'estabelecimento' => '',
                    'segmento' => (!empty($query->produtoGrupo->segmento->descricao)) ?  $query->produtoGrupo->segmento->descricao : '',
                    'estoque' => parserQtd($total_estoque),
                    'campanha_nome' => (!empty($campanha_id)) ? $query->estoque[0]->campanha->nome : '',
                    'campanha_id' => (!empty($campanha_id)) ? $campanha_id : '',
                ];
            } else if (empty($fields['sem_estoque']) && $total_estoque > 0) {
                $retorno[] = [
                    'codigo' => str_replace([',', '|'], '', $query->codigo_produto),
                    'grupo' => $query->grupo,
                    'marca' => $query->marca,
                    'linha' => $query->linha,
                    'subgrupo' => $query->subgrupo,
                    'descricao' => $query->descricao,
                    'title_modal' => $query->codigo_produto . " - " . $query->grupo . " - " . $query->descricao,
                    'ativo' => $campanha_ativa,
                    'mesma_campanha' => $mesma_campanha,
                    'total_popover' => $total_empresa,
                    'estabelecimento_codigo' => '',
                    'estabelecimento' => '',
                    'segmento' => (!empty($query->produtoGrupo->segmento->descricao)) ?  $query->produtoGrupo->segmento->descricao : '',
                    'estoque' => parserQtd($total_estoque),
                    'campanha_nome' => (!empty($campanha_id)) ? $query->estoque[0]->campanha->nome : '',
                    'campanha_id' => (!empty($campanha_id)) ? $campanha_id : '',
                ];
            }
        });

        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno
            ]
        ];

        return response()->json($return, 200);
    }

    public function registrarCampanha(CampanhaRegistrarRequest $request){
        set_time_limit('600');
        ini_set('memory_limit','1024M');

        $campos = $request->only([
            'estabelecimentos_selecionados',
            'nome_campanha',
            'data_inicio_campanha',
            'data_fim_campanha',
            'comissao_representante',
            'tipo_comissao_representante',
            'comissao_vendedor_interno',
            'tipo_comissao_vendedor',
            'tipo_comissao_vendedor_interno',
            'comissao_gerente',
            'tipo_comissao_gerente',
            'data_inicio_apuracao_vendedor',
            'data_fim_apuracao_vendedor',
            'meta_reais_representante',
            'meta_metros_representante',
            'data_inicio_apuracao_gerente',
            'data_fim_apuracao_gerentes',
            'meta_reais_gerente',
            'meta_metros_gerente',
            'produto_selecionado'
        ]);

        $campos['produto_selecionado'] = $this->tratarArrayProdutos($campos['produto_selecionado']);

        $campanha = new Campanha();
        $campanha->nome = $campos['nome_campanha'];
        $campanha->inicio_campanha = Carbon::createFromFormat('d/m/Y',$campos['data_inicio_campanha'])->setTime(0,0,0)->format('Y-m-d 00:00:00');
        $campanha->fim_campanha = Carbon::createFromFormat('d/m/Y',$campos['data_fim_campanha'])->setTime(23,59,59)->format('Y-m-d 23:59:59');
        $campanha->comissao_representante = (!empty($campos['comissao_representante'])) ? parserNumber($campos['comissao_representante']) : null;
        $campanha->comissao_vendedor_interno = (!empty($campos['comissao_vendedor_interno'])) ? parserNumber($campos['comissao_vendedor_interno']) : null;
        $campanha->comissao_gerente = (!empty($campos['comissao_gerente'])) ? parserNumber($campos['comissao_gerente']) : null;
        $campanha->tipo_comissao_representante = (!empty($campos['comissao_representante'])) ? parserNumber($campos['tipo_comissao_representante']) : null;
        $campanha->tipo_comissao_vendedor_interno = (!empty($campos['comissao_vendedor_interno'])) ? parserNumber($campos['tipo_comissao_vendedor_interno']) : null;
        $campanha->tipo_comissao_gerente = (!empty($campos['comissao_gerente'])) ? parserNumber($campos['tipo_comissao_gerente']) : null;
        $campanha->ativo = true;
        $campanha->created_by = Auth::user()->id;
        
        $campanha->save();

        if(!empty($campos['data_fim_apuracao_vendedor'][0])){
            foreach($campos['data_inicio_apuracao_vendedor'] as $key_apuracao_vendedor => $apuracao_vendedor){
                $campanha_aputacao_tipo_vendedor = new CampanhasApuracaoComissoe();
                $campanha_aputacao_tipo_vendedor->campanha_id = $campanha->id;
                $campanha_aputacao_tipo_vendedor->campanhas_apuracao_comissoe_tipo_id = 1;
                $campanha_aputacao_tipo_vendedor->inicio_periodo = Carbon::createFromFormat('d/m/Y',$apuracao_vendedor)->setTime(0,0,0)->format('Y-m-d 00:00:00');
                $campanha_aputacao_tipo_vendedor->fim_periodo = Carbon::createFromFormat('d/m/Y',$campos['data_fim_apuracao_vendedor'][$key_apuracao_vendedor])->setTime(0,0,0)->format('Y-m-d 00:00:00');
                $campanha_aputacao_tipo_vendedor->meta_reais = (!(empty($campos['meta_reais_representante'][$key_apuracao_vendedor]))) ? parserNumber($campos['meta_reais_representante'][$key_apuracao_vendedor]) : null;
                $campanha_aputacao_tipo_vendedor->meta_metros = (!(empty($campos['meta_metros_representante'][$key_apuracao_vendedor]))) ? parserNumber($campos['meta_metros_representante'][$key_apuracao_vendedor]) : null;
                $campanha_aputacao_tipo_vendedor->created_by = Auth::user()->id;
                $campanha_aputacao_tipo_vendedor->save();
            }
        }
        
        if(!empty($campos['data_fim_apuracao_gerentes'][0])){
            foreach($campos['data_inicio_apuracao_gerente'] as $key_apuracao_gerente => $apuracao_gerente){
                $campanha_aputacao_tipo_gerente = new CampanhasApuracaoComissoe();
                $campanha_aputacao_tipo_gerente->campanha_id = $campanha->id;
                $campanha_aputacao_tipo_gerente->campanhas_apuracao_comissoe_tipo_id = 2;
                $campanha_aputacao_tipo_gerente->inicio_periodo = Carbon::createFromFormat('d/m/Y',$apuracao_gerente)->setTime(0,0,0)->format('Y-m-d 00:00:00');
                $campanha_aputacao_tipo_gerente->fim_periodo = Carbon::createFromFormat('d/m/Y',$campos['data_fim_apuracao_gerentes'][$key_apuracao_gerente])->setTime(0,0,0)->format('Y-m-d 00:00:00');
                $campanha_aputacao_tipo_gerente->meta_reais = (!(empty($campos['meta_reais_gerente'][$key_apuracao_gerente]))) ? parserNumber($campos['meta_reais_gerente'][$key_apuracao_gerente]) : null;
                $campanha_aputacao_tipo_gerente->meta_metros = (!(empty($campos['meta_metros_gerente'][$key_apuracao_gerente]))) ? parserNumber($campos['meta_metros_gerente'][$key_apuracao_gerente]) : null;
                $campanha_aputacao_tipo_gerente->created_by = Auth::user()->id;
                $campanha_aputacao_tipo_gerente->save();
            }
        }
        $estabelecimentos_array = explode(',',$campos['estabelecimentos_selecionados']);
        
        foreach($estabelecimentos_array as $estabecimentos){
            $campanha_estabelecimento = new CampanhasEstabelecimento();
            $campanha_estabelecimento->campanha_id = $campanha->id;
            $campanha_estabelecimento->estabelecimento_codigo = str_pad($estabecimentos, 2, 0, STR_PAD_LEFT);
            $campanha_estabelecimento->created_by = Auth::user()->id;
            $campanha_estabelecimento->save();
        }
        

        foreach($campos['produto_selecionado'] as $produto){
            $verifica_produto_existente = false;
            $campanha_produto = CampanhasProduto::select()->where('campanha_id',$campanha->id)->where('produto_codigo', $produto)->first();
            if(empty($campanha_produto)){
                $campanha_produto = new CampanhasProduto();
                $campanha_produto->campanha_id = $campanha->id;
                $campanha_produto->produto_codigo = $produto;
                $campanha_produto->updated_by = Auth::user()->id;
                $campanha_produto->created_by = Auth::user()->id;
                $verifica_produto_existente = true;
            }else{
                $campanha_produto->campanha_id = $campanha->id;
                $campanha_produto->produto_codigo = $produto;
                $campanha_produto->updated_by = Auth::user()->id;
            }
     
            $campanha_produto->save();
            //ativa campanha no produto
            $produto_estoque = ProdutosEstoque::where('codigo_produto', $produto)
            ->whereNull('campanha_id')
            ->update(['campanha_id' => $campanha->id]);

        }

        $log = new CampanhasLog();
        $log->campanha_id = $campanha->id;
        $log->campanhas_acoe_id = 1;
        $log->created_by = Auth::user()->id;
        $log->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Campanha Gravada com Sucesso.',
            'error' => [],
            'response' => []
        ],200);
    }

    public function removerProdutoCampanha(Request $request){
        $campos = $request->only(['codigo', 'campanha', 'campanha_id']);

        $produto_estoque = ProdutosEstoque::where('codigo_produto', $campos['codigo'])
            ->update(['campanha_id' => null]);


            $produto_campanha = CampanhasProduto::where('produto_codigo', $campos['codigo'])
                ->where('campanha_id', $campos['campanha_id'])
                ->delete();
        

        if($produto_estoque){
            $log = new CampanhasLog();
            $log->campanha_id = $campos['campanha_id'];
            $log->descricao = 'Desativar produto '.$campos['codigo'] .' da campanha '.$campos['campanha'].'.';
            $log->campanhas_acoe_id = 4;
            $log->created_by = Auth::user()->id;
            $log->save();

            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => []
            ],200);
        }else{
            return response()->json([
                'status' => '',
                'message' => 'error',
                'error' => [],
                'response' => []
            ],200);
        }
    }

    public function modalEditarCampanha(Request $request){
        set_time_limit(500);
        ini_set('memory_limit','3096M');
        $campos = $request->only(['id']);

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $estabelecimentos = returnTodasEmpresasVendas();


        $comissoes_tipos = CampanhasComissoesTipo::get();
        $array_tipo_comissao = [];

        foreach($comissoes_tipos as $comissoes){
            $array_tipo_comissao[$comissoes->id] = $comissoes->tipo_comissao;
        }

        $campanha = Campanha::with([
            'periodoApuracao' => function($query){
                $query->orderBy('id');
            },
            'estabelecimentosCampanha.estabelecimentoNasajon',
            'tipoComissaoVendedorInterno',
            'tipoComissaoRepresentante',
            'tipoComissaoGerente',
            'produtosCampanha' => function ($query) {
                $query->withTrashed()
                    ->with('produtoEspecificacao.segmento', 'produtoEstoque');
            }
        ])->find($id);

        $retorno = [];
        $tipo_periodo_vendedor = 0;
        $tipo_periodo_gerente = 0;

        $campanha->estabelecimentosCampanha->each(function($query) use (&$retorno){
            $retorno['estabelecimento'][] = [
                'estabelecimento_descricao' => $query->estabelecimentoNasajon->descricao,
                'estabelecimento_codigo' => str_replace('0','',$query->estabelecimentoNasajon->codigo)
            ];
        });
      
        $campanha->periodoApuracao->each(function($query) use (&$retorno,&$tipo_periodo_vendedor,&$tipo_periodo_gerente){
            if($query->campanhas_apuracao_comissoe_tipo_id === 1){
                $tipo_periodo_vendedor ++;
            }

            if($query->campanhas_apuracao_comissoe_tipo_id === 2){
                $tipo_periodo_gerente ++;
            }

            $hoje = Carbon::now()->setTime(0,0,0);

            $retorno['apuracao'][] = [
                'tipo' => $query->campanhas_apuracao_comissoe_tipo_id,
                'inicio_periodo' => parserData($query->inicio_periodo),
                'fim_periodo' => parserData($query->fim_periodo),
                'meta_reais' => parserQtd($query->meta_reais),
                'meta_metros' => parserQtd($query->meta_metros),
                'usuario' => (!empty($query->updatedBy->name)) ? $query->updatedBy->name : $query->createdBy->name ,
                'id' => $query->id,
                'fim_apuracao' => (Carbon::parse($query->fim_periodo)->setTime(0,0,0)->lt($hoje)) ? true : false
            ];
        });

        $array_produtos_codigos = $campanha->produtosCampanha->pluck('produto_codigo')->toArray();

        $retorno['nome'] = $campanha->nome;
        $retorno['inicio_campanha'] = parserData($campanha->inicio_campanha);
        $retorno['fim_campanha'] = parserData($campanha->fim_campanha);
        $retorno['comissao_representante'] = (!empty($campanha->comissao_representante)) ? parserValor($campanha->comissao_representante) : '';
        $retorno['comissao_vendedor_interno'] = (!empty($campanha->comissao_vendedor_interno)) ? parserValor($campanha->comissao_vendedor_interno) : '';
        $retorno['comissao_gerente'] = (!empty($campanha->comissao_gerente)) ? parserValor($campanha->comissao_gerente) : '';
        $retorno['tipo_comissao_representante'] = $campanha->tipo_comissao_representante;
        $retorno['tipo_comissao_vendedor_interno'] = $campanha->tipo_comissao_vendedor_interno;
        $retorno['tipo_comissao_gerente'] = $campanha->tipo_comissao_gerente;
        $retorno['id'] = encrypt($campanha->id);

        $contador = 0;
    
        $campanha->produtosCampanha->each(function ($query) use (&$retorno, &$contador, &$campanha) {

            $mensagem_campanha_ativo = "O produto " .  $query->produto_codigo .  " será  ATIVADO na campanha " . $campanha->nome;

            $mensagem_campanha_inativo = "O produto " .  $query->produto_codigo .  " será  DESATIVADO da campanha " . $campanha->nome . ", quaisquer pedidos feitos a partir de agora não será vinculado a nenhuma campanha até que seja vinculado a outra campanha.";


            if (!empty($query->produtoEstoque[0]) && !empty($query->deleted_at)) {

             
                    if (!empty($query->produtoEstoque[0]->campanha_id)) {
                        if ($query->produtoEstoque[0]->campanha_id !=  $campanha->id) {
                            $mensagem_campanha_ativo = $mensagem_campanha_ativo . " , e a partir de agora  será desvinculado da  campanha. " . $query->produtoEstoque[0]->campanha->nome;
                        }
                    
                }
            }

            $retorno['produtos'][] = [
                'codigo' => (!empty($query->produtoEspecificacao->codigo_produto)) ? $query->produtoEspecificacao->codigo_produto :  $query->produto_codigo,
                'descricao' => (!empty($query->produtoEspecificacao->descricao)) ? $query->produtoEspecificacao->descricao : '',
                'grupo' => (!empty($query->produtoEspecificacao->grupo)) ? $query->produtoEspecificacao->grupo : '',
                'subgrupo' => (!empty($query->produtoEspecificacao->subgrupo)) ? $query->produtoEspecificacao->subgrupo : '',
                'marca' => (!empty($query->produtoEspecificacao->marca)) ? $query->produtoEspecificacao->marca : '',
                'linha' => (!empty($query->produtoEspecificacao->linha)) ? $query->produtoEspecificacao->linha : '',
                'estabelecimento_descricao' => '',
                'segmento' => !empty($query->produtoEspecificacao->segmento) ? $query->produtoEspecificacao->segmento->descricao : '',
                'estabelecimento' => '',
                'campanha' => (!empty($campanha->nome)) ? $campanha->nome : '',
                'id_campanha' => (!empty($campanha->id)) ? $campanha->id : '',
                'situacao' => !empty($query->deleted_at) ? 'INATIVO' : 'ATIVO',
                'cor' => !empty($query->deleted_at) ?  'text-danger' : 'text-success',
                'mensagem_campanha_ativo' => $mensagem_campanha_ativo,
                'mensagem_campanha_inativo' => $mensagem_campanha_inativo,

            ];

            $contador++;
        });
        return view('programs.campanhas.modal.editar')->with(['retorno' => $retorno,'estabelecimentos' => $estabelecimentos,'comissoes_tipo' => $array_tipo_comissao,'contador' => $contador,'tipo_periodo_vendedor' => $tipo_periodo_vendedor, 'tipo_periodo_gerente' =>$tipo_periodo_gerente]);
    }

    public function editarCampanha(CampanhaAtualizarRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campos = $request->only([
            'estabelecimentos_selecionados',
            'nome_campanha',
            'data_inicio_campanha',
            'data_fim_campanha',
            'comissao_representante',
            'tipo_comissao_representante',
            'comissao_vendedor_interno',
            'tipo_comissao_vendedor',
            'tipo_comissao_vendedor_interno',
            'comissao_gerente',
            'tipo_comissao_gerente',
            'data_inicio_apuracao_vendedor',
            'data_fim_apuracao_vendedor',
            'meta_reais_representante',
            'meta_metros_representante',
            'data_inicio_apuracao_gerente',
            'data_fim_apuracao_gerentes',
            'meta_reais_gerente',
            'meta_metros_gerente',
            'produto_selecionado',
            'campanha_id',
            'apuracao_vendedores_id',
            'apuracao_gerente_id'
        ]);

        $campos['produto_selecionado'] = $this->tratarArrayProdutos($campos['produto_selecionado']);
        
        try{
            $id = decrypt($campos['campanha_id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $campanha = Campanha::find($id);

        if($campanha->nome != $campos['nome_campanha']){
            $campanha->nome = $campos['nome_campanha'];
            $campanha->updated_by = Auth::user()->id;
            $campanha->save();

            $log = new CampanhasLog();
            $log->campanha_id = $id;
            $log->descricao = $campos['nome_campanha'];
            $log->campanhas_acoe_id = 7;
            $log->created_by = Auth::user()->id;
            $log->save();
        }

        $inicio_campanha = Carbon::createFromFormat('d/m/Y',$campos['data_inicio_campanha'])->setTime(0,0,0);
        $inicio_campanha_retorno = Carbon::parse($campanha->inicio_campanha)->setTime(0,0,0);

        if($inicio_campanha_retorno != $inicio_campanha){
            $campanha->inicio_campanha = $inicio_campanha;
            $campanha->updated_by = Auth::user()->id;
            $campanha->save();

            $log = new CampanhasLog();
            $log->campanha_id = $id;
            $log->descricao = $campos['data_inicio_campanha'];
            $log->campanhas_acoe_id = 8;
            $log->created_by = Auth::user()->id;
            $log->save();
        }

        $fim_campanha = Carbon::createFromFormat('d/m/Y',$campos['data_fim_campanha'])->setTime(23,59,59);
        $fim_campanha_retorno = Carbon::parse($campanha->fim_campanha)->setTime(23,59,59);

        if($fim_campanha_retorno != $fim_campanha){
            $campanha->fim_campanha = $fim_campanha;
            $campanha->updated_by = Auth::user()->id;
            $campanha->save();

            $log = new CampanhasLog();
            $log->campanha_id = $id;
            $log->descricao = $campos['data_fim_campanha'];
            $log->campanhas_acoe_id = 9;
            $log->created_by = Auth::user()->id;
            $log->save();
        }

        if((!empty($campos['comissao_representante']))){
            if($campanha->comissao_representante != parserNumber($campos['comissao_representante'])){
                $campanha->comissao_representante = parserNumber($campos['comissao_representante']);
                $campanha->updated_by = Auth::user()->id;
                $campanha->save();

                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_representante'].' - tipo '.$campos['tipo_comissao_representante'];
                $log->campanhas_acoe_id = 11;
                $log->created_by = Auth::user()->id;
                $log->save();
            }

            if($campanha->tipo_comissao_representante != $campos['tipo_comissao_representante']){
                $campanha->tipo_comissao_representante = $campos['tipo_comissao_representante'];
                $campanha->updated_by = Auth::user()->id;
                $campanha->save();

                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_representante'].' - tipo '.$campos['tipo_comissao_representante'];
                $log->campanhas_acoe_id = 11;
                $log->created_by = Auth::user()->id;
                $log->save();
            }

            if($campanha->comissao_representante != parserNumber($campos['comissao_representante']) || $campanha->tipo_comissao_representante != $campos['tipo_comissao_representante']){
                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_representante'].' - tipo '.$campos['tipo_comissao_representante'];
                $log->campanhas_acoe_id = 11;
                $log->created_by = Auth::user()->id;
                $log->save();
            }
        }

        if((!empty($campos['comissao_vendedor_interno']))){
            if($campanha->comissao_vendedor_interno != parserNumber($campos['comissao_vendedor_interno'])){
                $campanha->comissao_vendedor_interno = parserNumber($campos['comissao_vendedor_interno']);
                $campanha->updated_by = Auth::user()->id;
                $campanha->save();

                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_vendedor_interno'].' - tipo '.$campos['tipo_comissao_vendedor_interno'];
                $log->campanhas_acoe_id = 10;
                $log->created_by = Auth::user()->id;
                $log->save();
            }

            if($campanha->tipo_comissao_vendedor_interno != $campos['tipo_comissao_vendedor_interno']){
                $campanha->tipo_comissao_vendedor_interno = $campos['tipo_comissao_vendedor_interno'];
                $campanha->updated_by = Auth::user()->id;
                $campanha->save();
                
                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_vendedor_interno'].' - tipo '.$campos['tipo_comissao_vendedor_interno'];
                $log->campanhas_acoe_id = 10;
                $log->created_by = Auth::user()->id;
                $log->save();
            }

            if($campanha->comissao_vendedor_interno != parserNumber($campos['comissao_vendedor_interno']) || $campanha->tipo_comissao_vendedor_interno != $campos['tipo_comissao_vendedor_interno']){
                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_vendedor_interno'].' - tipo '.$campos['tipo_comissao_vendedor_interno'];
                $log->campanhas_acoe_id = 10;
                $log->created_by = Auth::user()->id;
                $log->save();
           }
        }

        if((!empty($campos['comissao_gerente']))){
            if($campanha->comissao_gerente != parserNumber($campos['comissao_gerente'])){
                $campanha->comissao_gerente = parserNumber($campos['comissao_gerente']);
                $campanha->updated_by = Auth::user()->id;
                $campanha->save();
                
                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_gerente'].' - tipo '.$campos['tipo_comissao_gerente'];
                $log->campanhas_acoe_id = 12;
                $log->created_by = Auth::user()->id;
                $log->save();
            }

            if($campanha->tipo_comissao_gerente != $campos['tipo_comissao_gerente']){
                $campanha->tipo_comissao_gerente = $campos['tipo_comissao_gerente'];
                $campanha->updated_by = Auth::user()->id;
                $campanha->save();
                
                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_gerente'].' - tipo '.$campos['tipo_comissao_gerente'];
                $log->campanhas_acoe_id = 12;
                $log->created_by = Auth::user()->id;
                $log->save();
            }

            if($campanha->comissao_gerente != parserNumber($campos['comissao_gerente']) || $campanha->tipo_comissao_gerente != $campos['tipo_comissao_gerente']){
                $log = new CampanhasLog();
                $log->campanha_id = $id;
                $log->descricao = $campos['comissao_gerente'].' - tipo '.$campos['tipo_comissao_gerente'];
                $log->campanhas_acoe_id = 12;
                $log->created_by = Auth::user()->id;
                $log->save();
           }
        }

        $estabelecimentos_array = explode(',',$campos['estabelecimentos_selecionados']);
        
        $estabelecimentos = CampanhasEstabelecimento::where('campanha_id',$id);
        $array_codigos_estabelecimentos = [];

        foreach($estabelecimentos_array as $estabelecimento_campo){
            $array_codigos_estabelecimentos[] = str_pad($estabelecimento_campo, 2, 0, STR_PAD_LEFT);
        }

        $array_verifica_estabelecimento = $estabelecimentos->get()->pluck('estabelecimento_codigo')->toArray();

        if(array_diff($array_verifica_estabelecimento,$array_codigos_estabelecimentos) || count($array_verifica_estabelecimento) != count($estabelecimentos_array)){
            $estabelecimentos_excluir = CampanhasEstabelecimento::where('campanha_id',$id)->delete();

            foreach($estabelecimentos_array as $estabecimentos){
                $campanha_estabelecimento = new CampanhasEstabelecimento();
                $campanha_estabelecimento->campanha_id = $campanha->id;
                $campanha_estabelecimento->estabelecimento_codigo = str_pad($estabecimentos, 2, 0, STR_PAD_LEFT);
                $campanha_estabelecimento->updated_by = Auth::user()->id;
                $campanha_estabelecimento->created_by = Auth::user()->id;
                $campanha_estabelecimento->save();
            }

            $log = new CampanhasLog();
            $log->campanha_id = $id;
            $log->descricao = $campos['estabelecimentos_selecionados'];
            $log->campanhas_acoe_id = 10;
            $log->created_by = Auth::user()->id;
            $log->save();
        }

        if(!empty($campos['data_fim_apuracao_vendedor'][0])){
            $verifica_alteracao = false;
            $texto_alteracao = '';

            $verifica_quantidade_periodos_editados = (isset($campos['apuracao_vendedores_id'])) ? count($campos['apuracao_vendedores_id']) : 0; 
            $verifica_quantidade_periodos_registrados = CampanhasApuracaoComissoe::where('campanha_id',$id)->where('campanhas_apuracao_comissoe_tipo_id',1)->get(); 
            
            if($verifica_quantidade_periodos_editados < $verifica_quantidade_periodos_registrados->count()){
                $id_periodos = $verifica_quantidade_periodos_registrados->sort()->pluck('id')->toArray();
                $array_saida = array_diff_assoc($id_periodos, $campos['apuracao_vendedores_id']);

                foreach($array_saida as $saida){
                    $trazer_data_registrada =  CampanhasApuracaoComissoe::find($saida);
                    $delete_periodo = CampanhasApuracaoComissoe::where('id',$saida)->delete();
                    $inicio_data = Carbon::parse($trazer_data_registrada->inicio_periodo)->format('d/m/Y');
                    $fim_data = Carbon::parse($trazer_data_registrada->fim_periodo)->format('d/m/Y');

                    $log = new CampanhasLog();
                    $log->campanha_id = $id;
                    $log->descricao = 'Exclui Período vendedor '.$inicio_data.' - '.$fim_data;
                    $log->campanhas_acoe_id = 5;
                    $log->created_by = Auth::user()->id;
                    $log->save();
                }
            }

            foreach($campos['data_inicio_apuracao_vendedor'] as $key_apuracao_vendedor => $apuracao_vendedor){
                $meta_reais = (!(empty($campos['meta_reais_representante'][$key_apuracao_vendedor]))) ? parserNumber($campos['meta_reais_representante'][$key_apuracao_vendedor]) : null;
                $meta_metros = (!(empty($campos['meta_metros_representante'][$key_apuracao_vendedor]))) ? parserNumber($campos['meta_metros_representante'][$key_apuracao_vendedor]) : null;
                $inicio_apuracao_vendedor = Carbon::createFromFormat('d/m/Y',$apuracao_vendedor)->setTime(0,0,0)->format('Y-m-d 00:00:00.0000');
                $data_fim_apuracao_vendedor = Carbon::createFromFormat('d/m/Y',$campos['data_fim_apuracao_vendedor'][$key_apuracao_vendedor])->setTime(0,0,0)->format('Y-m-d 00:00:00.0000');
                
                $verifica_campanha_aputacao_tipo_vendedor = (isset($campos['apuracao_vendedores_id'][$key_apuracao_vendedor])) ? CampanhasApuracaoComissoe::where('campanha_id',$id)
                ->where('id',$campos['apuracao_vendedores_id'][$key_apuracao_vendedor])
                ->first() : null;

                if(!empty($verifica_campanha_aputacao_tipo_vendedor)){
                    $alterar_campanha_aputacao_tipo_vendedor = CampanhasApuracaoComissoe::where('campanha_id',$id)
                    ->where('inicio_periodo',$inicio_apuracao_vendedor)
                    ->where('fim_periodo',$data_fim_apuracao_vendedor)
                    ->where('meta_metros',$meta_metros)
                    ->where('meta_reais',$meta_reais)
                    ->where('id',$campos['apuracao_vendedores_id'][$key_apuracao_vendedor])
                    ->first();

                    if(empty($alterar_campanha_aputacao_tipo_vendedor)){
                        $verifica_campanha_aputacao_tipo_vendedor->inicio_periodo = $inicio_apuracao_vendedor;
                        $verifica_campanha_aputacao_tipo_vendedor->fim_periodo = $data_fim_apuracao_vendedor;
                        $verifica_campanha_aputacao_tipo_vendedor->meta_metros = $meta_metros;
                        $verifica_campanha_aputacao_tipo_vendedor->meta_reais = $meta_reais;
                        $verifica_campanha_aputacao_tipo_vendedor->inicio_periodo = $inicio_apuracao_vendedor;
                        $verifica_campanha_aputacao_tipo_vendedor->save();    

                        $texto_alteracao = 'Alterou período vendedor para inicio: '.$inicio_apuracao_vendedor.' - fim: '.$data_fim_apuracao_vendedor.' - reais: '.$meta_reais.' - metros: '.$meta_metros.' | ';

                        $log = new CampanhasLog();
                        $log->campanha_id = $id;
                        $log->descricao = $texto_alteracao;
                        $log->campanhas_acoe_id = 14;
                        $log->created_by = Auth::user()->id;
                        $log->save();
                    }
                }else{
                    $campanha_aputacao_tipo_vendedor = new CampanhasApuracaoComissoe();
                    $campanha_aputacao_tipo_vendedor->campanha_id = $id;
                    $campanha_aputacao_tipo_vendedor->campanhas_apuracao_comissoe_tipo_id = 1;
                    $campanha_aputacao_tipo_vendedor->inicio_periodo = Carbon::createFromFormat('d/m/Y',$apuracao_vendedor)->setTime(0,0,0)->format('Y-m-d 00:00:00');
                    $campanha_aputacao_tipo_vendedor->fim_periodo = Carbon::createFromFormat('d/m/Y',$campos['data_fim_apuracao_vendedor'][$key_apuracao_vendedor])->setTime(0,0,0)->format('Y-m-d 00:00:00');
                    $campanha_aputacao_tipo_vendedor->meta_reais = (!(empty($campos['meta_reais_representante'][$key_apuracao_vendedor]))) ? parserNumber($campos['meta_reais_representante'][$key_apuracao_vendedor]) : null;
                    $campanha_aputacao_tipo_vendedor->meta_metros = (!(empty($campos['meta_metros_representante'][$key_apuracao_vendedor]))) ? parserNumber($campos['meta_metros_representante'][$key_apuracao_vendedor]) : null;
                    $campanha_aputacao_tipo_vendedor->updated_by = Auth::user()->id;
                    $campanha_aputacao_tipo_vendedor->created_by = Auth::user()->id;
                    $campanha_aputacao_tipo_vendedor->save();

                    $log = new CampanhasLog();
                    $log->campanha_id = $id;
                    $log->descricao = 'Adicionar Novo Período Vendedor'.$inicio_apuracao_vendedor.' - fim: '.$data_fim_apuracao_vendedor.' - reais: '.$meta_reais.' - metros: '.$meta_metros;
                    $log->campanhas_acoe_id = 14;
                    $log->created_by = Auth::user()->id;
                    $log->save();
                }
            }

        }else{
            $campanha_aputacao_vendedor_excluir = CampanhasApuracaoComissoe::where('campanha_id',$id)->where('campanhas_apuracao_comissoe_tipo_id',1)->delete();
        }

        if(!empty($campos['data_fim_apuracao_gerentes'][0])){
            $verifica_alteracao_gerente = false;
            $texto_alteracao_gerente = '';

            $verifica_quantidade_periodos_editados = (isset($campos['apuracao_gerente_id'])) ? count($campos['apuracao_gerente_id']) : 0; 
            $verifica_quantidade_periodos_registrados = CampanhasApuracaoComissoe::where('campanha_id',$id)->where('campanhas_apuracao_comissoe_tipo_id',2)->get(); 
            
            if($verifica_quantidade_periodos_editados > 0 && $verifica_quantidade_periodos_editados < $verifica_quantidade_periodos_registrados->count()){
                $id_periodos = $verifica_quantidade_periodos_registrados->sort()->pluck('id')->toArray();
                $array_saida = array_diff_assoc($id_periodos, $campos['apuracao_gerente_id']);
                
                foreach($array_saida as $saida){
                    $trazer_data_registrada = CampanhasApuracaoComissoe::find($saida);
                    $delete_periodo = CampanhasApuracaoComissoe::where('id',$saida)->delete();
                    $inicio_data = Carbon::parse($trazer_data_registrada->inicio_periodo)->format('d/m/Y');
                    $fim_data = Carbon::parse($trazer_data_registrada->fim_periodo)->format('d/m/Y');

                    $log = new CampanhasLog();
                    $log->campanha_id = $id;
                    $log->descricao = 'Exclui Período Gerente'.$inicio_data.' - '.$fim_data;
                    $log->campanhas_acoe_id = 5;
                    $log->created_by = Auth::user()->id;
                    $log->save();
                }
            }
            
            foreach($campos['data_inicio_apuracao_gerente'] as $key_apuracao_gerente => $apuracao_gerente){
                $meta_reais = (!(empty($campos['meta_reais_gerente'][$key_apuracao_gerente]))) ? parserNumber($campos['meta_reais_gerente'][$key_apuracao_gerente]) : null;
                $meta_metros = (!(empty($campos['meta_metros_gerente'][$key_apuracao_gerente]))) ? parserNumber($campos['meta_metros_gerente'][$key_apuracao_gerente]) : null;
                $inicio_apuracao_gerente = Carbon::createFromFormat('d/m/Y',$apuracao_gerente)->setTime(0,0,0)->format('Y-m-d 00:00:00');
                $data_fim_apuracao_gerente = Carbon::createFromFormat('d/m/Y',$campos['data_fim_apuracao_gerentes'][$key_apuracao_gerente])->setTime(0,0,0)->format('Y-m-d 00:00:00');

                $verifica_campanha_aputacao_tipo_gerente = (isset($campos['apuracao_gerente_id'][$key_apuracao_gerente])) ? CampanhasApuracaoComissoe::where('campanha_id',$id)
                ->where('id',$campos['apuracao_gerente_id'][$key_apuracao_gerente])
                ->first() : null;

                if(!empty($verifica_campanha_aputacao_tipo_gerente)){
                    $alterar_campanha_aputacao_tipo_vendedor = CampanhasApuracaoComissoe::where('campanha_id',$id)
                    ->where('inicio_periodo',$inicio_apuracao_gerente)
                    ->where('fim_periodo',$data_fim_apuracao_gerente)
                    ->where('meta_metros',$meta_metros)
                    ->where('meta_reais',$meta_reais)
                    ->where('id',$campos['apuracao_gerente_id'][$key_apuracao_gerente])
                    ->first();

                    if(empty($alterar_campanha_aputacao_tipo_vendedor)){
                        $verifica_campanha_aputacao_tipo_gerente->inicio_periodo = $inicio_apuracao_gerente;
                        $verifica_campanha_aputacao_tipo_gerente->fim_periodo = $data_fim_apuracao_gerente;
                        $verifica_campanha_aputacao_tipo_gerente->meta_metros = $meta_metros;
                        $verifica_campanha_aputacao_tipo_gerente->meta_reais = $meta_reais;
                        $verifica_campanha_aputacao_tipo_gerente->inicio_periodo = $inicio_apuracao_gerente;
                        $verifica_campanha_aputacao_tipo_gerente->save();    

                        $texto_alteracao = 'Alterou período gerente para inicio: '.$inicio_apuracao_gerente.' - fim: '.$data_fim_apuracao_gerente.' - reais: '.$meta_reais.' - metros: '.$meta_metros.' | ';

                        $log = new CampanhasLog();
                        $log->campanha_id = $id;
                        $log->descricao = $texto_alteracao;
                        $log->campanhas_acoe_id = 14;
                        $log->created_by = Auth::user()->id;
                        $log->save();
                    }
                }else{
                    $campanha_aputacao_tipo_vendedor = new CampanhasApuracaoComissoe();
                    $campanha_aputacao_tipo_vendedor->campanha_id = $id;
                    $campanha_aputacao_tipo_vendedor->campanhas_apuracao_comissoe_tipo_id = 2;
                    $campanha_aputacao_tipo_vendedor->inicio_periodo = Carbon::createFromFormat('d/m/Y',$apuracao_gerente)->setTime(0,0,0)->format('Y-m-d 00:00:00');
                    $campanha_aputacao_tipo_vendedor->fim_periodo = Carbon::createFromFormat('d/m/Y',$campos['data_fim_apuracao_gerentes'][$key_apuracao_gerente])->setTime(0,0,0)->format('Y-m-d 00:00:00');
                    $campanha_aputacao_tipo_vendedor->meta_reais = (!(empty($campos['meta_reais_gerente'][$key_apuracao_gerente]))) ? parserNumber($campos['meta_reais_gerente'][$key_apuracao_gerente]) : null;
                    $campanha_aputacao_tipo_vendedor->meta_metros = (!(empty($campos['meta_metros_gerente'][$key_apuracao_gerente]))) ? parserNumber($campos['meta_metros_gerente'][$key_apuracao_gerente]) : null;
                    $campanha_aputacao_tipo_vendedor->updated_by = Auth::user()->id;
                    $campanha_aputacao_tipo_vendedor->created_by = Auth::user()->id;
                    $campanha_aputacao_tipo_vendedor->save();

                    $log = new CampanhasLog();
                    $log->campanha_id = $id;
                    $log->descricao = 'Adicionar Novo Período Gerente '.$inicio_apuracao_gerente.' - fim: '.$data_fim_apuracao_gerente.' - reais: '.$meta_reais.' - metros: '.$meta_metros;
                    $log->campanhas_acoe_id = 14;
                    $log->created_by = Auth::user()->id;
                    $log->save();
                }
            }

        }else{
            $campanha_aputacao_vendedor_excluir = CampanhasApuracaoComissoe::where('campanha_id',$id)->where('campanhas_apuracao_comissoe_tipo_id',2)->delete();
        }

        $verifica_produtos = CampanhasProduto::where('campanha_id',$id);

        $verifica_produtos = $verifica_produtos->get()->pluck('produto_codigo')->toArray();


        if(isset($campos['produto_selecionado'])){

            foreach($campos['produto_selecionado'] as $key_produto => $produto){
                $verifica_produto_existente = false;
                $campanha_produto = CampanhasProduto::select()->where('campanha_id',$campanha->id)->where('produto_codigo', $produto)->first();
                if(empty($campanha_produto)){
                    $campanha_produto = new CampanhasProduto();
                    $campanha_produto->campanha_id = $campanha->id;
                    $campanha_produto->produto_codigo = $produto;
                    $campanha_produto->updated_by = Auth::user()->id;
                    $campanha_produto->created_by = Auth::user()->id;
                    $verifica_produto_existente = true;
                }else{
                    $campanha_produto->campanha_id = $campanha->id;
                    $campanha_produto->produto_codigo = $produto;
                    $campanha_produto->updated_by = Auth::user()->id;
                }
          
               $campanha_produto->save();
                
                //ativa campanha no produto

                $produto_estoque = ProdutosEstoque::where('codigo_produto', $produto)
                ->whereNull('campanha_id')
                ->update(['campanha_id' => $campanha->id]);
             
                if($verifica_produto_existente){
                  
                    $log = new CampanhasLog();
                    $log->campanha_id = $id;
                    $log->descricao = 'produto adicionado '.$produto;
                    $log->campanhas_acoe_id = 16;
                    $log->created_by = Auth::user()->id;
                    $log->save();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Campanha Alterada com sucesso.',
            'error' => [],
            'response' => []
        ],200);
    }

    public function desativarCampanha(Request $request){
        $campos = $request->only(['id']);

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        $campanha = Campanha::find($id);
        $campanha->ativo = false;
        $campanha->save();

        $produto_estoque = ProdutosEstoque::where('campanha_id',$id)->update(['campanha_id' => null]);

        if($produto_estoque){
            $log = new CampanhasLog();
            $log->campanha_id = $id;
            $log->descricao = 'Desativar Campanha '.$campanha->nome;
            $log->campanhas_acoe_id = 3;
            $log->created_by = Auth::user()->id;
            $log->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Campanha Desativada com sucesso.',
                'error' => [],
                'response' => []
            ],200);
        }else{
            return response()->json([
                'status' => 'success',
                'message' => 'Campanha desativada sem nenhum produto vinculado.',
                'error' => [],
                'response' => []
            ],200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Erro ao tentar desativar a campanha.',
            'error' => [],
            'response' => []
        ],422);
    }

    public function consultarCampanha(Request $request){
        $campos = $request->only(['id']);

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $estabelecimentos = returnTodasEmpresasVendas();

        $comissoes_tipos = CampanhasComissoesTipo::get();
        $array_tipo_comissao = [];

        foreach($comissoes_tipos as $comissoes){
            $array_tipo_comissao[$comissoes->id] = $comissoes->tipo_comissao;
        }

        $campanha = Campanha::with([
            'periodoApuracao' => function($query){
                $query->orderBy('id');
            },
            'estabelecimentosCampanha.estabelecimentoNasajon',
            'tipoComissaoVendedorInterno',
            'tipoComissaoRepresentante',
            'tipoComissaoGerente',
            'produtosCampanha' => function ($query) {
                $query->withTrashed()
                    ->with('produtoEspecificacao.segmento', 'produtoEstoque');
            }
        ])->find($id);

        $retorno = [];
        $tipo_periodo_vendedor = 0;
        $tipo_periodo_gerente = 0;

        $campanha->estabelecimentosCampanha->each(function($query) use (&$retorno){
            $retorno['estabelecimento'][] = [
                'estabelecimento_descricao' => $query->estabelecimentoNasajon->descricao,
                'estabelecimento_codigo' => str_replace('0','',$query->estabelecimentoNasajon->codigo)
            ];
        });
        
        $campanha->periodoApuracao->each(function($query) use (&$retorno,&$tipo_periodo_vendedor,&$tipo_periodo_gerente){
            if($query->campanhas_apuracao_comissoe_tipo_id === 1){
                $tipo_periodo_vendedor ++;
            }

            if($query->campanhas_apuracao_comissoe_tipo_id === 2){
                $tipo_periodo_gerente ++;
            }
            
            $retorno['apuracao'][] = [
                'tipo' => $query->campanhas_apuracao_comissoe_tipo_id,
                'inicio_periodo' => parserData($query->inicio_periodo),
                'fim_periodo' => parserData($query->fim_periodo),
                'meta_reais' => parserQtd($query->meta_reais),
                'meta_metros' => parserQtd($query->meta_metros),
                'usuario' => (!empty($query->updatedBy->name)) ? $query->updatedBy->name : $query->createdBy->name ,
                'id' => $query->id
            ];
        });

        $array_produtos_codigos = $campanha->produtosCampanha->pluck('produto_codigo')->toArray();

        $retorno['nome'] = $campanha->nome;
        $retorno['inicio_campanha'] = parserData($campanha->inicio_campanha);
        $retorno['fim_campanha'] = parserData($campanha->fim_campanha);
        $retorno['comissao_representante'] = (!empty($campanha->comissao_representante)) ? parserValor($campanha->comissao_representante) : '';
        $retorno['comissao_vendedor_interno'] = (!empty($campanha->comissao_vendedor_interno)) ? parserValor($campanha->comissao_vendedor_interno) : '';
        $retorno['comissao_gerente'] = (!empty($campanha->comissao_gerente)) ? parserValor($campanha->comissao_gerente) : '';
        $retorno['tipo_comissao_representante'] = $campanha->tipo_comissao_representante;
        $retorno['tipo_comissao_vendedor_interno'] = $campanha->tipo_comissao_vendedor_interno;
        $retorno['tipo_comissao_gerente'] = $campanha->tipo_comissao_gerente;
        $retorno['id'] = encrypt($campanha->id);

        $produtos = CampanhasProduto::with(['produtoEstoque.especificacao' => function($query){
            $query->where('ativo',true);
        },'produtoEstoque.estabelecimentosNasajon']);
        $produtos->whereIn('produto_codigo',$array_produtos_codigos);
        $produtos->whereHas('produtoEstoque.especificacao',function($query){
            $query->where('ativo',true);
        })
        ->where('campanha_id',$campanha->id);
        
        $produtos = $produtos->distinct()->get();
        $contador = 0;

        $produtos->each(function($query) use (&$retorno,&$contador){
            foreach($query->produtoEstoque as $produto){
                $retorno['produtos'][] = [
                    'codigo' => $produto->especificacao->codigo_produto,
                    'descricao' => $produto->especificacao->descricao,
                    'grupo' => $produto->especificacao->grupo,
                    'subgrupo' => $produto->especificacao->subgrupo,
                    'marca' => $produto->especificacao->marca,
                    'linha' => $produto->especificacao->linha,
                    'estabelecimento_descricao' => $produto->estabelecimentosNasajon->descricao,
                    'estabelecimento' => $produto->estabelecimento,
                    'segmento' => !empty( $produto->especificacao->segmento) ? $produto->especificacao->segmento->descricao : '',
                ];

                $contador ++;
            }
        });

        return view('programs.campanhas.modal.consultar')->with(['retorno' => $retorno,'estabelecimentos' => $estabelecimentos,'comissoes_tipo' => $array_tipo_comissao,'contador' => $contador,'tipo_periodo_vendedor' => $tipo_periodo_vendedor, 'tipo_periodo_gerente' =>$tipo_periodo_gerente]);
   }

    public function monitorarPedidosComissao(){
        $pedidos = PedidoPortal::has('campanha')
        ->where(function($query){
            $query->whereHas('campanhaComissao',function($query){
                $query->whereNull('nota_id');
            })
            ->orDoesntHave('campanhaComissao');
        })
        ->where('status_pedido',3)
        ->with(['pedidoNasajon' => function($query){
            $query->where(function($query){
                $query->where('grupodeoperacao', '=', 'VENDA')
                ->orWhere(function($query){
                    $query->whereNull('grupodeoperacao')
                    ->where('grupodeoperacao_pedido','ilike','VENDA');
                });
            })
            ->where('situacao_descricao','Faturado');
        },'itens_pedido.campanha','campanhaComissao'])
        ->get();

        $pedidos->each(function($query){
            if(!empty($query->pedidoNasajon)){
                if(!empty($query->campanhaComissao[0])){
                    foreach($query->campanhaComissao as $campanha_comissao){
                        $campanha_comissao->nota_id = $query->pedidoNasajon->notafiscal_id;
                        $campanha_comissao->save();
                    }
                }else{
                    $comissao = new CampanhasComissaoCalculo();
                    $comissao->pedido_id = $query->id;
                    $comissao->nota_id = $query->pedidoNasajon->notafiscal_id;
                    $comissao->pedido_nasajon_id = $query->pedidoNasajon->id;
                    $comissao->created_by = 1;
                    $comissao->save();

                    foreach($query->itens_pedido as $produtos){
                        $produtos_campanha = new CampanhasComissaoCalculoIten();
                        $produtos_campanha->campanha_comissao_calculo_id = $comissao->id;
                        $produtos_campanha->campanha_id = (!empty($produtos->campanha_id)) ? $produtos->campanha_id : null;
                        $produtos_campanha->produto_codigo = (!empty($produtos->cod_produto)) ? $produtos->cod_produto : null;
                        $produtos_campanha->comissao_padrao = (!empty($produtos->comissao)) ? $produtos->comissao : null;
                        $produtos_campanha->total_percentual = (!empty($produtos->comissao_campanha)) ? $produtos->comissao_campanha : null;
                        $produtos_campanha->incetivo_percentual_comissao = (!empty($produtos->incentivo_campanha)) ? $produtos->incentivo_campanha : null;
                        $produtos_campanha->tipo_comissao_campanha = (!empty($produtos->tipo_comissao_campanha)) ? $produtos->tipo_comissao_campanha : null;
                        $produtos_campanha->created_by = 1;
                        $produtos_campanha->save();
                    }
                }
            }
        });
    }

    public function monitorarPedidosDesativar(){
        $hoje = Carbon::now();

        $campanha = Campanha::where('ativo',true)
        ->where(DB::raw("fim_campanha + interval '1 day'"),'<',$hoje)
        ->get();
        
        $campanha->each(function($query){
            $produto_estoque = ProdutosEstoque::where('campanha_id',$query->id)->update(['campanha_id' => null]);

            $log = new CampanhasLog();
            $log->campanha_id = $query->id;
            $log->descricao = 'Desativar Campanha automático: '.$query->nome;
            $log->campanhas_acoe_id = 3;
            $log->created_by = 1;
            $log->save();

            $query->ativo = false;
            $query->save();
        });
    }

    private function tratarArrayProdutos($array){
        $retorno = [];
        $array = explode(',',$array);

        foreach($array as $produtos){
            if(isset(explode('|',$produtos)[0])){
                $retorno[] = explode('|',$produtos)[0];
            }
        }

        $retorno = array_unique($retorno);
        return $retorno;
    }

    public function controleComissaoGerente(){
        $campanhasComissaoCalculoObj = CampanhasComissaoCalculo::select()->with(['pedidoPortal.itens_pedido'])->whereNull('porcetagem_comissao_gerente')->get();

        foreach($campanhasComissaoCalculoObj as $campanha){
            $comissao_valor = 0;
            foreach($campanha->pedidoPortal->itens_pedido as $item){
                if(empty($item->campanha)){
                    $comissao_valor += $item->valor_total * 0.11 / 100;
                }else{
                    if($item->campanha->tipo_comissao_gerente == 1){
                        $comissao_final = $item->campanha->comissao_gerente + 0.11;
                    }else{
                        $comissao_final = $item->campanha->comissao_gerente;
                    }
                    $comissao_valor += $item->valor_total * $comissao_final / 100;
                }
            }

            $campanha->porcetagem_comissao_gerente = $comissao_valor / $campanha->pedidoPortal->valor_total_nota * 100;
            $campanha->save();
        }
    }

    public function campanhaMapa(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\CampanhaMapa") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\CampanhaMapa');
        
        return view('programs.campanhas.mapas.campanha');
    }

    public function campanhaMapaGerente(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\CampanhaMapaGerentes") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\CampanhaMapaGerentes');
        
        return view('programs.campanhas.mapas.gerente');
    }

    public function campanhaMapaDiretoria(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\CampanhaMapaDiretoria") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\CampanhaMapaDiretoria');
        
        return view('programs.campanhas.mapas.diretoria');
    }

    public function autoCompleteCampanhaNome(Request $request){
        $campos = $request->only(['nome_campanha']);

        $return = [];

        $query = Campanha::select('id', 'nome')
            ->limit("15")
            ->orderBy('nome', "ASC")
            ->where('nome','ilike','%'.$campos['nome_campanha'].'%')
            ->distinct('nome')
            ->get()
            ->toArray();

        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'label' => $value['nome'],
                'value' => $value['id']
            ];
        }
        return response()->json($return);
    }

    public function listarPeriodos(Request $request){
        $campos = $request->only(['nome_campanha']);

        $return = [];

        $query = Campanha::select('id', 'nome')
        ->orderBy('nome', "ASC")
        ->where('nome','ilike',$campos['nome_campanha'])
        ->with(['periodoApuracao' => function($query){
            $query->with('tipoApuracao');
            
            $hoje = Carbon::now()->setTime(0,0,0);

            $query->where('campanhas_apuracao_comissoe_tipo_id',1)
            ->where('inicio_periodo','<=',$hoje);
        }])
        ->first();

        foreach ($query->periodoApuracao as $value){
            $return[] = [
                'descricao' => Carbon::parse($value->inicio_periodo)->format('d/m/Y').' à '.Carbon::parse($value->fim_periodo)->format('d/m/Y').' Período '.$value->tipoApuracao->tipo,
                'id' => $value->id
            ];
        }

        return response()->json($return);
    }

    public function cargaConsultaCampanhaMeta(){
        set_time_limit(12000);
        ini_set('memory_limit','2048M');

        $campanha = Campanha::where('ativo',true)
        ->with(['periodoApuracao' => function($query){
            $query->where('campanhas_apuracao_comissoe_tipo_id',1);
        }])->get();

        $campanha->each(function($query){

            foreach($query->periodoApuracao as $periodos){
                $data_inicio_periodo = Carbon::parse($periodos->inicio_periodo);
                $data_fim_periodo = Carbon::parse($periodos->fim_periodo);
                
                $campanha = Campanha::where('id',$query->id)
                ->with(['associacaoPedido.pedido.campanhaComissao.pedidoNasajon' => function($query) use ($data_inicio_periodo,$data_fim_periodo){
                    $query->whereBetween('emissao',[$data_inicio_periodo,$data_fim_periodo]);
                    $query->where('situacao_descricao','Faturado');
                    $query->with(['pedido_portal.itens_pedido.especificacoes','pedido_portal.itens_pedido.pedido_portal.pedidoNasajon.nota.itens_nota','pedido_portal.itens_pedido.pedido_portal.pedidoNasajon.itens_pedido']);
                },'associacaoPedido.pedido.campanhaComissao.notaDevolucao.itens_faturamento' => function($query){
                    $query->distinct();
                }])
                ->first();
                
                $retorno = [];

                if(!empty($campanha->logo_url)){
                    $retorno['logo'] = "<img src='" . Storage::url($this->path . $campanha->logo_url) . "' width='250' height='340' />";
                }else{
                    $retorno['logo'] = '';
                }

                $retorno['metros'] = 0;
                $retorno['metros_medida'] = 0;
                $retorno['unidade'] = 0;
                $retorno['kilo'] = 0;
                $retorno['valor'] = 0;
                $retorno['periodo'] = '';
                $retorno['vendedores'] = [];
                $retorno['produtos'] = [];

                $inicio = $data_inicio_periodo->format('d/m/Y');
                $fim = $data_fim_periodo->format('d/m/Y');
            
                $retorno['periodo'] = 'Período: '.$inicio.' até '.$fim;
            

                $metros_medida = ['ML','METRO','mt','M','M2','MTS','m','M ','MIL','ML1'];
                $unidade = ['UND','UNID','UN','UNI','UN.','pct','PÇ','CX50','PCTE','RL','GALAO','PT'];
                $kilo = ['Kg','KG'];

                $campanha->associacaoPedido->each(function($query) use (&$retorno,$metros_medida,$unidade,$kilo,$data_inicio_periodo,$data_fim_periodo){
                    $id = $query->campanha_id;
                    if(!empty($query->pedido->campanhaComissao[0])){
                        
                        foreach($query->pedido->campanhaComissao as $campanha_comissao){
                            $devolucao_valor = (!empty($campanha_comissao->valor_nota_devolucao)) ? $campanha_comissao->valor_nota_devolucao : 0;
                            $devolucao_quantidade = (!empty($campanha_comissao->quantidade_nota_devolucao)) ? $campanha_comissao->quantidade_nota_devolucao : 0;
                            $retorno_devolucao_valor = 0;
                            $retorno_devolucao_quantidade = 0;

                            $valor = 0;
                            $metros = 0;
                            $metros_medida_quantidade = 0;
                            $kilo_quantidade = 0;
                            $unidade_quantidade = 0;

                            if(isset($campanha_comissao->pedidoNasajon->situacao_descricao) && !empty($campanha_comissao->pedidoNasajon->situacao_descricao)){
                                if($campanha_comissao->pedidoNasajon->situacao_descricao === 'Faturado'){
                                    if(mb_strpos($campanha_comissao->pedidoNasajon->vendedor_nome, 'DESLIGADO') !== false){
                                        continue;
                                    }
                                    
                                    foreach($campanha_comissao->pedidoNasajon->pedido_portal->itens_pedido as $itens_pedido){
                                        if($itens_pedido->campanha_id == $id){
                                            $metros_produto = 0;
                                            $unidade_produto = 0;
                                            $kilo_produto = 0;
                                            $valor_produto = 0;
                                            $quantidade_devolucao_produto = 0;
                                            $valor_devolucao_produto = 0;

                                            if($devolucao_quantidade > 0){
                                                $quantidade_devolucao_produto = (isset($campanha_comissao->notaDevolucao->itens_faturamento)) ? $campanha_comissao->notaDevolucao->itens_faturamento->where("Item - Código",$itens_pedido->cod_produto)->sum("Item - Quantidade") : 0;
                                                $valor_devolucao_produto = (isset($campanha_comissao->notaDevolucao->itens_faturamento)) ? $campanha_comissao->notaDevolucao->itens_faturamento->where("Item - Código",$itens_pedido->cod_produto)->sum("Item - Valor Total") : 0;
                                            }

                                            if(in_array($itens_pedido->especificacoes->unidade,$metros_medida)){
                                                $metros_medida_quantidade += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                                $metros_produto = (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                            }else if(in_array($itens_pedido->especificacoes->unidade,$unidade)){
                                                $unidade_quantidade += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                                $unidade_produto = (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                            }else if(in_array($itens_pedido->especificacoes->unidade,$kilo)){
                                                $kilo_quantidade += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                                $kilo_produto = (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                            }else{
                                                $metros_medida_quantidade += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                                $metros_produto = (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                            }

                                            $metros += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('quantidadecomercial') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('quantidade_faturada');
                                            
                                            if(strpos($itens_pedido->pedido_portal->tipo_venda, "pre_pago") !== false){
                                                $valor += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') * 2 : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('valortotal') * 2;
                                                $valor_produto = (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') * 2 : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('valortotal') * 2;
                                            }else{
                                                $valor += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('valortotal');
                                                $valor_produto = (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('valortotal');
                                            }   
                                            
                                            if(!isset($retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto])){
                                               $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto] = [
                                                    'metros_medida' => $metros_produto,
                                                    'unidade' => $unidade_produto,
                                                    'kilo' => $kilo_produto,
                                                    'devolucao_metragem' => (float)$quantidade_devolucao_produto,
                                                    'devolucao_valor' => (float)$valor_devolucao_produto,
                                                    'code' => $campanha_comissao->pedidoNasajon->vendedor_codigo,
                                                    'pedido_portal' => $campanha_comissao->pedidoNasajon->pedido_portal->id,
                                                    'valor' => $valor_produto,
                                                    'produto'
                                                ];
                                            }else{
                                                $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto]['valor'] += $valor_produto;
                                                $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto]['metros_medida'] += $metros_produto;
                                                $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto]['unidade'] += $unidade_produto;
                                                $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto]['kilo'] += $kilo_produto;
                                                $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto]['devolucao_metragem'] += (float)$quantidade_devolucao_produto;
                                                $retorno['produtos'][$campanha_comissao->pedidoNasajon->vendedor_codigo][$campanha_comissao->pedidoNasajon->pedido_portal->id][$itens_pedido->cod_produto]['devolucao_valor'] += (float)$valor_devolucao_produto;
                                            }
                                            
                                            $retorno_devolucao_quantidade += (float)$quantidade_devolucao_produto;
                                        }

                                    }
                                    
                                    if(!isset($retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome])){
                                        $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome] = [
                                            'valor' => $valor,
                                            'metros_medida' => ($metros_medida_quantidade > 0) ? $metros_medida_quantidade - $retorno_devolucao_quantidade : 0,
                                            'unidade' => ($unidade_quantidade > 0) ? $unidade_quantidade - $retorno_devolucao_quantidade : 0,
                                            'kilo' => ($kilo_quantidade > 0) ? $kilo_quantidade - $retorno_devolucao_quantidade : 0,
                                            'devolucao' => (float)$devolucao_valor,
                                            'code' => $campanha_comissao->pedidoNasajon->vendedor_codigo
                                        ];

                                    }else{
                                        $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['valor'] += $valor;
                                        $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['metros_medida'] += ($metros_medida_quantidade > 0) ? $metros_medida_quantidade - $retorno_devolucao_quantidade : 0;
                                        $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['unidade'] += ($unidade_quantidade > 0) ? $unidade_quantidade - $retorno_devolucao_quantidade : 0;
                                        $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['kilo'] += ($kilo_quantidade > 0) ? $kilo_quantidade - $retorno_devolucao_quantidade : 0;
                                        $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['devolucao'] += (float)$devolucao_valor;
                                    }

                                }
                            }

                            if($valor - $devolucao_valor <= 0){
                                continue;
                            }

                            $retorno['metros_medida'] += ($metros_medida_quantidade > 0) ? $metros_medida_quantidade - $devolucao_quantidade : 0;
                            $retorno['unidade'] += ($unidade_quantidade > 0) ? $unidade_quantidade - $devolucao_quantidade : 0;
                            $retorno['kilo'] += ($kilo_quantidade > 0) ? $kilo_quantidade - $devolucao_quantidade : 0;
                            $retorno['metros'] += ($metros > 0) ? $metros - $devolucao_quantidade : 0;
                            $retorno['valor'] += ($valor > 0) ? $valor - $devolucao_valor : 0;
                        }
                    }
                });

                $vendedores = collect();

                foreach($retorno['vendedores'] as $key => $retorno_vendedores){
                    if($retorno_vendedores['valor'] - $retorno_vendedores['devolucao'] <= 0){
                        continue;
                    }
                    
                    $vendedores->push([
                        'vendedor_codigo' => $key,
                        'bruto' => $retorno_vendedores['valor'],
                        'devolucao' => $retorno_vendedores['devolucao'],
                        'liquido' => ($retorno_vendedores['valor'] > 0)  ? $retorno_vendedores['valor'] - $retorno_vendedores['devolucao'] : 0,
                        'POS' => 0,
                        'code' => $retorno_vendedores['code'],
                        'metros_medida' => $retorno_vendedores['metros_medida'],
                        'unidade' => $retorno_vendedores['unidade'],
                        'kilo' => $retorno_vendedores['kilo'],
                        'produtos' => (isset($retorno['produtos'][$retorno_vendedores['code']])) ? $retorno['produtos'][$retorno_vendedores['code']] : null
                    ]);
                }

                $vendedores = $vendedores->sortByDesc('liquido')->toArray();
                $ordem = 1;
                
                foreach($vendedores as $key => $vendedor){
                    if($ordem <= 10){
                        $vendedores[$key]['POS'] = '<span class="campanha-small-boll-green"></span>'.$ordem;
                    }else{
                        $vendedores[$key]['POS'] = '<span class="campanha-small-boll-blue"></span>'.$ordem;
                    }

                    $vendedores[$key]['bruto'] = $vendedores[$key]['bruto'];
                    $vendedores[$key]['devolucao'] = $vendedores[$key]['devolucao'];
                    $vendedores[$key]['liquido'] = $vendedores[$key]['liquido'];
                    $vendedores[$key]['metros_medida'] = $vendedores[$key]['metros_medida'];
                    $vendedores[$key]['unidade'] = $vendedores[$key]['unidade'];
                    $vendedores[$key]['kilo'] = $vendedores[$key]['kilo'];
                    
                    $ordem ++;
                }   
                
                $retorno['vendedores'] = $vendedores;
                
                $retorno['metros'] = ($retorno['metros'] > 0) ? parserValor($retorno['metros']) : 'N/D';
                $retorno['valor'] = ($retorno['valor'] > 0) ? parserValor($retorno['valor']) : 'N/D';
                $retorno['metros_medida'] = ($retorno['metros_medida'] > 0) ? parserValor($retorno['metros_medida']) : 'N/D';
                $retorno['unidade'] = ($retorno['unidade'] > 0) ? parserValor($retorno['unidade']) : 'N/D';
                $retorno['kilo'] =  ($retorno['kilo'] > 0) ? parserValor($retorno['kilo']) : 'N/D';

                $verifica_periodo = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
                ->where('campanhas_apuracao_comissoe_id',$periodos->id)
                ->exists();

                if(!$verifica_periodo){
                    $periodo_campanha_consulta = new CampanhasConsultaMetaVendedoresPeriodo;
                    $periodo_campanha_consulta->campanha_id = $campanha->id;
                    $periodo_campanha_consulta->campanhas_apuracao_comissoe_id = $periodos->id;
                    $periodo_campanha_consulta->created_by = 1;
                    $periodo_campanha_consulta->save();

                    $consulta_campanha = new CampanhasConsultaMetaVendedore;
                    $consulta_campanha->metros = $retorno['metros'];
                    $consulta_campanha->valor = $retorno['valor'];
                    $consulta_campanha->kilo = $retorno['kilo'];
                    $consulta_campanha->logo = $retorno['logo'];
                    $consulta_campanha->periodo = $retorno['periodo'];
                    $consulta_campanha->unidade = $retorno['unidade'];
                    $consulta_campanha->metros_medida = $retorno['metros_medida'];
                    $consulta_campanha->campanhas_consulta_meta_vendedores_periodo_id = $periodo_campanha_consulta->id;
                    $consulta_campanha->created_by = 1;
                    $consulta_campanha->save();

                    if(isset($retorno['vendedores'][0]) && !empty($retorno['vendedores'][0])){
                        foreach($retorno['vendedores'] as $vendedor){
                            $consulta_campanha_vendedores = new CampanhasConsultaMetaVendedoresProduto;
                            $consulta_campanha_vendedores->vendedor_codigo = $vendedor['code'];
                            $consulta_campanha_vendedores->vendedor_descricao = $vendedor['vendedor_codigo'];
                            $consulta_campanha_vendedores->bruto = $vendedor['bruto'];
                            $consulta_campanha_vendedores->devolucao = $vendedor['devolucao'];
                            $consulta_campanha_vendedores->liquido = $vendedor['liquido'];
                            $consulta_campanha_vendedores->kilo = $vendedor['kilo'];
                            $consulta_campanha_vendedores->metros = $vendedor['metros_medida'];
                            $consulta_campanha_vendedores->unidade = $vendedor['unidade'];
                            $consulta_campanha_vendedores->POS = $vendedor['POS'];
                            $consulta_campanha_vendedores->campanhas_consulta_meta_vendedor_id = $consulta_campanha->id;
                            $consulta_campanha_vendedores->created_by = 1;
                            $consulta_campanha_vendedores->save();

                            foreach($vendedor['produtos'] as $pedidos_produtos){
                                foreach($pedidos_produtos as $key_produtos => $produtos){
                                    $produtos_contagem = new CampanhasConsultaMetaVendedoresContagemProduto;
                                    $produtos_contagem->campanhas_consulta_meta_vendedores_produto_id = $produtos['metros_medida'];
                                    $produtos_contagem->produto_codigo = $key_produtos;
                                    $produtos_contagem->metros = $produtos['metros_medida'];
                                    $produtos_contagem->unidade = $produtos['unidade'];
                                    $produtos_contagem->kilo = $produtos['kilo'];
                                    $produtos_contagem->pedido_id = $produtos['pedido_portal'];
                                    $produtos_contagem->devolucao_metragem = $produtos['devolucao_metragem'];
                                    $produtos_contagem->devolucao_valor = $produtos['devolucao_valor'];
                                    $produtos_contagem->valor = $produtos['valor'];
                                    $produtos_contagem->created_by = 1;
                                    $produtos_contagem->save();
                                }
                            }
                        }
                    }
                    
                }else{
                    $periodo = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
                    ->where('campanhas_apuracao_comissoe_id',$periodos->id)
                    ->first();

                    $consulta_campanha = CampanhasConsultaMetaVendedore::where('campanhas_consulta_meta_vendedores_periodo_id',$periodo->id)->first();

                    $consulta_campanha->metros = $retorno['metros'];
                    $consulta_campanha->valor = $retorno['valor'];
                    $consulta_campanha->kilo = $retorno['kilo'];
                    $consulta_campanha->logo = $retorno['logo'];
                    $consulta_campanha->unidade = $retorno['unidade'];
                    $consulta_campanha->metros_medida = $retorno['metros_medida'];
                    $consulta_campanha->periodo = $retorno['periodo'];
                    $consulta_campanha->updated_by = 1;
                    $consulta_campanha->save();

                    if(isset($retorno['vendedores'][0]) && !empty($retorno['vendedores'][0])){
                        foreach($retorno['vendedores'] as $vendedor){
                            $consulta_campanha_vendedores = CampanhasConsultaMetaVendedoresProduto::where('campanhas_consulta_meta_vendedor_id',$consulta_campanha->id)
                            ->where('vendedor_codigo',$vendedor['code'])
                            ->with(['produtosContagem'])
                            ->first();
                            
                            if(!empty($consulta_campanha_vendedores) && $vendedor['liquido'] > 0){
                                $consulta_campanha_vendedores->vendedor_codigo = $vendedor['code'];
                                $consulta_campanha_vendedores->vendedor_descricao = $vendedor['vendedor_codigo'];
                                $consulta_campanha_vendedores->bruto = $vendedor['bruto'];
                                $consulta_campanha_vendedores->devolucao = $vendedor['devolucao'];
                                $consulta_campanha_vendedores->liquido = $vendedor['liquido'];
                                $consulta_campanha_vendedores->kilo = $vendedor['kilo'];
                                $consulta_campanha_vendedores->metros = $vendedor['metros_medida'];
                                $consulta_campanha_vendedores->unidade = $vendedor['unidade'];
                                $consulta_campanha_vendedores->POS = $vendedor['POS'];
                                $consulta_campanha_vendedores->updated_by = 1;
                                $consulta_campanha_vendedores->save();

                                if(!empty($consulta_campanha_vendedores->produtosContagem[0])){
                                    foreach($vendedor['produtos'] as $pedidos_produtos){
                                        foreach($pedidos_produtos as $key_produtos => $produtos){
                                            $produtos_contagem = CampanhasConsultaMetaVendedoresContagemProduto::where('campanhas_consulta_meta_vendedores_produto_id',$consulta_campanha_vendedores->id)
                                            ->where('produto_codigo',$key_produtos)
                                            ->where('pedido_id',$produtos['pedido_portal'])
                                            ->first();

                                            if(!empty($produtos_contagem)){
                                                $produtos_contagem->campanhas_consulta_meta_vendedores_produto_id = $consulta_campanha_vendedores->id;
                                                $produtos_contagem->produto_codigo = $key_produtos;
                                                $produtos_contagem->metros = $produtos['metros_medida'];
                                                $produtos_contagem->unidade = $produtos['unidade'];
                                                $produtos_contagem->kilo = $produtos['kilo'];
                                                $produtos_contagem->devolucao_metragem = $produtos['devolucao_metragem'];
                                                $produtos_contagem->devolucao_valor = $produtos['devolucao_valor'];
                                                $produtos_contagem->valor = $produtos['valor'];
                                                $produtos_contagem->pedido_id = $produtos['pedido_portal'];
                                                $produtos_contagem->updated_by = 1;
                                                $produtos_contagem->save();
                                            }else{
                                                $produtos_contagem = new CampanhasConsultaMetaVendedoresContagemProduto;
                                                $produtos_contagem->campanhas_consulta_meta_vendedores_produto_id = $consulta_campanha_vendedores->id;
                                                $produtos_contagem->produto_codigo = $key_produtos;
                                                $produtos_contagem->metros = $produtos['metros_medida'];
                                                $produtos_contagem->unidade = $produtos['unidade'];
                                                $produtos_contagem->kilo = $produtos['kilo'];
                                                $produtos_contagem->devolucao_metragem = $produtos['devolucao_metragem'];
                                                $produtos_contagem->devolucao_valor = $produtos['devolucao_valor'];
                                                $produtos_contagem->valor = $produtos['valor'];
                                                $produtos_contagem->pedido_id = $produtos['pedido_portal'];
                                                $produtos_contagem->created_by = 1;
                                                $produtos_contagem->save();
                                            }
                                        }
                                    }
                                }else{
                                    foreach($vendedor['produtos'] as $pedidos_produtos){
                                        foreach($pedidos_produtos as $key_produtos => $produtos){
                                            $produtos_contagem = new CampanhasConsultaMetaVendedoresContagemProduto;
                                            $produtos_contagem->campanhas_consulta_meta_vendedores_produto_id = $consulta_campanha_vendedores->id;
                                            $produtos_contagem->produto_codigo = $key_produtos;
                                            $produtos_contagem->metros = $produtos['metros_medida'];
                                            $produtos_contagem->unidade = $produtos['unidade'];
                                            $produtos_contagem->kilo = $produtos['kilo'];
                                            $produtos_contagem->devolucao_metragem = $produtos['devolucao_metragem'];
                                            $produtos_contagem->devolucao_valor = $produtos['devolucao_valor'];
                                            $produtos_contagem->valor = $produtos['valor'];
                                            $produtos_contagem->pedido_id = $produtos['pedido_portal'];
                                            $produtos_contagem->created_by = 1;
                                            $produtos_contagem->save();
                                        }
                                    }
                                }

                            }else if(!empty($consulta_campanha_vendedores) && $vendedor['liquido'] <= 0){
                                $consulta_campanha_vendedores->delete();
                            }else if(empty($consulta_campanha_vendedores)){
                                $consulta_campanha_vendedores = new CampanhasConsultaMetaVendedoresProduto;
                                $consulta_campanha_vendedores->vendedor_codigo = $vendedor['code'];
                                $consulta_campanha_vendedores->vendedor_descricao = $vendedor['vendedor_codigo'];
                                $consulta_campanha_vendedores->bruto = $vendedor['bruto'];
                                $consulta_campanha_vendedores->devolucao = $vendedor['devolucao'];
                                $consulta_campanha_vendedores->liquido = $vendedor['liquido'];
                                $consulta_campanha_vendedores->kilo = $vendedor['kilo'];
                                $consulta_campanha_vendedores->metros = $vendedor['metros_medida'];
                                $consulta_campanha_vendedores->unidade = $vendedor['unidade'];
                                $consulta_campanha_vendedores->POS = $vendedor['POS'];
                                $consulta_campanha_vendedores->campanhas_consulta_meta_vendedor_id = $consulta_campanha->id;
                                $consulta_campanha_vendedores->created_by = 1;
                                $consulta_campanha_vendedores->save();

                                foreach($vendedor['produtos'] as $pedidos_produtos){
                                    foreach($pedidos_produtos as $key_produtos => $produtos){
                                        $produtos_contagem = new CampanhasConsultaMetaVendedoresContagemProduto;
                                        $produtos_contagem->campanhas_consulta_meta_vendedores_produto_id = $consulta_campanha_vendedores->id;
                                        $produtos_contagem->produto_codigo = $key_produtos;
                                        $produtos_contagem->metros = $produtos['metros_medida'];
                                        $produtos_contagem->unidade = $produtos['unidade'];
                                        $produtos_contagem->kilo = $produtos['kilo'];
                                        $produtos_contagem->devolucao_metragem = $produtos['devolucao_metragem'];
                                        $produtos_contagem->devolucao_valor = $produtos['devolucao_valor'];
                                        $produtos_contagem->valor = $produtos['valor'];
                                        $produtos_contagem->pedido_id = $produtos['pedido_portal'];
                                        $produtos_contagem->created_by = 1;
                                        $produtos_contagem->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $query->ultima_alteracao = Carbon::now();
            $query->save();
        });
    }

    public function filtroCampanhaMeta(Request $request){
        set_time_limit(900);
        ini_set('memory_limit','1024M');

        $campos = $request->only(['nome_campanha','periodo']);
        
        if(empty($campos['nome_campanha'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        /**
         * verifica tipo de usuário logado para trazer apenas o resultado pessoal oud a equipe
         */
        $representantes = [];

        if(in_array(Auth::id(),[11194, 10046])){
            if(Auth::id() == 11194){
                $codigo_representantes = $this->subordinado_supervisor_playstation_11194;
            }else if(Auth::id() == 10046){
                $codigo_representantes = $this->subordinado_supervisor_playstation_10046;
            }
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->whereIn('codigo_representante',  $codigo_representantes)
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[] = Auth::user()->codigo_representante;

            $representantes_busca->each(function($representante) use(&$representantes){
                $representantes[] = $representante->codigo_representante;
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[] = $value['codigo_representante'];
            }
        }else if(in_array(Auth::user()->tipo_usuario_id,[13])){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->where('codigo_representante', '!=', '')
                ->where('codigo_representante', '!=', '998')
                ->where('responsavel', Auth::user()->supervisor->id)
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[] = Auth::user()->codigo_representante;

            $representantes_busca->each(function($representante) use (&$representantes){
                $representantes[] = $representante->codigo_representante;
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[] = $value['codigo_representante'];
            }
        }else if(in_array(Auth::user()->tipo_usuario_id,[19, 14])){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->where('codigo_representante', '!=', '')
                ->where('codigo_representante', '!=', '998')
                ->where('responsavel', Auth::id())
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[] = Auth::user()->codigo_representante;

            $representantes_busca->each(function($representante) use(&$representantes){
                $representantes[] = $representante->codigo_representante;
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[] = $value['codigo_representante'];
            }
        }
        
        $campanha = Campanha::where('nome','ilike',$campos['nome_campanha'])->first();

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        if(empty($campos['periodo'])){
            $campanha_data = Campanha::where('nome',$campos['nome_campanha'])
            ->select('id')
            ->first();

            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha_data->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',1)
            ->where('fim_periodo','>=',$hoje)
            ->orderBy('id')
            ->first();

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);

        }


        $retorno = [];
        $vendedores = collect();

        $carga_consulta = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
        ->where('campanhas_apuracao_comissoe_id',$periodo->id)
        ->with(['campanhasConsultaMetaVendedores' => function($query){
            $query->orderBy('id','desc');
        },'campanhasConsultaMetaVendedores.periodoMetaProduto'])
        ->first();
        
        if(empty($carga_consulta->campanhasConsultaMetaVendedores)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $retorno['metros'] = $carga_consulta->campanhasConsultaMetaVendedores->metros;
        $retorno['metros_medida'] = $carga_consulta->campanhasConsultaMetaVendedores->metros_medida;
        $retorno['unidade'] = $carga_consulta->campanhasConsultaMetaVendedores->unidade;
        $retorno['kilo'] = $carga_consulta->campanhasConsultaMetaVendedores->kilo;
        $retorno['valor'] = $carga_consulta->campanhasConsultaMetaVendedores->valor;
        $retorno['periodo'] = $carga_consulta->campanhasConsultaMetaVendedores->periodo;
        $retorno['vendedores'] = [];

        if(!empty($carga_consulta->campanhasConsultaMetaVendedores->logo)){
            $retorno['logo'] = $carga_consulta->campanhasConsultaMetaVendedores->logo;
        }else{
            $retorno['logo'] = '';
        }

        foreach($carga_consulta->campanhasConsultaMetaVendedores->periodoMetaProduto as $key => $retorno_vendedores){
            if($retorno_vendedores->liquido <=0){
                continue;
            }

            $vendedores->push([
                'vendedor_codigo' => $retorno_vendedores->vendedor_descricao,
                'bruto' => $retorno_vendedores->bruto,
                'devolucao' => $retorno_vendedores->devolucao,
                'liquido' => $retorno_vendedores->liquido,
                'POS' => $retorno_vendedores["POS"],
                'code' => $retorno_vendedores->vendedor_codigo,
                'campos' => encrypt($campos),
            ]);
        }

        $vendedores = $vendedores->sortByDesc('liquido')->toArray();
        $ordem = 1;

        foreach($vendedores as $key => $vendedor){
            
            if(in_array(Auth::user()->tipo_usuario_id, [12, 16, 22])){
                if($vendedores[$key]['code'] == Auth::user()->codigo_representante){
                    $vendedores[$key]['bruto'] = ($vendedores[$key]['bruto'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['bruto']) . "'>" .parserValor($vendedores[$key]['bruto']) . "</a>" : "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = ($vendedores[$key]['devolucao'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['devolucao']) . "'>" .parserValor($vendedores[$key]['devolucao']) . "</a>" : "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }else{
                    $vendedores[$key]['bruto'] = "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }

            }else if(in_array(Auth::user()->tipo_usuario_id, [13])){
                if(in_array($vendedores[$key]['code'],$representantes)){
                    $vendedores[$key]['bruto'] = ($vendedores[$key]['bruto'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['bruto']) . "'>" .parserValor($vendedores[$key]['bruto']) . "</a>" : "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = ($vendedores[$key]['devolucao'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['devolucao']) . "'>" .parserValor($vendedores[$key]['devolucao']) . "</a>" : "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }else{
                    $vendedores[$key]['bruto'] = "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }

            }else if(in_array(Auth::user()->tipo_usuario_id, [19, 14])){
                if(in_array($vendedores[$key]['code'],$representantes)){
                    $vendedores[$key]['bruto'] = ($vendedores[$key]['bruto'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['bruto']) . "'>" .parserValor($vendedores[$key]['bruto']) . "</a>" : "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = ($vendedores[$key]['devolucao'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['devolucao']) . "'>" .parserValor($vendedores[$key]['devolucao']) . "</a>" : "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }else{
                    $vendedores[$key]['bruto'] = "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }

            }else if(in_array(Auth::id(),[11194, 10046])){
                if(in_array($vendedores[$key]['code'],$representantes)){
                    $vendedores[$key]['bruto'] = ($vendedores[$key]['bruto'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['bruto']) . "'>" .parserValor($vendedores[$key]['bruto']) . "</a>" : "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = ($vendedores[$key]['devolucao'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['devolucao']) . "'>" .parserValor($vendedores[$key]['devolucao']) . "</a>" : "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }else{
                    $vendedores[$key]['bruto'] = "R$ ".parserValor($vendedores[$key]['bruto']);
                    $vendedores[$key]['devolucao'] = "R$ ".parserValor($vendedores[$key]['devolucao']);
                    $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
                }

            }else{
                $vendedores[$key]['bruto'] = ($vendedores[$key]['bruto'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['bruto']) . "'>" .parserValor($vendedores[$key]['bruto']) . "</a>" : "R$ ".parserValor($vendedores[$key]['bruto']);
                $vendedores[$key]['devolucao'] = ($vendedores[$key]['devolucao'] > 0) ? 'R$ '." <a href='#' data-code='".encrypt($vendedores[$key]['code'])."' data-campos='".$vendedores[$key]['campos']."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($vendedores[$key]['devolucao']) . "'>" .parserValor($vendedores[$key]['devolucao']) . "</a>" : "R$ ".parserValor($vendedores[$key]['devolucao']);
                $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
            }

            $ordem ++;
        }   

        $retorno['vendedores'] = $vendedores;
        $retorno['ultima_atualizacao'] = (!empty($campanha->ultima_alteracao)) ? 'Última Atualização '.parserDataEHora($campanha->ultima_alteracao) : '';
        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno
            ]
        ], 200);
        
    }

    public function filtroCampanhaMetaGerentes(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campos = $request->only(['nome_campanha','periodo']);
        
        if(empty($campos['nome_campanha'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $campanha = Campanha::where('nome','ilike',$campos['nome_campanha'])->first();
        
        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        if(empty($campos['periodo'])){
            $campanha_data = Campanha::where('nome',$campos['nome_campanha'])
            ->select('id')
            ->first();

            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha_data->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',1)
            ->where('fim_periodo','>=',$hoje)
            ->orderBy('id')
            ->first();

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);

        }

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $retorno = [];
        $vendedores = collect();
        $segmentos = [];

        $carga_consulta = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
        ->where('campanhas_apuracao_comissoe_id',$periodo->id)
        ->with(['campanhasConsultaMetaVendedores' => function($query){
            $query->orderBy('id','desc');
        },'campanhasConsultaMetaVendedores.periodoMetaProduto.vendedor.unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio',
        'campanhasConsultaMetaVendedores.periodoMetaProduto.produtosContagem.produtoEspecificacao.segmento'])
        ->first();
        
        if(empty($carga_consulta->campanhasConsultaMetaVendedores)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }
        
        if(!empty($carga_consulta->campanhasConsultaMetaVendedores->logo)){
            $retorno['logo'] = $carga_consulta->campanhasConsultaMetaVendedores->logo;
        }else{
            $retorno['logo'] = '';
        }

        $retorno['metros'] = $carga_consulta->campanhasConsultaMetaVendedores->metros;
        $retorno['metros_medida'] = $carga_consulta->campanhasConsultaMetaVendedores->metros_medida;
        $retorno['unidade'] = $carga_consulta->campanhasConsultaMetaVendedores->unidade;
        $retorno['kilo'] = $carga_consulta->campanhasConsultaMetaVendedores->kilo;
        $retorno['valor'] = $carga_consulta->campanhasConsultaMetaVendedores->valor;
        $retorno['meta'] = 0;
        $retorno['percentual'] = 0;
        $retorno['percentual_tratado'] = 0;
        $retorno['percentual_restante'] = 0;
        $retorno['periodo'] = '';
        $retorno['segmentos'] = [];
        $meta = 0;

        if(empty($campos['periodo'])){
            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',2)
            ->where('fim_periodo','>=',$hoje)
            ->first();

            if(!empty($periodo)){
                $meta = $periodo->meta_reais;
            }else{
                $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha->id)
                ->where('campanhas_apuracao_comissoe_tipo_id',2)
                ->orderBy('fim_periodo')
                ->get()
                ->last();

                $meta = $periodo->meta_reais;
            }

            $inicio = Carbon::parse($periodo->inicio_periodo)->format('d/m/Y');
            $fim = Carbon::parse($periodo->fim_periodo)->format('d/m/Y');

            $retorno['periodo'] = 'Período: '.$inicio.' até '.$fim;

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);
            
            $inicio = Carbon::parse($periodo->inicio_periodo)->format('d/m/Y');
            $fim = Carbon::parse($periodo->fim_periodo)->format('d/m/Y');

            $data_busca = Carbon::parse($periodo->fim_periodo)->setTime(0,0,0);
            
            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',2)
            ->where('fim_periodo','>=',$data_busca)
            ->first();

            $retorno['periodo'] = 'Período: '.$inicio.' até '.$fim;

            $meta = $periodo->meta_reais;

        }

        foreach($carga_consulta->campanhasConsultaMetaVendedores->periodoMetaProduto as $key => $retorno_vendedores){
            
            if($retorno_vendedores->liquido > 0){
                $vendedores->push([
                    'liquido' => $retorno_vendedores->liquido,
                    'quantidade' => $retorno_vendedores->kilo + $retorno_vendedores->metros + $retorno_vendedores->unidade,
                    'campos' => encrypt($campos),
                    'id_unidade_negocio' => (isset($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)) ? $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->id : 'N/D',
                    'id_user' => (isset($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)) ? $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->users_id : 'N/D',
                    'equipe' => (isset($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)) ? $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade : 'N/D',
                ]);
            }

            foreach($retorno_vendedores->produtosContagem as $contagem){
                $valor_liquido = $contagem->valor - $contagem->devolucao_valor;

                if(!isset($segmentos[(!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D'])){
                    $segmento_descricao = (!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D'; 
                    $segmentos[(!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D'] = [
                        'valor' => $valor_liquido,
                        'percentual' => 0,
                        'campos' => encrypt($campos),
                        'segmento_id' => encrypt((!empty($contagem->produtoEspecificacao->segmento->id)) ? $contagem->produtoEspecificacao->segmento->id : 'N/D'),
                        'segmento_descricao' => $segmento_descricao,
                        'segmento' => '',
                        'ordem' => 0
                    ];
                }else{
                    $segmentos[(!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D']['valor'] += $valor_liquido;
                }
            }
        }

        $valor_total = $vendedores->sum('liquido');
        $equipes_array = [];
        
        foreach($vendedores as $retorno_vendedoresndedor){
            if(!isset($equipes_array[$retorno_vendedoresndedor['equipe']])){
                $link = "<a href='#' class='view-equipe-campanha' data-unidade='".encrypt($retorno_vendedoresndedor['id_unidade_negocio'])."' data-equipe='".$retorno_vendedoresndedor['equipe']."' data-campos='".$retorno_vendedoresndedor['campos']."' data-html='true' title='" .$retorno_vendedoresndedor['equipe'] . "'>" .$retorno_vendedoresndedor['equipe'] . "</a>";
                
                $equipes_array[$retorno_vendedoresndedor['equipe']] = [
                    'liquido' => $retorno_vendedoresndedor['liquido'],
                    'quantidade' => $retorno_vendedoresndedor['quantidade'],
                    'equipe' => $link,
                ];
            }else{
                $equipes_array[$retorno_vendedoresndedor['equipe']]['liquido'] += $retorno_vendedoresndedor['liquido'];
                $equipes_array[$retorno_vendedoresndedor['equipe']]['quantidade'] += $retorno_vendedoresndedor['quantidade'];
            }
        }

        $vendedores = collect($equipes_array)->sortByDesc('liquido')->take(9)->toArray();
        $ordem = 1;

        foreach($vendedores as $key => $vendedor){
            $vendedores[$key]['quantidade'] = parserValor($vendedores[$key]['quantidade']);
            $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
            $vendedores[$key]['pos'] = str_pad($ordem,2,'0',STR_PAD_LEFT);
        
            $ordem ++;
        }   

        $retorno['percentual'] = ($valor_total > 0) ? ($valor_total) * 100 : 0;
        
        if($retorno['percentual'] > 0 && $meta > 0){
            $retorno['percentual'] = $retorno['percentual'] / $meta;
            $retorno['percentual_restante'] = 100 - $retorno['percentual'];
            
            $retorno['percentual'] = (round($retorno['percentual'],2));
            $retorno['percentual_tratado'] = parserValor(($retorno['percentual']));
            $retorno['percentual_restante'] = (round($retorno['percentual_restante'],2));
        }
        
        if($retorno['percentual'] > 100){
            $retorno['percentual'] = 100;
        }

        if($retorno['percentual_restante'] < 0){
            $retorno['percentual_restante'] = 0;
        }

        $orderm_segmento = 1;

        foreach($segmentos as $key => $retorno_segmento){
            $segmentos[$key]['percentual'] = (($segmentos[$key]['valor'] * 100 / $valor_total) <= 100) ? $segmentos[$key]['valor'] * 100 / $valor_total : 100;
        }

        $segmentos = collect($segmentos)->sortByDesc('percentual')->take(5)->toArray();

        foreach($segmentos as $key => $retorno_segmento){
            $segmentos[$key]['ordem'] = str_pad($orderm_segmento,2,'0',STR_PAD_LEFT);
            $segmentos[$key]['percentual'] = parserValor($segmentos[$key]['percentual']);
            $segmentos[$key]['segmento'] = "<a href='#' class='view-segmento-campanha' data-descricao_segmento='".$segmentos[$key]['segmento_descricao'].' '.$segmentos[$key]['percentual']."' data-segmento_id='".$segmentos[$key]['segmento_id']."' data-campos='".$segmentos[$key]['campos']."' data-html='true' title='" .$segmentos[$key]['segmento_descricao']. "'>" .$segmentos[$key]['segmento_descricao']. "</a>";
            $orderm_segmento ++;
        }
        
        $retorno['segmentos'] = $segmentos;
        $retorno['vendedores'] = $vendedores;
        $retorno['valor'] = $retorno['valor'];
        $retorno['ultima_atualizacao'] = (!empty($campanha->ultima_alteracao)) ? 'Última Atualização '.parserDataEHora($campanha->ultima_alteracao) : '';
        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno
            ]
        ], 200);
    }

    public function modalSegmentosGerentes(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $retorno_campos = $request->only(['segmento_id','campos']);

        try{
            $segmento_id = decrypt($retorno_campos['segmento_id']);
            $campos = decrypt($retorno_campos['campos']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        if(empty($campos['nome_campanha'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $campanha = Campanha::where('nome','ilike',$campos['nome_campanha'])->first();

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        if(empty($campos['periodo'])){
            $campanha_data = Campanha::where('nome',$campos['nome_campanha'])
            ->select('id')
            ->first();

            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha_data->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',1)
            ->where('fim_periodo','>=',$hoje)
            ->orderBy('id')
            ->first();

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);

        }

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $retorno = [];
        $vendedores = collect();
        $segmentos = [];

        $carga_consulta = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
        ->where('campanhas_apuracao_comissoe_id',$periodo->id)
        ->with(['campanhasConsultaMetaVendedores' => function($query){
            $query->orderBy('id','desc');
        },'campanhasConsultaMetaVendedores.periodoMetaProduto.vendedor.unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio',
        'campanhasConsultaMetaVendedores.periodoMetaProduto.produtosContagem.produtoEspecificacao.segmento'])
        ->first();

        if(empty($carga_consulta->campanhasConsultaMetaVendedores)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }
        
        if(!empty($carga_consulta->campanhasConsultaMetaVendedores->logo)){
            $retorno['logo'] = $carga_consulta->campanhasConsultaMetaVendedores->logo;
        }else{
            $retorno['logo'] = '';
        }

        $retorno['segmentos'] = [];
        $total_valor = 0;
        $total_quantidade = 0;

        foreach($carga_consulta->campanhasConsultaMetaVendedores->periodoMetaProduto as $key => $retorno_vendedores){

            if($retorno_vendedores->liquido > 0){
                $vendedores->push([
                    'liquido' => $retorno_vendedores->liquido,
                 ]);
            }

            foreach($retorno_vendedores->produtosContagem as $contagem){

                if($segmento_id !== 'N/D'){
                    if(isset($contagem->produtoEspecificacao->segmento->id) && $contagem->produtoEspecificacao->segmento->id === $segmento_id){
                        $volume_liquido = ($contagem->metros + $contagem->unidade + $contagem->kilo) - $contagem->devolucao_metragem;
                        $valor_liquido = $contagem->valor - $contagem->devolucao_valor;
                        $total_valor += $valor_liquido;
                        $total_quantidade += $volume_liquido;

                        if(!isset($segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D'])){
                            $segmento_descricao = (!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D'; 
                            $segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D'] = [
                                'valor' => $valor_liquido,
                                'quantidade' => $volume_liquido,
                                'grupo' => (!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D',
                            ];
                        }else{
                            $segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D']['valor'] += $valor_liquido;
                            $segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D']['quantidade'] += $volume_liquido;
                        }

                    }
                }else if($segmento_id == 'N/D'){
                    if(!isset($contagem->produtoEspecificacao->segmento->id)){
                            $volume_liquido = ($contagem->metros + $contagem->unidade + $contagem->kilo) - $contagem->devolucao_metragem;
                            $valor_liquido = $contagem->valor - $contagem->devolucao_valor;
                            $total_valor += $valor_liquido;
                            $total_quantidade += $volume_liquido;

                        if(!isset($segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D'])){
                            $segmento_descricao = (!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D'; 
                            $segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D'] = [
                                'valor' => $valor_liquido,
                                'quantidade' => $volume_liquido,
                                'grupo' => (!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D',
                            ];
                        }else{
                            $segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D']['valor'] += $valor_liquido;
                            $segmentos[(!empty($contagem->produtoEspecificacao->grupo)) ? $contagem->produtoEspecificacao->grupo : 'N/D']['quantidade'] += $volume_liquido;
                        }
                    }
                }
            }
        }

        $segmentos = collect($segmentos)->sortByDesc('quantidade')->toArray();

        foreach($segmentos as $key => $retorno_segmento){
            $segmentos[$key]['valor'] = 'R$ '.parserValor($segmentos[$key]['valor']);
            $segmentos[$key]['quantidade'] = parserValor($segmentos[$key]['quantidade']);
        }


        $retorno['segmentos'] = $segmentos;
        $retorno['total_quantidade'] = parserValor($total_quantidade);
        $retorno['total_valor'] = parserValor($total_valor);

        return view('programs.campanhas.mapas.modal.segmentos')->with(['segmentos' => $retorno['segmentos'],'total_quantidade' => $retorno['total_quantidade'], 'total_valor' => $retorno['total_valor']]);
    }

    public function modalEquipesGerentes(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $retorno_campos = $request->only(['unidade','campos']);

        try{
            $unidade = decrypt($retorno_campos['unidade']);
            $campos = decrypt($retorno_campos['campos']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        if(empty($campos['nome_campanha'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $campanha = Campanha::where('nome','ilike',$campos['nome_campanha'])->first();
        
        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        if(empty($campos['periodo'])){
            $campanha_data = Campanha::where('nome',$campos['nome_campanha'])
            ->select('id')
            ->first();

            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha_data->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',1)
            ->where('fim_periodo','>=',$hoje)
            ->orderBy('id')
            ->first();

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);
        }

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $retorno = [];
        $vendedores = collect();
        $segmentos = [];
        
        $carga_consulta = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
        ->where('campanhas_apuracao_comissoe_id',$periodo->id)
        ->with(['campanhasConsultaMetaVendedores' => function($query){
            $query->orderBy('id','desc');
        },'campanhasConsultaMetaVendedores.periodoMetaProduto.vendedor.unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio'])
        ->first();
        
        if(empty($carga_consulta->campanhasConsultaMetaVendedores)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }
        
        $retorno['periodo'] = '';
        $retorno['segmentos'] = [];
        $total_bruto = 0;
        $total_devolucao = 0;
        
        foreach($carga_consulta->campanhasConsultaMetaVendedores->periodoMetaProduto as $key => $retorno_vendedores){
            
            if($retorno_vendedores->liquido > 0){
                if($unidade === 'N/D'){
                    if(!empty($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)){
                        continue;
                    }

                    $vendedores->push([
                        'representante' => $retorno_vendedores->vendedor_descricao,
                        'bruto' => ($retorno_vendedores->bruto > 0) ? 'R$ '." <a href='#' data-code='".encrypt($retorno_vendedores->vendedor_codigo)."' data-campos='".encrypt($campos)."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($retorno_vendedores->bruto) . "'>" .parserValor($retorno_vendedores->bruto) . "</a>" : "R$ ".parserValor($retorno_vendedores->bruto),
                        'liquido' => $retorno_vendedores->liquido,
                        'rank' => $retorno_vendedores->POS,
                        'devolucao' => ($retorno_vendedores->devolucao > 0) ? 'R$ '." <a href='#' data-code='".encrypt($retorno_vendedores->vendedor_codigo)."' data-campos='".encrypt($campos)."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($retorno_vendedores->devolucao) . "'>" .parserValor($retorno_vendedores->devolucao) . "</a>" : "R$ ".parserValor($retorno_vendedores->devolucao),
                     ]);

                    $total_bruto += $retorno_vendedores->bruto;
                    $total_devolucao += $retorno_vendedores->devolucao;
                }else if(!empty($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio) && 
                $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->id == $unidade){
                    
                    $vendedores->push([
                        'representante' => $retorno_vendedores->vendedor_descricao,
                        'bruto' => ($retorno_vendedores->bruto > 0) ? 'R$ '." <a href='#' data-code='".encrypt($retorno_vendedores->vendedor_codigo)."' data-campos='".encrypt($campos)."' class='view-bruto-campanha' data-html='true' title='" ."R$ ".parserValor($retorno_vendedores->bruto) . "'>" .parserValor($retorno_vendedores->bruto) . "</a>" : "R$ ".parserValor($retorno_vendedores->bruto),
                        'liquido' => $retorno_vendedores->liquido,
                        'rank' => $retorno_vendedores->POS,
                        'devolucao' => ($retorno_vendedores->devolucao > 0) ? 'R$ '." <a href='#' data-code='".encrypt($retorno_vendedores->vendedor_codigo)."' data-campos='".encrypt($campos)."' class='view-devolucao-campanha' data-html='true' title='" ."R$ ".parserValor($retorno_vendedores->devolucao) . "'>" .parserValor($retorno_vendedores->devolucao) . "</a>" : "R$ ".parserValor($retorno_vendedores->devolucao),
                     ]);

                    $total_bruto += $retorno_vendedores->bruto;
                    $total_devolucao += $retorno_vendedores->devolucao;
                }

            }
        }

        $vendedores = collect($vendedores)->sortByDesc('liquido')->toArray();

        $retorno['vendedores'] = $vendedores;
        $total_bruto = parserValor($total_bruto);
        $total_devolucao = parserValor($total_devolucao);

        return view('programs.campanhas.mapas.modal.equipes')->with(['equipes' => $retorno['vendedores'],'total_devolucao' => $total_devolucao, 'total_bruto' => $total_bruto]);
    }
    
    public function filtroCampanhaMetaDiretor(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campos = $request->only(['nome_campanha','periodo']);
        
        if(empty($campos['nome_campanha'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $campanha = Campanha::where('nome','ilike',$campos['nome_campanha'])->first();
        
        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }
        
        if(empty($campos['periodo'])){
            $campanha_data = Campanha::where('nome',$campos['nome_campanha'])
            ->select('id')
            ->first();

            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha_data->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',1)
            ->where('fim_periodo','>=',$hoje)
            ->orderBy('id')
            ->first();

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);
        }

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        $retorno = [];
        $vendedores = collect();
        $segmentos = [];

        $carga_consulta = CampanhasConsultaMetaVendedoresPeriodo::where('campanha_id',$campanha->id)
        ->where('campanhas_apuracao_comissoe_id',$periodo->id)
        ->with(['campanhasConsultaMetaVendedores' => function($query){
            $query->orderBy('id','desc');
        },'campanhasConsultaMetaVendedores.periodoMetaProduto.vendedor.unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio',
        'campanhasConsultaMetaVendedores.periodoMetaProduto.produtosContagem.produtoEspecificacao.segmento'])
        ->first();
        
        if(empty($carga_consulta->campanhasConsultaMetaVendedores)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }
        
        if(!empty($carga_consulta->campanhasConsultaMetaVendedores->logo)){
            $retorno['logo'] = $carga_consulta->campanhasConsultaMetaVendedores->logo;
        }else{
            $retorno['logo'] = '';
        }
        
        $retorno['metros'] = $carga_consulta->campanhasConsultaMetaVendedores->metros;
        $retorno['metros_medida'] = $carga_consulta->campanhasConsultaMetaVendedores->metros_medida;
        $retorno['unidade'] = $carga_consulta->campanhasConsultaMetaVendedores->unidade;
        $retorno['kilo'] = $carga_consulta->campanhasConsultaMetaVendedores->kilo;
        $retorno['valor'] = $carga_consulta->campanhasConsultaMetaVendedores->valor;
        $retorno['total_volume'] = 0;
        $retorno['meta_vendedores'] = 0;
        $retorno['meta'] = 0;
        $retorno['percentual'] = 0;
        $retorno['percentual_vendedores'] = 0;
        $retorno['percentual_tratado'] = 0;
        $retorno['percentual_restante'] = 0;
        $retorno['periodo'] = '';
        $retorno['segmentos'] = [];
        $meta = 0;

        if(empty($campos['periodo'])){
            $hoje = Carbon::now()->setTime(0,0,0);

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',2)
            ->where('fim_periodo','>=',$hoje)
            ->first();

            if(!empty($periodo)){
                $meta = $periodo->meta_reais;
            }else{
                $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha->id)
                ->where('campanhas_apuracao_comissoe_tipo_id',2)
                ->orderBy('fim_periodo')
                ->get()
                ->last();

                $meta = $periodo->meta_reais;
            }
            $inicio = Carbon::parse($periodo->inicio_periodo)->format('d/m/Y');
            $fim = Carbon::parse($periodo->fim_periodo)->format('d/m/Y');

            $retorno['periodo'] = 'Período: '.$inicio.' até '.$fim;

        }else if(!empty($campos['periodo'])){
            $periodo =  CampanhasApuracaoComissoe::find($campos['periodo']);
            
            $inicio = Carbon::parse($periodo->inicio_periodo)->format('d/m/Y');
            $fim = Carbon::parse($periodo->fim_periodo)->format('d/m/Y');

            $data_busca = Carbon::parse($periodo->fim_periodo)->setTime(0,0,0);
            
            if($periodo->campanhas_apuracao_comissoe_tipo_id == 1 && !empty($periodo->meta_reais)){
                $retorno['meta_vendedores'] = parserValor($periodo->meta_reais);
            }

            $periodo =  CampanhasApuracaoComissoe::where('campanha_id',$campanha->id)
            ->where('campanhas_apuracao_comissoe_tipo_id',2)
            ->where('fim_periodo','>=',$data_busca)
            ->first();


            $retorno['periodo'] = 'Período: '.$inicio.' até '.$fim;

            $meta = $periodo->meta_reais;

        }

        $retorno['meta'] = parserValor($meta);

        foreach($carga_consulta->campanhasConsultaMetaVendedores->periodoMetaProduto as $key => $retorno_vendedores){
            if($retorno_vendedores->liquido > 0){
                $vendedores->push([
                    'liquido' => $retorno_vendedores->liquido,
                    'quantidade' => $retorno_vendedores->kilo + $retorno_vendedores->metros + $retorno_vendedores->unidade,
                    'campos' => encrypt($campos),
                    'id_unidade_negocio' => (isset($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)) ? $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->id : 'N/D',
                    'id_user' => (isset($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)) ? $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->users_id : 'N/D',
                    'equipe' => (isset($retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)) ? $retorno_vendedores->vendedor->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade : 'N/D',
                ]);

            }

            foreach($retorno_vendedores->produtosContagem as $contagem){
                $valor_liquido = $contagem->valor - $contagem->devolucao_valor;

                $retorno['total_volume'] += ($contagem->kilo + $contagem->metros + $contagem->unidade) - $contagem->devolucao_metragem;
                
                if(!isset($segmentos[(!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D'])){
                    $segmento_descricao = (!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D'; 
                    $segmentos[(!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D'] = [
                        'valor' => $valor_liquido,
                        'percentual' => 0,
                        'campos' => encrypt($campos),
                        'segmento_id' => encrypt((!empty($contagem->produtoEspecificacao->segmento->id)) ? $contagem->produtoEspecificacao->segmento->id : 'N/D'),
                        'segmento_descricao' => $segmento_descricao,
                        'segmento' => '',
                        'ordem' => 0
                    ];
                }else{
                    $segmentos[(!empty($contagem->produtoEspecificacao->segmento->descricao)) ? $contagem->produtoEspecificacao->segmento->descricao : 'N/D']['valor'] += $valor_liquido;
                }
            }
        }

        $valor_total = $vendedores->sum('liquido');
        $equipes_array = [];

        foreach($vendedores as $retorno_vendedoresndedor){
            if(!isset($equipes_array[$retorno_vendedoresndedor['equipe']])){
                    $link = "<a href='#' class='view-equipe-campanha' data-unidade='".encrypt($retorno_vendedoresndedor['id_unidade_negocio'])."' data-equipe='".$retorno_vendedoresndedor['equipe']."' data-campos='".$retorno_vendedoresndedor['campos']."' data-html='true' title='" .$retorno_vendedoresndedor['equipe'] . "'>" .$retorno_vendedoresndedor['equipe'] . "</a>";
                    
                    $equipes_array[$retorno_vendedoresndedor['equipe']] = [
                    'liquido' => $retorno_vendedoresndedor['liquido'],
                    'quantidade' => $retorno_vendedoresndedor['quantidade'],
                    'equipe' => $link,
                ];
            }else{
                $equipes_array[$retorno_vendedoresndedor['equipe']]['liquido'] += $retorno_vendedoresndedor['liquido'];
                $equipes_array[$retorno_vendedoresndedor['equipe']]['quantidade'] += $retorno_vendedoresndedor['quantidade'];
            }
        }

        $vendedores = collect($equipes_array)->sortByDesc('liquido')->take(9)->toArray();
        $ordem = 1;

        foreach($vendedores as $key => $vendedor){
            $vendedores[$key]['quantidade'] = parserValor($vendedores[$key]['quantidade']);
            $vendedores[$key]['liquido'] = 'R$ '.parserValor($vendedores[$key]['liquido']);
            $vendedores[$key]['pos'] = str_pad($ordem,2,'0',STR_PAD_LEFT);
        
            $ordem ++;
        }

        $retorno['percentual'] = ($valor_total > 0) ? ($valor_total) * 100 : 0;
        $retorno['percentual_vendedores'] = ($valor_total > 0 && $retorno['meta_vendedores'] > 0) ? ($valor_total) * 100 : 0;
        
        if($retorno['percentual'] > 0 && $meta > 0){
            $retorno['percentual'] = $retorno['percentual'] / $meta;
            $retorno['percentual_restante'] = 100 - $retorno['percentual'];
            
            $retorno['percentual'] = (round($retorno['percentual'],2));
            $retorno['percentual_tratado'] = parserValor(($retorno['percentual']));
            $retorno['percentual_restante'] = (round($retorno['percentual_restante'],2));
        }

        if($retorno['meta_vendedores'] > 0 && $retorno['percentual_vendedores'] > 0){
            $retorno['percentual_vendedores'] = $retorno['percentual_vendedores'] / $retorno['meta_vendedores'];
            $retorno['percentual_vendedores'] = (round($retorno['percentual_vendedores'],2));
        }

        $retorno['percentual_vendedores'] = ($retorno['percentual_vendedores'] > 0) ? parserValor($retorno['percentual_vendedores']) : 'N/D';
        $retorno['meta_vendedores'] = ($retorno['meta_vendedores'] > 0) ? parserValor($retorno['meta_vendedores']) : 'N/D';
        
        if($retorno['percentual'] > 100){
            $retorno['percentual'] = 100;
        }

        if($retorno['percentual_restante'] < 0){
            $retorno['percentual_restante'] = 0;
        }

        $orderm_segmento = 1;

        foreach($segmentos as $key => $retorno_segmento){
            $segmentos[$key]['percentual'] = (($segmentos[$key]['valor'] * 100 / $valor_total) <= 100) ? $segmentos[$key]['valor'] * 100 / $valor_total : 100;
        }

        $segmentos = collect($segmentos)->sortByDesc('percentual')->take(5)->toArray();

        foreach($segmentos as $key => $retorno_segmento){
            $segmentos[$key]['ordem'] = str_pad($orderm_segmento,2,'0',STR_PAD_LEFT);
            $segmentos[$key]['percentual'] = parserValor($segmentos[$key]['percentual']);
            $segmentos[$key]['segmento'] = "<a href='#' class='view-segmento-campanha' data-descricao_segmento='".$segmentos[$key]['segmento_descricao'].' '.$segmentos[$key]['percentual']."' data-segmento_id='".$segmentos[$key]['segmento_id']."' data-campos='".$segmentos[$key]['campos']."' data-html='true' title='" .$segmentos[$key]['segmento_descricao']. "'>" .$segmentos[$key]['segmento_descricao']. "</a>";
            $orderm_segmento ++;
        }
        
        $retorno['segmentos'] = $segmentos;
        $retorno['vendedores'] = $vendedores;
        $retorno['valor'] = $retorno['valor'];
        $retorno['ultima_atualizacao'] = (!empty($campanha->ultima_alteracao)) ? 'Última Atualização '.parserDataEHora($campanha->ultima_alteracao) : '';
        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno
            ]
        ], 200);
    }

    public function monitorarDevolucoes(){
        $hoje = Carbon::now()->setTime(23,59,59);
        $dias_30 = Carbon::now()->subDays(31)->setTime(0,0,0);
        
        $faturamento_devolucao = FaturamentoNotaNasajon::whereBetween('Data Lançamento',[$dias_30,$hoje])
        ->whereHas('pedidoNasajonDevolucao',function($query){
            $query->where('situacao_descricao','Faturado')
            ->where('grupodeoperacao','VENDA');
        })
        ->with(['itens_faturamento'=> function($query){
            $query->with('produtoNasajonDetalhes')
            ->distinct();
        }])
        ->distinct()
        ->get();

        foreach($faturamento_devolucao as $faturamento){
            $comissao_campanha = CampanhasComissaoCalculo::where('nota_id',$faturamento["Id_Nota_Origem"])
            ->first();
            
            if(!empty($comissao_campanha)){
                $metros = 0;
                $valor = 0;

                foreach($faturamento->itens_faturamento as $item){
                    $campanha_produto = CampanhasComissaoCalculoIten::where('campanha_comissao_calculo_id',$comissao_campanha->id)
                    ->whereNotNull('campanha_id')
                    ->where('produto_codigo',$item["Item - Código"])
                    ->first();

                    if(!empty($campanha_produto)){
                        $metros += $item["Item - Quantidade"];
                    }

                    if(!empty($campanha_produto)){
                        $valor += $item["Item - Valor Total"];
                    }
                }
                
                $comissao_campanha->valor_nota_devolucao = $valor;
                $comissao_campanha->quantidade_nota_devolucao = $metros;
                $comissao_campanha->id_nota_devolucao = $faturamento["Id_Nota_Origem"];
                $comissao_campanha->save();
            }

        }
    }

    public function CorrecaoComissao(){
        $campanhas = CampanhasComissaoCalculo::select()->get();
        $teste = [];
        foreach($campanhas as $campanha){
            $faturamento = FaturamentoNotaNasajon::select()->where('Identificador Documento', $campanha->nota_id)->first();

            
            if(empty($faturamento)){
                $teste[] = $campanha;
            }else{
                if($faturamento['Vendedor - Percentual Comissão'] == 0.3){
                    $teste[] = $campanha;
                }else{
                    $vendedor = VendedorNasajon::select()->where('codigo', $faturamento['Vendedor - Código'])->first();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$faturamento['Identificador Documento']}', /* id_nota */
                        '{$vendedor->id}', /* id_vendedor */
                        '100', /* participacao */
                        '{$faturamento['Vendedor - Percentual Comissão']}', /* comissao */
                        true /* vendedor_principal */
                    );";
        
                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
            
        }

        dd('ok?', $teste);
    }

    public function verificaImportacaoProduto(){
        $campanhas = Campanha::where('ativo',true)
        ->with('estabelecimentosCampanha','produtosCampanha')
        ->get();

        foreach($campanhas as $query){
            foreach($query->produtosCampanha as $produtos){
                foreach($query->estabelecimentosCampanha as $estabelecimentos){
                    $produto_estoque = ProdutosEstoque::where('estabelecimento',str_pad($estabelecimentos->estabelecimento_codigo, 2, 0, STR_PAD_LEFT))
                    ->where('codigo_produto',$produtos->produto_codigo)
                    ->whereNull('campanha_id')
                    ->update(['campanha_id' => $query->id]);
                }
            }
        };
    }

    public function modalValorBruto(Request $request){
        set_time_limit(600);
        ini_set('memory_limit','1024M');

        $campos = $request->only(['code','campos']);

        try{
            $codigo_vendedor = decrypt($campos['code']);
            $filtro = decrypt($campos['campos']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $campanha = Campanha::where('nome',$filtro['nome_campanha'])
        ->with(['associacaoPedido.pedido.campanhaComissao.pedidoNasajon' => function($query) use ($filtro,$codigo_vendedor){
            $query->where('vendedor_codigo',$codigo_vendedor)
            ->where('grupodeoperacao','VENDA');

            if(empty($filtro['periodo'])){
                $campanha_data = Campanha::where('nome',$filtro['nome_campanha'])
                ->select('inicio_campanha','fim_campanha')
                ->first();

                if(empty($campanha_data)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Dados não encontrados',
                        'error' => [],
                        'response' => []
                    ], 200);
                }

                $inicio = Carbon::parse($campanha_data->inicio_campanha);
                $fim = Carbon::parse($campanha_data->fim_campanha);

                $query->whereBetween('emissao',[$inicio,$fim]);
            }else if(!empty($filtro['periodo'])){
                $periodo =  CampanhasApuracaoComissoe::find($filtro['periodo']);

                $inicio = Carbon::parse($periodo->inicio_periodo);
                $fim = Carbon::parse($periodo->fim_periodo);

                $query->whereBetween('emissao',[$inicio,$fim]);
            }

            $query->with(['pedido_portal.itens_pedido','cliente_detalhes','nota.itens_nota','pedido_portal.itens_pedido.pedido_portal.pedidoNasajon.itens_pedido']);
        }])
        ->first();

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }
        
        $ultima_atualizacao = (!empty($campanha->ultima_alteracao)) ? Carbon::parse($campanha->ultima_alteracao) : '';
        $retorno = [];
        $retorno['metros'] = 0;
        $retorno['valor'] = 0;
        $retorno['pedido'] = [];
        $retorno['vendedores'] = [];
        $estabelecimentos = returnTodasEmpresasVendas();

        $campanha->associacaoPedido->each(function($query) use (&$retorno,$estabelecimentos,$ultima_atualizacao){
            $id = $query->campanha_id;
            if(!empty($query->pedido->campanhaComissao[0])){
                foreach($query->pedido->campanhaComissao as $campanha_comissao){
                    $valor = 0;

                    if(isset($campanha_comissao->pedidoNasajon->situacao_descricao) && !empty($campanha_comissao->pedidoNasajon->situacao_descricao)){
                        if($campanha_comissao->pedidoNasajon->situacao_descricao == 'Faturado'){
                            $data_pedido = Carbon::parse($campanha_comissao->pedidoNasajon->pedido_portal->updated_at);
                            
                            if($ultima_atualizacao->lt($data_pedido)){
                                continue;
                            }

                            foreach($campanha_comissao->pedidoNasajon->pedido_portal->itens_pedido as $itens_pedido){
                                if($itens_pedido->campanha_id == $id){
                                    if(strpos($itens_pedido->pedido_portal->tipo_venda, "pre_pago") !== false){
                                        $valor += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') * 2 : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('valortotal') * 2;
                                    }else{
                                        $valor += (isset($itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota)) ? $itens_pedido->pedido_portal->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') : $itens_pedido->pedido_portal->pedidoNasajon->itens_pedido->where('produto_codigo',$itens_pedido->cod_produto)->sum('valortotal');
                                    } 
                                }
                            }

                            if(!isset($retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome])){
                                $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome] = [
                                    'valor' => $valor
                                ];
                            }else{
                                $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['valor'] += $valor;
                            }

                            $retorno['pedido'][] = [
                                'numero_pedido' => $campanha_comissao->pedidoNasajon->pedido_portal->id,
                                'numero_nasajon' => $campanha_comissao->pedidoNasajon->numero,
                                'numero_nota' => (!empty($campanha_comissao->pedidoNasajon->nota->numero)) ? $campanha_comissao->pedidoNasajon->nota->numero : $campanha_comissao->pedidoNasajon->notafiscal_numero,
                                'id_nota' => (!empty($campanha_comissao->pedidoNasajon->nota->id)) ? $campanha_comissao->pedidoNasajon->nota->id : $campanha_comissao->pedidoNasajon->notafiscal_numero,
                                'valor' => ($valor > 0) ? parserValor($valor) : 'N/D',
                                'estabelecimento' => $estabelecimentos[(int)$campanha_comissao->pedidoNasajon->estabelecimento_codigo],
                                'cliente' => $campanha_comissao->pedidoNasajon->cliente_detalhes->nome.' - '.$campanha_comissao->pedidoNasajon->cliente_cnpj,
                                'data' => parserData($campanha_comissao->pedidoNasajon->pedido_portal->data_pedido)
                            ];
                        }
                    }

                    $retorno['valor'] += ($valor > 0) ? $valor: 0;
                }
            }
        });
        
        return view('programs.campanhas.mapas.modal.lista_pedidos')->with(['retorno' => $retorno,'total' => parserValor($retorno['valor'])]);
    }

    public function modalDevolucao(Request $request){
        set_time_limit(600);
        ini_set('memory_limit','1024M');
        
        $campos = $request->only(['code','campos']);

        try{
            $codigo_vendedor = decrypt($campos['code']);
            $filtro = decrypt($campos['campos']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $campanha = Campanha::where('nome',$filtro['nome_campanha'])
        ->with(['associacaoPedido.pedido.campanhaComissao.pedidoNasajon' => function($query) use ($filtro,$codigo_vendedor){
            $query->where('vendedor_codigo',$codigo_vendedor)
            ->where('grupodeoperacao','VENDA');

            if(empty($filtro['periodo'])){
                $campanha_data = Campanha::where('nome',$filtro['nome_campanha'])
                ->select('inicio_campanha','fim_campanha')
                ->first();

                if(empty($campanha_data)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Dados não encontrados',
                        'error' => [],
                        'response' => []
                    ], 200);
                }

                $inicio = Carbon::parse($campanha_data->inicio_campanha);
                $fim = Carbon::parse($campanha_data->fim_campanha);

                $query->whereBetween('emissao',[$inicio,$fim]);
            }else if(!empty($filtro['periodo'])){
                $periodo =  CampanhasApuracaoComissoe::find($filtro['periodo']);

                $inicio = Carbon::parse($periodo->inicio_periodo);
                $fim = Carbon::parse($periodo->fim_periodo);

                $query->whereBetween('emissao',[$inicio,$fim]);
            }

            $query->with(['pedido_portal.itens_pedido','nota.itens_nota']);
        }])
        ->first();

        if(empty($campanha)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 200);
        }

        
        $retorno = [];
        $retorno['metros'] = 0;
        $retorno['valor'] = 0;
        $retorno['pedido'] = [];
        $retorno['vendedores'] = [];
        $estabelecimentos = returnTodasEmpresasVendas();

        $campanha->associacaoPedido->each(function($query) use (&$retorno,$estabelecimentos){
            $id = $query->campanha_id;
            if(!empty($query->pedido->campanhaComissao[0])){
                foreach($query->pedido->campanhaComissao as $campanha_comissao){
                    $devolucao_valor = (!empty($campanha_comissao->valor_nota_devolucao)) ? $campanha_comissao->valor_nota_devolucao : 0;
                    $devolucao_quantidade = (!empty($campanha_comissao->quantidade_nota_devolucao)) ? $campanha_comissao->quantidade_nota_devolucao : 0;
                    $valor = 0;

                    if(isset($campanha_comissao->pedidoNasajon->situacao_descricao) && !empty($campanha_comissao->pedidoNasajon->situacao_descricao)){
                        if($campanha_comissao->pedidoNasajon->situacao_descricao == 'Faturado'){

                            foreach($campanha_comissao->pedidoNasajon->pedido_portal->itens_pedido as $itens_pedido){
                                if($itens_pedido->campanha_id == $id){
                                    $valor += (isset($campanha_comissao->pedidoNasajon->nota->itens_nota)) ? $campanha_comissao->pedidoNasajon->nota->itens_nota->where('codigo',$itens_pedido->cod_produto)->sum('valortotal') : $itens_pedido->valor_total;
                                }
                            }

                            if(!isset($retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome])){
                                $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome] = [
                                    'valor' => $valor
                                ];
                            }else{
                                $retorno['vendedores'][$campanha_comissao->pedidoNasajon->vendedor_codigo.' - '.$campanha_comissao->pedidoNasajon->vendedor_nome]['valor'] += $valor;
                            }

                            if($devolucao_valor > 0){
                                $faturamento = FaturamentoNotaNasajon::where("Id_Nota_Origem",$campanha_comissao->id_nota_devolucao)->select("Id_Nota","Número Documento")->first();
                                
                                if(empty($faturamento)){
                                    return response()->json([
                                        'status' => 'error',
                                        'message' => 'Dados de devolução não encontrados',
                                        'error' => [],
                                        'response' => []
                                    ], 200);
                                }
                                $retorno['pedido'][] = [
                                    'numero_pedido' => $campanha_comissao->pedidoNasajon->pedido_portal->id,
                                    'numero_nasajon' => $campanha_comissao->pedidoNasajon->numero,
                                    'valor' => ($devolucao_valor > 0) ? parserValor($devolucao_valor) : 'N/D',
                                    'estabelecimento' => $estabelecimentos[(int)$campanha_comissao->pedidoNasajon->estabelecimento_codigo],
                                    'cliente' => $campanha_comissao->pedidoNasajon->cliente_cnpj.' - '.$campanha_comissao->pedidoNasajon->cliente_nomefantasia,
                                    'data' => parserData($campanha_comissao->pedidoNasajon->pedido_portal->data_pedido),
                                    'numero_nota' => (!empty($campanha_comissao->pedidoNasajon->nota->numero)) ? $campanha_comissao->pedidoNasajon->nota->numero : '',
                                    'id_nota' => (!empty($campanha_comissao->pedidoNasajon->nota->id)) ? $campanha_comissao->pedidoNasajon->nota->id : '',
                                    'id_nota_devolucao' => $faturamento["Id_Nota"],
                                    "nota_devolucao" => $faturamento["Número Documento"]
                                ];

                                $retorno['valor'] += ($devolucao_valor > 0) ? $devolucao_valor: 0;
                            }
                        }
                    }
                }
            }
        });

        return view('programs.campanhas.mapas.modal.lista_devolucao')->with(['retorno' => $retorno,'total' => parserValor($retorno['valor'])]);
    }

    public function exportCampanha(Request $request){
        $fields = $request->only('nome');

        $freteXLSX = new CampanhaExportarExcelExport($request);

        $pdfFilePath = 'campanha_'.str_replace(' ','',$fields['nome']).'.xlsx';
        return Excel::download(
            $freteXLSX, $pdfFilePath
        );
    }

    public function ativarProdutoCampanha(Request $request)
    {
        $campos = $request->only(['codigo', 'estabelecimento', 'campanha', 'campanha_id']);


        $produto_estoque = ProdutosEstoque::where('codigo_produto', $campos['codigo'])
        ->update(['campanha_id' =>  $campos['campanha_id']]);

   
        sleep(2);
       
            $verifica_produtos_ativos = ProdutosEstoque::where('campanha_id', $campos['campanha_id'])
            ->where('codigo_produto', $campos['codigo'])
            ->exists();

        sleep(2);
        $produto_outra_campanha = CampanhasProduto::where('produto_codigo', $campos['codigo'])
            ->where('campanha_id', '<>', $campos['campanha_id'])->withTrashed()
            ->whereNull('deleted_at')
            ->delete();
        if ($verifica_produtos_ativos === true) {
            $produto_campanha = CampanhasProduto::where('produto_codigo', $campos['codigo'])
                ->where('campanha_id', $campos['campanha_id'])->withTrashed()
                ->update(['deleted_at' => null]);
        }

        if ($produto_estoque) {
            $log = new CampanhasLog();
            $log->campanha_id = $campos['campanha_id'];
            $log->descricao = 'Mudado e Ativando produto ' . $campos['codigo'] . ' na campanha ' . $campos['campanha'];
            $log->campanhas_acoe_id = 4;
            $log->created_by = Auth::user()->id;
            $log->save();

            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => []
            ], 200);
        } else {
            $log = new CampanhasLog();
            $log->campanha_id = $campos['campanha_id'];
            $log->descricao = 'Ativando produto ' . $campos['codigo'] . ' na campanha ' . $campos['campanha'];
            $log->campanhas_acoe_id = 4;
            $log->created_by = Auth::user()->id;
            $log->save();

            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => []
            ], 200);
        }
    }

    public function monitorarPedidosPrePagos(){
        $hoje = Carbon::now();
        $um_mes = Carbon::now()->subMonths(3);

        $campanha = CampanhasAssociacaoPedido::whereHas('pedido', function($query) use($hoje,$um_mes){
            $query->where('tipo_venda','ilike','%pre_pago%')
            ->whereBetween('data_pedido',[$um_mes,$hoje]);
         })
         ->whereHas('pedido.pedidoPrePago.lancamentos', function($query) use($hoje,$um_mes){
            $query->whereNull('comissao');
         })
         ->with(['pedido' => function($query) use($hoje,$um_mes){
            $query->where('tipo_venda','ilike','%pre_pago%')
            ->whereBetween('data_pedido',[$um_mes,$hoje]);
         },'pedido.pedidoPrePago.lancamentos','pedido.pedidoNasajon.nota.revisao_vendedor_comissao'])
        ->get();

        if(empty($campanha->first())){
            return;
        }
        
        foreach($campanha as $campanhas){
            if($campanhas->pedido->pedidoNasajon->situacao_descricao == 'Faturado'){
                $percentual = (isset($campanhas->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao)) ? $campanhas->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao : '';

                if(empty($percentual)){
                    continue;
                }

                foreach($campanhas->pedido->pedidoPrePago->lancamentos as $campanha_comissao){
                    $campanha_comissao->comissao = $percentual;
                    $campanha_comissao->save();
                }
            }
        }

    }

    public function monitorarPedidosSemAssossiacao(){
        $pedidos = PedidoPortal::whereHas('itens_pedido',function($query){
            $query->whereNotNull('campanha_id');
        })
        ->with(['itens_pedido' => function($query){
            $query->whereNotNull('campanha_id')
            ->select('campanha_id','pedido');
        }])
        ->where('status_pedido',3)
        ->whereDoesntHave('campanhasPedido')
        ->get();

        foreach($pedidos as $pedido){
            $array_campanha_id = [];

            foreach($pedido->itens_pedido as $item_pedido){
                if(!in_array($item_pedido->campanha_id,$array_campanha_id)){
                    $array_campanha_id[] = $item_pedido->campanha_id;
                }
            }

            foreach($array_campanha_id as $id_campanha){
                $hoje = Carbon::now();

                $associacao_pedido = new CampanhasAssociacaoPedido;
                $associacao_pedido->pedido_id = $pedido->id;
                $associacao_pedido->campanha_id = $id_campanha;
                $associacao_pedido->created_by = 1;
                $associacao_pedido->created_at = $hoje;
                $associacao_pedido->save();
            }
        }

    }

    public function importarProtudosArquivo(CampanhaImportarProdutosArquivoResquest $request){
        $fields = $request->only('campanha_id');
        $arquivo = $request->file('arquivo');
        $stream = fopen($arquivo->getPathName(), 'r');

        $codigo = [];
        $extensao = $arquivo->getClientOriginalExtension();

        if($extensao === 'csv'){
            while(($data = fgetcsv($stream, 0)) !== false){

                if(!isset($codigo[$data[0]])){
                    $codigo[$data[0]] = $data[0];
                }

            }
        }else if($extensao === 'txt'){
            $csvAsArray = array_map('str_getcsv', file($arquivo));
            foreach($csvAsArray as $linhas){
                foreach($linhas as $codigos){
                    if(!empty($codigos) && !isset($codigo[$codigos])){
                        $codigo[$codigos] = $codigos;
                    }
                }
            }
        }
        
        if(count($codigo) <= 0){
            return response()->json([
                'status' => 'error',
                'message' => 'Produtos não encontrados no arquivo.',
                'error' => [],
                'response' => []
            ], 200);
        }

        $produto_desativados = '';

        if(!empty($fields['campanha_id'])){
            try{
                $id = decrypt($fields['campanha_id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ], 422);
            }

            $campanha_produtos = CampanhasProduto::whereIn('produto_codigo',$codigo)
            ->where('campanha_id',$id)
            ->whereNotNull('deleted_at')
            ->withTrashed()
            ->get();

            foreach($campanha_produtos as $produtos_desativados){
                $produto_desativados .= $produtos_desativados->produto_codigo.'<br>';
            }

        }

        $produtos = ProdutoEspecificacao::with(['estoque' => function ($query){
            $query->whereNull('campanha_id');
        }, 'reserva', 'segmento'])
        ->whereIn('codigo_produto',$codigo);

        $produtos->whereHas('estoque', function ($query){
            $query->whereNull('campanha_id');
        });

        $produtos->where('ativo', true);

        $produtos = $produtos->distinct()->get();
        
        $produtos_codigos = $produtos->unique('codigo_produto')->pluck('codigo_produto')->toArray();

        $itens_portal = collect();

        $pedido_portal = PedidoPortal::with(['itens_pedido'])
            ->whereNotIn('status_pedido', [3, 5, 7])
            ->whereHas('itens_pedido', function ($query) use ($produtos_codigos) {
                $query->whereIn("cod_produto", $produtos_codigos);
            })
            ->get();

        $pedido_portal->each(function ($query) use (&$itens_portal) {
            foreach ($query->itens_pedido as $itens) {
                $itens_portal->push([
                    'codigo_produto' =>  $itens->cod_produto,
                    'quantidade' => $itens->quantidade,
                    'estabelecimento' => str_pad($query->estabelecimento, 2, "0", STR_PAD_LEFT),
                ]);
            }
        });

        unset($pedido_portal);
        
        $retorno = [];

        $produtos->each(function ($query) use (&$retorno, $itens_portal, $fields) {
            $total_estoque_empresa = [];
            $total_estoque_produto = [];

            $total_estoque = 0;
            $compras = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $disponivel = (floatval($query->estoque->sum('estoque')) + floatval($query->estoque->sum('compras'))) - floatval($query->estoque->sum('empenho'));

            $compras[$query->codigo_produto] = $query->estoque->sum('compras');

            if ($disponivel != 0.0) {
                $total_estoque_empresa[$query->codigo_produto] = $disponivel;
            }

            foreach ($compras as $estabel => $compra) {
                if (isset($total_estoque_empresa[$query->codigo_produto]) || $compra === 0) {
                    continue;
                }

                $total_estoque_empresa[$query->codigo_produto] = $compra;
            }

            unset($compras);

            $query_itens = $itens_portal->where('codigo_produto', $query->codigo_produto);

            if (!empty($query_itens)) {
                foreach ($query_itens as $item) {

                    $quantidade = !isset($item->quantidade) ? $item['quantidade'] : $item->quantidade;
                    if (isset($total_estoque_empresa[$query->codigo_produto])) {
                        $total_estoque_empresa[$query->codigo_produto] -= $quantidade;
                    }
                }
            }

            foreach ($query->reserva as $reservas) {
                if (isset($total_estoque_empresa[$query->codigo_produto])) {
                    $total_estoque_empresa[$query->codigo_produto] -= $reservas->quantidade > 0 ? $reservas->quantidade : 0;
                }
            }

            $total_empresa = [];

            foreach ($total_estoque_empresa as $key_empresa => $value_empresa) {
                if ($value_empresa == 0) {
                    continue;
                }
                $total_estoque += $value_empresa;
            }

            if ($total_estoque < 0) {
                $total_estoque = 0;
            }

            $campanha_ativa = '';
            $mesma_campanha = false;
            $campanha_id = $query->estoque->max('campanha_id');
            
            $retorno[] = [
                'codigo' => str_replace([',', '|'], '', $query->codigo_produto),
                'grupo' => $query->grupo,
                'marca' => $query->marca,
                'linha' => $query->linha,
                'subgrupo' => $query->subgrupo,
                'descricao' => $query->descricao,
                'title_modal' => $query->codigo_produto . " - " . $query->grupo . " - " . $query->descricao,
                'ativo' => $campanha_ativa,
                'mesma_campanha' => $mesma_campanha,
                'total_popover' => $total_empresa,
                'estabelecimento_codigo' => '',
                'estabelecimento' => '',
                'segmento' => (!empty($query->produtoGrupo->segmento->descricao)) ?  $query->produtoGrupo->segmento->descricao : '',
                'estoque' => parserQtd($total_estoque),
                'campanha_nome' => (!empty($campanha_id)) ? $query->estoque[0]->campanha->nome : '',
                'campanha_id' => (!empty($campanha_id)) ? $campanha_id : '',
            ];
            
        });
        
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno,
                'produtos_desativados' => $produto_desativados
            ]
        ];

        return response()->json($return, 200);
    }

}
