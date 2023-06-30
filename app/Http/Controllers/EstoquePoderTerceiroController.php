<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Auth;
use Exception;
use Carbon\Carbon;

use App\ProdutosEstoque;
use App\EstoquePoderTerceiro;
use App\FornecedorNasajon;
use App\Preco;
use App\ProdutosCusto;
use App\AtualizacaoCron;

use App\Http\Requests\EstoquePoderTerceiroFiltroRequest;

class EstoquePoderTerceiroController extends Controller
{
    public $agrupar = ['fornecedor' => 'Fornecedor', 'produto' => 'Produto'];

    public function __construct() {
        $this->middleware(['auth']);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\EstoquePoderTerceiro") === false){
            return abort(403);
        };

        $request->session()->flash('model', 'App\EstoquePoderTerceiro');

        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'importacao:estoque_terceiro')->first();
        $atualizacao_movimentacao = Carbon::createFromFormat('Y-m-d H:i:s', $AtualizacaoCronObj->atualizacao);

        $horario = "Última Atualização: ".$atualizacao_movimentacao->format('d/m H:i');

    	return view("programs.estoque_poder_terceiro.index")->with(['agrupar' => $this->agrupar, 'horario' => $horario]);
    }

    public function filtro(EstoquePoderTerceiroFiltroRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','512M');

        $fields = $request->only('fornecedor','grupo','linha','marca','subgrupo','codigo');
        $estoque_terceiro =  EstoquePoderTerceiro::with('produtoDetalhe', 'custoPortal')->where('saldo_em_terceiro', '>', 0);
        
        $estoque_terceiro->whereHas('produtoDetalhe', function($query) use($fields){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if (!empty($fields['grupo'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$fields['grupo'].'%');
            }
            if (!empty( $fields['subgrupo'])){
                $query->where('subgrupo', 'ilike', '%'.$fields['subgrupo'].'%');
            }
            if (!empty( $fields['linha'])){
                $query->where('linha', 'ilike', '%'.$fields['linha'].'%');
            }
            if (!empty( $fields['marca'])){
                $query->where('marca', 'ilike','%'. $fields['marca'].'%');
            }
        });

        if((isset($fields['codigo']) && !empty($fields['codigo']))){
            if (!empty( $fields['codigo'])){
                $estoque_terceiro->where('produto_codigo', 'ilike', $fields['codigo']);
            }
        }

        if(!empty($fields['fornecedor'])){
            $FornecedorNasajonObj = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike', trim($fields['fornecedor']))->first();
            $estoque_terceiro->where('fornecedor_codigo', $FornecedorNasajonObj->codigo);
        }
        
        $estoque_terceiro = $estoque_terceiro->get();
        $retorno = [];
        $total = [
            'KG' => 0,
            'metros' => 0,
            'outras_unidades' => 0,
            'custo_contabil_nasajon' => 0,
            'custo_medio_contabil_portal' => 0,
            'custo_medio_gerencial_portal' => 0,
            'custo_gerencial' => 0
        ];
        $teste = [];
        $estoque_terceiro->each(function($query) use (&$retorno,&$total,$fields, &$teste){
            $unidade = 'M';
            if(!empty($query->produtoDetalhe->unidade)){
                $unidade = strtoupper(trim($query->produtoDetalhe->unidade));
            }

            if(!isset($retorno[$query->fornecedor_codigo])){
                $retorno[$query->fornecedor_codigo] = [
                    'fornecedor' => $query->fornecedor_nome.' - '.$query->fornecedor_codigo,
                    'kg' => 0,
                    'metros' => 0,
                    'outras_unidades' => 0,
                    'custo_contabil_nasajon' => 0,
                    'custo_medio_contabil_portal' => 0,
                    'custo_medio_gerencial_portal' => 0,
                    'custo_gerencial' => 0,
                    'filtro' => encrypt([
                        'fields' => $fields,
                        'fornecedor_codigo' => $query->fornecedor_codigo
                    ])
                ];
            }
            $custo_contabil = $query->custoPortal->where('estabelecimento', $query->estabelecimento_codigo)->first();
            if(empty($custo_contabil)){
                $custo_contabil = 0;
            }else{
                $custo_contabil = $custo_contabil->custo_medio_contabil;
            }

            $retorno[$query->fornecedor_codigo]['kg'] += ($unidade == 'KG') ? $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['metros'] += (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) ? $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['outras_unidades'] += (!in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME']) && $unidade != 'KG') ? $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_medio_contabil_portal'] += ($custo_contabil > 0) ? $custo_contabil * $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro  : 0;

            $total['KG'] += ($unidade == 'KG') ? $query->saldo_em_terceiro : 0;
            $total['metros'] += (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) ? $query->saldo_em_terceiro : 0;
            $total['outras_unidades'] += (!in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME']) && $unidade != 'KG') ? $query->saldo_em_terceiro : 0;
            $total['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $total['custo_medio_contabil_portal'] += ($custo_contabil > 0) ? $custo_contabil * $query->saldo_em_terceiro : 0;
            $total['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $total['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro : 0;
        });

        foreach($retorno as $key => $valores){
            $retorno[$key]['kg'] = ($retorno[$key]['kg'] > 0) ? parserValor4CasasDecimais($retorno[$key]['kg']) : '';
            $retorno[$key]['metros'] = ($retorno[$key]['metros'] > 0) ? parserValor4CasasDecimais($retorno[$key]['metros']) : '';
            $retorno[$key]['outras_unidades'] = ($retorno[$key]['outras_unidades'] > 0) ? parserValor4CasasDecimais($retorno[$key]['outras_unidades']) : '';
            $retorno[$key]['custo_contabil_nasajon'] = ($retorno[$key]['custo_contabil_nasajon'] > 0) ? parserValor($retorno[$key]['custo_contabil_nasajon']) : '';
            $retorno[$key]['custo_medio_contabil_portal'] = ($retorno[$key]['custo_medio_contabil_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_contabil_portal']) : '';
            $retorno[$key]['custo_medio_gerencial_portal'] = ($retorno[$key]['custo_medio_gerencial_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_gerencial_portal']) : '';
            $retorno[$key]['custo_gerencial'] = ($retorno[$key]['custo_gerencial']) ? parserValor($retorno[$key]['custo_gerencial']) : '';
        }

        $total['KG'] = ($total['KG'] > 0) ? parserValor4CasasDecimais($total['KG']) : '';
        $total['metros'] = ($total['metros'] > 0) ? parserValor4CasasDecimais($total['metros']) : '';
        $total['outras_unidades'] = ($total['outras_unidades'] > 0) ? parserValor4CasasDecimais($total['outras_unidades']) : '';
        $total['custo_contabil_nasajon'] = ($total['custo_contabil_nasajon'] > 0) ? parserQtd($total['custo_contabil_nasajon']) : '';
        $total['custo_medio_contabil_portal'] = ($total['custo_medio_contabil_portal'] > 0) ? parserQtd($total['custo_medio_contabil_portal']) : '';
        $total['custo_medio_gerencial_portal'] = ($total['custo_medio_gerencial_portal'] > 0) ? parserQtd($total['custo_medio_gerencial_portal']) : '';
        $total['custo_gerencial'] = ($total['custo_gerencial'] > 0) ? parserQtd($total['custo_gerencial']) : '';
        
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'total' => $total,
                'retorno' => $retorno
            ],      
        ];
        
        return $return;
    }

    public function modalProdutos(Request $request){
        $fields = $request->only('filtro');

        try{
            $fields = decrypt($fields['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $campos = $fields['fields'];

        $estoque_terceiro =  EstoquePoderTerceiro::with('produtoDetalhe')->where('saldo_em_terceiro', '>', 0);

        if($fields['fornecedor_codigo'] != 'Todos'){
            $estoque_terceiro->where('fornecedor_codigo',$fields['fornecedor_codigo']);
        }

        if(
            (isset($campos['grupo']) && !empty($campos['grupo'])) ||
            (isset($campos['subgrupo']) && !empty($campos['subgrupo'])) ||
            (isset($campos['linha']) && !empty($campos['linha'])) ||
            (isset($campos['marca']) && !empty($campos['marca']))
        ){
            $estoque_terceiro->whereHas('produtoDetalhe', function($query) use($campos){
                $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
                if (!empty($campos['grupo'])){
                    $query->where('produto_grupos.descricao', 'ilike', '%'.$campos['grupo'].'%');
                }
                if (!empty( $campos['subgrupo'])){
                    $query->where('subgrupo', 'ilike', '%'.$campos['subgrupo'].'%');
                }
                if (!empty( $campos['linha'])){
                    $query->where('linha', 'ilike', '%'.$campos['linha'].'%');
                }
                if (!empty( $campos['marca'])){
                    $query->where('marca', 'ilike','%'. $campos['marca'].'%');
                }
            });
        }

        if((isset($campos['codigo']) && !empty($campos['codigo']))){
            if (!empty( $campos['codigo'])){
                $estoque_terceiro->where('produto_codigo', 'ilike', $campos['codigo']);
            }
        }

        $estoque_terceiro  = $estoque_terceiro->get();
        $retorno = [];
        $total = [
            'KG' => 0,
            'metros' => 0,
            'outras_unidades' => 0,
            'custo_contabil_nasajon' => 0,
            'custo_medio_contabil_portal' => 0,
            'custo_medio_gerencial_portal' => 0,
            'custo_gerencial' => 0
        ];

        $estoque_terceiro->each(function($query) use (&$retorno,&$total){
            $unidade = 'M';
            if(!empty($query->produtoDetalhe->unidade)){
                $unidade = strtoupper(trim($query->produtoDetalhe->unidade));
            }

            if(!isset($retorno[$query->produto_codigo.$query->estabelecimento_codigo])){

                $retorno[$query->produto_codigo.$query->estabelecimento_codigo] = [
                    'produto' => $query->produto_nome,
                    'codigo' => $query->produto_codigo,
                    'grupo' =>!empty( $query->produtoDetalhe->grupo) ? $query->produtoDetalhe->grupo:'NÃO CADASTRADO',
                    'linha' =>!empty( $query->produtoDetalhe->linha) ? $query->produtoDetalhe->linha:'CONSUMO',
                    'subgrupo' =>!empty( $query->produtoDetalhe->subgrupo) ? $query->produtoDetalhe->subgrupo:'NÃO CADASTRADO', 
                    'marca' =>!empty( $query->produtoDetalhe->marca) ? $query->produtoDetalhe->marca:'NÃO CADASTRADO', 
                    'kg' => 0,
                    'metros' => 0,
                    'outras_unidades' => 0,
                    'custo_contabil_nasajon' => 0,
                    'custo_medio_contabil_portal' =>  0,
                    'custo_medio_gerencial_portal' =>  0,
                    'custo_gerencial' =>  0,
                ];
            }
            
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['kg'] += ($unidade == 'KG') ? $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['metros'] += (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) ? $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['outras_unidades'] += (!in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME']) && $unidade != 'KG') ? $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['custo_medio_contabil_portal'] += ($query->valor_custo_contabil_portal > 0) ? $query->valor_custo_contabil_portal * $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo.$query->estabelecimento_codigo]['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro : 0;

            $total['KG'] += ($unidade == 'KG') ? $query->saldo_em_terceiro : 0;
            $total['metros'] += (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) ? $query->saldo_em_terceiro : 0;
            $total['outras_unidades'] += (!in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME']) && $unidade != 'KG') ? $query->saldo_em_terceiro : 0;
            $total['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $total['custo_medio_contabil_portal'] += ($query->valor_custo_contabil_portal > 0) ? $query->valor_custo_contabil_portal * $query->saldo_em_terceiro : 0;
            $total['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $total['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro : 0;
        });

        foreach($retorno as $key => $valores){
            $retorno[$key]['kg'] = ($retorno[$key]['kg'] > 0) ? parserValor4CasasDecimais($retorno[$key]['kg']) : '';
            $retorno[$key]['metros'] = ($retorno[$key]['metros'] > 0) ? parserValor4CasasDecimais($retorno[$key]['metros']) : '';
            $retorno[$key]['outras_unidades'] = ($retorno[$key]['outras_unidades'] > 0) ? parserValor4CasasDecimais($retorno[$key]['outras_unidades']) : '';
            $retorno[$key]['custo_contabil_nasajon'] = ($retorno[$key]['custo_contabil_nasajon'] > 0) ? parserValor($retorno[$key]['custo_contabil_nasajon']) : '';
            $retorno[$key]['custo_medio_contabil_portal'] = ($retorno[$key]['custo_medio_contabil_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_contabil_portal']) : '';
            $retorno[$key]['custo_medio_gerencial_portal'] = ($retorno[$key]['custo_medio_gerencial_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_gerencial_portal']) : '';
            $retorno[$key]['custo_gerencial'] = ($retorno[$key]['custo_gerencial']) ? parserValor($retorno[$key]['custo_gerencial']) : '';
        }
        
        $total['KG'] = ($total['KG'] > 0) ? parserValor4CasasDecimais($total['KG']) : '';
        $total['metros'] = ($total['metros'] > 0) ? parserValor4CasasDecimais($total['metros']) : '';
        $total['outras_unidades'] = ($total['outras_unidades'] > 0) ? parserValor4CasasDecimais($total['outras_unidades']) : '';
        $total['custo_contabil_nasajon'] = ($total['custo_contabil_nasajon'] > 0) ? parserQtd($total['custo_contabil_nasajon']) : '';
        $total['custo_medio_contabil_portal'] = ($total['custo_medio_contabil_portal'] > 0) ? parserQtd($total['custo_medio_contabil_portal']) : '';
        $total['custo_medio_gerencial_portal'] = ($total['custo_medio_gerencial_portal'] > 0) ? parserQtd($total['custo_medio_gerencial_portal']) : '';
        $total['custo_gerencial'] = ($total['custo_gerencial'] > 0) ? parserQtd($total['custo_gerencial']) : '';

        return view('programs.estoque_poder_terceiro.modal.produto')->with(['response' => $retorno,'total' => $total, 'fornecedor_codigo' => $fields['fornecedor_codigo']]);
    }

    public function importarEstoqueTerceiro(){
        set_time_limit(12000);
        ini_set('memory_limit','1024M');

        $estoque_terceiro_nasajon = [
            '05' => [],
            '06' => []
        ];
        $array_saida = [];
        $produto_codigo_Array = [];
        $array_excluir = [];

        try{
            $estoque_terceiro_nasajon['05'] = DB::connection("nasajon")->select("select integracoes.exportar_produtos_saldos_de_terceiros('05')");
            $estoque_terceiro_nasajon['06'] = DB::connection("nasajon")->select("select integracoes.exportar_produtos_saldos_de_terceiros('06')");
        }catch(\Illuminate\Database\QueryException $e){
            throw new Exception(vsprintf(str_replace(['?'], ['\'%s\''], $e->getSql()), $e->getBindings()));
        }

        foreach($estoque_terceiro_nasajon as $estoque){
            foreach($estoque as $valores){
                $produto_nome = '';
                $fornecedor_nome = '';
                $string_valores = $valores->exportar_produtos_saldos_de_terceiros;
                if(preg_match('/"([^"]+)"/', $string_valores, $m)) {
                    $produto_nome = $m[0];
                }

                $string_valores = str_replace($produto_nome, "", $string_valores);

                if(preg_match('/"([^"]+)"/', $string_valores, $m)) {
                    $fornecedor_nome = $m[0];
                }

                $strings_remover = ['(',')'];
                $string_valores = str_replace($strings_remover,"",str_replace($fornecedor_nome, "", $string_valores));
                $string_valores = str_replace(",,",",",$string_valores);
                $array_valores = explode(",",$string_valores);
                $produto_codigo_Array[] = $array_valores[1];

                if(empty($fornecedor_nome)){
                    $fornecedor_nome = $produto_nome;
                    $produto_nome = $array_valores[2];
                }

                $fornecedor_codigo = (isset($array_valores[4])) ? $array_valores[3] : $array_valores[2];

                $array_excluir[] = $array_valores[0].' - '.$array_valores[1].' - '.$fornecedor_codigo;
                $array_saida[] = [
                    'estabelecimento_codigo' => $array_valores[0],
                    'produto_codigo' => $array_valores[1],
                    'produto_nome' => str_replace('"',"",$produto_nome),
                    'fornecedor_codigo' => $fornecedor_codigo,
                    'fornecedor_nome' => str_replace('"',"",$fornecedor_nome),
                    'saldo_em_terceiro' => (isset($array_valores[4])) ? $array_valores[4] : $array_valores[3],
                ];
            }
        }

        unset($estoque_terceiro_nasajon);

        $precos = Preco::whereIn('codigo_produto',$produto_codigo_Array)->get();
        $produtos_custos = ProdutosCusto::whereIn('estabelecimento',['05','06'])->whereIn('produto_codigo',$produto_codigo_Array)->get();

        $deletar_produtos = EstoquePoderTerceiro::whereNotIn(DB::raw('CONCAT(estabelecimento_codigo, \' - \', produto_codigo, \' - \',fornecedor_codigo)'),$array_excluir);
        $deletar_produtos->delete();

        foreach($array_saida as $saida){
            $verifica_produto_existente = EstoquePoderTerceiro::where('estabelecimento_codigo',$saida['estabelecimento_codigo'])
            ->where('produto_codigo',$saida['produto_codigo'])
            ->where('fornecedor_codigo',$saida['fornecedor_codigo'])
            ->first();

            $produtos_custos_portal = $produtos_custos->where('estabelecimento',$saida['estabelecimento_codigo'])->where('produto_codigo',$saida['produto_codigo'])->first();
            
            if(empty($verifica_produto_existente)){
                $estoque_terceiro = new EstoquePoderTerceiro;
                $estoque_terceiro->estabelecimento_codigo = $saida['estabelecimento_codigo'];
                $estoque_terceiro->produto_codigo = $saida['produto_codigo'];
                $estoque_terceiro->produto_nome = $saida['produto_nome'];
                $estoque_terceiro->fornecedor_codigo = $saida['fornecedor_codigo'];
                $estoque_terceiro->fornecedor_nome = $saida['fornecedor_nome'];
                $estoque_terceiro->saldo_em_terceiro = $saida['saldo_em_terceiro'];
                $estoque_terceiro->valor_custo_contabil_nasajon = 0;
                $estoque_terceiro->valor_custo_gerencial = 0;
                $estoque_terceiro->valor_custo_contabil_portal = (!empty($produtos_custos_portal)) ? $produtos_custos_portal->custo_medio_contabil : 0;
                $estoque_terceiro->valor_custo_gerencial_portal = 0;

                try{
                    $estoque_terceiro->save();
                }catch(\Illuminate\Database\QueryException $e){
                    throw new Exception(vsprintf(str_replace(['?'], ['\'%s\''], $e->getSql()), $e->getBindings()));
                }

            }else{
                $verifica_produto_existente->estabelecimento_codigo = $saida['estabelecimento_codigo'];
                $verifica_produto_existente->produto_codigo = $saida['produto_codigo'];
                $verifica_produto_existente->produto_nome = $saida['produto_nome'];
                $verifica_produto_existente->fornecedor_codigo = $saida['fornecedor_codigo'];
                $verifica_produto_existente->fornecedor_nome = $saida['fornecedor_nome'];
                $verifica_produto_existente->saldo_em_terceiro = $saida['saldo_em_terceiro'];
                $verifica_produto_existente->valor_custo_contabil_nasajon = 0;
                $verifica_produto_existente->valor_custo_gerencial = 0;
                $verifica_produto_existente->valor_custo_contabil_portal = (!empty($produtos_custos_portal)) ? $produtos_custos_portal->custo_medio_contabil : 0;
                $verifica_produto_existente->valor_custo_gerencial_portal = 0;

                try{
                    $verifica_produto_existente->save();
                }catch(\Illuminate\Database\QueryException $e){
                    throw new Exception(vsprintf(str_replace(['?'], ['\'%s\''], $e->getSql()), $e->getBindings()));
                }
            }
        }

    }

    public function filtroProduto(EstoquePoderTerceiroFiltroRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','512M');

        $fields = $request->only('fornecedor','grupo','linha','marca','subgrupo','codigo');
        $estoque_terceiro =  EstoquePoderTerceiro::with(['produtoDetalhe.produtoGrupo', 'custoPortal', 'estoque' => function($query){
            $query->whereIn('estabelecimento', ['05', '06']);
            $query->where('saldo_em_terceiros', '>', 0);
        }])->where('saldo_em_terceiro', '>', 0);
        
        $estoque_terceiro->whereHas('produtoDetalhe', function($query) use($fields){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if (!empty($fields['grupo'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$fields['grupo'].'%');
            }
            if (!empty( $fields['subgrupo'])){
                $query->where('subgrupo', 'ilike', '%'.$fields['subgrupo'].'%');
            }
            if (!empty( $fields['linha'])){
                $query->where('linha', 'ilike', '%'.$fields['linha'].'%');
            }
            if (!empty( $fields['marca'])){
                $query->where('marca', 'ilike','%'. $fields['marca'].'%');
            }
        });

        if((isset($fields['codigo']) && !empty($fields['codigo']))){
            if (!empty( $fields['codigo'])){
                $estoque_terceiro->where('produto_codigo', 'ilike', $fields['codigo']);
            }
        }

        if(!empty($fields['fornecedor'])){
            $FornecedorNasajonObj = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike', trim($fields['fornecedor']))->first();
            $estoque_terceiro->where('fornecedor_codigo', $FornecedorNasajonObj->codigo);
        }
        
        $estoque_terceiro = $estoque_terceiro->get();
        $retorno = [];
        $total = [
            'portal' => 0,
            'nasajon' => 0,
            'diferenca' => 0,
            'custo_contabil_nasajon' => 0,
            'custo_medio_contabil_portal' => 0,
            'custo_medio_gerencial_portal' => 0,
            'custo_gerencial' => 0
        ];
        $teste = [];
        $estoque_terceiro->each(function($query) use (&$retorno,&$total,$fields, &$teste){
            $unidade = 'M';
            if(!empty($query->produtoDetalhe->unidade)){
                $unidade = strtoupper(trim($query->produtoDetalhe->unidade));
            }

            if(!isset($retorno[$query->produto_codigo])){
                $retorno[$query->produto_codigo] = [
                    'link_movimento' =>'<a href="#" class="bt-estoque" data-url="'.route('produto.movimento_estoque.movimento_portal_terceiro').'" data-title="Movimento de Estoque - '.$query->produto_codigo.' - '.(empty($query->produtoDetalhe->produtoGrupo)? '' : $query->produtoDetalhe->produtoGrupo->descricao).' - '.(empty($query->produtoDetalhe)? '' : $query->produtoDetalhe->descricao).'" data-estabel="05" data-codigo="'.$query->produto_codigo.'" data-inicial="2018-07-01" data-fim="'.date('Y-m-d').'" data-toggle="tooltip" data-placement="top" title="Movimento de Estoque" onclick="showModalMovimentoEstoqueProduto($(this))"></a>'.(empty($query->produtoDetalhe)? '' : $query->produtoDetalhe->descricao),
                    'codigo' => $query->produto_codigo,
                    'produto' => empty($query->produtoDetalhe)? '' : $query->produtoDetalhe->descricao,
                    'grupo' =>  empty($query->produtoDetalhe->produtoGrupo)? '' : $query->produtoDetalhe->produtoGrupo->descricao,
                    'linha' => empty($query->produtoDetalhe)? '' : $query->produtoDetalhe->linha,
                    'marca' => empty($query->produtoDetalhe)? '' : $query->produtoDetalhe->marca,
                    'portal' => 0,
                    'nasajon' => 0,
                    'diferenca' => 0,
                    'custo_contabil_nasajon' => 0,
                    'custo_medio_contabil_portal' => 0,
                    'custo_medio_gerencial_portal' => 0,
                    'custo_gerencial' => 0,
                    'filtro' => encrypt([
                        'fields' => $fields,
                        'produto_codigo' => $query->produto_codigo
                    ])
                ];
            }
            $custo_contabil = $query->custoPortal->where('estabelecimento', $query->estabelecimento_codigo)->first();
            if(empty($custo_contabil)){
                $custo_contabil = 0;
            }else{
                $custo_contabil = $custo_contabil->custo_medio_contabil;
            }

            $retorno[$query->produto_codigo]['portal'] += $query->estoque->sum('saldo_em_terceiros');
            $retorno[$query->produto_codigo]['nasajon'] +=  $query->saldo_em_terceiro;
            $retorno[$query->produto_codigo]['diferenca'] += $query->estoque->sum('saldo_em_terceiros') - $query->saldo_em_terceiro;
            $retorno[$query->produto_codigo]['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo]['custo_medio_contabil_portal'] += ($custo_contabil > 0) ? $custo_contabil * $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo]['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $retorno[$query->produto_codigo]['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro  : 0;

            $total['portal'] += $query->estoque->sum('saldo_em_terceiros');
            $total['nasajon'] += $query->saldo_em_terceiro;
            $total['diferenca'] += $query->estoque->sum('saldo_em_terceiros') - $query->saldo_em_terceiro;
            $total['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $total['custo_medio_contabil_portal'] += ($custo_contabil > 0) ? $custo_contabil * $query->saldo_em_terceiro : 0;
            $total['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $total['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro : 0;
        });

        foreach($retorno as $key => $valores){
            $retorno[$key]['portal'] = ($retorno[$key]['portal'] == 0) ? '' : parserValor4CasasDecimais($retorno[$key]['portal']);
            $retorno[$key]['nasajon'] = ($retorno[$key]['nasajon'] == 0) ? '' : parserValor4CasasDecimais($retorno[$key]['nasajon']);
            $retorno[$key]['diferenca'] = ($retorno[$key]['diferenca'] == 0) ? '' : parserValor4CasasDecimais($retorno[$key]['diferenca']);
            $retorno[$key]['custo_contabil_nasajon'] = ($retorno[$key]['custo_contabil_nasajon'] > 0) ? parserValor($retorno[$key]['custo_contabil_nasajon']) : '';
            $retorno[$key]['custo_medio_contabil_portal'] = ($retorno[$key]['custo_medio_contabil_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_contabil_portal']) : '';
            $retorno[$key]['custo_medio_gerencial_portal'] = ($retorno[$key]['custo_medio_gerencial_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_gerencial_portal']) : '';
            $retorno[$key]['custo_gerencial'] = ($retorno[$key]['custo_gerencial']) ? parserValor($retorno[$key]['custo_gerencial']) : '';
        }

        $total['portal'] = ($total['portal'] == 0) ? '' : parserValor4CasasDecimais($total['portal']);
        $total['nasajon'] = ($total['nasajon'] == 0) ? '' : parserValor4CasasDecimais($total['nasajon']);
        $total['diferenca'] = ($total['diferenca'] == 0) ? '' : parserValor4CasasDecimais($total['diferenca']);
        $total['custo_contabil_nasajon'] = ($total['custo_contabil_nasajon'] > 0) ? parserQtd($total['custo_contabil_nasajon']) : '';
        $total['custo_medio_contabil_portal'] = ($total['custo_medio_contabil_portal'] > 0) ? parserQtd($total['custo_medio_contabil_portal']) : '';
        $total['custo_medio_gerencial_portal'] = ($total['custo_medio_gerencial_portal'] > 0) ? parserQtd($total['custo_medio_gerencial_portal']) : '';
        $total['custo_gerencial'] = ($total['custo_gerencial'] > 0) ? parserQtd($total['custo_gerencial']) : '';
        
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'total' => $total,
                'retorno' => $retorno
            ],      
        ];
        
        return $return;
    }

    public function modalFornecedor(Request $request){
        $fields = $request->only('filtro', 'codigo');
        $codigo = $fields['codigo'];
        try{
            $fields = decrypt($fields['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $campos = $fields['fields'];

        $estoque_terceiro =  EstoquePoderTerceiro::with('produtoDetalhe')->where('saldo_em_terceiro', '>', 0);

        if(!empty($campos['fornecedor'])){
            $FornecedorNasajonObj = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike', trim($campos['fornecedor']))->first();
            $estoque_terceiro->where('fornecedor_codigo', $FornecedorNasajonObj->codigo);
        }
        if(
            (isset($campos['grupo']) && !empty($campos['grupo'])) ||
            (isset($campos['subgrupo']) && !empty($campos['subgrupo'])) ||
            (isset($campos['linha']) && !empty($campos['linha'])) ||
            (isset($campos['marca']) && !empty($campos['marca']))
        ){
            $estoque_terceiro->whereHas('produtoDetalhe', function($query) use($campos){
                $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
                if (!empty($campos['grupo'])){
                    $query->where('produto_grupos.descricao', 'ilike', '%'.$campos['grupo'].'%');
                }
                if (!empty( $campos['subgrupo'])){
                    $query->where('subgrupo', 'ilike', '%'.$campos['subgrupo'].'%');
                }
                if (!empty( $campos['linha'])){
                    $query->where('linha', 'ilike', '%'.$campos['linha'].'%');
                }
                if (!empty( $campos['marca'])){
                    $query->where('marca', 'ilike','%'. $campos['marca'].'%');
                }
            });
        }

        $estoque_terceiro->where('produto_codigo', 'ilike', $codigo);
        $estoque_terceiro  = $estoque_terceiro->get();
        $retorno = [];
        $total = [
            'KG' => 0,
            'metros' => 0,
            'outras_unidades' => 0,
            'custo_contabil_nasajon' => 0,
            'custo_medio_contabil_portal' => 0,
            'custo_medio_gerencial_portal' => 0,
            'custo_gerencial' => 0
        ];

        $estoque_terceiro->each(function($query) use (&$retorno,&$total){
            $unidade = 'M';
            if(!empty($query->produtoDetalhe->unidade)){
                $unidade = strtoupper(trim($query->produtoDetalhe->unidade));
            }

            if(!isset($retorno[$query->fornecedor_codigo])){
                $retorno[$query->fornecedor_codigo] = [
                    'fornecedor' => $query->fornecedor_nome.' - '.$query->fornecedor_codigo,
                    'kg' => 0,
                    'metros' => 0,
                    'outras_unidades' => 0,
                    'custo_contabil_nasajon' => 0,
                    'custo_medio_contabil_portal' =>  0,
                    'custo_medio_gerencial_portal' =>  0,
                    'custo_gerencial' =>  0,
                ];
            }
            
            $retorno[$query->fornecedor_codigo]['kg'] += ($unidade == 'KG') ? $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['metros'] += (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) ? $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['outras_unidades'] += (!in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME']) && $unidade != 'KG') ? $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_medio_contabil_portal'] += ($query->valor_custo_contabil_portal > 0) ? $query->valor_custo_contabil_portal * $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $retorno[$query->fornecedor_codigo]['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro : 0;

            $total['KG'] += ($unidade == 'KG') ? $query->saldo_em_terceiro : 0;
            $total['metros'] += (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) ? $query->saldo_em_terceiro : 0;
            $total['outras_unidades'] += (!in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME']) && $unidade != 'KG') ? $query->saldo_em_terceiro : 0;
            $total['custo_contabil_nasajon'] += ($query->valor_custo_contabil_nasajon > 0) ? $query->valor_custo_contabil_nasajon * $query->saldo_em_terceiro : 0;
            $total['custo_medio_contabil_portal'] += ($query->valor_custo_contabil_portal > 0) ? $query->valor_custo_contabil_portal * $query->saldo_em_terceiro : 0;
            $total['custo_medio_gerencial_portal'] += ($query->valor_custo_gerencial_portal > 0) ? $query->valor_custo_gerencial_portal * $query->saldo_em_terceiro : 0;
            $total['custo_gerencial'] += ($query->valor_custo_gerencial > 0) ? $query->valor_custo_gerencial * $query->saldo_em_terceiro : 0;
        });

        foreach($retorno as $key => $valores){
            $retorno[$key]['kg'] = ($retorno[$key]['kg'] > 0) ? parserValor4CasasDecimais($retorno[$key]['kg']) : '';
            $retorno[$key]['metros'] = ($retorno[$key]['metros'] > 0) ? parserValor4CasasDecimais($retorno[$key]['metros']) : '';
            $retorno[$key]['outras_unidades'] = ($retorno[$key]['outras_unidades'] > 0) ? parserValor4CasasDecimais($retorno[$key]['outras_unidades']) : '';
            $retorno[$key]['custo_contabil_nasajon'] = ($retorno[$key]['custo_contabil_nasajon'] > 0) ? parserValor($retorno[$key]['custo_contabil_nasajon']) : '';
            $retorno[$key]['custo_medio_contabil_portal'] = ($retorno[$key]['custo_medio_contabil_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_contabil_portal']) : '';
            $retorno[$key]['custo_medio_gerencial_portal'] = ($retorno[$key]['custo_medio_gerencial_portal'] > 0) ? parserValor($retorno[$key]['custo_medio_gerencial_portal']) : '';
            $retorno[$key]['custo_gerencial'] = ($retorno[$key]['custo_gerencial']) ? parserValor($retorno[$key]['custo_gerencial']) : '';
        }
        
        $total['KG'] = ($total['KG'] > 0) ? parserValor4CasasDecimais($total['KG']) : '';
        $total['metros'] = ($total['metros'] > 0) ? parserValor4CasasDecimais($total['metros']) : '';
        $total['outras_unidades'] = ($total['outras_unidades'] > 0) ? parserValor4CasasDecimais($total['outras_unidades']) : '';
        $total['custo_contabil_nasajon'] = ($total['custo_contabil_nasajon'] > 0) ? parserQtd($total['custo_contabil_nasajon']) : '';
        $total['custo_medio_contabil_portal'] = ($total['custo_medio_contabil_portal'] > 0) ? parserQtd($total['custo_medio_contabil_portal']) : '';
        $total['custo_medio_gerencial_portal'] = ($total['custo_medio_gerencial_portal'] > 0) ? parserQtd($total['custo_medio_gerencial_portal']) : '';
        $total['custo_gerencial'] = ($total['custo_gerencial'] > 0) ? parserQtd($total['custo_gerencial']) : '';

        return view('programs.estoque_poder_terceiro.modal.fornecedor')->with(['response' => $retorno,'total' => $total]);
    }
}
