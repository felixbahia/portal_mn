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

class ValorEstoqueArmazemController extends Controller
{
    public function index(Request $request){
        ini_set('memory_limit','2024M');
        if (Auth::user()->hasPermissionTo("programas App\ValorEstoqueArmazem") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\ValorEstoqueArmazem');

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

        return view("programs.valor_estoque_armazem.index")->with(['estabelecimentos' => $estabelecimentos]);
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

        $movimentacaoValorArmazemObj = MovimentacaoValorArmazem::select(DB::Raw('sum(saldo_anterior) as saldo_anterior, sum(entrada) as entrada, sum(saida) as saida, sum(saldo_atual) as saldo_atual, data_movimentacao'));
        $movimentacaoValorArmazemObj->whereBetween('data_movimentacao', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $movimentacaoValorArmazemObj->groupBy('data_movimentacao');
        if(!empty($fields['estabelecimento'])){
            $movimentacaoValorArmazemObj->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }
        $movimentacaoValorArmazemObj = $movimentacaoValorArmazemObj->get();

        foreach($movimentacaoValorArmazemObj as $value){
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
        $movimentacao20Obj->whereIn('item_cfop', ['2905', '6907']);
        $movimentacao20Obj->where('sinal', 'ilike', 'ENTRADA');
        $movimentacao20Obj->whereBetween('data_movimentacao', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        if(!empty($fields['estabelecimento'])){
            $movimentacao20Obj->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }
        $movimentacao20Obj->with(['notaEntrada']);
        $movimentacao20Obj->groupBy('item_cfop', 'documento_id');
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
        
        $movimentacao20Obj = Movimentacao20::select(DB::Raw('sum(item_valor_icms) as icms, item_cfop, documento_id'));
        $movimentacao20Obj->whereIn('item_cfop', ['2905', '6907']);
        $movimentacao20Obj->where('sinal', 'ilike', 'SAÍDA');
        $movimentacao20Obj->whereBetween('data_movimentacao', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        if(!empty($fields['estabelecimento'])){
            $movimentacao20Obj->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }
        $movimentacao20Obj->with(['notaSaida']);
        $movimentacao20Obj->groupBy('item_cfop', 'documento_id');
        $movimentacao20Obj = $movimentacao20Obj->get();

        $dados = [];
        $total = [
            'valor' => 0,
            'icms' => 0,
        ];
        foreach($movimentacao20Obj as $value){
            $dados[] = [
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
}
