<?php

namespace App\Http\Controllers;

use App\DevolucaoNotaMotivo;
use App\DevolucaoNotaMotivoStatus;
use App\DevolucaoNotaStatus;

use App\Http\Requests\DevolucaoNotaMotivoNovoRequest;
use App\Http\Requests\DevolucaoNotaMotivoEditarRequest;

use Illuminate\Http\Request;
use Auth;

class DevolucaoNotaMotivoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DevolucaoNotaMotivo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DevolucaoNotaMotivo');

        return view('programs.devolucao_nota_motivos.index');
    }

    public function filter(Request $request){

        $fields = $request->only('descricao');

        $query = DevolucaoNotaMotivo::query();

        if(isset($fields['descricao']) && !empty($fields['descricao'])){
            $query->where('descricao', 'ilike', '%'.$fields['descricao'].'%');
        }

        $devolucaoNotaMotivoObj = $query->get();

        $retorno = [];

        $devolucaoNotaMotivoObj->each(function($motivo) use (&$retorno){
            $linha = [];

            $linha['id'] = $motivo->id;
            $linha['descricao'] = $motivo->descricao;
            $linha['afeta_premiacao'] = $motivo->afeta_premiacao;
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

    public function modalNovo(){
        return view('programs.devolucao_nota_motivos.modal.novo');
    }

    public function modalEditar(Request $request){
        $fields = $request->only('id');

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::find($fields['id']);

        $retorno = [];

        $retorno['id'] = $devolucaoNotaMotivoObj->id;
        $retorno['descricao'] = $devolucaoNotaMotivoObj->descricao;
        $retorno['afeta_premiacao'] = $devolucaoNotaMotivoObj->afeta_premiacao;
        $retorno['assinatura_pedido'] = $devolucaoNotaMotivoObj->assinatura_pedido;

        return view('programs.devolucao_nota_motivos.modal.editar')->with($retorno);
    }

    public function modalStatus(Request $request){

        $fields = $request->only('id');

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::with(
            [
                'status' => function($query){
                    $query->whereHas('devolucao_nota_status', function($q){
                        $q->where('selecionavel', true)
                        ->orderBy('ordem', 'asc');
                    });
                },
                'status.devolucao_nota_status'
            ])
            ->find($fields['id']);

        $devolucaoNotaStatusObj = DevolucaoNotaStatus::whereNotIn('id', $devolucaoNotaMotivoObj->status->pluck('devolucao_nota_status_id'))
            ->where('selecionavel', true)
            ->get();

        $vinculados = $devolucaoNotaMotivoObj->status->sortBy('ordem')->pluck('devolucao_nota_status.descricao', 'devolucao_nota_status_id');
        $cadastrados = $devolucaoNotaStatusObj->pluck('descricao', 'id');

        return view('programs.devolucao_nota_motivos.modal.status')->with(['cadastrados' => $cadastrados, 'vinculados' => $vinculados, 'id' => $fields['id']]);
    }

    public function salvarNovo(DevolucaoNotaMotivoNovoRequest $request){
        $fields = $request->only('descricao', 'afeta_premiacao', 'assinatura_pedido');

        $devolucaoNotaMotivoObj = new DevolucaoNotaMotivo;

        $devolucaoNotaMotivoObj->descricao = $fields['descricao'];
        $devolucaoNotaMotivoObj->afeta_premiacao = $fields['afeta_premiacao'];
        $devolucaoNotaMotivoObj->assinatura_pedido = $fields['assinatura_pedido'];
        $devolucaoNotaMotivoObj->created_by = Auth::user()->id;

        $devolucaoNotaMotivoObj->save();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function salvarEdicao(DevolucaoNotaMotivoEditarRequest $request){
        $fields = $request->only('id', 'descricao', 'afeta_premiacao', 'assinatura_pedido');

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::find($fields['id']);

        $devolucaoNotaMotivoObj->descricao = $fields['descricao'];
        $devolucaoNotaMotivoObj->afeta_premiacao = empty($fields['afeta_premiacao'])? 'nao' : $fields['afeta_premiacao'];
        $devolucaoNotaMotivoObj->assinatura_pedido = empty($fields['assinatura_pedido'])? 'nao' : $fields['assinatura_pedido'];
        $devolucaoNotaMotivoObj->updated_by = Auth::user()->id;

        $devolucaoNotaMotivoObj->save();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function salvarStatus(Request $request){
        $fields = $request->only('id', 'id_status');

        DevolucaoNotaMotivoStatus::where('devolucao_nota_motivo_id', $fields['id'])
            ->whereNull('deleted_at')
            ->update([
                'deleted_by' => Auth::id(),
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

        $x = 1;

        foreach($fields['id_status'] as $status){
            $devolucaoNotaMotivoStatusObj = new DevolucaoNotaMotivoStatus;

            $devolucaoNotaMotivoStatusObj->ordem = $x++;
            $devolucaoNotaMotivoStatusObj->devolucao_nota_motivo_id = $fields['id'];
            $devolucaoNotaMotivoStatusObj->devolucao_nota_status_id = $status;
            $devolucaoNotaMotivoStatusObj->created_by = Auth::id();

            $devolucaoNotaMotivoStatusObj->save();
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );

    }

    public function excluir(Request $request){
        $fields = $request->only('id');

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::find($fields['id']);

        $devolucaoNotaMotivoObj->deleted_by = Auth::user()->id;

        $devolucaoNotaMotivoObj->save();
        $devolucaoNotaMotivoObj->delete();

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
