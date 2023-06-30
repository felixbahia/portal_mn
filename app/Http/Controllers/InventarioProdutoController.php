<?php

namespace App\Http\Controllers;
use Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\InventarioHistorico;
use App\InventarioHistoricoProduto;
use App\InventarioHistoricoProdutoPeca;
use App\InventarioProduto;
use App\FracaoNasajon;
use App\ProdutosEstoque;
use App\LocalDeEstoqueEnderecoNasajon;
use App\NasajonEstabelecimento;
use App\ProdutoEspecificacao;

use App\Http\Controllers\EmailController;

class InventarioProdutoController extends Controller
{
    private $estabelecimentos_erp = [1, 2];
    public function index(Request $request){
        
        if(Auth::user()->hasPermissionTo("programas App\AnaliseDeInventario") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnaliseDeInventario');

        $coletores = [];
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[20]);
        return view("programs.inventario.index")->with(['coletores' => $coletores, 'estabelecimentos' => $estabelecimentos ]);
    }

    public function filtro(Request $request){
        $fields = $request->only(['estabelecimento', 'usuario']);
        
        $empresa = returnEmpresasNasajonView();
        $usuario = "";
        $InventarioProdutoObj = InventarioProduto::select('estabelecimento', 'contagem', DB::raw('count(*) as quantidade'))->groupBy('estabelecimento', 'contagem');
        $InventarioProdutoCodigoBarrasObj = InventarioProduto::select('estabelecimento', 'contagem', DB::raw('SUM(CASE WHEN quantidade_produto != \'\' THEN CAST(quantidade_produto AS float) ELSE 1 END) as quantidade'))->groupBy('estabelecimento', 'contagem');
        $InventarioProdutoObj->where('produto_codigobarras', false);
        $InventarioProdutoCodigoBarrasObj->where('produto_codigobarras', true);
        if(strlen($fields['estabelecimento']) > 0){
            $InventarioProdutoObj->where('estabelecimento', $fields['estabelecimento']);
            $InventarioProdutoCodigoBarrasObj->where('estabelecimento', $fields['estabelecimento']);
        }
        if(strlen($fields['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $fields['usuario']);
            $InventarioProdutoCodigoBarrasObj->where('created_by', $fields['usuario']);
            $usuario = "";
        }

		if(Auth::id() == 21){
			$InventarioProdutoObj->whereIn('estabelecimento', ['3', '4']);
			$InventarioProdutoCodigoBarrasObj->whereIn('estabelecimento', ['3', '4']);
		}
		if(Auth::id() == 45 || Auth::id() == 577 || Auth::id() == 557){
			$InventarioProdutoObj->whereIn('estabelecimento', ['5']);
			$InventarioProdutoCodigoBarrasObj->whereIn('estabelecimento', ['5']);
		}
		if(Auth::id() == 18 || Auth::id() == 604){
			$InventarioProdutoObj->whereIn('estabelecimento', ['8']);
			$InventarioProdutoCodigoBarrasObj->whereIn('estabelecimento', ['8']);
		}

        $inventarioProduto = $InventarioProdutoObj->get();
        $inventarioProdutoCodigoBarras = $InventarioProdutoCodigoBarrasObj->get();

        $retorno = [];
        foreach ($inventarioProduto as $key => $inventario) {
            if(!isset($retorno[intval($inventario->estabelecimento)])){
                $retorno[intval($inventario->estabelecimento)] = [
                    'criterios' => encrypt(["estabelecimento" => $inventario->estabelecimento, "usuario" => $usuario]),
                    'estabelecimento' => $empresa[intval($inventario->estabelecimento)],
                    'estabelecimento_codigo' => $inventario->estabelecimento,
                    'contagem1' => 0,
                    'contagem2' => 0,
                ];
            }
            $retorno[intval($inventario->estabelecimento)]['contagem'.$inventario->contagem] = $inventario->quantidade;
        }
        foreach ($inventarioProdutoCodigoBarras as $key => $inventario) {
            if(!isset($retorno[intval($inventario->estabelecimento)])){
                $retorno[intval($inventario->estabelecimento)] = [
                    'criterios' => encrypt(["estabelecimento" => $inventario->estabelecimento, "usuario" => $usuario]),
                    'estabelecimento' => $empresa[intval($inventario->estabelecimento)],
                    'estabelecimento_codigo' => $inventario->estabelecimento,
                    'contagem1' => 0,
                    'contagem2' => 0,
                ];
            }
            $retorno[intval($inventario->estabelecimento)]['contagem'.$inventario->contagem] += $inventario->quantidade;
        }
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalContagem(Request $request){
        $fields = $request->only(['criterios', 'contagem']);
        
        $criterios = decrypt($fields['criterios']);

        $quantidade_erp = ProdutosEstoque::
            select('estoque AS quantidade', 'codigo_produto')
            ->whereRaw('estabelecimento = \'0'.$criterios['estabelecimento'].'\'');
        $InventarioProdutoObj = InventarioProduto::with('produtoNasajon')->selectRaw(
            'inventario_produtos.codigo_produto, '.
            'inventario_produtos.produto_codigobarras, '.
            'COUNT(inventario_produtos.codigo_barras) AS volumes_inventario, '.
            'SUM(CAST ((CASE WHEN inventario_produtos.quantidade_produto != \'\' THEN inventario_produtos.quantidade_produto ELSE \'0\' END) as float)) AS quantidade_inventario,'.
            'quantidades.quantidade,'.
            'min(inventario_produtos.created_at) as data_inicio'
        )
        ->groupBy('inventario_produtos.codigo_produto', 'inventario_produtos.produto_codigobarras', 'quantidades.quantidade')
        ->leftJoinSub($quantidade_erp, 'quantidades', function($join){
            $join->on('quantidades.codigo_produto', '=', 'inventario_produtos.codigo_produto');
        });
        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('contagem', $fields['contagem']);
        if(strlen($criterios['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $criterios['usuario']);
        }
        $inventario = $InventarioProdutoObj->get();
        $produtos = [];
        foreach ($inventario as $key => $value) {
            $estoque = [
                "volumes" => 0,
                "quantidade" => 0.0
            ];
            $atualizar_estoque = true;
            if(!is_null($value->produtoNasajon)){
                $codigo = $value->produtoNasajon->codigo;
                $descricao = $value->produtoNasajon->especificacao;
                $estoque['quantidade'] = $value['quantidade'];
                if($value->produto_codigobarras === false){
                    $volumes_erp = DB::connection('nasajon')->select("SELECT count(*) as quantidade FROM integracoes.exportar_fracoes_produtos('0".$criterios['estabelecimento']."','".$value->produtoNasajon->codigo."')");
                    $volumes_erp = (array) $volumes_erp[0];
                    $estoque['volumes'] = $volumes_erp['quantidade'] ?? 0;
                }
                $data_inicio = Carbon::createFromFormat('Y-m-d H:i:s', $value->data_inicio);
                if(
					!Auth::user()->hasRole('Administradores') &&
					(!in_array(Auth::id(), [21, 45]))
				){
                    $atualizar_estoque = false;
                }else if(MovimentoEstoqueController::checarSeTeveMovimento("0".$criterios['estabelecimento'], $value->codigo_produto, $data_inicio->format('Y-m-d')) === true ){
                    $atualizar_estoque = 'movimento';
                }
            }else{
                $atualizar_estoque = false;
                $codigo = "Sem Codigo";
                $descricao = "Produto não encontrado";
            }
            $diferenca = [
                "volumes" => intval($value->volumes_inventario) - intval($estoque['volumes']),
                "quantidade" => ((floatval($value->quantidade_inventario) - floatval($estoque['quantidade'])) > 0 ? "+" : "" ). parserQtd(floatval($value->quantidade_inventario) - floatval($estoque['quantidade'])),
            ];

            $estoque['quantidade'] = parserQtd($estoque['quantidade']);
            $produtos[] = [
                "produto" => [
                    "codigo" => $codigo,
                    "descricao" => $descricao
                ],
                "inventario" => [
                    "volumes" => intval($value->volumes_inventario),
                    "quantidade" => parserQtd($value->quantidade_inventario),
                ],
                "estoque" => $estoque,
                "diferenca" => $diferenca,
                "criterios" => encrypt(["codigo_produto" => $value->codigo_produto, "estabelecimento" => $criterios['estabelecimento'], "usuario" => $criterios['usuario'], 'contagem' => $fields['contagem']]),
                'atualizar_estoque' => $atualizar_estoque
            ];
            unset($inventario);


        }
        $InventarioProdutoObj = InventarioProduto::select('endereco', DB::raw('count(*) as quantidade'))->groupBy('endereco');

        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('contagem', $fields['contagem']);
        if(strlen($criterios['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $criterios['usuario']);
        }
        $inventario = $InventarioProdutoObj->get();
        $enderecos = [];
        foreach ($inventario as $key => $value) {
            $enderecos[] = [
                "endereco" => $value->endereco,
                "quantidade" => $value->quantidade
            ];
        }
        $InventarioProdutoObj = InventarioProduto::with('createdby')->select('created_by', DB::raw('count(*) as quantidade'))->groupBy('created_by');

        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('contagem', $fields['contagem']);
        if(strlen($criterios['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $criterios['usuario']);
        }
        $inventario = $InventarioProdutoObj->get();
        $operadores = [];
        foreach ($inventario as $key => $value) {
            $usuario = $value->createdby;
            $operadores[] = [
                "operador" => $usuario->name,
                "quantidade" => $value->quantidade
            ];
        }
        return view("programs.inventario.modal.dialog")->with(['produtos' =>  $produtos, 'enderecos' =>  $enderecos, 'operadores' =>  $operadores]);
    }

    public function modalProduto(Request $request){
        $fields = $request->only(['criterios']);
        
        $criterios = decrypt($fields['criterios']);

        $InventarioProdutoObj = InventarioProduto::select('*')->with(['createdby']);

        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('contagem', $criterios['contagem']);
        $InventarioProdutoObj->where('codigo_produto', $criterios['codigo_produto']);
        if(strlen($criterios['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $criterios['usuario']);
        }
        $inventario = $InventarioProdutoObj->get();
        $produtos = [];
        foreach ($inventario as $key => $produto) {
            $produtos[] = [
                'id' =>  encrypt($produto->id),
                "operador" => $produto->createdby->name,
                'codigo' => $produto->codigo_barras,
                'endereco' => $produto->endereco,
                'data_hora' => parserDataEHora($produto->created_at)
            ];
        }

        $produto = ['codigo' => $criterios['codigo_produto']];
        return view("programs.inventario.modal.produto")->with(['produtos' =>  $produtos, 'produto' => $produto]);
    }

    public function modalDiferenca(Request $request){
        $fields = $request->only(['criterios']);
        
        $criterios = decrypt($fields['criterios']);
        $estabelecimento = $criterios['estabelecimento'];
        $estabelecimento = str_pad($estabelecimento, 2, '0', STR_PAD_LEFT);
        $quantidade_erp = ProdutosEstoque::
            select('estoque', 'codigo_produto')
            ->where('estabelecimento', $estabelecimento)
            ->where('codigo_produto', $criterios['codigo_produto'])
            ->get();

        $InventarioProdutoObj = InventarioProduto::query()->with('createdby');
        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('contagem', $criterios['contagem']);
        $InventarioProdutoObj->where('codigo_produto', $criterios['codigo_produto']);
        if(strlen($criterios['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $criterios['usuario']);
        }
        $inventario = $InventarioProdutoObj->get();
        $estoque = 0;
        $volumes = [];

        $volumes_erp = [];
        if($inventario[0]->produto_codigobarras === false && $criterios['codigo_produto'] !== ''){
            $volumes_erp = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_fracoes_produtos('0".$criterios['estabelecimento']."','".$criterios['codigo_produto']."')");
            foreach ($volumes_erp as $key => $value) {
                $volumes[$value->fracao_codigo] = [
                    "volume" => $value->fracao_codigo,
                    "quantidade" => parserQtd($value->saldo),
                    "endereco_erp" => $value->local_de_estoque_endereco,
                    "endereco_inventario" => "",
                    "operador" => "",
                    "status" => [
                        "error" => true,
                        "onde" => "erp"
                    ]
                ];
            }
            unset($volumes_erp);
        }
        foreach($inventario as $key => $value){
            if(isset($volumes[$value->codigo_barras])){
                $volumes[$value->codigo_barras]["endereco_inventario"] = $value->endereco;
                $volumes[$value->codigo_barras]["status"] = [
                    "error" => false,
                    "onde" => ""
                ];
                $volumes[$value->codigo_barras]["operador"] = $value->createdby->name;
            }else{
                $volumes[$value->codigo_barras] = [
                    "volume" => $value->codigo_barras,
                    "quantidade" => parserQtd($value->quantidade_produto),
                    "endereco_erp" => "",
                    "endereco_inventario" => $value->endereco,
                    "operador" => $value->createdby->name,
                    "status" => [
                        "error" => true,
                        "onde" => "inventario"
                    ]
                ];
            }
        }
        unset($inventario);
        $quantidade_erp = reset($quantidade_erp);
        $estoque_disponivel = '';
        if(!empty($quantidade_erp)){
            $quantidade_erp = $quantidade_erp[0];
            $estoque_disponivel = parserQtd($quantidade_erp->estoque);
        }

        return view("programs.inventario.modal.diferenca")->with(['volumes' =>  $volumes, 'estoque_disponivel' => $estoque_disponivel]);
    }

    public static function diferencas($estabelecimento, $contagem){
        $criterios = [
            "estabelecimento" => $estabelecimento,
            "contagem" => $contagem
        ];
        $volumes_erp = DB::table('volumes_0'.$criterios['estabelecimento'])
            ->selectRaw('volumes_0'.$criterios['estabelecimento'].'.codprd as codigo_produto, COUNT(volumes_0'.$criterios['estabelecimento'].'.*) AS volumes_erp')
            ->whereNull('volumes_0'.$criterios['estabelecimento'].'.data_saida')
            ->groupBy('volumes_0'.$criterios['estabelecimento'].'.codprd')
        ;
        $quantidade_erp = DB::table('inventario_produtos_estoque')
            ->selectRaw('((inventario_produtos_estoque.est_prateleira + inventario_produtos_estoque.est_deposito) - inventario_produtos_estoque.empenho) AS quantidade_erp, inventario_produtos_estoque.codprd AS codigo_produto, inventario_produtos_estoque.pmedio_cicm as custo_medido')
            ->whereRaw('inventario_produtos_estoque.estabel = \'0'.$criterios['estabelecimento'].'\'')
        ;
        $InventarioProdutoObj = InventarioProduto::with('produto')->selectRaw(
            'inventario_produtos.codigo_produto, '.
            'COUNT(inventario_produtos.codigo_barras) AS volumes_inventario, '.
            'SUM(CAST ((CASE WHEN inventario_produtos.quantidade_produto != \'\' THEN inventario_produtos.quantidade_produto ELSE \'0\' END) as float)) AS quantidade_inventario,'.
            'volumes.volumes_erp,'.
            'quantidades.quantidade_erp, '.
            'quantidades.custo_medido'
        )
        ->groupBy('inventario_produtos.codigo_produto', 'volumes.volumes_erp', 'quantidades.quantidade_erp', 'quantidades.custo_medido')
        ->joinSub($volumes_erp, 'volumes', function($join){
            $join->on('volumes.codigo_produto', '=', 'inventario_produtos.codigo_produto');
        })
        ->joinSub($quantidade_erp, 'quantidades', function($join){
            $join->on('quantidades.codigo_produto', '=', 'inventario_produtos.codigo_produto');
        });
        $InventarioProdutoObj->where('inventario_produtos.estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('inventario_produtos.contagem', $criterios['contagem']);
        $InventarioProdutoObj->where('inventario_produtos.codigo_produto', "!=", "");

        $inventario = $InventarioProdutoObj->get();
        $return_diferencas = [];
        $return_diferencas[] = [
            "Marca",
            "Grupo",
            "Código",
            "Produto",
            "Quantidade estoque",
            "Quantidade invetariada",
            "Volumes estoque",
            "Volumes inventariada",
            "Ajuste Positivo",
            "Ajuste Negativo",
            "Custo Produto",
            "Valor Positivo",
            "Valor Negativo"
        ];
        foreach ($inventario as $key => $produto) {
            $diferenca = floatval(floatval($produto->quantidade_inventario) - floatval($produto->quantidade_erp));
            $ajuste_positivo = ($diferenca > 0) ? $diferenca : 0;
            $ajuste_negativo = ($diferenca < 0) ? $diferenca * -1 : 0;
            $custo_produto = $produto->custo_medido;
            $valor_positivo = ($diferenca > 0) ? ($diferenca * floatval($produto->custo_medido)) : 0;
            $valor_negativo = ($diferenca < 0) ? (($diferenca * -1) * floatval($produto->custo_medido)) : 0;
            if(floatval($diferenca) === 0.0 && intval($produto->volumes_inventario) == intval($produto->volumes_erp) ){
                continue;
            }
            $return_diferencas[] = [
                "marca" => utf8_encode($produto->produto->MARCA),
                "grupo" => utf8_encode($produto->produto->GRUPO),
                "codigo" => $produto->produto->CODPRD,
                "produto" => utf8_encode($produto->produto->DESCR),
                "quantidade_estoque" => parserQtd(floatval($produto->quantidade_erp)),
                "quantidade_invetariada" => parserQtd(floatval($produto->quantidade_inventario)),
                "volumes_estoque" => intval($produto->volumes_erp),
                "volumes_inventariada" => intval($produto->volumes_inventario),
                "ajuste_positivo" => parserQtd($ajuste_positivo),
                "ajuste_negativo" => parserQtd($ajuste_negativo),
                "custo_produto" => parserQtd($custo_produto),
                "valor_positivo" => parserQtd($valor_positivo),
                "valor_negativo" => parserQtd($valor_negativo),
            ];
        }
        unset($inventario);
        $name_file = "diferenca_inventario_".$criterios["estabelecimento"]."_contagem_".$criterios["contagem"].".csv";
        $lines = "";
        foreach ($return_diferencas as $key => $return) {
            $lines .= implode(";", $return)."\n";
        }
        Storage::put($name_file, $lines);
        return true;
    }

    public function aplicarEstoque(Request $request){
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '5000M');
        $fields = $request->only(['criterios']);
        if(empty($fields['criterios'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Nenhum produto selecionado',
                'error' => [],
                'response' => []
            ], 422);
        }
        $criterios_notparser = $fields['criterios'];
        $produtos = [];
        foreach ($criterios_notparser as $value) {
            $produtos[] = decrypt($value);
        }
        $contagem = $produtos[0]['contagem'];
        $estabelecimento = $produtos[0]['estabelecimento'];
        $estabelecimento = str_pad($estabelecimento, 2, '0', STR_PAD_LEFT);

        if(in_array(intval($estabelecimento), [3, 4])){
            $estabelecimentoNasajon_uuid = NasajonEstabelecimento::where('codigo', '20')->first()->estabelecimento;
        }else{
            $estabelecimentoNasajon_uuid = NasajonEstabelecimento::where('codigo', $estabelecimento)->first()->estabelecimento;
        }
        
        $sql_init_inventario = "select * from integracoes.api_inventario_fracao_novo(
        (select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}'),
        current_date
        );";

        try{
            $insert_inventario_nasajon = DB::connection('nasajon')->select($sql_init_inventario);
        }catch(\Exception $e){
            Log::error($e->getMessage());
            Log::error($sql_init_inventario);
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$e, $sql_init_inventario],
                'response' => []
            ], 422);
        }
        $mensagem_nasajon = $insert_inventario_nasajon[0]->mensagem;
        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            Log::error($mensagem_nasajon['mensagem']);
            Log::error($sql_init_inventario);
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem'], $sql_init_inventario],
                'response' => []
            ], 422);
        }
        $inventario_uuid = $mensagem_nasajon['mensagem'];

        $InventarioHistoricoObj = new InventarioHistorico();
        $InventarioHistoricoObj->inventario_nasajaon = $inventario_uuid;
        $InventarioHistoricoObj->estabelecimento = $estabelecimento;
        $InventarioHistoricoObj->contagem = $contagem;
        $InventarioHistoricoObj->data = date('Y-m-d H:i:s');
        $InventarioHistoricoObj->created_by = Auth::id();
        $InventarioHistoricoObj->save();

        foreach($produtos as $produto){
            $InventarioHistoricoProdutoObj = new InventarioHistoricoProduto();
            $InventarioHistoricoProdutoObj->inventario_historicos_id = $InventarioHistoricoObj->id;
            $InventarioHistoricoProdutoObj->created_by = Auth::id();
            $InventarioHistoricoProdutoObj->codigo_produto = $produto['codigo_produto'];

            $InventarioProdutoObj = InventarioProduto::with('produtoNasajon')->
                where('estabelecimento', $produto['estabelecimento'])->
                where('codigo_produto', $produto['codigo_produto'])->
                where('contagem', $produto['contagem'])->
                where('produto_codigobarras', true)->
                first();
            if(!empty($InventarioProdutoObj)){
                $InventarioHistoricoProdutoObj->codigo_barras = true;
                $InventarioHistoricoProdutoObj->quantidade = $InventarioProdutoObj->quantidade_produto;
                $InventarioHistoricoProdutoObj->save();

                $InventarioHistoricoProdutoPecaObj = new InventarioHistoricoProdutoPeca();
                $InventarioHistoricoProdutoPecaObj->inventario_historico_produtos_id = $InventarioHistoricoProdutoObj->id;
                $InventarioHistoricoProdutoPecaObj->endereco = $InventarioProdutoObj->endereco;
                $InventarioHistoricoProdutoPecaObj->quantidade = $InventarioProdutoObj->quantidade_produto;
                $InventarioHistoricoProdutoPecaObj->created_by = Auth::id();
                $InventarioHistoricoProdutoPecaObj->usuario_id = $InventarioProdutoObj->created_by;
                $InventarioHistoricoProdutoPecaObj->save();

                $produto_uuid = $InventarioProdutoObj->produtoNasajon->produto;
                $LocalDeEstoqueNasajonObj = LocalDeEstoqueEnderecoNasajon::where(function($query) use ($InventarioProdutoObj){
                    $query->
                        orWhere("endereco", $InventarioProdutoObj->endereco)->
                        orWhere("endereco_simplificado", $InventarioProdutoObj->endereco);
                })->where('estabelecimento', $estabelecimentoNasajon_uuid)->first();
                $endereco_uuid = $LocalDeEstoqueNasajonObj->localdeestoqueendereco;
                $lote_uuid = 'NULL';
                $quantidade = $InventarioProdutoObj->quantidade_produto;

                $sql_init_inventario_lote = "select * from integracoes.api_inventario_incluir_fracao(
                    '{$inventario_uuid}',
                    '{$produto_uuid}',
                    {$lote_uuid},
                    '{$endereco_uuid}',
                    {$quantidade}
                    );";
            
                try{
                    $insert_inventario_nasajon = DB::connection('nasajon')->select($sql_init_inventario_lote);
                }catch(\Exception $e){
                    Log::error($e->getMessage());
                    Log::error($sql_init_inventario);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => [$e, $sql_init_inventario_lote],
                        'response' => []
                    ], 422);
                }
                $mensagem_nasajon = $insert_inventario_nasajon[0]->mensagem;
                $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                if($mensagem_nasajon['codigo'] !== 'OK'){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => [$mensagem_nasajon['mensagem'], $sql_init_inventario_lote],
                        'response' => []
                    ], 422);
                }

                $InventarioHistoricoProdutoObj = new InventarioHistoricoProduto();
                $InventarioHistoricoProdutoObj->inventario_historicos_id = $InventarioHistoricoObj->id;
                $InventarioHistoricoProdutoObj->created_by = Auth::id();
                $InventarioHistoricoProdutoObj->codigo_produto = $produto['codigo_produto'];
            }
            
            $InventarioProdutoObj = InventarioProduto::with('produtoNasajon')->
                where('estabelecimento', $produto['estabelecimento'])->
                where('codigo_produto', $produto['codigo_produto'])->
                where('contagem', $produto['contagem'])->
                where('produto_codigobarras', false)->
                get();
            if($InventarioProdutoObj->isNotEmpty()){
                $data_inicio = $InventarioProdutoObj->min('created_at')->format('Y-m-d');
                $data_fim = date('Y-m-d');
                $produto_uuid = $InventarioProdutoObj[0]->produtoNasajon->produto;
                
                $sql_busca_pecas_já_lidas = "select * from integracoes.exportar_lotes_baixados(
                    (select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}'),
                    '{$produto_uuid}',
                    '{$data_inicio}',
                    '{$data_fim}'
                );";

                try{
                    $busca_inventarios = DB::connection('nasajon')->select($sql_busca_pecas_já_lidas);
                }catch(\Exception $e){
                    Log::error($e->getMessage());
                    Log::error($sql_busca_pecas_já_lidas);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => [$e, $sql_busca_pecas_já_lidas],
                        'response' => []
                    ], 422);
                }
                $codigos_iguinorar = [];
                foreach($busca_inventarios as $peca_esperar){
                    $codigos_iguinorar[] = $peca_esperar;
                }
                foreach($InventarioProdutoObj as $inventario){
                    $InventarioHistoricoProdutoObj->codigo_barras = false;
                    $InventarioHistoricoProdutoObj->quantidade = $inventario->quantidade_produto;

                    $InventarioHistoricoProdutoObj->save();

                    if(in_array($inventario->codigo_barras, $codigos_iguinorar)){
                        continue;
                    }
    
                    $InventarioHistoricoProdutoPecaObj = new InventarioHistoricoProdutoPeca();
                    $InventarioHistoricoProdutoPecaObj->inventario_historico_produtos_id = $InventarioHistoricoProdutoObj->id;
                    $InventarioHistoricoProdutoPecaObj->codigo_peca = $inventario->codigo_barras;
                    $InventarioHistoricoProdutoPecaObj->endereco = $inventario->endereco;
                    $InventarioHistoricoProdutoPecaObj->quantidade = $inventario->quantidade_produto;
                    $InventarioHistoricoProdutoPecaObj->created_by = Auth::id();
                    $InventarioHistoricoProdutoPecaObj->usuario_id = $inventario->created_by;
                    $InventarioHistoricoProdutoPecaObj->save();

                    $produto_uuid = $inventario->produtoNasajon->produto;
                    $LocalDeEstoqueNasajonObj = LocalDeEstoqueEnderecoNasajon::where(function($query) use ($inventario){
                        $query->
                            orWhere("endereco", $inventario->endereco)->
                            orWhere("endereco_simplificado", $inventario->endereco);
                    })->where('estabelecimento', $estabelecimentoNasajon_uuid)->first();
                    $endereco_uuid = $LocalDeEstoqueNasajonObj->localdeestoqueendereco;

                    $PecasNasajonObj = FracaoNasajon::where('codigo', $inventario->codigo_barras)->first();
                    $quantidade = $inventario->quantidade_produto;
                    if(!empty($PecasNasajonObj)){
                        try{
                            $lote_uuid = $PecasNasajonObj->fracao;
                        }catch(\Exception $e){
                            Log::error($e->getMessage());
                            Log::error('Produto não encontrado: '.$inventario->codigo_barras);
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Peça não encontrado: '.$inventario->codigo_barras.' para o produto código: '.$produto['codigo_produto'],
                                'error' => [$inventario->codigo_barras],
                                'response' => []
                            ], 422);
                        }
    
                        $sql_init_inventario_lote = "select * from integracoes.api_inventario_incluir_fracao(
                            '{$inventario_uuid}',
                            '{$produto_uuid}',
                            '{$lote_uuid}',
                            '{$endereco_uuid}',
                            {$quantidade}
                            );";
                    }else{

                        $sql_init_inventario_lote = "select * from integracoes.api_inventario_incluir_fracao(
                            '{$inventario_uuid}',
                            '{$produto_uuid}',
                            NULL,
                            '{$endereco_uuid}',
                            {$quantidade},
                            '{$inventario->codigo_barras}',
                            NULL
                            );";
                    }
                
                    try{
                        $insert_inventario_nasajon = DB::connection('nasajon')->select($sql_init_inventario_lote);
                    }catch(\Exception $e){
                        Log::error($e->getMessage());
                        Log::error($sql_init_inventario_lote);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                            'error' => [$e, $sql_init_inventario_lote],
                            'response' => []
                        ], 422);
                    }
                    $mensagem_nasajon = $insert_inventario_nasajon[0]->mensagem;
                    $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                    if($mensagem_nasajon['codigo'] !== 'OK'){
                        Log::error(print_r($mensagem_nasajon, true));
                        Log::error($sql_init_inventario_lote);
                        $produtoEspecificacaoObj = ProdutoEspecificacao::select()->where('codigo_produto', $produto['codigo_produto'])->first();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Produto '.$produtoEspecificacaoObj->descricao.' não controla fração.',
                            'error' => [$mensagem_nasajon['mensagem'], $sql_init_inventario_lote],
                            'response' => []
                        ], 422);
                    }

                    $InventarioHistoricoProdutoObj = new InventarioHistoricoProduto();
                    $InventarioHistoricoProdutoObj->inventario_historicos_id = $InventarioHistoricoObj->id;
                    $InventarioHistoricoProdutoObj->created_by = Auth::id();
                    $InventarioHistoricoProdutoObj->codigo_produto = $produto['codigo_produto'];

                }
            }
        }

        $sql_inventario_processa = "select * from integracoes.inventario_fracao_processar(
            '{$inventario_uuid}'
        );";
        
        try{
            $insert_inventario_nasajon = DB::connection('nasajon')->select($sql_inventario_processa);
        }catch(\Exception $e){
            $mensagem = $e->errorInfo[2];
            $mensagem = explode('CONTEXT:', $mensagem);
            $mensagem = $mensagem[0];
            $mensagem = str_replace('ERROR:', '', $mensagem);
            $mensagem = trim($mensagem);
            Log::error($e->getMessage());
            Log::error($sql_inventario_processa);
            return response()->json([
                'status' => 'error',
                'message' => $mensagem,
                'error' => [$e, $sql_inventario_processa],
                'response' => []
            ], 422);
        }

        foreach($produtos as $key => $produto){

            $InventarioProdutoObj = InventarioProduto::with('produtoNasajon')->
                where('estabelecimento', $produto['estabelecimento'])->
                where('codigo_produto', $produto['codigo_produto'])->
                where('contagem', $produto['contagem'])->
                sum(DB::raw('CAST(quantidade_produto as float)'));

            $ProdutosEstoqueObj = ProdutosEstoque::
                where('estabelecimento', str_pad($produto['estabelecimento'], 2, '0', STR_PAD_LEFT))->
                where('codigo_produto', $produto['codigo_produto'])->
                first();
            $quantidade_antes = 0;
            if(empty($ProdutosEstoqueObj)){
                $ProdutosEstoqueObj = new ProdutosEstoque();
                $ProdutosEstoqueObj->estabelecimento = str_pad($produto['estabelecimento'], 2, '0', STR_PAD_LEFT);
                $ProdutosEstoqueObj->codigo_produto = $produto['codigo_produto'];
                $ProdutosEstoqueObj->empenho = 0;
                $ProdutosEstoqueObj->compras = 0;
                $ProdutosEstoqueObj->data_atulizacao = date('Y-m-d');
                $ProdutosEstoqueObj->estoque = $InventarioProdutoObj;
                $ProdutosEstoqueObj->save();
            }
            else{
                $quantidade_antes = $ProdutosEstoqueObj->estoque;
            }
            $produtos[$key]['quantidade_anterior'] = $quantidade_antes;
            $produtos[$key]['quantidade_inventario'] = $InventarioProdutoObj;
            $produtos[$key]['quantidade_diferenca'] = $InventarioProdutoObj - $quantidade_antes;

            $InventarioProdutoObj = InventarioProduto::with('produtoNasajon')->
                where('estabelecimento', $produto['estabelecimento'])->
                where('codigo_produto', $produto['codigo_produto'])->
                where('contagem', $produto['contagem'])->
                where('produto_codigobarras', true)->
                first();
            if(!empty($InventarioProdutoObj)){
                $InventarioProdutoObj->delete();
            }
            
            $InventarioProdutoObj = InventarioProduto::with('produtoNasajon')->
                where('estabelecimento', $produto['estabelecimento'])->
                where('codigo_produto', $produto['codigo_produto'])->
                where('contagem', $produto['contagem'])->
                where('produto_codigobarras', false)->
                get();
            if($InventarioProdutoObj->isNotEmpty()){
                foreach($InventarioProdutoObj as $inventario){
                    $inventario->delete();
                }
            }
        }

        $empresa = returnEmpresasNasajonView();
		$EmailObj = new EmailController();
		$email_send = [Auth::user()->email];
        $variaveis = [
            'estabelecimento' => $empresa[intval($estabelecimento)],
            'usuario' => Auth::user()->name,
            'tabela_produtos' => $this->tabelaProdutosInventariados($produtos)
        ];
        $return = $EmailObj->sendEmailToken('00', "efetivacao_inventario", $email_send, $variaveis);
        
        $criterios_busca = [
            'estabelecimento' => intval($estabelecimento),
            'usuario' => ''
        ];
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'codigo_estabelecimento' => intval($estabelecimento),
                'contagem' => $contagem,
                'criterios' => encrypt($criterios_busca)
            ]
        ]);
    }


    public function excluirProduto(Request $request){
        $fields = $request->only('id');
        $criterios = $fields['id'];

        try{
            $criterios = decrypt($criterios);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [],
                'response' => []
            ], 422);
        }

        $InventarioProdutoObj = InventarioProduto::query()->with('createdby');
        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
        $InventarioProdutoObj->where('contagem', $criterios['contagem']);
        $InventarioProdutoObj->where('codigo_produto', $criterios['codigo_produto']);
        if(strlen($criterios['usuario']) > 0){
            $InventarioProdutoObj->where('created_by', $criterios['usuario']);
        }
        $inventarios = $InventarioProdutoObj->get();
        foreach($inventarios as $inventario){
            $inventario->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => []
        ]);

    }

    public function excluirPecaProduto(Request $request){
        $fields = $request->only('id');
        $id = $fields['id'];


        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [],
                'response' => []
            ], 422);
        }

        $InventarioProdutoObj = InventarioProduto::find($id);
        if(empty($InventarioProdutoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Produto não encontrado!',
                'error' => [],
                'response' => []
            ], 422);
        }
        $InventarioProdutoObj->delete();

        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => []
        ]);

    }

    private function tabelaProdutosInventariados($produtos){
        $html = '<br /><br /><table border="1" width="700" cellpadding="0" align="center">'.
            '<tr>'.
                '<th><b>Produto</b></th>'.
                '<th><b>Estoque Anterior</b></th>'.
                '<th><b>Estoque Inventariado</b></th>'.
                '<th><b>Diferença</b></th>'.
            '</tr>';

        foreach($produtos as $produto){
            $produto_dados = ProdutoEspecificacao::where('codigo_produto', $produto['codigo_produto'])->first();
            $html .= '<tr>'.
                    '<td> ' . $produto_dados->codigo_produto . ' - ' . $produto_dados->descricao . '</td>'.
                    '<td> ' . parserQtd(floatval($produto['quantidade_anterior'])) . '</td>'.
                    '<td> ' . parserQtd(floatval($produto['quantidade_inventario'])) . '</td>'.
                    '<td> ' . parserQtd(floatval($produto['quantidade_diferenca'])) . '</td>'.
                '</tr>';
        }
        $html .= '</table>';

        return $html;
    }
    
    public function modalNaoInventariado(Request $request){
        $fields = $request->only('estabelecimento');

        $InventarioHistoricoObj = new InventarioHistorico();
        $InventarioHistoricoProdutoObj = new InventarioHistoricoProduto();
        $InventarioProdutoObj = new InventarioProduto();

        $ProdutoEspecificacaoObj = new ProdutoEspecificacao();
        $ProdutosEstoqueObj = new ProdutosEstoque();

        $estabelecimento = str_pad($fields["estabelecimento"], 2, 0, STR_PAD_LEFT);

        $query_inventario = InventarioProduto::selectRaw("distinct codigo_produto as codigo_produto")
            ->where("estabelecimento", $estabelecimento)
            ->where("codigo_produto", "!=", "")->get();

        $query_estoque = ProdutosEstoque::query()
            ->leftJoin($ProdutoEspecificacaoObj->getTable(), "{$ProdutoEspecificacaoObj->getTable()}.codigo_produto", "{$ProdutosEstoqueObj->getTable()}.codigo_produto")
            ->where("estabelecimento", $estabelecimento)
            ->where("estoque", ">", 0)
            ->whereNotIn("{$ProdutosEstoqueObj->getTable()}.codigo_produto", $query_inventario->pluck("codigo_produto"))
            ->get();
        $retorno = [];

        $query_estoque->each(function($produto) use (&$retorno, $estabelecimento){
            $codigo_produto = $produto->codigo_produto;
            if($produto->descricao != ''){
                $retorno[] = [
                    'codigo' => $codigo_produto,
                    'descricao' => $produto->descricao,
                    'estoque' => parserValor($produto->estoque),
                    'estoque_notFormat' => $produto->estoque,
                    'unidade' => $produto->unidade
                ];
            }
        });
        unset($query_estoque);
        
        return view("programs.inventario.modal.nao_inventariado")->with(['retorno' => $retorno, 'estabelecimento' => $estabelecimento]);
    }
}
