<?php

namespace App\Http\Controllers;
use Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\PedidoVenda;
use App\User;
use App\Cliente;
use App\PedidosVendaNasajon;

class AnalisePeidosController extends Controller
{

    private $codigosOperacaoNasajon = ['PEDIDOISENTO', 'PEDIDOVENDA', 'PEDTRIANGULAR', 'PEDVENDATORO'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnalisePedidosPrologos") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnalisePedidosPrologos');
        return view("programs.analise_pedidos_prologos.index");
    }

    public function filtro(Request $request){
        $fields = $request->only(['data_inicio', 'data_fim']);

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0, 0, 0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(0, 0, 0);
        $empresa = returnEmpresasNasajonView();

        $pedidos_gerados_nasajon = PedidosVendaNasajon::select(DB::Raw("estabelecimento_codigo as estabelecimento, count(*) as quantidade"))->whereBetween('emissao', [$data_inicio, $data_fim])->whereIn('operacao_codigo', $this->codigosOperacaoNasajon)->where('origem', '!=', 'Pedido Mobile')->groupBy('estabelecimento_codigo')->get();

        $pedidos_gerados_portal_nasajon = PedidosVendaNasajon::select(DB::Raw("estabelecimento_codigo as estabelecimento, count(*) as quantidade"))->whereBetween('emissao', [$data_inicio, $data_fim])->whereIn('operacao_codigo', $this->codigosOperacaoNasajon)->where('origem', 'Pedido Mobile')->groupBy('estabelecimento_codigo')->get();

        $retorno = [];

        foreach ($pedidos_gerados_portal_nasajon as $key => $pedido){
            if(!isset($retorno[intval($pedido->estabelecimento)])){
                $retorno[intval($pedido->estabelecimento)] = [
                    'estabelecimento' => $empresa[intval($pedido->estabelecimento)],
                    'quantidade_portal' => 0,
                    'quantidade_nasajon' => 0,
                    'quantidade_total' => 0
                ];
            }
            $retorno[intval($pedido->estabelecimento)]['quantidade_portal'] += $pedido->quantidade;
        }

        foreach ($pedidos_gerados_nasajon as $key => $pedido) {
            if(!isset($retorno[intval($pedido->estabelecimento)])){
                $retorno[intval($pedido->estabelecimento)] = [
                    'estabelecimento' => $empresa[intval($pedido->estabelecimento)],
                    'quantidade_portal' => 0,
                    'quantidade_nasajon' => 0,
                    'quantidade_total' => 0
                ];
            }
            $retorno[intval($pedido->estabelecimento)]['quantidade_nasajon'] = $pedido->quantidade;
        }
        foreach ($retorno as $estabelecimento => $value) {
            $retorno[$estabelecimento]['quantidade_total'] = intval($value['quantidade_portal']) + intval($value['quantidade_nasajon']);
        }
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalAbertura(Request $request){

        ini_set('memory_limit', '1024M');

        $fields = $request->only(['estabelecimento', 'action', 'data_inicio', 'data_fim']);

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0, 0, 0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(0, 0, 0);
        $empresa = returnEmpresasNasajonView();

        $pedidos = [];

        if($fields['action'] === 'todos'){            
            $pedidos_nasajon = PedidosVendaNasajon::whereBetween('emissao', [$data_inicio, $data_fim])->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT))->whereIn('operacao_codigo', $this->codigosOperacaoNasajon)->where('origem', '!=', 'Pedido Mobile')->get();

            $pedidos_portal_nasajon = PedidosVendaNasajon::whereBetween('emissao', [$data_inicio, $data_fim])->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT))->whereIn('operacao_codigo', $this->codigosOperacaoNasajon)->where('origem', 'Pedido Mobile')->get();

            foreach ($pedidos_nasajon as $key => $value) {
                $pedidos[] = [
                    'numero_pedido' => $value->numero,
                    'id' => $value->id,
                    'vendedor' => ($value->vendedor_detalhes->codigo??'') .' - '. ($value->vendedor_detalhes->nome??''),
                    'cliente' => $value->cliente_detalhes->nome,
                    'usario_criou' => $value->userNasajon->name??'',
                    'origem' => 'Nasajon'
                ];
            }

            foreach ($pedidos_portal_nasajon as $key => $value) {
                $pedidos[] = [
                    'numero_pedido' => $value->numero,
                    'id' => $value->id,
                    'vendedor' => ($value->vendedor_detalhes->codigo??'') .' - '. ($value->vendedor_detalhes->nome??''),
                    'cliente' => $value->cliente_detalhes->nome,
                    'usario_criou' => $value->userNasajon->name??'',
                    'origem' => 'Nasajon'
                ];
            }

        }

        else if($fields['action'] == 'nasajon'){
            $pedidos = PedidosVendaNasajon::whereBetween('emissao', [$data_inicio, $data_fim])->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT))->whereIn('operacao_codigo', $this->codigosOperacaoNasajon)->where('origem', '!=', 'Pedido Mobile')->get();

            foreach ($pedidos as $key => $value) {
                $pedidos[] = [
                    'numero_pedido' => $value->numero,
                    'id' => $value->id,
                    'vendedor' => ($value->vendedor_detalhes->codigo??'') .' - '. ($value->vendedor_detalhes->nome??''),
                    'cliente' => $value->cliente_detalhes->nome,
                    'usario_criou' => $value->userNasajon->nome??'',
                    'origem' => 'Nasajon'

                ];
            }
        }

        $estabelecimento = $empresa[intval($fields['estabelecimento'])];
        return view("programs.analise_pedidos_prologos.modal.abertura")->with(['estabelecimento' => $estabelecimento, 'pedidos' => $pedidos]);
    }
}