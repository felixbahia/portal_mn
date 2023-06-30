<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\ComprasNasajon;
use App\Http\Requests\RecebimentosComprasRequest;
use App\ProdutoEspecificacao;
use App\NotasEntradasNasajon;
use App\PedidoComprasAssociacaoNotaNasajon;
use Carbon\Carbon;

class RecebimentosComprasController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\RecebimentosComprasController") === false){
            return abort(403);
        }
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);
        $request->session()->flash('model', 'App\RecebimentosComprasController');
    	return view("programs.recebimentos_compras.index")->with('estabelecimentos',$estabelecimentos);
    }
    
    public function buscarNota(Request $request){
        $id_nota = $request->only('id_nota');
        $codido_produto = $request->only('codido_produto');

        try{
            $id = decrypt($id_nota['id_nota']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        $estabelecimentos = returnEmpresasNasajonView();
        $pedidos = PedidoComprasAssociacaoNotaNasajon::where('id_pedido',$id)->select('id_nota')->get()->pluck('id_nota');
        $notas = NotasEntradasNasajon::whereIn('Identificador Documento',$pedidos)
        ->whereHas('itens_nota', function($query) use ($codido_produto){
            $query->where('Item - Código',$codido_produto['codido_produto']);
        })
        ->get();
        $retorno = [];

        foreach($notas as $nota){
            $cfop = [];
            foreach($nota->itens_nota as $itens){
                if(!in_array($itens['Item - CFOP'],$cfop)){
                    $cfop = [$itens['Item - CFOP']];
                }
            }
            $cfop = implode(',',$cfop);
            $retorno[] = [
                'estabelecimento' => $estabelecimentos[(integer)$nota['Estabelecimento']],
                'numero' => $nota['Número do Documento'],
                'entrega' => (isset($nota['Data de Entrada'])) ? parserData($nota['Data de Entrada']) : '',
                'operacao' => $nota['Descrição da Operação'],
                'natureza' => $cfop,
                'codido_produto' => $codido_produto['codido_produto'],
                'fornecedor' => $nota['Nome do Fornecedor'],
                'identificador_documento' => $nota['Identificador Documento'],
            ];
        }

        return view('programs.recebimentos_compras.modal.lista_dialog')->with(['retorno' => $retorno]);
    }

    public function filter(RecebimentosComprasRequest $request){
        set_time_limit(300);
        $inputs = $request->only('grupo', 'status', 'marca','data_inicio_entrega','data_fim_entrega','data_inicio_previsao','data_fim_previsao', 'estabelecimento','industrializacao');
        $compras = ComprasNasajon::select()
        ->where('situacao','<>','Cancelado');
        if(!empty($inputs['data_inicio_entrega']) && !empty($inputs['data_fim_entrega'])){
            $datainicio_entrega = Carbon::createFromFormat("d/m/Y", $inputs['data_inicio_entrega'])->format('Y-m-d');
            $datafim_entrega = Carbon::createFromFormat("d/m/Y", $inputs['data_fim_entrega'])->format('Y-m-d');
            $compras->whereBetween('data_entrega',[$datainicio_entrega,$datafim_entrega]);
        }
        if(!empty($inputs['data_inicio_previsao']) && !empty($inputs['data_fim_previsao'])){
            $datainicio_previsao = Carbon::createFromFormat("d/m/Y", $inputs['data_inicio_previsao'])->format('Y-m-d');
            $datafim_previsao = Carbon::createFromFormat("d/m/Y", $inputs['data_fim_previsao'])->format('Y-m-d');
            $compras->whereBetween('previsao_entrega',[$datainicio_previsao,$datafim_previsao]);
        }
        if(!empty($inputs['grupo'])){
            $produto = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->where('produto_grupos.descricao',$inputs['grupo'])->select('codigo_produto')->get()->toArray();
            $compras->whereIn('cod_produto', $produto);
        }
        if(!empty($inputs['marca'])){
            $produto = ProdutoEspecificacao::where('marca',$inputs['marca'])->select('codigo_produto')->get()->toArray();
            $compras->whereIn('cod_produto', $produto);
        }
        if(!empty($inputs['status']) || $inputs['status'] === '0'){
            if($inputs['status'] === 'saldo_aberto'){
                $compras->whereRaw('quantidade_restante > 0');
            }else if($inputs['status'] === 'saldo_encerrado'){
                $compras->whereRaw('quantidade_restante <= 0 and quantidade > 0');
            }
        }
        if($inputs['industrializacao'] == 'sem_industrializacao'){
            $compras->whereNotIn('cfop',['1101','1902','2101','2902']);
        }
        if($inputs['industrializacao'] == 'somente_industrializacao'){
            $compras->whereIn('cfop',['1101','1902','2101','2902']);
        }
        if(!empty($inputs['estabelecimento'])){
            $compras->where('estabelecimento', str_pad($inputs['estabelecimento'],2,'0', STR_PAD_LEFT));
        }

        $querys = $compras->get();
        $produto = ProdutoEspecificacao::whereIn('codigo_produto',$querys->pluck('cod_produto'))->get();
        $total = [
            'preco_compra' => 0,
            'quantidade' => 0,
            'quantidade_recebida' => 0,
            'saldo' => 0,
        ];

        $retorno = [];
        foreach($querys as $query){
            $recebido = $query->quantidade - $query->quantidade_restante;
            $item = $produto->where('codigo_produto',$query->cod_produto)->first();
            $retorno[] = [
                'marca' => (isset($item->marca)) ? $item->marca : null,
                'grupo' => (isset($item->grupo)) ? $item->grupo : null,
                'pedido' => $query->numero_pedido,
                'emissao' => parserData($query->data_compra),
                'previsao_entreda' => parserData($query->previsao_entrega),
                'data_entrega' => (!empty($query->data_entrega)) ? parserData($query->data_entrega) : '',
                'situacao' => $query->situacao,
                'codigo_produto' => $query->cod_produto,
                'descricao' => (isset($item->descricao)) ? $item->descricao : null,
                'id_nota' => encrypt($query->id_nota),
                'estabelecimento' => $query->estabelecimento,
                'preco_compra' => ($query->preco_compra > 0) ? parserValor($query->preco_compra) : '',
                'quantidade' => ($query->quantidade > 0) ? parserQtd($query->quantidade) : '',
                'quantidade_recebida' => ($recebido > 0) ? parserQtd($recebido) : '',
                'saldo' => ($query->quantidade_restante > 0) ? parserValor( $query->quantidade_restante) : '',
                'filter' => encrypt([
                    'estabelecimento' => (isset($inputs['estabelecimento'])) ? str_pad($inputs['estabelecimento'],2,'0', STR_PAD_LEFT) : null,
                    'marca' => (isset($item->marca)) ? $item->marca : null,
                    'grupo' => (isset($item->grupo)) ? $item->grupo : null,
                    'codigo' => $query->cod_produto,
                ]),
            ];
            $total['preco_compra'] += $query->preco_compra;
            $total['quantidade'] += $query->quantidade;
            $total['quantidade_recebida'] +=  $recebido;
            $total['saldo'] += ($query->quantidade_restante > 0) ? $query->quantidade_restante : 0;
        }

        $total['preco_compra'] = ($total['preco_compra'] > 0) ? parserValor($total['preco_compra']) : '';
        $total['quantidade'] = ($total['quantidade'] > 0) ? parserValor($total['quantidade']) : '';
        $total['quantidade_recebida'] = ($total['quantidade_recebida'] > 0) ? parserValor($total['quantidade_recebida']) : '';
        $total['saldo'] = ($total['saldo'] > 0) ? parserValor($total['saldo']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['retorno' => $retorno, 'total' => $total],
        ]);
    }

}
