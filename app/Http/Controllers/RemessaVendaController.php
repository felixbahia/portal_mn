<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\PedidosVendaNasajon;
use App\NotasNasajon;

use Illuminate\Support\Facades\DB;

class RemessaVendaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConsultaRemessaVenda") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaRemessaVenda');

        $primeiro_dia_do_mes = Carbon::now()->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::now()->setTime(0,0,0)->lastOfMonth();

        $primeiro_dia_do_mes = Carbon::createFromFormat('Y-m-d H:i:s', $primeiro_dia_do_mes)->format('d/m/Y');
        $ultimo_dia_do_mes = Carbon::createFromFormat('Y-m-d H:i:s', $ultimo_dia_do_mes)->format('d/m/Y');

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.remessa_venda.index')->with(['estabelecimentos' => $estabelecimentos, 'primeiro_dia_do_mes' => $primeiro_dia_do_mes, 'ultimo_dia_do_mes' => $ultimo_dia_do_mes]);
    }

    public function filtro(Request $request){
        set_time_limit(600);
        ini_set('memory_limit','2048M');

        $fields = $request->only('estabelecimento_filtro', 'cliente', 'data_emissao_inicio', 'data_emissao_fim');

        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [];
        $NotasNasajonObj = new NotasNasajon;
        $PedidosVendaNasajonObj = new PedidosVendaNasajon;

        $query = NotasNasajon::select(DB::raw('grupodeoperacao, '.$PedidosVendaNasajonObj->getTable().'.operacao_codigo, '.$PedidosVendaNasajonObj->getTable().'.estabelecimento_codigo,'.$PedidosVendaNasajonObj->getTable().'.numero, notafiscal_numero, '.$NotasNasajonObj->getTable().'.emissao, cliente_nome, cliente_documento, '.$NotasNasajonObj->getTable().'.valor, valoricms, '.$NotasNasajonObj->getTable().'.id'));
        $query->leftJoin($PedidosVendaNasajonObj->getTable(), "{$NotasNasajonObj->getTable()}.id", "{$PedidosVendaNasajonObj->getTable()}.notafiscal_id");
        $query->with(['primeiro_itens_nota']);
        if(!empty($fields['estabelecimento_filtro'])){
            $query->where($PedidosVendaNasajonObj->getTable().'.estabelecimento_codigo', str_pad($fields["estabelecimento_filtro"], 2, '0', STR_PAD_LEFT));
        }else{
            $query->whereIn($PedidosVendaNasajonObj->getTable().'.estabelecimento_codigo', ['03','04']);
        }
        if(!empty($fields['data_emissao_inicio'])){
            $data_emissao_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_emissao_inicio'])->setTime(0,0,0);
            $query->where($NotasNasajonObj->getTable().'.emissao', '>=', $data_emissao_inicio);
        }
        if(!empty($fields['data_emissao_fim'])){
            $data_emissao_fim = Carbon::createFromFormat('d/m/Y', $fields['data_emissao_fim'])->setTime(0,0,0);
            $query->where($NotasNasajonObj->getTable().'.emissao', '<=', $data_emissao_fim);
        }
        $query->whereIn('grupodeoperacao', ['REMESSA','VENDA']);
        $query->whereColumn($PedidosVendaNasajonObj->getTable().'.cliente_cnpj', $NotasNasajonObj->getTable().'.cliente_documento');
        $query->orderBy($PedidosVendaNasajonObj->getTable().'.operacao_codigo', $PedidosVendaNasajonObj->getTable().'.estabelecimento_codigo', $PedidosVendaNasajonObj->getTable().'.numero');
        $query->with(['primeiro_itens_nota']);
        $result = $query->get();

        foreach($result as $valor){         
            if(empty($retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero])){
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero] = [
                    'numero_armazem' => '',
                    'cfop_armazem' => '',
                    'emissao_armazem' => '',
                    'cliente_armazem' => '',
                    'icms_armazem' => '',
                    'valor_armazem' => '',
                    
                    'numero_textil' => '',
                    'cfop_textil' => '',
                    'emissao_textil' => '',
                    'cliente_textil' => '',
                    'icms_textil' => '',
                    'valor_textil' => '',
                ];
            }

            if($valor->grupodeoperacao === 'REMESSA'){
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['numero_armazem'] = $valor->notafiscal_numero;
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['cfop_armazem'] = empty($valor->primeiro_itens_nota)? '' : $valor->primeiro_itens_nota->cfop;
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['emissao_armazem'] = parserData($valor->emissao);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['emissao_carbon_armazem'] = Carbon::parse($valor->emissao)->setTime(0,0,0);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['cliente_armazem'] = $valor->cliente_nome.' - '.$valor->cliente_documento;
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['icms_armazem'] = empty($valor->valoricms)? '' : parserValor($valor->valoricms);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['valor_armazem'] = parserValor($valor->valor);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['nota_id'] = $valor->id;
            }else{
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['estabelecimento_textil'] = $estabelecimentos[intval($valor->estabelecimento_codigo)];
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['numero_textil'] = $valor->notafiscal_numero;
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['cfop_textil'] = empty($valor->primeiro_itens_nota)? '' : $valor->primeiro_itens_nota->cfop;
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['emissao_textil'] = parserData($valor->emissao);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['emissao_carbon_textil'] = Carbon::parse($valor->emissao)->setTime(0,0,0);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['cliente_textil'] = $valor->cliente_nome.' - '.$valor->cliente_documento;
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['valor_textil'] = parserValor($valor->valor);
                $retorno[$valor->operacao_codigo.$valor->estabelecimento_codigo.$valor->numero]['nota_id'] = $valor->id;
            }
        }
        
        foreach($retorno as $index => $value){
            if(empty($value['numero_armazem']) || empty($value['numero_textil'])){
                unset($retorno[$index]);
            }

            if(!empty($fields['cliente'])){
                if($fields['cliente'] != $value['cliente_armazem'] && $fields['cliente'] != $value['cliente_textil']){
                    unset($retorno[$index]);
                }
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }
}
