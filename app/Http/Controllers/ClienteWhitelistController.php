<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ClienteWhitelist;
use App\ClienteNasajon;
use Auth;

use App\Http\Requests\ClienteWhitelistAdicionarRequest;

class ClienteWhitelistController extends Controller
{
    public function index(Request $request){
    
        if(Auth::user()->hasPermissionTo("programas App\ClienteWhitelist") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\ClienteWhitelist');

        return view('programs.cliente_whitelist.index');
    }


    public function filter(Request $request){

        $fields = $request->only('cliente_nome');

        if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){
            $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'%' . $fields['cliente_nome'] . '%\'')->get();;

            $cliente_whitelist = ClienteWhitelist::with('cliente')->whereIn('cpf_cnpj', $clienteNasajon->pluck('cpf_cnpj'))->get();

        }
        else{
            $cliente_whitelist = ClienteWhitelist::with('cliente')->get();
        }

        $clientes_table = [];
          
        $cliente_whitelist->each(function($whitelist) use (&$clientes_table){
            $clientes_table[] = [
                'id' => $whitelist->id,
                'nome_razao' => $whitelist->cliente->nome,
                'cpf_cnpj' => $whitelist->cpf_cnpj   
            ];
        });
        unset($cliente_whitelist);
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $clientes_table
        ];

        return response()->json($response, 220);
    }

    public function modalAdicionar(){
        return view('programs.cliente_whitelist.modal.adicionar');
    }

    public function adicionar(ClienteWhitelistAdicionarRequest $request){
        $fields = $request->only('cliente_nome');

        $cliente = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \'' . $fields['cliente_nome'] . '\'')->first();

        if(!is_null($cliente)){
            $cnpj_cpf_cliente = $cliente->cpf_cnpj;
    
            $cliente_pre_pago = new ClienteWhitelist();
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

    public function modalDelete(Request $request){
        $fields = $request->only('id');

        $cliente = ClienteWhitelist::with('cliente')->find($fields['id']);
          
        return view('programs.cliente_whitelist.modal.delete')->with(['cliente' => $cliente]);
    }


    public function excluir(Request $request){
        $fields = $request->all();

        $ClienteWhitelist = ClienteWhitelist::find($fields['id']);

        $ClienteWhitelist->deleted_by = Auth::user()->id;

        if($ClienteWhitelist->save() && $ClienteWhitelist->delete()){
            return response()->json(["status" => "success"], 220);
        }else{
            return response()->json(["status" => "error", "message" => "Ocorreu um erro ao gravar as informações.", 422]);
        }
    }
}
