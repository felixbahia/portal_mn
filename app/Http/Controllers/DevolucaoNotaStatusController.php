<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Spatie\Permission\Models\Permission;

use App\DevolucaoNotaStatus;
use App\User;
use Auth;

class DevolucaoNotaStatusController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DevolucaoNotaStatus") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DevolucaoNotaStatus');

        return view("programs.devolucao_nota_status.index");
    }

    public function filter(Request $request){

        $fields = $request->only('descricao');

        $query = DevolucaoNotaStatus::query();

        if(isset($fields['descricao']) && !empty($fields['descricao'])){
            $query->where('descricao', $fields['descricao']);
        }

        $devolucaoNotaStatus = $query->get();

        $retorno = [];

        $devolucaoNotaStatus->each(function($status) use(&$retorno){
            $linha = [];

            $linha['id'] = $status->id;
            $linha['status'] = $status->descricao;
            $linha['selecionavel'] = $status->selecionavel;

            $retorno[] = $linha;
        });

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => [
                    'dados' => $retorno 
                ]
            ], 220
        );
    }

    public function novoModal(Request $request){
        $userObj = User::all();
        return view('programs.devolucao_nota_status.modal.novo');
    }

    public function editarModal(Request $request){

        $fields = $request->only('id');

        $devolucaoNotaStatus = DevolucaoNotaStatus::find($fields['id']);

        $retorno = [
            'id' => $devolucaoNotaStatus->id,
            'descricao' => $devolucaoNotaStatus->descricao,
            'selecionavel' => $devolucaoNotaStatus->selecionavel
        ];

        return view('programs.devolucao_nota_status.modal.editar')->with($retorno);

    }

    public function novoSalvar(Request $request){

        $fields = $request->only('descricao', 'selecionavel');

        $devolucaoNotaStatusObj = new DevolucaoNotaStatus;

        $devolucaoNotaStatusObj->descricao = $fields['descricao'];
        $devolucaoNotaStatusObj->chave =  str_replace(' ', '_', strtolower($fields['descricao']));
        $devolucaoNotaStatusObj->selecionavel = $fields['selecionavel']??false;
        $devolucaoNotaStatusObj->created_by = Auth::id();

        $devolucaoNotaStatusObj->save();

        if(isset($fields['selecionavel'])){
            $permissionObj = new Permission;
            $permissionObj->name = 'action App\DevolucaoNotaAprovacao ' . $devolucaoNotaStatusObj->chave;
            $permissionObj->save();
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function editarSalvar(Request $request){
        $fields = $request->only('id', 'descricao', 'selecionavel');

        $devolucaoNotaStatusObj = DevolucaoNotaStatus::find($fields['id']);
        
        if(isset($fields['selecionavel']) && $devolucaoNotaStatusObj->selecionavel === true){
            $permissionObj = Permission::where('name', 'action App\DevolucaoNotaAprovacao ' . $devolucaoNotaStatusObj->chave)->first();
        }
        else if(isset($fields['selecionavel']) && $devolucaoNotaStatusObj->selecionavel === false){
            $permissionObj = new Permission;
        }
        else{
            $permissionObj = Permission::where('name', 'action App\DevolucaoNotaAprovacao ' . $devolucaoNotaStatusObj->chave)->delete();
        }

        $devolucaoNotaStatusObj->descricao = $fields['descricao'];
        $devolucaoNotaStatusObj->chave =  str_replace(' ', '_', strtolower($fields['descricao']));
        $devolucaoNotaStatusObj->selecionavel = $fields['selecionavel']??false;
        $devolucaoNotaStatusObj->created_by = Auth::id();

        $devolucaoNotaStatusObj->save();

        if(isset($fields['selecionavel'])){
            $permissionObj->name = 'action App\DevolucaoNotaAprovacao ' . $devolucaoNotaStatusObj->chave;
            $permissionObj->save();
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function excluir(Request $request){
        $fields = $request->only('id');
        $devolucaoNotaStatusObj = DevolucaoNotaStatus::find($fields['id']);
        $devolucaoNotaStatusObj->deleted_by = Auth::id();
        $devolucaoNotaStatusObj->save();
        $devolucaoNotaStatusObj->delete();

        $permissionObj = Permission::where('name', 'action App\DevolucaoNotaAprovacao ' . $devolucaoNotaStatusObj->chave)->delete();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados excluídos com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );

    }

}
