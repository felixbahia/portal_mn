<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\Red;

use App\Http\Requests\RedAdicionarEditarRequest;

class RedController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\RED") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RED');

        return view('programs.red.index');
    }

    public function modalAdicionar(){
        return view('programs.red.modal.adicionar');
    }

    public function adicionar(RedAdicionarEditarRequest $request){
        $fields = $request->only('documento','valor', 'taxa_cambio', 'vencimento');

        $data = Carbon::createFromFormat('d/m/Y', $fields['vencimento'])->setTime(23,59,59);

        $redObj = new Red;
        $redObj->numero_documento = $fields['documento'];
        $redObj->vencimento = $data;
        $redObj->valor = parserNumber($fields['valor']);  
        $redObj->saldo = parserNumber($fields['valor']);  
        $redObj->taxa_cambio = parserNumber($fields['taxa_cambio']);   
        $redObj->created_by = Auth::id();
        $redObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('documento_busca_filtro', 'vencimento_inicial_busca_filtro', 'vencimento_final_busca_filtro');
        
        $query = Red::select();
        if(!empty($fields['documento_busca_filtro'])){
            $query->where('numero_documento', 'ilike', '%'.$fields['documento_busca_filtro'].'%');
        }
        if(!empty($fields['vencimento_inicial_busca_filtro'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['vencimento_inicial_busca_filtro'])->setTime(0,0,0);
            $query->where('vencimento', '>=', $data);
        }
        if(!empty($fields['vencimento_final_busca_filtro'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['vencimento_final_busca_filtro'])->setTime(23,59,59);
            $query->where('vencimento', '<=', $data);
        }
        $result = $query->get();

        $reds = [];

        $total = [
            'valor' => 0,
            'saldo' => 0,
        ];
        foreach($result as $red){
            $reds[] = [
                'id' => encrypt($red->id),
                'numero_documento' => $red->numero_documento,
                'vencimento' => $red->vencimento->format('d/m/Y'),
                'valor' => empty($red->valor)? '' : parserValor($red->valor),
                'taxa_cambio' => empty($red->taxa_cambio)? '' : parserValor4CasasDecimais($red->taxa_cambio),
                'saldo' => empty($red->saldo)? '' : parserValor($red->saldo),
            ];

            $total['valor'] += $red->valor;
            $total['saldo'] += $red->saldo;
        }

        $total['valor'] = empty($total['valor'])? '' : parserValor($total['valor']);
        $total['saldo'] = empty($total['saldo'])? '' : parserValor($total['saldo']);
    
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'reds' => $reds,
                'total' => $total,
            ],
        ]);
    }

    public function modalEditar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $redObj = Red::find($id);

        $dados = [
            'id' => encrypt($redObj->id),
            'vencimento' => $redObj->vencimento->format('d/m/Y'),
            'valor' => empty($redObj->valor)? '' : parserValor($redObj->valor),
            'documento' => empty($redObj->numero_documento)? '' : $redObj->numero_documento,
            'taxa_cambio' => empty($redObj->taxa_cambio)? '' : parserValor4CasasDecimais($redObj->taxa_cambio),
        ];

        return view('programs.red.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(RedAdicionarEditarRequest $request){
        $fields = $request->only('id', 'documento','valor', 'taxa_cambio', 'vencimento');

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

        $data = Carbon::createFromFormat('d/m/Y', $fields['vencimento'])->setTime(23,59,59);

        $redObj = Red::find($id);
        $redObj->numero_documento = $fields['documento'];
        $redObj->vencimento = $data;
        $redObj->valor = parserNumber($fields['valor']);  
        $redObj->taxa_cambio = parserNumber($fields['taxa_cambio']);   
        $redObj->created_by = Auth::id();
        $redObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $redObj = Red::find($id);

        $dados = [
            'id' => encrypt($redObj->id),
            'vencimento' => $redObj->vencimento->format('d/m/Y'),
            'valor' => empty($redObj->valor)? '' : parserValor($redObj->valor),
            'documento' => empty($redObj->numero_documento)? '' : $redObj->numero_documento,
            'taxa_cambio' => empty($redObj->taxa_cambio)? '' : parserValor4CasasDecimais($redObj->taxa_cambio),
        ];

        return view('programs.red.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $id = $request->only(['id'])['id'];

        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $redObj = Red::find($id);
        $redObj->deleted_by = Auth::id();
        $redObj->save();
        $redObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function autoComplete(Request $request){
        $fields = $request->only(["term"]);
        $return = [];
        $query = Red::select();
        $query->where('numero_documento', 'ilike', '%'.$fields['term'].'%');
        $query->where('saldo', '>', 0);
        $result = $query->get();
        foreach ($result as $value){
            $return[] = [
                'label' => trim($value->numero_documento),
                'value' => trim($value->numero_documento),
                'saldo' => parserValor($value->saldo),
                'taxa_cambio' => parserValor4CasasDecimais($value->taxa_cambio),
            ];
        }
        return response()->json($return);
    }

    public function modalBuscar(){
        return view('programs.red.modal.buscar');
    }

    public function modalDetalhes(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $redObj = Red::find($id);

        $proformas = [];
        $saldo = $redObj->valor;
        $total = [
            'red_utilizado' => 0,
            'saldo' => 0,
        ];

        if(!empty($redObj->detalhesRedImportacao)){
            foreach($redObj->detalhesRedImportacao as $proforma){
                $saldo -= $proforma->utilizado_valor;
                $proformas[] = [
                    "red_utilizado" => parserValor($proforma->utilizado_valor),
                    "proforma" => $proforma->detalhesLancamentos->importacaoFinanceiroDetalhes->importacaoDetalhes->numero_proforma,
                    "saldo" => empty($saldo)? "" : parserValor($saldo),
                ];
                $total["red_utilizado"] += $proforma->utilizado_valor;
            }            
        }

        $total["red_utilizado"] = empty($total["red_utilizado"])? '' : parserValor($total["red_utilizado"]);
        $total["saldo"] = empty($saldo)? "" : parserValor($saldo);

        return view('programs.red.modal.detalhes')->with(['proformas' => $proformas, 'total' => $total]);
    }
}
