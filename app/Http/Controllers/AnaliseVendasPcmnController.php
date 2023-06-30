<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use App\ComprasNasajon;
use App\ProdutoEspecificacao;
use App\PedidoPortal;
use App\PedidosReservaProdutoNasajon;
use App\Movimentacao;
use App\NotasVenda;

use App\Http\Requests\AnaliseVendasPcmnConsultaRequest;

use Auth;

class AnaliseVendasPcmnController extends Controller
{
    public function __construct() {
        $this->middleware(['auth']);
        $estabelecimentosEmpty[''] = 'ESTABELECIMENTO';
        $estabelecimentosReturn = returnEmpresasNasajonView();

        foreach($estabelecimentosReturn as $key => $estabelecimento){
            $estabelecimentosEmpty[$key] = $estabelecimento;
        }
        
        $this->estabelecimentos = $estabelecimentosEmpty;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseVendasPcmnController") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\AnaliseVendasPcmnController');
    	return view("programs.vendas_pcmn.index")->with(['estabelecimentos' => $this->estabelecimentos]);
    }

    public function filter(AnaliseVendasPcmnConsultaRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('estabelecimento','grupo', 'pcmn','data_inicio', 'data_fim');

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        $data = new Carbon();  

        $produtos_compras = ComprasNasajon::whereBetween('data_entrega',[$data_inicio,$data_fim])
        ->with('produto')
        ->where('situacao','Liquidado');

        if(!empty($fields['grupo'])){
            $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')-> where('produto_grupos.descricao', 'ilike', strtoupper($fields['grupo']))->get()->pluck('codigo_produto');
            $produtos_compras->whereIn('cod_produto',$produtos);
        }
        if(!empty($fields['pcmn'])){
            $produtos_compras->where('numero_pedido',$fields['pcmn']);
        }
        if(!empty($fields['estabelecimento'])){
            $produtos_compras->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT));
        }

        $produtos_compras = $produtos_compras->get();
        $codigo_produtos = $produtos_compras->pluck('cod_produto')->unique();
        $produtos_pedido = collect();
        $produtos_pedido_em_aberto = collect();

        $pedido_portal = PedidoPortal::with(['itens_pedido'])
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->whereHas('itens_pedido', function($query) use ($codigo_produtos){
            $query->whereIn("cod_produto", $codigo_produtos);
        })->get();

        foreach($pedido_portal as $pedido){
            foreach($pedido->itens_pedido as $item){
                $produtos_pedido_em_aberto->push(['estabelecimento' => $pedido->estabelecimento,
                'codigo_produto' => $item->cod_produto,
                'quantidade' => $item->quantidade,
                'pedido' => '',
                'data_pedido' => $item->data_pedido]);
            }
        }

        unset($pedido_portal);

        $pedido_venda_nasajon = Movimentacao::whereIn('cfop', 
        // ['5922', '5949', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '1124', '2124'])
        ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '1124', '2124'])
        ->where('data_movimentacao','>=',$data_inicio)
        ->whereIn('produto_codigo', $codigo_produtos)
        ->get();

        foreach($pedido_venda_nasajon as $pedido){
            $produtos_pedido->push(['estabelecimento'=> $pedido->estabelecimento,
            'codigo_produto'=> $pedido->produto_codigo,
            'quantidade'=> $pedido->quantidade,
            'pedido' => $pedido->documento,
            'data_pedido'=> $pedido->data_movimentacao]);
        }

        unset($pedido_venda_nasajon);
        $query_pedido_reservado = PedidosReservaProdutoNasajon::whereIn("codigo_produto", $codigo_produtos)
        ->get();

        foreach($query_pedido_reservado as $pedido){
            $quantidade = $pedido->quantidade;
            $quantidade_separada = $pedido->quantidade_faturada;
            $quantidade_total = 0;
            if($quantidade_separada > 0){
                $quantidade_total = $quantidade_separada;
            }else{
                $quantidade_total = $quantidade;
            }
            $produtos_pedido_em_aberto->push(['estabelecimento'=> $pedido->estabelecimento_codigo,
            'codigo_produto'=> $pedido->produto_codigo,
            'quantidade'=> $quantidade_total,
            'pedido' => $pedido->numero,
            'data_pedido'=> $pedido->emissao]);
        }

        unset($query_pedido_reservado);

        $seisMeses = Carbon::Now();
        $seisMeses->day = 1;
        $seisMeses->addMonths(-5);

        $notas_vendas = NotasVenda::whereRaw("cast( concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date ) >= '{$seisMeses->format('Y-m-d')}'")
            ->whereIn('codigo_produto', $codigo_produtos)
            ->get();

        $dados = [];
        $retorno = [];
        $total = [
            'quantidade_comprada' => 0,
            'quantidade_vendida' => 0,
            'percentual_vendido' => 0,
            'media_vendas_mensal' => 0
        ];
        $estabelecimentos = returnEmpresasNasajonView();
        $media_mensal = $data->diffInMonths($data_inicio) + 1;

        foreach($produtos_compras as $produto){
            $quantidade_vendida = 0;

            $resultado_busca_produtos = $produtos_pedido->where('codigo_produto',$produto->cod_produto)
            ->where('data_pedido','>=',$produto->data_entrega)
            ->sum('quantidade');

            if(!empty($resultado_busca_produtos)){
                $quantidade_vendida += $resultado_busca_produtos;
            }

            $resultado_busca_produtos = $produtos_pedido_em_aberto->where('codigo_produto',$produto->cod_produto)
            ->sum('quantidade');

            if(!empty($resultado_busca_produtos)){
                $quantidade_vendida += $resultado_busca_produtos;
            }

            $media_mensal = $notas_vendas->where('codigo_produto',$produto->cod_produto)->sum('quantidade');

            if(!isset($dados[$produto->numero_pedido])){
                $dados[$produto->numero_pedido] = [
                    'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                    'pcmn' => $produto->numero_pedido,
                    'proforma' => $produto->proforma,
                    'id_pedido' => encrypt($produto->id_nota),
                    'entrada' => (!empty($produto->data_entrega)) ? parserData($produto->data_entrega) : '',
                    'quantidade_comprada' => 0,
                    'quantidade_vendida' => 0,
                    'percentual_vendido' => 0,
                    'media_vendas_mensal' => 0,
                    'filter' => encrypt([
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'grupo' => $fields['grupo'],
                        'pcmn' => $produto->numero_pedido,
                        'estabelecimento' => $fields['estabelecimento'],
                    ]),
                ];
            }

            $dados[$produto->numero_pedido]['quantidade_comprada'] += $produto->quantidade;
            $dados[$produto->numero_pedido]['quantidade_vendida'] += $quantidade_vendida;
            $dados[$produto->numero_pedido]['media_vendas_mensal'] += $media_mensal;

            $total['quantidade_comprada'] += ($produto->quantidade > 0) ? $produto->quantidade : 0;
            $total['media_vendas_mensal'] += ($media_mensal > 0) ? $media_mensal : 0;
        }

        unset($produtos_compras);

        foreach($dados as $key => $dado){
            $quantidade_vendida = ($dado['quantidade_vendida'] > $dado['quantidade_comprada']) ? $dado['quantidade_comprada'] : $dado['quantidade_vendida'];
            $retorno[] = [
                'estabelecimento' => $dado['estabelecimento'],
                'pcmn' => $dado['pcmn'],
                'proforma' => $dado['proforma'],
                'entrada' => $dado['entrada'],
                'filter' => $dado['filter'],
                'id_pedido' => $dado['id_pedido'],
                'percentual_vendido' => ($dado['quantidade_comprada'] > 0 && $quantidade_vendida > 0) ? parserQtd($quantidade_vendida / $dado['quantidade_comprada'] * 100) : '',
                'media_vendas_mensal' => ($dado['media_vendas_mensal'] > 0) ? parserQtd($dado['media_vendas_mensal'] / 6) : '',
                'quantidade_comprada' => ($dado['quantidade_comprada'] > 0) ? parserQtd($dado['quantidade_comprada']) : '',
                'quantidade_vendida' => ($quantidade_vendida > 0) ? parserQtd($quantidade_vendida) : '',
            ];
            
            $total['quantidade_vendida'] += ($quantidade_vendida > 0) ? $quantidade_vendida : 0;
        }

        $total['percentual_vendido'] = ($total['quantidade_comprada'] > 0) ? parserQtd($total['quantidade_vendida'] / $total['quantidade_comprada']  * 100) : '';
        $total['media_vendas_mensal'] = ($total['media_vendas_mensal'] > 0) ? parserQtd($total['media_vendas_mensal'] / 6) : '';
        $total['quantidade_comprada'] = ($total['quantidade_comprada'] > 0) ? parserQtd($total['quantidade_comprada']) : '';
        $total['quantidade_vendida'] = ($total['quantidade_vendida'] > 0) ? parserQtd($total['quantidade_vendida']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $retorno, 'total' => $total],
        ]);
    }

    public function mediaVenda(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filtro']);
        try{
            $fields = decrypt($filter['filtro']);
        }catch(Exception $e){   
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        $data = new Carbon();  

        $produtos_compras = ComprasNasajon::whereBetween('data_entrega',[$data_inicio,$data_fim])
        ->where('situacao','Liquidado');

        if(!empty($fields['grupo']) && empty($fields['codigo_produto'])){
            $produtos = ProdutoEspecificacao::where('grupo', 'ilike', strtoupper($fields['grupo']))->get()->pluck('codigo_produto');
            $produtos_compras->whereIn('cod_produto',$produtos);
        }
        if(!empty($fields['pcmn'])){
            $produtos_compras->where('numero_pedido',$fields['pcmn']);
        }
        if(!empty($fields['estabelecimento'])){
            $produtos_compras->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT));
        }
        
        $produtos_compras = $produtos_compras->get();
        $codigo_produtos = $produtos_compras->pluck('cod_produto')->unique();
        $agora = Carbon::Now()->format('Y-m-d');
        $seisMeses = Carbon::Now();
        $seisMeses->day = 1;
        $seisMeses->addMonths(-5)->format('Y-m-d');

        
        $notas_vendas = NotasVenda::selectRaw("cast( concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date ) as data, quantidade, codigo_produto")
            ->whereRaw("cast( concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date ) >= '{$seisMeses->format('Y-m-d')}'")
            ->whereIn('codigo_produto', $codigo_produtos)
            ->get();

        $dados = [];
        $retorno = [];

        foreach($produtos_compras as $produto){
            $data_header = (!empty($produto->data_entrega)) ? Carbon::parse($produto->data_entrega)->format('Ym') : '';
            if(!isset($dados[$data_header])){
                $dados[$data_header] = [
                    'data' => $data_header,
                    'quantidade_vendida' => 0,
                    'quantidade_comprada' => 0,
                ];
            }
            $dados[$data_header]['quantidade_comprada'] += $produto->quantidade;
        }
        
        foreach($notas_vendas as $produto){
            $data_header = (!empty($produto->data)) ? Carbon::parse($produto->data)->format('Ym') : '';
            if(!isset($dados[$data_header])){
                $dados[$data_header] = [
                    'data' => $data_header,
                    'quantidade_vendida' => 0,
                    'quantidade_comprada' => 0,
                ];
            }
            $dados[$data_header]['quantidade_vendida'] += $produto->quantidade;
        }
        $quantidade_comprada = 0;
        foreach($dados as $dado){
            $quantidade_comprada +=  $dado['quantidade_comprada'];
            $quantidade_vendida = $dado['quantidade_vendida'];
            
            $retorno[] = [
                'data' => $dado['data'],
                'quantidade_vendida' => ($quantidade_vendida > 0) ? parserQtd($quantidade_vendida) : '',
                'percentual' => ($quantidade_comprada  > 0 && $quantidade_vendida) ? parserQtd($quantidade_vendida / $quantidade_comprada  * 100) : '',
                'media' => ($quantidade_vendida > 0) ? parserQtd($quantidade_vendida) : '',
            ];
        }

        $head = [];

        for($i = 0; $i <= 6 ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('Ym');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }
        asort($head);
        
        return view('programs.vendas_pcmn.modal.media_venda')->with(['heads' => $head, 'dados' => $retorno, 'pedido' => $fields['pcmn']]);
    }

}
