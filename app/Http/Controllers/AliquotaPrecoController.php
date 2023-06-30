<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\AliquotaPreco;
use App\CepEstado;
use Auth;
use App\Http\Requests\AliquotaPrecoRequest;
use App\Http\Requests\AliquotaFiltroRequest;

class AliquotaPrecoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AliquotaPreco") === false){
            return abort(403);
        }

        $estadosObj = CepEstado::all()->sortBy('estado')->toArray();

        $estados = array();

        $origemObj = AliquotaPreco::with('origem_detalhe')->get();

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }

        foreach ($estadosObj as $value) {
        	$estados[$value['uf'] ] = $value['estado'];
        }

        $request->session()->flash('model', 'App\AliquotaPreco');
    	return view("programs.aliquota_precos.index")->with(['estados' => $estados, 'origem' => $origem]);
    }

    public function filter(AliquotaFiltroRequest $request){

    	$fields = $request->only(['origem','estado','internacional']);

        $query = AliquotaPreco::select('*');

        if (!is_null($fields['origem'])) {
            $query->where('origem', $fields['origem']);
        }
        if (!is_null($fields['estado'])) {
            $query->where('estado', $fields['estado']);
        }

        if(isset($fields['internacional'])){

            if ($fields['internacional'] === '0'){
                $query->where('internacional', false);
            }
            else if ($fields['internacional'] === '1'){
                $query->where('internacional', true);
            }
        }

        $result = $query->get()->toArray();

        // dd($result);


        $estadosObj = CepEstado::select('uf', 'estado')->get()->toArray();
        $estados = [];

        $origemObj = AliquotaPreco::with('origem_detalhe')->get();

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }
        
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }


        foreach ($result as $key => $value) {
            // $result[$key]['aliquota'] = parserValor($result[$key]['aliquota']);
            $result[$key]['frete_adicional'] = parserValor($result[$key]['frete_adicional']);
            $result[$key]['icms_venda'] = parserValor($result[$key]['icms_venda']);
            $result[$key]['icms_venda_cliente_isento'] = parserValor($result[$key]['icms_venda_cliente_isento']);
            $result[$key]['estado'] = $estados[$result[$key]['estado']];
            $result[$key]['origem'] = $origem[$result[$key]['origem']];
            $result[$key]['internacional'] = $result[$key]['internacional']?'Importado':"Nacional" ;
        }

        return response()->json($result);

    }
    public function formCadastro(Request $request){

        $linha = new AliquotaPreco();
        
        $estadosObj = CepEstado::select('uf', 'estado')->get()->toArray();
        
        $estados = [];
        $estabelecimentos = [];

        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }

        $origemObj = AliquotaPreco::with('origem_detalhe')->get();

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }

        return view("programs.aliquota_precos.cadastro")->with(['aliquota' => $linha, 'origem' => $origem, 'estados' => $estados]);

    }

    public function formEdit(Request $request){

    	$linha = AliquotaPreco::findOrFail($request->id);
        
        $estadosObj = CepEstado::select('uf', 'estado')->get()->toArray();
        
        $estados = [];
        $estabelecimentos = [];

        // dd($linha);
        
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }

        $origemObj = AliquotaPreco::with('origem_detalhe')->get();

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }

    	return view("programs.aliquota_precos.editar")->with(['aliquota' => $linha, 'origem' => $origem, 'estados' => $estados]);

    }
    public function cadastro(AliquotaPrecoRequest $request){
        
        $valores = $request->only(['origem', 'estado', 'frete_adicional', 'icms_venda', 'internacional', 'icms_venda_cliente_isento']);
        
        $linha = new AliquotaPreco();

        if(empty($valores['frete_adicional']) || is_null($valores['frete_adicional'])){
            $linha->frete_adicional = 0;
        }
        else{
            $linha->frete_adicional = str_replace(',', '.', $valores['frete_adicional']);
        }

        $linha->origem = $valores['origem'];
        $linha->estado = $valores['estado'];
        $linha->aliquota = str_replace(',', '.', $valores['icms_venda']);
        $linha->icms_venda = str_replace(',', '.', $valores['icms_venda']);
        $linha->icms_venda_cliente_isento = str_replace(',', '.', $valores['icms_venda_cliente_isento']);
        $linha->internacional = isset($valores['internacional'])?'true':'false';
        $linha->created_by = Auth::id();

        $result = $linha->save();

        return response()->json($result);

    }

    public function editar(AliquotaPrecoRequest $request){

        $valores = $request->only(['id', 'origem', 'estado', 'frete_adicional', 'icms_venda', 'internacional', 'icms_venda_cliente_isento']);
        
        $linha = AliquotaPreco::findOrFail($valores['id']);

        if(empty($valores['frete_adicional']) || is_null($valores['frete_adicional'])){
            $linha->frete_adicional = 0;
        }
        else{
            $linha->frete_adicional = str_replace(',', '.', $valores['frete_adicional']);
        }

        $linha->origem = $valores['origem'];
        $linha->estado = $valores['estado'];
        $linha->icms_venda = str_replace(',', '.', $valores['icms_venda']);
        $linha->icms_venda_cliente_isento = str_replace(',', '.', $valores['icms_venda_cliente_isento']);
        $linha->internacional = isset($valores['internacional'])?'true':'false';
        $linha->modified_by = Auth::id();

        $result = $linha->save();

        return response()->json($result);

    }


}
