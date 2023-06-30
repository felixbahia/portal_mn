<?php

namespace App\Http\Controllers;

use Auth;
use App\Preco;
use App\Produto;
use App\Segmento;
use App\CepEstado;
use Carbon\Carbon;
use App\NotasVenda;
use App\MargemPrazo;
use App\NotasNasajon;
use App\PedidoPortal;
use App\AliquotaPreco;
use App\ComprasNasajon;
use App\ProdutoNasajon;
use App\ProdutosEstoque;
use App\PedidoItemPortal;
use App\ProdutoTecidoBase;
use App\FichaTecnicaProduto;
use App\PedidosVendaNasajon;
use Illuminate\Http\Request;
use App\NotasEmAbertoNasajon;
use App\NotasEntradasNasajon;
use App\ProdutoEspecificacao;
use App\CarrinhoCompra;
use App\Movimentacao;
use App\ProdutoGrupo;
use App\Campanha;
use App\CampanhasProduto;
use App\ProdutoTecidoEstampado;

use App\Exports\ExportarEstoque;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\PedidosReservaProdutoNasajon;
use App\UnidadeConversaoProdutoNasajon;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ListaDePrecosRequest;
use App\Http\Controllers\PedidoPortalController;

use App\Http\Requests\ProdutoFilterSimplesRequest;

use App\Http\Controllers\ListagemDePrecosController;
use App\Http\Controllers\MovimentoEstoqueController;
use App\Http\Requests\BookVirtualFiltroPrincipalRequest;

class ProdutoController extends Controller
{
    private $codigo_cliente_balcao = ['0000010069999'];

    private $estabelecimentos_prologos = [];

    private $grupos_especiais = [];
    private $media_especial = 45;

    private $quantidade_pecas_pedido = 40;
    private $count_pecas_pedido = 3;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Produto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Produto');

        $segmentos =$this->segmentos();


        return view('programs.produto.index')->with(['segmentos' => $segmentos]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Cliente  $codcad
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request){
        $codigo_produto = $request->only(["codprd"])["codprd"];
        $dados_temp = ProdutoNasajon::where('codigo', $codigo_produto)->first();
        $dados = [];
        $dados_especificacoes = ProdutoEspecificacao::with(['custos'])->where('codigo_produto', $codigo_produto)->first()->toArray();
        $dados_especificacoes = $this->parserProduto($dados_especificacoes);
        $dados_especificacoes["peso"] = empty($dados_temp)? '' : parserQtd($dados_temp->pesoliquido);
        $dados_especificacoes["ipi"] = empty($dados_temp)? '' : parserQtd($dados_temp->ipi);
        $dados["original"] = $dados_especificacoes;
        $dados["ean"] = empty($dados_temp)? '' : $dados_temp->codigodebarras;
        foreach ($dados_especificacoes as $key => $value) {
            if(in_array($key, ['custos'])){
                $dados[strtolower($key)] = $value;
                continue;
            }
            if($key === 'subgrupo'){
                $key = "sub_grupo";
            }
            $dados[strtolower($key)] = "<div data-toggle=\"tooltip\" data-placement=\"top\">".trim($value)."</div>";
        }
        if(!empty($dados_temp)){
            $dados['ncm'] = $dados_temp->ncm;
            $dados['unidade'] = $dados_temp->unidade;
        }else{
            $dados['ncm'] = '';
            $dados['unidade'] = '';
        }
        $dados["estoque"] = [];
        $dados["estoque_total"] = ["compra"=>0,"estoque"=>0,"reserva"=>0,"disponivel"=>0, "armazem"=>0, "em_transito"=>0, "em_terceiros"=>0, "fiscal"=>0];
        $empresas = returnEmpresasNasajonView();
        unset($empresas['20']);

        $estoquesObj = ProdutosEstoque::where('codigo_produto', $codigo_produto)->orderBy('estabelecimento')->get();

   
        $compras = [];
        $estoques = [];
        $armazem = [];
        $fiscal = [];
        $em_terceiros = [];

        foreach ($estoquesObj as $key => $value) {
            if(!isset($compras[intval($value["estabelecimento"])])){
                $compras[intval($value["estabelecimento"])] = 0;
            }
            $compras[intval($value["estabelecimento"])] += floatval($value['compras_aberto']);
            $estoques[intval($value["estabelecimento"])] = $value;
            $armazem[intval($value["estabelecimento"])] = $value['saldo_armazem'];
            $fiscal[intval($value["estabelecimento"])] = $value['saldo_fiscal'];
            $saldo_movimento_nao_efetivado[intval($value["estabelecimento"])] = $value['saldo_movimento_nao_efetivado'];
            $em_terceiros[intval($value["estabelecimento"])] = $value['saldo_em_terceiros']-$value['saldo_armazem'];
        }

        unset($estoquesObj);

        $empenho_portal = [];
        $PedidoPortalObj = PedidoPortal::with(['itens_pedido'])
            ->whereNotIn('status_pedido', [3, 5, 7])
            ->whereHas('itens_pedido', function($query) use ($codigo_produto){
                $query->where("cod_produto", $codigo_produto);
            });
        $PedidoPortal = $PedidoPortalObj->get();
        foreach($PedidoPortal as $pedido){
            if(!isset($empenho_portal[$pedido->estabelecimento])){
                $empenho_portal[$pedido->estabelecimento] = 0;
            }
            foreach($pedido->itens_pedido as $item_pedido){
                if($item_pedido->cod_produto !== $codigo_produto){
                    continue;
                }
                $empenho_portal[$pedido->estabelecimento] += $item_pedido->quantidade;
            }
        }
        unset($PedidoPortalObj);
        unset($PedidoPortal);

        $empenho_nasajaon = [];
        
        $PedidosVendaNasajon = PedidosReservaProdutoNasajon::where('codigo_produto', $codigo_produto)->get();
        foreach($PedidosVendaNasajon as $pedido){
            if(!isset($empenho_nasajaon[intval($pedido->codigo_estabelecimento)])){
                $empenho_nasajaon[intval($pedido->codigo_estabelecimento)] = 0;
            }
            if($pedido->codigo_produto !== $codigo_produto){
                continue;
            }
            $empenho_nasajaon[intval($pedido->codigo_estabelecimento)] += $pedido->quantidade;
        }
        unset($PedidosVendaNasajon);

        $movimentoEstoqueObj = new MovimentoEstoqueController;

        foreach($empresas as $codigo => $empresa) {
            $dados["estoque"][$codigo] = [
                "original" => [
                    'estabel' => str_pad(intval($codigo), 2, '0', STR_PAD_LEFT),
                    'cod_estabel' => intval($codigo),
                    'compra' => 0,
                    'estoque' => 0,
                    'reserva' => 0,
                    'disponivel' => 0
                ],
                "estabel" => $empresa,
                "compra" => 0,
                "estoque" => 0,
                "reserva" => 0,
                "disponivel" => 0,
                "armazem" => 0,
                'em_transito' => 0,
                'em_terceiros'=> 0,
                'fiscal' => 0,
            ];

            if(isset($empenho_portal[intval($codigo)])){
                $empenho_portal[intval($codigo)] = (float) $empenho_portal[intval($codigo)];
            }else{
                $empenho_portal[intval($codigo)] = 0;
            }

            if(isset($empenho_nasajaon[intval($codigo)])){
                $empenho_nasajaon[intval($codigo)] = (float) $empenho_nasajaon[intval($codigo)];
            }else{
                $empenho_nasajaon[intval($codigo)] = 0;
            }

            $dados["estoque"][$codigo]['reserva'] = parserQtd($empenho_portal[intval($codigo)] + $empenho_nasajaon[intval($codigo)]);
            $dados["estoque"][$codigo]['original']['reserva'] = $empenho_portal[intval($codigo)] + $empenho_nasajaon[intval($codigo)];

            if(!empty($estoques[$codigo])){
                $disponivel = ($estoques[$codigo]->estoque + $compras[intval($codigo)]);
                $disponivel -= $empenho_portal[intval($codigo)];
                $disponivel -= $empenho_nasajaon[intval($codigo)] > 0 ?  $empenho_nasajaon[intval($codigo)] : 0;
                
                if($disponivel <= 0){
                    $disponivel = 0;
                } 

                $dados["estoque"][$codigo]['compra'] = ($compras[intval($codigo)]);
                $dados["estoque"][$codigo]['original']['compra'] = $compras[intval($codigo)];

                $dados["estoque"][$codigo]['estoque'] = ($estoques[$codigo]->estoque);
                $dados["estoque"][$codigo]['original']['estoque'] = $estoques[$codigo]->estoque;

                $dados["estoque"][$codigo]['reserva'] = ($empenho_portal[intval($codigo)] + $empenho_nasajaon[intval($codigo)]);
                $dados["estoque"][$codigo]['original']['reserva'] = $empenho_portal[intval($codigo)] + $empenho_nasajaon[intval($codigo)];

                $dados["estoque"][$codigo]['disponivel'] = ($disponivel);
                $dados["estoque"][$codigo]['original']['disponivel'] = $disponivel;
                
                if($codigo == 3 || $codigo == 4){
                    $dados["estoque"][$codigo]['armazem'] = ($fiscal[intval($codigo)]);
                    $dados["estoque"][$codigo]['original']['armazem'] = $fiscal[intval($codigo)];
                }else{
                    $dados["estoque"][$codigo]['armazem'] = ($armazem[intval($codigo)]);
                    $dados["estoque"][$codigo]['original']['armazem'] = $armazem[intval($codigo)];
                }

                $dados["estoque"][$codigo]['fiscal'] = ($fiscal[intval($codigo)]);
                $dados["estoque"][$codigo]['original']['fiscal'] = $fiscal[intval($codigo)];

                if(intval($codigo) == 3 || intval($codigo) == 4){
                    $dados["estoque"][$codigo]['em_terceiros'] = ($em_terceiros[intval($codigo)]) - $saldo_movimento_nao_efetivado[intval($codigo)];
                    $dados["estoque"][$codigo]['original']['em_terceiros'] = $em_terceiros[intval($codigo)] - $saldo_movimento_nao_efetivado[intval($codigo)];
                    
                    $dados["estoque"][$codigo]['em_transito'] = $fiscal[intval($codigo)] + $saldo_movimento_nao_efetivado[intval($codigo)];
                    $dados["estoque"][$codigo]['original']['em_transito'] = $fiscal[intval($codigo)] + $saldo_movimento_nao_efetivado[intval($codigo)];
                }else{
                    $dados["estoque"][$codigo]['em_terceiros'] = ($em_terceiros[intval($codigo)]);
                    $dados["estoque"][$codigo]['original']['em_terceiros'] = $em_terceiros[intval($codigo)];

                    $dados["estoque"][$codigo]['em_transito'] = 0;
                    $dados["estoque"][$codigo]['original']['em_transito'] = 0;
                }

                $dados["estoque_total"]["compra"] += floatval($compras[intval($estoques[$codigo]->estabelecimento)]);
                $dados["estoque_total"]["estoque"] += $estoques[$codigo]->estoque;
                $dados["estoque_total"]["reserva"] += (floatval($empenho_portal[intval($codigo)] + $empenho_nasajaon[intval($codigo)])) > 0 ? floatval($empenho_portal[intval($codigo)] + $empenho_nasajaon[intval($codigo)]) : 0;
                $dados["estoque_total"]["disponivel"] += floatval($disponivel);
                $dados["estoque_total"]["armazem"] += $dados["estoque"][$codigo]['armazem'];
                $dados["estoque_total"]["em_transito"] += $dados["estoque"][$codigo]['em_transito'];
                $dados["estoque_total"]["em_terceiros"] += $dados["estoque"][$codigo]['em_terceiros'] < 0? 0 : $dados["estoque"][$codigo]['em_terceiros'];
                $dados["estoque_total"]["fiscal"] += $fiscal[intval($codigo)];
            }
        }
 
        foreach($dados["estoque"] as $codigo => $dado){
            if($dado['disponivel'] <= 0){
                $verificar = Movimentacao::select()->where('estabelecimento', str_pad(intval($dado['original']['cod_estabel']), 2, '0', STR_PAD_LEFT))->where('produto_codigo', $codigo_produto)->where('data_movimentacao', '>=', '2019-07-01')->first();
                if(empty($verificar) && empty($dados["estoque"][$codigo]['original']["estoque"]) && empty($dados["estoque"][$codigo]['original']["compra"]) && empty($dados["estoque"][$codigo]['original']["reserva"])){
                    unset($dados["estoque"][$codigo]);
                }
            }
            if(!isset($dados["estoque"][$codigo])){
                continue;
            }
            foreach($dado as $campo => $valor){
                if(is_numeric($valor)){
                    if($valor > 0){
                        $dados["estoque"][$codigo][$campo] = parserQtd($valor);
                       
                    }else{
                        $dados["estoque"][$codigo][$campo] = '';
                    }
                }
            }
        }
		$total_estoque = $dados["estoque_total"]["estoque"];
        $dados["estoque_total"]["compra"] = parserQtd($dados["estoque_total"]["compra"]);
        $dados["estoque_total"]["estoque"] = parserQtd($dados["estoque_total"]["estoque"]);
        $dados["estoque_total"]["reserva"] = parserQtd($dados["estoque_total"]["reserva"]);
        $dados["estoque_total"]["disponivel"] = parserQtd($dados["estoque_total"]["disponivel"]);
        $dados["estoque_total"]["armazem"] = parserQtd($dados["estoque_total"]["armazem"]);
        $dados["estoque_total"]["em_transito"] = parserQtd($dados["estoque_total"]["em_transito"]);
        $dados["estoque_total"]["em_terceiros"] = parserQtd($dados["estoque_total"]["em_terceiros"]);
        $dados["estoque_total"]["fiscal"] = parserQtd($dados["estoque_total"]["fiscal"]);

		$dados['marca'] = $dados_especificacoes['marca'];
		$dados['grupo'] = $dados_especificacoes['grupo'];
		$dados['subgrupo'] = $dados_especificacoes['sub_grupo'];
		$dados['linha'] = $dados_especificacoes['linha'];
		$dados['codigo_produto'] = $codigo_produto;
		$dados['descricao'] = $dados_especificacoes['descricao'];
        $dados['data_de_cadastro'] = parserData($dados_especificacoes["data_de_cadastro"]);

		$FichaTecnicaProdutoObj = FichaTecnicaProduto::where('codigo_produto', $codigo_produto)->first();

		if(!empty($FichaTecnicaProdutoObj)){
			$dados['ficha_tecnica_id'] = $FichaTecnicaProdutoObj->id;
		}
        if(!in_array(Auth::user()->tipo_usuario_id, [12, 16])){ //liberado para todos - 16/07/2021

            $precoObj = Preco::select(DB::Raw('*', 'SUM(estoque * compra_dolar) as estoque_vezes_dolar, SUM(estoque) as custo_medio_dolar'))->find($codigo_produto);

            $dados['preco_real'] = '';
            $dados['preco_dolar'] = '';

            $custo_total = 0;
            $estoque_total = 0;

            $estoqueObj = ProdutosEstoque::where('codigo_produto', $codigo_produto)->get();

            $estoqueObj->each(function ($linha) use (&$custo_total, &$estoque_total){
                $custo_total += $linha->estoque * $linha->custo;
                $estoque_total += $linha->estoque;
            });

            if($estoque_total > 0){
                $custo_medio = $custo_total / $estoque_total;
            }
            else{
                $custo_medio = 0;
            }

            $dados['custo_portal'] = [
                'custo_contabil' => '',
                'custo_gerencial' => '',
                'popover_custo_contabil' => '',
                'popover_custo_gerencial' => ''
            ];
            if(!empty($dados['custos']) && is_array($dados['custos'])){
                $custo_contabil = 0;
                $custo_gerencial = 0;
                $popover_custo_contabil = '';
                $popover_custo_gerencial = '';

                foreach($dados['custos'] as $index => $custo){
                    $estoque = 0;
                    if(isset($dados["estoque"][intval($custo['estabelecimento'])]['original']['estoque'])){
                        $estoque = $dados["estoque"][intval($custo['estabelecimento'])]['original']['estoque'];
                    }
                    $custo_contabil += ($estoque > 0) ? $custo['custo_medio_contabil'] * $estoque : 0;
                    $custo_gerencial += ($estoque > 0) ? $custo['custo_medio_gerencial'] * $estoque : 0;

                    if($estoque > 0){
                        $popover_custo_contabil .= '<p>'.$empresas[intval($custo['estabelecimento'])].' - ' .parserValor($custo['custo_medio_contabil']).'</p>';
                        $popover_custo_gerencial .= '<p>'.$empresas[intval($custo['estabelecimento'])].' - ' .parserValor($custo['custo_medio_gerencial']).'</p>';
                    }                
                }

                $contar_estabelecimentos = count($dados['custos']);
                $custo_contabil = ($total_estoque > 0) ? $custo_contabil / $total_estoque : $custo_contabil / $contar_estabelecimentos;
                $custo_gerencial = ($total_estoque > 0) ? $custo_gerencial / $total_estoque : $custo_gerencial / $contar_estabelecimentos;
                
                $dados['custo_portal'] = [
                    'custo_contabil' => parserValor($custo_contabil),
                    'custo_gerencial' => parserValor($custo_gerencial),
                    'popover_custo_contabil' => $popover_custo_contabil,
                    'popover_custo_gerencial' => $popover_custo_gerencial,
                ];
            }

            $dados['custo_medio'] = parserValor($custo_medio);
            
            if(is_null($precoObj)){
                $dados['ultima_compra_real'] = '';
                $dados['custo_real'] = '';
                $dados['ultima_compra_dolar'] = '';
                $dados['custo_dolar'] = '';
                $dados['entrega'] = '';
                $dados['valor_compra'] = '';
                $dados['proforma'] = '';
                $dados['venda_ultimos_meses'] = '';
                $dados['venda_media'] = '';
                $dados['estoque_meses'] = '';
            }

            else{

                $dados['ultima_compra_real'] = '';
                $dados['ultima_compra_dolar'] = '';

                if (!empty($precoObj->ultima_compra_real)){
                    $dados['ultima_compra_real'] = parserData($precoObj->ultima_compra_real);
                }
                if(!empty($precoObj->ultima_compra_dolar)){
                    $dados['ultima_compra_dolar'] = parserData($precoObj->ultima_compra_dolar);
                }
                if(!empty($precoObj->preco_real)){
                    $dados['preco_real'] = parservalor($precoObj->preco_real);
                }
                if(!empty($precoObj->preco_dolar)){
                    $dados['preco_dolar'] = parservalor($precoObj->preco_dolar);
                }
                
                $agora = Carbon::Now();
                $seisMeses = Carbon::Now();
                $seisMeses->day = 1;
                $seisMeses->addMonths(-5);

                $notas = NotasVenda::query()
                    ->whereRaw("cast( concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date ) >= '{$seisMeses->format('Y-m-d')}'")
                    ->where('codigo_produto', $codigo_produto)
                    ->get();

                $vendas_periodo = $notas->sum('quantidade');

                if(!empty($precoObj->estoque_vezes_dolar)){
                    $custo_medio_dolar  = $precoObj->custo_medio_dolar / $precoObj->estoque_vezes_dolar;
                }else{
                    $custo_medio_dolar = 0;
                }

                $dados['custo_dolar'] = '';
                $dados['custo_real'] = '';

                if(!empty($precoObj->compra_real)){
                    $dados['custo_real'] = parserValor($precoObj->compra_real);
                }

                if(!empty($precoObj->compra_dolar)){
                    $dados['custo_dolar'] = parserValor($precoObj->compra_dolar);
                }

                if(!empty($precoObj->previsao_entrega)){
                    $dados['entrega'] = parserData($precoObj->previsao_entrega);
                }
                else{
                    $dados['entrega'] = '';
                }
                
                if(!empty($precoObj->valor_compra)){
                    $dados['valor_compra'] = parserValor($precoObj->valor_compra);
                } else {
                    $dados['valor_compra'] = '';
                }
                $dados['proforma'] = $precoObj->numero_proforma;

                $dados['custo_medio_dolar'] = parserValor($custo_medio_dolar);
                $dados['venda_ultimos_meses'] = parserValor($vendas_periodo);

                $venda_media = $vendas_periodo / 6;

                $dados['venda_media'] = parserValor($venda_media);

                if($vendas_periodo > 0 ){
                    $dados['estoque_meses'] = parserValor(str_replace(',','.', str_replace('.','',$dados['estoque_total']['estoque'])) / $venda_media) . ' MESES';
                }
                else{
                    $dados['estoque_meses'] = '0 MESES';
                }
            }

            return view('programs.produto.view_admin')->with("dados",$dados);
        }
        return view('programs.produto.view')->with("dados",$dados);
    }

