<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests\ContatoEmergenciaEditarRequest;
use App\Http\Requests\ContatoEmergenciaAdicionarRequest;

class ContatoDeEmergenciaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ContatoDeEmergencia") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ContatoDeEmergencia');

        return view('programs.contato_emergencia.index');
    }

    public function filtro(Request $request){
        $fields = $request->only('nome','inativo');
      
        $UserObj = User::where('tipo_usuario_id', '!=', 21) ->orderBy('id', 'asc');

        if(!empty($fields["nome"])){
            $UserObj->where('name', $fields["nome"]);
        }
        $ativo=true;
        if(!empty($fields['inativo'])){
        
                $User = $UserObj->onlyTrashed();
                $ativo=false;
        }
        $User = $UserObj->get();

        $dadosUsuario = [];

        foreach($User as $usuario){
            $dadosUsuario[] = [
                'id' => encrypt($usuario->id),
                'usuario' => $usuario->username,
                'nome' => $usuario->name,
                'telefone' => $usuario->celular,
                'setor' => $usuario->setor,
                'contato_emergencia' => $usuario->contato_emergencia,
                'telefone_emergencia' => $usuario->telefone_emergencia,
                'ativo' => $ativo
            ];
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => [],
            'response' => [
                'usuario' => $dadosUsuario
            ] 
        ];
        return response()->json($retorno, 200);
    }

    public function autoCompleteUsuario(Request $request){
        $fields = $request->only(["term"]);
        $return = [];
        $query = User::select('id','name')
            ->limit("15")
            ->where('tipo_usuario_id', '!=', 21)
            ->where('name', 'ilike', '%'.$fields["term"].'%')
            ->get()
            ->toArray();
        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'label' => $value['name'],
                'id' => encrypt($value['id']),
            ];
        }
        return response()->json($return);
    }

    public function modalAdicionar() {
    
        return view('programs.contato_emergencia.modal.adicionar');
    }

    public function salvar(ContatoEmergenciaAdicionarRequest $request){
        $campo = $request->only('nome','telefone','setor','contato_emergencia','telefone_emergencia','email');

        $temp = explode(" ",$campo['nome']);

        $user_name = $temp[0] . "." . $temp[count($temp)-1];
        $contatoEmergenciaObj = new User;
        $contatoEmergencia = User::where('username', 'ilike', $user_name)->exists();
        if($contatoEmergencia === true){
           
            $contatoEmergenciaObj->username=  $user_name . '1';
        }else{
            $contatoEmergenciaObj->username=  $user_name;
        }

       
        $contatoEmergenciaObj->name=  $campo['nome'];
        $contatoEmergenciaObj->tipo_usuario_id=11;
        $contatoEmergenciaObj->celular= $campo['telefone'];
        $contatoEmergenciaObj->setor= $campo['setor'];
        $contatoEmergenciaObj->contato_emergencia= $campo['contato_emergencia'];
        $contatoEmergenciaObj->telefone_emergencia = $campo['telefone_emergencia'];
        $contatoEmergenciaObj->email= empty($campo['email'])? 'gestaodepessoas@tecidosmn.com.br' : $campo['email']; 
        $contatoEmergenciaObj->password='123';
        $contatoEmergenciaObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalEditar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $contatoEmergenciaObj = User::find($id);
        if(is_null($contatoEmergenciaObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

    

        $dados = [

        'id' => encrypt($contatoEmergenciaObj->id),
        'usuario' => $contatoEmergenciaObj->username,
        'nome' => $contatoEmergenciaObj->name,
        'telefone' => $contatoEmergenciaObj->celular,
        'setor' => $contatoEmergenciaObj->setor,
        'contato_emergencia' => $contatoEmergenciaObj->contato_emergencia,
        'telefone_emergencia' => $contatoEmergenciaObj->telefone_emergencia,
        'email' =>  $contatoEmergenciaObj->email
    ];
        return view('programs.contato_emergencia.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ContatoEmergenciaEditarRequest $request){
        $campo = $request->only('id','nome','telefone','setor','contato_emergencia','telefone_emergencia','email');
   
        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $contatoEmergenciaObj = User::find($id);
        $contatoEmergenciaObj->name=  $campo['nome'];
        $contatoEmergenciaObj->celular= $campo['telefone'];
        $contatoEmergenciaObj->setor= $campo['setor'];
        $contatoEmergenciaObj->contato_emergencia= $campo['contato_emergencia'];
        $contatoEmergenciaObj->telefone_emergencia = $campo['telefone_emergencia'];
        $contatoEmergenciaObj->email= empty($campo['email']) ? 'gestaodepessoas@tecidosmn.com.br' : $campo['email']; 
        $contatoEmergenciaObj->save(); 

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $userObj = User::find($id);

        if(is_null($userObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt($userObj->id),
            'descricao' => $userObj->name
        ];
 
        return view('programs.contato_emergencia.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $userObj = User::find($id);
        $userObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }





}
