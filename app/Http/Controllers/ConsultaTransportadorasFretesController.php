<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CepEstado;
use Auth;
use App\ConhecimentoTransporteNasajon;
use App\ClienteNasajon;
use App\NotasNasajon;
use Illuminate\Support\Facades\DB;
use App\NotasImportadasEntrada;
use Carbon\Carbon;
use App\NotasImportadasEntradaRelacaoNota;

class ConsultaTransportadorasFretesController extends Controller
{
    public function __construct() {
        $this->middleware(['auth']);
    }

    private function estados(){
        $estados = CepEstado::select('uf','estado')->get();
        $return =[];
        foreach($estados as $estado){
            $return[$estado->uf] = $estado->estado;
        }
        return $return;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConsultaTransportadorasFretesController") === false){
            return abort(403);
        }
        $estabelecimentos = returnEmpresasNasajonView();
        $estados = $this->estados();
        unset($estabelecimentos[20]);
        $request->session()->flash('model', 'App\ConsultaTransportadorasFretesController');
    	return view("programs.consulta_transportadoras_fretes.index", ['estabelecimentos'=>$estabelecimentos,'estados' => $estados]);
    }

    public function filter(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only(['estabelecimento','estados','nota','operacao','data_inicio','data_fim','transportador','frete_duplicado','divergencia_transportadora']);
        $datainicio = Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00';
        $datafim = Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59';
        
        $notas = NotasNasajon::selectRaw('id_transportadora, id, transportadora_nome, transportadora_documento, chavene, sum(valor) as valor, count(transportadora_nome) as quantidade, sum(pesoliquido) as pesoliquido')
        ->with('transportadora')
        ->whereBetween('emissao',[$datainicio,$datafim])
        ->whereNotNull('transportadora_nome')
        ->whereNotIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC'])
        ->groupBy('id','id_transportadora','transportadora_nome','transportadora_documento','chavene');

        if(!empty($fields['estabelecimento']) || $fields['estabelecimento'] == '0'){
            $notas->where('estabelecimento_codigo',str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT));
        }
        if(!empty($fields['transportador'])){
            $notas->whereHas('transportadora',function ($query) use ($fields){
            $query->where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj)'), 'ilike', $fields['transportador']);
            });
        }
        if(!empty($fields['estados'])){
            $clientes = ClienteNasajon::where('uf','ilike',$fields['estados'])->get();
            $cnpj = $clientes->pluck('cpf_cnpj');
            $notas->whereIn('transportadora_documento',$cnpj);
        }
        if(!empty($fields['operacao'])){
            $notas->whereHas('itens_nota', function($query) use ($fields){
            $query->where('cfop',$fields['operacao']);
            });
        }
        if(!empty($fields['nota'])){
            $notas->where('numero',$fields['nota']);
        }
        if(isset($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::selectRaw('notas_importadas_entradas_id_nfe, count(*)')
            ->groupBy('notas_importadas_entradas_id_nfe')
            ->havingRaw('count(\'*\') > 1')
            ->get()
            ->pluck('notas_importadas_entradas_id_nfe');
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('notas_importadas_entradas_id_nfe', $relacaonotas)
            ->select('chave','notas_importadas_entradas_id_cte')
            ->distinct()
            ->get();   
            $chaves = $relacaonotas->pluck('chave');
            $notas = (!empty($chaves)) ? $notas->whereIn('chavene',$chaves) : [];
        }

        $notas = $notas->get();
        $chaves = $notas->pluck('chavene');
        if(!isset($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('chave',$chaves)->get();
        }
        $idcte = $relacaonotas->pluck('notas_importadas_entradas_id_cte');
        $notascte = NotasImportadasEntrada::selectRaw("lpad(cast(documento_numero as varchar),9,'0') as documento_numero, fornecedor_id, valor_frete, fornecedor_cnpj,peso_bruto,peso_base_calculo, id")
        ->whereIn('id',$idcte)->get();
        $numerocte = $notascte->pluck('documento_numero');
        $fornecedorcte = $notascte->pluck('fornecedor_id');
        $conhecimentotransporte = ConhecimentoTransporteNasajon::whereIn('numero',$numerocte)
        ->whereIn('idtransportador',$fornecedorcte)
        ->get();

        $return = [];
        $total = [
            'transportadora_quantidade' => 0,
            'transportadora_valor' => 0,
            'transportadora_peso' => 0,
            'emitidas_quantidade' => 0,
            'emitidas_valor' => 0,
            'emitidas_peso' => 0,
            'emitidas_custo' => 0,
            'lancadas_quantidade' => 0,
            'lancadas_valor' => 0,
            'lancadas_peso' => 0,
            'lancadas_custo' => 0,
        ];
        
        $numeronota = [];
        foreach($notas as $query){
            $transportadora = '';
            $cnpjtransportadora = '';
            $valorcteemitida = 0;
            $quantidadecteemitida = 0;
            $valorctelancada = 0;
            $quantidadectelancada = 0;
            $quantidadetotal = 0;
            $pesoemitida = 0;
            $relacao = $relacaonotas->where('chave',$query->chavene)->pluck('notas_importadas_entradas_id_cte');
            $cte = $notascte->whereIn('id',$relacao)->all();
            
            if(!empty($cte)){
                foreach($cte as $notacte){
                    if(isset($fields['divergencia_transportadora']) && $query->transportadora_documento == $notacte->fornecedor_cnpj){
                        continue;
                    }
                    if(!in_array($notacte->documento_numero,$numeronota)){
                        $lancadasnumero = $conhecimentotransporte->where('numero',$notacte->documento_numero)->where('idtransportador',$notacte->fornecedor_id)->first();
                        if(empty($lancadasnumero)){
                            $valorcteemitida += $notacte->valor_frete;
                            $quantidadecteemitida += 1;
                            if($notacte->peso_bruto > 0){
                                $pesoemitida +=  $notacte->peso_bruto;
                            }else{
                                $pesoemitida +=  $notacte->peso_base_calculo;
                            }
                        }else{
                            $valorctelancada += $notacte->valor_frete;
                            $quantidadectelancada += 1;
                        }
                        $quantidadetotal ++;
                        $numeronota[] = $notacte->documento_numero;
                    }
                }
            }
            if(isset($fields['divergencia_transportadora']) && $quantidadetotal == 0){
                continue;
            }
            if(isset($query->transportadora->id)) {
                if($query->transportadora->nome == 'ENTREGA' || $query->transportadora->nome == 'SEDEX' || $query->transportadora->nome == 'RETIRA'){
                    $transportadora = $query->transportadora->nome;
                    $cnpjtransportadora = $query->transportadora->nome;
                }else{
                    $transportadora = $query->transportadora->nome . ' - ' . $query->transportadora->cnpj;
                    $cnpjtransportadora = $query->transportadora->id;
                }
            }else{
                if($query->transportadora_nome == 'ENTREGA' || $query->transportadora_nome == 'SEDEX' || $query->transportadora_nome == 'RETIRA'){
                    $transportadora = $query->transportadora_nome . ' - ' . $query->transportadora_documento;
                    $cnpjtransportadora = $query->transportadora_nome;
                }else{
                    $transportadora = $query->transportadora_nome . ' - ' . $query->transportadora_documento;
                    $cnpjtransportadora = $query->id_transportadora;
                }

            }

            if(!isset($return[$transportadora])){
                $return[$transportadora] = [
                    'transportadora_notas' => $transportadora,
                    'transportadora_quantidade' => 0,
                    'transportadora_valor' => 0,
                    'transportadora_peso' => 0,
                    'emitidas_quantidade' => 0,
                    'emitidas_valor' => 0,
                    'emitidas_peso' => 0,
                    'emitidas_custo' => 0,
                    'lancadas_quantidade' => 0,
                    'lancadas_valor' => 0,
                    'lancadas_peso' => 0,
                    'lancadas_custo' => 0,
                ];
            }

            $return[$transportadora]['filter'] = encrypt([
                'estabelecimento' => (isset($fields['estabelecimento'])) ? str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT) : null,
                'estados' => $fields['estados'],
                'nota' => $fields['nota'],
                'operacao' => $fields['operacao'],
                'transportador' => $fields['transportador'],
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim'],
                'codigo' => '',
                'cnpjtransportadora' => $cnpjtransportadora,
                'divergencia_transportadora' => (isset($fields['divergencia_transportadora'])) ? $fields['divergencia_transportadora'] : null,
                'frete_duplicado' => (isset($fields['frete_duplicado'])) ? $fields['frete_duplicado'] : null,
            ]);

            $return[$transportadora]['emitidas_quantidade'] += $quantidadecteemitida;
            $return[$transportadora]['emitidas_valor'] += $valorcteemitida ;
            $return[$transportadora]['emitidas_peso'] += $pesoemitida;
            $return[$transportadora]['lancadas_quantidade'] += $quantidadectelancada;
            $return[$transportadora]['lancadas_valor'] += $valorctelancada;
            $return[$transportadora]['transportadora_quantidade'] += $query->quantidade;
            $return[$transportadora]['transportadora_valor'] += $query->valor;
            $return[$transportadora]['transportadora_peso'] += $query->pesoliquido;
            $total['transportadora_quantidade'] += $query->quantidade;
            $total['transportadora_valor'] += $query->valor;
            $total['transportadora_peso'] += $query->pesoliquido;
            $total['lancadas_quantidade'] += $quantidadectelancada;
            $total['lancadas_valor'] += $valorctelancada;
            $total['emitidas_quantidade'] += $quantidadecteemitida;
            $total['emitidas_valor'] += $valorcteemitida;
            $total['emitidas_peso'] += $pesoemitida;
        }

        foreach($return as $key => $saida){
            $custo = 0;
            if($return[$key]['emitidas_peso'] > 0){
                $custo = $return[$key]['emitidas_valor'] / $return[$key]['emitidas_peso'];
            }
            $return[$key]['transportadora_quantidade'] = ($return[$key]['transportadora_quantidade'] > 0) ? $return[$key]['transportadora_quantidade'] : '';
            $return[$key]['transportadora_valor'] = ($return[$key]['transportadora_valor'] > 0) ? parserValor($return[$key]['transportadora_valor']) : '';
            $return[$key]['transportadora_peso'] = ($return[$key]['transportadora_peso'] > 0) ? parserQtd($return[$key]['transportadora_peso']) : '';
            $return[$key]['emitidas_quantidade'] = ($return[$key]['emitidas_quantidade'] > 0) ? $return[$key]['emitidas_quantidade'] : '';
            $return[$key]['emitidas_valor'] = ($return[$key]['emitidas_valor'] > 0) ? parserValor($return[$key]['emitidas_valor']) : '';
            $return[$key]['emitidas_peso'] = ($return[$key]['emitidas_peso'] > 0) ? parserQtd($return[$key]['emitidas_peso']) : '';
            $return[$key]['emitidas_custo'] = ($custo > 0) ? parserValor($custo) : '';
            $return[$key]['lancadas_quantidade'] = ($return[$key]['lancadas_quantidade'] > 0) ? $return[$key]['lancadas_quantidade'] : '';
            $return[$key]['lancadas_valor'] = ($return[$key]['lancadas_valor'] > 0) ? parserValor($return[$key]['lancadas_valor']) : '';
            $return[$key]['lancadas_peso'] = ($return[$key]['lancadas_peso'] > 0) ? parserQtd($return[$key]['lancadas_peso']) : '';
            $return[$key]['lancadas_custo'] = ($return[$key]['lancadas_custo'] > 0) ? parserValor($return[$key]['lancadas_custo']) : '';
        }

        $totalcusto = 0;
        if($total['emitidas_peso'] > 0 ){
            $totalcusto = $total['emitidas_valor'] / $total['emitidas_peso'];
        }
        $total['transportadora_quantidade'] = ($total['transportadora_quantidade'] > 0) ?  $total['transportadora_quantidade'] : '';
        $total['transportadora_valor'] = ($total['transportadora_valor'] > 0) ? parserValor($total['transportadora_valor']) : '';
        $total['transportadora_peso'] = ($total['transportadora_peso'] > 0) ?  parserQtd($total['transportadora_peso']) : '';
        $total['emitidas_quantidade'] = ($total['emitidas_quantidade'] > 0) ?  $total['emitidas_quantidade'] : '';
        $total['emitidas_valor'] = ($total['emitidas_valor'] > 0) ?  parserValor($total['emitidas_valor']) : '';
        $total['emitidas_peso'] = ($total['emitidas_peso'] > 0) ?  parserQtd($total['emitidas_peso']) : '';
        $total['emitidas_custo'] = ($totalcusto > 0) ?  parserQtd($totalcusto) : '';
        $total['lancadas_quantidade'] = ($total['lancadas_quantidade'] > 0) ? $total['lancadas_quantidade'] : '';
        $total['lancadas_valor'] = ($total['lancadas_valor'] > 0) ? parserValor($total['lancadas_valor']) : '';
        $total['lancadas_peso'] = ($total['lancadas_peso'] > 0) ? parserValor($total['lancadas_peso']) : '';
        $total['lancadas_custo'] = ($total['lancadas_custo'] > 0) ? parserValor($total['lancadas_custo']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $return, 'total' => $total]
        ]);
    }

    public function modalTitulosLancadas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','512M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        $datainicio = Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00';
        $datafim = Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59';

        
        $notas = NotasNasajon::with('transportadora')
            ->whereBetween('emissao',[$datainicio,$datafim])
            ->whereNotNull('transportadora_nome')
            ->whereNotIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC'])
            ;
        if($fields['cnpjtransportadora'] == 'RETIRA' || $fields['cnpjtransportadora'] == 'ENTREGA' || $fields['cnpjtransportadora'] == 'SEDEX') {
            $notas->where('transportadora_nome', $fields['cnpjtransportadora']);
        }else{
            $notas->where('id_transportadora', $fields['cnpjtransportadora']);
        }
        if(!empty($fields['estabelecimento']) || $fields['estabelecimento'] == '0'){
            $notas->where('estabelecimento_codigo',str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT));
        }
        if(!empty($fields['transportador'])){
            $notas->whereHas('transportadora',function ($query) use ($fields){
                $query->where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj)'), 'ilike', $fields['transportador']);
            });
        }
        if(!empty($fields['estados'])){
            $clientes = ClienteNasajon::where('uf','ilike',$fields['estados'])->get();
            $cnpj = $clientes->pluck('cpf_cnpj');
            $notas->whereIn('transportadora_documento',$cnpj);
        }
        if(!empty($fields['operacao'])){
            $notas->whereHas('itens_nota', function($query) use ($fields){
                $query->where('cfop',$fields['operacao']);
            });
        }
        if(!empty($fields['nota'])){
            $notas->where('numero',$fields['nota']);
        }
        if(!empty($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::selectRaw('notas_importadas_entradas_id_nfe, count(*)')
            ->groupBy('notas_importadas_entradas_id_nfe')
            ->havingRaw('count(\'*\') > 1')
            ->get()
            ->pluck('notas_importadas_entradas_id_nfe');
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('notas_importadas_entradas_id_nfe', $relacaonotas)
            ->get();   
            $chaves = $relacaonotas->pluck('chave');
            $notas = (!empty($chaves)) ? $notas->whereIn('chavene',$chaves) : [];
        }

        $notas = $notas->get();
        $chaves = $notas->pluck('chavene');
        if(!isset($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('chave',$chaves)->get();
        }
        $idcte = $relacaonotas->pluck('notas_importadas_entradas_id_cte');
        $notascte = NotasImportadasEntrada::selectRaw("lpad(cast(documento_numero as varchar),9,'0') as documento_numero, 
        destinatario_nome, destinatario_cpf_cnpj, data_emissao, fornecedor_nome, fornecedor_id, valor_frete, fornecedor_cnpj,peso_bruto,peso_base_calculo, id")
        ->whereIn('id',$idcte)->get();
        $numerocte = $notascte->pluck('documento_numero');
        $fornecedorcte = $notascte->pluck('fornecedor_id');
        $conhecimentotransporte = ConhecimentoTransporteNasajon::whereIn('numero',$numerocte)
        ->whereIn('idtransportador',$fornecedorcte)
        ->get();
        $retorno = [];
        $numeronota = [];
        $total = [
            'peso' => 0,
            'valor' => 0,
            'custo' => 0,
        ];

        foreach($notas as $nota){
            $peso = 0;
            $relacao = $relacaonotas->where('chave',$nota->chavene)->pluck('notas_importadas_entradas_id_cte');
            $cte = $notascte->whereIn('id',$relacao)->all();
            if(!empty($cte)){
                foreach($cte as $notacte){
                    if(!empty($fields['divergencia_transportadora']) && $nota->transportadora_documento == $notacte->fornecedor_cnpj){
                        continue;
                    }
                    if(!in_array($notacte->documento_numero.$notacte->fornecedor_id,$numeronota)){
                        $peso = 0;
                        if($notacte->peso_bruto > 0){
                            $peso =  $notacte->peso_bruto;
                        }else{
                            $peso =  $notacte->peso_base_calculo;
                        }
                        $lancadasnumero = $conhecimentotransporte->where('numero',$notacte->documento_numero)->where('idtransportador',$notacte->fornecedor_id)->first();
                        if(!empty($lancadasnumero)){
                            $retorno[] = [
                                'numero' => $notacte->documento_numero,
                                'transportador' => $notacte->fornecedor_nome.' - '.$notacte->fornecedor_cnpj,
                                'emissao' => parserData($notacte->data_emissao),
                                'cliente' => $notacte->destinatario_nome.' - '.$notacte->destinatario_cpf_cnpj,
                                'valor' => $notacte->valor_frete,
                                'data_emissao' => (!empty($notacte->data_emissao)) ? parserData($notacte->data_emissao) : null,
                                'id' => encrypt($notacte->id),
                                'peso' => $peso,
                                'custo' => ($peso > 0) ? ($notacte->valor_frete / $peso) : 0,
                            ];
                            $total['valor'] += $notacte->valor_frete;
                            $total['peso'] += $peso;
                        }
                        $numeronota[] = $notacte->documento_numero.$notacte->fornecedor_id;
                    }
                }
            }
        }
        $custototal = 0;
        if($total['peso'] > 0){
            $custototal = $total['valor'] / $total['peso'];
        }
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['peso'] = ($total['peso'] > 0) ? parserValor($total['peso']) : '';
        $total['custo'] = ($custototal > 0) ? parserValor($custototal) : '';
        foreach($retorno as $key => $saida){
            $custo = 0;
            if($retorno[$key]['peso'] > 0){
                $custo = $retorno[$key]['valor'] / $retorno[$key]['peso'];
            }
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
            $retorno[$key]['peso'] = ($retorno[$key]['peso'] > 0) ? parserValor($retorno[$key]['peso']) : '';
            $retorno[$key]['custo'] = ($custo > 0) ? parserValor($custo) : '';
        }

        return view('programs.consulta_transportadoras_fretes.modal.notas_emitidas')->with(["dados" => $retorno, 'total' => $total]);
    }

    public function modalTitulosEmitidos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','512M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $datainicio = Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00';
        $datafim = Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59';

        $notas = NotasNasajon::with('transportadora')
            ->whereBetween('emissao',[$datainicio,$datafim])
            ->whereNotNull('transportadora_nome')
            ->whereNotIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC'])
            ;
        if($fields['cnpjtransportadora'] == 'RETIRA' || $fields['cnpjtransportadora'] == 'ENTREGA' || $fields['cnpjtransportadora'] == 'SEDEX') {
            $notas->where('transportadora_nome', $fields['cnpjtransportadora']);
        }else{
            $notas->where('id_transportadora', $fields['cnpjtransportadora']);
        }
        if(!empty($fields['estabelecimento']) || $fields['estabelecimento'] == '0'){
            $notas->where('estabelecimento_codigo',str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT));
        }
        if(!empty($fields['transportador'])){
            $notas->whereHas('transportadora',function ($query) use ($fields){
                $query->where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj)'), 'ilike', $fields['transportador']);
            });
        }
        if(!empty($fields['estados'])){
            $clientes = ClienteNasajon::where('uf','ilike',$fields['estados'])->get();
            $cnpj = $clientes->pluck('cpf_cnpj');
            $notas->whereIn('transportadora_documento',$cnpj);
        }
        if(!empty($fields['operacao'])){
            $notas->whereHas('itens_nota', function($query) use ($fields){
                $query->where('cfop',$fields['operacao']);
            });
        }
        if(!empty($fields['nota'])){
            $notas->where('numero',$fields['nota']);
        }
        if(!empty($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::selectRaw('notas_importadas_entradas_id_nfe, count(*)')
            ->groupBy('notas_importadas_entradas_id_nfe')
            ->havingRaw('count(\'*\') > 1')
            ->get()
            ->pluck('notas_importadas_entradas_id_nfe');
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('notas_importadas_entradas_id_nfe', $relacaonotas)
            ->get();   
            $chaves = $relacaonotas->pluck('chave');
            $notas = (!empty($chaves)) ? $notas->whereIn('chavene',$chaves) : [];
        }

        $notas = $notas->get();
        $chaves = $notas->pluck('chavene');
        if(!isset($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('chave',$chaves)->get();
        }
        $idcte = $relacaonotas->pluck('notas_importadas_entradas_id_cte');
        $notascte = NotasImportadasEntrada::selectRaw("lpad(cast(documento_numero as varchar),9,'0') as documento_numero, 
        destinatario_nome, destinatario_cpf_cnpj, data_emissao, fornecedor_nome, fornecedor_id, valor_frete, fornecedor_cnpj,peso_bruto,peso_base_calculo, id")
        ->whereIn('id',$idcte)->get();
        $numerocte = $notascte->pluck('documento_numero');
        $fornecedorcte = $notascte->pluck('fornecedor_id');
        $conhecimentotransporte = ConhecimentoTransporteNasajon::whereIn('numero',$numerocte)
        ->whereIn('idtransportador',$fornecedorcte)
        ->get();
        $retorno = [];
        $numeronota = [];
        $total = [
            'peso' => 0,
            'valor' => 0,
            'custo' => 0,
        ];

        foreach($notas as $nota){
            $peso = 0;
            $relacao = $relacaonotas->where('chave',$nota->chavene)->pluck('notas_importadas_entradas_id_cte');
            $cte = $notascte->whereIn('id',$relacao)->all();
            if(!empty($cte)){
                foreach($cte as $notacte){
                    if(!empty($fields['divergencia_transportadora']) && $nota->transportadora_documento == $notacte->fornecedor_cnpj){
                        continue;
                    }
                    if(!in_array($notacte->documento_numero.$notacte->fornecedor_id,$numeronota)){
                        $peso = 0;
                        if($notacte->peso_bruto > 0){
                            $peso =  $notacte->peso_bruto;
                        }else{
                            $peso =  $notacte->peso_base_calculo;
                        }
                        $lancadasnumero = $conhecimentotransporte->where('numero',$notacte->documento_numero)->where('idtransportador',$notacte->fornecedor_id)->first();
                        if(empty($lancadasnumero)){
                            $retorno[] = [
                                'numero' => $notacte->documento_numero,
                                'transportador' => $notacte->fornecedor_nome.' - '.$notacte->fornecedor_cnpj,
                                'emissao' => parserData($notacte->data_emissao),
                                'cliente' => $notacte->destinatario_nome.' - '.$notacte->destinatario_cpf_cnpj,
                                'valor' => $notacte->valor_frete,
                                'data_emissao' => (!empty($notacte->data_emissao)) ? parserData($notacte->data_emissao) : null,
                                'id' => encrypt($notacte->id),
                                'peso' => $peso,
                                'custo' => ($peso > 0) ? ($notacte->valor_frete / $peso) : 0,
                            ];
                            $total['valor'] += $notacte->valor_frete;
                            $total['peso'] += $peso;
                        }
                        $numeronota[] = $notacte->documento_numero.$notacte->fornecedor_id;
                    }
                }
            }
        }
        $custototal = 0;
        if($total['peso'] > 0){
            $custototal = $total['valor'] / $total['peso'];
        }
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['peso'] = ($total['peso'] > 0) ? parserValor($total['peso']) : '';
        $total['custo'] = ($custototal > 0) ? parserValor($custototal) : '';
        foreach($retorno as $key => $saida){
            $custo = 0;
            if($retorno[$key]['peso'] > 0){
                $custo = $retorno[$key]['valor'] / $retorno[$key]['peso'];
            }
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
            $retorno[$key]['peso'] = ($retorno[$key]['peso'] > 0) ? parserValor($retorno[$key]['peso']) : '';
            $retorno[$key]['custo'] = ($custo > 0) ? parserValor($custo) : '';
        }

        return view('programs.consulta_transportadoras_fretes.modal.notas_emitidas')->with(["dados" => $retorno, 'total' => $total]);
    }

    public function modalNotasMN(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','512M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $datainicio = Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00';
        $datafim = Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59';
        $notas = NotasNasajon::whereBetween('emissao',[$datainicio,$datafim])
            ->whereNotIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC'])
            ->with('transportadora');
        if($fields['cnpjtransportadora'] == 'RETIRA' || $fields['cnpjtransportadora'] == 'ENTREGA' || $fields['cnpjtransportadora'] == 'SEDEX') {
            $notas->where('transportadora_nome', $fields['cnpjtransportadora']);
        }else{
            $notas->where('id_transportadora', $fields['cnpjtransportadora']);
        }

        if(!empty($fields['estabelecimento']) || $fields['estabelecimento'] == '0'){
            $notas->whereRaw('estabelecimento_codigo = \''.str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT).'\'');
        }
        if(!empty($fields['transportador'])){
            $notas->whereHas('transportadora', function($query) use ($fields){
                $query->where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj)'), 'ilike', $fields['transportador']);
            });
        }
        if(!empty($fields['estados'])){
            $clientes = ClienteNasajon::where('uf','ilike',$fields['estados'])->get();
            $cnpj = $clientes->pluck('cpf_cnpj');
            $notas->whereIn('transportadora_documento',$cnpj);
        }
        if(!empty($fields['operacao'])){
            $notas->where('numero',$fields['nota']);
        }
        if(!empty($fields['nota'])){
            $notas->where('numero',$fields['nota']);
        }
        if(!empty($fields['frete_duplicado'])){
            $relacaonotas = NotasImportadasEntradaRelacaoNota::selectRaw('notas_importadas_entradas_id_nfe, count(*)')
            ->groupBy('notas_importadas_entradas_id_nfe')
            ->havingRaw('count(\'*\') > 1')
            ->get()
            ->pluck('notas_importadas_entradas_id_nfe');
            $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('notas_importadas_entradas_id_nfe', $relacaonotas)
            ->get();
            $chaves = $relacaonotas->pluck('chave');
            $notas = $notas->whereIn('chavene',$chaves);
        }
        
        $notas = $notas->get();
        if(!empty($fields['divergencia_transportadora'])){
            $chaves = $notas->pluck('chavene');
            if(!isset($fields['frete_duplicado'])){
                $relacaonotas = NotasImportadasEntradaRelacaoNota::whereIn('chave',$chaves)->get();
            }
            $idcte = $relacaonotas->pluck('notas_importadas_entradas_id_cte');
            $notascte = NotasImportadasEntrada::selectRaw("lpad(cast(documento_numero as varchar),9,'0') as documento_numero, 
            destinatario_nome, destinatario_cpf_cnpj, data_emissao, fornecedor_nome, fornecedor_id, valor_frete, fornecedor_cnpj,peso_bruto,peso_base_calculo, id")
            ->whereIn('id',$idcte)->get();
            $numerocte = $notascte->pluck('documento_numero');
            $fornecedorcte = $notascte->pluck('fornecedor_id');
            $conhecimentotransporte = ConhecimentoTransporteNasajon::whereIn('numero',$numerocte)
            ->whereIn('idtransportador',$fornecedorcte)
            ->get();
        }
        $return = [];
        $numeronota = [];
        $total = [
            'valor' => 0,
            'peso' => 0
        ];
        foreach($notas as $query){
            if(!empty($fields['divergencia_transportadora'])){
                $relacao = $relacaonotas->where('chave',$query->chavene)->pluck('notas_importadas_entradas_id_cte');
                $cte = $notascte->whereIn('id',$relacao)->all();
                $quantidadetotal = 0;
                if(!empty($cte)){
                    foreach($cte as $notacte){
                        if(isset($fields['divergencia_transportadora']) && $query->transportadora_documento == $notacte->fornecedor_cnpj){
                            continue;
                        }
                        if(!in_array($notacte->documento_numero,$numeronota)){
                            $quantidadetotal ++;
                            $numeronota[] = $notacte->documento_numero;
                        }
                    }
                }
                if(isset($fields['divergencia_transportadora']) && $quantidadetotal == 0){
                    continue;
                }
            }
            $return[] = [
                'numero' => $query->numero,
                'emissao' => parserData($query->emissao),
                'cliente' => ($query->cliente_documento) ? $query->cliente_nome.' - '.$query->cliente_documento : $query->cliente_nome,
                'peso' => $query->pesoliquido,
                'valor' => $query->valor,
                'idnota' => $query->id,
                'filter' => encrypt(['estabelecimento' => (isset($fields['estabelecimento'])) ? str_pad($fields['estabelecimento'],2,'0', STR_PAD_LEFT) : null,
                    'estados' => $fields['estados'],
                    'nota' => $query->numero,
                    'operacao' => $fields['operacao'],
                    'transportador' => $fields['transportador'],
                    'data_inicial' => $fields['data_inicio'],
                    'data_final' => $fields['data_fim'],
                    'idtransportadora' => $query->idtransportador,
                ]),
            ];
            $total['valor'] += $query->valor;
            $total['peso'] += $query->pesoliquido;

        }

        foreach($return as $key => $row){
            $return[$key]['valor'] = ($row['valor'] > 0) ?  parserValor($row['valor']) : '';
            $return[$key]['peso'] = ($row['peso'] > 0) ?  parserValor($row['peso']) : '';
        }

        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['peso'] = ($total['peso'] > 0) ? parserValor($total['peso']) : '';

        return view('programs.consulta_transportadoras_fretes.modal.notas')->with(["dados" => $return, 'total' => $total]);
    }

}