    private function parserProduto($data){
        $data_parse = [];
        $keys_parse = [
            "codigo_produto" => "codigo",
            "grupo" => "grupo",
            "descricao" => "descricao",
            "linha" => "linha",
            "marca" => "marca",
            "subgrupo" => "sub_grupo",
            "unidade" => "unidade",
            "ncm" => "ncm",
            "peso" => "peso",
            "custos" => "custos",
            "data_de_cadastro" => "data_de_cadastro",
        ];
        foreach ($data as $key => $value) {
            if(!array_key_exists(strtolower($key), $keys_parse)){
                continue;
            }
            if(empty($value)){
                $value = " ";
            }
            $data_parse[$keys_parse[strtolower($key)]] = $value;
        }
        return $data_parse;
    }
    /**
     * Filtro da listagem
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request, $array = false){
        set_time_limit(300);
        $fields = $request->only(['grupo', 'codigo', 'nome', 'marca', 'linha', 'desativado','segmentos']);
        
        if(empty($fields["grupo"]) && empty($fields["codigo"]) && empty($fields["nome"]) && empty($fields["marca"]) && empty($fields["linha"]) && empty($fields["desativado"])  && empty($fields["segmentos"])){
            if($array == true){
                return [];
            }
            else{
                $return = [
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "total_estoque" => "0,0",
                    "data" => []
                ];
                return response()->json($return);
            }

        }

        foreach($fields as $key => $value){
            if(empty($value) || is_array($value)){
                continue;
            }
            $fields[$key] = strip_tags($value);
        }

        $where = [];
        if(!empty($fields['grupo'])){
            $where[] = ['produto_grupos', 'ilike',trim(strtolower($fields['grupo']))];
        }
        if(!empty($fields['codigo'])){
            $where[] = ['LOWER(codigo_produto)', 'ilike', trim(strtolower($fields['codigo']))];
        }
        if(!empty($fields['nome'])){
            $where[] = ['LOWER(descricao)', 'ilike', trim(strtolower($fields['nome']))];
        }
        if(!empty($fields['marca'])){
            $where[] = ['LOWER(marca)', 'ilike', trim(strtolower($fields['marca']))];
        }
        if(!empty($fields['linha'])){
            $where[] = ['LOWER(linha)', 'ilike', trim(strtolower($fields['linha']))];
        }
        if(empty($fields['desativado']) || $fields['desativado'] !== 'sim'){
            $where[] = ['ativo', '=', 'true'];
        }else{
            $where[] = ['ativo', '=', 'false'];
        }

        if(!empty($fields['segmentos'])){
            $where[] = ['segmentos_id', '=', $fields['segmentos']];
        }

        $query_produto = ProdutoEspecificacao::select()->with(['ficha_tecnica', 'produtoGrupo.segmento','produtoNasajon', 'foto', 'reserva', 'produtoGrupo', 'estoque'=> function($query){
            $query->orderBy('estabelecimento');
        }]);
        foreach($where as $value){
            if(in_array($value[0], ['segmentos_id', 'produto_grupos'])){
                $query_produto->whereHas('produtoGrupo', function($query)use($value){
                    if($value[0] === 'produto_grupos'){
                        $query->where('descricao', 'ilike', $value[2]);
                    }else if($value[0] === 'segmentos_id'){
                        $query->where('segmentos_id', $value[2]);
                    }                    
                });
            }else if(count($value) === 3 && $value[1] === "ilike"){
                $query_produto->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
            } elseif(count($value) === 3 && $value[1] !== "like"){
                $query_produto->whereRaw("{$value[0]} {$value[1]} '{$value[2]}'");
            } else {
                $query_produto->where($value[0], $value[1]);
            }
        }

        try {

            $query_produto = $query_produto->distinct()->get();
        } catch (\Exception $e){
            $return = [
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "total_estoque" => "0,0",
                "data" => [],
                "erro" => $e->getMessage(),
            ];
            return response()->json($return);
        }
        $key = 0;
        $produtos = [];
        $results = [];
        $empresas = returnEmpresasNasajonView();
        $codigos = [];
        $produtos_codigos = $query_produto->unique('codigo_produto')->pluck('codigo_produto')->toArray();
        $itens_portal = collect();

        $pedido_portal = PedidoPortal::with(['itens_pedido'])
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->whereHas('itens_pedido', function($query) use ($produtos_codigos){
            $query->whereIn("cod_produto", $produtos_codigos);
        })
        ->get();

        $pedido_portal->each(function($query) use (&$itens_portal){
            foreach($query->itens_pedido as $itens){
                $itens_portal->push([
                    'codigo_produto' =>  $itens->cod_produto,
                    'quantidade' => $itens->quantidade,
                    'estabelecimento' => str_pad($query->estabelecimento, 2, "0", STR_PAD_LEFT),
                ]);
            }
        });

        unset($pedido_portal);

        $query_produto->each(function($query) use ($itens_portal,$empresas,&$key,&$results,$array){

            $compras = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0];
            $total_estoque = 0;
            $total_estoque_empresa = [];

            foreach($query->estoque as $estoque){
                $disponivel = (floatval($estoque->estoque) + floatval($estoque->compras_aberto)) - floatval($estoque->empenho);
                $compras[str_pad(intval($estoque->estabelecimento), 2, "0", STR_PAD_LEFT)] = $estoque->compras;
                if($disponivel == 0.0){
                    continue;
                }
                $total_estoque_empresa[str_pad(intval($estoque->estabelecimento), 2, "0", STR_PAD_LEFT)] = $disponivel;
            }

            foreach($compras as $estabel => $compra){
                if(isset($total_estoque_empresa[str_pad(intval($estabel), 2, "0", STR_PAD_LEFT)]) || $compra === 0){
                    continue;
                }
                $total_estoque_empresa[str_pad(intval($estabel), 2, "0", STR_PAD_LEFT)] = $compra;
            }

            unset($compras);

            $query_itens = $itens_portal->where('codigo_produto',$query->codigo_produto);
            
            if(!empty($query_itens)){
                foreach($query_itens as $item){
                    $estabelecimento = !isset($item->estabelecimento) ? $item['estabelecimento'] : $item->estabelecimento;
                    $quantidade = !isset($item->quantidade) ? $item['quantidade'] : $item->quantidade;
                    if(isset($total_estoque_empresa[str_pad($estabelecimento, 2, "0", STR_PAD_LEFT)])){
                        $total_estoque_empresa[str_pad($estabelecimento, 2, "0", STR_PAD_LEFT)] -= $quantidade;
                    }
                }
            }

            foreach($query->reserva as $reservas){
                if(isset($total_estoque_empresa[str_pad($reservas->codigo_estabelecimento, 2, "0", STR_PAD_LEFT)])){
                    $total_estoque_empresa[str_pad($reservas->codigo_estabelecimento, 2, "0", STR_PAD_LEFT)] -= $reservas->quantidade > 0 ? $reservas->quantidade : 0;
                }
            }

            $total_empresa = [];

            foreach ($total_estoque_empresa as $key_empresa => $value_empresa) {
                if($value_empresa == 0){
                    continue;
                }
                $total_estoque += $value_empresa;
                $total_empresa[] = $empresas[intval($key_empresa)] ." - ".parserQtd($value_empresa);
            }

            if($total_estoque < 0){
                $total_estoque = 0;
            }

            $results[$key]["foto"] = "";

            // if(isset($query->foto) && Storage::exists('public/produto_fotos/' . $query->foto->filename) && Storage::exists('public/produto_fotos/' . $query->foto->thumb_filename)){
            //     $results[$key]["foto"] = "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url('public/produto_fotos/' . $query->foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $query->foto->filename) . "\" class=\"btn-foto-estoque thumb ml-2 mt-1\"></a>"; 
            // }
            // else{
            //     $results[$key]["foto"] = "";
            // }

            // if(isset($query->produtoGrupo->laudo) && !empty($query->produtoGrupo->laudo) && Storage::exists($query->produtoGrupo->laudo->pdf_laudo)){
            //     $results[$key]['laudo'] = "<a target='_blank' href='" . Storage::url($query->produtoGrupo->laudo->pdf_laudo) . "'><i class='btn-laudo' data-toggle='tooltip' data-original-title='Ver laudo' data-placement='right'></i></a>";
            // }
            // else{
            //     $results[$key]['laudo'] = '';
            // }

            $estabelecimentos_comercial = '';
            $estoque_produto_entrega_comercial = '';

            if(!empty($total_empresa)){
                foreach($total_empresa as $total_empresa_comercial){
                    $estabelecimentos_comercial .= substr($total_empresa_comercial, 0, 2)."-";
                    $estoque_produto_entrega_comercial .= $total_empresa_comercial.";";
                }
            }
            $bookVirtualExibicao = new BookVirtualExibicaoControllerNew;
            $requestBookVirtual = new BookVirtualFiltroPrincipalRequest([
                'codigo_produto' => $query->codigo_produto,
                'grupo' => '',
                'marca' => '',
                'linha' => '',
                'tipo_venda' => '',
                'codigos_produtos' => '',
                'codigo_desenho' => '',
                'desenho_produtos' => '',
                'busca_navegacao' => null,
                'campanha' => '',
            ]);
            
            $retorno = $bookVirtualExibicao->gerarLinkBusca($requestBookVirtual);

            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retorno;
            }

            $link_carrinho = '<a href="'.$retorno->getData()->response->link.'" target="_blank" rel="noopener" title="Adicionar Carrinho" class="bt-carrinho-comprar" style="color: black;"></a>';

            $results[$key]['laudo'] = '<a href="#" class="btn-pedido" title="Ficha Técnica Comercial" data-title="Ficha Técnica Comercial" data-codigo="'.$query->codigo_produto.'" data-estabelecimentos="'.$estabelecimentos_comercial.'" data-estoque_produto_entrega="'.$estoque_produto_entrega_comercial.'" data-carrinho=\''.$link_carrinho.'\' onclick="modalFichaComercialDetalhes($(this))" style="display: initial !important;"></a>';

            if($array !== true){
                $results[$key]["grupo"] = $query->produtoGrupo->descricao;
                $results[$key]["codigo"] = $query->codigo_produto;
                $results[$key]["nome"] = $query->descricao;
                $results[$key]["marca"] = $query->marca;
                $results[$key]["linha"] = $query->linha;
                $results[$key]["segmento"] = (!empty($query->produtoGrupo->segmento->descricao)) ? $query->produtoGrupo->segmento->descricao : '';
            }else{
                $results[$key]["grupo"] = $query->produtoGrupo->descricao;
                $results[$key]["codigo"] = $query->codigo_produto;
                $results[$key]["nome"] = $query->descricao;
                $results[$key]["marca"] = $query->marca;
                $results[$key]["linha"] = $query->linha;
                $results[$key]["segmento"] = (!empty($query->produtoGrupo->segmento->descricao)) ? $query->produtoGrupo->segmento->descricao : '';
            }

            if(empty($query->ficha_tecnica)){
                $results[$key]["ficha_tecnica"] = false;
            }else{
                $results[$key]["ficha_tecnica"] = true;
            }

            if (isset($query->produtoGrupo)){
                $results[$key]["gramatura"] = !empty($query->produtoGrupo->gramatura_gml) ? $query->produtoGrupo->gramatura_gml : '';
                $results[$key]["largura"] = !empty($query->produtoGrupo->largura) ? $query->produtoGrupo->largura : '';
            }
            else{
                $results[$key]["gramatura"] = '';
                $results[$key]["largura"] = '';
            }

            if(!is_null($query->produtoNasajon)){
                $results[$key]["composicao"] = $query->produtoNasajon->composicao;
                $results[$key]["unidade"] = $query->produtoNasajon->unidade;
            }
            else if(!is_null($query->produtoUsoConsumoNasajon)){
                $results[$key]["composicao"] = $query->produtoUsoConsumoNasajon->composicao;
                $results[$key]["unidade"] = $query->produtoUsoConsumoNasajon->unidade;
            }
            else if(!is_null($query->produtoCompletoNasajon)){
                $results[$key]["composicao"] = $query->produtoCompletoNasajon->composicao;
                $results[$key]["unidade"] = $query->produtoCompletoNasajon->unidade;
            }
            else{
                $results[$key]["composicao"] = '';
                $results[$key]["unidade"] = '';
            }

            $results[$key]["estoque"] = parserQtd($total_estoque);

            $results[$key]["estoque"] = parserQtd($total_estoque);

            if($array == true){
                $results[$key]["total_popover"] = implode(PHP_EOL, $total_empresa);
            }else{
                $results[$key]["total_popover"] = $total_empresa;
            }

            $results[$key]["title_modal"] = "".$query->codigo_produto." - ".$query->grupo." - ".$query->descricao."";

            $key++;

        });
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ['dados' => $results],
        ]);

    }

    public function autoComplete(Request $request){
        $fields = $request->only(["name", "term"]);
        $return = [];
        $keys_parse = [
            "grupo" => "grupo",
            "nome" => "descricao",
            "linha" => "linha",
            "marca" => "marca",
            "subgrupo" => "subgrupo"
        ];
        $name_filter = $keys_parse[$fields["name"]];
        if($fields['name'] == 'grupo'){
            $query = ProdutoGrupo::select('descricao')
                ->limit("15")
                ->orderBy('descricao', "ASC")
                ->where('descricao', 'ilike', '%'.$fields['term'].'%')
                ->distinct('descricao')
                ->get()
                ->toArray();
            foreach ($query as $value){
                $value = (array) $value;
                $return[] = trim($value['descricao']);
            }
        }else{
            $query = ProdutoEspecificacao::select($name_filter)
                ->limit("15")
                ->orderBy($name_filter, "ASC")
                ->whereRaw("LOWER({$name_filter}) ilike '%".(strtolower(trim($fields["term"])))."%'")
                ->where('ativo','true')
                ->distinct($name_filter)
                ->get()
                ->toArray();
            foreach ($query as $value){
                $value = (array) $value;
                $return[] = trim($value[$name_filter]);
            }
        }
        
        return response()->json($return);
    }

    public function indexEstoque(Request $request){
        $fields = $request->only(["codigo", "estabel"]);
        
        $estoques = ProdutosEstoque::where('codigo_produto', $fields["codigo"])->where('estabelecimento', $fields["estabel"])->first()->toArray();
        $unidade = ProdutoEspecificacao::select('unidade')->where('codigo_produto',$fields["codigo"])->first();

        $estoques_liquido = floatval($estoques['estoque']) - floatval($estoques['empenho']);
        $empresas = returnEmpresasNasajonView();
        
        $dados["codigo"] = $fields["codigo"];
        $dados["unidade"] = $unidade->unidade;
        $dados["estabelecimento"] = $empresas[intval($fields["estabel"])];
        $dados["total_estoque"] = parserQtd($estoques_liquido);
        $dados["total_reserva"] = parserQtd($estoques['empenho']);
        $dados["total_pecapeca"] = 0;
        $dados["total_empenho"] = 0;
        $dados["peca_peca"] = [];
        $estoque = ["total_peca" => 0, "total_empenho" =>0, "pecas" => []];
        $estoque = $this->getEstoqueNasajon($fields["codigo"], $fields["estabel"]);

        $reserva = 0;
        foreach ($estoque['pecas'] as $key => $peca){
            if($peca['empenho'] === true){
                $reserva += $peca['quantidade'];
            }
            $estoque['pecas'][$key]['lote_pai'] = $peca['lote_pai'];
            $estoque['pecas'][$key]['empenho'] = ($peca['empenho'] == true) ? 'Sim - Pedido: '.$peca['pedido'] : 'Não';
            $estoque['pecas'][$key]['quantidade'] = parserQtd($peca['quantidade']);
            $estoque['pecas'][$key]['data_entrada'] = empty($peca['data_entrada'])? '' : parserData($peca['data_entrada']);
            $estoque['pecas'][$key]['documento_entrada'] = $peca['nota_entrada'];
            $estoque['pecas'][$key]['nota_id'] = $peca['nota_id'];
            $estoque['pecas'][$key]['pcmn'] = $peca['pcmn'];
        }
      
        $dados["total_pecapeca"] = parserQtd($estoque["total_peca"]);
        $dados["total_reserva_pecapeca"] = parserQtd($reserva);
        $dados["peca_peca"] = $estoque["pecas"];

        return view('programs.produto.estoque')->with("dados",$dados);
    }

    private function getEstoqueNasajon($codigo_produto, $estabelecimento, $numped = ""){
        $return = [
            "pecas" => [],
            "total_peca" => 0,
            "total_empenho" => 0,
        ];
        $pecas = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_fracoes_produtos('".$estabelecimento."','".$codigo_produto."')");

        $notasEntradasNasajonObj = NotasEntradasNasajon::whereIn('Número do Documento', array_column($pecas, 'nota_entrada'))->where('Estabelecimento', $estabelecimento)->get();
        
        foreach($pecas as $peca){
            if(!empty($numped) && $peca->empenhado_para_pedido !== $numped){
                continue;
            }

            $notaEntrada = $notasEntradasNasajonObj->firstWhere('Número do Documento', $peca->nota_entrada);
            $notaEntradaPrimeira = $notasEntradasNasajonObj->firstWhere('Número do Documento', $peca->primeira_nota_entrada);

            $linha['volume'] = $peca->fracao_codigo;
            $linha['data_entrada'] = '';
            $linha['documento_entrada'] = '';
            $linha['quantidade'] = $peca->saldo;
            $linha['localizacao'] = $peca->local_de_estoque_endereco;
            $linha['empenho'] = $peca->empenhado;
            $linha['pedido'] = $peca->empenhado_para_pedido;
            $linha['nota_entrada'] = $peca->nota_entrada;
            $linha['data_entrada'] = $peca->data_entrada;
            $linha['lote_pai'] = $peca->fracao_pai;
            $linha['pcmn'] = '';

            if(!is_null($notaEntrada)){
                $linha['nota_id'] = $notaEntrada['Identificador Documento'];
            }
            else{
                $linha['nota_id'] = '';
            }

            if(!is_null($notaEntradaPrimeira)){
                if(!is_null($notaEntradaPrimeira['Pedido'])){
                    $linha['pcmn'] = $notaEntradaPrimeira['Pedido'];
                }
            }

            $return['pecas'][] = $linha;
            $return['total_peca'] += floatval($peca->saldo);
        }
        unset($pecas);
        return $return;
    }

    public function indexReserva(Request $request){
        $fields = $request->only(["codigo", "estabel"]);
        $codprd = $fields["codigo"];
        $estabelecimento = $fields["estabel"];
        $dados_temp = ProdutoEspecificacao::where('codigo_produto', $codprd)->first()->toArray();
        $empresas = returnEmpresasNasajonView();
        $dados = [];
        $dados_temp = $this->parserProduto($dados_temp);
        $dados["original"] = $dados_temp;
        foreach ($dados_temp as $key => $value) {
            $dados[strtolower($key)] = trim(($value));
        }
        $dados["pedidos"] = [];
        $dados["estabelecimento"] = $empresas[intval($estabelecimento)];
        $dados["fields"] = $fields;

        $total_pe = 0;
        $total_pf = 0;
        $total_pedido = 0;
        $protudos_portal = [];
        
        $PedidoPortalObj = PedidoPortal::with(['itens_pedido.comprasNasajon'])->
            whereNotIn('status_pedido', [3, 5, 7])
            ->where("estabelecimento", $estabelecimento)
            ->whereHas('itens_pedido', function($query) use ($codprd){
                $query->where("cod_produto", $codprd);
            });
        $PedidoPortal = $PedidoPortalObj->get();
        $data_agora = Carbon::now();
        
        if(intval($data_agora->format('d')) > 15){
            $data_agora = $data_agora->lastOfMonth();
        }else{
            $data_agora = Carbon::parse($data_agora->format('Y-m').'-15');
        }

        foreach($PedidoPortal as $pedido){
            $cliente = $pedido->cliente;
            $cliente_nome = '';
            if(isset($cliente->CODCAD)){
                $cliente_nome = $cliente->NOME;
            }else{
                $cliente_nome = $cliente->nome;
            }
            $vendedor = $pedido->usuario_detalhes;
            $quantidade = 0;
            $pedido_compra = '';
            foreach($pedido->itens_pedido as $item){
                if($item->cod_produto !== $codprd){
                    continue;
                }
                $quantidade += $item->quantidade;
                $pedido_compra = $item->numero_compra;
                $pedido_compra_previsao_entrega = empty($item->comprasNasajon)? '' : $item->comprasNasajon->previsao_entrega;
            }
            $data_previsao_entrega = Carbon::parse($pedido->data_previsao_entrega);
            $tipo_estoque = '';
            if($pedido->pedido_futuro == false){
                $total_pe += $quantidade;
                $tipo_estoque = 'PRONTA ENTREGA';
            }else{
                if($data_agora->gte($data_previsao_entrega)){
                    $total_pe += $quantidade;
                    $tipo_estoque = 'PRONTA ENTREGA';
                }else{
                    $total_pf += $quantidade;
                    $tipo_estoque = 'FUTURO';
                }                
            }
            $total_pedido = floatval($total_pedido) + floatval($quantidade);
            $dados["pedidos"][] = [
                'id' => $pedido->id,
                "pedido" => $pedido->id,
                "id_nasajon" => "",
                "pedido_nasajon" => "",
                "nota" => "",
                "data_pedido" => $pedido->data_pedido,
                "cliente" => $cliente_nome,
                "vendedor" => $vendedor->name,
                "quantidade" => parserQtd($quantidade),
                "quantidade_separada" => '',
                "status" => $pedido->status_pedido_detalhes->status,
                "origem" => 'portal',
                "pedido_compra" => $pedido_compra,
                "data_previsao_entrega" => empty($pedido_compra_previsao_entrega)? '' : parserData($pedido_compra_previsao_entrega),
                "tipo" => 'pedido',
                'tipo_estoque' =>  $tipo_estoque,
            ];
        }
        
        $protudos_portal = [];
        $PedidosReservaProdutoNasajonObj = PedidosReservaProdutoNasajon::
            where("codigo_estabelecimento", $estabelecimento)
            ->where("codigo_produto", $codprd);
        $PedidosReservaProdutoNasajon = $PedidosReservaProdutoNasajonObj->get();
        foreach($PedidosReservaProdutoNasajon as $pedido){
            $quantidade = 0;
            $quantidade_separada = 0;
            $quantidade = $pedido->quantidade;
            $quantidade_separada = $pedido->quantidade_faturada;
            if($quantidade_separada > 0){
                $total_pedido = floatval($total_pedido) + floatval($quantidade_separada);
                $total_pe = floatval($total_pe) + floatval($quantidade_separada);
            }else{
                $total_pedido = floatval($total_pedido) + floatval($quantidade);
                $total_pe = floatval($total_pe) + floatval($quantidade);
            }
            $dados_pedido = [];
            $tipo = '';
            $pedido_html = $pedido->numero;
            $cliente = '';
            $vendedor = '';
            $status = '';

            $nota = '';
            $pedido_numero = '';
            $id_nasajon = '';
            $emissao = '';
            $pedido_compra = '';
            $pedido_compra_previsao_entrega = '';
            $pedido_portal = '';
            $pedido_portal_id = '';
            $pedido_compra_previsao_entrega = '';

            if($pedido->eh_pedido == false){
                $emissao = $pedido->emissao;
                $tipo = 'nota';
                $dados_pedido = NotasNasajon::where('id', $pedido->id)->with(['revisao_vendedor_comissao', 'revisao_vendedor_comissao.usuario'])->first();
                if(!empty($dados_pedido)){
                    $status = 'Em aberto';
                    $cliente = $dados_pedido->cliente_nome;
                    if(!empty($dados_pedido->revisao_vendedor_comissao)){
                        $vendedor = empty($dados_pedido->usuario)? '' : $dados_pedido->usuario->name;
                    }
                    $nota = "<a href=\"#\" id=\"bt_link\" onclick=\"showNotaDetalhes('{$pedido->id}')\">{$pedido->numero}</a>";
                }else{
                    $dados_pedido = NotasEmAbertoNasajon::where('id', $pedido->id)->with(['revisao_vendedor_comissao', 'revisao_vendedor_comissao.usuario'])->first();
                    $status = 'Em aberto';
                    if(!empty($dados_pedido)){
                        $cliente = $dados_pedido->cliente_nome;
                        if(!empty($dados_pedido->revisao_vendedor_comissao)){
                            $vendedor = '';
                            if(!empty($dados_pedido->usuario->name)){
                                $vendedor = $dados_pedido->usuario->name;
                            }
                        }
                        $nota = "<a href=\"#\" id=\"bt_link\" onclick=\"showNotaDetalhesAberta('{$pedido->id}')\">{$pedido->numero}</a>";
                    }else{
                        $nota = $pedido->numero;
                        $cliente = '';
                        $vendedor = '';
                    }
                }
            }elseif($pedido->eh_pedido == true){
                $tipo = 'pedido';
                $dados_pedido = PedidosVendaNasajon::find($pedido->id);
                $cliente = $dados_pedido->cliente_detalhes->nome;
                $status = $dados_pedido->situacao_descricao;
                $vendedor = $dados_pedido->vendedor_nome;

                $pedido_numero = $pedido->numero;
                $id_nasajon = $pedido->id;

                $emissao = $pedido->emissao;
                $PedidoPortalObj = PedidoPortal::with(['itens_pedido.comprasNasajon'])->
                    where("pedido_gerado", $pedido->numero)
                    ->where("estabelecimento", $estabelecimento)
                    ->where("codigo_operacao", $dados_pedido->operacao_codigo)
                    ->whereHas('itens_pedido', function($query) use ($codprd){
                        $query->where("cod_produto", $codprd);
                    })->first();
                if(!empty($PedidoPortalObj)){
                    $pedido_portal = $PedidoPortalObj->id;
                    $emissao = $PedidoPortalObj->data_pedido;
                    $PedidoPortalObj->itens_pedido->each(function($item) use (&$pedido_compra, $codprd, &$pedido_compra_previsao_entrega){
                        if($item->cod_produto == $codprd){
                            $pedido_compra = $item->numero_compra;
                            $pedido_compra_previsao_entrega = empty($item->comprasNasajon)? '' : $item->comprasNasajon->previsao_entrega;
                        }
                    });
                }
            }

            $dados["pedidos"][] = [
                "id" => $pedido_portal,
                "pedido" => $pedido_portal,
                "id_nasajon" => $id_nasajon,
                "pedido_nasajon" => $pedido_numero,
                "nota" => $nota,
                "data_pedido" => $emissao,
                "cliente" => $cliente,
                "vendedor" => $vendedor,
                "quantidade" => parserQtd($quantidade),
                "quantidade_separada" => parserQtd($quantidade_separada),
                "status" => $status,
                "origem" => "nasajon",
                "pedido_compra" => $pedido_compra,
                "data_previsao_entrega" => empty($pedido_compra_previsao_entrega)? '' : parserData($pedido_compra_previsao_entrega),
                "tipo" => $tipo,
                'tipo_estoque' =>  'PRONTA ENTREGA',
            ];
        }

        $dados["total_pe"] = parserQtd($total_pe);
        $dados["total_pf"] = parserQtd($total_pf);
        $dados["total_pedidos"] = parserQtd($total_pedido);
        return view('programs.produto.reserva')->with("dados",$dados);
    }

    public function indexPecasReserva(Request $request){
        $fields = $request->only(["codigo", "codigo_item", "estabel"]);
        $numped = $fields["codigo"];
        $codprd = $fields["codigo_item"];
        $estabel = $fields["estabel"];
        $dados = [];
        $dados_temp = ProdutoEspecificacao::where('codigo_produto', $codprd)->first()->toArray();
        $empresas = returnEmpresasNasajonView();
        $dados = [];
        $dados_temp = $this->parserProduto($dados_temp);
        $dados["original"] = $dados_temp;
        foreach ($dados_temp as $key => $value) {
            $dados[strtolower($key)] = trim(($value));
        }
        $dados["pedido"] = [];
        $dados["estabelecimento"] = $empresas[intval($estabel)];
        $dados["fields"] = $fields;

        $estoque = [];
        $estoque = $this->getEstoqueNasajon($codprd, $fields["estabel"], $numped);
        foreach ($estoque['pecas'] as $key => $peca){
            $estoque['pecas'][$key]['quantidade'] = parserQtd($peca['quantidade']);
        }
        $dados["total_pecapeca"] = parserQtd($estoque["total_peca"]);
        $dados["total_empenho"] = parserQtd($estoque["total_empenho"]);
        $dados["peca_peca"] = $estoque["pecas"];
        return view('programs.produto.reserva_produto')->with("dados",$dados);
    }

    public function parserStatus($status){
        switch ($status){
            case "1":
                return 'pedido confirmado';
                break;
            case "2":
                return 'alterado, após aprovação';
                break;
            case "3":
                return 'aprovado esperando separação';
                break;
            case "4":
                return 'pedido em Ordem Separação';
                break;
            case "6":
                return 'faturamento complemento';
                break;
            case "7":
                return 'pedido faturado á cancelado saldo';
                break;
            case "9":
                return 'pedido cancelado / reprovado';
                break;
            case "A":
                return 'Em andamento (tratado pela WEB)';
                break;
            case "E":
                return 'Espera por aprovação de prazo e/ou preço (tratado pela WEB)';
                break;
            case "F":
                return 'Pronto para geração pedido definitivo (tratado pela WEB)';
                break;
            case "R":
                return 'Pedido Futuro';
                break;
        }
    }

    public function indexCompras(Request $request){
        $fields = $request->only("codigo", "estabel");
        $codigo = $fields["codigo"];
        $estabel = $fields["estabel"];
        $dados_temp = ProdutoEspecificacao::where('codigo_produto', $codigo)->first()->toArray();
        $empresas = returnEmpresasNasajonView();
        $dados = [];
        $dados_temp = $this->parserProduto($dados_temp);
        $dados["original"] = $dados_temp;
        foreach ($dados_temp as $key => $value) {
            $dados[strtolower($key)] = trim(($value));
        }

        $unidadeConversao = UnidadeConversaoProdutoNasajon::where('codigo_produto', $codigo)->get();

        $comprasNasajon = ComprasNasajon::where('cod_produto', $codigo)->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->where('estabelecimento', $estabel)->get();
        $total_compra = 0;
        $compras = [];
        foreach ($comprasNasajon as $compra) {
            if($compra->situacao === 'Aguardando Documento'){
                $quantidade = floatval($compra->quantidade);
            }else{
                if(floatval($compra->quantidade_restante) == 0){
                   continue; 
                }
                $quantidade = floatval($compra->quantidade_restante);
            }
            $conversao = ($unidadeConversao->firstWhere('codigo_unidadeconversao', $compra->unidade_comercial)->razao) ?? 1;
            $quantidade = $quantidade * $conversao;
            $total_compra += $quantidade;
            $compras[] = [
                'cod_pedido' => $compra->numero_pedido,
                'data_compra' => parserData($compra->data_compra),
                'data_previsao' => parserData($compra->previsao_entrega),
                'quantidade' => parserQtd($quantidade),
                'status' => $compra->situacao,
                'fornecedor' => '',
                'proforma' => $compra->proforma,
                'nota' => ''
            ];
        }

        $transito = DB::connection('nasajon')->table('integracoes.vw_saldos_em_transitos')->where('produto', $codigo)->where('estabelecimento_codigo', $estabel)->get();
        foreach ($transito as $compra) {
            $quantidade = floatval($compra->quantidade);
            $total_compra += $quantidade;
            $compras[] = [
                'cod_pedido' => '',
                'data_compra' => '',
                'data_previsao' => 'Em transito',
                'quantidade' => parserQtd($quantidade),
                'status' => 'Transito',
                'fornecedor' => '',
                'proforma' => '',
                'nota' => $compra->remessa_numero
            ];
        }

        
        $dados["total_compra"] = parserQtd($total_compra);
        $dados["estabelecimento"] = $empresas[intval($estabel)];
        $dados["fields"] = $fields;
        $dados["compras"] = $compras;

        return view('programs.produto.compras')->with("dados",$dados);
    }

    public function indexAnalise(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseProduto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnaliseProduto');
        $header_meses = [];
        $date = date("d");
        $month = intval(date("m"));
        $year = intval(date("Y"));
        if(intval($date) <= 15){
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        }
        $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        $month++;
        for ($i=0; $i < 3; $i++) {
            if($month > 12){
                $month = 1;
                $year++;
            }
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $month++;
        }
        $estabelecimento = returnEmpresasNasajonView();
        unset($estabelecimento[6]);
        unset($estabelecimento[20]);

        $carrinhoCompraObj = CarrinhoCompra::select();
        $carrinhoCompraObj->with(['dadosClientePedido.cliente', 'carrinhoCompraItens', 'pedido']);
        $carrinhoCompraObj->where('finalizado', false);
        $carrinhoCompraObj->where('created_by', Auth::id());
        $carrinhoCompraObj = $carrinhoCompraObj->first();

        $quantidade = 0;

        if(!empty($carrinhoCompraObj->carrinhoCompraItens)){
            $carrinhoCompraObj->carrinhoCompraItens->each(function($item) use(&$quantidade){
                $quantidade++;
            });
        }

        $campanhaObj = Campanha::select()
            ->where('inicio_campanha', '<=', date('Y-m-d'))
            ->where('fim_campanha', '>=', date('Y-m-d'))
            ->where('ativo', true)
            ->get();

        $campanhas = [];
        foreach($campanhaObj as $value){
            $campanhas[$value->id] = $value->nome;
        }

        $segmentos =$this->segmentos();

        return view('programs.analise_produto.index')->with('header_meses', $header_meses)->with('estabelecimento', $estabelecimento)->with('carrinho', empty($carrinhoCompraObj)? false : true)->with('quantidade', $quantidade)->with('campanhas', $campanhas)->with('segmentos', $segmentos);
    }

    public function filterAnaliseTela(Request $request, $response_json = true, $retorna_um_resultado = false, $return_pedido = false, $return_compras = false){

        ini_set('memory_limit', '4048M');
    
        $fields = $request->only(['estabel', 'grupo', 'codigo', 'nome', 'marca', 'linha', 'desativado', 'agrupar', 'limit', 'offset', 'count', 'cod_exato', 'pedido_futuro', 'pedido', 'in_codigos', 'campanha', 'segmentos']);

        if(empty($fields["grupo"]) && strlen($fields["estabel"]) === 0 && empty($fields["codigo"]) && empty($fields["nome"]) && empty($fields["marca"]) && empty($fields["linha"]) && empty($fields["desativado"]) && empty($fields["in_codigos"]) && empty($fields["campanha"]) && empty($fields["segmentos"])){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Informe pelomenos um campo para a busca!"
            ];
    
            if($response_json === false){
                return $return;
            }
            return response()->json($return);
        }
        foreach($fields as $key => $value){
            if(empty($value) || is_array($value)){
                continue;
            }
            $fields[$key] = strip_tags($value);
        }
        $empresas = returnEmpresasNasajonView();
        
        $where = [];
        $where_estoque = [];
        if(!empty($fields['grupo'])){
            $where[] = ['produto_grupos.descricao', 'ilike', $fields['grupo'] ];
        }
        if(!empty($fields['segmentos'])){
            $where[] = ['produto_grupos.segmentos_id', $fields['segmentos'] ];
        }
        if(!empty($fields['codigo'])){
            if (isset($fields['cod_exato'])){
                $where[] = ['codigo_produto', 'ilike', $fields['codigo']];
            }
            else{
                $where[] = ['codigo_produto', 'ilike', $fields['codigo'] . '%' ];
            }
        }
        if(!empty($fields['nome'])){
            $where[] = ['produto_especificacaos.descricao', 'ilike', '%'. $fields['nome'] . '%'];
        }
        if(!empty($fields['marca'])){
            $where[] = ['marca', 'ilike', '%' . $fields['marca'] . '%'];
        }
        if(!empty($fields['linha'])){
            $where[] = ['linha', 'ilike', '%' . $fields['linha'] . '%'];
        }
        if(empty($fields['desativado']) || $fields['desativado'] !== 'sim'){
            $where_estoque[] = ['ativo','true'];
        }
        if(strlen($fields['estabel']) === 0){
            $where_estoque[] = ["estabelecimento", '<>', '00'];
        }else{
            $where_estoque[] = ["estabelecimento", str_pad($fields['estabel'], 2, "0", STR_PAD_LEFT)];
        }
        if($return_pedido !== true){
            $where_estoque[] = ["estabelecimento", '<>', '06'];
        }

        if(isset($fields['campanha'])){
            if(!empty($fields['campanha'])){
                $where_estoque[] = ["campanha_id", $fields['campanha']];
            }
        }

        $query_produto = ProdutoEspecificacao::select('produto_grupos.descricao as grupo',
        'produto_especificacaos.descricao as descricao', 
        'produto_grupos.pecas as pecas',
        'codigo_produto', 
        'marca', 
        'linha', 
        'subgrupo', 
        'unidade', 
        'procedencia', 
        'peso', 
        'ativo', 
        'data_de_cadastro',
		'industrializado',
        'produto_especificacaos.segmentos_id',
        'produto_grupos_id')->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')
        ->with(['foto', 'produtoNasajon', 'estoque'=> function($query) use ($return_pedido){
            $query->orderBy('estabelecimento');
            $query->with('campanha');
            if($return_pedido !== true){
                $query->where("estabelecimento", '!=', '06');
            }
        }])
        ->whereHas('estoque', function($query) use ($where_estoque){
            $query->where($where_estoque);
        })
        ->where($where)
        ->orderBy('produto_grupos.descricao', 'asc');
    
        if(!empty($fields['in_codigos'])){
            $query_produto->whereRaw('UPPER(codigo_produto) in '. $fields['in_codigos']);
        }
    
        if($return_pedido === false){
            if (isset($fields['cod_exato'])){
                $query_produto->limit(1);
            }
        }
    
        try{
            $query_produto = $query_produto->get();
        } catch(\Exception $e){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Ocorreu uma instabilidade!<br />Tente novamente!",
                "error" => $e
            ];
            if($response_json === false){
                return $return;
            }
            return response()->json($return);
        }

        if($query_produto->count() === 0 && (!isset($fields['pedido_futuro']) || $fields['pedido_futuro'] === false)){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Nenhum produto com estoque disponivel encontrado!"
            ];
            if($response_json === false){
                return $return;
            }
            return response()->json($return);
        }
    
        $key = 0;
        $results = [];
        $produtos = [];
    
        $produtos = [];
        $codProds = [];
    
        $codProds = $query_produto->pluck('codigo_produto');
    
        foreach ($query_produto as $produto) {
            $ncm = null;
            $ean = null;
    
            if(isset($produto->produtoNasajon->codigodebarras) && !empty($produto->produtoNasajon->codigodebarras)){
                $ean = $produto->produtoNasajon->codigodebarras;
            }
    
            if(isset($produto->produtoNasajon->ncm) && !empty($produto->produtoNasajon->ncm)){
                $ncm = $produto->produtoNasajon->ncm;
            }
    
            if(floatval($produto->empenho) < 0){
                $produto->empenho = 0;
            }

            $bookVirtualExibicao = new BookVirtualExibicaoControllerNew;
            $requestBookVirtual = new BookVirtualFiltroPrincipalRequest([
                'codigo_produto' => $produto->codigo_produto,
                'grupo' => '',
                'marca' => '',
                'linha' => '',
                'tipo_venda' => '',
                'codigos_produtos' => '',
                'codigo_desenho' => '',
                'desenho_produtos' => '',
                'busca_navegacao' => null,
                'campanha' => '',
            ]);
            $retorno = $bookVirtualExibicao->gerarLinkBusca($requestBookVirtual);

            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retorno;
            }
            
            $produtos[$produto->codigo_produto]["link_carrinho"] = $retorno->getData()->response->link;
            $produtos[$produto->codigo_produto]["codigo"] = $produto->codigo_produto;
            $produtos[$produto->codigo_produto]["linha"] = $produto->linha;
            $produtos[$produto->codigo_produto]["marca"] = $produto->marca;
            $produtos[$produto->codigo_produto]["grupo"] = $produto->grupo;
            $produtos[$produto->codigo_produto]["descricao"] = $produto->descricao;
            $produtos[$produto->codigo_produto]["grupo"] = $produto->grupo;
            $produtos[$produto->codigo_produto]["unidade"] = $produto->unidade;
            $produtos[$produto->codigo_produto]["compras"] = [];
            $produtos[$produto->codigo_produto]["compras_futuras"] = [];
            $produtos[$produto->codigo_produto]["total_show"] = 0.0;
            $produtos[$produto->codigo_produto]["total_pronta_entrega"] = 0.0;
            $produtos[$produto->codigo_produto]["transito"] = 0.0;
            $produtos[$produto->codigo_produto]["ncm"] = $ncm;
            $produtos[$produto->codigo_produto]["ean"] = $ean;
            $produtos[$produto->codigo_produto]["pecas"] = $produto->pecas;
            $produtos[$produto->codigo_produto]["campanha"] = '';
    
            $produtos[$produto->codigo_produto]["estoque_empresa"] = [];
            foreach($produto->estoque as $estoque){
                if(!empty($estoque->campanha)){
                    $produtos[$estoque->codigo_produto]["campanha"] = $estoque->campanha->nome;
                }
                if(strlen($fields['estabel']) !== 0 && $estoque->estabelecimento != str_pad($fields['estabel'], 2, "0", STR_PAD_LEFT)){
                    continue;
                }
                $produtos[$estoque->codigo_produto]["estoque_empresa"][] = [
                    "estabel" => $estoque->estabelecimento,
                    "quantidade_estoque" => floatval($estoque->estoque),
                    "quantidade_empenho" => floatval($estoque->empenho),
                    "quantidade_disponivel" => (floatval($estoque->estoque) - floatval($estoque->empenho))
                ];
                $produtos[$estoque->codigo_produto]["estoque_estabelecimentos"][intval($estoque->estabelecimento)] = (floatval($estoque->estoque) - floatval($estoque->empenho));
                if(
                    isset($produtos[$estoque->codigo_produto]["total_pronta_entrega"]) &&
                    floatval($produtos[$estoque->codigo_produto]["total_pronta_entrega"]) > 0
                ){
                    $produtos[$estoque->codigo_produto]["total_pronta_entrega"] += (floatval($estoque->estoque) - floatval($estoque->empenho));
                }else{
                    $produtos[$estoque->codigo_produto]["total_pronta_entrega"] = (floatval($estoque->estoque) - floatval($estoque->empenho));
                }
                if(in_array($estoque->estabelecimento, ['03', '04'])){
                    $produtos[$estoque->codigo_produto]["transito"] = $estoque->saldo_fiscal + $estoque->saldo_movimento_nao_efetivado;
                }
            }
    
            $date = date("d");
            $month = intval(date("m"));
            $year = intval(date("Y"));
            if(intval($date) <= 15){
                $produtos[$produto->codigo_produto]["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = [ "data" => "", "pcmn" => "", "quantidade" => " ", "pedidos" => " " ];
            }
            $produtos[$produto->codigo_produto]["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = [ "data" => "", "pcmn" => "", "quantidade" => " ", "pedidos" => " " ];
            if($produtos[$estoque->codigo_produto]["transito"] > 0){
                $produtos[$produto->codigo_produto]["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_".(intval($date) <= 15 ? '1' : '2')]["quantidade"] = $produtos[$estoque->codigo_produto]["transito"];
            }
            $month++;
    
            for ($i=0; $i < 3; $i++) {
                if($month > 12){
                    $month = 1;
                    $year++;
                }
                $produtos[$produto->codigo_produto]["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = [ "data" => "", "pcmn" => "", "quantidade" => "", "pedidos" => "" ];
                $produtos[$produto->codigo_produto]["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = [ "data" => "", "pcmn" => "", "quantidade" => "", "pedidos" => "" ];
                $month++;
            }
    
        }
    
        unset($query_produto);
        if(count($produtos) === 0 && (!isset($fields['pedido_futuro']) || $fields['pedido_futuro'] === false)){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Nenhum produto com estoque disponivel encontrado!"
            ];
            if($response_json === false){
                return $return;
            }
            return response()->json($return);
        }
    
        $data_sql = date("Y-m-d 00:00:00");
        $data_ano_sql = date("Y");
        $data_mes_sql = date("m");
    
        if(strlen($fields['estabel']) === 0){
            $where_empresa_pedidos = "estabelecimento <> '00'";
        }else{
            $where_empresa_pedidos = "estabelecimento = '".intval($fields['estabel'])."'";
        }
        $compras_pedidos = [];
    
        $compras_pedidos_nasajon = [];
        $tableUnidadeConversao = new UnidadeConversaoProdutoNasajon;
        $tableCompras = new ComprasNasajon;
        if(strlen($fields['estabel']) === 0){
            try{
                $compras_pedidos_nasajon = ComprasNasajon::
                select("estabelecimento", "cod_produto", DB::Raw("TO_CHAR(previsao_entrega, 'YYYY_MM') AS ano_mes, CASE WHEN CAST(TO_CHAR(previsao_entrega, 'DD') AS INT) <= 15 THEN 1 ELSE 2 END AS quinzena, SUM(quantidade_restante * CASE WHEN ".$tableUnidadeConversao->getTable().".razao IS NOT NULL THEN ".$tableUnidadeConversao->getTable().".razao ELSE 1 END) AS quantidade"))->
                leftJoin($tableUnidadeConversao->getTable(), function($query) use ($tableUnidadeConversao, $tableCompras){
                    $query->on($tableUnidadeConversao->getTable().".codigo_unidadeconversao", $tableCompras->getTable().".unidade_comercial");
                    $query->on($tableUnidadeConversao->getTable().".codigo_produto", $tableCompras->getTable().".cod_produto");
                })->
                whereIn('cod_produto', $codProds)->
                whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->
                where('estabelecimento', '!=', '06')->
                groupBy(DB::Raw("estabelecimento, cod_produto, TO_CHAR(previsao_entrega, 'YYYY_MM'), CASE WHEN CAST(TO_CHAR(previsao_entrega, 'DD') AS INT) <= 15 THEN 1 ELSE 2 END"));
                $compras_pedidos_nasajon = $compras_pedidos_nasajon->get();
            } 
            catch(\Exception $e){
                $return = [
                    "data" => [],
                    "status" => "error",
                    "message" => "Ocorreu uma instabilidade!<br />Tente novamente!",
                    'error' => $e
                ];
                if($response_json === false){
                    return $return;
                }
                return response()->json($return);
            }
        }elseif(intval($fields['estabel']) != 6){
            try{
                $compras_pedidos_nasajon = ComprasNasajon::
                    selectRaw("estabelecimento, cod_produto, TO_CHAR(previsao_entrega, 'YYYY_MM') AS ano_mes, CASE WHEN CAST(TO_CHAR(previsao_entrega, 'DD') AS INT) <= 15 THEN 1 ELSE 2 END AS quinzena, SUM(quantidade_restante * CASE WHEN ".$tableUnidadeConversao->getTable().".razao IS NOT NULL THEN ".$tableUnidadeConversao->getTable().".razao ELSE 1 END) AS quantidade")->
                    leftJoin($tableUnidadeConversao->getTable(), function($query) use ($tableUnidadeConversao, $tableCompras){
                        $query->on($tableUnidadeConversao->getTable().".codigo_unidadeconversao", $tableCompras->getTable().".unidade_comercial");
                        $query->on($tableUnidadeConversao->getTable().".codigo_produto", $tableCompras->getTable().".cod_produto");
                    })->
                    whereIn('cod_produto', $codProds)->
                    where('quantidade_restante', '>', 0)->
                    whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->
                    where('estabelecimento', str_pad(intval($fields['estabel']), 2, "0", STR_PAD_LEFT))->
                    groupBy(DB::Raw("estabelecimento, cod_produto, TO_CHAR(previsao_entrega, 'YYYY_MM'), CASE WHEN CAST(TO_CHAR(previsao_entrega, 'DD') AS INT) <= 15 THEN 1 ELSE 2 END"))->
                    get();
            } catch(\Exception $e){
                $return = [
                    "data" => [],
                    "status" => "error",
                    "message" => "Ocorreu uma instabilidade!<br />Tente novamente!",
                    'error' => $e,
                ];
                if($response_json === false){
                    return $return;
                }
                return response()->json($return);
            }
        }
    
        $total_compras = [];
        foreach($compras_pedidos_nasajon as $compra){
            $data = explode("_", $compra->ano_mes);
            $ano = $data[0];
            $mes = $data[1];
            $quinzena = $compra->quinzena;
    
            $ano_mes_quinzena = $compra->ano_mes . '_' . $quinzena;
            $produtos[$compra->cod_produto]["total_show"] += floatval($compra->quantidade);
            if(!isset($total_compras[$compra->cod_produto][intval($compra->estabelecimento)])){
                $total_compras[$compra->cod_produto][intval($compra->estabelecimento)] = 0;
            }
            $total_compras[$compra->cod_produto][intval($compra->estabelecimento)] += floatval($compra->quantidade);
            if(strtotime($ano . '-' . $mes . '-' .(($quinzena === 2) ? 16 : 1) ) < strtotime(date('Y-m-d'))){
                $data = explode("-", date('Y-m-d'));
                $ano = $data[0];
                $mes = $data[1];
                $quinzena = ((intval($data[2]) <= 15) ? '1' : '2' );
                $ano_mes_quinzena = "{$ano}_{$mes}_{$quinzena}";
            }
            if(isset($produtos[$compra->cod_produto]["compras"]["k_".$ano_mes_quinzena])){
                if(empty(trim($produtos[$compra->cod_produto]["compras"]["k_".$ano_mes_quinzena]['quantidade']))){
                    $produtos[$compra->cod_produto]["compras"]["k_".$ano_mes_quinzena] = [
                        "mes" => $ano,
                        "ano" => $mes,
                        "quinzena" => $quinzena,
                        "pedidos" => 0.0,
                        "quantidade" => floatval($compra->quantidade)
                    ];
                }else{
                    $produtos[$compra->cod_produto]["compras"]["k_".$ano_mes_quinzena]['quantidade'] += floatval($compra->quantidade);
                }
            }else{
                if($return_compras === false){
                    $produtos[$compra->cod_produto]["compras_futuras"] =+ floatval($compra->quantidade);
                }else{
                    $produtos[$compra->cod_produto]["compras"]["k_".$ano_mes_quinzena] = [
                        "mes" => $ano,
                        "ano" => $mes,
                        "quinzena" => $quinzena,
                        "pedidos" => 0.0,
                        "quantidade" => floatval($compra->quantidade)
                    ];
                }
            }
        }
        $PedidoPortalObj = PedidoPortal::with(['itens_pedido.comprasNasajon' => function($query) use ($codProds){
                $query->whereIn("cod_produto", $codProds);
            }])
            ->whereNotIn('status_pedido', [3, 5, 7])
            ->whereRaw($where_empresa_pedidos)
            ->whereHas('itens_pedido', function($query) use ($codProds){
                $query->whereIn("cod_produto", $codProds);
            })
            ->orderBy('data_pedido');
    
        if(isset($fields['pedido'])){
            $PedidoPortalObj->where('id', '!=', $fields['pedido']);
        }
    
        $PedidoPortal = $PedidoPortalObj->get();
        $total_empenho_compras = [];
        $itens_reservado = [];

        $PedidoPortal->each(function($pedido) use (&$produtos, &$itens_reservado, &$teste){
            $pedido->itens_pedido->each(function($item_pedido) use (&$produtos, $pedido, &$itens_reservado, &$teste){
                if($pedido->pedido_futuro == true){
                    $dataPrevisaoCarbon = Carbon::parse($pedido->data_previsao_entrega)->setTime(0,0,0);
                    $dataHoje = Carbon::now()->setTime(0,0,0);
                    if(!empty($item_pedido->comprasNasajon)){
                        if(in_array($item_pedido->comprasNasajon->situacao, ['Cancelado', 'Aberto']) || in_array($item_pedido->comprasNasajon->situacao_item, ['Cancelado', 'Liquidado'])){
                            if(empty($itens_reservado[$item_pedido->cod_produto]['pronta_entrega'][$pedido->estabelecimento])){
                                $itens_reservado[$item_pedido->cod_produto]['pronta_entrega'][$pedido->estabelecimento] = 0;
                            }
            
                            $itens_reservado[$item_pedido->cod_produto]['pronta_entrega'][$pedido->estabelecimento] += $item_pedido->quantidade;
                        }else{
                            if($dataHoje->gte($dataPrevisaoCarbon)){
                                $dataPrevisaoCarbon = $dataHoje;
                            }
                            $ano_mes_quinzena = $dataPrevisaoCarbon->format('\k_Y_m_') . ($dataPrevisaoCarbon->format('d') <= 15? "1": "2");
                
                            if(empty($itens_reservado[$item_pedido->cod_produto][$ano_mes_quinzena][$pedido->estabelecimento])){
                                $itens_reservado[$item_pedido->cod_produto][$ano_mes_quinzena][$pedido->estabelecimento] = 0;
                            }
    
                            $itens_reservado[$item_pedido->cod_produto][$ano_mes_quinzena][$pedido->estabelecimento] += $item_pedido->quantidade;
                        }
                    }else{
                        if($dataHoje->gte($dataPrevisaoCarbon)){
                            $dataPrevisaoCarbon = $dataHoje;
                        }
                        $ano_mes_quinzena = $dataPrevisaoCarbon->format('\k_Y_m_') . ($dataPrevisaoCarbon->format('d') <= 15? "1": "2");
                
                        if(empty($itens_reservado[$item_pedido->cod_produto][$ano_mes_quinzena][$pedido->estabelecimento])){
                            $itens_reservado[$item_pedido->cod_produto][$ano_mes_quinzena][$pedido->estabelecimento] = 0;
                        }

                        $itens_reservado[$item_pedido->cod_produto][$ano_mes_quinzena][$pedido->estabelecimento] += $item_pedido->quantidade;
                    }                    
                }else{
                    if(empty($itens_reservado[$item_pedido->cod_produto]['pronta_entrega'][$pedido->estabelecimento])){
                        $itens_reservado[$item_pedido->cod_produto]['pronta_entrega'][$pedido->estabelecimento] = 0;
                    }
    
                    $itens_reservado[$item_pedido->cod_produto]['pronta_entrega'][$pedido->estabelecimento] += $item_pedido->quantidade;
                }
            });
        });

        $PedidosVendaNasajonObj = PedidosReservaProdutoNasajon::whereIn('codigo_produto', $codProds)->get();
        foreach($PedidosVendaNasajonObj as $pedido){
            if(!empty($produtos[$pedido['codigo_produto']])){
                if(empty($itens_reservado[$pedido['codigo_produto']]['pronta_entrega'][intval($pedido['codigo_estabelecimento'])])){
                    $itens_reservado[$pedido['codigo_produto']]['pronta_entrega'][intval($pedido['codigo_estabelecimento'])] = 0;
                }
    
                $itens_reservado[$pedido['codigo_produto']]['pronta_entrega'][intval($pedido['codigo_estabelecimento'])] += $pedido['quantidade'];
            }
        }
        
        $acumulo_anterior = [];
        foreach($produtos as $produto_codigo => $produto){
            if(empty($acumulo_anterior[$produto_codigo])){
                $acumulo_anterior[$produto_codigo] = 0; 
            }
    
            if(!empty($itens_reservado[$produto_codigo]['pronta_entrega'])){
                foreach($itens_reservado[$produto_codigo]['pronta_entrega'] as $estabelecimento => $reserva_pronta_entrega){
                    $produtos[$produto_codigo]["total_pronta_entrega"] -= $reserva_pronta_entrega;
                    if(empty($produtos[$produto_codigo]['estoque_estabelecimentos'][$estabelecimento])){
                        $produtos[$produto_codigo]['estoque_estabelecimentos'][$estabelecimento] = 0;
                    }
                    $produtos[$produto_codigo]['estoque_estabelecimentos'][$estabelecimento] -= $reserva_pronta_entrega;
                }

                if($produtos[$produto_codigo]["total_pronta_entrega"] < 0){
                    $cumulo_anterior[$produto_codigo] = (-1) * $produtos[$produto_codigo]["total_pronta_entrega"];
                }
            }
            $indexs_compras = [];
            foreach($produto["compras"] as $quinzena_compra => $compra){
                if(!empty($itens_reservado[$produto_codigo][$quinzena_compra])){
                    if(empty(trim($compra["quantidade"]))){
                        foreach($itens_reservado[$produto_codigo][$quinzena_compra] as $estabelecimento => $reserva_item){
                            $acumulo_anterior[$produto_codigo] += $reserva_item;
                        }
                    }else{
                        $total_reservado = 0; 
    
                        foreach($itens_reservado[$produto_codigo][$quinzena_compra] as $estabelecimento => $reserva_item){
                            $total_reservado += $reserva_item;
                        }
    
                        $total_reservado += $acumulo_anterior[$produto_codigo];
                        $acumulo_anterior[$produto_codigo] = 0;
    
                        $produtos[$produto_codigo]["compras"][$quinzena_compra]["quantidade"] -= $total_reservado;
    
                        if($produtos[$produto_codigo]["compras"][$quinzena_compra]["quantidade"] < 0 ){
                            $acumulo_anterior[$produto_codigo] = (-1) * $produtos[$produto_codigo]["compras"][$quinzena_compra]["quantidade"];
                        }
                    }
                }
                $indexs_compras[] = $quinzena_compra;
            }

            if(!empty($itens_reservado[$produto_codigo])){
                foreach($itens_reservado[$produto_codigo] as $index_item_reservado => $item_reservado){
                    if(!in_array($index_item_reservado, $indexs_compras)){
                        foreach($item_reservado as $estabelecimento => $reserva_item){
                            $acumulo_anterior[$produto_codigo] += $reserva_item;
                        }
                    }
                }
            }            
        }

        foreach($acumulo_anterior as $produto_codigo => $acumulo){
            if(!empty($acumulo_anterior[$produto_codigo])){
                if(!empty($produtos[$produto_codigo]["compras_futuras"])){
                    $produtos[$produto_codigo]["compras_futuras"] -= $acumulo;
                    $acumulo_anterior[$produto_codigo] = 0;
                }
            }
            
        }

        foreach($produtos as $codigo_produto => $produto){
            $compras_negativa = 0;
            foreach($produto['compras'] as $compras){
                if(!empty($compras['quantidade']) && floatval($compras['quantidade']) < 0){
                    $compras_negativa += floatval($compras['quantidade']);
                    $produtos[$codigo_produto]['estoque_estabelecimentos'][3] += floatval($compras['quantidade']);
                    $produtos[$codigo_produto]['total_pronta_entrega'] += floatval($compras['quantidade']);
                }
            }
        }
        
        foreach($produtos as $index_produto => $produto){
            $estoque_negativo = 0; 
            $produtos[$index_produto]['total_pronta_entrega'] = 0;
            foreach($produto['estoque_estabelecimentos'] as $index_estabelecimento => $estoque_estabelecimento){
                if($estoque_estabelecimento < 0){
                    $estoque_negativo = (-1) * $estoque_estabelecimento; 
                }else{
                    $produtos[$index_produto]['total_pronta_entrega'] += floatval($estoque_estabelecimento);
                }
            }
            foreach($produto['compras'] as $index_compras => $compra){
                if(!empty(trim($compra['quantidade']))){
                    if($estoque_negativo > 0){
                        $aux_estoque = $produtos[$index_produto]['compras'][$index_compras]['quantidade'];
                        $produtos[$index_produto]['compras'][$index_compras]['quantidade'] -= $estoque_negativo;
                        $estoque_negativo -= $aux_estoque;
    
                        if($produtos[$index_produto]['compras'][$index_compras]['quantidade'] < 0){
                            $produtos[$index_produto]['compras'][$index_compras]['quantidade'] = 0;
                        }
                    }else{
                        if($produtos[$index_produto]['compras'][$index_compras]['quantidade'] < 0){
                            $produtos[$index_produto]['compras'][$index_compras]['quantidade'] = 0;
                        }else{
                            $produtos[$index_produto]['compras'][$index_compras]['quantidade'] = parserFloat10($compra['quantidade']);
                        }
                    }
                }
            }
        }

        $results_return = [];
        foreach ($produtos as $key => $result) {
            if(floatval($result["total_pronta_entrega"]) <= 0.0 && floatval($result["compras_futuras"]) <= 0.0 && floatval($result["total_show"]) <= 0.0){
                $produtos[$key] = [];
                continue;
            }
            if(isset($fields['agrupar']) && $fields['agrupar'] == 'sim'){
    
                if(empty($result["grupo"])){
                    $result["grupo"] = 'A CADASTRAR';
                }
    
                if(!isset($results_return[$result["grupo"]])){
                    $results_return[$result["grupo"]] = [
                        "codigo" => '',
                        "descricao" => '',
                        "grupo" => $result["grupo"],
                        "pronta_entrega" => 0.0,
                        "total_popover" => '',
                        "estoque_estabelecimentos" => [],
                        "futuro" => 0,
                        'quinzenas' => [],
                        'campanha' => '',
                    ];
                }
    
                if(!empty($result["total_pronta_entrega"])){
                    $results_return[$result["grupo"]]["pronta_entrega"] += $result["total_pronta_entrega"];
                }
                if(!empty($result["campanha"])){
                    $results_return[$result["grupo"]]["pronta_entrega"] += $result["total_pronta_entrega"];
                }
    
                if(!empty($result["compras_futuras"])){
                    $results_return[$result["grupo"]]["futuro"] += parserNumber($result["compras_futuras"]);
                }
    
                foreach($result["estoque_estabelecimentos"] as $estabelecimento => $estoque){
                    if(!isset($results_return[$result["grupo"]]['estoque_estabelecimentos'][intval($estabelecimento)])){
                        $results_return[$result["grupo"]]['estoque_estabelecimentos'][$estabelecimento] = 0;
                    }
    
                    if($estoque > 0){
                        $results_return[$result["grupo"]]['estoque_estabelecimentos'][$estabelecimento] += $estoque;
                    }
                }
    
                foreach($result["compras"] as $quinzena => $dados){
    
                    if(!isset($results_return[$result["grupo"]]["quinzenas"][$quinzena])){
                        $results_return[$result["grupo"]]["quinzenas"][$quinzena] = [
                            'pedidos' => 0.0,
                            'quantidade' => 0.0
                        ];
                    }
    
                    $results_return[$result["grupo"]]["quinzenas"][$quinzena]['pedidos'] += parserNumber($dados['pedidos']);
    
                    $results_return[$result["grupo"]]["quinzenas"][$quinzena]['quantidade'] += parserNumber($dados['quantidade']);
    
                }
    
                unset($produtos[$key]);
            }
            else{
                $results_return[] = [
                    "link_carrinho" => $result["link_carrinho"],
                    "codigo" => $result["codigo"],
                    "descricao" => $result["descricao"],
                    "linha" => $result["linha"],
                    "grupo" => $result["grupo"],
                    "ncm" => $result["ncm"],
                    "ean" => $result["ean"],
                    "pecas" => $result["pecas"],
                    "marca" => $result["marca"],
                    "unidade" => $result["unidade"],
                    "pronta_entrega" => (floatval($result["total_pronta_entrega"] > 0.0) ? parserQtd($result["total_pronta_entrega"]) : ""),
                    "total_popover" => $result["estoque_empresa"],
                    "estoque_estabelecimentos" => $result["estoque_estabelecimentos"],
                    "quinzenas" => $result["compras"],
                    "futuro" => (!empty($result["compras_futuras"]) ? parserQtd($result["compras_futuras"]) : " "),
                    "title_modal" => "".$result["codigo"]." - ".$result["grupo"]." - ".$result["descricao"]."",
                    "campanha" => $result["campanha"],
                ];
                unset($produtos[$key]);
            }
        }
        if(count($results_return) === 0 && (!isset($fields['pedido_futuro']) || $fields['pedido_futuro'] === false)){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Nenhum produto com estoque disponivel encontrado!",
                'resultado' => $results_return,
                'request_recebido' => $request->toArray()
            ];
            if($response_json === false){
                return $return;
            }
            return response()->json($return);
        }
        if(isset($fields["offset"]) && isset($fields["limit"])){
            $temp_result = array_values($results_return);
            $results_return = [];
            for($i = intval($fields["offset"]); $i < (intval($fields["limit"]) + intval($fields["offset"])); $i++){
                if(isset($temp_result[$i])){
                    $results_return[] = $temp_result[$i];
                }
            }
        }
        $return = [
            "data" => $results_return,
            "status" => "success"
        ];
        $return = $this->ajusteEstoqueEstabelecimento($return);
        
        if(isset($fields["offset"]) && isset($fields["limit"])){
            $return["total"] = count(array_values($temp_result));
        }else{
            $return['total_coluna'] = [
                'pronta_entrega' => 0.0,
                'futuro' => 0.0
            ];
            $date = date("d");
            $month = intval(date("m"));
            $year = intval(date("Y"));
            if(intval($date) <= 15){
                $return['total_coluna']["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = 0.0;
            }
            $return['total_coluna']["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = 0.0;
            $month++;
            for ($i=0; $i < 3; $i++) {
                if($month > 12){
                    $month = 1;
                    $year++;
                }
                $return['total_coluna']["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = 0.0;
                $return['total_coluna']["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = 0.0;
                $month++;
            }
            foreach ($return['data'] as $key => $value) {
                $return['total_coluna']['pronta_entrega'] += parserNumber($value['pronta_entrega_total']);
                foreach ($value['quinzenas'] as $chave => $quinzena) {
                    if(!isset($return['total_coluna'][$chave])){
                        $return['total_coluna'][$chave] = 0;
                    }
    
                    if(isset($fields['agrupar']) && $fields['agrupar'] == 'sim'){
                        $return['total_coluna'][$chave] += $quinzena['quantidade'];
                    }
                    else{
                        $return['total_coluna'][$chave] += empty(trim($quinzena['quantidade']))? 0 : $quinzena['quantidade'];
                    }
                    
                    if($quinzena['quantidade'] > 0){
                        $return['data'][$key]['quinzenas'][$chave]['quantidade'] = parserQtd($quinzena['quantidade']);
                    }
                    else{
                        $return['data'][$key]['quinzenas'][$chave]['quantidade'] = '';
                    }
                }
    
                if(isset($fields['agrupar']) && $fields['agrupar'] == 'sim'){
    
                    if( $value['futuro'] > 0){
                        $return['data'][$key]['futuro'] = parserValor($value['futuro']);
                        $return['total_coluna']['futuro'] += $value['futuro'];
                    }
                    else{
                        $return['data'][$key]['futuro'] = '';
                    }
                }
                else{
                    $return['total_coluna']['futuro'] += parserNumber($value['futuro']);
                }
            }
            foreach ($return['total_coluna'] as $key => $value) {
                if(!empty($value)){
                    $return['total_coluna'][$key] = parserQtd($value);
                }else{
                    $return['total_coluna'][$key] = '';
                }
            }
            
        }
        if($retorna_um_resultado === true){
            $return['data'] = reset($return['data']);
        }
    
        if(empty($return['data'])){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Nenhum produto com estoque disponivel encontrado!"
            ];
            if($response_json === false){
                return $return;
            }
            return response()->json($return);
        }
    
        if($response_json === false){
            return $return;
        }
    
        return response()->json($return);
    }

    public function modalPesquisaProdutos(Request $request){

        $pesquisa['codprod'] = $request->codprod; 
        $pesquisa['descr'] = $request->descr; 
        
        return view('programs.produto.search_modal')->with(['pesquisa' => $pesquisa]);
    }

    
    public function exportAnalisePedido(Request $request){

        $fields = $request->only('agrupar');
        $dados = $this->filterAnaliseTela($request, false);
        $total = $dados['total_coluna'];
        $dados = $dados["data"];

        $arquivo = 'analise_estoque.xls';
        $header_meses = [];
        $date = date("d");
        $month = intval(date("m"));
        $year = intval(date("Y"));
        if(intval($date) <= 15){
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        }
        $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        $month++;
        for ($i=0; $i < 3; $i++) {
            if($month > 12){
                $month = 1;
                $year++;
            }
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $month++;
        }
        $html = "";
        
        if(isset($fields['agrupar']) && $fields['agrupar'] == 'sim'){
            $html .= "<table>".
                        "<tr>".
                            "<th colspan=\"".(count($header_meses) + 3)."\" align=\"center\">Analise de Produto</th>".
                        "</tr>".
                        "<tr>".
                            "<th>GRUPO</th>".
                            "<th>PRONTA ENTREGA</th>";
        }
        else{
            $html .= "<table>".
                        "<tr>".
                            "<th colspan=\"".(count($header_meses) + 4)."\" align=\"center\">Analise de Produto</th>".
                        "</tr>".
                        "<tr>".
                            "<th>CÓDIGO</th>".
                            "<th>DESCRIÇÃO</th>".
                            "<th>PRONTA ENTREGA</th>";
        }
                    foreach($header_meses as $header_mes):
                    $html .= "<th>{$header_mes}</th>";
                    endforeach;
        $html .=        "<th>FUTURO</th>".
                    "</tr>";
        foreach ($dados as $key => $value) {
            $value = (array) $value;
            $value["quinzenas"] = (array) $value["quinzenas"];

            if(isset($fields['agrupar']) && $fields['agrupar'] == 'sim'){
                $html .= "<tr>".
                    "<td>{$value["grupo"]}</td>".
                    "<td>{$value["pronta_entrega_total"]} </td>";
            }
            else{
                $html .= "<tr>".
                        "<td>{$value["codigo"]}</td>".
                        "<td>{$value["descricao"]}</td>".
                        "<td>{$value["pronta_entrega_total"]} </td>";
            }
                    foreach($header_meses as $key_mes => $header_mes):
                        $value["quinzenas"]["k_".$key_mes] = (array) $value["quinzenas"]["k_".$key_mes];
                    $html .= "<td>".($value["quinzenas"]["k_".$key_mes]["quantidade"])." </td>";
                    endforeach;
            $html .= "<td>{$value["futuro"]} </td>".
                    "</tr>";
        }
        if(isset($fields['agrupar']) && $fields['agrupar'] == 'sim'){
            $html_total = "<tr>".
            "<td>Total</td>".
            "<td>:pronta_entrega</td>";
        }
        else{
            $html_total = "<tr>".
                "<td></td>".
                "<td>Total</td>".
                "<td>:pronta_entrega</td>";
        }
        foreach($header_meses as $key => $header_mes):
            $html_total .= "<td>:k_{$key}</td>";
        endforeach;
        $html_total .= "<td>:futuro</td>".
            "</tr>";
        foreach ($total as $key => $value) {
            $html_total = str_replace(":".$key, $value, $html_total);
        }
        $html .= $html_total;
        $html .= "</table>";
        header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Pragma: no-cache");
        header ("Content-type: application/vnd.ms-excel; charset=UTF-8");
        header ("Content-Disposition: attachment; filename=\"{$arquivo}\"" );
        header ("Content-Transfer-Encoding: BINARY");
        header ("Content-Description: MN Tecidos" );
        echo utf8_decode($html);
        exit;
    }

    static public function retornaInformacoesProduto(Request $request){
        $produtoObj = ProdutoEspecificacao::where('codigo_produto', $request->codigo_produto)->first();

        if (empty($produtoObj)){
            return [
                'codigo_produto' => '',
                'descricao' => '',
                'marca' => '',
                'linha' => '',
                'grupo' => '',
                'subgrupo' => '',
            ];
        }
        else{
            return [
                'codigo_produto' => $produtoObj->codigo_produto,
                'descricao' => $produtoObj->descricao,
                'marca' => $produtoObj->marca,
                'linha' => $produtoObj->linha,
                'grupo' => $produtoObj->grupo,
                'subgrupo' => $produtoObj->subgrupo,
            ];
        }
    }

    public function retornaInformacoesPreco(Request $request, $retorno_array = false, $ignorar_estoque = false){
        $fields = $request->only('codprd', 'pedido', 'preco_base_antes', 'preco_antes', 'comissao_antes');
        $pedidoObj = PedidoPortal::findOrFail($fields['pedido']);
        $return = ['linha' => ''];
        $fields['codprd'] = strtoupper($fields['codprd']);
        
        $produtoObj = ProdutoEspecificacao::with(['produtoGrupo'])->where('codigo_produto', $fields['codprd'])->first();
        if($produtoObj->ativo == false){
            return response()->json(['msg' => 'Produto desativado'], 422);
        }

        $return['grupo'] = $produtoObj->produtoGrupo->descricao;
        $return['unidade'] = $produtoObj->unidade;
        $return['codigo'] = $produtoObj->codigo_produto;

        $precoObj = Preco::find($produtoObj->codigo_produto);
        if(strtolower($produtoObj->linha) == 'outlet'){
            $return['linha'] = strtolower($produtoObj->linha);
        }else if(strtolower($produtoObj->linha) == 'ultra black'){
            $return['linha'] = strtolower($produtoObj->linha);
        }
        switch ($pedidoObj->estabelecimento) {
            case "03":
            case 3:
                $origem  = "RO";
                break;
            case "04":
            case 4:
                $origem = "TO";
                break;
            default:
                $origem = 'SP';
                break;
        }
		if($pedidoObj->rj_x_sp == true){
			$origem = 'SP';
		}
		
        $estadoObj = CepEstado::find($pedidoObj->cliente->uf);

        $PedidoPortalControllerObj = new PedidoPortalController();
        $preco_frete = $PedidoPortalControllerObj->fretePreco($pedidoObj);
        $frete = $preco_frete['preco'];

        $internacional = 'false';

        $aliquotaObj = AliquotaPreco::where('origem', $origem)
            ->where('estado', $pedidoObj->cliente->uf)
            ->where('internacional', $internacional)
            ->first();

        $arr['origem'] = $origem;
        $arr['produto'] = $fields['codprd'];
        $arr['estado'] = $pedidoObj->cliente->uf;
        $arr['aliquota'] = $aliquotaObj->icms_venda;
            
        $coluna_view = $pedidoObj->usuario_detalhes->comissao_a . '%';
        $coluna = 'coluna_a';

        if ($pedidoObj->pedido_futuro === false || intval($pedidoObj->estabelecimento) != 3){
            $arr['moeda'] = 'real';
        }
        else if($pedidoObj->pedido_futuro === true && intval($pedidoObj->estabelecimento) == 3){
            $arr['moeda'] = 'dolar';
        }

        $promocao = false;
        if($pedidoObj->pedido_futuro === false){
            $promocao = true;
        }
        $arr['promocao'] = $promocao;
        $arr['cliente'] = $pedidoObj->cod_cliente;
        $arr['codigo_vendedor'] = $pedidoObj->usuario_detalhes->codigo_representante;

        $arr['frete'] = $frete;
        $arr['regiao'] = $estadoObj->regiao;
        $arr['tipo_cliente'] = (
            $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        
        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $arr['prazo_medio'] = 0;
            $preco_custo = true;
        } else {
            $arr['prazo_medio'] = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;
            if(in_array($produtoObj->grupo, $this->grupos_especiais) && ($pedidoObj->condicao_pagamento_detalhes->media ?? 0) > $this->media_especial){
                return response()->json(['erro' => 'estoque', 'msg' => "Condição média para este produto tem que ser menor ou igual a {$this->media_especial} dias"], 422);
            }    
        }

        if($pedidoObj->rj_x_sp == true){
			$arr['estabelecimento'] = 7;
		}else{
            $arr['estabelecimento'] = $pedidoObj->estabelecimento;
        }
        
        $listaDePrecosObj = new ListagemDePrecosController();
        $filter_request = new ListaDePrecosRequest($arr);

        $arr_result = $listaDePrecosObj->filter($filter_request, true, false, true);
        $result_estoque = $this->retornarDadosEstoqueNasajon($produtoObj, $pedidoObj->estabelecimento, $pedidoObj, $preco_custo);
        //dd($result_estoque);
        $estoque_disponivel = 0;
 
        if ($pedidoObj->pedido_futuro === false || is_null($pedidoObj->pedido_futuro)){

            if(
                !isset($result_estoque['pronta_entrega']) ||
                parserNumber($result_estoque['pronta_entrega']) <= 0
            ){
                $estoques_disponivel_em = [];
                $empresas = returnEmpresasNasajonView();
                if(isset($result_estoque['estoque_estabelecimentos'])){
                    foreach ($result_estoque['estoque_estabelecimentos'] as $empresa => $estoque) {
                        if($estoque <= 0){
                            continue;
                        }
                        $estoques_disponivel_em[] = $empresas[$empresa].' - '. parserQtd($estoque);
                    }
                }
                if(count($estoques_disponivel_em) > 0){
                    return response()->json(['erro' => 'estoque', 'msg' => 'Produto com estoque disponível conforme abaixo: <br />'.implode('<br />', $estoques_disponivel_em)], 422);
                }else{
                    return response()->json(['erro' => 'estoque', 'msg' => 'Produto sem estoque disponível.'], 422);
                }
            }else{
                $estoque_disponivel = $result_estoque['pronta_entrega'];
            }
        }
        
        $return['coluna'] = $coluna_view;
        $return['aliquota'] = $arr['aliquota']."%";

        if(
            is_null($precoObj) ||
            (floatval($precoObj->preco_real) == 0  && ($pedidoObj->pedido_futuro === false || $pedidoObj->estabelecimento != 3) ) ||
            (floatval($precoObj->preco_dolar) == 0  && $pedidoObj->pedido_futuro === true && intval($pedidoObj->estabelecimento) == 3) ||
            count($arr_result) == 0
        ){
            return response()->json(['erro' => 'preço', 'msg' => 'Produto com estoque, mas sem preço cadastrado. Por favor, verifique com o setor responsável.'], 422);
        }


        if ($pedidoObj->pedido_futuro === false || is_null($pedidoObj->pedido_futuro)){
            if(!empty($result_estoque['pronta_entrega'])){
                $result = $arr_result[0];
                $return['descricao'] = $result['nome'];
                $return['descricao_pecas'] = empty($produtoObj->produtoGrupo->pecas)? $produtoObj->descricao : $produtoObj->descricao." - Pecas: ".$produtoObj->produtoGrupo->pecas;
                $return['preco_unitario'] = $result[$coluna];
                $return['promocional'] = $result['promocional'];
                $return['estoque_disponivel'] = $estoque_disponivel;
            }
        }
        else if ($pedidoObj->pedido_futuro === true){
            if(isset($pedidoObj->data_previsao_entrega)){
                $data_previsao_entrega = strtotime(str_replace("/", "-", $pedidoObj->data_previsao_entrega));
                $coluna_quinzena = date("\k\_Y_m_", $data_previsao_entrega) . ((date("d", $data_previsao_entrega) <= 15)? "1": "2"); 
                if((isset($result_estoque['quinzenas'][$coluna_quinzena]['quantidade']) && $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'] > 0) || $ignorar_estoque) {
                    $result = (array) $arr_result[0];
                    $return['descricao'] = $result['nome'];
                    $return['descricao_pecas'] = empty($produtoObj->produtoGrupo->pecas)? $produtoObj->descricao : $produtoObj->descricao." - Pecas: ".$produtoObj->produtoGrupo->pecas;
                    $return['preco_unitario'] = $result[$coluna];
                    $return['promocional'] = $result['promocional'];   
                    $return['estoque_disponivel'] = empty($result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])? '' : $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'];
                }
                else{
                    return response()->json(['erro' => 'estoque', 'msg' => 'Produto sem estoque futuro para a data programada.'], 422);
                }
            }
        }
        else{
            return response()->json(['msg' => 'Erro na requisição.'], 422);
        }
        if(isset($return['promocional']) && $return['promocional'] !== false){
            $comissao = $return['promocional']['comissao'];
            $comissao = str_replace('.', ',', $comissao);
            $return['coluna'] = $comissao.'%' ?? '2%';
        }
        if($preco_custo === true){
            $return['coluna'] = '0%';
            $return['aliquota'] = "0%";
            //$custo = $produtoObj->custos->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
            $custo = $produtoObj->estoque->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
            $custo_portal = $produtoObj->custos->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
            if(empty($custo) && empty($custo_portal)){
                $custo = $produtoObj->preco->preco_real / 1.43;
            }else if(!empty($custo) && !empty($custo_portal)){
                if($custo->custo > $custo_portal->custo_medio_contabil){
                    $custo = $custo->custo;
                }else if(empty($custo->custo) && empty($custo_portal->custo_medio_contabil)){
                    $custo = $produtoObj->preco->preco_real / 1.43;
                }else{
                    $custo = $custo_portal->custo_medio_contabil;
                }
            }else if(!empty($custo) && empty($custo_portal)){
                $custo = empty($custo->custo)? $produtoObj->preco->preco_real / 1.43 : $custo->custo;
            }else if(empty($custo) && !empty($custo_portal)){
                $custo = empty($custo_portal->custo_medio_contabil)? $produtoObj->preco->preco_real / 1.43 : $custo_portal->custo_medio_contabil;
            }else{
                $custo = $custo->custo;
            }

            if($produtoObj->industrializado){
                if(($produtoObj->preco->preco_real / 1.43) > $custo){
                    $custo = $produtoObj->preco->preco_real / 1.43;
                }
            }
            
            /*else  if(empty($custo->custo_medio_contabil)){
                $custo = $produtoObj->preco->preco_real / 1.43;
            }else{
                $custo = $custo->custo_medio_contabil;
            }*/
            
