<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

use App\ScoreFornecedorFormulario;
use App\ScoreFornecedorFormularioTipoRespostas;
use App\ScoreFornecedorFormularioGrupoPergunta;
use App\ScoreFornecedoresFormularioRespondido;

use App\Http\Requests\ScoreFornecedorFormularioGravarRequest;
use App\Http\Requests\ScoreFornecedorFormularioExcluirGrupoRequest;
use App\Http\Requests\ScoreFornecedorFormularioGravarGrupoRequest;
use App\Http\Requests\ScoreFornecedorFormularioEditarRequest;

class ScoreFornecedorFormularioController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ScoreFornecedoresFormularios") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ScoreFornecedoresFormularios');

        return view('programs.score_fornecedores_formularios.index');
    }

    public function carregaFormulario(){
        $retorno = [];
        $formularios = ScoreFornecedorFormulario::with(['tipoResposta','grupoPergunta'])->get();
        
        foreach($formularios as $formulario){
            $retorno[] = [
                'pergunta' => $formulario->pergunta,
                'grupo_pergunta' => $formulario->grupoPergunta->grupo_pergunta,
                'tipo_resposta' => $formulario->tipoResposta->tipo_respostas,
                'id' => encrypt($formulario->id)
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'dados' => $retorno
            ]
        ];

        return response()->json($response);
    }

    public function modalAdicionarQuestao(){
        $tipo_resposta_array = [];
        $grupo_pergunta_array = [];
        $tipo_resposta = ScoreFornecedorFormularioTipoRespostas::get();
        $grupo_pergunta = ScoreFornecedorFormularioGrupoPergunta::get();

        foreach($grupo_pergunta as $pergunta){
            $grupo_pergunta_array[$pergunta->id] = $pergunta->grupo_pergunta;
        }

        foreach($tipo_resposta as $resposta){
            $tipo_resposta_array[$resposta->id] = $resposta->tipo_respostas;
        }

        return view('programs.score_fornecedores_formularios.modal.adicionar_questao')->with(['tipo_resposta' => $tipo_resposta_array,'grupo_pergunta' => $grupo_pergunta_array]);
    }

    public function modalGerenciarGrupos(){
        $grupo_pergunta_array = [];
        $grupo_pergunta = ScoreFornecedorFormularioGrupoPergunta::get();

        foreach($grupo_pergunta as $pergunta){
            $grupo_pergunta_array[$pergunta->id] = $pergunta->grupo_pergunta;
        }

        return view('programs.score_fornecedores_formularios.modal.gerenciar_grupos')->with(['grupo_pergunta' => $grupo_pergunta_array]);
    }

    public function gravarGrupo(ScoreFornecedorFormularioGravarGrupoRequest $request){
        $campos = $request->only(["novo_grupo"]);

        $formulario = new ScoreFornecedorFormularioGrupoPergunta;
        $formulario->grupo_pergunta = $campos["novo_grupo"];
        $formulario->created_by = Auth::id();

        if($formulario->save()){
            $response = [
                "status" => 'success',
                "message" => 'Gravado com Sucesso.',
                "error" => [],
                "response" => ['value' => $formulario->id, 'text' => $formulario->grupo_pergunta]
            ];
        }else{
            $response = [
                "status" => 'error',
                "message" => 'Falha ao gravar, tente novamente mais tarde.',
                "error" => [],
                "response" => []
            ];
        }

        return response()->json($response);
    }

    public function excluirGrupo(ScoreFornecedorFormularioExcluirGrupoRequest $request){
        $filter = $request->only(['grupo']);

        try{
            $score = ScoreFornecedorFormularioGrupoPergunta::findOrFail($filter['grupo']);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ],422);
        }

        $score->deleted_by = Auth::user()->id;
        $score->save();
        $score->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Grupo excluído com sucesso.',
            'error' => '',
            'response' => ['id' => $filter['grupo']]
        ]);
    }

    public function gravarQuestao(ScoreFornecedorFormularioGravarRequest $request){
        $campos = $request->only(["questao","tipo_questao","grupo"]);

        $formulario = new ScoreFornecedorFormulario;
        $formulario->pergunta = $campos["questao"];
        $formulario->score_fornecedor_formulario_grupo_perguntas_id = $campos["grupo"];
        $formulario->score_fornecedor_formulario_tipo_respostas_id = $campos["tipo_questao"];
        $formulario->created_by = Auth::id();

        if($formulario->save()){
            $response = [
                "status" => 'success',
                "message" => 'Gravado com Sucesso.',
                "error" => [],
                "response" => []
            ];

            return response()->json($response);
        }else{
            $response = [
                "status" => 'error',
                "message" => 'Falha ao gravar, tente novamente mais tarde.',
                "error" => [],
                "response" => []
            ];
            return response()->json($response,422);
        }

    }

    public function modalEditarPergunta(Request $request){
        $campos = $request->only(["id"]);

        try{
            $id = decrypt($campos['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $tipo_resposta_array = [];
        $grupo_pergunta_array = [];
        $formulario = ScoreFornecedorFormulario::find($id);
        $tipo_resposta = ScoreFornecedorFormularioTipoRespostas::get();
        $grupo_pergunta = ScoreFornecedorFormularioGrupoPergunta::get();

        foreach($grupo_pergunta as $pergunta){
            $grupo_pergunta_array[$pergunta->id] = $pergunta->grupo_pergunta;
        }

        foreach($tipo_resposta as $resposta){
            $tipo_resposta_array[$resposta->id] = $resposta->tipo_respostas;
        }

        return view('programs.score_fornecedores_formularios.modal.editar_questao')->with(['dados' => $formulario,'id' => $campos['id'],'tipo_resposta' => $tipo_resposta_array,'grupo_pergunta' => $grupo_pergunta_array]);
    }

    public function editarPergunta(ScoreFornecedorFormularioEditarRequest $request){
        $campos = $request->only(["questao","tipo_questao","grupo_pergunta","id"]);

        try{
            $id = decrypt($campos['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $formulario = ScoreFornecedorFormulario::find($id);
        $formulario->pergunta = $campos['questao'];
        $formulario->score_fornecedor_formulario_tipo_respostas_id = $campos['tipo_questao'];
        $formulario->score_fornecedor_formulario_grupo_perguntas_id = $campos['grupo_pergunta'];
        $formulario->updated_by = Auth::id();

        $formulario_respondido =  ScoreFornecedoresFormularioRespondido::where('score_fornecedor_formulario_id',$id)->get();

        if(!empty($formulario_respondido)){
            $grupo_pergunta = ScoreFornecedorFormularioGrupoPergunta::find($campos['grupo_pergunta']);
            $formulario_respondido->each(function($query) use ($campos,$grupo_pergunta){
                $query->pergunta = $campos['questao'];
                $query->score_fornecedor_formulario_tipo_respostas_id = $campos['tipo_questao'];
                $query->grupo_pergunta = $grupo_pergunta->grupo_pergunta;
                $query->save();
            });
        }

        if($formulario->save()){
            $response = [
                "status" => 'success',
                "message" => 'Editado com Sucesso.',
                "error" => [],
                "response" => []
            ];
            return response()->json($response);
        }else{
            $response = [
                "status" => 'error',
                "message" => 'Falha ao editar, tente novamente mais tarde.',
                "error" => [],
                "response" => []
            ];
            return response()->json($response,422);
        }

    }

    public function excluirPergunta(Request $request){
        $filter = $request->only(['id']);

        try{
            $fields = decrypt($filter['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        try{
            $score = ScoreFornecedorFormulario::findOrFail($fields);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ],422);
        }

        $score->deleted_by = Auth::user()->id;
        $score->save();
        $score->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pergunta excluída com sucesso.',
            'error' => '',
            'response' => []
        ]);
    }
}
