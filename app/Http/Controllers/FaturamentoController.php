<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Auth;
use App\User;
use App\Cliente;
use App\ClienteNasajon;
use App\FaturamentoOnline;
use App\FaturamentoPrevisto;
use App\NotasCanceladasNasajon;

use App\Http\Controllers\UserController;

use App\Http\Requests\FaturamentoRequest;

class FaturamentoController extends Controller
{
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
        if(Auth::user()->hasPermissionTo("programas App\Faturamento") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Faturamento');
        return view('programs.faturamento.index');
    }

    public function filter(FaturamentoRequest $request){
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 120);
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);

        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }

        $notas = [];
        $data = date('Y-m-d', strtotime($data_busca));

        $result_total = [
            'dia' => 0.0,
            'mes' => 0.0,
            'ano' => 0.0
        ];

        $notas_canceladas = NotasCanceladasNasajon::where('emissao',$data)->get()->pluck('id')->toArray();
        
        $estabelecimentos = returnEmpresasNasajonView();
        if(strlen($fields['estabelecimento']) === 0){
            foreach($estabelecimentos as $key => $value){
                $estabelecimento = str_pad($key, 2, "0", STR_PAD_LEFT);
                if(isset($fields['devolucao']) && isset($fields['prepago'])){
                    $devolucao_dia_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "DEV%")->
                            orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                    $devolucao_mes_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "DEV%")->
                            orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                    $devolucao_ano_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "DEV%")->
                            orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                    $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                        where("estabelecimento", $estabelecimento)->
                        whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->
                        toArray();

                    $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $devolucao_dia = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->whereNotIn("nota_uuid",$notas_canceladas)->where("tipo_operacao", "like", "ED%")->get()->toArray();
                    $devolucao_mes = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->whereNotIn("nota_uuid",$notas_canceladas)->where("tipo_operacao", "like", "ED%")->get()->toArray();
                    $devolucao_ano = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->whereNotIn("nota_uuid",$notas_canceladas)->where("tipo_operacao", "like", "ED%")->get()->toArray();
                    
                    $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"])) - (floatval($devolucao_dia[0]["total"]) + floatval($devolucao_dia_nasajon[0]["total"]));
                    $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"])) - (floatval($devolucao_mes[0]["total"]) + floatval($devolucao_mes_nasajon[0]["total"]));
                    $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"])) - (floatval($devolucao_ano[0]["total"]) + floatval($devolucao_ano_nasajon[0]["total"]));
                   
                }else if(isset($fields['prepago'])){
                    $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                        where("estabelecimento", $estabelecimento)->
                        whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->
                        toArray();

                    $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $devolucao_dia_nasajon = '';
                    $devolucao_mes_nasajon = '';
                    $devolucao_ano_nasajon = '';

                    $devolucao_dia = '';
                    $devolucao_mes = '';
                    $devolucao_ano = '';

                    $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->
                        whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                        where("tipo_operacao", "like", "SV%")->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();
                    $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                        where("estabelecimento", $estabelecimento)->
                        whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                        where("tipo_operacao", "like", "SV%")->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();
                    $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                        where("estabelecimento", $estabelecimento)->
                        whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                        where("tipo_operacao", "like", "SV%")->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"]));
                    $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"]));
                    $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"]));
                   
                }else if(isset($fields['devolucao'])){
                    $devolucao_dia_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "DEV%")->
                            orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                    $devolucao_mes_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "DEV%")->
                            orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                    $devolucao_ano_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "DEV%")->
                            orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                    $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();

                    $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
    
                    $devolucao_dia = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereNotIn("nota_uuid",$notas_canceladas)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "ED%")->get()->toArray();
                    $devolucao_mes = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereNotIn("nota_uuid",$notas_canceladas)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "ED%")->get()->toArray();
                    $devolucao_ano = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereNotIn("nota_uuid",$notas_canceladas)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "ED%")->get()->toArray();
                    
                    $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"])) - (floatval($devolucao_dia[0]["total"]) + floatval($devolucao_dia_nasajon[0]["total"]));
                    $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"])) - (floatval($devolucao_mes[0]["total"]) + floatval($devolucao_mes_nasajon[0]["total"]));
                    $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"])) - (floatval($devolucao_ano[0]["total"]) + floatval($devolucao_ano_nasajon[0]["total"]));

                }else{
                    $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();

                    $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();

                    $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                        where(function($query){
                            $query->
                                orWhere("tipo_operacao", "like", "VENDA%")->
                                orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                                orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                        })->
                        where("nasajon", true)->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->toArray();
                    
                    $devolucao_dia_nasajon = '';
                    $devolucao_mes_nasajon = '';
                    $devolucao_ano_nasajon = '';

                    $devolucao_dia = '';
                    $devolucao_mes = '';
                    $devolucao_ano = '';

                    $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                    $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();

                    $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"]));
                    $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"]));
                    $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"]));
                }
                
                unset($compra_dia);
                unset($compra_dia_nasajon);
                unset($devolucao_dia);
                unset($devolucao_dia_nasajon);
                unset($compra_mes);
                unset($compra_mes_nasajon);
                unset($devolucao_mes);
                unset($devolucao_mes_nasajon);
                unset($compra_ano);
                unset($compra_ano_nasajon);
                unset($devolucao_ano);
                unset($devolucao_ano_nasajon);

                $result_total['dia'] += $total_dia;
                $result_total['mes'] += $total_mes;
                $result_total['ano'] += $total_ano;
                if(empty($total_dia) && empty($total_mes) && empty($total_ano)){
                    continue;
                }
                $notas[] = [
                    'estabelecimento' => $estabelecimentos[intval($estabelecimento)],
                    'estabelecimento_not_parse' => $estabelecimento,
                    'dia' => empty($total_dia) ? "" : parserValor($total_dia),
                    'mes' => empty($total_mes) ? "" : parserValor($total_mes),
                    'ano' => empty($total_ano) ? "" : parserValor($total_ano),
                ];
            }
        }else{
            $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
            if(isset($fields['devolucao']) && isset($fields['prepago'])){
                $devolucao_dia_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                where("estabelecimento", $estabelecimento)->
                whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "DEV%")->
                        orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->toArray();

                $devolucao_mes_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                where("estabelecimento", $estabelecimento)->
                whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "DEV%")->
                        orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->toArray();

                $devolucao_ano_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "DEV%")->
                        orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->toArray();

                $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();

                $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->whereNotIn("nota_uuid",$notas_canceladas)->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->get()->toArray();
                $devolucao_dia = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->whereNotIn("nota_uuid",$notas_canceladas)->where("tipo_operacao", "like", "ED%")->get()->toArray();
                $devolucao_mes = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "ED%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $devolucao_ano = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "ED%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                
                $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"])) - (floatval($devolucao_dia[0]["total"]) + floatval($devolucao_dia_nasajon[0]["total"]));
                $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"])) - (floatval($devolucao_mes[0]["total"]) + floatval($devolucao_mes_nasajon[0]["total"]));
                $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"])) - (floatval($devolucao_ano[0]["total"]) + floatval($devolucao_ano_nasajon[0]["total"]));
               
            }else if(isset($fields['prepago'])){
                $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    get()->
                    toArray();

                $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $devolucao_dia_nasajon = '';
                $devolucao_mes_nasajon = '';
                $devolucao_ano_nasajon = '';

                $devolucao_dia = '';
                $devolucao_mes = '';
                $devolucao_ano = '';

                $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                    where("tipo_operacao", "like", "SV%")->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();
                $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where("tipo_operacao", "like", "SV%")->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();
                $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) + SUM( valor_prepago )) - SUM( valor_troco )) as total"))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    where("tipo_operacao", "like", "SV%")->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"]));
                $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"]));
                $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"]));
               
            }else if(isset($fields['devolucao'])){
                $devolucao_dia_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                where("estabelecimento", $estabelecimento)->
                whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "DEV%")->
                        orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->toArray();

                $devolucao_mes_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->
                where("estabelecimento", $estabelecimento)->
                whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "DEV%")->
                        orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->toArray();

                $devolucao_ano_nasajon = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "DEV%")->
                        orWhere("tipo_operacao", "like", "ENTRADADEVEXPORTA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->toArray();

                $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->
                where("estabelecimento", $estabelecimento)->
                whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "VENDA%")->
                        orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                        orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->
                toArray();

                $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "SV%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();

                $devolucao_dia = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "ED%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $devolucao_mes = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "ED%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $devolucao_ano = FaturamentoOnline::select(DB::raw("SUM( valor_compra ) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-31", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "ED%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();

                $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"])) - (floatval($devolucao_dia[0]["total"]) + floatval($devolucao_dia_nasajon[0]["total"]));
                $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"])) - (floatval($devolucao_mes[0]["total"]) + floatval($devolucao_mes_nasajon[0]["total"]));
                $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"])) - (floatval($devolucao_ano[0]["total"]) + floatval($devolucao_ano_nasajon[0]["total"]));

            }else{
                $compra_dia_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->
                where("estabelecimento", $estabelecimento)->
                whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->
                where(function($query){
                    $query->
                        orWhere("tipo_operacao", "like", "VENDA%")->
                        orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                        orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                })->
                where("nasajon", true)->
                whereNotIn("nota_uuid",$notas_canceladas)->
                get()->
                toArray();

                $compra_mes_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();

                $compra_ano_nasajon = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    where(function($query){
                        $query->
                            orWhere("tipo_operacao", "like", "VENDA%")->
                            orWhere("tipo_operacao", "like", "REMESSAFIMEXPOR")->
                            orWhere("tipo_operacao", "like", "SIMPLESFATFUTURA");
                    })->
                    where("nasajon", true)->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->toArray();
                
                $devolucao_dia_nasajon = '';
                $devolucao_mes_nasajon = '';
                $devolucao_ano_nasajon = '';

                $devolucao_dia = '';
                $devolucao_mes = '';
                $devolucao_ano = '';

                $compra_dia = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [$data.' 00:00:00', $data.' 23:59:59'])->where("tipo_operacao", "like", "SV%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $compra_mes = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();
                $compra_ano = FaturamentoOnline::select(DB::raw("(( SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi )) - SUM( valor_troco )) as total"))->where("estabelecimento", $estabelecimento)->whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->where("tipo_operacao", "like", "SV%")->whereNotIn("nota_uuid",$notas_canceladas)->get()->toArray();

                $total_dia = (floatval($compra_dia[0]["total"]) + floatval($compra_dia_nasajon[0]["total"]));
                $total_mes = (floatval($compra_mes[0]["total"]) + floatval($compra_mes_nasajon[0]["total"]));
                $total_ano = (floatval($compra_ano[0]["total"]) + floatval($compra_ano_nasajon[0]["total"]));
            }
            

            unset($compra_dia);
            unset($compra_dia_nasajon);
            unset($devolucao_dia);
            unset($devolucao_dia_nasajon);
            unset($compra_mes);
            unset($compra_mes_nasajon);
            unset($devolucao_mes);
            unset($devolucao_mes_nasajon);
            unset($compra_ano);
            unset($compra_ano_nasajon);
            unset($devolucao_ano);
            unset($devolucao_ano_nasajon);

            $result_total['dia'] += $total_dia;
            $result_total['mes'] += $total_mes;
            $result_total['ano'] += $total_ano;
            if(!empty($total_dia) || !empty($total_mes) || !empty($total_ano)){
                $notas[] = [
                    'estabelecimento' => $estabelecimentos[intval($estabelecimento)],
                    'estabelecimento_not_parse' => $estabelecimento,
                    'dia' => empty($total_dia) ? "" : parserValor($total_dia),
                    'mes' => empty($total_mes) ? "" : parserValor($total_mes),
                    'ano' => empty($total_ano) ? "" : parserValor($total_ano),
                ];
            }
        }

        $result_total = [
            'dia' => (empty($result_total['dia']) ? "" : parserValor($result_total['dia']) ),
            'mes' => (empty($result_total['mes']) ? "" : parserValor($result_total['mes']) ),
            'ano' => (empty($result_total['ano']) ? "" : parserValor($result_total['ano']) ),
        ];

        $return = [
            'status' => 'success', /// success, error
            'message' => '', /// mensagem
            'error' => [],
            'response' => [
                'dados' => $notas,
                'total' => $result_total
            ]
        ];
        return response()->json($return);
    }

    private function createSqlCompraPrologos($estabelecimento, $data){

        $timestamp = strtotime($data);

        $table_dum = 'DUM' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";
        $table_dui = 'DUI' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";
        $table_duf = 'DUF' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";

        if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table_dum)){
            return DB::connection('srv_prologos')->table($table_dum)->
                select(DB::raw("(( SUM( {$table_dui}.PRECOTOT ) + SUM( {$table_dui}.VALOR_FRETE ) + SUM( {$table_dui}.VALOR_IPI )) - SUM( {$table_duf}.FALTA_TROCO )) as total"))->
                select(DB::raw("'". $estabelecimento . "' as estabelecimento"))->
                leftJoin($table_dui, function($join) use ($table_dum, $table_dui){
                    $join->on("{$table_dum}.NUMDOC",'=', "{$table_dui}.NUMDOC");
                    $join->on("{$table_dum}.CODCAD",'=', "{$table_dui}.CODCAD");
                })->
                leftJoin($table_duf, function($join) use ($table_dum, $table_duf){
                    $join->on("{$table_dum}.NUMDOC",'=', "{$table_duf}.NUMDOC");
                    $join->on("{$table_dum}.CODCAD",'=', "{$table_duf}.CODCAD");
                })->
                where("{$table_dum}.STATDOC", '!=', "C")->
                where("{$table_dum}.FLAGCV", '!=', "N")->
                where("{$table_dum}.FLAGEV", '!=', "N")->
                where(function($query) use ($table_dum){
                    $query->where("{$table_dum}.TIPOPER", '!=', 'SO@');
                    $query->where("{$table_dum}.TIPOPER", '!=', 'EDB');
                })->
                where("{$table_dum}.TIPOPER", 'like', "SV%")->
                where("{$table_dum}.CODCAD", '<>', '0050758840002')->
                where("{$table_dum}.CODCAD", '<>', '0063112740002')->
                where("{$table_dum}.CODCAD", '<>', '0063112740003')->
                where("{$table_dum}.CODCAD", '<>', '0050758840001');
        }
        else{
            return null;
        }
    }
    private function createSqlDevolucaoPrologos($estabelecimento, $data){

        $timestamp = strtotime($data);

        $table_dum = 'DUM' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";

        if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table_dum)){
            return DB::connection('srv_prologos')->table($table_dum)->
                select(DB::raw("SUM({$table_dum}.VALTOTDOC) as total"))->
                select(DB::raw("'". $estabelecimento . "' as estabelecimento"))->
                where("{$table_dum}.STATDOC", '!=', "C")->
                where("{$table_dum}.FLAGCV", '!=', "N")->
                where("{$table_dum}.FLAGEV", '!=', "N")->
                where(function($query) use ($table_dum){
                    $query->where("{$table_dum}.TIPOPER", '!=', 'SO@');
                    $query->where("{$table_dum}.TIPOPER", '!=', 'EDB');
                })->
                where("{$table_dum}.TIPOPER", 'like', "ED%")->
                where("{$table_dum}.CODCAD", '<>', '0050758840002')->
                where("{$table_dum}.CODCAD", '<>', '0063112740002')->
                where("{$table_dum}.CODCAD", '<>', '0063112740003')->
                where("{$table_dum}.CODCAD", '<>', '0050758840001');
        }
        else{
            return null;
        }
    }

    public function dialogDia(Request $request){
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);
        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data = date('Y-m-d', strtotime($data_busca));

        $notas_canceladas = NotasCanceladasNasajon::where('emissao',$data)->get()->pluck('id')->toArray();
        
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = empty($fields['estabelecimento'])? '' : str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        if($fields['devolucao'] == 'true' && $fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }            
        }else if($fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                }else{
                    $dados = FaturamentoOnline::
                        select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                        where("estabelecimento", $estabelecimento)->
                        whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                        groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                        whereNotIn("nota_uuid",$notas_canceladas)->
                        get()->
                        toArray();
                }   
        }else if ($fields['devolucao'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra)) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra)) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            } 
        }else{
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            } 
        }
        $return = $this->transformDados($dados);
        return view('programs.faturamento.dialog.index')->with(['return' => $return]);
    }

    public function dialogMes(Request $request){
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);

        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data = date('Y-m-d', strtotime($data_busca));

        $notas_canceladas = NotasCanceladasNasajon::whereBetween('emissao', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->get()->pluck('id')->toArray();

        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = empty($fields['estabelecimento'])? '' : str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);

        if($fields['prepago'] == 'true' && $fields['devolucao'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }                
        }else if($fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
         }else if ($fields['devolucao'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra)) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra)) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
        }else{
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
        }
        $return = $this->transformDados($dados);
        return view('programs.faturamento.dialog.index')->with(['return' => $return]);
    }

    public function dialogAnoAnalise(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);

        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data_atual = date('Y-m-d', strtotime($data_busca));
        $ano_atual = date('Y', strtotime($data_busca));

        $data_antes = date('Y-m-d', strtotime('-1 Year', strtotime($data_atual)));
        $ano_antes = date('Y', strtotime('-1 Year', strtotime($data_atual)));

        $notas_canceladas = NotasCanceladasNasajon::whereBetween('emissao', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->get()->pluck('id')->toArray();
        
        $mes = date('m', strtotime($data_busca));
        if($fields['prepago'] == 'true' && $fields['devolucao'] == 'true'){
            if(!empty($fields['estabelecimento'])){
                $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
        }else if ($fields['prepago'] == 'true'){
            if(!empty($fields['estabelecimento'])){
                $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
        }else if ($fields['devolucao'] == 'true'){
            if(!empty($fields['estabelecimento'])){
                $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra)  as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
        }else{
            if(!empty($fields['estabelecimento'])){
                $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }else{
                $dados_antes = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra)  as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_antes)).' 00:00:00', date("Y-12-31", strtotime($data_antes)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
                $dados_atual = FaturamentoOnline::
                    select('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data") as mes'), DB::raw('extract(YEAR FROM "data") as ano'), DB::raw('COUNT(*) as pedidos'),  DB::raw('SUM(valor_compra) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-31", strtotime($data_atual)).' 23:59:59'])->
                    groupBy('tipo_operacao', 'nasajon', DB::raw('extract(MONTH FROM "data")'), DB::raw('extract(YEAR FROM "data")'))->
                    orderBy(DB::raw('extract(YEAR FROM "data")'), 'asc', DB::raw('extract(MONTH FROM "data")'), 'asc')->
                    whereNotIn("nota_uuid",$notas_canceladas)->
                    get()->
                    toArray();
            }
        }

        $return = $this->transformDadosAnalise($dados_antes, $dados_atual, $ano_antes, $ano_atual, $mes);
        unset($dados_antes, $dados_atual);
        $meses = [];
        for($i = 1; $i <= 12; $i++){
            $meses[$i] = parserNameMonthFull($i);
        }
        
        $valor_zerado_objetivo = 0;
        $data_atual = Carbon::now();
        if(empty($fields['estabelecimento'])){
            $query_faturamento_previsto = FaturamentoPrevisto::select();
            $query_faturamento_previsto->whereBetween('data', [date("Y-01-01", strtotime($data_atual)).' 00:00:00', date("Y-12-30", strtotime($data_atual)).' 23:59:59']);
            $result_faturamento_previsto = $query_faturamento_previsto->get();
            $total_objetivo = 0;
            $total_parcial = 0;
            $total_parcial_anterior = 0;
            foreach ($result_faturamento_previsto as $faturamento_previsto) {
                $total = 0;
                $total = ($faturamento_previsto->nacional_valor + $faturamento_previsto->importado_valor);
                $mes = str_pad($faturamento_previsto->data->month, 2, 0, STR_PAD_LEFT);
                $return['retorno_dados'][$mes]['objetivo'] = parserValor($total);
                $return['retorno_tabela']['objetivo'][intval($mes)] = $total;
                $return['retorno_dados'][$mes]['objetivo_atingido'] = empty(parserNumber($return['retorno_dados'][$mes]['ano_atual']))? 0 : ((((parserNumber($return['retorno_dados'][$mes]['ano_atual']))/$total)*100) - 100);
                if(empty(parserNumber($return['retorno_dados'][$mes]['ano_atual']))){
                    $return['retorno_dados'][$mes]['objetivo_atingido'] = '';
                }else{
                    $return['retorno_dados'][$mes]['objetivo_atingido'] =  $return['retorno_dados'][$mes]['objetivo_atingido'] > 0 ? "+".parserValor($return['retorno_dados'][$mes]['objetivo_atingido'])." %": parserValor($return['retorno_dados'][$mes]['objetivo_atingido'])." %";
                }
                $total_objetivo += $total;
                $total_parcial += empty(parserNumber($return['retorno_dados'][$mes]['ano_atual']))? 0 : $total;
                if((intval($data_atual->month) - 1) >= $faturamento_previsto->data->month){
                    $total_parcial_anterior += empty(parserNumber($return['retorno_dados'][$mes]['ano_atual']))? 0 : $total;
                }                
            }

            for($i = 1; $i <= 12; $i++){
                $mes = str_pad($i, 2, 0, STR_PAD_LEFT);
                if(empty($return['retorno_dados'][$mes]['objetivo'])){
                    $return['retorno_dados'][$mes]['objetivo'] = '';
                }
                if(empty($return['retorno_dados'][$mes]['objetivo_atingido'])){
                    $return['retorno_dados'][$mes]['objetivo_atingido'] = '';
                }
            }
            $valor_zerado_objetivo = $total_objetivo;

            $total_ano_objetivo_atingido = empty(parserNumber($return['total_ano']['ano_atual'])) || empty($total_objetivo)? 0 : ((((parserNumber($return['total_ano']['ano_atual']))/$total_objetivo) -1) *100);
            $total_mes_objetivo_atingido = empty(parserNumber($return['total_mes']['ano_atual'])) || empty($total_parcial)? 0 : ((((parserNumber($return['total_mes']['ano_atual']))/$total_parcial) -1)*100);
            $total_mes_anterior_objetivo_atingido = empty(parserNumber($return['total_mes_anterior']['ano_atual'])) || empty($total_parcial_anterior)? 0 : ((((parserNumber($return['total_mes_anterior']['ano_atual']))/$total_parcial_anterior) -1)*100);

            $return['total_ano']['objetivo'] = parserValor($total_objetivo);
            $return['total_mes']['objetivo'] = parserValor($total_parcial);
            $return['total_mes_anterior']['objetivo'] = parserValor($total_parcial_anterior);
            $return['total_ano']['objetivo_atingido'] = $total_ano_objetivo_atingido > 0? '+'.parserValor($total_ano_objetivo_atingido).'%' : parserValor($total_ano_objetivo_atingido).'%';
            $return['total_mes']['objetivo_atingido'] = $total_mes_objetivo_atingido > 0? '+'.parserValor($total_mes_objetivo_atingido).'%' : parserValor($total_mes_objetivo_atingido).'%';
            $return['total_mes_anterior']['objetivo_atingido'] = $total_mes_anterior_objetivo_atingido > 0? '+'.parserValor($total_mes_anterior_objetivo_atingido).'%' : parserValor($total_mes_anterior_objetivo_atingido).'%';
        }

        for($i = 1; $i <= 12; $i++){
            $mes = str_pad($i, 2, 0, STR_PAD_LEFT);
            if(empty($return['retorno_dados'][$mes]['ano_atual'])){
                $return['retorno_dados'][$mes]['ano_atual'] = '';
                $return['retorno_dados'][$mes]['porcentagem'] = '';
            }
        }
        
        return view('programs.faturamento.dialog.analise')->with(['return' => $return, 'ano_antes' => $ano_antes,'ano_atual' => $ano_atual, 'meses' => $meses, 'mes_atual' => $data_atual->month, 'valor_zerado_objetivo' => $valor_zerado_objetivo]);
    }

    public function dialogAno(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);

        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data = date('Y-m-d', strtotime($data_busca));

        $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);

        if($fields['prepago'] == 'true' && $fields['devolucao'] == 'true'){
            $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    get()->
                    toArray();
        }else if($fields['devolucao'] == 'true'){
            $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra)) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    get()->
                    toArray();
        }else if ($fields['prepago'] == 'true'){
            $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra) + SUM( valor_prepago )) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%') ->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    get()->
                    toArray();
        }else{
            $dados = FaturamentoOnline::
                    select('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data', DB::raw('COUNT(*) as pedidos'),  DB::raw('(SUM(valor_compra)) as valor_compra'), DB::raw('SUM(valor_frete) as valor_frete'), DB::raw('SUM(valor_ipi) as valor_ipi'), DB::raw('SUM(valor_troco) as valor_troco'))->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%') ->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    groupBy('estabelecimento', 'codigo_cadastro', 'tipo_operacao', 'nasajon', 'nome_cliente', 'data')->
                    get()->
                    toArray();
        }

        $return = $this->transformDados($dados);
        
        return view('programs.faturamento.dialog.index')->with(['return' => $return]);
    }

    private function transformDados($dados){
        $clientes = [];
        foreach ($dados as $key => $value) {
            if(!isset($clientes[$value["codigo_cadastro"]])){
                $clientes[$value["codigo_cadastro"]] = [
                    'pedidos' => [],
                    'canceladas' => []
                ];
            }
            if(
                $value['nasajon'] === true
            ){
                if(substr($value['tipo_operacao'], 0, 5) === 'VENDA' || $value['tipo_operacao'] ==='SIMPLESFATFUTURA' || $value['tipo_operacao'] ==='REMESSAFIMEXPOR'){
                    $clientes[$value["codigo_cadastro"]]['pedidos'][] = $value;
                }else if(substr($value['tipo_operacao'], 0, 3) === 'DEV' || $value['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                    $clientes[$value["codigo_cadastro"]]['canceladas'][] = $value;
                }
            }else{
                if(substr($value['tipo_operacao'], 0, 2) == 'SV'){
                    $clientes[$value["codigo_cadastro"]]['pedidos'][] = $value;
                }else{
                    $clientes[$value["codigo_cadastro"]]['canceladas'][] = $value;
                }
            }
        }
        $return = [];
        foreach ($clientes as $codigo => $cliente) {
            if(empty($cliente['pedidos']) && empty($cliente['canceladas'])){
                continue;
            }
            $valor_compra = 0.0;
            $valor_devolucao = 0.0;
            $valor_total = 0.0;
            $nasajon = false;
            $cliente_nome = $codigo;
            foreach ($cliente['pedidos'] as $key => $value) {
                $nasajon = boolval($value['nasajon']);
                $cliente_nome = $value['nome_cliente'];
                $valor_compra += (floatval($value['valor_compra']) + floatval($value['valor_frete']) + floatval($value['valor_ipi'])) - floatval($value['valor_troco']);
                $valor_total += (floatval($value['valor_compra']) + floatval($value['valor_frete']) + floatval($value['valor_ipi'])) - floatval($value['valor_troco']);
            }
            foreach ($cliente['canceladas'] as $key => $value) {
                $nasajon = boolval($value['nasajon']);
                $cliente_nome = $value['nome_cliente'];
                $valor_devolucao += floatval($value['valor_compra']);
                $valor_total -= floatval($value['valor_compra']);
            }

            if($nasajon === false){
                $cliente_nasajon = ClienteNasajon::where('codigo', $codigo)->first();
                if(!empty($cliente_nasajon)){
                    $cliente_nome = $cliente_nasajon->nome;
                }   
            }
            $return[] = [
                'cliente' => $cliente_nome,
                'total_compra' => (!empty($valor_compra)) ? ($valor_compra) : '',
                'total_devolucao' => (!empty($valor_devolucao)) ? ($valor_devolucao) : '',
                'total' => ($valor_total),
            ];
        }
        return $return;
    }

    private function transformDadosAnalise($dados_antes, $dados_atual, $ano_antes, $ano_atual, $mes_atual){
        $agrupando = [];
        foreach ($dados_antes as $key => $value) {
            if(!isset($agrupando['ano_antes'][$value["mes"]])){
                $agrupando['ano_antes'][$value["mes"]] = [
                    'pedidos' => [],
                    'canceladas' => []
                ];
            }
            if(
                $value['nasajon'] === true
            ){
                if(substr($value['tipo_operacao'], 0, 5) === 'VENDA'|| $value['tipo_operacao'] ==='SIMPLESFATFUTURA' || $value['tipo_operacao'] ==='REMESSAFIMEXPOR'){
                    $agrupando['ano_antes'][$value["mes"]]['pedidos'][] = $value;
                }else if(substr($value['tipo_operacao'], 0, 3) === 'DEV' || $value['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                    $agrupando['ano_antes'][$value["mes"]]['canceladas'][] = $value;
                }
            }else{
                if(substr($value['tipo_operacao'], 0, 2) == 'SV'){
                    $agrupando['ano_antes'][$value["mes"]]['pedidos'][] = $value;
                }else{
                    $agrupando['ano_antes'][$value["mes"]]['canceladas'][] = $value;
                }
            }
        }
        unset($dados_antes);
        foreach ($dados_atual as $key => $value) {
            if(!isset($agrupando['ano_atual'][$value["mes"]])){
                $agrupando['ano_atual'][$value["mes"]] = [
                    'pedidos' => [],
                    'canceladas' => []
                ];
            }
            if(
                $value['nasajon'] === true
            ){
                if(substr($value['tipo_operacao'], 0, 5) === 'VENDA' || $value['tipo_operacao'] ==='SIMPLESFATFUTURA' || $value['tipo_operacao'] ==='REMESSAFIMEXPOR'){
                    $agrupando['ano_atual'][$value["mes"]]['pedidos'][] = $value;
                }else if(substr($value['tipo_operacao'], 0, 3) === 'DEV' || $value['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                    $agrupando['ano_atual'][$value["mes"]]['canceladas'][] = $value;
                }
            }else{
                if(substr($value['tipo_operacao'], 0, 2) == 'SV'){
                    $agrupando['ano_atual'][$value["mes"]]['pedidos'][] = $value;
                }else{
                    $agrupando['ano_atual'][$value["mes"]]['canceladas'][] = $value;
                }
            }
        }
        unset($dados_atual);
        $retorno_dados = [];
        $retorno_tabela = [];
        
        $total_mes_anterior = [];
        $total_mes = [];
        $total_ano = [];

        $label_tabela = [];
        foreach ($agrupando as $ano => $meses) {
            ksort($meses);
            foreach ($meses as $mes => $valores) {
                $valor_total = 0;
                $mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
                foreach ($valores['pedidos'] as $key => $value) {
                    $valor_total += (floatval($value['valor_compra']) + floatval($value['valor_frete']) + floatval($value['valor_ipi'])) - floatval($value['valor_troco']);
                }
                foreach ($valores['canceladas'] as $key => $value) {
                    $valor_total -= floatval($value['valor_compra']);
                }
                $retorno_dados[$mes][$ano] = parserValor($valor_total);
                $retorno_tabela[$ano][intval($mes)] = (float) number_format($valor_total, 2, '.', '' );
                $label_tabela[intval($mes)] = parserNameMonthFull($mes);

                if(!isset($total_mes[$ano])){
                    $total_mes[$ano] = 0;
                }
                if(!isset($total_ano[$ano])){
                    $total_ano[$ano] = 0;
                }
                if(!isset($total_mes_anterior[$ano])){
                    $total_mes_anterior[$ano] = 0;
                }
                $total_ano[$ano] += $valor_total;
                if(intval($mes) <= intval($mes_atual)){
                    $total_mes[$ano] += $valor_total;
                }
                if(intval($mes) <= (intval($mes_atual) - 1)){
                    $total_mes_anterior[$ano] += $valor_total;
                }
            }
        }
        unset($dados);
        ksort($label_tabela);
        ksort($retorno_dados);
        foreach ($retorno_dados as $mes => $anos) {
            if(!array_key_exists('ano_antes', $anos)){
                $retorno_dados[$mes]['ano_antes'] = 0;
            }
            if(!array_key_exists('ano_atual', $anos)){
                $retorno_dados[$mes]['ano_atual'] = 0;
            }
            $valor_ano_antes = parserNumber($retorno_dados[$mes]['ano_antes']);
            if($valor_ano_antes > 0){
                $valor_ano_atual = parserNumber($retorno_dados[$mes]['ano_atual']);
                $diff = ((($valor_ano_atual - $valor_ano_antes) / $valor_ano_antes) * 100);
                if($diff > 0){
                    $diff = '+'. parserQtd($diff);
                }else{
                    $diff = parserQtd($diff);
                }
            }else{
                $diff = '0';
            }
            $retorno_dados[$mes]['porcentagem'] = $diff. ' %';
        }
        if(!isset($total_mes['ano_antes'])){
            $total_mes['ano_antes'] = 0;
        }
        $total_mes['ano_antes'] = parserValor($total_mes['ano_antes']);
        $total_mes['ano_atual'] = parserValor($total_mes['ano_atual']);

        if(!isset($total_mes_anterior['ano_antes'])){
            $total_mes_anterior['ano_antes'] = 0;
        }
        $total_mes_anterior['ano_antes'] = parserValor($total_mes_anterior['ano_antes']);
        $total_mes_anterior['ano_atual'] = parserValor($total_mes_anterior['ano_atual']);

        if(!isset($total_ano['ano_antes'])){
            $total_ano['ano_antes'] = 0;
        }
        $total_ano['ano_antes'] = parserValor($total_ano['ano_antes']);
        $total_ano['ano_atual'] = parserValor($total_ano['ano_atual']);

        $valor_ano_antes = parserNumber($total_ano['ano_antes']);
        if($valor_ano_antes > 0){
            $valor_ano_atual = parserNumber($total_ano['ano_atual']);
            $diff = ((($valor_ano_atual - $valor_ano_antes) / $valor_ano_antes) * 100);
            if($diff > 0){
                $diff = '+'. parserQtd($diff);
            }else{
                $diff = parserQtd($diff);
            }
        }
        else{
            $diff = '0';
        }
        $total_ano['porcentagem'] = $diff. ' %';

        $valor_ano_antes = parserNumber($total_mes['ano_antes']);
        if($valor_ano_antes > 0){
            $valor_ano_atual = parserNumber($total_mes['ano_atual']);
            $diff = ((($valor_ano_atual - $valor_ano_antes) / $valor_ano_antes) * 100);
            if($diff > 0){
                $diff = '+'. parserQtd($diff);
            }else{
                $diff = parserQtd($diff);
            }
        }
        else{
            $diff = '0';
        }
        $total_mes['porcentagem'] = $diff. ' %';

        $valor_ano_antes = parserNumber($total_mes_anterior['ano_antes']);
        if($valor_ano_antes > 0){
            $valor_ano_atual = parserNumber($total_mes_anterior['ano_atual']);
            $diff = ((($valor_ano_atual - $valor_ano_antes) / $valor_ano_antes) * 100);
            if($diff > 0){
                $diff = '+'. parserQtd($diff);
            }else{
                $diff = parserQtd($diff);
            }
        }
        else{
            $diff = '0';
        }
        $total_mes_anterior['porcentagem'] = $diff. ' %';

        $retorno_tabela_antes = $retorno_tabela;
        $retorno_tabela = [];
        foreach ($retorno_tabela_antes as $key1 => $value1) {
            if($key1 === 'ano_antes'){
                $key1 = $ano_antes;
            }
            if($key1 === 'ano_atual'){
                $key1 = $ano_atual;
            }
            $retorno_tabela[$key1] = $value1;
        }
        unset($retorno_tabela_antes);
        $total_mes_anterior['objetivo'] = 0;
        $total_mes_anterior['objetivo_atingido'] = 0;

        return ['retorno_tabela' => $retorno_tabela, 'retorno_dados' => $retorno_dados, 'label_tabela' => $label_tabela, 'total_ano' => $total_ano, 'total_mes' => $total_mes, 'total_mes_anterior' => $total_mes_anterior];
    }

    public function dialogDiaFechamentoCaixa(Request $request){
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);
        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data = date('Y-m-d', strtotime($data_busca));

        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = empty($fields['estabelecimento'])? '' : str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        if($fields['devolucao'] == 'true' && $fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }            
        }else if($fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }   
        }else if ($fields['devolucao'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            } 
        }else{
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos','notasCanceladadas'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-d", strtotime($data)).' 00:00:00', date("Y-m-d", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            } 
        }

        $resultado = [];

        if(empty($fields['estabelecimento'])){
            $total = [
                'dinheiro' => 0,
                'cartao_debito' => 0,
                'cartao_credito' => 0,
                'duplicata' => 0,
                'carteira' => 0, 
                'usar_credito' => 0,
                'total' => 0,
                'prepago' => 0,
                'devolucao' => 0,
                'mercado_pago' => 0,
                'cartao_bndes' => 0,
                'pix_feira' => 0,
            ];

            foreach($dados as $dado){
                if(!empty($dado['notas_canceladadas'])){
                    continue;
                }
                if(empty($resultado[$dado['estabelecimento']])){
                    $resultado[$dado['estabelecimento']] = [
                        'estabelecimento' => $estabelecimentos[intval($dado['estabelecimento'])],
                        'dinheiro' => 0,
                        'cartao_debito' => 0,
                        'cartao_credito' => 0,
                        'duplicata' => 0,
                        'carteira' => 0, 
                        'usar_credito' => 0,
                        'total' => 0,
                        'prepago' => 0,
                        'devolucao' => 0,
                        'mercado_pago' => 0,
                        'cartao_bndes' => 0,
                        'pix_feira' => 0,
                    ];
                }

                if($fields['prepago'] == 'true'){
                    $resultado[$dado['estabelecimento']]['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $resultado[$dado['estabelecimento']]['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $total['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $total['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                }

                if($fields['devolucao'] == 'true'){
                    if(substr($dado['tipo_operacao'], 0, 3) === 'DEV' || $dado['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                        $resultado[$dado['estabelecimento']]['devolucao'] -= floatval($dado['valor_compra']);
                        $resultado[$dado['estabelecimento']]['total'] -= floatval($dado['valor_compra']);
                        $total['devolucao'] -= floatval($dado['valor_compra']);
                        $total['total'] -= floatval($dado['valor_compra']);
                    }
                }

                $verificacao_total = $dado['valor_compra'];
                
                foreach($dado['detalhes_condicoes_pagamentos'] as $condicao_pagamento){ 
                    if($condicao_pagamento['formapagamento_descricao'] == 'Boleto Bancário'){
                        $index = 'duplicata';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Usar Crédito'){
                        $index = 'usar_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Crédito'){
                        $index = 'cartao_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Débito'){
                        $index = 'cartao_debito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Dinheiro'){
                        $index = 'dinheiro';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Carteira'){
                        $index = 'carteira';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'MERCADO PAGO'){
                        $index = 'mercado_pago';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão BNDES'){
                        $index = 'cartao_bndes';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'PIX - FEIRA'){
                        $index = 'pix_feira';
                    }
                    if(count($dado['detalhes_condicoes_pagamentos']) == 1){
                        $valor_compra = $dado['valor_compra'];
                    }else{
                        if($verificacao_total > $condicao_pagamento['valor']){
                            $valor_compra = $condicao_pagamento['valor'];
                        }else{
                            $valor_compra = $verificacao_total;
                        }
                        $verificacao_total -= $condicao_pagamento['valor'];
                    }
                    $resultado[$dado['estabelecimento']][$index] += $valor_compra;
                    $resultado[$dado['estabelecimento']]['total'] += $valor_compra;
                    $total[$index] += $valor_compra;
                    $total['total'] += $valor_compra;
                }
            }

            $resultado = $this->ajusteArrayParaValores($resultado);
            $total = $this->ajusteArrayParaValoresFormatacao($total);

            return view('programs.faturamento.dialog.fechamento_caixa_dia_total')->with(['resultado' => $resultado, 'total' => $total, 'prepago' => $fields['prepago'], 'devolucao' => $fields['devolucao']]);
        }else{
            foreach($dados as $dado){
                if(!empty($dado['notas_canceladadas'])){
                    continue;
                }

                if(empty($resultado[$dado['estabelecimento']])){
                    $resultado[$dado['estabelecimento']] = [
                        'dinheiro' => 0,
                        'cartao_debito' => 0,
                        'cartao_credito' => 0,
                        'duplicata' => 0,
                        'carteira' => 0, 
                        'usar_credito' => 0,
                        'total' => 0,
                        'prepago' => 0,
                        'devolucao' => 0,
                        'mercado_pago' => 0,
                        'cartao_bndes' => 0,
                        'pix_feira' => 0,
                    ];
                }

                if($fields['prepago'] == 'true'){
                    $resultado[$dado['estabelecimento']]['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $resultado[$dado['estabelecimento']]['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                }

                if($fields['devolucao'] == 'true'){
                    if(substr($dado['tipo_operacao'], 0, 3) === 'DEV' || $dado['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                        $resultado[$dado['estabelecimento']]['devolucao'] -= floatval($dado['valor_compra']);
                        $resultado[$dado['estabelecimento']]['total'] -= floatval($dado['valor_compra']);
                    }
                }

                $verificacao_total = $dado['valor_compra'];

                foreach($dado['detalhes_condicoes_pagamentos'] as $condicao_pagamento){ 
                    if($condicao_pagamento['formapagamento_descricao'] == 'Boleto Bancário'){
                        $index = 'duplicata';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Usar Crédito'){
                        $index = 'usar_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Crédito'){
                        $index = 'cartao_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Débito'){
                        $index = 'cartao_debito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Dinheiro'){
                        $index = 'dinheiro';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Carteira'){
                        $index = 'carteira';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'MERCADO PAGO'){
                        $index = 'mercado_pago';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão BNDES'){
                        $index = 'cartao_bndes';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'PIX - FEIRA'){
                        $index = 'pix_feira';
                    }
                    if(count($dado['detalhes_condicoes_pagamentos']) == 1){
                        $valor_compra = $dado['valor_compra'];
                    }else{
                        if($verificacao_total > $condicao_pagamento['valor']){
                            $valor_compra = $condicao_pagamento['valor'];
                        }else{
                            $valor_compra = $verificacao_total;
                        }
                        $verificacao_total -= $condicao_pagamento['valor'];                        
                    }
                    $resultado[$dado['estabelecimento']][$index] += $valor_compra;
                    $resultado[$dado['estabelecimento']]['total'] += $valor_compra;
                }
            }

            $resultado = $this->ajusteArrayParaValores($resultado);
            return view('programs.faturamento.dialog.fechamento_caixa_dia')->with(['resultado' => $resultado, 'prepago' => $fields['prepago'], 'devolucao' => $fields['devolucao'], 'estabelecimento' => $dado['estabelecimento']]);
        }
    }

    public function dialogMesFechamentoCaixa(Request $request){
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);
        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data = date('Y-m-d', strtotime($data_busca));

        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = empty($fields['estabelecimento'])? '' : str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        if($fields['devolucao'] == 'true' && $fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }            
        }else if($fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }   
        }else if ($fields['devolucao'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            } 
        }else{
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-m-01", strtotime($data)).' 00:00:00', date("Y-m-t", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            } 
        }

        $resultado = [];

        if(empty($fields['estabelecimento'])){
            $total = [
                'dinheiro' => 0,
                'cartao_debito' => 0,
                'cartao_credito' => 0,
                'duplicata' => 0,
                'carteira' => 0, 
                'usar_credito' => 0,
                'total' => 0,
                'prepago' => 0,
                'devolucao' => 0,
                'mercado_pago' => 0,
                'cartao_bndes' => 0,
                'pix_feira' => 0,
            ];

            foreach($dados as $dado){
                if(empty($resultado[$dado['estabelecimento']][$dado['data']])){
                    $resultado[$dado['estabelecimento']][$dado['data']] = [
                        'estabelecimento' => $estabelecimentos[intval($dado['estabelecimento'])],
                        'data' => $dado['data'],
                        'data_descricao' => parserData($dado['data']),
                        'dinheiro' => 0,
                        'cartao_debito' => 0,
                        'cartao_credito' => 0,
                        'duplicata' => 0,
                        'carteira' => 0, 
                        'usar_credito' => 0,
                        'total' => 0,
                        'prepago' => 0,
                        'devolucao' => 0,
                        'mercado_pago' => 0,
                        'cartao_bndes' => 0,
                        'pix_feira' => 0,
                    ];
                }
                if($fields['prepago'] == 'true'){
                    $resultado[$dado['estabelecimento']][$dado['data']]['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $resultado[$dado['estabelecimento']][$dado['data']]['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $total['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $total['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                }

                if($fields['devolucao'] == 'true'){
                    if(substr($dado['tipo_operacao'], 0, 3) === 'DEV' || $dado['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                        $resultado[$dado['estabelecimento']][$dado['data']]['devolucao'] -= floatval($dado['valor_compra']);
                        $resultado[$dado['estabelecimento']][$dado['data']]['total'] -= floatval($dado['valor_compra']);
                        $total['devolucao'] -= floatval($dado['valor_compra']);
                        $total['total'] -= floatval($dado['valor_compra']);
                    }
                }

                foreach($dado['detalhes_condicoes_pagamentos'] as $condicao_pagamento){ 
                    if($condicao_pagamento['formapagamento_descricao'] == 'Boleto Bancário'){
                        $index = 'duplicata';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Usar Crédito'){
                        $index = 'usar_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Crédito'){
                        $index = 'cartao_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Débito'){
                        $index = 'cartao_debito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Dinheiro'){
                        $index = 'dinheiro';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Carteira'){
                        $index = 'carteira';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'MERCADO PAGO'){
                        $index = 'mercado_pago';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão BNDES'){
                        $index = 'cartao_bndes';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'PIX - FEIRA'){
                        $index = 'pix_feira';
                    }
                    
                    if(count($dado['detalhes_condicoes_pagamentos']) == 1){
                        $valor_compra = $dado['valor_compra'];
                    }else{
                        $valor_compra = $condicao_pagamento['valor'];
                    }

                    $resultado[$dado['estabelecimento']][$dado['data']][$index] += $valor_compra;
                    $resultado[$dado['estabelecimento']][$dado['data']]['total'] += $valor_compra;
                    $total[$index] += $valor_compra;
                    $total['total'] += $valor_compra;
                }
            }

            $resultado = $this->ajusteArrayParaValores($resultado);
            $total = $this->ajusteArrayParaValoresFormatacao($total);

            return view('programs.faturamento.dialog.fechamento_caixa_mes_total')->with(['resultado' => $resultado, 'total' => $total, 'prepago' => $fields['prepago'], 'devolucao' => $fields['devolucao']]);
        }else{
            $total = [
                'dinheiro' => 0,
                'cartao_debito' => 0,
                'cartao_credito' => 0,
                'duplicata' => 0,
                'carteira' => 0, 
                'usar_credito' => 0,
                'total' => 0,
                'prepago' => 0,
                'devolucao' => 0,
                'mercado_pago' => 0,
                'cartao_bndes' => 0,
                'pix_feira' => 0,
            ];

            foreach($dados as $dado){
                if(empty($resultado[$dado['estabelecimento']][$dado['data']])){
                    $resultado[$dado['estabelecimento']][$dado['data']] = [
                        'data' => $dado['data'],
                        'data_descricao' => parserData($dado['data']),
                        'dinheiro' => 0,
                        'cartao_debito' => 0,
                        'cartao_credito' => 0,
                        'duplicata' => 0,
                        'carteira' => 0, 
                        'usar_credito' => 0,
                        'total' => 0,
                        'prepago' => 0,
                        'devolucao' => 0,
                        'mercado_pago' => 0,
                        'cartao_bndes' => 0,
                        'pix_feira' => 0,
                    ];
                }
                if($fields['prepago'] == 'true'){
                    $resultado[$dado['estabelecimento']][$dado['data']]['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $resultado[$dado['estabelecimento']][$dado['data']]['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $total['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                    $total['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                }
                if($fields['devolucao'] == 'true'){
                    if(substr($dado['tipo_operacao'], 0, 3) === 'DEV' || $dado['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                        $resultado[$dado['estabelecimento']][$dado['data']]['devolucao'] -= floatval($dado['valor_compra']);
                        $resultado[$dado['estabelecimento']][$dado['data']]['total'] -= floatval($dado['valor_compra']);
                        $total['devolucao'] -= floatval($dado['valor_compra']);
                        $total['total'] -= floatval($dado['valor_compra']);
                    }
                }

                foreach($dado['detalhes_condicoes_pagamentos'] as $condicao_pagamento){ 
                    if($condicao_pagamento['formapagamento_descricao'] == 'Boleto Bancário'){
                        $index = 'duplicata';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Usar Crédito'){
                        $index = 'usar_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Crédito'){
                        $index = 'cartao_credito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Débito'){
                        $index = 'cartao_debito';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Dinheiro'){
                        $index = 'dinheiro';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Carteira'){
                        $index = 'carteira';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'MERCADO PAGO'){
                        $index = 'mercado_pago';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão BNDES'){
                        $index = 'cartao_bndes';
                    }else if($condicao_pagamento['formapagamento_descricao'] == 'PIX - FEIRA'){
                        $index = 'pix_feira';
                    }
                    $resultado[$dado['estabelecimento']][$dado['data']][$index] += $condicao_pagamento['valor'];
                    $resultado[$dado['estabelecimento']][$dado['data']]['total'] += $condicao_pagamento['valor'];
                    $total[$index] += $condicao_pagamento['valor'];
                    $total['total'] += $condicao_pagamento['valor'];
                }
            }

            $resultado = $this->ajusteArrayParaValores($resultado);
            $total = $this->ajusteArrayParaValoresFormatacao($total);
            return view('programs.faturamento.dialog.fechamento_caixa_mes')->with(['resultado' => $resultado, 'total' => $total, 'prepago' => $fields['prepago'], 'devolucao' => $fields['devolucao']]);
        }
    }

    public function dialogAnoFechamentoCaixa(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(['estabelecimento', 'data_busca', 'prepago', 'devolucao']);
        $data_busca = $fields['data_busca'];
        if(!empty($data_busca)){
            $data_busca = Carbon::createFromFormat('d/m/Y', $data_busca);
        } else {
            $data_busca = date('Y-m-d');
        }
        $data = date('Y-m-d', strtotime($data_busca));

        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = empty($fields['estabelecimento'])? '' : str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        if($fields['devolucao'] == 'true' && $fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    whereNotIn("estabelecimento", ['20'])->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }            
        }else if($fields['prepago'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    whereNotIn("estabelecimento", ['20'])->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }   
        }else if ($fields['devolucao'] == 'true'){
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    whereNotIn("estabelecimento", ['20'])->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            } 
        }else{
            if(empty($fields['estabelecimento'])){
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    whereNotIn("estabelecimento", ['20'])->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            }else{
                $dados = FaturamentoOnline::with(['detalhesCondicoesPagamentos'])->
                    select()->
                    where("estabelecimento", $estabelecimento)->
                    where('tipo_operacao', 'not like', 'DEV%')->
                    where('tipo_operacao', 'not like', 'ENTRADADEVEXPORTA')->
                    whereBetween('data', [date("Y-01-01", strtotime($data)).' 00:00:00', date("Y-12-30", strtotime($data)).' 23:59:59'])->
                    get()->
                    toArray();
            } 
        }

        $resultado = [];


        $total = [
            'dinheiro' => 0,
            'cartao_debito' => 0,
            'cartao_credito' => 0,
            'duplicata' => 0,
            'carteira' => 0, 
            'usar_credito' => 0,
            'total' => 0,
            'prepago' => 0,
            'devolucao' => 0,
            'mercado_pago' => 0,
            'cartao_bndes' => 0,
            'pix_feira' => 0,
        ];

        foreach($dados as $dado){
            $data = Carbon::parse($dado['data']);

            if(empty($resultado[$dado['estabelecimento']][$data->format('Y-m')])){
                $resultado[$dado['estabelecimento']][$data->format('Y-m')] = [
                    'estabelecimento' => $estabelecimentos[intval($dado['estabelecimento'])],
                    'data' => $data->format('Y-m'),
                    'data_descricao' => '<a href="#" onclick="openDialog2(this, event, \'Detalhes do faturamento do estabelecimento '.$estabelecimentos[intval($dado['estabelecimento'])].'\')" data-route="'.route('faturamento.dialog.mes_fechamento_caixa').'" data-data_busca="'.$data->format('d/m/Y').'" data-estabelecimento="'.$dado['estabelecimento'].'" data-prepago="true" data-devolucao="true">'.$data->format('m/Y').'</a>',
                    'dinheiro' => 0,
                    'cartao_debito' => 0,
                    'cartao_credito' => 0,
                    'duplicata' => 0,
                    'carteira' => 0, 
                    'usar_credito' => 0,
                    'total' => 0,
                    'prepago' => 0,
                    'devolucao' => 0,
                    'mercado_pago' => 0,
                    'cartao_bndes' => 0,
                    'pix_feira' => 0,
                ];
            }

            if($fields['prepago'] == 'true'){
                $resultado[$dado['estabelecimento']][$data->format('Y-m')]['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                $resultado[$dado['estabelecimento']][$data->format('Y-m')]['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                $total['prepago'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
                $total['total'] += empty($dado['valor_prepago'])? 0 : $dado['valor_prepago'];
            }     
            
            if($fields['devolucao'] == 'true'){
                if(substr($dado['tipo_operacao'], 0, 3) === 'DEV' || $dado['tipo_operacao'] ==='ENTRADADEVEXPORTA'){
                    $resultado[$dado['estabelecimento']][$data->format('Y-m')]['devolucao'] -= floatval($dado['valor_compra']);
                    $resultado[$dado['estabelecimento']][$data->format('Y-m')]['total'] -= floatval($dado['valor_compra']);
                    $total['devolucao'] -= floatval($dado['valor_compra']);
                    $total['total'] -= floatval($dado['valor_compra']);
                }
            }

            foreach($dado['detalhes_condicoes_pagamentos'] as $condicao_pagamento){ 
                if($condicao_pagamento['formapagamento_descricao'] == 'Boleto Bancário'){
                    $index = 'duplicata';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'Usar Crédito'){
                    $index = 'usar_credito';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Crédito'){
                    $index = 'cartao_credito';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão Débito'){
                    $index = 'cartao_debito';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'Dinheiro'){
                    $index = 'dinheiro';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'Carteira'){
                    $index = 'carteira';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'MERCADO PAGO'){
                    $index = 'mercado_pago';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'Cartão BNDES'){
                    $index = 'cartao_bndes';
                }else if($condicao_pagamento['formapagamento_descricao'] == 'PIX - FEIRA'){
                    $index = 'pix_feira';
                }
                $resultado[$dado['estabelecimento']][$data->format('Y-m')][$index] += $condicao_pagamento['valor'];
                $resultado[$dado['estabelecimento']][$data->format('Y-m')]['total'] += $condicao_pagamento['valor'];
                $total[$index] += $condicao_pagamento['valor'];
                $total['total'] += $condicao_pagamento['valor'];
            }
        }

        $resultado = $this->ajusteArrayParaValores($resultado);
        $total = $this->ajusteArrayParaValoresFormatacao($total);
        return view('programs.faturamento.dialog.fechamento_caixa_ano')->with(['resultado' => $resultado, 'total' => $total, 'prepago' => $fields['prepago'], 'devolucao' => $fields['devolucao'], 'estabelecimento_exibir' => empty($fields['estabelecimento'])? true : false]);
    }

    private function ajusteArrayParaValores($array){
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->ajusteArrayParaValores($value);
                } else {
                    if (is_numeric($value)) {
                        $array[$key] = empty($value) ? '' : $value;
                    } else {
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    private function ajusteArrayParaValoresFormatacao($array){
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->ajusteArrayParaValores($value);
                } else {
                    if (is_numeric($value)) {
                        $array[$key] = empty($value) ? '' : parserValor($value);
                    } else {
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }
}
