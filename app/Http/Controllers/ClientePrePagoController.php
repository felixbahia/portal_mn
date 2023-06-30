<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
/* Cliente Pré-pagos */
use App\ClientePrePago;
use App\ClienteNasajon;

use App\Http\Requests\ClientePrePagoRequest;

class ClientePrePagoController extends Controller
{

    public function index(Request $request){
    
        if(Auth::user()->hasPermissionTo("programas App\ClientePrePago") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\ClientePrePago');

        return view('programs.cliente.pre_pago.index');
    }

    public function store(ClientePrePagoRequest $request){
        $fields = $request->only('cliente_nome');

        $cliente = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \'' . $fields['cliente_nome'] . '\'')->first();

        if(!is_null($cliente)){
            $cnpj_cpf_cliente = $cliente->cpf_cnpj;
    
            $cliente_pre_pago = new ClientePrePago();
            $cliente_pre_pago->cpf_cnpj = $cnpj_cpf_cliente;
            $cliente_pre_pago->created_by = Auth::user()->id;
            
            if($cliente_pre_pago->save()){
                return response()->json(["status" => "success"], 220);
            }else{
                return response()->json(["status" => "error", "message" => "Ocorreu um erro ao gravar as informações"], 422);
            }
        }
        else{
            return response()->json(["status" => "error", 'message' => '', "errors" => ["cliente_nome" => "Cliente inválido"]], 422);
        }
    }

    public function filter(Request $request){

        $fields = $request->only('cliente_nome');

        if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){
            $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'%' . $fields['cliente_nome'] . '%\'')->get();;

            $cliente_pre_pagos = ClientePrePago::with('cliente_nasajon')->whereIn('cpf_cnpj', $clienteNasajon->pluck('cpf_cnpj'))->get();

        }
        else{
            $cliente_pre_pagos = ClientePrePago::with('cliente_nasajon')->get();
        }

        $clientes_table = [];
          
        foreach($cliente_pre_pagos as $prepago){
            $clientes_table[] = [
                'id' => $prepago->id,
                'nome_razao' => $prepago->cliente_nasajon->nome??$prepago->cpf_cnpj,
                'cpf_cnpj' => $prepago->cpf_cnpj   
            ];
        }
        
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $clientes_table
        ];

        return response()->json($response, 220);
    }

    public function formAdd(Request $request){
        return view('programs.cliente.pre_pago.add');
    }

    public function formEdit(Request $request){
        $fields = $request->only('id');

        $cliente = ClientePrePago::with('cliente_nasajon')->find($fields['id']);
       
        return view('programs.cliente.pre_pago.dialog')->with(['cliente' => $cliente]);
    }

    public function formDelete(Request $request){
        $fields = $request->only('id');

        $cliente = ClientePrePago::with('cliente_nasajon')->find($fields['id']);
          
        return view('programs.cliente.pre_pago.delete')->with(['cliente' => $cliente]);
    }

    public function update(ClientePrePagoRequest $request){
        $fields = $request->only('id', 'cliente_nome');

        $clientePrePago = ClientePrePago::find($fields['id']);

        $cliente = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \'' . $fields['cliente_nome'] . '\'')->first();

        if(!is_null($cliente)){
            $clientePrePago->cpf_cnpj = $cliente->cpf_cnpj;
            $clientePrePago->updated_by = Auth::user()->id;

            if($clientePrePago->save()){
                return response()->json(["status" => "success"]);
            }
            else{
                return response()->json(["status" => "error", "message" => "Ocorreu um erro ao salvar"]);
            }
        }
        else{
            return response()->json(["status" => "error", 'message' => '', "errors" => ["cliente_nome" => "Cliente inválido"]], 422);
        }
    }

    public function delete(Request $request){
        $fields = $request->all();

        $clientePrePago = ClientePrePago::find($fields['id']);

        $clientePrePago->deleted_by = Auth::user()->id;

        if($clientePrePago->save() && $clientePrePago->delete()){
            return response()->json(["status" => "success"], 220);
        }else{
            return response()->json(["status" => "error", "message" => "Ocorreu um erro ao gravar as informações.", 422]);
        }
    }
}