            /**
             * Regra preço transferencia
             */
            /*switch($pedidoObj->estabelecimento){
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
                    switch($pedidoObj->cliente->uf){
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

            $return['preco_unitario'] = parserValor($custo);
        }

        $return['preco_base'] = $return['preco_unitario'];
        if(isset($fields['preco_base_antes']) && !empty($fields['preco_base_antes'])){
            if($fields['preco_base_antes'] === $return['preco_base'] && !empty($fields['preco_antes'])){
                $return['preco_unitario'] = strip_tags($fields['preco_antes']);
                $return['coluna'] = $fields['comissao_antes'];
            }
        }
        $return['ipi'] = false;
        $return['ipi_valor'] = 0;
        $return['estabelecimento'] = intval($pedidoObj->estabelecimento);
        if(intval($pedidoObj->estabelecimento) != 3){
            $produto_nasajon = ProdutoNasajon::where('codigo', $fields['codprd'])->first();
            if(!empty($produto_nasajon->ipi)){
                $return['ipi'] = true;
            }
        }else{
            $produto_nasajon = ProdutoNasajon::where('codigo', $fields['codprd'])->first();
            if(!empty($produto_nasajon->ipi)){
                $return['ipi'] = true;
                $return['ipi_valor'] = parserValor($produto_nasajon->ipi);
            }
        }

        if(in_array($pedidoObj->usuario_detalhes->id, [108, 610, 9087])){
            $return['coluna'] = '0.30%';
        }

        $produtoCampanha = CampanhasProduto::with('campanha')
        ->where('produto_codigo', $fields['codprd'])
        ->whereHas('campanha', function($query) {
            $query->where('ativo', true);
        })
        ->first();

        $return['nome_campanha'] = '';
        $return['coluna_informativo'] = '';

        if(!empty($produtoCampanha->produto_codigo)){
            $campanha = $produtoCampanha->campanha;
            $return['nome_campanha'] = $campanha->nome;

            if($pedidoObj->usuario_detalhes->tipo_usuario_id == 12){
                if($campanha->tipo_comissao_representante == 1){
                    $return['coluna_informativo'] = 'Comissão '.$return['coluna'].' + '.parserValor($campanha->comissao_representante).'% incentivo';
                    $return['coluna'] = parserValor(floatval($return['coluna']) + $campanha->comissao_representante).'% ';
                }
                if($campanha->tipo_comissao_representante == 2){
                    $return['coluna_informativo'] = 'Comissão '.parserValor($campanha->comissao_representante).'% incentivo';
                    $return['coluna'] = parserValor($campanha->comissao_representante).'% ';
                }
            }
            if(in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [16, 13])){
                if($campanha->tipo_comissao_vendedor_interno == 1){
                    $return['coluna_informativo'] = 'Comissão '.$return['coluna'].' + '.parserValor($campanha->comissao_vendedor_interno).'% incentivo';
                    $return['coluna'] = parserValor(floatval($return['coluna']) + $campanha->comissao_vendedor_interno).'% ';
                }
                if($campanha->tipo_comissao_vendedor_interno == 2){
                    $return['coluna_informativo'] = 'Comissão '.parserValor($campanha->comissao_vendedor_interno).'% incentivo';
                    $return['coluna'] = parserValor($campanha->comissao_vendedor_interno).'% ';
                }
            }
            if(in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [14, 19])){
                if($campanha->tipo_comissao_gerente == 1){
                    $return['coluna_informativo'] = 'Comissão '.$return['coluna'].' + '.parserValor($campanha->comissao_gerente).'% incentivo';
                    $return['coluna'] = parserValor(floatval($return['coluna']) + $campanha->comissao_gerente).'% ';
                }
                if($campanha->tipo_comissao_gerente == 2){
                    $return['coluna_informativo'] = 'Comissão '.parserValor($campanha->comissao_gerente).'% incentivo';
                    $return['coluna']  = parserValor($campanha->comissao_gerente).'% ';
                }
            }
        }

        if($retorno_array){
            return $return;
        }else{
            return response()->json($return);
        }        
    }

    public function retornarDadosEstoqueNasajon($produto, $estabelecimento, PedidoPortal $pedidoPortal, $preco_custo = false, $codigo_tecidos_base = '', $desconsiderar_pedido_maior_que_data_pedido = false){
        if($pedidoPortal->tipo_venda === "producao_normal" || $pedidoPortal->tipo_venda === "producao_isento"){
            $produto = $this->getTecidoBasePeloEstampado($produto->codigo_produto, $codigo_tecidos_base);
        }

        $estabelecimento = str_pad($estabelecimento, 2, "0", STR_PAD_LEFT);

        $agoraCarbon = Carbon::now();

        if(intval($estabelecimento) == 3 || intval($estabelecimento) == 4){
            $saldo_produto = DB::connection('nasajon')->select("SELECT saldo_em_terceiros, (select sum(quantidade) from integracoes.exportar_produtos_movimentacoes('{$estabelecimento}', '2023-02-06', '{$agoraCarbon->format('Y-m-d')}', '{$produto->codigo_produto}') as mov where not mov.efetivado = true) as saldo_movimento_nao_efetivado, sal.qtd_em_pedidos as qtd_em_pedidos, saldo_fiscal as saldo_fiscal FROM integracoes.exportar_produtos_saldos('{$estabelecimento}', '{$produto->codigo_produto}') as sal group by saldo_em_terceiros, sal.qtd_em_pedidos, saldo_fiscal;")[0];
        }else{
            $saldo_produto = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_produtos_saldos('{$estabelecimento}', '{$produto->codigo_produto}');")[0];
        }

        $saldo = 0;
        $transito = 0;
        if(!empty($saldo_produto)){
            $qtd_em_pedidos_nasajon = empty($saldo_produto->qtd_em_pedidos)? 0 : $saldo_produto->qtd_em_pedidos;
            if(intval($estabelecimento) == 3 || intval($estabelecimento) == 4){
                $saldo = empty($saldo_produto->saldo_movimento_nao_efetivado)? $saldo_produto->saldo_em_terceiros - $qtd_em_pedidos_nasajon : $saldo_produto->saldo_em_terceiros - $saldo_produto->saldo_movimento_nao_efetivado - $qtd_em_pedidos_nasajon;
                $transito = $saldo_produto->saldo_fiscal + $saldo_produto->saldo_movimento_nao_efetivado;
            }else{
                $transito = 0;
                $saldo = $saldo_produto->saldo_fiscal - $qtd_em_pedidos_nasajon;
            }
        }

        $produtos = [];

        $produtos["compras"] = [];
        $produtos["compras_futuras"] = [];

        $produtos["total_pronta_entrega"] = (float) $saldo;
        $produtos["saldo"] = (float) $saldo;
        
        $date = date("d");
        $month = intval(date("m"));
        $year = intval(date("Y"));
        if(intval($date) <= 15){
            $produtos["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = [ "data" => "", "pcmn" => "", "quantidade" => " ", "pedidos" => " " ];
        }
        $produtos["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = [ "data" => "", "pcmn" => "", "quantidade" => " ", "pedidos" => " " ];
        $month++;
        for ($i=0; $i < 3; $i++) {
            if($month > 12){
                $month = 1;
                $year++;
            }
            $produtos["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = [ "data" => "", "pcmn" => "", "quantidade" => " ", "pedidos" => " " ];
            $produtos["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = [ "data" => "", "pcmn" => "", "quantidade" => " ", "pedidos" => " " ];
            $month++;
        }

        $total_compras = 0;
        if($transito > 0){
            $total_compras += $transito;
            if(intval($date) <= 15){
                $quinzena = 1;
            }else{
                $quinzena = 2;
            }
            $month = intval(date("m"));
            $year = intval(date("Y"));
            $produtos["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_".$quinzena]["quantidade"] = $transito;
            $produtos["compras"]["k_".$year."_".str_pad($month, 2, "0", STR_PAD_LEFT)."_".$quinzena]["pedidos"] = 'transito';
        }

        $tableUnidadeConversao = new UnidadeConversaoProdutoNasajon;
        $tableCompras = new ComprasNasajon;
        try{
            $compras_pedidos_nasajon = ComprasNasajon::
                selectRaw("estabelecimento, cod_produto, TO_CHAR(previsao_entrega, 'YYYY_MM') AS ano_mes, CASE WHEN CAST(TO_CHAR(previsao_entrega, 'DD') AS INT) <= 15 THEN 1 ELSE 2 END AS quinzena, SUM(quantidade_restante * CASE WHEN ".$tableUnidadeConversao->getTable().".razao IS NOT NULL THEN ".$tableUnidadeConversao->getTable().".razao ELSE 1 END) AS quantidade, max(numero_pedido) as pedido")->
                leftJoin($tableUnidadeConversao->getTable(), function($query) use ($tableUnidadeConversao, $tableCompras){
                    $query->on($tableUnidadeConversao->getTable().".codigo_unidadeconversao", $tableCompras->getTable().".unidade_comercial");
                    $query->on($tableUnidadeConversao->getTable().".codigo_produto", $tableCompras->getTable().".cod_produto");
                })->
                where('cod_produto', $produto->codigo_produto)->
                where('estabelecimento', $estabelecimento)->
                where('quantidade_restante', '>', 0)->
                whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->
                groupBy(DB::Raw("estabelecimento, cod_produto, TO_CHAR(previsao_entrega, 'YYYY_MM'), CASE WHEN CAST(TO_CHAR(previsao_entrega, 'DD') AS INT) <= 15 THEN 1 ELSE 2 END"))->
                get();
        } catch(\Exception $e){
            $return = [
                "data" => [],
                "status" => "error",
                "message" => "Ocorreu uma instabilidade!<br />Tente novamente!"
            ];
            return $return;
        }
		// dd($compras_pedidos_nasajon);
        foreach($compras_pedidos_nasajon as $compra){
            $data = explode("_", $compra->ano_mes);
            $ano = $data[0];
            $mes = $data[1];
            $quinzena = $compra->quinzena;
            $ano_mes_quinzena = $compra->ano_mes . '_' . $quinzena;
            if(strtotime($ano . '-' . $mes . '-' .(($quinzena === 2) ? 16 : 1) ) < strtotime(date('Y-m-d'))){
                $data = explode("-", date('Y-m-d'));
                $ano = $data[0];
                $mes = $data[1];
                $quinzena = ((intval($data[2]) <= 15) ? '1' : '2' );
                $ano_mes_quinzena = "{$ano}_{$mes}_{$quinzena}";
            }
            $total_compras += floatval($compra->quantidade);
            if(isset($produtos["compras"]["k_".$ano_mes_quinzena])){
                if(empty(trim($produtos["compras"]["k_".$ano_mes_quinzena]['quantidade']))){
                    $produtos["compras"]["k_".$ano_mes_quinzena] = [
                        "mes" => $ano,
                        "ano" => $mes,
                        "quinzena" => $quinzena,
                        "pedidos" => $compra->pedido,
                        "pedidos_separado" =>[$compra->pedido => ["pedido" => $compra->pedido, "quantidade" => floatval($compra->quantidade)]],
                        "quantidade" => floatval($compra->quantidade),
                        'pedidos_compras' => [$compra->pedido]
                    ];
                }else{
                    $produtos["compras"]["k_".$ano_mes_quinzena]['quantidade'] += floatval($compra->quantidade);
                    $produtos["compras"]["k_".$ano_mes_quinzena]['pedidos_separado'][$compra->pedido] = ["pedido" => $compra->pedido, "quantidade" => floatval($compra->quantidade)];
                    $produtos["compras"]["k_".$ano_mes_quinzena]['pedidos_compras'][] = $compra->pedido;
                }
            }else{
                $produtos["compras"]["k_".$ano_mes_quinzena] = [
                    "mes" => $ano,
                    "ano" => $mes,
                    "quinzena" => $quinzena,
                    "pedidos" => $compra->pedido,
                    "pedidos_separado" =>[["pedido" => $compra->pedido, "quantidade" => floatval($compra->quantidade)]],
                    "quantidade" => floatval($compra->quantidade),
                    'pedidos_compras' => [$compra->pedido]
                ];
            }
        }
        $produtos['saldo'] += $total_compras;
        unset($PedidoPortalObj);
        $PedidoPortalObj = PedidoPortal::query()
            ->with(['itens_pedido' => function($query) use ($produto){
                $query->where("cod_produto", $produto->codigo_produto)
                ->whereNull('deleted_at');
            }])
            ->with(['itens_pedido.comprasNasajon']);
            if(in_array($pedidoPortal->tipo_venda, ['pedido_futuro_venda', 'pedido_futuro_triangular']) && $pedidoPortal->pedido_futuro == false){
                $PedidoPortalObj->whereNotIn('status_pedido', [3, 5, 7, 8]);
            }else if($desconsiderar_pedido_maior_que_data_pedido){
                $PedidoPortalObj->whereNotIn('status_pedido', [3, 5, 7, 8]);
            }else{
                $PedidoPortalObj->whereNotIn('status_pedido', [3, 5, 7]);
            }
            $PedidoPortalObj->where('estabelecimento', $estabelecimento)
            ->whereHas('itens_pedido', function($query) use ($produto){
                $query->where("cod_produto", $produto->codigo_produto)
                ->whereNull('deleted_at');
            })
            ->where('id', '!=', $pedidoPortal->id);
        /* if($desconsiderar_pedido_maior_que_data_pedido){
            $PedidoPortalObj->where(DB::Raw("case
                when status_pedido = 8 then
                    updated_at < '".$pedidoPortal->updated_at."'
                end"));
        } */
        $PedidoPortalObj = $PedidoPortalObj->get();

