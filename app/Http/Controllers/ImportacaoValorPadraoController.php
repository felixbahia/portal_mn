<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\ImportacaoValorPadrao;

use App\Http\Requests\ImportacaoValorPadraoModificacaoRequest;

class ImportacaoValorPadraoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ImportacaoValorPadrao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ImportacaoValorPadrao');

        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();
        if(!empty($importacaoValorPadraoObj)){
            $dados = [
                'id' => encrypt($importacaoValorPadraoObj->id),
                'dolar_referencia' => empty($importacaoValorPadraoObj->dolar_referencia)? '' : parserValor($importacaoValorPadraoObj->dolar_referencia),
                'pis' => empty($importacaoValorPadraoObj->pis)? '' : parserValor($importacaoValorPadraoObj->pis),
                'cofins' => empty($importacaoValorPadraoObj->cofins)? '' : parserValor($importacaoValorPadraoObj->cofins),
                'capatazia' => empty($importacaoValorPadraoObj->capatazia)? '' : parserValor($importacaoValorPadraoObj->capatazia),
                'taxa_siscomex' => empty($importacaoValorPadraoObj->taxa_siscomex)? '' : parserValor($importacaoValorPadraoObj->taxa_siscomex),
                'sda' => empty($importacaoValorPadraoObj->sda)? '' : parserValor($importacaoValorPadraoObj->sda),
                'honorarios' => empty($importacaoValorPadraoObj->honorarios)? '' : parserValor($importacaoValorPadraoObj->honorarios),
                'expediente' => empty($importacaoValorPadraoObj->expediente)? '' : parserValor($importacaoValorPadraoObj->expediente),
                'armazenagem' => empty($importacaoValorPadraoObj->armazenagem)? '' : parserValor($importacaoValorPadraoObj->armazenagem),
                'laudo' => empty($importacaoValorPadraoObj->laudo)? '' : parserValor($importacaoValorPadraoObj->laudo),
                'frete_rodoviario' => empty($importacaoValorPadraoObj->frete_rodoviario)? '' : parserValor($importacaoValorPadraoObj->frete_rodoviario),
                'ultima_atualizacao' => "Última Atualização: ".$importacaoValorPadraoObj->updated_at->format('d/m H:i'),
                'agencia_maritima' => empty($importacaoValorPadraoObj->agencia_maritima)? '' : parserValor($importacaoValorPadraoObj->agencia_maritima),
            ];
        }else{
            $dados = [
                'id' => encrypt(''),
                'dolar_referencia' => '',
                'pis' => '',
                'cofins' => '',
                'capatazia' => '',
                'taxa_siscomex' => '',
                'sda' => '',
                'honorarios' => '',
                'expediente' => '',
                'armazenagem' => '',
                'laudo' => '',
                'frete_rodoviario' => '',
                'agencia_maritima' => '',
                'ultima_atualizacao' => '',
            ];
        }

        return view('programs.importacao_valor_padrao.index')->with(['dados' => $dados]);
    }

    public function modificarValorPadrao(ImportacaoValorPadraoModificacaoRequest $request){
        $fields = $request->only('id','dolar_referencia','pis','cofins','capatazia','taxa_siscomex','sda','honorarios','expediente','armazenagem','laudo','frete_rodoviario', 'agencia_maritima');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        if(empty($id)){
            $importacaoValorPadraoObj = new ImportacaoValorPadrao;
        }else{
            $importacaoValorPadraoObj = ImportacaoValorPadrao::find($id);
            $importacaoValorPadraoObj->deleted_by = Auth::id();
            $importacaoValorPadraoObj->save();
            $importacaoValorPadraoObj->delete();

            $importacaoValorPadraoObj = new ImportacaoValorPadrao;
        }

        $importacaoValorPadraoObj->dolar_referencia = parserNumber($fields['dolar_referencia']);
        $importacaoValorPadraoObj->pis = parserNumber($fields['pis']);
        $importacaoValorPadraoObj->cofins = parserNumber($fields['cofins']);
        $importacaoValorPadraoObj->capatazia = parserNumber($fields['capatazia']);
        $importacaoValorPadraoObj->taxa_siscomex = parserNumber($fields['taxa_siscomex']);
        $importacaoValorPadraoObj->sda = parserNumber($fields['sda']);
        $importacaoValorPadraoObj->honorarios = parserNumber($fields['honorarios']);
        $importacaoValorPadraoObj->expediente = parserNumber($fields['expediente']);
        $importacaoValorPadraoObj->armazenagem = parserNumber($fields['armazenagem']);
        $importacaoValorPadraoObj->laudo = parserNumber($fields['laudo']);
        $importacaoValorPadraoObj->frete_rodoviario = parserNumber($fields['frete_rodoviario']);
        $importacaoValorPadraoObj->agencia_maritima = parserNumber($fields['agencia_maritima']);
        $importacaoValorPadraoObj->created_by = Auth::id();
        $importacaoValorPadraoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'id' => encrypt($importacaoValorPadraoObj->id),
                'ultima_atualizacao' => "Última Atualização: ".$importacaoValorPadraoObj->updated_at->format('d/m H:i'),
            ]
        ];
        return response()->json($response);
    }
}
