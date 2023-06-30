<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\FaturamentoNasajon;

class FaturamentoFeiraController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FaturamentoFeira") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FaturamentoFeira');

        return view('programs.faturamento_feira.index');
    }

    public function filtro(Request $request){
        $fields = $request->only(['grupo_filtro']);

        $notasNasajonObj = FaturamentoNasajon::select();
        $notasNasajonObj->with(['detalhesDeCondicoesPagamentos']);
        if(!empty($fields['grupo_filtro'])){
            $notasNasajonObj->with(['itens_nota.especificacaos.produtoGrupo' => function($query) use($fields){
                $query->where('descricao', 'ilike', '%'.$fields['grupo_filtro'].'%');
            }]);
        }
        $notasNasajonObj->whereIn('Cfop', ['5104', '6104']);
        $notasNasajonObj = $notasNasajonObj->get();

        $dados = [];
        $total = [
            'dinheiro' => 0,
            'cartao_debito' => 0,
            'cartao_credito' => 0,
            'mercado_pago' => 0,
            'pix' => 0,
            'total' => 0,
        ];

        foreach($notasNasajonObj as $value){
            foreach($value->detalhesDeCondicoesPagamentos as $condicao){
                if(empty($fields['grupo_filtro'])){
                    if(empty($dados[$value['Data de Emissão']])){
                        $dados[$value['Data de Emissão']] = [
                            'data' => parserData($value['Data de Emissão']),
                            'dinheiro' => 0,
                            'cartao_debito' => 0,
                            'cartao_credito' => 0,
                            'mercado_pago' => 0,
                            'pix' => 0,
                            'total' => 0,
                        ];
                    }
    
                    if($condicao->formapagamento_descricao == 'Cartão Débito'){
                        $index = 'cartao_debito';
                    }else if($condicao->formapagamento_descricao == 'Dinheiro'){
                        $index = 'dinheiro';
                    }else if($condicao->formapagamento_descricao == 'Cartão Crédito'){
                        $index = 'cartao_credito';
                    }else if($condicao->formapagamento_descricao == 'MERCADO PAGO'){
                        $index = 'mercado_pago';
                    }else if($condicao->formapagamento_descricao == 'PIX - FEIRA'){
                        $index = 'pix';
                    }
    
                    $dados[$value['Data de Emissão']][$index] += $condicao->formapagamento_valor; 
                    $dados[$value['Data de Emissão']]['total'] += $condicao->formapagamento_valor; 
                    $total[$index] += $condicao->formapagamento_valor; 
                    $total['total'] += $condicao->formapagamento_valor; 
                }else{
                    foreach($value->itens_nota as $item){
                        if(!empty($item->especificacaos->produtoGrupo)){
                            if(empty($dados[$value['Data de Emissão']])){
                                $dados[$value['Data de Emissão']] = [
                                    'data' => parserData($value['Data de Emissão']),
                                    'dinheiro' => 0,
                                    'cartao_debito' => 0,
                                    'cartao_credito' => 0,
                                    'mercado_pago' => 0,
                                    'pix' => 0,
                                    'total' => 0,
                                ];
                            }
            
                            if($condicao->formapagamento_descricao == 'Cartão Débito'){
                                $index = 'cartao_debito';
                            }else if($condicao->formapagamento_descricao == 'Dinheiro'){
                                $index = 'dinheiro';
                            }else if($condicao->formapagamento_descricao == 'Cartão Crédito'){
                                $index = 'cartao_credito';
                            }else if($condicao->formapagamento_descricao == 'MERCADO PAGO'){
                                $index = 'mercado_pago';
                            }else if($condicao->formapagamento_descricao == 'PIX - FEIRA'){
                                $index = 'pix';
                            }
            
                            $dados[$value['Data de Emissão']][$index] += $item->valortotal; 
                            $dados[$value['Data de Emissão']]['total'] += $item->valortotal; 
                            $total[$index] += $item->valortotal; 
                            $total['total'] += $item->valortotal;   
                        }
                    }
                }
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'dados' => $this->ajusteArrayParaValores($dados),
                'total' => $this->ajusteArrayParaValores($total),
                'filtro_grupo' => $fields['grupo_filtro'],
            ]
        ];
        return response()->json($response);
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                $array[$key] = empty($value)? '': parserValor($value);
                            }else{
                                $array[$key] = empty($value)? '': parserValor($value).'%';
                            }                            
                        }
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    public function modalDetalhesProdutos(Request $request){
        $fields = $request->only(['data', 'filtro_grupo']);
        $dia = empty($fields['data'])? "" : Carbon::createFromFormat('d/m/Y', $fields['data']);

        $notasNasajonObj = FaturamentoNasajon::select();
        if(!empty($fields['filtro_grupo'])){
            $notasNasajonObj->with(['itens_nota.especificacaos.produtoGrupo' => function($query) use($fields){
                $query->where('descricao', 'ilike', '%'.$fields['filtro_grupo'].'%');
            }]);
        }else{
            $notasNasajonObj->with(['itens_nota.especificacaos.produtoGrupo']);
        }        
        $notasNasajonObj->whereIn('Cfop', ['5104', '6104']);
        if(!empty( $dia)){
            $notasNasajonObj->where('Data de Emissão', $dia);
        }else{
            $notasNasajonObj->where('Data de Emissão', '>', '2022-10-01');
        }
        $notasNasajonObj = $notasNasajonObj->get();
        
        $produtos = [];
        $total = [
            'quantidade' => 0,
            'valor' => 0,
        ];

        foreach($notasNasajonObj as $nota){
            foreach($nota->itens_nota as $item){
                if(!empty($item->especificacaos->produtoGrupo)){
                    if(empty($produtos[$item->codigo])){
                        $produtos[$item->codigo] = [
                            'codigo' => $item->codigo,
                            'descricao' => $item->especificacaos->descricao,
                            'grupo' => $item->especificacaos->produtoGrupo->descricao,
                            'linha' => $item->especificacaos->linha,
                            'marca' => $item->especificacaos->marca,
                            'quantidade' => 0,
                            'valor' => 0,
                        ];
                    }
    
                    $produtos[$item->codigo]['quantidade'] += $item->quantidadecomercial;
                    $produtos[$item->codigo]['valor'] += $item->valortotal;
    
                    $total['quantidade'] += $item->quantidadecomercial;
                    $total['valor'] += $item->valortotal;
                }                
            }
        }

        $produtos = $this->ajusteArrayParaValores($produtos);
        $total = $this->ajusteArrayParaValores($total);

        return view('programs.faturamento_feira.modal.produto_detalhes')->with(['produtos' => $produtos, 'total' => $total]);
    }
}
