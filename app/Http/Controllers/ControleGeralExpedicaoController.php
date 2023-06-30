<?php

namespace App\Http\Controllers;

use App\PedidosVendaNasajon;
use Illuminate\Http\Request;
use Auth;
use App\Http\Requests\ControleGeralConsultaRequest;
use App\PecasConferenciaNasajon;
use Illuminate\Support\Facades\DB;

class ControleGeralExpedicaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ControleGeralExpedicao") === false){
            return abort(403);
        }
        $estabelecimentos = returnEmpresasNasajonView();
        $request->session()->flash('model', 'App\ControleGeralExpedicao');
    	return view("programs.controle_geral_expedicao.index")->with('estabelecimentos',$estabelecimentos);
    }

    public function filtro(ControleGeralConsultaRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campo = $request->only('estabelecimento');


        $ControleGeralExpedicaoObj = PecasConferenciaNasajon::select('estabelecimento_codigo', 'numero_nota', 'quantidade_peca', 'conferido', 'codigo_peca');

        $PedidosVendaObj = PedidosVendaNasajon::with('itens_pedido')
        ->select('estabelecimento_codigo', 'situacao_descricao', 'id', 'numero', 
        'valor',DB::raw('count(numero) as quantidade_pedido'))
        ->where('rascunho', 'false')
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA',])
                ->orWhereNull('grupodeoperacao')
                ->orWhereIn('operacao_codigo', ['PEDAMOSTRA','PEDAMOSTRAGRATIS']);
        })
        ->whereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA','REMESSA'])
        ->whereIn('situacao_descricao', ['Aberto', 'Em Faturamento'])
        ->where('sinal', 0)
        ->groupBy('estabelecimento_codigo', 'situacao_descricao', 'id', 'numero', 'valor');
        
        if(!empty($campo['estabelecimento'])){
            $ControleGeralExpedicaoObj->where('estabelecimento_codigo', str_pad($campo['estabelecimento'],2,'0', STR_PAD_LEFT));
            $PedidosVendaObj->where('estabelecimento_codigo', str_pad($campo['estabelecimento'],2,'0', STR_PAD_LEFT));
        }

        $ExpedicaoConferencia = $ControleGeralExpedicaoObj->get()
        ->groupBy('estabelecimento_codigo');
        $PedidosVenda = $PedidosVendaObj->get();
        $pedido_a_faturar = $PedidosVenda->where('situacao_descricao', 'Em Faturamento');
        $pedido_separar = $PedidosVenda->where('situacao_descricao', 'Aberto');

        $saida = [];
        $total = [
            'nota_a_conferir' => 0,
            'peca_a_conferir' => 0,
            'quantidade_a_conferir' => 0,
            'nota_conferida' => 0,
            'peca_conferida' => 0,
            'quantidade_conferida' => 0,
            'pedido_separado' => 0,
            'pedido_a_separar' => 0,
            'quantidade_a_separar' => 0,
            'valor' => 0,
            'filters' =>  [
                'estabelecimento' => !empty($campo['estabelecimento']) ? str_pad($campo['estabelecimento'],2,'0', STR_PAD_LEFT) : ''
            ]
        ];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($PedidosVenda->chunk(100) as $chuck){
            foreach($chuck as $pedido){
                $pedido_a_separar = $pedido->situacao_descricao === 'Aberto' ? $pedido->quantidade_pedido : 0;
                $quantidade_a_separar  = $pedido->situacao_descricao === 'Aberto' ? $pedido->itens_pedido->sum('quantidadecomercial') : 0;
                $valor_pedido = $pedido->situacao_descricao === 'Em Faturamento' ? $pedido->valor : 0;
                $pedido_separado = $pedido->situacao_descricao === 'Em Faturamento' ? $pedido->quantidade_pedido : 0;
                
                $saida[$pedido->estabelecimento_codigo] = [ 
                    'estabelecimento' =>  $estabelecimentos[(integer)$pedido->estabelecimento_codigo],
                    'pedido_a_separar' => 0,
                    'quantidade_a_separar' => 0,
                    'pedido_separado' => 0,
                    'valor' => 0,
                    'nota_a_conferir' => 0,
                    'peca_a_conferir' => 0,
                    'quantidade_a_conferir' => 0,
                    'nota_conferida' => 0,
                    'peca_conferida' => 0,
                    'filters' => [
                        'estabelecimento' => $pedido->estabelecimento_codigo
                    ],
                    'quantidade_conferida' => 0
                ];
                $total['pedido_separado'] += $pedido_separado;
                $total['pedido_a_separar'] += $pedido_a_separar;
                $total['quantidade_a_separar'] += $quantidade_a_separar;
                $total['valor'] += $valor_pedido;
            }
        }

        foreach($ExpedicaoConferencia->chunk(100) as $chuck){
            foreach($chuck as $estabelecimento => $value){
                $nota_a_conferir = $value->where('conferido', '!=', true)->unique('numero_nota')->count(); 
                $peca_a_conferir = $value->where('conferido', '!=', true)->where('codigo_peca', '!=' , null)->count();
                $quantidade_a_conferir = $value->where('conferido', '!=', true)->sum('quantidade_peca');

                $nota_conferida = $value->where('conferido', true)->unique('numero_nota')->count(); 
                $peca_conferida = $value->where('conferido', true)->where('codigo_peca', '!=' , null)->count(); 
                $quantidade_conferida = $value->where('conferido', true)->sum('quantidade_peca');

                $saida[$estabelecimento] = [ 
                    'estabelecimento' =>  $estabelecimentos[(integer)$estabelecimento],
                    'pedido_a_separar' => 0,
                    'quantidade_a_separar' => 0,
                    'pedido_separado' => 0,
                    'valor' => 0,
                    'nota_a_conferir' => $nota_a_conferir,
                    'peca_a_conferir' => $peca_a_conferir,
                    'quantidade_a_conferir' => $quantidade_a_conferir,
                    'nota_conferida' => $nota_conferida,
                    'peca_conferida' => $peca_conferida,
                    'filters' => [
                        'estabelecimento' => $estabelecimento
                    ],
                    'quantidade_conferida' => $quantidade_conferida,
                ];

                $total['nota_a_conferir'] += $nota_a_conferir;
                $total['peca_a_conferir'] += $peca_a_conferir;
                $total['quantidade_a_conferir'] += $quantidade_a_conferir;
                $total['nota_conferida'] += $nota_conferida;
                $total['peca_conferida'] += $peca_conferida;
                $total['quantidade_conferida'] += $quantidade_conferida;
            }
        }

        foreach($pedido_a_faturar as $pedidos){
            $valor = $pedidos->valor;
            $pedidos_separado = $pedidos->quantidade_pedido;
            $saida[$pedidos->estabelecimento_codigo]['pedido_separado'] += $pedidos_separado;
            $saida[$pedidos->estabelecimento_codigo]['valor'] += $valor;
        }
        foreach($pedido_separar as $quantidades){
            $quantidades_a_separar = $quantidades->itens_pedido->sum('quantidadecomercial');
            $pedidos_a_separar = $quantidades->quantidade_pedido;
            $saida[$quantidades->estabelecimento_codigo]['quantidade_a_separar'] += $quantidades_a_separar;
            $saida[$quantidades->estabelecimento_codigo]['pedido_a_separar'] += $pedidos_a_separar;
        }

        foreach($saida as $key => $values){
            $saida[$key]['pedido_separado'] = $saida[$key]['pedido_separado'] > 0 ? $saida[$key]['pedido_separado'] : '';
            $saida[$key]['pedido_a_separar'] = $saida[$key]['pedido_a_separar'] > 0 ? $saida[$key]['pedido_a_separar'] : '';
            $saida[$key]['quantidade_a_separar'] = $saida[$key]['quantidade_a_separar'] > 0 ? parserQtd($saida[$key]['quantidade_a_separar']) : '';
            $saida[$key]['valor'] = $saida[$key]['valor'] > 0 ? parserValor($saida[$key]['valor']) : '';
            $saida[$key]['nota_a_conferir'] = !empty($saida[$key]['nota_a_conferir']) ? $saida[$key]['nota_a_conferir'] : '';
            $saida[$key]['peca_a_conferir'] = $saida[$key]['peca_a_conferir'] > 0 ? $saida[$key]['peca_a_conferir'] : '';
            $saida[$key]['quantidade_a_conferir'] = $saida[$key]['quantidade_a_conferir'] > 0 ? parserQtd($saida[$key]['quantidade_a_conferir']) : '';
            $saida[$key]['nota_conferida'] = !empty($saida[$key]['nota_conferida']) ? $saida[$key]['nota_conferida'] : '';
            $saida[$key]['peca_conferida'] = $saida[$key]['peca_conferida'] > 0 ? $saida[$key]['peca_conferida'] : '';
            $saida[$key]['quantidade_conferida'] = $saida[$key]['quantidade_conferida'] > 0 ? parserQtd($saida[$key]['quantidade_conferida']) : '';
            $saida[$key]['filters'] = encrypt($saida[$key]['filters']); 
        }

        $total['pedido_separado'] = $total['pedido_separado'] > 0 ? $total['pedido_separado'] : '';
        $total['pedido_a_separar'] = $total['pedido_a_separar'] > 0 ? $total['pedido_a_separar'] : '';
        $total['quantidade_a_separar'] = $total['quantidade_a_separar'] > 0 ? parserQtd($total['quantidade_a_separar']) : '';
        $total['valor'] = $total['valor'] > 0 ? parserValor($total['valor']) : '';
        $total['nota_a_conferir'] = $total['nota_a_conferir'] > 0 ? $total['nota_a_conferir'] : '';
        $total['peca_a_conferir'] = $total['peca_a_conferir'] > 0 ? $total['peca_a_conferir'] : '';
        $total['quantidade_a_conferir'] = $total['quantidade_a_conferir'] > 0 ? parserQtd($total['quantidade_a_conferir']) : '';
        $total['nota_conferida'] = $total['nota_conferida'] > 0 ? $total['nota_conferida'] : '';
        $total['peca_conferida'] = $total['peca_conferida'] > 0 ? $total['peca_conferida'] : '';
        $total['quantidade_conferida'] = $total['quantidade_conferida'] > 0 ? parserQtd($total['quantidade_conferida']) : '';
        $total['filters'] = encrypt($total['filters']);
        
        
        sort($saida);
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['saida' => $saida, 'total' => $total],
        ]);
    }

    public function modalNotasConferir(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campo = $request->only(['filters', 'total']);
        try{
            $fields = decrypt($campo['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $ControleGeralExpedicaoObj = PecasConferenciaNasajon::with('nomeFornecedor')
        ->select('estabelecimento_codigo', 'numero_nota','id_nota', DB::raw('sum(quantidade_peca) as quantidade, count(codigo_peca) as pecas'))
        ->where(function($query) {
            $query->where('conferido', false)
            ->orWhere('conferido', null);
        })
        ->groupBy('estabelecimento_codigo', 'numero_nota', 'id_nota');

        if($campo['total'] != 'true' || !empty($fields['estabelecimento'])){
            $ControleGeralExpedicaoObj->where('estabelecimento_codigo', $fields['estabelecimento']);
        }

        $ExpedicaoConferencia = $ControleGeralExpedicaoObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $saida = [];
        $total = [
            'nota_numero' => 0,
            'peca' => 0,
            'quantidade' => 0,
        ];

        $nota_a_conferir = 0;
        foreach($ExpedicaoConferencia as $value){
            $nota_a_conferir += 1; 
            $peca_a_conferir = $value->pecas;
            $quantidade_a_conferir = $value->quantidade;
                $saida[] = [ 
                    'estabelecimento' => $estabelecimentos[(integer)$value->estabelecimento_codigo],
                    'nota_numero' => $value->numero_nota,
                    'nota_id' => $value->id_nota,
                    'fornecedor' => isset($value->nomeFornecedor->fornecedor) ? $value->nomeFornecedor->fornecedor : '',
                    'peca' =>  !empty($peca_a_conferir) ? $peca_a_conferir : '',
                    'quantidade' => !empty($quantidade_a_conferir) ? parserQtd($quantidade_a_conferir) : ''                
                ];
            $total['nota_numero'] = $nota_a_conferir;
            $total['peca'] += $peca_a_conferir;
            $total['quantidade'] += $quantidade_a_conferir; 
        }
        return view('programs.controle_geral_expedicao.modal.notas')->with(['dados' => $saida, 'total' => $total]);
    }

    public function modalNotasConferida(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campo = $request->only(['filters', 'total']);
        try{
            $fields = decrypt($campo['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $ControleGeralExpedicaoObj = PecasConferenciaNasajon::with('nomeFornecedor')
        ->select('estabelecimento_codigo', 'numero_nota','id_nota', DB::raw('sum(quantidade_peca) as quantidade, count(codigo_peca) as pecas'))
        ->where('conferido', true)
        ->groupBy('estabelecimento_codigo', 'numero_nota', 'id_nota');

        if($campo['total'] != 'true' || !empty($fields['estabelecimento'])){
            $ControleGeralExpedicaoObj->where('estabelecimento_codigo', $fields['estabelecimento']);
        }

        $ExpedicaoConferencia = $ControleGeralExpedicaoObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $saida = [];
        $total = [
            'nota_numero' => 0,
            'peca' => 0,
            'quantidade' => 0,
        ];
      
        $nota_conferida = 0;
        foreach($ExpedicaoConferencia as $value){
            $nota_conferida += 1; 
            $peca_conferida = $value->pecas;
            $quantidade_conferida = $value->quantidade;
       
                $saida[] = [ 
                    'estabelecimento' => $estabelecimentos[(integer)$value->estabelecimento_codigo],
                    'nota_numero' => $value->numero_nota,
                    'nota_id' => $value->id_nota,
                    'fornecedor' => isset($value->nomeFornecedor->fornecedor) ? $value->nomeFornecedor->fornecedor : '',
                    'peca' =>  !empty($peca_conferida) ? $peca_conferida : '',
                    'quantidade' => !empty($quantidade_conferida) ? parserQtd($quantidade_conferida) : ''                
                ];
            $total['nota_numero'] = $nota_conferida;
            $total['peca'] += $peca_conferida;
            $total['quantidade'] += $quantidade_conferida; 
        }
        return view('programs.controle_geral_expedicao.modal.notas')->with(['dados' => $saida, 'total' => $total]);
    }

    public function modalPedidosConferidos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campo = $request->only(['filters', 'total']);
        try{
            $fields = decrypt($campo['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $PedidosVendaObj = PedidosVendaNasajon::with('cliente_detalhes')
        ->select('estabelecimento_codigo', 'situacao_descricao', 'id',
         'cliente', 'numero', 'valor', 'cliente', 'origem', DB::raw('count(numero) as quantidade_pedido'))
        ->where('rascunho', 'false')
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA',])
                ->orWhereNull('grupodeoperacao')
                ->orWhereIn('operacao_codigo', ['PEDAMOSTRA','PEDAMOSTRAGRATIS']);
        })
        ->whereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA','REMESSA'])
        ->where('situacao_descricao', 'Em Faturamento')
        ->where('sinal', 0)
        ->groupBy('estabelecimento_codigo', 'situacao_descricao', 'id',
         'numero', 'valor', 'cliente', 'origem');

        if($campo['total'] != 'true' || !empty($fields['estabelecimento'])){
            $PedidosVendaObj->where('estabelecimento_codigo', $fields['estabelecimento']);
        }

        $PedidosVenda = $PedidosVendaObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $total = [
            'pedido_quantidade' => 0,
            'valor_total' => 0
        ];

        foreach($PedidosVenda as $pedido){
            $pedido_separado =  $pedido->quantidade_pedido;
            $valor = $pedido->valor;
            
            $saida[] = [ 
                'estabelecimentos' =>  $estabelecimentos[(integer)$pedido->estabelecimento_codigo],
                'pedido_numero' => $pedido->numero,
                'pedido_id' => $pedido->id,
                'cliente' => $pedido->cliente_detalhes->nome,
                'valor' => $valor,
                'estabelecimento' => $pedido->estabelecimento_codigo,
                'origem' => $pedido->origem
            ];
            $total['pedido_quantidade'] += $pedido_separado;
            $total['valor_total'] += $valor;
        }
        return view('programs.controle_geral_expedicao.modal.pedidos')->with(['dados' => $saida, 'total' => $total]);
    }

    public function modalPedidosConferir(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campo = $request->only(['filters', 'total']);
        try{
            $fields = decrypt($campo['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $PedidosVendaObj = PedidosVendaNasajon::with(['cliente_detalhes', 'itens_pedido'])
        ->select('estabelecimento_codigo', 'situacao_descricao', 'id',
         'cliente', 'numero', 'cliente', 'origem', DB::raw('count(numero) as quantidade_pedido'))
        ->where('rascunho', 'false')
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA',])
                ->orWhereNull('grupodeoperacao')
                ->orWhereIn('operacao_codigo', ['PEDAMOSTRA','PEDAMOSTRAGRATIS']);
        })
        ->whereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA','REMESSA'])
        ->where('situacao_descricao', 'Aberto')
        ->where('sinal', 0)
        ->groupBy('estabelecimento_codigo', 'situacao_descricao', 'id',
         'numero', 'cliente', 'origem');

        if($campo['total'] != 'true' || !empty($fields['estabelecimento'])){
            $PedidosVendaObj->where('estabelecimento_codigo', $fields['estabelecimento']);
        }

        $PedidosVenda = $PedidosVendaObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $total = [
            'pedido_quantidade' => 0,
            'quantidade_total' => 0
        ];
        $quantidade = true;

        foreach($PedidosVenda as $pedido){
            $pedido_separado =  $pedido->quantidade_pedido;
            $quantidade = $pedido->itens_pedido->sum('quantidadecomercial');
            
            $saida[] = [ 
                'estabelecimentos' =>  $estabelecimentos[(integer)$pedido->estabelecimento_codigo],
                'pedido_numero' => $pedido->numero,
                'pedido_id' => $pedido->id,
                'cliente' => !empty($pedido->cliente_detalhes->nome) ? $pedido->cliente_detalhes->nome : '',
                'quantidade' => $quantidade,
                'estabelecimento' => $pedido->estabelecimento_codigo,
                'origem' => $pedido->origem
            ];
            $total['pedido_quantidade'] += $pedido_separado;
            $total['quantidade_total'] += $quantidade;
        }
        return view('programs.controle_geral_expedicao.modal.pedidos')->with(['dados' => $saida, 'total' => $total, 'quantidade' => $quantidade]);

    }

    public function modalNotaConferencia(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $fields = $request->only('id_nota');
        $sql_dados_nota = "SELECT * FROM integracoes.fn_exportar_importacao_detalhe('".$fields['id_nota']."'::uuid)";

        try{
             $dados_nota = DB::connection('nasajon')->select($sql_dados_nota);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
       
        $PecasConferenciaObj = PecasConferenciaNasajon::select('codigo_produto', 'descricao_produto', 'quantidade_item')
        ->where('id_nota', $fields['id_nota'])
        ->groupBy('codigo_produto', 'descricao_produto', 'quantidade_item')
        ->get();

        
        $dados_nota = (array) reset($dados_nota);
        
        if(strlen($dados_nota['cnpjfornecedor']) > 9){
            $cnpj_fornecedor = mask($dados_nota['cnpjfornecedor'], '##.###.###/####-##');

        }else{
            $cnpj_fornecedor = mask($dados_nota['cnpjfornecedor'], '###.###.###-##');
        }
       
        $header_nota_array = [
            'emissao' => parserData($dados_nota['emissao']),
            'numero' => $dados_nota['numero'],
            'data_entrada' => parserData($dados_nota['dataentrada']),
            'natureza_operacao' => $dados_nota['naturezaoperacao'],
            'quantidade_volume' => parserQtd($dados_nota['quantidadevolume']),
            'peso_volume' => parserQtd($dados_nota['pesovolume']).' KG',
            'valor_total' => parserValor($dados_nota['valortotal']),
            'frete' => parserValor($dados_nota['frete']),
            'seguro' => parserValor($dados_nota['seguro']),
            'desconto' => parserValor($dados_nota['desconto']),
            'nome_cliente' => $dados_nota['nomecliente'],
            'cnpj_cliente' => mask($dados_nota['cnpjcliente'], '##.###.###/####-##'),
            'nome_fornecedor' => $dados_nota['nomefornecedor'],
            'cnpj_fornecedor' => $cnpj_fornecedor
        ];

        $quantidadeTotal = 0;

        foreach($PecasConferenciaObj as $nota){
            $quantidadeTotal += $nota->quantidade_item;

            $itens_array[] = [
                'codigo' => $nota->codigo_produto,
                'descricao' => $nota->descricao_produto,
                'quantidade' => parserQtd($nota->quantidade_item)
            ];
        }

        return view('programs.controle_geral_expedicao.modal.exibir_nota')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array, 'quantidade_total' => $quantidadeTotal]);

    }
}
