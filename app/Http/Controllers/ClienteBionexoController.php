<?php

namespace App\Http\Controllers;

use App\ClienteBionexo;
use Illuminate\Http\Request;
use Auth;
use App\Http\Requests\ClienteBionexoCadastrarResquest;
use App\Http\Requests\ClienteBionexoEditarResquest;
use App\ClienteNasajon;

class ClienteBionexoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ClienteBionexo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteBionexo');

        return view('programs.cliente_bionexo.index');
    }

    public function ModalAdicionar() {
        return view('programs.cliente_bionexo.modal.adicionar');
    }

    public function ClienteAdicionar(ClienteBionexoCadastrarResquest $request){
        $fields = $request->only('cliente_nome_modal');

        $cliente_nome_modal = $fields['cliente_nome_modal'];

        $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'' . $cliente_nome_modal . '\'')->first();

        $ClienteBionexo = new ClienteBionexo;
        $ClienteBionexo->cpf_cnpj = $clienteNasajon->cpf_cnpj;
        $ClienteBionexo->created_by = Auth::id();
        $ClienteBionexo->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function ClienteFilter(Request $request){
        $fields = $request->only('cliente_nome');

        $cliente_nome = $fields['cliente_nome'];

        $ClienteBionexo = ClienteBionexo::with('ClienteNasajon');
        if(!empty($cliente_nome)){
            $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'%' . $cliente_nome . '%\'')->get();

            $ClienteBionexo->whereIn('cpf_cnpj', $clienteNasajon->pluck('cpf_cnpj'));

        }
        $result = $ClienteBionexo->get();

        $dadosCliente = [];
        foreach($result as $value){
            $dadosCliente []= [
                'id' => encrypt($value->id),
                'nome_razao' => $value->ClienteNasajon->nome,
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

    public function ModalEditar(Request $request){
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

        $ClienteBionexo = ClienteBionexo::find($id);
        if(is_null($ClienteBionexo)){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $cliente = [
            'id' => encrypt($ClienteBionexo->id),
            'nome_razao' => $ClienteBionexo->ClienteNasajon->nome,
            'cpf_cnpj' => $ClienteBionexo->cpf_cnpj
        ];

        return view('programs.cliente_bionexo.modal.editar')->with(['cliente' => $cliente]);
    }

    public function ClienteEditar(ClienteBionexoEditarResquest $request){
        $fields = $request->only('id', 'cliente_nome_modal');
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

        $cliente_nome_modal = $fields['cliente_nome_modal'];

        $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'' . $cliente_nome_modal . '\'')->first();

        $ClienteBionexo = ClienteBionexo::find($id);
        $ClienteBionexo->cpf_cnpj = $clienteNasajon->cpf_cnpj;
        $ClienteBionexo->updated_by = Auth::id();
        $ClienteBionexo->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function ModalDeletar(Request $request){
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

        $ClienteBionexo = ClienteBionexo::find($id);
        if(is_null($ClienteBionexo)){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $cliente = [
            'id' => encrypt($ClienteBionexo->id),
            'nome_razao' => $ClienteBionexo->ClienteNasajon->nome,
            'cpf_cnpj' => $ClienteBionexo->cpf_cnpj
        ];

        return view('programs.cliente_bionexo.modal.delete')->with(['cliente' => $cliente]);
    }

    public function ClienteDeletar(Request $request){
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

        $ClienteBionexo= ClienteBionexo::find($id);
        $ClienteBionexo->deleted_by = Auth::id();
        $ClienteBionexo->save();
        $ClienteBionexo->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
