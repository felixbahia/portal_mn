<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;

use App\RemessaConsignacao;
use App\ProdutoEspecificacao;
use App\ClienteNasajon;
use App\TransportadorNasajon;
use App\PedidoPortal;
use App\PedidoItemPortal;

use App\Imports\RemessaConsignacaoImport;

use App\Http\Controllers\ProdutoController;

use App\Http\Requests\RemessaConsignacaoSalvarRequest;

class RemessaConsignacaoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
		if(Auth::user()->hasPermissionTo("programas App\RemessaConsignacao") === false){
			return abort(403);
		}
		$request->session()->flash('model', 'App\RemessaConsignacao');
        $tipo_frete = [
            '' => 'Selecione',
            'P' => "PAGO - CIF",
            'A' => "A PAGAR - FOB",
            'C' => "COBRADO - FOB",
            'T' => "TERCEIRO - FOB",
            'S' => "SEM FRETE - FOB"
        ];

		return view('programs.remessa_consignacao.index')->with(['tipo_frete' => $tipo_frete]);
    }

	public function enviarArquivo(Request $request){
        ini_set('post_max_size', '20M');
        ini_set('upload_max_filesize', '20M');
        ini_set('memory_limit', '200M');
		$arquivo = $request->file('arquivo');
		$id = $request->only(['pedido_id'])['pedido_id'];

		$RemessaConsignacaoObj = new RemessaConsignacao();
		$nome_arquivo = $arquivo->getClientOriginalName();
		$arquivo->storeAs($RemessaConsignacaoObj->caminho, $nome_arquivo);
		$RemessaConsignacaoObj->arquivo = $nome_arquivo;
		$RemessaConsignacaoObj->created_by = Auth::id();
		$RemessaConsignacaoObj->save();

		$retorno = Excel::toCollection(new RemessaConsignacaoImport, $RemessaConsignacaoObj->caminho.$nome_arquivo);
		$retorno = $this->tratarArquivoPedido($retorno, $id);
		
		return response()->json([
			'status' => 'success',
			'message' => '',
			'error' => [],
			'response' => ['id' => encrypt($RemessaConsignacaoObj->id), 'produtos' => $retorno]
		], 200);
	}

	private function tratarArquivoPedido($arquivo, $pedido_id){
        ini_set('post_max_size', '20M');
        ini_set('upload_max_filesize', '20M');
        ini_set('memory_limit', '200M');
		$retorno = [];
		$produtos = [];
		$arquivo->each(function($esquema) use(&$retorno, &$produtos){
			$esquema->each(function($linha) use(&$retorno, &$produtos){
				$produto_codigo = $linha[1];
				$quantidade = $linha[3];
				if(is_string($quantidade)){
					$quantidade = strtolower($quantidade);
				}

				if(!empty($produto_codigo) && !empty($quantidade) && $quantidade != 'quantidade'){
					$produtos[] = $produto_codigo;
					$retorno[$produto_codigo] = [
						'produto_codigo' => $produto_codigo,
						'quantidade' => $quantidade
					];
					unset($dados_produto);
				}
			});
		});
		$dados_produtos = $this->getProdutosDados($produtos, $pedido_id);
		$retorno = $this->tratarProdutos($retorno, $dados_produtos);
		unset($dados_produtos);
		$retorno = array_values($retorno);
		return $retorno;
	}

	private function getProdutosDados($produto_codigos, $pedido_id){
		$retorno_padrao = [
			'descricao' => 'Produto não Encontrado',
			'preco' => 0
		];
		if(empty($pedido_id)){
			$produtos = ProdutoEspecificacao::where('ativo', true)->whereIn('codigo_produto', $produto_codigos)->get();
			$retorno = [];
			if(!empty($produtos)){
				$produtos->each(function($produto) use(&$retorno, $retorno_padrao){
					$temp_retorno = $retorno_padrao;
					$temp_retorno['descricao'] = $produto->descricao;
					$retorno[$produto->codigo_produto] = $temp_retorno;
				});
			}
			unset($produtos);
			return $retorno;
		}else{

			$ProdutoControllerObj = new ProdutoController();
			$requestProduto = new Request([
				'codprd' => $produto_codigos,
				'pedido' => $pedido_id
			]);
			$preco_estoque = $ProdutoControllerObj->retornaInformacoesPreco($requestProduto);
			unset($requestProduto);
			unset($ProdutoControllerObj);
			return json_decode($preco_estoque->getContent(), true);
		}
	}

	private function tratarProdutos($retorno, $dados_produtos){
		$retorno_temp = [];
		foreach($retorno as $produto => $dados_produto){
			$preco = $dados_produtos[$produto]['preco'];
			$retorno_temp[$produto] = [
				'produto_codigo' => $dados_produto['produto_codigo'],
				'quantidade' => parserQtd($dados_produto['quantidade']),
				'descricao' => $dados_produtos[$produto]['descricao'],
				'preco' => parserValor($preco),
				'preco_total' => parserValor($preco * $dados_produto['quantidade'])
			];
		}
		unset($retorno);
		unset($dados_produtos);
		return $retorno_temp;
	}

	public function salvarPedido(RemessaConsignacaoSalvarRequest $request){
		$campos = $request->only(['id', 'pedido_id', 'cliente_nome', 'transportadora_nome', 'transportadora_tipo_frete', 'valor_frete', 'item']);
		$PedidoPortalObj = null;
		$ClienteNasajonObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($campos['cliente_nome']))->where('bloqueado', 'false')->first();
		$TransportadorNasajonObj = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ILIKE', trim($campos['transportadora_nome']))->where('bloqueado', 'false')->first();
		if(empty($campos['pedido_id'])){
			$PedidoPortalObj = new PedidoPortal();
			$PedidoPortalObj->data_pedido = Carbon::today()->format('Y-m-d');
			$PedidoPortalObj->estabelecimento = '5';
			$PedidoPortalObj->migracao = false;
			$PedidoPortalObj->cartao = false;
			$PedidoPortalObj->presencial = false;
			$PedidoPortalObj->nasajon = true;
			$PedidoPortalObj->usuario = 1;
			$PedidoPortalObj->status_pedido = 1;
		}else{
			$PedidoPortalObj = PedidoPortal::find($campos['pedido_id']);
		}

		$PedidoPortalObj->cod_cliente = $ClienteNasajonObj->codigo;
		$PedidoPortalObj->transportadora = $TransportadorNasajonObj->codigo;
		$PedidoPortalObj->tipo_frete = $campos['transportadora_tipo_frete'];
		$PedidoPortalObj->valor_frete = $campos['valor_frete'];

		$PedidoPortalObj->save();

		$RemessaConsignacaoObj = RemessaConsignacao::find(decrypt($campos['id']));
		$RemessaConsignacaoObj->pedido_id = $PedidoPortalObj->id;
		$RemessaConsignacaoObj->save();

		$itens = $this->tratarItensPedido($campos['item'], $PedidoPortalObj->id);
		return response()->json([
			'status' => 'success',
			'message' => '',
			'error' => [],
			'response' => ['id' => encrypt($PedidoPortalObj->id), 'produtos' => $itens]
		], 200);
	}

	private function tratarItensPedido($itens, $pedido){
		$produtos = [];
		foreach($itens as $key => $item){
			$produto = $item['produto'];
			$quantidade = parserNumber($item['quantidade']);
			$dados = $this->getProdutosDados($produto, $pedido);
			$preco = parserNumber($dados['preco_unitario']);
			$produtos[$key] = [
				'produto' => $produto,
				'preco' => $dados['preco_unitario'],
				'preco_total' => parserValor($preco * $quantidade)
			];
			unset($preco);
		}
		unset($itens);
		return $produtos;
	}

}
