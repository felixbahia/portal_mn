<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ProdutoEspecificacao;
use App\LancamentoProjeto;

use App\Http\Requests\ListaDePrecosRequest;
use App\Http\Controllers\ListagemDePrecosController;

class ProdutoTecidosController extends Controller
{
    public function modalBuscarTecido(){
    	return view('programs.produto.modal.buscar_tecido');
    }

    public function autoComplete(Request $request){
        $descricao = $request->only('term');

        $return = [];

        $produtoQuery = ProdutoEspecificacao::with(['preco','estoque' => function($query){
                $query->whereIn('estabelecimento', ['03', '04', '06']);
                $query->orderBy('estabelecimento');
            }])->
            where('descricao', '!=', 'DESATIVADO')->
            distinct('descricao')->
            where('ativo', 'true')->
            where('descricao', 'ilike', "%" . $descricao['term'] . "%")->
            orderBy('grupo', 'subgrupo', 'descricao', 'linha', 'marca')->
            limit("15");

        $result_produto = $produtoQuery->get()->toArray();

        foreach ($result_produto as $value){
            $value = (array) $value;

            if(!empty($value['estoque']) && count($value['estoque']) > 0){
                $estoque = parserValor($value['estoque'][0]['estoque']);
            }else{
                $estoque = "0,00";
            }

            $return[] = [
                'label' => trim(utf8_decode(utf8_encode($value['descricao']))),
                'nome' => trim(utf8_decode(utf8_encode($value['descricao']))),
                'value' => trim(utf8_encode($value['codigo_produto'])),
                'preco' => empty($value['preco'])?"0,0":parserValor($value['preco']['preco_real']),
                'estoque' => $estoque
            ];
        }
        return response()->json($return);
    }

