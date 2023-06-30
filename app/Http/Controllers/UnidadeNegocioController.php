<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\UnidadeNegocio;
use App\User;

use App\Http\Requests\UnidadeNegocioRequest;

use Auth;

class UnidadeNegocioController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\UnidadeNegocio") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\UnidadeNegocio');

        return view('programs.unidade_negocio.index');
    }

    public function modalAdicionar() {
        return view('programs.unidade_negocio.modal.adicionar');
    }

    public function adicionar(UnidadeNegocioRequest $request){
        $fields = $request->only('unidade', 'usuario_responsavel');

        $usuario = User::withTrashed()->where('name', 'ilike', $fields['usuario_responsavel'])->first();

        $unidadeNegocioObj = new UnidadeNegocio;
        $unidadeNegocioObj->unidade = strtoupper($fields['unidade']);
        $unidadeNegocioObj->users_id = $usuario->id;
        $unidadeNegocioObj->created_by = Auth::id();
        $unidadeNegocioObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('unidade', 'usuario_responsavel');

        $query = UnidadeNegocio::select()->with(['detalhesUsuarioResponsavel']);
        if(!empty($fields['unidade'])){
            $query->where('unidade', 'ilike', '%'.$fields['unidade'].'%');
        }
        if(!empty($fields['usuario_responsavel'])){
            $query->whereHas('detalhesUsuarioResponsavel', function($query) use($fields){ 
                $query->where('name', 'ilike', '%'.$fields['usuario_responsavel'].'%');
            });
        }
        $unidade_gerente = $this->verificacaoGerente();
        if(!empty($unidade_gerente)){
            $query->whereIn('id',$unidade_gerente);
        }   

        $result = $query->get();

        $unidades_negocio = [];
        foreach($result as $unidade_negocio){
            $unidades_negocio[] = [
                'id' => encrypt($unidade_negocio->id),
                'unidade' => $unidade_negocio->unidade,
                'usuario' => $unidade_negocio->detalhesUsuarioResponsavel->name,
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $unidades_negocio
        ];
        return response()->json($response);
    }

    public function modalEditar(Request $request) {
        $id = $request->only('id')['id'];

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

        $unidade_negocio = UnidadeNegocio::find($id);

        $dados = [
            'id' => encrypt($unidade_negocio->id),
            'unidade' => $unidade_negocio->unidade,
            'usuario' => $unidade_negocio->detalhesUsuarioResponsavel->name,
        ];

        return view('programs.unidade_negocio.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(UnidadeNegocioRequest $request){
        $fields = $request->only('id', 'unidade', 'usuario_responsavel');

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

        $usuario = User::withTrashed()->where('name', 'ilike', $fields['usuario_responsavel'])->first();

        $unidadeNegocioObj = UnidadeNegocio::find($id);
        $unidadeNegocioObj->unidade = strtoupper($fields['unidade']);
        $unidadeNegocioObj->users_id = $usuario->id;
        $unidadeNegocioObj->updated_by = Auth::id();
        $unidadeNegocioObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request) {
        $id = $request->only('id')['id'];

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

        $unidade_negocio = UnidadeNegocio::find($id);

        $dados = [
            'id' => encrypt($unidade_negocio->id),
            'unidade' => $unidade_negocio->unidade,
            'usuario' => $unidade_negocio->detalhesUsuarioResponsavel->name,
        ];

        return view('programs.unidade_negocio.modal.deletar')->with(['dados' => $dados]);
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
            ]);
        }
        
        $unidadeNegocioObj = UnidadeNegocio::find($id);
        $unidadeNegocioObj->deleted_by = Auth::id();
        $unidadeNegocioObj->save();
        $unidadeNegocioObj->delete();

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

        $unidade_gerente = $this->verificacaoGerente();

        $query = UnidadeNegocio::select();
        $query->limit("15");
        $query->orderBy('unidade', "ASC");
        $query->where("unidade",  'ilike', "%". $fields["term"] ."%");
        if(!empty($unidade_gerente)){
            $query->whereIn('id',$unidade_gerente);
        }    
        
        $result = $query->get();
        $result = $result->toArray();

        foreach ($result as $value){
            $value = (array) $value;
            $return[] = $value['unidade'];
        }
        
        return response()->json($return);
    }

    public function modalBuscar(){
        return view('programs.unidade_negocio.modal.buscar');
    }

    private function verificacaoGerente(){
        $query = UnidadeNegocio::select();
        $query->where('users_id', Auth::id());
        $result = $query->get();

        $unidades = [];

        foreach($result as $unidade){
            $unidades[] = $unidade->id;
        }

        return $unidades;
    }
}
