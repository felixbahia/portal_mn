<?php

namespace App\Http\Controllers;

use App\FornecedorNasajon;
use App\ValorCustoNota;
use App\ValorCustoNotaProduto;
use App\ComprasNasajon;
use App\Preco;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use App\Http\Requests\ValorCustoNotaSalvarRequest;

use Auth;

use Carbon\Carbon;

class ValorCustoNotaProdutoController extends Controller
{

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ValorCustoNotaProduto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ValorCustoNotaProduto');

        return view('programs.valor_custo_nota_produto.index');
    }

    public function filter(Request $request){

        $fields = $request->only('estabelecimento', 'fornecedor', 'data_inicio', 'data_fim', 'pedido');

        $valorCustoNotaQuery = ValorCustoNota::with('estabelecimento_detalhes');

        if(isset($fields['estabelecimento']) &&  !empty($fields['estabelecimento'])){
            $valorCustoNotaQuery->where('estabelecimento', $fields['estabelecimento']);
        }

        if(isset($fields['pedido']) &&  !empty($fields['pedido'])){
            $valorCustoNotaQuery->where('numero_pedido', $fields['pedido']);
        }

        if(isset($fields['fornecedor']) &&  !empty($fields['fornecedor'])){
            
            $fornecedorObj = FornecedorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike', '%' . $fields['fornecedor'] . '%')->get();

            $valorCustoNotaQuery->where( function($query) use($fornecedorObj){
                $query->whereIn('fornecedor_cnpj', $fornecedorObj->pluck('cnpj_cpf'))
                    ->orWhereIn('fornecedor_codigo', $fornecedorObj->pluck('codigo'));
            });
        }

        if(isset($fields['data_inicio']) &&  !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat("d/m/Y",$fields['data_inicio']);
            $valorCustoNotaQuery->where('data_compra', '>=', $data_inicio->format('Y-m-d'));
        }

        if(isset($fields['data_fim']) &&  !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat("d/m/Y",$fields['data_fim']);
            $valorCustoNotaQuery->where('data_compra', '<=', $data_fim->format('Y-m-d'));
        }

        $valorCustoNotaObj = $valorCustoNotaQuery->get();

        $dados = [];

        if(!isset($fornecedorObj)){
            $fornecedorObj = FornecedorNasajon::whereIn('cnpj_cpf', $valorCustoNotaObj->pluck('fornecedor_cnpj'))
                ->orWhereIn('codigo', $valorCustoNotaObj->pluck('fornecedor_codigo'))
                ->get();
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $valorCustoNotaObj->each(function ($nota) use(&$dados, $fornecedorObj, $estabelecimentos){

            $linha = [];

            $linha['id'] = Crypt::encrypt($nota->id);
            $linha['numero_pedido'] = $nota->numero_pedido;
            $linha['estabelecimento'] = $estabelecimentos[intval($nota->estabelecimento_detalhes->codigo)];

            
            if(!empty($nota->fornecedor_codigo)){
                $fornecedor = $fornecedorObj->where('codigo', $nota->fornecedor_codigo)->values();
            }
            else{
                $fornecedor = $fornecedorObj->where('cnpj_cpf', $nota->fornecedor_cnpj)->values();
            }

            $fornecedor_nome = '';
            if(!empty($fornecedor)){
                $fornecedor = reset($fornecedor);
                $fornecedor = reset($fornecedor);
                if(!empty($fornecedor)){
                    $fornecedor_nome = $fornecedor->nome;
                    if(!empty($fornecedor->cnpj_cpf)){
                        $fornecedor_nome .= ' - ' . $fornecedor->cnpj_cpf;
                    }
                }
            }

            $linha['fornecedor'] = $fornecedor_nome;
            $linha['data_compra'] = parserData($nota->data_compra);

            $dados[] = $linha;
        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'errors' => [],
            'response' => $dados    
        ], 200);
    }

    public function novosCustosModal(){
        $estabelecimento = returnEmpresasNasajonView();
        foreach($estabelecimento as $codigo => $texto){
            if(intval($codigo) != 3){
                unset($estabelecimento[$codigo]);
            }
        }
        return view('programs.valor_custo_nota_produto.modal.novo')->with(['estabelecimento'=>$estabelecimento]);
    }

    public function editarCustosModal(Request $request){

        $id = Crypt::decrypt($request->id);

        $valorCustoNotaObj = ValorCustoNota::with('estabelecimento_detalhes', 'produtos', 'produtos.especificacoes')->find($id);

        $info_pedido = [];

        $info_pedido['id'] = Crypt::encrypt($valorCustoNotaObj->id);
        $info_pedido['estabelecimento'] = returnEmpresasNasajonView()[intval($valorCustoNotaObj->estabelecimento_detalhes->codigo)];
        $info_pedido['estabelecimento_codigo'] = $valorCustoNotaObj->estabelecimento_detalhes->codigo;
        $info_pedido['numero_pedido'] = $valorCustoNotaObj->numero_pedido;

        if(!empty($valorCustoNotaObj->fornecedor_cnpj)){
            $fornecedorObj = FornecedorNasajon::where('cnpj_cpf', $valorCustoNotaObj->fornecedor_cnpj)->first();
        }
        else{
            $fornecedorObj = FornecedorNasajon::where('codigo', $valorCustoNotaObj->fornecedor_codigo)->first();
        }

        $info_pedido['fornecedor'] = $fornecedorObj->nome . ' - ' . $fornecedorObj->cnpj_cpf;

        $itens = [];
        
        $valorCustoNotaObj->produtos->each(function($produto) use (&$itens){
            $item = [];

            $item['codigo_produto'] = $produto->codigo_produto;
            $item['grupo'] = $produto->especificacoes->grupo;
            $item['descricao'] = $produto->especificacoes->descricao;
            $item['custo'] = parserValor($produto->custo_gerencial);

            $itens[] = $item;
        });

        $info_pedido['itens'] = $itens;

        return view('programs.valor_custo_nota_produto.modal.editar')
            ->with($info_pedido);
    }

    public function excluirCustosModal(Request $request){

        $id = Crypt::decrypt($request->id);

        $valorCustoNotaObj = ValorCustoNota::with('estabelecimento_detalhes')->find($id);

        $info_nota = [];

        if(!empty($valorCustoNotaObj->fornecedor_cnpj)){
            $fornecedorObj = FornecedorNasajon::where('cnpj_cpf', $valorCustoNotaObj->fornecedor_cnpj)->first();
        }
        else{
            $fornecedorObj = FornecedorNasajon::where('codigo', $valorCustoNotaObj->fornecedor_codigo)->first();
        }

        $info_nota['numero_pedido'] = $valorCustoNotaObj->numero_pedido;
        $info_nota['estabelecimento'] = $valorCustoNotaObj->estabelecimento_detalhes->codigo . ' - ' . $valorCustoNotaObj->estabelecimento_detalhes->descricao;
        $info_nota['fornecedor'] = $fornecedorObj->nome . ' - ' . $fornecedorObj->cnpj_cpf;
        $info_nota['data_compra'] = parserData($valorCustoNotaObj->data_compra);
        $info_nota['id'] = $request->id;

        return view('programs.valor_custo_nota_produto.modal.deletar')
        ->with($info_nota);

    }

    public function novoCusto(ValorCustoNotaSalvarRequest $request){

        $fields = $request->only('estabelecimento', 'numero_pedido', 'custos');

        $valorCustoNotaObj = new ValorCustoNota;

        $itensNotasCompraNasajonObj = ComprasNasajon::with('fornecedor')
            ->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where('numero_pedido', $fields['numero_pedido'])
            ->where('situacao', '!=', 'Cancelado')
            ->where('quantidade', '>', '0')
            ->first();

        $valorCustoNotaObj->estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
        $valorCustoNotaObj->fornecedor_cnpj = $itensNotasCompraNasajonObj->fornecedor->cnpj_cpf;
        $valorCustoNotaObj->fornecedor_codigo = $itensNotasCompraNasajonObj->fornecedor->codigo;
        $valorCustoNotaObj->numero_pedido = $fields['numero_pedido'];
        $valorCustoNotaObj->proforma = $itensNotasCompraNasajonObj->proforma;
        $valorCustoNotaObj->data_compra = $itensNotasCompraNasajonObj->data_alteracao;
        $valorCustoNotaObj->created_by = Auth::user()->id;
        $valorCustoNotaObj->save();

        foreach($fields['custos'] as $custo){

            $itensNotasCompraNasajonProdutoObj = ComprasNasajon::select('*')
                ->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
                ->where('numero_pedido', $fields['numero_pedido'])
                ->where('cod_produto', $custo['cod_produto'])
                ->where('situacao', '!=', 'Cancelado')
                ->where('quantidade', '>', '0')
                ->first();

            $custo_valor = parserNumber($custo['custo']);

            $custoObj = new ValorCustoNotaProduto;

            $custoObj->valor_custo_notas_id = $valorCustoNotaObj->id;
            $custoObj->codigo_produto = $custo['cod_produto'];
            $custoObj->numero_pedido = $fields['numero_pedido'];
            $custoObj->proforma = $itensNotasCompraNasajonObj->proforma;
            $custoObj->custo = $itensNotasCompraNasajonProdutoObj->preco_compra_unitario;
            $custoObj->quantidade = $itensNotasCompraNasajonProdutoObj->quantidade;
            $custoObj->custo_gerencial = $custo_valor;
            $custoObj->created_by = Auth::id();
            $custoObj->save();

            $PrecoObj = Preco::find($custo['cod_produto']);
            $PrecoObj->ultima_compra_real = $itensNotasCompraNasajonObj->data_alteracao;
            $PrecoObj->compra_real = $custo_valor;
            $PrecoObj->updated_by = Auth::id();
            $PrecoObj->save();

        }


        return response()->json(
            ['status' => 'success',
                'message' => 'Os custos foram salvos com sucesso!',
                'errors' => [],
                'response' => []    
            ], 200
        );
    }

    public function editarCusto(ValorCustoNotaSalvarRequest $request){

        $fields = $request->only('id', 'estabelecimento', 'numero_pedido', 'custos');

        $valorCustoNotaObj = ValorCustoNota::with('produtos')->findorFail(Crypt::decrypt($fields['id']));

        $itensNotasCompraNasajonObj = ComprasNasajon::with('produto', 'fornecedor')
            ->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where('numero_pedido', $fields['numero_pedido'])
            ->where('situacao', '!=', 'Cancelado')
            ->where('quantidade', '>', '0')
            ->first();

        $valorCustoNotaObj->estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
        $valorCustoNotaObj->fornecedor_cnpj = $itensNotasCompraNasajonObj->fornecedor->cnpj_cpf;
        $valorCustoNotaObj->fornecedor_codigo = $itensNotasCompraNasajonObj->fornecedor->codigo;
        $valorCustoNotaObj->numero_pedido = $fields['numero_pedido'];
        $valorCustoNotaObj->proforma = $itensNotasCompraNasajonObj->proforma;
        $valorCustoNotaObj->data_compra = $itensNotasCompraNasajonObj->data_alteracao;
        $valorCustoNotaObj->updated_by = Auth::user()->id;
        $valorCustoNotaObj->save();

        $valorCustoNotaObj->produtos->each(function ($produto){
            $produto->deleted_by = Auth::user()->id;
            $produto->save();
            $produto->delete();
        });

        foreach($fields['custos'] as $custo){

            $itensNotasCompraNasajonProdutoObj = ComprasNasajon::select('*')
                ->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
                ->where('numero_pedido', $fields['numero_pedido'])
                ->where('cod_produto', $custo['codigo_produto'])
                ->where('situacao', '!=', 'Cancelado')
                ->where('quantidade', '>', '0')
                ->first();

            $custo_valor = parserNumber($custo['custo']);

            $custoObj = new ValorCustoNotaProduto;

            $custoObj->valor_custo_notas_id = $valorCustoNotaObj->id;
            $custoObj->codigo_produto = $custo['codigo_produto'];
            $custoObj->numero_pedido = $fields['numero_pedido'];
            $custoObj->proforma = $itensNotasCompraNasajonObj->proforma;
            $custoObj->quantidade = $itensNotasCompraNasajonProdutoObj->quantidade;
            $custoObj->custo = $itensNotasCompraNasajonProdutoObj->preco_compra_unitario;
            $custoObj->custo_gerencial = $custo_valor;
            $custoObj->created_by = Auth::id();

            $custoObj->save();

            $PrecoObj = Preco::find($custo['codigo_produto']);
            $PrecoObj->ultima_compra_real = $itensNotasCompraNasajonObj->data_alteracao;
            $PrecoObj->compra_real = $custo_valor;
            $PrecoObj->updated_by = Auth::id();
            $PrecoObj->save();
        }

        return response()->json(
            ['status' => 'success',
                'message' => 'Os custos foram salvos com sucesso!',
                'errors' => [],
                'response' => []    
            ], 200
        );
    }

    public function excluirCusto(Request $request){
        $fields = $request->only('id');

        $valorCustoNotaObj = ValorCustoNota::with('produtos')->findorFail(Crypt::decrypt($fields['id']));

        $valorCustoNotaObj->deleted_by = Auth::user()->id;
        $valorCustoNotaObj->save();

        $valorCustoNotaObj->delete();

        return response()->json(
            ['status' => 'success',
                'message' => 'Os custos foram excluídos com sucesso!',
                'errors' => [],
                'response' => []    
            ], 200
        );
    }

    public function recuperaNota(Request $request){

        $fields = $request->only('estabelecimento', 'numero_pedido');

        if(ValorCustoNota::where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))->where('numero_pedido', $fields['numero_pedido'])->exists()){

            return response()->json([
                'status' => 'error',
                'message' => 'Nota já cadastrada',
                'errors' => [
                    'numero_pedido' => 'Pedido já cadastrado!'
                ],
                'response' => []
            ], 422);

        }

        $ItensNotasCompraNasajonObj = ComprasNasajon::with('produto', 'fornecedor')
            ->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where('numero_pedido', $fields['numero_pedido'])
            ->where('situacao', '!=', 'Cancelado')
            ->where('quantidade', '>', '0')
            ->get();

        if($ItensNotasCompraNasajonObj->isEmpty()){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido de compra não encontrado',
                'errors' => [
                    'numero_pedido' => 'Pedido de compra não encontrado!'
                ],
                'response' => []
            ], 422);
        }
        if($ItensNotasCompraNasajonObj[0]->situacao != 'Liquidado'){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido de compra se contra como "'.$ItensNotasCompraNasajonObj[0]->situacao.'"',
                'errors' => [
                    'numero_pedido' => 'Pedido de compra se contra como "'.$ItensNotasCompraNasajonObj[0]->situacao.'"'
                ],
                'response' => []
            ], 422);
        }

        $fornecedor = $ItensNotasCompraNasajonObj[0]->fornecedor->nome . ' - ' . $ItensNotasCompraNasajonObj[0]->fornecedor->cnpj_cpf;
        $estabelecimento = $ItensNotasCompraNasajonObj[0]->estabelecimento;
        $numero_pedido = $ItensNotasCompraNasajonObj[0]->numero_pedido;

        $itens = [];

        $ItensNotasCompraNasajonObj->each(function ($linha) use (&$itens){
            $item = [];

            $item['codigo'] = $linha->cod_produto;
            $item['grupo'] = $linha->produto->grupo;
            $item['descricao'] = $linha->produto->descricao;

            $itens[] = $item;

        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'errors' => [],
            'response' => [
                'estabelecimento' => $estabelecimento,
                'numero_pedido' => $numero_pedido,
                'fornecedor' => $fornecedor,
                'itens' => $itens
            ]    
        ], 200);
    }
}
