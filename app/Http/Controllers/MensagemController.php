<?php

namespace App\Http\Controllers;

use Auth;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

use App\TipoUsuario;
use App\Mensagem;
use App\MensagemTipoUsuario;

use App\Http\Requests\MensagemSalvarRequest;
use App\Http\Requests\MensagemEditarRequest;
use App\Http\Requests\MensagemFiltroRequest;

use Carbon\Carbon;

class MensagemController extends Controller
{
    public $path = 'public/mensagem/';

    private function tipoUsuarios(){
        $TipoUsuarioObj = TipoUsuario::orderBy('nome')->get();
        $tipo_usuario[null] = 'Tipo de Usuário';

        foreach($TipoUsuarioObj as $tipo){
            $tipo_usuario[encrypt($tipo->id)] = $tipo->nome;
        }

        return $tipo_usuario;
    }
    
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Mensagem") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Mensagem');

        $tipo_usuario = $this->tipoUsuarios();

        return view('programs.mensagem.index')->with(['tipo_usuario' => $tipo_usuario]);
    }

    public function filtro(MensagemFiltroRequest $request){
        $fields = $request->only('tipo_usuario','data_busca_inicial','data_busca_final');

        $data_busca_inicial = (!empty($fields['data_busca_inicial'])) ? Carbon::createFromFormat('d/m/Y', $fields['data_busca_inicial'])->format('Y-m-d') : '';
        $data_busca_final = (!empty($fields['data_busca_final'])) ? Carbon::createFromFormat('d/m/Y', $fields['data_busca_final'])->format('Y-m-d') : '';

        $MensagemObj = Mensagem::with(['tipoUsuarios', 'tipoUsuarios.tipoUsuario']);

        if(!empty($data_busca_inicial)){
            $MensagemObj->where('data_inicio','>=',$data_busca_inicial);
        }

        if(!empty($data_busca_final)){
            $MensagemObj->where('data_inicio','<=',$data_busca_final);
        }

        try{
            $field_perfil = (!empty($fields['tipo_usuario'])) ? decrypt($fields['tipo_usuario']) : '';
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consulta, tente novamente mais tarde.',
                'error' => $e->getMessage(),
                'response' => '',
            ]);
        }

        if($field_perfil != '' && $field_perfil != 'todos'){
            $MensagemObj->whereHas('tipoUsuarios', function($query) use ($field_perfil){
                $query->where('tipo_usuario_id', $field_perfil);
            });
        }

        $response = [];
        $mensagens = $MensagemObj->get();
        $mensagens->each(function($mensagem) use(&$response){
            $tipo_usuario_retorno = '';
            $mensagem->tipoUsuarios->each(function($tipo_usuario) use(&$tipo_usuario_retorno){
                $tipo_usuario_retorno .= $tipo_usuario->tipoUsuario->nome .', ';
            });
            if(empty($tipo_usuario_retorno)){
                $tipo_usuario_retorno = 'Todos';
            }else{
                $tipo_usuario_retorno = substr($tipo_usuario_retorno, 0, -2);
            }
            $response[] = [
                'id' => encrypt($mensagem->id),
                'titulo' => $mensagem->titulo,
                'tipo_usuario' => $tipo_usuario_retorno,
                'data_inicio' => parserData($mensagem->data_inicio),
                'data_fim' => parserData($mensagem->data_fim),
            ];
        });

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $response],
        ]);

    }

    public function modalAdicionar(){        
        return view('programs.mensagem.modal.adicionar');
    }
    
    public function salvar(MensagemSalvarRequest $request){
        $fields = $request->only('arquivo','titulo','data_inicio','data_final','tipo_usuarios');

        $tipo_usuarios = [];
        if(isset($fields['tipo_usuarios'])){
            $tipo_usuarios = $fields['tipo_usuarios'];
        }

        foreach($tipo_usuarios as $key => $value){
            try{
                $tipo_usuarios[$key] = decrypt($value);
            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao consulta, tente novamente mais tarde.',
                    'error' => $e->getMessage(),
                    'response' => '',
                ]);
            }
        }

        $MensagemObj = new Mensagem;
        $MensagemObj->titulo = $fields['titulo'];
        $MensagemObj->data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0,0,0);
        $MensagemObj->data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_final'])->setTime(0,0,0);
        $MensagemObj->created_by = Auth::id();
        $MensagemObj->save();

        if($request->hasFile('arquivo')){
            $file = $this->path.$MensagemObj->id . '.' . $request->file('arquivo')->getClientOriginalExtension();
            $request->file('arquivo')->storeAs($this->path,  $MensagemObj->id . '.' . $request->file('arquivo')->getClientOriginalExtension());
            $MensagemObj->arquivo = $file;
            $MensagemObj->nome_arquivo = $request->file('arquivo')->getClientOriginalName();
            $MensagemObj->save();
        }


        foreach($tipo_usuarios as $tipo_usuario){
            $MensagemTipoUsuarioObj  = new MensagemTipoUsuario;
            $MensagemTipoUsuarioObj->mensagem_id = $MensagemObj->id;
            $MensagemTipoUsuarioObj->tipo_usuario_id = $tipo_usuario;
            $MensagemTipoUsuarioObj->created_by = Auth::id();
            $MensagemTipoUsuarioObj->save();
        }

        if(!$MensagemObj){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao gravar',
                'error' => $e->getMessage(),
                'response' => 'ok',
            ]);
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => 'Mensagem Gravada com Sucesso',
                'error' => '', 
                'response' => 'ok',
            ]);
        }
    }

    public function modalEditar(Request $request){
        $fields = $request->only('id');

        try{
            $id = decrypt($fields['id']);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consulta, tente novamente mais tarde.',
                'error' => $e->getMessage(),
                'response' => '',
            ]);
        }

        $MensagemObj = Mensagem::find($id);

        $dados = [];
        $tipo_usuarios = [];

        if(!empty($MensagemObj)){

            foreach($MensagemObj->tipoUsuarios as $tipo_usuario){
                $tipo_usuarios[] = [
                    'nome' => $tipo_usuario->tipoUsuario->nome,
                    'id' => encrypt($tipo_usuario->tipo_usuario_id),
                ];
            }

            $dados['link_arquivo'] = Storage::url($MensagemObj->arquivo);
            $dados['titulo'] = $MensagemObj->titulo;
            $dados['id'] = encrypt($MensagemObj->id);
            $dados['tipo_usuario'] = $tipo_usuarios;
            $dados['nome_arquivo'] = $MensagemObj->nome_arquivo;
            $dados['data_inicio'] = parserData($MensagemObj->data_inicio);
            $dados['data_fim'] = parserData($MensagemObj->data_fim);
        }

        return view('programs.mensagem.modal.editar')->with(["dados" => $dados]);
    }

    public function editar(MensagemEditarRequest $request){
        $fields = $request->only('id', 'arquivo','titulo','data_inicio','data_final','tipo_usuarios');
        $tipo_usuario = [];
        if(isset($fields['tipo_usuarios'])){
            $tipo_usuario = $fields['tipo_usuarios'];
        }

        foreach($tipo_usuario as $key => $value){
            try{
                $tipo_usuario[$key] = decrypt($value);
            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao consulta, tente novamente mais tarde.',
                    'error' => $e->getMessage(),
                    'response' => '',
                ]);
            }
        }
        try{
            $id = decrypt($fields['id']);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consulta, tente novamente mais tarde.',
                'error' => $e->getMessage(),
                'response' => '',
            ]);
        }

        $MensagemObj = Mensagem::find($id);
        $MensagemObj->titulo = $fields['titulo'];
        $MensagemObj->data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0,0,0);
        $MensagemObj->data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_final'])->setTime(0,0,0);
        $MensagemObj->updated_by = Auth::id();
        $MensagemObj->save();

        if($request->hasFile('arquivo')){
            Storage::delete($MensagemObj->arquivo);
            $file = $this->path.$MensagemObj->id . '.' . $request->file('arquivo')->getClientOriginalExtension();
            $request->file('arquivo')->storeAs($this->path,  $MensagemObj->id . '.' . $request->file('arquivo')->getClientOriginalExtension());
            $MensagemObj->arquivo = $file;
            $MensagemObj->nome_arquivo = $request->file('arquivo')->getClientOriginalName();
            $MensagemObj->save();
        }
        foreach($tipo_usuario as $perfil){
            if($MensagemObj->tipoUsuarios->where('tipo_usuario_id', $perfil)->count() !== 0){
                $pefil = $MensagemObj->tipoUsuarios->where('tipo_usuario_id', $perfil)->first();
                $pefil->updated_by = Auth::id();
                $pefil->save();
            }else{
                $MensagemTipoUsuarioObj  = new MensagemTipoUsuario;
                $MensagemTipoUsuarioObj->mensagem_id = $MensagemObj->id;
                $MensagemTipoUsuarioObj->tipo_usuario_id = $perfil;
                $MensagemTipoUsuarioObj->created_by = Auth::id();
                $MensagemTipoUsuarioObj->save();
            }
        }
        if(empty($tipo_usuario)){
            $MensagemObj->tipoUsuarios->each(function($perfil){
                $perfil->deleted_by = Auth::id();
                $perfil->save();
                $perfil->delete();
            });
        }

        if(!$MensagemObj){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao editar',
                'error' => $e->getMessage(),
                'response' => 'ok',
            ]);
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => 'Mensagem Editada com Sucesso',
                'error' => '',
                'response' => 'ok',
            ]);
        }
    }

    public function excluir(Request $request){
        $filter = $request->only(['id']);

        try{
            $id = decrypt($filter['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        try{
            $MensagemObj = Mensagem::findOrFail($id);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'erro',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ]);
        }
        $MensagemObj->deleted_by = Auth::user()->id;
        $MensagemObj->save();
        $MensagemObj->delete();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => []
        ]);
    }
}
