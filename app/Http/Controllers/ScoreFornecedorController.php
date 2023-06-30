<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\ScoreFornecedorSalvarRequest;

use App\NotasEntradasNasajon;
use App\FornecedorNasajon;
use App\ScoreFornecedore;
use App\ScoreFornecedorFormulario;
use App\ScoreFornecedoresFormularioRespondido;
use App\NasajonEstabelecimento;
use Illuminate\Support\Facades\Storage;

class ScoreFornecedorController extends Controller
{
    private $path = 'public/score_fornecedor/ficha_tecnica/';

    private $tipo_simples = [
        '0' => 'Indefinido',
        '1' => 'Optante',
        '2' => 'Não Optante'
    ];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ScoreFornecedores") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ScoreFornecedores');

        return view('programs.score_fornecedores.index');
    }

    public function filtro(Request $request){
        ini_set('memory_limit','1024M');
        $campos = $request->only(['fornecedor','score']);
        
        $FornecedorNasajonObj = FornecedorNasajon::select(DB::raw('distinct(id) as id, cnpj_cpf, nome, uf, tiposimples'));

        $score = ScoreFornecedore::select('fornecedor_nasajon_id')->get()->pluck('fornecedor_nasajon_id')->toArray();

        if($campos['score'] == 'sem_score'){
            $FornecedorNasajonObj->whereNotIn('id',$score);
        }else if($campos['score'] == 'com_score'){
            $FornecedorNasajonObj->whereIn('id',$score);
        }

        if(!empty($campos['fornecedor'])){
            $FornecedorNasajonObj->where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike', trim($campos['fornecedor']));

            if(empty($FornecedorNasajonObj)){
                return response()->json([
                    'status' => 'success',
                    'message' => '',
                    'error' => [],
                    'response' => []
                ]);
            }
            
        }

        $FornecedorNasajonObj = $FornecedorNasajonObj->get();
        $retorno = [];
        $contador_Score = [
            'realizado' => 0,
            'nao_realizado' => 0
        ];

        $FornecedorNasajonObj->each(function($query) use (&$retorno,$score,&$contador_Score){
            $score_realizado = false;

            if(in_array($query->id,$score)){
                $score_realizado = true;
                $contador_Score['realizado'] ++;
            }else{
                $contador_Score['nao_realizado'] ++;
            }


            $retorno[] = [
                'fornecedor' => $query->nome.' - '.$query->cnpj_cpf,
                'estado' => $query->uf,
                'regime_tributacao' => (!empty($query->tiposimples)) ? $this->tipo_simples[$query->tiposimples] : '',
                'score' => $score_realizado,
                'id' => encrypt($query->id)
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ['retorno' => $retorno, 'contador_Score' => $contador_Score]
        ]);
    }

    public function modalFormularioScore(Request $request){
        $id = $request->only(['id']);

        $formulario_query = ScoreFornecedorFormulario::with(['grupoPergunta','tipoResposta'])->get();
        $formulario_Array = [];

        $formulario_query->each(function($query) use (&$formulario_Array){

            if(!isset($formulario_Array[$query->grupoPergunta->grupo_pergunta])){
                $formulario_Array[$query->grupoPergunta->grupo_pergunta] = [
                    'id' => $query->id,
                    'grupo' => $query->grupoPergunta->grupo_pergunta,
                    'pergunta' => [],
                    'tipo_resposta' => [],
                    'id_proximo' => null,
                    'id_anterior' => null
                ];
            }

            $formulario_Array[$query->grupoPergunta->grupo_pergunta]['pergunta'][] = [
                'pergunta' => $query->pergunta,
                'id' => $query->id,
                'tipo_resposta_id' => $query->tipoResposta->id
            ];

            $formulario_Array[$query->grupoPergunta->grupo_pergunta]['tipo_resposta'][] = $query->tipoResposta->tipo_respostas;

        });

        foreach($formulario_Array as $key => $formulario){
            $formulario_Array[$key]['id_proximo'] = next($formulario_Array)['id'];
        }
        
        return view('programs.score_fornecedores.modal.formulario_score')->with(['id' => $id, 'formulario' => $formulario_Array, 'active' => true,'active_menu' => true]);
    }

    public function salvar(ScoreFornecedorSalvarRequest $request){
        $campos = $request->only(['id_nota','pergunta','resposta','grupo_pergunta','tipo_resposta','iso','file','resposta_abvtex']);
        
        try{
            $id = decrypt($campos['id_nota']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => [],
            ];
            return response()->json($return);
        }

        $verifica_score = ScoreFornecedore::where("fornecedor_nasajon_id",$id)->first();
        
        if(!empty($verifica_score)){
            $score_respondido = ScoreFornecedoresFormularioRespondido::where('score_fornecedores_id', $verifica_score->id);
            $score_respondido->deleted_by = Auth::id();
            $score_respondido->delete();

            foreach($campos['pergunta'] as $key => $pergunta){
                $formulario = new ScoreFornecedoresFormularioRespondido;
                $formulario->pergunta = $pergunta;
                $formulario->resposta = (isset($campos['resposta'][$key])) ? $campos['resposta'][$key] : null;
                $formulario->grupo_pergunta = $campos['grupo_pergunta'][$key];
                $formulario->score_fornecedores_id = $verifica_score->id;
                $formulario->created_by = Auth::id();
                $formulario->score_fornecedor_formulario_tipo_respostas_id = $campos['tipo_resposta'][$key];
                $formulario->score_fornecedor_formulario_id = $key;
                $formulario->save();

                if(isset($campos['tipo_resposta'][$key]) && isset($campos['resposta'][$key])){
                    if($campos['tipo_resposta'][$key] == 11 && $campos['resposta'][$key] == 'Sim'){
                        foreach($campos['iso'] as $key_iso => $iso){
                            $formulario->resposta = $iso;
                            $formulario->save();
                        }
                    }
                }
                
                if(isset($campos['tipo_resposta'][$key]) && isset($campos['resposta'][$key])){
                    if($campos['tipo_resposta'][$key] == 12 && $campos['resposta'][$key] == 'Sim'){
                        foreach($campos['resposta_abvtex'] as $key_abvtex => $abvtex){
                            $formulario->resposta = $abvtex;
                            $formulario->save();
                        }
                    }else if($campos['tipo_resposta'][$key] == 12 && $campos['resposta'][$key] == 'Não'){
                        $formulario->resposta = 'Não';
                        $formulario->save();
                    }
                }

                $file_ficha_tecnica = $request->file('file');
                if($request->hasFile('file') && $campos['tipo_resposta'][$key] == 10 && $campos['resposta'][$key] == 'Sim'){
                    foreach($file_ficha_tecnica as $key => $files){
                        $file = $formulario->id.'.'.$files->getClientOriginalExtension();
                        $formulario->resposta = $this->path.$file;
                        $files->storeAs($this->path, $file);
                        $formulario->save();
                    }
                }
            }

        }else{

            $score = new ScoreFornecedore;
            $score->fornecedor_nasajon_id = $id;
            $score->created_by = Auth::id();
            $score->save();

            foreach($campos['pergunta'] as $key => $pergunta){
                
                $formulario = new ScoreFornecedoresFormularioRespondido;
                $formulario->pergunta = $pergunta;
                $formulario->resposta = (isset($campos['resposta'][$key])) ? $campos['resposta'][$key] : null;
                $formulario->grupo_pergunta = $campos['grupo_pergunta'][$key];
                $formulario->score_fornecedores_id = $score->id;
                $formulario->created_by = Auth::id();
                $formulario->score_fornecedor_formulario_tipo_respostas_id = $campos['tipo_resposta'][$key];
                $formulario->score_fornecedor_formulario_id = $key;
                $formulario->save();

                if(isset($campos['tipo_resposta'][$key]) && isset($campos['resposta'][$key])){
                    if($campos['tipo_resposta'][$key] == 11 && $campos['resposta'][$key] == 'Sim'){
                        foreach($campos['iso'] as $key_iso => $iso){
                            $formulario->resposta = $iso;
                            $formulario->save();

                        }
                    }
                }

                if($campos['tipo_resposta'][$key] == 12 && $campos['resposta'][$key] == 'Sim'){
                    foreach($campos['resposta_abvtex'] as $key_abvtex => $abvtex){
                        $formulario->resposta = $abvtex;
                        $formulario->save();
                    }
                }else if($campos['tipo_resposta'][$key] == 12 && $campos['resposta'][$key] == 'Não'){
                    $formulario->resposta = 'Não';
                    $formulario->save();
                }

                $file_ficha_tecnica = $request->file('file');
                if($request->hasFile('file') && $campos['tipo_resposta'][$key] == 10 && $campos['resposta'][$key] == 'Sim'){
                    foreach($file_ficha_tecnica as $key => $files){
                        $file = $formulario->id.'.'.$files->getClientOriginalExtension();
                        $formulario->resposta = $this->path.$file;
                        $files->storeAs($this->path, $file);
                        $formulario->save();
                    }
                }

            }

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Score Realizado.',
            'error' => [],
            'response' => []
        ]);
    }

    public function modalFormularioEditar(Request $request){
        $campos = $request->only(['id']);

        try{
            $id = decrypt($campos['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => [],
            ];
            return response()->json($return);
        }

        $escore = ScoreFornecedore::where('fornecedor_nasajon_id',$id)->first();
        $respondido = ScoreFornecedoresFormularioRespondido::where('score_fornecedores_id',$escore->id)->get();
        
        $formulario_query = ScoreFornecedorFormulario::with(['grupoPergunta','tipoResposta'])->get();
        $formulario_Array = [];

        $formulario_query->each(function($query) use (&$formulario_Array){

            if(!isset($formulario_Array[$query->grupoPergunta->grupo_pergunta])){
                $formulario_Array[$query->grupoPergunta->grupo_pergunta] = [
                    'id' => $query->id,
                    'grupo' => $query->grupoPergunta->grupo_pergunta,
                    'pergunta' => [],
                    'tipo_resposta' => [],
                    'id_proximo' => null,
                    'id_anterior' => null
                ];
            }

            $formulario_Array[$query->grupoPergunta->grupo_pergunta]['pergunta'][] = [
                'pergunta' => $query->pergunta,
                'id' => $query->id,
                'tipo_resposta_id' => $query->tipoResposta->id
            ];

            $formulario_Array[$query->grupoPergunta->grupo_pergunta]['tipo_resposta'][] = $query->tipoResposta->tipo_respostas;

        });

        foreach($formulario_Array as $key => $formulario){
            $formulario_Array[$key]['id_proximo'] = next($formulario_Array)['id'];
        }

        return view('programs.score_fornecedores.modal.formulario_editar')->with(['id' => $campos['id'],'respondido' => $respondido,'formulario' => $formulario_Array,'active' => true,'active_menu' => true]);
    }

}