        $pedidos_futuros_portal = [];
        $empenho_portal = 0;
        $id = [];

        foreach($PedidoPortalObj as $pedido){
            if($pedido->pedido_futuro == false){
                $pedido->itens_pedido->each(function($item_pedido) use($produto, &$produtos, &$empenho_portal){
                    if($item_pedido->cod_produto === $produto->codigo_produto){
                        $produtos['total_pronta_entrega'] -= $item_pedido->quantidade;
                    }
                });
            }else{
                if($total_compras > 0){
                    $id[] = $pedido->id;
                    $dataPrevisaoCarbon = Carbon::parse($pedido->data_previsao_entrega)->setTime(0,0,0);
                    $dataHoje = Carbon::now()->setTime(0,0,0);
                    if($dataHoje->gte($dataPrevisaoCarbon)){
                        $pedido->data_previsao_entrega = date('Y-m-d');
                        $dataPrevisaoCarbon = Carbon::now()->setTime(0,0,0);
                    }
        
                    $data_pedido = date('d/m/Y', strtotime($pedido->data_previsao_entrega));
                    $data_pedido = explode("/", $data_pedido);
                    $coluna_quinzena = (int) ($data_pedido[2] . $data_pedido[1] . ($data_pedido[0] <= 15? "1": "2"));
                    $ano_mes_quinzena = 'k_'. $data_pedido[2] . '_' . $data_pedido[1] . '_' . ($data_pedido[0] <= 15? "1": "2");
					
                    foreach($pedido->itens_pedido as $item_pedido){
                        if($item_pedido->cod_produto === $produto->codigo_produto){
                            if(!empty($item_pedido->comprasNasajon)){
                                if(in_array($item_pedido->comprasNasajon->situacao, ['Cancelado', 'Aberto']) || in_array($item_pedido->comprasNasajon->situacao_item, ['Cancelado', 'Liquidado'])){
                                    $produtos['total_pronta_entrega'] -= $item_pedido->quantidade;
                                }else{
                                    if(!isset($pedidos_futuros_portal[$coluna_quinzena])){
                                        $pedidos_futuros_portal[$coluna_quinzena] = 0;
                                    }
                                    $pedidos_futuros_portal[$coluna_quinzena] += $item_pedido->quantidade;
                                    $produtos['saldo'] -= $item_pedido->quantidade;
                                    if(!empty($produtos['compras'][$ano_mes_quinzena]['pedidos_separado'][$item_pedido->numero_compra])){
                                        $produtos['compras'][$ano_mes_quinzena]['pedidos_separado'][$item_pedido->numero_compra]['quantidade'] -= $item_pedido->quantidade;
                                    }                                
                                }
                            }else{
                                if(!isset($pedidos_futuros_portal[$coluna_quinzena])){
                                    $pedidos_futuros_portal[$coluna_quinzena] = 0;
                                }
                                $pedidos_futuros_portal[$coluna_quinzena] += $item_pedido->quantidade;
                                $produtos['saldo'] -= $item_pedido->quantidade;
                                if(!empty($produtos['compras'][$ano_mes_quinzena]['pedidos_separado'][$item_pedido->numero_compra])){
                                    $produtos['compras'][$ano_mes_quinzena]['pedidos_separado'][$item_pedido->numero_compra]['quantidade'] -= $item_pedido->quantidade;
                                }       
                            }
                            
                        }
                    }
                }else{
                    foreach($pedido->itens_pedido as $item_pedido){
                        $dataPrevisaoCarbon = Carbon::parse($pedido->data_previsao_entrega)->setTime(0,0,0);
                        $dataHoje = Carbon::now()->setTime(0,0,0);
                        if($dataHoje->gte($dataPrevisaoCarbon)){
                            if($item_pedido->cod_produto === $produto->codigo_produto){
                                $produtos['total_pronta_entrega'] -= $item_pedido->quantidade;
                                $produtos['saldo'] -= $item_pedido->quantidade;
                            }
                            $pedido->data_previsao_entrega = date('Y-m-d');
                            $dataPrevisaoCarbon = Carbon::now()->setTime(0,0,0);
                        }else{
                            $data_pedido = date('d/m/Y', strtotime($pedido->data_previsao_entrega));
                            $data_pedido = explode("/", $data_pedido);
                            $coluna_quinzena = (int) ($data_pedido[2] . $data_pedido[1] . ($data_pedido[0] <= 15? "1": "2"));
                            $ano_mes_quinzena = 'k_'. $data_pedido[2] . '_' . $data_pedido[1] . '_' . ($data_pedido[0] <= 15? "1": "2");
    
    
                            if($item_pedido->cod_produto === $produto->codigo_produto){
                                if(!empty($produtos['compras'][$ano_mes_quinzena]['pedidos_separado'][$item_pedido->numero_compra])){
                                    $produtos['compras'][$ano_mes_quinzena]['pedidos_separado'][$item_pedido->numero_compra]['quantidade'] -= $item_pedido->quantidade;
                                }  
                            }
                        }
            
                        
                    }
                }
            }
            unset($PedidoPortalObj);
        }
        //dd($produtos);
        foreach ($produtos["compras"] as $key => $value) {
            if((floatval($value["quantidade"]) > 0.0) && isset($pedidos_futuros_portal) && !empty($pedidos_futuros_portal)){
                $chave_compra = $key;
                $chave_compra = (int) str_replace('_', '', str_replace('k', '', $chave_compra));
                if(isset($pedidos_futuros_portal[$chave_compra])){
                    if(floatval($pedidos_futuros_portal[$chave_compra]) > floatval($value["quantidade"])){
                        if((floatval($pedidos_futuros_portal[$chave_compra]) - floatval($value["quantidade"])) > 0){
                            $produtos['total_pronta_entrega'] -= floatval($pedidos_futuros_portal[$chave_compra]) - floatval($value["quantidade"]);
                        }
                    }
                    $value["quantidade"] = floatval($value["quantidade"]) - floatval($pedidos_futuros_portal[$chave_compra]);
                }
            }
            $produtos["compras"][$key]["quantidade"] = (floatval($value["quantidade"]) > 0.0) ? parserQtd($value["quantidade"]) : "";
        }
        $estoque_negativo = 0; 
        if($produtos['total_pronta_entrega'] < 0){
            $estoque_negativo = (-1) * $produtos['total_pronta_entrega'];
        }

