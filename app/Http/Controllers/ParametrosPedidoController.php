<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ParametrosPedido;
use Auth;

class ParametrosPedidoController extends Controller
{
    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\ParametrosPedido") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ParametrosPedido');

        return view('programs.parametros_pedido.index');
    }

    public function modal(Request $request){

    	$fields = $request->only('estabelecimento');

        $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $fields['estabelecimento'])->first();

        $empresas = returnEmpresasNasajonView();

        $return = [
        	'estabelecimento' => $empresas[$fields['estabelecimento']],
			'valor_minimo_porcentagem' => $parametrosPedidoObj->valor_minimo_porcentagem??'',
            'valor_maximo_porcentagem' => $parametrosPedidoObj->valor_maximo_porcentagem??'',
			'dias_integracao' => $parametrosPedidoObj->dias_integracao??'',
            'id' => $fields['estabelecimento']
		];

        return view('programs.parametros_pedido.modal')->with(['return' => $return]);

    }

    public function salvar(Request $request){
        $fields = $request->only('estabelecimento', 'valor_maximo_porcentagem', 'valor_minimo_porcentagem', 'dias_integracao');
        if(floatval($fields['valor_minimo_porcentagem']) > 80.00){
            return response()->json(['errors' => ['valor_minimo_porcentagem' => 'Porcentagem máxima de desconto não pode ser maior que 80%']], 422);
        }
        $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $fields['estabelecimento'])->first();
        if(empty($parametrosPedidoObj)){
            $parametrosPedidoObj = new ParametrosPedido();
            $parametrosPedidoObj->estabelecimento = $fields['estabelecimento'];
            $parametrosPedidoObj->created_by = Auth::user()->id;
        }
        else{
            $parametrosPedidoObj->modified_by = Auth::user()->id;
        }

        $parametrosPedidoObj->fill($fields);
        $parametrosPedidoObj->save();

        return response()->json(['Ok' => 'Ok'], 200);
    }

    public function filter(){

        $empresas = returnEmpresasNasajonView();
        unset($empresas[0]);
        unset($empresas[20]);

        $parametrosPedidoObj = ParametrosPedido::all();
        $return = [];

        foreach ($empresas as $key => $value){
            $return[] = [
                'estabelecimento' => $value,
                'valor_minimo_porcentagem' => $parametrosPedidoObj->where('estabelecimento', $key)->first()->valor_minimo_porcentagem??'',
                'valor_maximo_porcentagem' => $parametrosPedidoObj->where('estabelecimento', $key)->first()->valor_maximo_porcentagem??'',
                'dias_integracao' => $parametrosPedidoObj->where('estabelecimento', $key)->first()->dias_integracao??'0',
                'id' => $key
            ];
        }

        return response()->json($return);
    }

}
