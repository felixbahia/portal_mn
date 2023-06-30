<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\ProdutoEspecificacao;
use App\ProdutoNasajon;
use App\CepEstado;
use App\AliquotaPreco;
use App\CepEndereco;
use App\EstabelecimentoCidadeFob;
use App\MargemPrazo;
use App\ParametrosAprovacao;

use App\Http\Requests\ListaDePrecosRequest;

use App\Http\Controllers\ListagemDePrecosController;

class ProdutoProjetoController extends Controller
{
    public function retornaDescricao(Request $request){
        $fields = $request->only('produto_codigo');

        if(strcasecmp($fields['produto_codigo'], '') == ''){
            $retorno = [
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => ['nome' => 'Produto Novo']
            ];
            return response()->json($retorno);
        }

        $produtoQuery = ProdutoEspecificacao::where('descricao', '!=', 'DESATIVADO');
        $produtoQuery->where('codigo_produto', $fields['produto_codigo']);

        $produto_nasajon = ProdutoNasajon::select()->where('codigo', $fields['produto_codigo'])->first();
        $ncm = empty($produto_nasajon->ncm)? '': $produto_nasajon->ncm;
        $peso = empty($produto_nasajon->pesobruto)? '': $produto_nasajon->pesobruto;

        $produto = $produtoQuery->first();
        $retorno = [];

        if (!empty($produto)){
            $retorno = [
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => [
                    'nome' => utf8_decode(utf8_encode($produto->descricao)),
                    'codigo' => utf8_decode(utf8_encode($produto->codigo_produto)),
                    'ncm' => $ncm,
                    'peso' => parserQtd($peso)
                ]
            ];
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Código de Produto não existe.',
                'error' => ['produto_codigo' => 'Código de Produto não existe.'],
                'response' => []
            ],422);
        }

        return response()->json($retorno);
    }

    public function filter(Request $request){
        $busca = $request->only('marca', 'linha','grupo','codigo', 'nome');

        $produtoQuery = ProdutoEspecificacao::with('produtoGrupo')->where('descricao', '!=', 'DESATIVADO')->has('produtoProjeto');

        if (!empty($busca['marca'])){
            $produtoQuery->where('marca', 'ilike', "%" . $busca['marca'] . "%");
        }
        
        if (!empty($busca['linha'])){
            $produtoQuery->where('linha', 'ilike', "%" . $busca['linha'] . "%");

        }
        
        if (!empty($busca['grupo'])){
            $produtoQuery->where('grupo', 'ilike', "%" . $busca['grupo'] . "%");
        }
        
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', "%" . $busca['codigo'] . "%");
        }
        
        if (!empty($busca['nome'])){
            $produtoQuery->where('descricao', 'ilike', "%" . $busca['nome'] . "%");
        }

        if (empty($busca['marca']) && empty($busca['linha']) && empty($busca['grupo']) && empty($busca['codigo']) && empty($busca['nome'])) {
            
            $error_array = [
                'linha' => 'Especifique um critério para a busca',
                'grupo' => 'Especifique um critério para a busca',
                'codigo' => 'Especifique um critério para a busca',
                'nome' => 'Especifique um critério para a busca',
                'marca' => 'Especifique um critério para a busca',
            ];

            return response()->json(['status' => 'error', 'message' => '', 'error' => $error_array, 'response' => []], 422);

        }

        $produtos = $produtoQuery->get();

        $result = [];

        foreach ($produtos as $key => $value) {
            if (is_null($value->produtoGrupo)){
 
                $result[$key] = [
                    'marca' => utf8_encode($value->produto_projeto),
                    'codigo' => utf8_encode($value->codigo_produto??$value->codigo),
                    'linha' => utf8_encode($value->linha),
                    'grupo' => utf8_encode($value->grupo),
                    'nome' => utf8_decode(utf8_encode($value->descricao??$value->especificacao)),
                ];
    
            }

        }

        return response()->json($result);
    }
}