        foreach($produtos['compras'] as $index_compras => $compra){
            if(!empty(trim($compra['quantidade']))){
                if($estoque_negativo > 0){
                    $aux_estoque = parserNumber($produtos['compras'][$index_compras]['quantidade']);
                    $produtos['compras'][$index_compras]['quantidade'] = parserNumber($produtos['compras'][$index_compras]['quantidade']) - $estoque_negativo;
                    $estoque_negativo -= $aux_estoque;

                    if($produtos['compras'][$index_compras]['quantidade'] < 0){
                        $produtos['compras'][$index_compras]['quantidade'] = 0;
                    }else{
                        $produtos['compras'][$index_compras]['quantidade'] = parserValor($produtos['compras'][$index_compras]['quantidade']);
                    }
                }
            }
        }
        
		$produtos['total_pronta_entrega'] -= $empenho_portal;
        if($produtos['saldo'] < 0){
            $produtos['total_pronta_entrega'] = 0;
        }
        $results_return = [
            "codigo" => $produto->codigo_produto,
            "descricao" => $produto->descricao,
            "linha" => $produto->linha,
            "grupo" => $produto->grupo,
            "marca" => $produto->marca,
            "unidade" => $produto->unidade,
            "pronta_entrega" => (floatval($produtos["total_pronta_entrega"] > 0.0) ? parserQtd($produtos["total_pronta_entrega"]) : ""),
            "quinzenas" => $produtos["compras"],
            "futuro" => (!empty($produtos["compras_futuras"]) ? parserQtd($produtos["compras_futuras"]) : " "),
            "saldo" => $produtos["saldo"]
        ];
        if(
            $preco_custo === true
        ){
            $produtoEstoque = ProdutosEstoque::where('codigo_produto', $produto)->where('estabelecimento', $estabelecimento)->first();
            if(!empty($produtoEstoque)){
                $results_return['custo'] = parserValor($produtoEstoque->custo);
            }
        }
        unset($produto);
        unset($produtos);
        unset($pedidos);