    public function retornaTecidoDescricao(Request $request){
        $field = $request->only('tecido_codigo', 'id_projeto');

        try{    
            $id_projeto = decrypt($field['id_projeto']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);

        switch(strtoupper($lancamentoProjetoObj->estabelecimento)){
            case 5:
                $origem = "SP";
                $estabelecimento = 5;
                break;
            default:
                $origem = "TO";
                $estabelecimento = 4;
                break;
        }

        $tipo_cliente  = (
            $lancamentoProjetoObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($lancamentoProjetoObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($lancamentoProjetoObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';

        $estabelecimentos = $this->estabelecimentos();

        $return = [];

        $produtoQuery = ProdutoEspecificacao::with(['preco','estoque'=> function($query){
            $query->whereIn('estabelecimento', ['03', '04', '06']);
            $query->orderBy('estabelecimento');
        }])->
        where('ativo', 'true')->
        where('codigo_produto', 'ilike', strtoupper($field['tecido_codigo']));

        $produto = $produtoQuery->first();

        if(empty($produto)){
            $return = [
                'status' => 'error',
                'message' => '',
                'error' => ["tecido_codigo" => "Tecido não encontrado"],
                'response' => []
            ];
            return response()->json($return, 422);
        }

        $precoObj = new ListagemDePrecosController;
        $coluna = '';
        $raiz_cnpj = substr($lancamentoProjetoObj->cliente->cpf_cnpj,0,10);

        if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
            if ($lancamentoProjetoObj->condicoes_pagamento_web->media < 15){
                $coluna = 'prazo_vista';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 15 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 30){
                $coluna = 'prazo_15';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 30 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 45){
                $coluna = 'prazo_30';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 45 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 60){
                $coluna = 'prazo_45';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 60 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 75){
                $coluna = 'prazo_60';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 75 && $lancamentoProjetoObj->condicoes_pagamento_web->media < 90){
                $coluna = 'prazo_75';
            }
            else if($lancamentoProjetoObj->condicoes_pagamento_web->media >= 90){
                $coluna = 'prazo_90';
            }
        }

        unset($arr);

        $arr['origem'] = $origem;
        $arr['produto'] = $produto->codigo_produto;
        $arr['moeda'] = 'real';
        $arr['prazo_medio'] = (!in_array($raiz_cnpj,$this->cnpjIntercompany())) ? $lancamentoProjetoObj->condicoes_pagamento_web->media : '';
        $arr['frete'] = strtolower($lancamentoProjetoObj->tipo_frete);
        $arr['estado'] = $lancamentoProjetoObj->cliente->uf;
        $arr['tipo_cliente'] = $tipo_cliente;
        $arr['coluna'] = $coluna;
        $arr['promocao'] = true;
        $arr['estabelecimento'] = $estabelecimento;
        $arr['cliente'] = $lancamentoProjetoObj->cliente->cpf_cnpj;
        $arr['codigo_vendedor'] = $lancamentoProjetoObj->users_codigo_representante;

        $items = new ListaDePrecosRequest($arr);

        $precos = $precoObj->filter($items, false, false, true, true, false, false, false);

        $estoques = [];
        $index_estabelecimento = [
            0 => 3,
            1 => 4,
            2 => 5,
            3 => 6
        ];
        $index = 0;
        
        for ($i = 0; $i <= 3; $i++) {
            if(!empty($produto->estoque[$index])){
                if($index_estabelecimento[$i] == intval($produto->estoque[$index]->estabelecimento)){
                    $estoques[$index_estabelecimento[$i]] = [
                        'codigo_estabelecimento' => $index_estabelecimento[$i],
                        'estabelecimento' => $estabelecimentos[$index_estabelecimento[$i]],
                        'estoque' => empty($produto->estoque[$index]->estoque)? '' : parserValor($produto->estoque[$index]->estoque),
                        'compras' => empty($produto->estoque[$index]->compras)? '' : parserValor($produto->estoque[$index]->compras)
                    ];
                    $index++;
                }else{
                    $estoques[$index_estabelecimento[$i]] = [
                        'codigo_estabelecimento' => $index_estabelecimento[$i],
                        'estabelecimento' => $estabelecimentos[$index_estabelecimento[$i]],
                        'estoque' => '',
                        'compras' => ''
                    ];
                }
            }else{
                $estoques[$index_estabelecimento[$i]] = [
                    'codigo_estabelecimento' => $index_estabelecimento[$i],
                    'estabelecimento' => $estabelecimentos[$index_estabelecimento[$i]],
                    'estoque' => '',
                    'compras' => ''
                ];	
            }    
        }

        if(!empty($produto)){
            $return = [
                'codigo' => $produto->codigo_produto,
                'nome' => utf8_decode(trim(utf8_encode($produto->descricao))),
                'estoque' => $estoques,
                'preco' => empty($precos[0]['coluna_a'])? empty($produto->preco->preco_real)? '0,00' : parserValor($produto->preco->preco_real) : $precos[0]['coluna_a'],
            ];
        }else{
            $return = [
                'codigo' => '',
                'nome' => '',
                'preco' => '',
                'estoque' => ''
            ]; 
        }
        return response()->json($return);
    }

    public function calculoTecidos(Request $request){
        $fields = $request->only('consumo', 'preco_unitario', 'quantidade');

        $quantidade = empty($fields["quantidade"])?0.0:$this->formtFloat($fields['quantidade']);
        $consumo = empty($fields["consumo"])?0.0:$this->formtFloat($fields["consumo"]);
        $consumo_total = $quantidade * $consumo;
        $preco_unitario = empty($fields["preco_unitario"])?0.0:$this->formtFloat($fields["preco_unitario"]);
        $valor_total = $consumo_total * $preco_unitario;

        $return = [
            'valor_total' => parserValor($valor_total),
            'consumo_total' => parserValor($consumo_total)
        ];

        return response()->json($return);
    }

    function formtFloat($value){
        $value = str_replace(",", ".", str_replace(".", "", $value));
        $value = floatval($value);
        
        return $value;
    }

    public function validarDescricaoTecido(Request $request){
        $field = $request->only('tecido_descricao');

        $return = [];

        $produtoQuery = ProdutoEspecificacao::select();
        $produtoQuery->where('ativo', 'true');
        $produtoQuery->where('descricao', $field['tecido_descricao']);

        $result_produto = $produtoQuery->first()->toArray();
        
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ''
        ];

        return response()->json($retorno);
    }

    public function retornarEstoque(Request $request){
        $fields = $request->only('codigo_produto');
        $query_produto = ProdutoEspecificacao::with(['produtoNasajon', 'estoque'=> function($query){
            $query->whereIn('estabelecimento', ['03', '04', '06']);
            $query->orderBy('estabelecimento');
        }])->
        where('ativo', 'true')->
        where('codigo_produto', $fields['codigo_produto']);
    }

    public function estabelecimentos(){
        $estabelecimentos[''] = 'Selecione';
        $estabelecimentos = array_merge($estabelecimentos, returnEmpresasNasajonView());

        return $estabelecimentos;
    }
    
    private function cnpjIntercompany(){
        $raiz[] = '05.075.884';
        $raiz[] = '06.311.274';
        return $raiz;
    }
}