<?php

namespace App\Http\Controllers;

use Auth;

use Carbon\Carbon;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ScoreFornecedorNotasFiltroRequest;
use App\Http\Requests\ScoreFornecedorNotaSalvarRequest;

use App\NotasImportadasCompra;
use App\FornecedorNasajon;
use App\ScoreFornecedorFormulario;
use App\ScoreFornecedoresNotasLancamento;
use App\ScoreFornecedoresNotasLancamentosDocumento;

class ScoreFornecedorNotasController extends Controller
{
    private $path = 'public/score_fornecedor/ficha_tecnica/';
    private $path_documento = 'public/score_fornecedor/documentos/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ScoreFornecedorNota") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\ScoreFornecedorNota');
        
        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.score_fornecedor_nota.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtro(ScoreFornecedorNotasFiltroRequest $request){
        $campos = $request->only(["estabelecimento","fornecedor","data_emissao_inicio","data_emissao_fim","numero_nota","score"]);
        
        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_emissao_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_emissao_fim'])->format('Y-m-d');

        $notas_compras = NotasImportadasCompra::whereBetween('data_entrada',[$data_inicio,$data_fim]);

        if(!empty($campos['estabelecimento'])){
            $notas_compras->where('estabelecimento_codigo', str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }

        if(!empty($campos['fornecedor'])){
            $fornecedor = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike', trim($campos['fornecedor']))->first();

            if(empty($fornecedor)){
                return response()->json([
                    'status' => 'success',
                    'message' => '',
                    'error' => [],
                    'response' => []
                ]);
            }

            $notas_compras->where('fornecedor_id',$fornecedor->id);
        }

        if(!empty($campos['numero_nota'])){
            $notas_compras->where('documento_numero','ilike','%'.$campos['numero_nota'].'%');
        }

        if(!empty($campos['score'])){
            $notas_compras->where('score',$campos['score']);
        }

        $notas_compras = $notas_compras->get();
        $retorno = [];

        $notas_compras->each(function($query) use (&$retorno){
            $retorno[] = [
                'id' => $query->nota_id,
                'estabelecimento' => $query->estabelecimento_codigo .' - '.$query->estabelecimento_nome,
                'numero' => $query->documento_numero,
                'fornecedor' =>$query->fornecedor_nome.' - '.$query->fornecedor_documento,
                'emissao' => (!empty($query->emissao)) ? parserData($query->emissao) : '',
                'entrada' => (!empty($query->data_entrada)) ? parserData($query->data_entrada) : '',
                'operacao' => $query->descricao_operacao,
                'transportadora' => $query->transportadora_nome.' - '.$query->transportadora_documento,
                'valor' => ($query->valor_total > 0) ? parserValor($query->valor_total) : '',
                'score' => $query->score,
                'id_score' => encrypt($query->id)
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ['response' => $retorno]
        ]);
    }

    public function modalFormularioLancamento(Request $request){
        $array_lancar = $request->only(['lancar']);
        $array_lancar['lancar'] = str_replace('&','',$array_lancar['lancar']);
        $array_lancar = explode('lancar%5B%5D=',$array_lancar['lancar'] );
        $array_lancar = array_filter($array_lancar);
        $array_lancar = encrypt($array_lancar);

        $formulario_query = ScoreFornecedorFormulario::with(['grupoPergunta' => function($query){
            $query->where('grupo_pergunta','ENTREGA');
        },'tipoResposta'])
        ->whereHas('grupoPergunta',function($query){
            $query->where('grupo_pergunta','ENTREGA');
        })
        ->get();
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
        
        return view('programs.score_fornecedor_nota.modal.formulario_score')->with(['array_lancamento' => $array_lancar, 'formulario' => $formulario_Array, 'active' => true,'active_menu' => true]);
    }   

    public function modalFormularioEditar(Request $request){
        $id = $request->only(['id']);
        $documentos = [];
        $array_lancamento = encrypt([$id['id']]);
        try{
            $id_score = decrypt($id['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => [],
            ];
            return response()->json($return);
        }

        $respondido = ScoreFornecedoresNotasLancamento::where('notas_importadas_compra_id',$id_score)->with('documentos')->get();

        if(!empty($respondido)){
            foreach($respondido as $respondidos){
                if(!empty($respondidos->documentos)){
                    foreach($respondidos->documentos as $documento){
                        $documentos[] = [
                            'descricao' => $documento->descricao,
                            'documento' => Storage::url($documento->caminho),
                            'id_documento' => encrypt($documento->id),
                        ];
                    }
                }
            }
        }
        
        $formulario_query = ScoreFornecedorFormulario::with(['grupoPergunta' => function($query){
            $query->where('grupo_pergunta','ENTREGA');
        },'tipoResposta'])
        ->whereHas('grupoPergunta',function($query){
            $query->where('grupo_pergunta','ENTREGA');
        })
        ->get();
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

        return view('programs.score_fornecedor_nota.modal.formulario_editar')->with(['documentos' =>$documentos,'array_lancamento' => $array_lancamento,'respondido' => $respondido,'formulario' => $formulario_Array,'active' => true,'active_menu' => true]);
    }

    public function salvar(ScoreFornecedorNotaSalvarRequest $request){
        $campos = $request->only(['descricao_documento','documento','array_lancamento','pergunta','resposta','grupo_pergunta','tipo_resposta','iso','file','resposta_abvtex']);
        
        try{
            $array_lancamento = decrypt($campos['array_lancamento']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => [],
            ];
            return response()->json($return);
        }
        
        foreach($array_lancamento as $id){
            $remover = ['%3D%3D','%3D'];
            $id = str_replace($remover,'',$id);
            
            try{
                $id_crecrypt = decrypt($id);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => [],
                    'response' => [],
                ];
                return response()->json($return);
            }

            $verifica_score = NotasImportadasCompra::where("id",$id_crecrypt)->where('score',true)->first();
        
            if(!empty($verifica_score)){
                $score_respondido = ScoreFornecedoresNotasLancamento::where('notas_importadas_compra_id', $verifica_score->id)->with('documentos')->get();
                $id_documento = [];
                foreach($score_respondido as $respondido){
                    if(!empty($respondido->documentos)){
                        foreach($respondido->documentos as $documento_id){
                            $id_documento[] = $documento_id->id;
                        }
                    }
                }
                $score_respondido = ScoreFornecedoresNotasLancamento::where('notas_importadas_compra_id', $verifica_score->id);
                $score_respondido->deleted_by = Auth::id();
                $score_respondido->delete();

                foreach($campos['pergunta'] as $key => $pergunta){
                    $formulario = new ScoreFornecedoresNotasLancamento;
                    $formulario->pergunta = $pergunta;
                    $formulario->resposta = (isset($campos['resposta'][$key])) ? $campos['resposta'][$key] : null;
                    $formulario->grupo_pergunta = $campos['grupo_pergunta'][$key];
                    $formulario->notas_importadas_compra_id = $verifica_score->id;
                    $formulario->created_by = Auth::id();
                    $formulario->score_fornecedor_tipo_respostas_nota_id = $campos['tipo_resposta'][$key];
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

                if(!empty($id_documento[0])){
                    foreach($id_documento as $id_documentos){
                        $update_documento = ScoreFornecedoresNotasLancamentosDocumento::find($id_documentos);
                        $update_documento->score_fornecedores_notas_lancamentos_id = $formulario->id;
                        $update_documento->save();
                    }
                }

                $documentos = $request->file('documento');

                if($request->hasFile('documento'))
                {
                    foreach($documentos as $key => $documento){
                        $score_documento = new ScoreFornecedoresNotasLancamentosDocumento;
                        $score_documento->descricao = $campos['descricao_documento'][$key];
                        $score_documento->created_by = Auth::user()->id;
                        $score_documento->score_fornecedores_notas_lancamentos_id = $formulario->id;
                        $score_documento->save();
                        $file = $score_documento->id.'.' .$documento->getClientOriginalExtension();
                        $score_documento->caminho = $this->path_documento.$file;
                        $documento->storeAs($this->path_documento, $file);
                        $score_documento->save();
                    }
                }

            }else{

                $score = NotasImportadasCompra::find($id_crecrypt);
                $score->score = true;
                $score->save();

                foreach($campos['pergunta'] as $key => $pergunta){
                    
                    $formulario = new ScoreFornecedoresNotasLancamento;
                    $formulario->pergunta = $pergunta;
                    $formulario->resposta = (isset($campos['resposta'][$key])) ? $campos['resposta'][$key] : null;
                    $formulario->grupo_pergunta = $campos['grupo_pergunta'][$key];
                    $formulario->notas_importadas_compra_id = $score->id;
                    $formulario->created_by = Auth::id();
                    $formulario->score_fornecedor_tipo_respostas_nota_id = $campos['tipo_resposta'][$key];
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

                $documentos = $request->file('documento');

                if($request->hasFile('documento'))
                {
                    foreach($documentos as $key => $documento){
                        $score_documento = new ScoreFornecedoresNotasLancamentosDocumento;
                        $score_documento->descricao = $campos['descricao_documento'][$key];
                        $score_documento->created_by = Auth::user()->id;
                        $score_documento->score_fornecedores_notas_lancamentos_id = $formulario->id;
                        $score_documento->save();
                        $file = $score_documento->id.'.' .$documento->getClientOriginalExtension();
                        $score_documento->caminho = $this->path_documento.$file;
                        $documento->storeAs($this->path_documento, $file);
                        $score_documento->save();
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

    public function excluirDocumento(Request $request){
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
            return response()->json($return);
        }
        
        try{
            $documento = ScoreFornecedoresNotasLancamentosDocumento::findOrFail($fields);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'erro',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ]);
        }

        Storage::delete($documento->caminho);
        $documento->deleted_by = Auth::user()->id;
        $documento->save();
        $documento->delete();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => []
        ]);
    }

    public function scoreRespondido(Request $request){
        $campos = $request->only(['id_nota']);

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

        $respondido = ScoreFornecedoresNotasLancamento::where('notas_importadas_compra_id',$id)->get();
        
        $formulario_query = ScoreFornecedorFormulario::with(['grupoPergunta' => function($query){
            $query->where('grupo_pergunta','ENTREGA');
        },'tipoResposta'])
        ->whereHas('grupoPergunta',function($query){
            $query->where('grupo_pergunta','ENTREGA');
        })
        ->get();
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
        
        return view('programs.score_fornecedores_consulta.modal.score_respondido')->with(['respondido' => $respondido,'formulario' => $formulario_Array,'active' => true,'active_menu' => true]);
    }
}