        return $results_return;
    }

    public function modalPesquisaPedido(Request $request){
        $fields = $request->only('pedido');
        $PedidoPortalObj = PedidoPortal::findOrFail($fields['pedido']);

        $campanhaObj = Campanha::select()
            ->where('inicio_campanha', '<=', date('Y-m-d'))
            ->where('fim_campanha', '>=', date('Y-m-d'))
            ->where('ativo', true)
            ->get();

        $campanhas = [];
        foreach($campanhaObj as $value){
            $campanhas[$value->id] = $value->nome;
        }

        $segmentos =$this->segmentos();
        
        return view('programs.produto.modal_pedido')->with(['pesquisa' => $PedidoPortalObj->toArray(), 'campanhas' => $campanhas, 'segmentos' => $segmentos]);
    }

    public function filterModalPedido(Request $request){
        $fields = $request->only('grupo', 'codigo', 'nome', 'marca', 'linha', 'pedido', 'campanha', 'segmentos');

        if (empty($fields['grupo']) && empty($fields['codigo']) && empty($fields['nome']) && empty($fields['marca']) && empty($fields['linha']) && empty($fields['campanha'])&& empty($fields['segmentos'])){

            $mensagem[] = "Informe pelo menos um campo para a busca!";

            $return = [
                "message" => '',
                'errors' => [
                    'grupo' => $mensagem,
                    'codigo' => $mensagem,
                    'nome' => $mensagem,
                    'marca' => $mensagem,
                    'linha' => $mensagem,
                    'campanha' => $mensagem,
                    'segmentos' => $mensagem,
                ]
            ];

            return response()->json($return, 422);
        }

        $PedidoPortalObj = PedidoPortal::with(['itens_pedido'])->findOrFail($fields['pedido']);

        $request_filtro = new Request([
            'estabel' => str_pad($PedidoPortalObj->estabelecimento, 2, "0", STR_PAD_LEFT),
            'grupo' => $fields['grupo'],
            'codigo' => $fields['codigo'],
            'nome' => $fields['nome'],
            'marca' => $fields['marca'],
            'linha' => $fields['linha'],
            'campanha' => $fields['campanha'],
        ]);
        $result = $this->filterAnaliseTela($request_filtro, false, false, true, true);
        //dd($request_filtro, $result);
        if($result["status"] == 'error_campanha'){
            $empresas = returnEmpresasNasajonView();
            $mensagem[] =  "Não foi cadastrado nenhum produto na campanha ".Campanha::find($fields['campanha'])->nome." no estabelecimento ".$empresas[$PedidoPortalObj->estabelecimento];

            $return = [
                "message" => '',
                'errors' => [
                    'campanha' => $mensagem,
                ]
            ];

            return response()->json($return, 422);
        }
        $return = [];

        $itens_pedido = $PedidoPortalObj->itens_pedido->pluck('cod_produto')->toArray();

        foreach($result['data'] as $key => $produto_estoque){
            if($PedidoPortalObj->pedido_futuro === true){
                $data_explodida = explode("/", date("d/m/Y", strtotime($PedidoPortalObj->data_previsao_entrega)));
                $coluna_quinzena = "k_" . $data_explodida[2] . "_" . $data_explodida[1] . "_" . ($data_explodida[0] <= 15? "1": "2");
                if (!empty($produto_estoque['quinzenas'][$coluna_quinzena]['quantidade'])) {
                    $return[$key]['grupo'] = $produto_estoque['grupo'];
                    $return[$key]['codigo'] = $produto_estoque['codigo'];
                    $return[$key]['nome'] = $produto_estoque['descricao'];
                    $return[$key]['marca'] = $produto_estoque['marca'];
                    $return[$key]['linha'] = $produto_estoque['linha'];
                    $return[$key]['unidade'] = $produto_estoque['unidade'];
                    $return[$key]['estoque'] = $produto_estoque['quinzenas'][$coluna_quinzena]['quantidade'];
                    $return[$key]['ja_no_pedido'] = 'false';
                    $return[$key]['pecas'] = $produto_estoque['pecas'];
                    $return[$key]['campanha'] = $produto_estoque['campanha'];
                }
                else{
                    continue;
                }
            }
            else{
                if (!empty($produto_estoque['pronta_entrega'])) {
                    $return[$key]['grupo'] = $produto_estoque['grupo'];
                    $return[$key]['codigo'] = $produto_estoque['codigo'];
                    $return[$key]['nome'] = $produto_estoque['descricao'];
                    $return[$key]['marca'] = $produto_estoque['marca'];
                    $return[$key]['linha'] = $produto_estoque['linha'];
                    $return[$key]['unidade'] = $produto_estoque['unidade'];
                    $return[$key]['estoque'] = $produto_estoque['pronta_entrega'];
                    $return[$key]['ja_no_pedido'] = 'false';
                    $return[$key]['pecas'] = $produto_estoque['pecas'];
                    $return[$key]['campanha'] = $produto_estoque['campanha'];
                }
                else{
                    continue;
                }
            }

            if(in_array($produto_estoque['codigo'], $itens_pedido)){
                $return[$key]['ja_no_pedido'] = 'true';  
            }
        }
        $return = array_values($return);
        return response()->json($return);
    }


    public function autoCompletePedido(Request $request){
        $fields = $request->only(["pedido", "term"]);
        $pedido = PedidoPortal::find($fields['pedido']);
        if(is_null($pedido)){
            return response()->json([]);
        }
		$PedidoItemPortalTable = new PedidoItemPortal();
		$PedidoItemPortalTable = $PedidoItemPortalTable->getTable();
		
		$PedidoPortalTable = new PedidoPortal();
		$PedidoPortalTable = $PedidoPortalTable->getTable();
		
		$ProdutoEspecificacaoTable = new ProdutoEspecificacao();
		$ProdutoEspecificacaoTable = $ProdutoEspecificacaoTable->getTable();
		
		$ProdutosEstoqueTable = new ProdutosEstoque();
		$ProdutosEstoqueTable = $ProdutosEstoqueTable->getTable();

        $ProdutoGrupoTable = new ProdutoGrupo();
		$ProdutoGrupoTable = $ProdutoGrupoTable->getTable();

        $PedidoPortalObj = PedidoPortal::query()
        ->with(['itens_pedido.especificacoes' => function($query) use ($fields){
            $query->where("descricao", 'ilike', "%".$fields["term"]."%")
            ->whereNull('deleted_at');
        }])
        ->with(['itens_pedido.comprasNasajon'])
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->where('estabelecimento', $pedido->estabelecimento)
        ->whereHas('itens_pedido.especificacoes', function($query) use ($fields){
            $query->where("descricao", 'ilike', "%".$fields["term"]."%")
            ->whereNull('deleted_at');
        })
        ->where('id', '!=', $pedido->id);

        $PedidoPortal = $PedidoPortalObj->get();
        $itens_reservado = [];

        $PedidoPortal->each(function($pedido_verificacao) use (&$itens_reservado){
            $pedido_verificacao->itens_pedido->each(function($item_pedido) use ($pedido_verificacao, &$itens_reservado){
                if($pedido_verificacao->pedido_futuro == true){
                    if(!empty($item_pedido->comprasNasajon)){
                        if(in_array($item_pedido->comprasNasajon->situacao, ['Cancelado', 'Aberto']) || in_array($item_pedido->comprasNasajon->situacao_item, ['Cancelado', 'Liquidado'])){
                            if(empty($itens_reservado[$item_pedido->cod_produto])){
                                $itens_reservado[$item_pedido->cod_produto] = 0;
                            }
            
                            $itens_reservado[$item_pedido->cod_produto] += $item_pedido->quantidade;
                        }
                    }
                }
            });
        });

        if($pedido->pedido_futuro === true){
            $dataPrevisaoCarbon = Carbon::createFromFormat("Y-m-d", $pedido->data_previsao_entrega);
            
            $dataPrevisaoCarbonIni = Carbon::createFromFormat("Y-m-d", $pedido->data_previsao_entrega)->day(1);
            $dataPrevisaoCarbonfim = Carbon::createFromFormat("Y-m-d", $pedido->data_previsao_entrega)->endOfMonth(1);
            if($dataPrevisaoCarbon->format("d") > 15){
                $dataPrevisaoCarbonIni->day(15);
            }else{
                $dataPrevisaoCarbonfim->day(15);

            }
            
            $periodoPrevisao = [$dataPrevisaoCarbonIni->format("Y-m-d"), $dataPrevisaoCarbonfim->format("Y-m-d")];

            $busca_pedidos = PedidoPortal::join($PedidoItemPortalTable, "{$PedidoItemPortalTable}.pedido", "{$PedidoPortalTable}.id")
                ->selectRaw("{$PedidoPortalTable}.estabelecimento, {$PedidoItemPortalTable}.cod_produto AS codigo_produto, SUM({$PedidoItemPortalTable}.quantidade) AS empenho_pedido")
                ->whereNotIn("{$PedidoPortalTable}.status_pedido", [3, 5, 7])
                ->where("{$PedidoPortalTable}.pedido_futuro", true)
                ->whereNull("{$PedidoItemPortalTable}.deleted_at")
                ->whereBetween("{$PedidoPortalTable}.data_previsao_entrega", $periodoPrevisao)
                ->groupBy("{$PedidoPortalTable}.estabelecimento", "{$PedidoItemPortalTable}.cod_produto");

            $busca_estoque = ProdutoEspecificacao::
                select("{$ProdutoEspecificacaoTable}.codigo_produto", "{$ProdutoEspecificacaoTable}.descricao", "{$ProdutoGrupoTable}.pecas")
                ->join("{$ProdutosEstoqueTable}", function($join) use ($ProdutoEspecificacaoTable, $ProdutosEstoqueTable) {
                    $join->on("{$ProdutosEstoqueTable}.codigo_produto", "=", "{$ProdutoEspecificacaoTable}.codigo_produto");
                })
                ->join("{$ProdutoGrupoTable}", function($join) use ($ProdutoEspecificacaoTable, $ProdutoGrupoTable) {
                    $join->on("{$ProdutoGrupoTable}.id", "=", "{$ProdutoEspecificacaoTable}.produto_grupos_id");
                })
                ->leftJoinSub($busca_pedidos, "empenhopedido", function($join) use ($ProdutosEstoqueTable){
                    $join->on(DB::raw("CAST(empenhopedido.estabelecimento AS INTEGER)"), "=", DB::raw("CAST({$ProdutosEstoqueTable}.estabelecimento AS INTEGER)"))
                    ->on("empenhopedido.codigo_produto", "=", "{$ProdutosEstoqueTable}.codigo_produto");
                })
                ->where("{$ProdutoEspecificacaoTable}.descricao", "ILIKE", "%".$fields["term"]."%")
                ->whereNotNull("{$ProdutosEstoqueTable}.codigo_produto")
                ->where(DB::raw("CAST({$ProdutosEstoqueTable}.estabelecimento AS INTEGER)"), $pedido->estabelecimento)
                ->whereRaw("(({$ProdutosEstoqueTable}.compras + ({$ProdutosEstoqueTable}.saldo_movimento_nao_efetivado + {$ProdutosEstoqueTable}.saldo_fiscal)) - ((CASE WHEN empenhopedido.empenho_pedido IS NOT NULL THEN empenhopedido.empenho_pedido ELSE 0 END))) > 0")
                ->orderBy("{$ProdutoEspecificacaoTable}.descricao", "asc")
                ->limit(15)
                ->get();
                $return = [];
                $busca_estoque->each(function($item) use (&$return){
                    $pecas = preg_replace('/[^0-9]/', '', $item->pecas);
                    $return[] = [
                        'value' => $item->codigo_produto,
                        'label' => !empty($item->pecas)?$item->descricao. ' - Peça: '. $item->pecas : $item->descricao,
                        'pecas' => !empty($pecas)?intval($pecas) : '',
                    ];
                });
        }else{
            $busca_pedidos = PedidoPortal::join("{$PedidoItemPortalTable}", "{$PedidoItemPortalTable}.pedido", "{$PedidoPortalTable}.id")
                ->selectRaw("{$PedidoPortalTable}.estabelecimento, {$PedidoItemPortalTable}.cod_produto AS codigo_produto, SUM({$PedidoItemPortalTable}.quantidade) AS empenho_pedido")
                ->whereNotIn("{$PedidoPortalTable}.status_pedido", [3, 5, 7])
                ->where(DB::raw("CAST({$PedidoPortalTable}.estabelecimento AS INTEGER)"), $pedido->estabelecimento)
                ->where("{$PedidoPortalTable}.pedido_futuro", false)
                ->whereNull("{$PedidoItemPortalTable}.deleted_at")
                ->groupBy("{$PedidoPortalTable}.estabelecimento", "{$PedidoItemPortalTable}.cod_produto");

            $busca_estoque = ProdutoEspecificacao::
                select("{$ProdutoEspecificacaoTable}.codigo_produto", "{$ProdutoEspecificacaoTable}.descricao", DB::raw("(({$ProdutosEstoqueTable}.estoque) - ({$ProdutosEstoqueTable}.reserva_pronta_entrega )) as estoque"), DB::raw("{$ProdutosEstoqueTable}.empenho + (CASE WHEN empenhopedido.empenho_pedido IS NOT NULL THEN empenhopedido.empenho_pedido ELSE 0 END) as ped"), "{$ProdutoGrupoTable}.pecas")
                ->join("{$ProdutosEstoqueTable}", function($join) use ($ProdutoEspecificacaoTable, $ProdutosEstoqueTable) {
                    $join->on("{$ProdutosEstoqueTable}.codigo_produto", "=", "{$ProdutoEspecificacaoTable}.codigo_produto");
                })
                ->join("{$ProdutoGrupoTable}", function($join) use ($ProdutoEspecificacaoTable, $ProdutoGrupoTable) {
                    $join->on("{$ProdutoGrupoTable}.id", "=", "{$ProdutoEspecificacaoTable}.produto_grupos_id");
                })
                ->leftJoinSub($busca_pedidos, "empenhopedido", function($join) use ($ProdutosEstoqueTable) {
                    $join->on(DB::raw("CAST(empenhopedido.estabelecimento AS INTEGER)"), "=", DB::raw("CAST({$ProdutosEstoqueTable}.estabelecimento AS INTEGER)"))
                    ->on("empenhopedido.codigo_produto", "=", "{$ProdutosEstoqueTable}.codigo_produto");
                })
                ->where("{$ProdutoEspecificacaoTable}.descricao", "ILIKE", "%".$fields["term"]."%")
                ->whereNotNull("{$ProdutosEstoqueTable}.codigo_produto")
                ->where(DB::raw("CAST({$ProdutosEstoqueTable}.estabelecimento AS INTEGER)"), $pedido->estabelecimento)
                ->whereRaw("(({$ProdutosEstoqueTable}.estoque) - ({$ProdutosEstoqueTable}.empenho + (CASE WHEN empenhopedido.empenho_pedido IS NOT NULL THEN empenhopedido.empenho_pedido ELSE 0 END))) > 0")
                ->orderBy("{$ProdutoEspecificacaoTable}.descricao", "asc")
                ->get();
			$return = [];
			$busca_estoque->each(function($item) use (&$return){
				$estoque = $item->estoque;
	
                if($estoque > 0){
                    $pecas = preg_replace('/[^0-9]/', '', $item->pecas);
                    $return[] = [
                        'value' => $item->codigo_produto,
                        'label' => !empty($item->pecas)? $item->descricao . ' - ' . parserQtd($estoque) . ' - Peça: '. $item->pecas : $item->descricao . ' - ' . parserQtd($estoque),
                        'pecas' => !empty($pecas)?intval($pecas) : '',
                    ];
                }				
			});
        }
        unset($busca_pedidos);
        unset($busca_estoque);

        return response()->json($return);
    }

    public function getEstoqueProdutoDisponivel(Request $request){
        $fields = $request->only('codigo', 'pedido');
        $pedidoObj = PedidoPortal::find($fields['pedido']);
        $return = [];
        
        $produtoObj = ProdutoEspecificacao::with(['produtoGrupo'])->where('codigo_produto', $fields['codigo'])->first();
        $pedidoItemObj = PedidoItemPortal::where('cod_produto', $fields['codigo'])->where('pedido', $fields['pedido'])->first();

        $result_estoque = $this->retornarDadosEstoqueNasajon($produtoObj, $pedidoObj->estabelecimento, $pedidoObj);
        $estoque_disponivel = 0;
        if ($pedidoObj->pedido_futuro === false || is_null($pedidoObj->pedido_futuro)){

            if(
                isset($result_estoque['pronta_entrega']) &&
                parserNumber($result_estoque['pronta_entrega']) > 0
            ){
                $estoque_disponivel = $result_estoque['pronta_entrega'];
            }
        }
        
        if ($pedidoObj->pedido_futuro === false || is_null($pedidoObj->pedido_futuro)){
            if(!empty($result_estoque['pronta_entrega'])){
                $return['estoque_disponivel'] = $estoque_disponivel;
            }
        }
        else if ($pedidoObj->pedido_futuro === true){
            if(isset($pedidoObj->data_previsao_entrega)){
                $data_previsao_entrega = strtotime(str_replace("/", "-", $pedidoObj->data_previsao_entrega));
                $coluna_quinzena = date("\k\_Y_m_", $data_previsao_entrega) . ((date("d", $data_previsao_entrega) <= 15)? "1": "2");
                if(isset($result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])) {
                    if($result_estoque['quinzenas'][$coluna_quinzena]['quantidade'] > 0){
                        $return['estoque_disponivel'] = $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'];
                    }
                }else{
                    $return['estoque_disponivel'] = $result_estoque['pronta_entrega'];
                }
            }
        }
        $preco_unitario_item = parserValor($pedidoItemObj->preco_unitario);
        $return['valor'] = $preco_unitario_item ;  
        $return['ipi'] = $pedidoItemObj->tem_ipi;
        $return['ipi_valor'] = $pedidoItemObj->ipi_produto;
        $return['estabelecimento'] = $pedidoObj->estabelecimento;

        $pecas = preg_replace('/[^0-9]/', '', $produtoObj->produtoGrupo->pecas);
        $return['pecas'] = !empty($pecas)?intval($pecas) : '';

        return response()->json($return);
    }

    public function modalPesquisa(){
		return view('programs.produto.search_modal_produto');
    }
    
    public function filterSimples(ProdutoFilterSimplesRequest $request){
        $fields = $request->only('grupo','codigo_produto','descricao','marca', 'linha');
        $query = ProdutoEspecificacao::with(['produtoGrupo' => function($query) use ($fields){
            if(!empty($fields['grupo'])){
                $query->where('descricao', 'ilike', '%'.trim($fields['grupo']).'%');
            }
        },'produtoNasajon','estoque'])->select();
        if(!empty($fields['grupo'])){
            $query->whereHas('produtoGrupo',function($query) use ($fields){
                $query->where('descricao', 'ilike', '%'.trim($fields['grupo']).'%');
            });
        }
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', 'ilike', '%'.trim($fields['codigo_produto']).'%');
        }
        if(!empty($fields['descricao'])){
            $query->where('descricao', 'ilike', '%'.trim($fields['descricao']).'%');
        }
        if(!empty($fields['marca'])){
            $query->where('marca', 'ilike', '%'.trim($fields['marca']).'%');
        }
        if(!empty($fields['linha'])){
            $query->where('linha', 'ilike', '%'.trim($fields['linha']).'%');
        }
        $query->where('ativo', true);
        $query->where('descricao', 'not ilike', '%DESATIVADO%');

        $result = $query->get();

        $retorno_array = [];

        $result->each(function($query) use (&$retorno_array){
            $retorno_array[] = [
                'grupo' => $query->grupo,
                'marca' => $query->marca,
                'linha' => $query->linha,
                'codigo_produto' => $query->codigo_produto,
                'subgrupo' => $query->subgrupo,
                'descricao' => $query->descricao,
                'unidade' => $query->unidade,
                'procedencia' => $query->procedencia,
                'data_de_cadastro' => $query->data_de_cadastro,
                'industrializado' => $query->industrializado,
                'composicao' => (!empty($query->produtoNasajon->composicao)) ? $query->produtoNasajon->composicao : '',
                'estoque' => (!empty($query->estoque[0]->estoque)) ? parserQtd($query->estoque->sum('estoque')) : ''
            ];
        });

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno_array
        ];
        return response()->json($retorno);
    }

    private function chavesProduto(){
        $chaves = [
            'DESCR' => 'descricao',
            'GRUPO' => 'grupo',
            'CODPRD' => 'codigo_produto',
            'MARCA' => 'marca',
            'LINHA' => 'linha',
            'PROCEDENCIA' => 'procedencia',
            'PRCVND_PREFIX_A' => 'preco_fob'
        ];
        return $chaves;
    }

    private function array_replace_key($arr, $chaves){
        $newarr = [];
        $retorno = [];
        foreach($arr as $key => $value){
            foreach($value as $chave => $dados){
                switch ($chave){
                    case 'PRCVND_PREFIX_A':
                        $newarr[$chaves[$chave]] = parserValor($dados);
                        break;
                    default:
                        $newarr[$chaves[$chave]] = utf8_encode($dados);
                        break;
                }
            }
            $retorno [] = $newarr; 
        }
        return $retorno;
    }

    public function pesquisaProduto(Request $request){
        $fields = $request->only('codigo_produto', 'descricao');
        $query = Produto::select('DESCR', 'GRUPO','CODPRD', 'MARCA', 'LINHA', 'PROCEDENCIA', 'PRCVND_PREFIX_A');
        if(!empty($fields['codigo_produto'])){
            $query->where('CODPRD', 'like', $fields['codigo_produto']);
            $query->where('DESCR', 'not like', '%DESATIVADO%');
            $result = $query->get()->toArray();
            if(count($result) == 1){
                $ajustearray = $this->array_replace_key($result, $this->chavesProduto());
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $ajustearray
                ];
                return response()->json($retorno);
            }
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => 'Error',
            'response' => ''
        ];
        return response()->json($retorno,422);   
    }

    public function pesquisaProdutoPorCodigo(Request $request){
        $fields = $request->only('codigo_produto', 'campo', 'condicao');
        $query = ProdutoEspecificacao::select();
        $query->with(['preco']);
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', '=', $fields['codigo_produto']);
            if(!empty($fields['campo']) && !empty($fields['condicao'])){
                $query->where($fields['campo'], 'ilike', $fields['condicao']);
            }
            $query->where('descricao', 'not ilike', '%DESATIVADO%');
            $result = $query->first();
            if(!empty($result)){
                $resultado = [
                    'descricao' => $result->descricao,
                    'grupo' => $result->grupo,
                    'codigo_produto' => $result->codigo_produto,
                    'marca' => $result->marca,
                    'linha' => $result->linha,
                    'procedencia' => $result->procedencia,
                    'preco_fob' => empty($result->preco)? '':$result->preco->preco_real
                ];
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $resultado
                ];
                return response()->json($retorno);
            }
        }
        $retorno = [
            'status' => 'error',
            'message' => '',
            'error' => ['codigo_produto' => 'Código não encontrado'],
            'response' => ''
        ];
        return response()->json($retorno,422);   
    }

    public function pesquisaProdutoPorDescricao(Request $request){
        $fields = $request->only('descricao', 'campo', 'condicao');

        $query = ProdutoEspecificacao::select();
        $query->with(['preco']);
        if(!empty($fields['descricao'])){
            $query->where('descricao', '=', utf8_decode(trim($fields['descricao'])));
            if(!empty($fields['campo']) && !empty($fields['condicao'])){
                $query->where($fields['campo'], 'ilike', $fields['condicao']);
            }
            $query->where('descricao', 'not ilike', '%DESATIVADO%');
            $result = $query->first();
            if(!empty($result)){
                $resultado = [
                    'descricao' => $result->descricao,
                    'grupo' => $result->grupo,
                    'codigo_produto' => $result->codigo_produto,
                    'marca' => $result->marca,
                    'linha' => $result->linha,
                    'procedencia' => $result->procedencia,
                    'preco_fob' => empty($result->preco)? '':$result->preco->preco_real
                ];
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $resultado
                ];
                return response()->json($retorno);
            }
        }
        if(!empty($fields['campo']) && !empty($fields['condicao'])){
            $retorno = [
                'status' => 'error',
                'message' => '',
                'error' => ['descricao' => strtoupper($fields['condicao'])." não encontrado"],
                'response' => ''
            ];
        }else{
            $retorno = [
                'status' => 'error',
                'message' => '',
                'error' => ['descricao' => 'Produto não encontrado'],
                'response' => ''
            ];
        }
        return response()->json($retorno,422);   
    } 

    public function pesquisaProdutoPorCodigoEspecificacao(Request $request){
        $fields = $request->only('codigo_produto');
        $query = ProdutoEspecificacao::select();
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', '=', $fields['codigo_produto']);
            $query->where('descricao', 'not like', '%DESATIVADO%');
            $result = $query->first();
            if(!empty($result)){
                $result = $result->toArray();
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => [
						'descricao' => $result['descricao'],
                        'codigo_produto' => $result['codigo_produto'],
                        'grupo' => $result['grupo'],
                        'marca' => $result['marca'],
                        'linha' => $result['linha']
					]
                ];
                return response()->json($retorno);
            }
        }
        $retorno = [
            'status' => 'error',
            'message' => '',
            'error' => ['codigo_produto' => 'Código não encontrado'],
            'response' => ''
        ];
        return response()->json($retorno,422);   
    }

    public function pesquisaProdutoPorDescricaoEspecificacao(Request $request){
        $fields = $request->only('descricao');
        $query = ProdutoEspecificacao::select();

        if(!empty($fields['descricao'])){
            $query->where('descricao', '=', utf8_decode(trim($fields['descricao'])));
            $query->where('descricao', 'not like', '%DESATIVADO%');
            $result = $query->first()->toArray();

            if(!empty($result)){
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => [
						'descricao' => $result['descricao'],
                        'codigo_produto' => $result['codigo_produto'],
                        'grupo' => $result['grupo'],
                        'marca' => $result['marca'],
                        'linha' => $result['linha']
					]
                ];
                return response()->json($retorno);
            }
        }
        $retorno = [
            'status' => 'error',
            'message' => '',
            'error' => ['descricao' => 'Produto não encontrado'],
            'response' => ''
        ];
        return response()->json($retorno,422);   
    }

    public function exportarExcel(Request $request){
        
        return Excel::download(new ExportarEstoque($request), 'estoque.xlsx');

    }

    public function pesquisaPrecoFobPorGrupo(Request $request){
        $fields = $request->only('grupo');
        $query = ProdutoEspecificacao::select();
        $produto = [];
        if(!empty($fields['grupo'])){
            $query->where('grupo', utf8_decode(trim($fields['grupo'])));
            $query->where('descricao', 'not ilike', '%DESATIVADO%');
            $query->whereHas('preco', function($query){
                $query->where('preco_real', '>', 0);
            });
            $result = $query->first();
            if(!empty($result)){
                $produto = [
                    'descricao' => $result->descricao,
                    'grupo' => $result->grupo,
                    'codigo_produto' => $result->codigo_produto,
                    'marca' => $result->marca,
                    'linha' => $result->linha,
                    'procedencia' => $result->procedencia,
                    'preco_fob' => parserValor($result->preco->preco_real),
                ];
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $produto
                ];
                return response()->json($retorno);
            }
        }
        $retorno = [
            'status' => 'error',
            'message' => '',
            'error' => ['Grupo' => 'Grupo não encontrado'],
            'response' => ''
        ];
        return response()->json($retorno,422);   
    }

    private function ajusteEstoqueEstabelecimento($value){
        $empresas = returnEmpresasNasajonView();
        foreach($value['data'] as $key => $dados){
            $value['data'][$key]['pronta_entrega_total'] = 0;

            if(!empty($value['data'][$key]['ncm'])){
                $value['data'][$key]['informacoes'][] = 'NCM - '.$value['data'][$key]['ncm'];
            }
            if(!empty($value['data'][$key]['ean'])){
                $value['data'][$key]['informacoes'][] = 'EAN - '.$value['data'][$key]['ean'];
            }
            if(empty($value['data'][$key]['ean']) && empty($value['data'][$key]['ncm'])){
                $value['data'][$key]['informacoes'][] = null;
            }
            
            foreach($dados['estoque_estabelecimentos'] as $key_estoque => $estoque){
                if($estoque > 0){
                    $value['data'][$key]['estoque_dados'][] = $empresas[$key_estoque]." - ".parserValor($estoque);
                    $value['data'][$key]['pronta_entrega_total'] += $estoque;
                }    
            }
            if(!empty($value['data'][$key]['pronta_entrega_total'])){
                $value['data'][$key]['pronta_entrega_total'] = parserValor($value['data'][$key]['pronta_entrega_total']);
            }else{
                $value['data'][$key]['pronta_entrega_total'] = '';
            }
            $compras = false;
            foreach($dados['quinzenas'] as $compra){
                if(floatval($compra['quantidade']) > 0){
                    $compras = true;
                }
            }
            if(floatval($dados['futuro']) > 0){
                $compras = true;
            }
            if(empty($value['data'][$key]['pronta_entrega_total']) && $compras === false){
                unset($value['data'][$key]);
            }
            
        }
        return $value;
    }

    public function promocional($produto, $estabelecimento, $codigo_vendedor, $codigo_cliente, $tipo_frete){
        $produto_promocao = ProdutoEspecificacao::with('promocoes')->find($produto);
        $busca = [
            'estabelecimento'   => $estabelecimento,
            'codigo_vendedor'   => $codigo_vendedor,
            'cliente'           => $codigo_cliente,
            'frete'             => $tipo_frete,
            'produto'           => $produto
        ];
        $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
            if (
                intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                $item->codigo_cliente == $busca['cliente'] &&
                isset($item->grupo) &&
                $item->codigo_produto == $busca['produto'] &&
                strtolower($item->tipo_frete) == strtolower($busca['frete'])
            ){
                return true;
            }else{
                return false;
            }
        });
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    $item->codigo_cliente == $busca['cliente'] &&
                    isset($item->grupo) &&
                    is_null($item->codigo_vendedor) &&
                    is_null($item->tipo_frete) &&
                    is_null($item->codigo_produto)
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    $item->codigo_cliente == $busca['cliente'] &&
                    isset($item->grupo) &&
                    $item->codigo_produto == $busca['produto'] &&
                    is_null($item->codigo_vendedor) &&
                    is_null($item->tipo_frete)
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    $item->codigo_cliente == $busca['cliente'] &&
                    isset($item->grupo) &&
                    $item->codigo_produto == $busca['produto'] &&
                    is_null($item->codigo_vendedor) &&
                    strtolower($item->tipo_frete) == strtolower($busca['frete'])
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    $item->codigo_cliente == $busca['cliente'] &&
                    is_null($item->codigo_produto) &&
                    is_null($item->codigo_vendedor) &&
                    strtolower($item->tipo_frete) == strtolower($busca['frete'])
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    (isset($item->vendedor) && $item->vendedor->codigo_representante == $busca['codigo_vendedor']) &&
                    isset($item->grupo) &&
                    is_null($item->codigo_cliente) &&
                    is_null($item->codigo_produto) &&
                    is_null($item->tipo_frete)
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    (isset($item->vendedor) && $item->vendedor->codigo_representante == $busca['codigo_vendedor']) &&
                    isset($item->grupo) &&
                    $item->codigo_produto == $busca['produto'] &&
                    is_null($item->codigo_cliente) &&
                    is_null($item->tipo_frete)
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    (isset($item->vendedor) && $item->vendedor->codigo_representante == $busca['codigo_vendedor']) &&
                    isset($item->grupo) &&
                    $item->codigo_produto == $busca['produto'] &&
                    is_null($item->codigo_cliente) &&
                    strtolower($item->tipo_frete) == strtolower($busca['frete'])
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    isset($item->grupo) &&
                    is_null($item->codigo_vendedor) &&
                    $item->codigo_produto == $busca['produto'] &&
                    is_null($item->codigo_cliente) &&
                    is_null($item->tipo_frete)
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        if($promossao === false){
            $promossao = $produto_promocao->promocoes->search(function($item) use ($busca){
                if (
                    intval($item->codigo_estabelecimento) == intval($busca['estabelecimento']) &&
                    isset($item->grupo) &&
                    is_null($item->codigo_vendedor) &&
                    is_null($item->codigo_produto) &&
                    is_null($item->codigo_cliente) &&
                    is_null($item->tipo_frete)
                ){
                    return true;
                }else{
                    return false;
                }
            });
        }
        unset($produto_promocao);
        
        return $promossao === false ? false : true;
    }

    public function autoCompleteLimitado(Request $request){
        $fields = $request->only(["term", "campo", "condicao"]);
        $return = [];
        if($fields["condicao"] == 'todos'){
        $query = ProdutoEspecificacao::select('descricao', 'codigo_produto')
            ->limit("15")
            ->orderBy('descricao', "ASC")
            ->where("descricao", "ilike", '%'.(strtolower(trim($fields["term"]))).'%')
            ->distinct('descricao')
            ->get()
            ->toArray();
        }else{
            $query = ProdutoEspecificacao::select('descricao', 'codigo_produto')
            ->limit("15")
            ->orderBy('descricao', "ASC")
            ->where("descricao", "ilike", '%'.(strtolower(trim($fields["term"]))).'%')
            ->where($fields["campo"], "ilike", $fields["condicao"])
            ->distinct('descricao')
            ->get()
            ->toArray();
        }
        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'value' => trim($value['codigo_produto']),
                'label' => trim($value['descricao'])
            ];
        }
        return response()->json($return);
    }

    public function modalPesquisaLimitado(Request $request){
        $fields = $request->only(['campo', 'condicao']);

        $campo = $fields['campo'];
        $condicao = $fields['condicao'];

		return view('programs.produto.modal.buscar_limitada')->with(['campo' => $campo, 'condicao' => $fields['condicao']]);
    }

    public function filterSimplesLimitado(Request $request){
        $fields = $request->only('grupo','codigo_produto','descricao','marca', 'linha', 'subgrupo', 'campo', 'condicao');

        if (empty($fields['grupo']) && empty($fields['codigo_produto']) && empty($fields['descricao']) && empty($fields['marca']) && empty($fields['linha']) && empty($fields['campo']) && empty($fields['condicao'])){

            $mensagem[] = "Informe pelo menos um campo para a busca!";

            $return = [
                "message" => '',
                'errors' => [
                    'grupo' => $mensagem,
                    'codigo_produto' => $mensagem,
                    'descricao' => $mensagem,
                    'marca' => $mensagem,
                    'linha' => $mensagem,
                    'subgrupo' => $mensagem,
                ]
            ];

            return response()->json($return, 422);
        }

        $query = ProdutoEspecificacao::select();
        if(!empty($fields['grupo'])){
            $query->where('grupo', 'ilike', '%'.utf8_decode(trim($fields['grupo'])).'%');
        }
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', 'ilike', '%'.utf8_decode(trim($fields['codigo_produto'])).'%');
        }
        if(!empty($fields['descricao'])){
            $query->where('descricao', 'ilike', '%'.utf8_decode(trim($fields['descricao'])).'%');
        }
        if(!empty($fields['marca'])){
            $query->where('marca', 'ilike', '%'.utf8_decode(trim($fields['marca'])).'%');
        }
        if(!empty($fields['linha'])){
            $query->where('linha', 'ilike', '%'.utf8_decode(trim($fields['linha'])).'%');
        }
        if(!empty($fields['campo']) && !empty($fields['condicao'])){
            $query->where($fields['campo'], 'ilike', utf8_decode(trim($fields['condicao'])));
        }
        $query->where('descricao', 'not ilike', '%DESATIVADO%');
        $query->where('ativo', 'true');
        $result = $query->get();

        $produtos = [];

        foreach($result as $produto){
            $produtos[]=[
                'grupo' => $produto->grupo,
                'codigo' => $produto->codigo_produto,
                'descricao' => $produto->descricao,
                'marca' => $produto->marca,
                'linha' => $produto->linha,
                'subgrupo' => $produto->subgrupo
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produtos
        ];
        return response()->json($retorno);
    }

    public function retornaInformacaoPrecoProducao(Request $request){
        $fields = $request->only('codigo_base', 'codigo_desenho', 'pedido');
        $PedidoPortalObj = PedidoPortal::findOrFail($fields['pedido']);

        $arr_preco = [];
        $return = [];
        $fields['codigo_base'] = strtoupper($fields['codigo_base']);
        $fields['codigo_desenho'] = strtoupper($fields['codigo_desenho']);
        
        $ProdutoBaseObj = ProdutoEspecificacao::where('codigo_produto', $fields['codigo_base'])->first();
        if(is_null($ProdutoBaseObj)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_base' => 'Produto não encontrado.'
                ]
            ], 422);
        }
        if($ProdutoBaseObj->ativo != 'true'){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_base' => 'Produto desativado.'
                ]
            ], 422);
        }
        $ProdutoTecidoBaseObj = ProdutoTecidoBase::with('tecido_base_detalhes')->where('codigo_produto', $fields['codigo_base'])->first();
        if(is_null($ProdutoTecidoBaseObj)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_base' => 'Produto não é para produção.'
                ]
            ], 422);
        }
        $ProdutoBasePrecoObj = Preco::where('codigo_produto', $fields['codigo_base'])->where('preco_real', '>', '0')->first();
        if(is_null($ProdutoBasePrecoObj)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_base' => 'Produto preço cadastrado. Por favor, verifique com o setor responsável.'
                ]
            ], 422);
        }


        $razao_cnpj_textil = '06311274';
        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $PedidoPortalObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);
        $preco_custo = false;
        if(
            !in_array(str_pad($PedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $arr_preco['prazo_medio'] = 0;
            $preco_custo = true;
        }
        else {
            $arr_preco['prazo_medio'] = $PedidoPortalObj->condicao_pagamento_detalhes->media ?? 0;
        }

        $result_estoque = $this->retornarDadosEstoqueNasajon($ProdutoBaseObj, $PedidoPortalObj->estabelecimento, $PedidoPortalObj, $preco_custo);
        $estoque_disponivel = 0;

        if(
            !isset($result_estoque['pronta_entrega']) ||
            parserNumber($result_estoque['pronta_entrega']) <= 0
        ){
            $return['sem_estoque'] = [
                'check' => true,
                'mensagem' => 'Produto sujeito a confirmação de estoque'
            ];
        }else{
            $return['sem_estoque'] = [
                'check' => false,
                'mensagem' => ''
            ];
            $estoque_disponivel = $result_estoque['pronta_entrega'];
        }
        

        $ProdutoDesenhoObj = ProdutoEspecificacao::where('codigo_produto', $fields['codigo_desenho'])->first();
        if(is_null($ProdutoDesenhoObj)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_desenho' => 'Produto não encontrado.'
                ]
            ], 422);
        }
        if(strtolower($ProdutoDesenhoObj->grupo) != 'desenho estamparia digital'){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_desenho' => 'Produto não encontrado.'
                ]
            ], 422);
        }
        if($ProdutoDesenhoObj->ativo != 'true'){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_desenho' => 'Produto desativado.'
                ]
            ], 422);
        }
        $ProdutoDesenhoPrecoObj = Preco::where('codigo_produto', $fields['codigo_desenho'])->where('preco_real', '>', '0')->first();
        if(is_null($ProdutoDesenhoPrecoObj)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => [
                    'codigo_desenho' => 'Produto sem preço cadastrado. Por favor, verifique com o setor responsável.'
                ]
            ], 422);
        }

        $return['desenho'] = [
            'codigo' => $ProdutoDesenhoObj->codigo_produto,
            'descricao' => $ProdutoDesenhoObj->descricao
        ];
        $return['base'] = [
            'codigo' => $ProdutoBaseObj->codigo_produto,
            'descricao' => $ProdutoBaseObj->descricao
        ];

        $ProdutoTecidoEstampadoObj = ProdutoTecidoEstampado::with(['produto_final_detalhes'])->
            where('produto_codigo_desenho', $ProdutoDesenhoObj->codigo_produto)->
            where('produto_tecidos_bases_id', $ProdutoTecidoBaseObj->id)->
            first();
            
        if(!is_null($ProdutoTecidoEstampadoObj)){
            $return['final'] = [
                'codigo' => $ProdutoTecidoEstampadoObj->produto_final_detalhes->codigo_produto,
                'descricao' => $ProdutoTecidoEstampadoObj->produto_final_detalhes->descricao,
                'novo' => false,
                'preco' => 0
            ];
        }
        else{
            $codigo = $ProdutoTecidoBaseObj->codigo_produto_base . '' . $ProdutoDesenhoObj->codigo_produto;
            $descricao = strtoupper($ProdutoBaseObj->grupo . ' Digital ' . $ProdutoDesenhoObj->codigo_produto);
            $return['final'] = [
                'codigo' => $codigo,
                'descricao' => $descricao,
                'novo' => true,
                'preco' => 0
            ];
        }
        
        switch ($PedidoPortalObj->estabelecimento) {
            case "03":
            case 3:
                $origem  = "RO";
                break;
            case "04":
            case 4:
                $origem = "TO";
                break;
            default:
                $origem = 'SP';
                break;
        }

        $arr_preco['produto'] = $ProdutoBaseObj->codigo_produto;

        $estadoObj = CepEstado::find($PedidoPortalObj->cliente->uf);

        $PedidoPortalControllerObj = new PedidoPortalController();
        $preco_frete = $PedidoPortalControllerObj->fretePreco($PedidoPortalObj);
        $internacional = 'false';


        $aliquotaObj = AliquotaPreco::where('origem', $origem)
            ->where('estado', $estadoObj->uf)
            ->where('internacional', $internacional)
            ->first();
        
        $MargemPrazoObj = MargemPrazo::where('estabelecimento', $PedidoPortalObj->estabelecimento)->first();
        $valor_frete = 1;
        if($preco_frete['preco'] === 'CIF'){
            $valor_frete = 1 + ($aliquotaObj->frete_adicional / 100);
        }

        $icms_base = 12;
        $icms = (float) (
            $PedidoPortalObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($PedidoPortalObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($PedidoPortalObj->cliente->indicadorinscricaoestadual) == 9
        ) ? $aliquotaObj->icms_venda_cliente_isento : $aliquotaObj->icms_venda;
        $valor_icms = 1 + (($icms) - $icms_base)/100;

        unset($aliquotaObj);

        $valor_ipi = 1;
        $valor_base = $ProdutoBasePrecoObj->preco_real + $ProdutoDesenhoPrecoObj->preco_real;

        $valor_fatorado = (($valor_base * $valor_icms) * $valor_frete) * $valor_ipi;

        $preco_final = $valor_fatorado * (1 + ($MargemPrazoObj->fator_diario * $arr_preco['prazo_medio']));
        $preco_final = round($preco_final * 100) / 100;
        $preco_final = parserValor($preco_final);

        $return['coluna'] = '3%';
        
        unset($MargemPrazoObj);

        $return['final']['preco'] = $preco_final;
        $return['final']['estoque'] = $estoque_disponivel;
        $return['promocional'] = false;

        if($return['promocional'] !== false){
            $comissao = $return['promocional']['comissao'];
            $comissao = str_replace('.', ',', $comissao);
            $return['coluna'] = $comissao.'%' ?? '2%';
        }
        if($preco_custo === true){
            $return['coluna'] = '0%';
            $return['aliquota'] = "0%";
            $return['preco_unitario'] = $result_estoque['custo'] ?? 0;
        }

        if(!empty($PedidoPortalObj->usuario_detalhes->detalhesModelHasRoles)){
            $perfil_acesso = $PedidoPortalObj->usuario_detalhes->detalhesModelHasRoles->detalhesRoles->name;

            if($perfil_acesso === 'REP.Playstation'){
                $return['coluna'] = '6%';
            }
        }

        $produtoCampanha = CampanhasProduto::with('campanha')
        ->where('produto_codigo', $fields['produto_codigo'])
        ->whereHas('campanha', function($query) {
            $query->where('ativo', true);
        })
        ->first();

        $return['nome_campanha'] = '';
        $return['coluna_informativo'] = '';

        if(!empty($produtoCampanha->produto_codigo)){
            $campanha = $produtoCampanha->campanha;
            $return['nome_campanha'] = $campanha->nome;

            if($PedidoPortalObj->usuario_detalhes->tipo_usuario_id == 12){
                if($campanha->tipo_comissao_representante == 1){
                    $return['coluna_informativo'] = 'Comissão '.$return['coluna'].' + '.parserValor($campanha->comissao_representante).'% incentivo';
                    $return['coluna'] = parserValor(floatval($return['coluna']) + $campanha->comissao_representante).'% ';
                }
                if($campanha->tipo_comissao_representante == 2){
                    $return['coluna_informativo'] = 'Comissão '.parserValor($campanha->comissao_representante).'% incentivo';
                    $return['coluna'] = parserValor($campanha->comissao_representante).'% ';
                }
            }
            if(in_array($PedidoPortalObj->usuario_detalhes->tipo_usuario_id, [16, 13])){
                if($campanha->tipo_comissao_vendedor_interno == 1){
                    $return['coluna_informativo'] = 'Comissão '.$return['coluna'].' + '.parserValor($campanha->comissao_vendedor_interno).'% incentivo';
                    $return['coluna'] = parserValor(floatval($return['coluna']) + $campanha->comissao_vendedor_interno).'% ';
                }
                if($campanha->tipo_comissao_vendedor_interno == 2){
                    $return['coluna_informativo'] = 'Comissão '.parserValor($campanha->comissao_vendedor_interno).'% incentivo';
                    $return['coluna'] = parserValor($campanha->comissao_vendedor_interno).'% ';
                }
            }
            if(in_array($PedidoPortalObj->usuario_detalhes->tipo_usuario_id, [14, 19])){
                if($campanha->tipo_comissao_gerente == 1){
                    $return['coluna_informativo'] = 'Comissão '.$return['coluna'].' + '.parserValor($campanha->comissao_gerente).'% incentivo';
                    $return['coluna'] = parserValor(floatval($return['coluna']) + $campanha->comissao_gerente).'% ';
                }
                if($campanha->tipo_comissao_gerente == 2){
                    $return['coluna_informativo'] = 'Comissão '.parserValor($campanha->comissao_gerente).'% incentivo';
                    $return['coluna']  = parserValor($campanha->comissao_gerente).'% ';
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'response' => $return,
            'error' => [
            ]
        ]);

    }

    function getTecidoBasePeloEstampado($produto, $codigo_tecidos_base){
        if(!empty($codigo_tecidos_base)){
            $produto_nasajon = ProdutoNasajon::where('codigo', $codigo_tecidos_base)->first();
            return $produto_nasajon;
        }else{
            $query = ProdutoTecidoEstampado::select();
            $query->where('produto_codigo_final', 'ilike', $produto);
            $result = $query->first();

            if(!empty($result)){
                return $result->tecido_base->codigo_produto;
            }
        }

        return $produto;
    }

    public function pecasPedido(Request $request){
        $fields = $request->only(['produto', 'estabelecimento', 'quantidade']);
        $pecas = [];
        $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
        $produto = $fields['produto'];
        $quantidade = parserNumber($fields['quantidade']);

        $produto_nasajon = ProdutoNasajon::where('codigo', $produto)->first();
        if(
            $produto_nasajon->controlalote != true ||
            !in_array($produto_nasajon->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS', 'Kg', 'KG'])
        ){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => []
            ], 422);
        }
        $pecasNasajonObj = collect(DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_fracoes_produtos('".$estabelecimento."','".$produto."') WHERE saldo <=  {$this->quantidade_pecas_pedido} and empenhado = false order by saldo desc"));
        $pecasNasajon = [];
        if($pecasNasajonObj->isEmpty()){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => []
            ], 422);
        }
        foreach($pecasNasajonObj as $peca){
            $pecasNasajon[] = $peca;
        }
        unset($pecasNasajonObj);

        foreach($pecasNasajon as $key => $peca){
            if($peca->saldo >= $quantidade){
                if(count($pecas) < 3){
                    $pecas[] = $peca;
                }
            }
        }
        $pecas = collect($pecas);

        $retorno = [];
        foreach($pecas as $peca){
            $retorno[] = [
                'codigo' => $peca->fracao_codigo,
                'quantidade' => parserQtd($peca->saldo),
                'localização' => $peca->local_de_estoque_endereco
            ];
        }
        if(empty($retorno)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'response' => [],
                'error' => []
            ], 422);
        }
        return response()->json([
            'status' => 'success',
            'message' => '',
            'response' => $retorno,
            'error' => []
        ]);
    }

    public function segmentos(){

        $segmentos = [];
  
        $segmentos= Segmento::all()->pluck('descricao', 'id');
               
        return  $segmentos;
    }
}
