<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Auth;

use Illuminate\Support\Facades\DB;

use App\Movimentacao20;
use App\MovimentacaoIcmsArmazem;
use App\MovimentacaoValorArmazem;
use App\NotasEntradasNasajon;
use App\NotasNasajon;

class IcmsEstoqueArmazemController extends Controller
{
    public function index(Request $request){
        ini_set('memory_limit','2024M');
        if (Auth::user()->hasPermissionTo("programas App\IcmsEstoqueArmazem") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\IcmsEstoqueArmazem');

        $estabelecimentos[''] = 'Estabelecimento';
        $estabelecimentos = array_merge($estabelecimentos, returnEmpresasNasajonView());
        unset($estabelecimentos[0]);
        unset($estabelecimentos[1]);
        unset($estabelecimentos[2]);
        unset($estabelecimentos[5]);
        unset($estabelecimentos[6]);
        unset($estabelecimentos[7]);
        unset($estabelecimentos[8]);
        unset($estabelecimentos[10]);
        
        return view("programs.icms_estoque_armazem.index")->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtro(Request $request){
        $fields = $request->only('ano', 'estabelecimento');

        $ano = $fields['ano'];

        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);

        $retorno = [
            'ano_codigo' => $ano,
            'entrada' => [
                'descricao' => 'Entrada',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'saida' => [
                'descricao' => 'Saída',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'saldo_atual' => [
                'descricao' => 'Saldo Atual',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'saldo_anterior' => [
                'descricao' => 'Saldo Anterior',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
        ];

        $movimentacaoIcmsArmazemObj = MovimentacaoIcmsArmazem::select(DB::Raw('sum(saldo_anterior) as saldo_anterior, sum(entrada) as entrada, sum(saida) as saida, sum(saldo_atual) as saldo_atual, data_movimentacao'));
        $movimentacaoIcmsArmazemObj->whereBetween('data_movimentacao', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $movimentacaoIcmsArmazemObj->groupBy('data_movimentacao');
        if(!empty($fields['estabelecimento'])){
            $movimentacaoIcmsArmazemObj->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }
        $movimentacaoIcmsArmazemObj = $movimentacaoIcmsArmazemObj->get();

        foreach($movimentacaoIcmsArmazemObj as $value){
            $retorno['saldo_anterior']['inteiro_'.$value->data_movimentacao->month] = empty($value->saldo_anterior)? '' : $value->saldo_anterior;
            $retorno['entrada']['inteiro_'.$value->data_movimentacao->month] = empty($value->entrada)? '' : $value->entrada;
            $retorno['saida']['inteiro_'.$value->data_movimentacao->month] = empty($value->saida)? '' : $value->saida;
            $retorno['saldo_atual']['inteiro_'.$value->data_movimentacao->month] = empty($value->saldo_atual)? '' : $value->saldo_atual;

            $retorno['entrada']['inteiro_total'] += empty($value->entrada)? 0 : $value->entrada;
            $retorno['saida']['inteiro_total'] += empty($value->saida)? 0 : $value->saida;
        }

        $retorno['saldo_atual']['inteiro_total'] = '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'dados' => $this->ajusteArrayParaValores($retorno),
                'estabelecimento' => $fields['estabelecimento'],
            ]
        ], 200);
    }

    public function atualizarMovimentacao20(){
        ini_set('memory_limit','2024M');

        $primeiro_dia_do_ano = Carbon::now()->subMonths(2);
        $ultimo_dia_do_ano = Carbon::now();

        $excluir = Movimentacao20::select();
        $excluir->whereBetween("data_movimentacao", [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $excluir =  $excluir->get();

        foreach($excluir as $value){
            $value->delete();
        }

        $notasEntradasNasajonObj = NotasEntradasNasajon::select();
        $notasEntradasNasajonObj->with(['itens']);
        $notasEntradasNasajonObj->whereHas('itens', function($query){
            $query->where('cfop', '2905');
        });
        $notasEntradasNasajonObj->whereBetween("Data de Emissão", [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $notasEntradasNasajonObj->orderBy("Data de Emissão");
        $notasEntradasNasajonObj = $notasEntradasNasajonObj->get();

        foreach($notasEntradasNasajonObj as $value){
            foreach($value->itens as $item){
                $movimentacao20Obj = new Movimentacao20;              
                $movimentacao20Obj->estabelecimento_codigo = $value["Fornecedor"] == '06311274000269'? '03' : '04';
                $movimentacao20Obj->produto_codigo = $item->cod_produto;
                $movimentacao20Obj->data_movimentacao = $value["Data de Emissão"];
                $movimentacao20Obj->quantidade = $item->quantidade;
                $movimentacao20Obj->sinal = "ENTRADA";
                $movimentacao20Obj->origem = "DOCUMENTO FISCAL";
                $movimentacao20Obj->documento_id = $value["Identificador Documento"];
                $movimentacao20Obj->documento_numero = $value["Número do Documento"];
                $movimentacao20Obj->movimento_id = null;
                $movimentacao20Obj->cliente_codigo = $value["Fornecedor"];
                $movimentacao20Obj->item_cfop = $item->cfop;
                $movimentacao20Obj->item_aliquota = $item->aliquota_icms;
                $movimentacao20Obj->item_preco_unitario = $item->valor_unitario;
                $movimentacao20Obj->item_preco_total = $item->valor_total;
                $movimentacao20Obj->item_unidade = $item->unidade;
                $movimentacao20Obj->item_frete = 0;
                $movimentacao20Obj->item_ipi = $item->valor_ipi;
                $movimentacao20Obj->item_desconto = 0;
                $movimentacao20Obj->item_seguro = 0;
                $movimentacao20Obj->slot = "";
                $movimentacao20Obj->efetivado = true;
                $movimentacao20Obj->item_valor_icms = $item->valor_icms;
                $movimentacao20Obj->custo = 0;
                $movimentacao20Obj->save();
            }
                      
        }

        unset($notasEntradasNasajonObj);

        $notasNasajonObj = NotasNasajon::select();
        $notasNasajonObj->with(['itens_nota', 'pedido']);
        $notasNasajonObj->whereHas('itens_nota', function($query){
            $query->whereIn('cfop', ['5923', '6923', '6907']);
        });
        $notasNasajonObj->whereBetween("emissao", [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $notasNasajonObj->orderBy("emissao");
        $notasNasajonObj = $notasNasajonObj->get();

        foreach($notasNasajonObj as $value){
            foreach($value->itens_nota as $item){
                $movimentacao20Obj = new Movimentacao20;
                $movimentacao20Obj->estabelecimento_codigo = empty($value->pedido)? '20' : $value->pedido->estabelecimento_codigo;
                $movimentacao20Obj->produto_codigo = $item->codigo;
                $movimentacao20Obj->data_movimentacao = $value->emissao;
                $movimentacao20Obj->quantidade = $item->quantidadecomercial;
                $movimentacao20Obj->sinal = "SAÍDA";
                $movimentacao20Obj->origem = "DOCUMENTO FISCAL";
                $movimentacao20Obj->documento_id = $value->id;
                $movimentacao20Obj->documento_numero = $value->numero;
                $movimentacao20Obj->movimento_id = null;
                $movimentacao20Obj->cliente_codigo = $value->cliente_documento;
                $movimentacao20Obj->item_cfop = $item->cfop;
                $movimentacao20Obj->item_aliquota = $item->valoraliquotaicms;
                $movimentacao20Obj->item_preco_unitario = $item->valorunitariocomercial;
                $movimentacao20Obj->item_preco_total = $item->valortotal;
                $movimentacao20Obj->item_unidade = $item->unidade;
                $movimentacao20Obj->item_frete = 0;
                $movimentacao20Obj->item_ipi = $item->valoripi;
                $movimentacao20Obj->item_desconto = 0;
                $movimentacao20Obj->item_seguro = 0;
                $movimentacao20Obj->slot = "";
                $movimentacao20Obj->efetivado = true;
                $movimentacao20Obj->item_valor_icms = $item->valoricms;
                $movimentacao20Obj->custo = 0;
                $movimentacao20Obj->save();
            }
                      
        }

        unset($notasNasajonObj);
    }

    public function atualizarMovimentacoaIcms(){
        $movimentacao20 = Movimentacao20::select(DB::Raw('sum(item_valor_icms) as icms, sum(item_preco_total) as item_preco_total, sinal, item_cfop, data_movimentacao, estabelecimento_codigo'))
            ->whereIn('item_cfop', ['2905', '5923', '6923'])
            ->whereIn('estabelecimento_codigo', ['03', '04', '20'])
            ->where('data_movimentacao', '>=', '2020-01-01')
            ->groupBy('sinal', 'item_cfop', 'data_movimentacao', 'estabelecimento_codigo')
            ->get();

        $dados = [];
        foreach($movimentacao20 as $value){
            if(empty($dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo])){
                $dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo] = [
                    'entrada' => 0,
                    'saida' => 0,
                    'estabelecimento' => $value->estabelecimento_codigo,
                ];
            }
            if($value->sinal == 'ENTRADA'){
                $dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo]['entrada'] += $value->icms;              
            }else{
                $dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo]['saida'] += $value->icms;    
            }
        }
        
        foreach($dados as $index => $dado){
            $primeiro_dia_do_mes = Carbon::parse(substr($index, 0, 7).'-01')->setTime(0,0,0)->firstOfMonth();

            $movimentacaoIcmsArmazemObj = MovimentacaoIcmsArmazem::select()
                ->where('data_movimentacao', $primeiro_dia_do_mes)
                ->where('estabelecimento_codigo', $dado['estabelecimento'])
                ->first();

            if(empty($movimentacaoIcmsArmazemObj)){
                $movimentacaoIcmsArmazemObj = new MovimentacaoIcmsArmazem;
                $movimentacaoIcmsArmazemObj->saldo_anterior = 0;
                $movimentacaoIcmsArmazemObj->saldo_atual = 0;
                $movimentacaoIcmsArmazemObj->data_movimentacao = $primeiro_dia_do_mes;
                $movimentacaoIcmsArmazemObj->estabelecimento_codigo = $dado['estabelecimento'];
            }

            $movimentacaoIcmsArmazemObj->entrada = $dado['entrada'];
            $movimentacaoIcmsArmazemObj->saida = $dado['saida'];
            $movimentacaoIcmsArmazemObj->save();
        }
        
        $movimentacaoIcmsArmazemObj = MovimentacaoIcmsArmazem::select()
        ->where('estabelecimento_codigo', '03')
        ->where('data_movimentacao', '>=', '2020-01-01')
        ->orderBy('data_movimentacao', 'asc')->get();

        $saldo_anterior = 0;
        $saldo_atual = 0;
        $mudar = false;
        foreach($movimentacaoIcmsArmazemObj as $value){
            $saldo_anterior = $saldo_atual;

            if($mudar){
                $value->saldo_anterior = $saldo_anterior;
            }else{
                $mudar = true;
                $saldo_atual =  $value->saldo_anterior;
            }            

            $saldo_atual += $value->entrada;
            $saldo_atual -= $value->saida;

            $value->saldo_atual = $saldo_atual;
            $value->save();
        }

        $movimentacaoIcmsArmazemObj = MovimentacaoIcmsArmazem::select()
        ->where('estabelecimento_codigo', '04')
        ->where('data_movimentacao', '>=', '2020-01-01')
        ->orderBy('data_movimentacao', 'asc')->get();

        $saldo_anterior = 0;
        $saldo_atual = 0;
        $mudar = false;
        foreach($movimentacaoIcmsArmazemObj as $value){
            $saldo_anterior = $saldo_atual;

            if($mudar){
                $value->saldo_anterior = $saldo_anterior;
            }else{
                $mudar = true;
                $saldo_atual =  $value->saldo_anterior;
            }            

            $saldo_atual += $value->entrada;
            $saldo_atual -= $value->saida;

            $value->saldo_atual = $saldo_atual;
            $value->save();
        }

        $movimentacaoIcmsArmazemObj = MovimentacaoIcmsArmazem::select()
        ->where('estabelecimento_codigo', '20')
        ->where('data_movimentacao', '>=', '2020-01-01')
        ->orderBy('data_movimentacao', 'asc')->get();

        $saldo_anterior = 0;
        $saldo_atual = 0;
        $mudar = false;
        foreach($movimentacaoIcmsArmazemObj as $value){
            $saldo_anterior = $saldo_atual;

            if($mudar){
                $value->saldo_anterior = $saldo_anterior;
            }else{
                $mudar = true;
                $saldo_atual =  $value->saldo_anterior;
            }            

            $saldo_atual += $value->entrada;
            $saldo_atual -= $value->saida;

            $value->saldo_atual = $saldo_atual;
            $value->save();
        }
    }

    public function atualizarMovimentacoaValor(){
        $movimentacao20 = Movimentacao20::select(DB::Raw('sum(item_valor_icms) as icms, sum(item_preco_total) as item_preco_total, sinal, item_cfop, data_movimentacao, estabelecimento_codigo'))
            ->whereIn('item_cfop', ['2905', '6907'])
            ->whereIn('estabelecimento_codigo', ['03', '04', '20'])
            ->groupBy('sinal', 'item_cfop', 'data_movimentacao', 'estabelecimento_codigo')
            ->get();

        $dados = [];
        foreach($movimentacao20 as $value){
            if(empty($dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo])){
                $dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo] = [
                    'entrada' => 0,
                    'saida' => 0,
                    'estabelecimento' => $value->estabelecimento_codigo,
                ];
            }
            if($value->sinal == 'ENTRADA'){
                $dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo]['entrada'] += $value->item_preco_total;              
            }else{
                $dados[$value->data_movimentacao->format('Y-m').$value->estabelecimento_codigo]['saida'] += $value->item_preco_total;    
            }
        }
        
        foreach($dados as $index => $dado){
            $primeiro_dia_do_mes = Carbon::parse(substr($index, 0, 7).'-01')->setTime(0,0,0)->firstOfMonth();

            $movimentacaoValorArmazemObj = MovimentacaoValorArmazem::select()
                ->where('data_movimentacao', $primeiro_dia_do_mes)
                ->where('estabelecimento_codigo', $dado['estabelecimento'])
                ->first();
            if(empty($movimentacaoValorArmazemObj)){
                $movimentacaoValorArmazemObj = new MovimentacaoValorArmazem;
                $movimentacaoValorArmazemObj->saldo_anterior = 0;
                $movimentacaoValorArmazemObj->saldo_atual = 0;
                $movimentacaoValorArmazemObj->data_movimentacao = $primeiro_dia_do_mes;
                $movimentacaoValorArmazemObj->estabelecimento_codigo = $dado['estabelecimento'];
            }

            $movimentacaoValorArmazemObj->entrada = $dado['entrada'];
            $movimentacaoValorArmazemObj->saida = $dado['saida'];
            $movimentacaoValorArmazemObj->save();
        }
        
        $movimentacaoValorArmazemObj = MovimentacaoValorArmazem::select()
        ->where('estabelecimento_codigo', '03')
        ->orderBy('data_movimentacao', 'asc')->get();

        $saldo_anterior = 0;
        $saldo_atual = 0;
        $mudar = false;
        foreach($movimentacaoValorArmazemObj as $value){
            $saldo_anterior = $saldo_atual;

            if($mudar){
                $value->saldo_anterior = $saldo_anterior;
            }else{
                $mudar = true;
                $saldo_atual =  $value->saldo_anterior;
            }            

            $saldo_atual += $value->entrada;
            $saldo_atual -= $value->saida;

            $value->saldo_atual = $saldo_atual;
            $value->save();
        }

        $movimentacaoValorArmazemObj = MovimentacaoValorArmazem::select()
        ->where('estabelecimento_codigo', '04')
        ->orderBy('data_movimentacao', 'asc')->get();

        $saldo_anterior = 0;
        $saldo_atual = 0;
        $mudar = false;
        foreach($movimentacaoValorArmazemObj as $value){
            $saldo_anterior = $saldo_atual;

            if($mudar){
                $value->saldo_anterior = $saldo_anterior;
            }else{
                $mudar = true;
                $saldo_atual =  $value->saldo_anterior;
            }            

            $saldo_atual += $value->entrada;
            $saldo_atual -= $value->saida;

            $value->saldo_atual = $saldo_atual;
            $value->save();
        }

        $movimentacaoValorArmazemObj = MovimentacaoValorArmazem::select()
        ->where('estabelecimento_codigo', '20')
        ->orderBy('data_movimentacao', 'asc')->get();

        $saldo_anterior = 0;
        $saldo_atual = 0;
        $mudar = false;
        foreach($movimentacaoValorArmazemObj as $value){
            $saldo_anterior = $saldo_atual;

            if($mudar){
                $value->saldo_anterior = $saldo_anterior;
            }else{
                $mudar = true;
                $saldo_atual =  $value->saldo_anterior;
            }            

            $saldo_atual += $value->entrada;
            $saldo_atual -= $value->saida;

            $value->saldo_atual = $saldo_atual;
            $value->save();
        }
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
                                //$array[$key] = empty($value)? '': parserValor($value);
                                if($key == 'diferenca_meta'){
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }else{
                                    $array[$key] = !empty($value)?  parserValor($value) : '';
                                }
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

    public function modalEntrada(Request $request){
        $fields = $request->only('mes', 'ano', 'estabelecimento');

        $ano = $fields['ano'];
        $mes = $fields['mes'];
        if (empty($mes)) {
            $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);
        } else {
            $primeiro_dia_do_ano = Carbon::parse($ano . "-" . $mes . "-01")->setTime(0, 0, 0)->firstOfMonth();
            $ultimo_dia_do_ano = Carbon::parse($ano . "-" . $mes . "-01")->setTime(23, 59, 59)->lastOfMonth();
        }
        
        $movimentacao20Obj = Movimentacao20::select(DB::Raw('sum(item_valor_icms) as icms, item_cfop, documento_id'));
        $movimentacao20Obj->whereIn('item_cfop', ['2905', '5923', '6923']);
        $movimentacao20Obj->where('sinal', 'ilike', 'ENTRADA');
        $movimentacao20Obj->whereBetween('data_movimentacao', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $movimentacao20Obj->with(['notaEntrada']);
        $movimentacao20Obj->groupBy('item_cfop', 'documento_id');
        if(!empty($fields['estabelecimento'])){
            $movimentacao20Obj->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }
        $movimentacao20Obj = $movimentacao20Obj->get();

        $dados = [];
        $total = [
            'valor' => 0,
            'icms' => 0,
        ];
        foreach($movimentacao20Obj as $value){
            $dados[] = [
                'documento' => "<a class='exibir-nota-entrada' href='#' data-id='" . $value->documento_id . "'>" . $value->notaEntrada["Número do Documento"] . "</a>",
                'fornecedor' => $value->notaEntrada["Nome do Fornecedor"].' - '.$value->notaEntrada["CNPJ/CPF do Fornecedor"],
                'data' => parserData($value->notaEntrada["Data de Entrada"]),
                'data_codigo' => $value->notaEntrada["Data de Entrada"],
                'valor' => $value->notaEntrada["Valor do Documento"],
                'icms' => $value->icms,
                'cfop' => $value->item_cfop,
            ];
            $total['valor'] += $value->notaEntrada["Valor do Documento"];
            $total['icms'] += $value->icms;
        }

        $total['valor'] = empty( $total['valor'])? '' : parserValor($total['valor']);
        $total['icms'] = empty( $total['icms'])? '' : parserValor($total['icms']);

        return view("programs.icms_estoque_armazem.modal.entrada")->with(['dados' => $dados, 'total' => $total]);
    }

    public function modalSaida(Request $request){
        $fields = $request->only('mes', 'ano', 'estabelecimento');

        $ano = $fields['ano'];
        $mes = $fields['mes'];
        if (empty($mes)) {
            $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);
        } else {
            $primeiro_dia_do_ano = Carbon::parse($ano . "-" . $mes . "-01")->setTime(0, 0, 0)->firstOfMonth();
            $ultimo_dia_do_ano = Carbon::parse($ano . "-" . $mes . "-01")->setTime(23, 59, 59)->lastOfMonth();
        }
        
        $movimentacao20Obj = Movimentacao20::select(DB::Raw('sum(item_valor_icms) as icms, item_cfop, documento_id, estabelecimento_codigo'));
        $movimentacao20Obj->whereIn('item_cfop', ['2905', '5923', '6923']);
        $movimentacao20Obj->where('sinal', 'ilike', 'SAÍDA');
        $movimentacao20Obj->whereBetween('data_movimentacao', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $movimentacao20Obj->whereNotIn('estabelecimento_codigo', ['05', '06', '07', '08']);
        if(!empty($fields['estabelecimento'])){
            $movimentacao20Obj->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }
        $movimentacao20Obj->with(['notaSaida']);
        $movimentacao20Obj->groupBy('item_cfop', 'documento_id', 'estabelecimento_codigo');
        $movimentacao20Obj = $movimentacao20Obj->get();

        $dados = [];
        $total = [
            'valor' => 0,
            'icms' => 0,
        ];
        foreach($movimentacao20Obj as $value){
            $dados[] = [
                'estabelecimento_codigo' => $value->estabelecimento_codigo,
                'documento' => "<a class='exibir-nota' href='#' data-id='" . $value->documento_id . "'>" . $value->notaSaida->numero . "</a>",
                'fornecedor' => $value->notaSaida->cliente_nome.' - '.$value->notaSaida->cliente_documento,
                'data' => parserData($value->notaSaida->datasaida),
                'data_codigo' => $value->notaSaida->datasaida,
                'valor' => $value->notaSaida->valor,
                'icms' => $value->icms,
                'cfop' => $value->item_cfop,
            ];
            $total['valor'] += $value->notaSaida->valor;
            $total['icms'] += $value->icms;
        }

        $total['valor'] = empty( $total['valor'])? '' : parserValor($total['valor']);
        $total['icms'] = empty( $total['icms'])? '' : parserValor($total['icms']);

        return view("programs.icms_estoque_armazem.modal.saida")->with(['dados' => $dados, 'total' => $total]);
    }

    public function atualizarMovimentacao20Teste(){
        ini_set('memory_limit','2024M');

        $movimentacao20Obj = Movimentacao20::select()
            ->where('sinal', 'ilike', 'SAÍDA')
            ->whereIn('estabelecimento_codigo', ['20'])
            ->with(['notaSaida.pedido'])
            ->limit(5)
            ->get();
        foreach($movimentacao20Obj as $value){
            $value->estabelecimento_codigo = $value->notaSaida->estabelecimento_codigo;
            $value->save();
        }
    }
}
