<?php

namespace App\Http\Controllers;

use App\ChequesEmAberto;
use App\ClienteNasajon;
use App\TitulosEmAbertoNasajon;
use App\GrupoEmpresarial;
use App\TitulosVendedor998Nasajon;

use Illuminate\Http\Request;

use Auth;

use Carbon\Carbon;

use App\Http\Requests\TitulosAbertosRequest;

class TitulosAbertosController extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(['auth']);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\TitulosAbertos") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TitulosAbertos');
        return view('programs.titulos_abertos.index');
    }

    public function filter(TitulosAbertosRequest $request){
        $fields = $request->only(['cliente', 'emissao_vencimento', 'data_inicio', 'data_fim']);
        
        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format("Y-m-d");
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format("Y-m-d");

        try{
            $ClienteNasajonObj = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ILIKE \''.$fields['cliente'].'\'')->first();
        }catch(\Exception $e){
            $error = [
                "status" => 'error',
                'message' => 'Cliente não encontrado'
            ];
            return response()->json($error,422);
        }


        if(strlen(trim($ClienteNasajonObj->cpf_cnpj)) == 18){
            $cpf_cnpj = substr($ClienteNasajonObj->cpf_cnpj, 0, 10);
        }

        $clientesNasajonQuery = ClienteNasajon::query();

        if(!isset($fields['unico']) || is_null($fields['unico']) || $fields['unico'] == 'false'){

            $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
            ->where('raiz_cnpj', 'like', $cpf_cnpj . '%')
            ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                $query->where('raiz_cnpj', 'like', $cpf_cnpj . '%');
            })->first();

            if(!is_null($grupoEmpresarialObj)){
                $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                    if(isset($grupoEmpresarialObj->participantes)){
                        foreach($grupoEmpresarialObj->participantes as $participante){
                            $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                        }
                        $grupo[] = $participante->raiz_cnpj;
                    }
                    $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                });
            }
            else{
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }    
        }
        else{
            $clientesNasajonQuery->where('cpf_cnpj', $cliente['cpf_cnpj']);
        }

        $clientesNasajon = $clientesNasajonQuery->get();

        Auth::user()->cliente_padrao_id = $ClienteNasajonObj->codigo;
        Auth::user()->save();

        $empresa = returnEmpresasNasajonView();
        
        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998'){
            $titulosAbertosNasajon = TitulosVendedor998Nasajon::whereIn('cod_cliente', $clientesNasajon->pluck('codigo'))
            ->where('situacao', 'Aberto');
        }
        else{
            $titulosAbertosNasajon = TitulosEmAbertoNasajon::whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));
        }
            
        $chequesNasajonQuery = ChequesEmAberto::whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));

        if($fields['emissao_vencimento'] === 'emissao'){
            if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998'){
                $contasNasajon = $titulosAbertosNasajon->whereBetween('emissao', [$data_inicio, $data_fim])
                ->get();
            }
            else{
                $contasNasajon = $titulosAbertosNasajon->where(function ($query) use ($data_inicio, $data_fim){
                    $query->whereBetween('titulo_emissao', [$data_inicio, $data_fim]);
                })
                ->get();
            }

            $chequesNasajon = $chequesNasajonQuery->where( function ($query) use ($data_inicio, $data_fim){
                $query->whereBetween('data_entrada', [$data_inicio, $data_fim]);
            })
            ->get();

        }
        
        else if($fields['emissao_vencimento'] === 'vencimento'){

            $contasNasajon = $titulosAbertosNasajon->whereBetween('vencimento', [$data_inicio, $data_fim])
            ->get();
            
            $chequesNasajon = $chequesNasajonQuery->where( function ($query) use ($data_inicio, $data_fim){
                $query->whereBetween('data_vencimento', [$data_inicio, $data_fim]);
            })
            ->get();
        }

        $dados = [];
        $total = ['vencidos' =>  ['valor' => 0.0,'quantidade' => ''], 'avencer' => ['valor' => 0.0,'quantidade' => '']];

        $contasNasajon->each(function($conta) use(&$dados, &$total, $empresa) {

            $banco_popover =  '<p>Banco: ' . $conta->banco_codigo . '</p>
            <p>Agencia: ' . $conta->conta_agencia . '-' . $conta->conta_agencia_digito . '</p>
            <p>Conta: ' . $conta->conta_numero . '-' . $conta->conta_digito . '</p>';

            $carbonVencimento = Carbon::parse($conta->vencimento);
            
            if($carbonVencimento->lt(Carbon::now()->format('Y-m-d'))){
                $atraso = $carbonVencimento->diffInDays(Carbon::now());

                $total['vencidos']['valor'] += $conta->saldotitulo;
                $total['vencidos']['quantidade']++;
            } 
            else{
                $atraso = '';

                $total['avencer']['valor'] += $conta->saldotitulo;
                $total['avencer']['quantidade']++;
            }

            $linha = [];

            $linha['estabelecimento'] = $empresa[intval($conta->codigo)];
            $linha['estabelecimento_not_parse'] = intval($conta->codigo);
            $linha['tipo'] = 'titulo';
            $linha['numero_titulo'] = $conta->numero;
            $linha['parcela'] = $conta->parcela;
            $linha['numero_nota'] = empty($conta->nota_numero)? '': $conta->nota_numero;
            $linha['numero_documento'] = '';
            if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998'){
                $linha['numero_nota'] = empty($conta->documento_numero)? '': $conta->documento_numero;
                $linha['data_emissao'] = parserData($conta->emissao);
                $linha['data_emissao_not_parse'] = $conta->emissao;
            }
            else{
                $linha['numero_nota'] = empty($conta->nota_numero)? '': $conta->nota_numero;
                $linha['data_emissao'] = parserData($conta->titulo_emissao);
                $linha['data_emissao_not_parse'] = $conta->titulo_emissao;
            }
            $linha['data_vencimento'] = parserData($conta->vencimento);
            if($conta->tem_prorrogacao === true ){
                $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($conta->vencimento_original).'"> *</a>';
            }
            $linha['valor'] = parserValor($conta->saldotitulo);
            $linha['dias_atraso'] = $atraso;
            $linha['banco'] = $conta->banco_codigo;
            $linha['banco_popover'] = $banco_popover;
            $linha['origem'] = 'nasajon';
            $linha['id_nota'] = $conta->nota_id;

            $dados[] = $linha;


        });

        $chequesNasajon->each(function($cheque) use (&$dados, &$total, $empresa){

            $banco_popover =  '<p>Agencia: ' . $cheque->conta_agencia . '-' . $cheque->conta_agencia_digito . '</p>
            <p>Conta: ' . $cheque->conta_numero . '-' . $cheque->conta_digito . '</p>';

            $carbonVencimento = Carbon::parse($cheque->data_vencimento);
            
            if($carbonVencimento < Carbon::now()){
                $atraso = $carbonVencimento->diff(Carbon::now())->format('%d');
                
                $total['vencidos']['valor'] += $cheque->valor;
                $total['vencidos']['quantidade']++;
            } 

            else{
                $atraso = '';

                $total['avencer']['valor'] += $cheque->valor;
                $total['avencer']['quantidade']++;
            }

            $dados[] = [
                'estabelecimento' => $empresa[intval($cheque->estabelecimento)],
                'estabelecimento_not_parse' => intval($cheque->estabelecimento),
                'tipo' => 'cheque',
                'numero_titulo' => $cheque->numero_cheque,
                'parcela' => '',
                'numero_nota' => '',
                'numero_documento' => '',
                'data_emissao' => parserData($cheque->data_entrada),
                'data_emissao_not_parse' => $cheque->data_entrada,
                'data_vencimento' => parserData($cheque->data_vencimento),
                'valor' => parserValor($cheque->valor),
                'dias_atraso'=> $atraso,
                'banco' => $cheque->banco,
                'banco_popover' => $banco_popover
            ];
        });

        $datas_busca = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => []
        ];

        foreach ($dados as $key => $value) {
            $datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))][] = $value['numero_titulo'];
        }
        $ObjTemps = [];
        $HistoricoDeVendasControllerObj = new HistoricoDeVendasController();
        foreach ($datas_busca as $estabelecimento => $estabelecimentos) {
            foreach ($estabelecimentos as $data => $value) {
                // $ObjTemps[$estabelecimento][$data] = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data);
            }
            
        }
        foreach ($ObjTemps as $estabelecimento => $estabelecimentos) {
            foreach ($estabelecimentos as $data => $value) {
                if(is_null($value)){
                    continue;
                }
            }
        }
        foreach ($datas_busca as $estabelecimento => $estabelecimentos) {
            foreach ($estabelecimentos as $data => $notas) {
                foreach ($notas as $key => $value) {
                    
                    $value = (array) $value;

                    if (!isset($value['titulo'])){
                        continue;
                    }
                    $datas_busca[$estabelecimento][$data][$value['titulo']] = ["nota"=>$value['nf'],"documento"=>$value['documento']];
                    unset($datas_busca[$estabelecimento][$data][$key]);
                }
            }
        }
        foreach ($dados as $key => $value) {
            if(
                isset($datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))]) &&
                array_key_exists($value['numero_titulo'], $datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))])
            ){
                $dados[$key]['numero_nota'] = trim($datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))][$value['numero_titulo']]["nota"]);
                $dados[$key]['numero_documento'] = trim($datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))][$value['numero_titulo']]["documento"]);
            }
        }

        $cliente = [];

        if(intval($total['vencidos']['quantidade']) <= 0){
            $total['vencidos']['quantidade'] = '';
            $total['vencidos']['valor'] = '';
        }else{
            $total['vencidos']['valor'] = parserValor($total['vencidos']['valor']);
        }
        if(intval($total['avencer']['quantidade']) <= 0){
            $total['avencer']['quantidade'] = '';
            $total['avencer']['valor'] = '';
        }else{
            $total['avencer']['valor'] = parserValor($total['avencer']['valor']);
        }
        $return = utf8_converter([
            "status" => "success",
            "data" => $dados,
            'cliente' => $cliente,
            'total' => $total
        ]);

        return response()->json($return);

    }

}
