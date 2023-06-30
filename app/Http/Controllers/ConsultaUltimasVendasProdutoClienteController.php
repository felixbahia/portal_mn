<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\ConsultaUltimasVendasProdutoClienteFilterRequest;

use App\Cliente;
use App\PedidoVenda;
use App\Produto;
use App\PedidosVendaNasajon;
use App\ProdutoNasajon;
use App\ClienteNasajon;
use App\NotasNasajon;
use App\ProdutoEspecificacao;

use Illuminate\Support\Facades\Crypt;

use Auth;
use Carbon\Carbon;

class ConsultaUltimasVendasProdutoClienteController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\ConsultaUltimasVendasProdutoCliente") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaUltimasVendasProdutoCliente');

        return view('programs.consulta_ultimas_vendas_produto_cliente.index');
    }

    public function filter(ConsultaUltimasVendasProdutoClienteFilterRequest $request){
        $fields = $request->only('grupo', 'descricao', 'codigo', 'cliente_nome', 'subgrupo', 'marca', 'linha', 'data_inicio', 'data_fim');
        $itens = $this->itensVendaNasajon($fields);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $itens
        ];
        return response()->json($retorno);
    }

    function itensVendaNasajon($fields){
        $produtos = [];

        $clienteQuery = ClienteNasajon::query();
        if(isset($fields['cliente_nome']) && !is_null($fields['cliente_nome'])){
            $clienteQuery->whereRaw('nome || \' - \' || cpf_cnpj LIKE \'%'.$fields['cliente_nome'].'%\'');
        }

        if(isset($fields['codigo_cliente']) && !is_null($fields['codigo_cliente'])){
            $clienteQuery->where('codigo', 'ilike', $fields['codigo_cliente']);
        }

        if((
            isset($fields['cliente_nome']) && !is_null($fields['cliente_nome'])) ||
            (isset($fields['codigo_cliente']) && !is_null($fields['codigo_cliente']))
        ){
            $result_cliente = $clienteQuery->get();
        }

        foreach($result_cliente as $cliente){
            $clientes[] = $cliente->cpf_cnpj; 
        }
        
        if(!empty($fields['grupo']) || !empty($fields['subgrupo']) || !empty($fields['codigo']) || !empty($fields['descricao'])){

            $query_produto = ProdutoEspecificacao::query();
            
            if(isset($fields['codigo'])){
                $query_produto->where('codigo_produto', 'ilike', '%' . $fields['codigo'] . '%');
            }

            if(!empty($fields['grupo'])){
                $query_produto->where('grupo', 'ilike', '%'.$fields['grupo'].'%');
            }

            if(!empty($fields['subgrupo'])){
                $query_produto->where('grupo', 'ilike', '%'.$fields['subgrupo'].'%');
            }

            if(!empty($fields['descricao'])){
                $query_produto->where('descricao', 'ilike', '%'.$fields['descricao'].'%');
            }

            $result_produto = $query_produto->get();

            foreach($result_produto as $produto){
                $produtos[] = $produto->codigo_produto; 
            }
        }

        $query_pedido = NotasNasajon::query();
        $query_pedido->where('operacao_codigo', 'like', 'VENDA%');

        if(isset($clientes) && !is_null($clientes)){
            $query_pedido->whereIn('cliente_documento', $clientes);
        }
        else{
            return [];
        }

        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = $fields['data_inicio'];
            $data_fim = $fields['data_fim'];

            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';
            $query_pedido->whereBetween('emissao', [$data_inicio, $data_fim]);
        }

        if (!empty($produtos)){
            $query_pedido->whereHas('itens_nota', function($query) use ($produtos){
                $query->whereIn('codigo', $produtos);
            });
        }

        $result_pedido = $query_pedido->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $itens = [];
        foreach($result_pedido as $pedido){
            foreach($pedido->itens_nota as $item){
                $preco_unitario = $item->valortotal / $item->quantidadecomercial;
                $itens[] = [
                    'pedido' => $pedido->pedido->numero??'',
                    'pedido_id' => $pedido->pedido->id??'',
                    'cod_estabelecimento' => $pedido->estabelecimento_codigo,
                    'estabelecimento' => $estabelecimentos[intval($pedido->estabelecimento_codigo)],
                    'data' => parserData($pedido->emissao),
                    'codigo' => $item->codigo,
                    'descricao' => $item->especificacao,
                    'quantidade' => parserQtd($item->quantidadecomercial),
                    'preco_unitario' => parserQtd($preco_unitario),
                    'preco_total' => parserQtd($item->valortotal),
                    'nota' => $pedido->numero,
                    'id_nota' => $pedido->id,
                    'documento' => $pedido->numero,
                    'origem' => 'nasajon',
                    'pedido_origem' => 'nasajon'
                ];

            }
        }
        return $itens;
    }

    function dialog(Request $request){
        $fields = $request->only('grupo', 'descricao', 'codigo', 'cliente_nome', 'subgrupo', 'marca', 'linha', 'data_inicio', 'data_fim', 'hash_cliente');

        if(isset($fields['hash_cliente']) && !is_null($fields['hash_cliente'])){
            $fields['codigo_cliente'] = Crypt::decrypt($fields['hash_cliente']);
        }

        $itens = $this->itensVendaNasajon($fields);

        return view('programs.consulta_ultimas_vendas_produto_cliente.dialog')->with(['itens' => $itens]);
    }
}
