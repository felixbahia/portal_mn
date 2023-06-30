<?php

namespace App\Http\Controllers;

use App\ClienteNasajon;
use App\ClienteTriangular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ClienteTriangularAdicionarRequest;

class ClienteTriangularController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ClienteTriangular") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteTriangular');

        return view('programs.cliente_triangular.index');
    }

    public function modalAdicionar() {
        return view('programs.cliente_triangular.modal.adicionar');
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

        $ClienteTriangular = ClienteTriangular::find($id);
        if(is_null($ClienteTriangular)){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $dados = [
            'id' => encrypt($ClienteTriangular->id),
            'nome_razao' => $ClienteTriangular->cliente_nasajon->nome,
            'cpf_cnpj' => $ClienteTriangular->cpf_cnpj
        ];

        return view('programs.cliente_triangular.modal.delete')->with(['dados' => $dados]);
    }

    public function adicionar(ClienteTriangularAdicionarRequest $request){
        $fields = $request->only('cliente_nome_modal');

        $cliente_nome_modal = $fields['cliente_nome_modal'];

        $clienteNasajon = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome_modal))->first();

        $ClienteTriangular = new ClienteTriangular;
        $ClienteTriangular->cpf_cnpj = $clienteNasajon->cpf_cnpj;
        $ClienteTriangular->created_by = Auth::id();
        $ClienteTriangular->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('cliente_nome');

        $cliente_nome = $fields['cliente_nome'];

        $cliente_Triangular = ClienteTriangular::with('cliente_nasajon');
        if(!empty($cliente_nome)){
            $clienteNasajon = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome))->get();

            $cliente_Triangular->whereIn('cpf_cnpj', $clienteNasajon->pluck('cpf_cnpj'));

        }
        $result = $cliente_Triangular->get();

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

    public function deletar(Request $request){
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

        $ClienteTriangular = ClienteTriangular::find($id);
        $ClienteTriangular->deleted_by = Auth::id();
        $ClienteTriangular->save();
        $ClienteTriangular->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

}



