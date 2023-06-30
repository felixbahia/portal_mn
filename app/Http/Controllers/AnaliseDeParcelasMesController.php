<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//use App\ContasReceber;
//use App\ChequesRecebido;
//use App\ContasReceberBaixado;

use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnaliseDeParcelasMesController extends Controller
{
    private $estabelecimentos = [];

    public function __construct(){
        $estabelecimentos = returnEmpresasNasajonView();
        // unset($estabelecimentos[0]);
        // $estabelecimentos[20] = 'ARMAZÉM';
        ksort($estabelecimentos);
        $this->estabelecimentos = $estabelecimentos;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseDeParcelas") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnaliseDeParcelas');
        $estabelecimentos = $this->estabelecimentos;
        return view('programs.analise_de_entrada.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filter(Request $request){
        $fields = $request->only(['data']);

        $data = $fields['data'];

        $data = Carbon::createFromFormat('m/Y', $data);
        $data_inicio = $data->format('Y-m-01');
        $data_fim = $data->format('Y-m-t');

        // $ContasReceberObj = ContasReceber::selectRaw('ESTABEL AS estabelecimento, COUNT(DISTINCT NUMDOC) AS \'numeros_documento\', COUNT(NUMDOC) AS \'numero_parcelas\', SUM(VALOR) AS \'valor_total\'')
        //     ->whereBetween('DTEMIS', [$data_inicio, $data_fim])
        //     ->where('TIPREG', '!=', 'C')
        //     ->where('TIPREG', '!=', 'D')
        //     ->groupBy('ESTABEL')
        //     ->get();

        $TitulosAReceberNasajonObj = DB::connection('nasajon')->table('integracoes.vw_titulosemaberto_portal')->selectRaw('codigo as estabelecimento, count(distinct(regexp_replace(numero, \'\.([0-9]|[0-9]{2})$\', \'\'))) as numeros_documento, count(regexp_replace(numero, \'\.([0-9]|[0-9]{2})$\', \'\')) as numero_parcelas, sum(valor) as valor_total')
            ->whereBetween('titulo_emissao', [$data_inicio, $data_fim])
            ->groupBy('codigo')
            ->get();

        // $ContasReceberBaixadoObj = ContasReceberBaixado::selectRaw('ESTABEL AS estabelecimento, COUNT(DISTINCT NUMDOC) AS \'numeros_documento\', COUNT(NUMDOC) AS \'numero_parcelas\', SUM(VALOR) AS \'valor_total\'')
        //     ->whereBetween('DTEMIS', [$data_inicio, $data_fim])
        //     ->where('TIPREG', '!=', 'C')
        //     ->where('TIPREG', '!=', 'D')
        //     ->groupBy('ESTABEL')
        //     ->get();

        $TitulosPagoNasajonObj = DB::connection('nasajon')->table('integracoes.vw_titulospagos_portal')->selectRaw('codigo as estabelecimento, count(distinct(regexp_replace(numero, \'\.([0-9]|[0-9]{2})$\', \'\'))) as numeros_documento, count(regexp_replace(numero, \'\.([0-9]|[0-9]{2})$\', \'\')) as numero_parcelas, sum(valor) as valor_total')
            ->whereBetween('emissao', [$data_inicio, $data_fim])
            ->groupBy('codigo')
            ->get();

        $response = [];
        $total = [
            'numero_notas' => 0,
            'numero_parcelas' => 0,
            'valor_total' => 0,
            'valor_medio' => 0
        ];
        foreach ($TitulosAReceberNasajonObj as $key => $contas) {
            $response[intval($contas->estabelecimento)] = [
                'estabelecimento' => $this->estabelecimentos[intval($contas->estabelecimento)],
                'numero_notas' => $contas->numeros_documento,
                'numero_parcelas' => $contas->numero_parcelas,
                'valor_total' => floatval($contas->valor_total),
                'valor_medio' => 0
            ];
        }
        foreach($this->estabelecimentos as $key => $value){
            if(empty($response[$key])){ 
                $response[$key] = [
                    'estabelecimento' => $this->estabelecimentos[$key],
                    'numero_notas' => 0.0,
                    'numero_parcelas' => 0.0,
                    'valor_total' => 0.0,
                    'valor_medio' => 0
                ];
            }
        }
        foreach ($TitulosPagoNasajonObj as $key => $contas) {
            $response[intval($contas->estabelecimento)]['numero_notas'] += $contas->numeros_documento;
            $response[intval($contas->estabelecimento)]['numero_parcelas'] += $contas->numero_parcelas;
            $response[intval($contas->estabelecimento)]['valor_total'] += $contas->valor_total;
        }
        foreach($response as $key => $value){
            $total['numero_notas'] += $value['numero_notas'];
            $total['numero_parcelas'] += $value['numero_parcelas'];
            $total['valor_total'] += $value['valor_total'];
            $total['valor_medio'] += ($value['numero_parcelas'] > 0) ? $value['valor_total'] / $value['numero_parcelas'] : 0;
            if(empty($response[$key]['valor_total'])){
                unset($response[$key]);
            }else{
                $response[$key]['valor_medio'] = parserValor($value['valor_total'] / $value['numero_parcelas']);
                $response[$key]['valor_total'] = \parserValor($value['valor_total']);
            }
        }
        
        $total['valor_total'] = parserValor($total['valor_total']);
        $total['valor_medio'] = parserValor($total['valor_medio']);
        
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'response' => $response,
                'total' => $total
            ]
        ];
        return response()->json($return);
    }

    private function buscaDumVendas($estabelecimento, Carbon $data){
        $table_dum = "DUM".str_pad($estabelecimento, 2, "0", STR_PAD_LEFT)."_".$data->format('ym').'2';
        if(!DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table_dum) ){
            return null;
        }
        return DB::connection('srv_prologos')
                    ->table($table_dum)
                    ->whereRaw('TIPOPER LIKE \'SV%\'')
                    ->where('TIPOPER', '!=', 'SV?')
                    ->get();
    }

}
