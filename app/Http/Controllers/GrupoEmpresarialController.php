<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GrupoEmpresarial;
use App\GrupoEmpresarialParticipante;
use App\ClienteNasajon;

use Auth;

use Carbon\Carbon;
use App\Http\Requests\GrupoEmpresarialSalvar;

use Illuminate\Support\Facades\DB;

class GrupoEmpresarialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\GrupoEmpresarial") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\GrupoEmpresarial');
        return view('programs.grupo_empresarial.index');
    }

    public function filter(Request $request){

        $fields = $request->only(['cliente', 'cnpj']);

        $GrupoEmpresarialObj = GrupoEmpresarial::with(['createdby', 'participantes']);
        $clienteFilterObj = ClienteNasajon::select('*');

        if (!is_null($fields['cliente'])){
            $GrupoEmpresarialObj->where('nome', 'ilike', "%" .$fields['cliente'] . "%");
            $clienteFilterObj->where(DB::Raw('LOWER(NOME)'), 'like', "%" . strtolower($fields['cliente']) . "%");
        }

        if(!is_null($fields['cnpj'])){
            $clienteFilterObj->where('cpf_cnpj', 'like', $fields['cnpj'] . "%");
        }

        if (!is_null($fields['cliente']) || !is_null($fields['cnpj'])){
            $clienteFilter = $clienteFilterObj->get();

            $cnpjs = [];

            foreach($clienteFilter as $key => $value){
                if(strlen($value->cpf_cnpj) == 18){
                    $cnpjs[] = substr($value->cpf_cnpj, 0, 10);
                }
                else{
                    $cnpjs[] = $value->cpf_cnpj;
                }

            }
            // dd($cnpjs);

            if(!empty($cnpjs)){
                $GrupoEmpresarialObj->orWhere(function ($query) use ($cnpjs){
                    foreach ($cnpjs as $cnpj){
                        $query->orWhere('raiz_cnpj', 'like', $cnpj . '%' );
                    }
                })
                ->orWhereHas('participantes', function($query) use ($cnpjs){
                    $query->where(function ($q) use ($cnpjs){
                        foreach ($cnpjs as $cnpj){
                            $q->orWhere('raiz_cnpj', 'like', $cnpj . '%');
                        }
                    });
                });
            }
            else{
                $GrupoEmpresarialObj->whereRaw('1=2');
            }

        }

        // dd($GrupoEmpresarialObj->toSql());

        $GrupoEmpresarialObj = $GrupoEmpresarialObj->get();
        $response = [];
        foreach ($GrupoEmpresarialObj as $key => $grupo) {

            $participantes_array = $grupo->participantes->pluck('raiz_cnpj');
            
            $participante_query = ClienteNasajon::select(DB::Raw("cpf_cnpj || ' - ' || nome as participante"));

            $participante_query->where(function($query) use ($participantes_array){
                foreach($participantes_array as $value){
                    $query->orWhere('cpf_cnpj', 'like', $value . '%');
                }
            });

            $participante_query->orWhere('cpf_cnpj', 'like', $grupo->raiz_cnpj . "%");
            
            $participantes = implode(",<br />", $participante_query->get()->pluck('participante')->toArray());

            if(!empty($participante_query->get()->toArray())){
                $response[] = [
                    'nome' => $grupo->nome,
                    'raiz_cnpj' => $grupo->raiz_cnpj,
                    'participantes' => $participantes,
                    'id' => encrypt($grupo->id)
                ];
            }
        }
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $response
        ];
        return response()->json($return);
    }


    public function modalAdicionar(Request $request){
    	return view("programs.grupo_empresarial.modal.adicionar");
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
            ]);
        }
        
        try{
            $GrupoEmpresarialObj = GrupoEmpresarial::with(['createdby', 'participantes'])->find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $principal = ClienteNasajon::where('cpf_cnpj', 'like', $GrupoEmpresarialObj->raiz_cnpj . "%")->first();

        foreach($GrupoEmpresarialObj->participantes as $key => $value){

            $participante = ClienteNasajon::select('nome')->where('cpf_cnpj', 'like', $value->raiz_cnpj . "%")->get()->first();

            if(!is_null($participante)){

                $participantes[] = [
                    'nome' => utf8_encode($participante->nome),
                    'raiz_cnpj' => $value->raiz_cnpj
                ];
    
            }
        }

        $dados = array_merge($GrupoEmpresarialObj->toArray(), ['nome_cliente' => $principal->nome]);

        return view("programs.grupo_empresarial.modal.editar")->with(['dados' => $dados, 'participantes' => $participantes]);

    }

    public function modalDeletar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        try{
            $GrupoEmpresarialObj = GrupoEmpresarial::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $GrupoEmpresarialObj->toArray();
        // $dados['valor'] = \parserValor($dados['valor']);
        // $data = new Carbon($dados['data_atualizacao']);
        // $dados['data_atualizacao'] = $data->format('d/m/Y');
    	return view("programs.grupo_empresarial.modal.deletar")->with(['dados' => $dados]);
    }
    

    public function adicionar(GrupoEmpresarialSalvar $request){
        $fields = $request->only(['raiz_cnpj', 'nome', 'participantes']);

        $GrupoEmpresarialObj = new GrupoEmpresarial;
        $GrupoEmpresarialObj->nome = $fields['nome'];
        $GrupoEmpresarialObj->raiz_cnpj = strlen($fields['raiz_cnpj']) == 18 ? substr($fields['raiz_cnpj'], 0, 10) : $fields['raiz_cnpj'];
        $GrupoEmpresarialObj->created_by = Auth::id();
        $GrupoEmpresarialObj->save();

        if(count($fields['participantes'])){
            foreach ($fields['participantes'] as $key => $value) {
                if(empty($value)){
                    continue;
                }
                $GrupoEmpresarialParticipanteObj = new GrupoEmpresarialParticipante();
                $GrupoEmpresarialParticipanteObj->grupo_empresarial_id = $GrupoEmpresarialObj->id;
                $GrupoEmpresarialParticipanteObj->raiz_cnpj = strlen($value) == 18? substr($value, 0, 10 ) : $value;
                $GrupoEmpresarialParticipanteObj->created_by = Auth::id();
                $GrupoEmpresarialParticipanteObj->save();
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(GrupoEmpresarialSalvar $request){
        $fields = $request->only(['id', 'raiz_cnpj', 'participantes']);
        
        $GrupoEmpresarialObj = GrupoEmpresarial::with('participantes')->find($fields['id']);
        $GrupoEmpresarialObj->raiz_cnpj = strlen($fields['raiz_cnpj']) == 18 ? substr($fields['raiz_cnpj'], 0, 10) : $fields['raiz_cnpj'];
        $GrupoEmpresarialObj->updated_by = Auth::id();
        $GrupoEmpresarialObj->save();

        foreach ($GrupoEmpresarialObj->participantes as $participante){
            $participante->delete();
        }

        if(count($fields['participantes'])){
            foreach ($fields['participantes'] as $key => $value) {
                if(empty($value)){
                    continue;
                }
                $GrupoEmpresarialParticipanteObj = new GrupoEmpresarialParticipante();
                $GrupoEmpresarialParticipanteObj->grupo_empresarial_id = $GrupoEmpresarialObj->id;
                $GrupoEmpresarialParticipanteObj->raiz_cnpj = strlen($value) == 18 ? substr($value, 0, 10 ) : $value;
                $GrupoEmpresarialParticipanteObj->created_by = Auth::id();
                $GrupoEmpresarialParticipanteObj->save();
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
    
    public function excluir(Request $request){
        $fields = $request->only(['id']);
        
        $GrupoEmpresarialObj = GrupoEmpresarial::find($fields['id']);
        $GrupoEmpresarialObj->deleted_by = Auth::id();
        $GrupoEmpresarialObj->save();

        $GrupoEmpresarialParticipanteObj = GrupoEmpresarialParticipante::where('grupo_empresarial_id', $fields['id'])->delete();

        $GrupoEmpresarialObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

}
