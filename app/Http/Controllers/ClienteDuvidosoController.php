<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\ClienteDuvidoso;
use App\Http\Requests\ClienteDuvidosoCadastrarRequest;
use App\ClienteNasajon;

use Illuminate\Support\Facades\DB;

class ClienteDuvidosoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ClienteDuvidoso") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteDuvidoso');

        return view('programs.clientes_duvidosos.index');
    }

    public function modalAdicionar() {
        return view('programs.clientes_duvidosos.modal.adicionar');
    }

    public function modalDeletar(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $ClienteDuvidoso = ClienteDuvidoso::find($id);
        if(is_null($ClienteDuvidoso)){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $dados = [
            'id' => encrypt($ClienteDuvidoso->id),
            'nome_razao' => $ClienteDuvidoso->cliente_nasajon->nome,
            'cpf_cnpj' => $ClienteDuvidoso->cpf_cnpj
        ];

        return view('programs.clientes_duvidosos.modal.delete')->with(['dados' => $dados]);
    }

    public function adicionarClienteDuvidoso(ClienteDuvidosoCadastrarRequest $request){
        $fields = $request->only('cliente_nome_modal');

        $cliente_nome_modal = $fields['cliente_nome_modal'];

        $clienteNasajon = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome_modal))->first();

        $ClienteDuvidoso = new ClienteDuvidoso;
        $ClienteDuvidoso->cpf_cnpj = $clienteNasajon->cpf_cnpj;
        $ClienteDuvidoso->created_by = Auth::id();
        $ClienteDuvidoso->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filterClienteDuvidoso(Request $request){
        $fields = $request->only('cliente_nome');

        $cliente_nome = $fields['cliente_nome'];

        $cliente_duvidoso = ClienteDuvidoso::with('cliente_nasajon');
        if(!empty($cliente_nome)){
            $clienteNasajon = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome))->get();

            $cliente_duvidoso->whereIn('cpf_cnpj', $clienteNasajon->pluck('cpf_cnpj'));

        }
        $result = $cliente_duvidoso->get();

        $dadosCliente = [];
        foreach($result as $value){
            $dadosCliente []= [
                'id' => encrypt($value->id),
                'nome_razao' => $value->cliente_nasajon->nome,
                'cpf_cnpj' => $value->cpf_cnpj
            ];
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $dadosCliente
        ];
        return response()->json($retorno);
    }

    public function deletarClienteDuvidoso(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não encontrado',
                'error' => [],
                'response' => []
            ]);
        }

        $ClienteDuvidoso = ClienteDuvidoso::find($id);
        $ClienteDuvidoso->deleted_by = Auth::id();
        $ClienteDuvidoso->save();
        $ClienteDuvidoso->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

}



