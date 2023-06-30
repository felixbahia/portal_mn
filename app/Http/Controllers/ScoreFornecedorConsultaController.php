<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\ScoreFornecedorConsultaRequest;

use App\ScoreFornecedore;
use App\FornecedorNasajon;
use App\ScoreFornecedoresFormularioRespondido;
use App\ScoreFornecedorFormulario;
use App\ScoreFornecedoresNotasLancamento;
use App\NotasImportadasCompra;

class ScoreFornecedorConsultaController extends Controller
{
    private $tipo_simples = [
        '0' => 'Indefinido',
        '1' => 'Optante',
        '2' => 'Não Optante'
    ];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ScoreFornecedoresConsulta") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ScoreFornecedoresConsulta');

        return view('programs.score_fornecedores_consulta.index');
    }

    public function filtro(ScoreFornecedorConsultaRequest $request){
        $campos = $request->only(["fornecedor","data_inicio","data_fim"]);
        
        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_inicio'])->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_fim'])->format('Y-m-d 23:59:59');

        $score = ScoreFornecedore::whereBetween('created_at',[$data_inicio,$data_fim])->with('scoreFornecedores');
        $score_nota = ScoreFornecedoresNotasLancamento::whereBetween('created_at',[$data_inicio,$data_fim])
        ->whereNotNull('resposta')
        ->with('notasCompras');
        $id_fornecedor = null;

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
            $id_fornecedor = $fornecedor->id;
            $score_nota->whereHas('notasCompras',function($query) use ($id_fornecedor){
                $query->where('fornecedor_id',$id_fornecedor);
            });
            $score->where('fornecedor_nasajon_id',$fornecedor->id);
        }

        $retorno = [];
        $score_nota = $score_nota->get();
        $score = $score->get();

        $score_nota->each(function($query) use (&$retorno,$id_fornecedor,$data_inicio,$data_fim){
            if(!isset($retorno[$query->grupo_pergunta])){
                $retorno[$query->grupo_pergunta] = [
                    'grupo' => $query->grupo_pergunta,
                    'pergunta' => [],
                    'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                ];
            }

            if($query->score_fornecedor_tipo_respostas_nota_id == 1){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta] = [
                        'resposta' => $query->resposta,
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => $query->resposta,
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta]['quantidade_resposta'] ++;
                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta]['soma_respostas'] += $query->resposta;

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 2){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta] = [
                        'resposta' => $query->resposta,
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => $query->resposta,
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta]['quantidade'] ++;

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 3){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta] = [
                        'resposta' => $query->resposta,
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => $query->resposta,
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta]['quantidade'] ++;

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 11){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                $resposta_iso = null;

                if($query->resposta != 'Não' && !empty($query->resposta)){
                    $resposta_iso = 'Sim';
                }else if($query->resposta == 'Não'){
                    $resposta_iso = 'Não';
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)] = [
                        'resposta' => ($resposta_iso),
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => ($resposta_iso),
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                if(!empty($query->resposta)){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)]['quantidade'] ++;
                }

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 10){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                $resposta_iso = null;

                if($query->resposta != 'Não' && !empty($query->resposta)){
                    $resposta_iso = 'Sim';
                }else if($query->resposta == 'Não'){
                    $resposta_iso = 'Não';
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)] = [
                        'resposta' => ($resposta_iso),
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => ($resposta_iso),
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)]['quantidade'] ++;

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 12){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                $resposta_iso = null;

                if($query->resposta != 'Não' && !empty($query->resposta)){
                    $resposta_iso = 'Sim';
                }else if($query->resposta == 'Não'){
                    $resposta_iso = 'Não';
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)] = [
                        'resposta' => ($resposta_iso),
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => ($resposta_iso),
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][($resposta_iso)]['quantidade'] ++;

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 9){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta] = [
                        'resposta' => $query->resposta,
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => $query->resposta,
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta]['quantidade'] ++;

            }else if($query->score_fornecedor_tipo_respostas_nota_id == 7){

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta] = [
                        'pergunta' => $query->pergunta,
                        'resposta' => [],
                    ];
                }

                if(!isset($retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta])){
                    $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta] = [
                        'resposta' => $query->resposta,
                        'quantidade_resposta' => 0,
                        'quantidade' => 0,
                        'soma_respostas' => 0,
                        'media_respostas' => 0,
                        'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                        'filtro' => encrypt([
                            'pergunta' => $query->pergunta,
                            'id_pergunta' => $query->score_fornecedor_formulario_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'resposta' => $query->resposta,
                            'tipo_resposta' => $query->score_fornecedor_tipo_respostas_nota_id,
                            'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                        ]),
                    ];
                }

                $retorno[$query->grupo_pergunta]['pergunta'][$query->pergunta]['resposta'][$query->resposta]['quantidade'] ++;

            }

        });
        
        $score->each(function ($query) use (&$retorno,$id_fornecedor,$data_inicio,$data_fim){
            
            foreach($query->scoreFornecedores as $respostas){
                if(empty($respostas->resposta)){
                    continue;
                }
                if(!isset($retorno[$respostas->grupo_pergunta])){
                    $retorno[$respostas->grupo_pergunta] = [
                        'grupo' => $respostas->grupo_pergunta,
                        'pergunta' => [],
                        'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                    ];
                }

                if($respostas->score_fornecedor_formulario_tipo_respostas_id == 1){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta] = [
                            'resposta' => $respostas->resposta,
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => $respostas->resposta,
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta]['quantidade_resposta'] ++;
                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta]['soma_respostas'] += $respostas->resposta;

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 2){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta] = [
                            'resposta' => $respostas->resposta,
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => $respostas->resposta,
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta]['quantidade'] ++;

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 3){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta] = [
                            'resposta' => $respostas->resposta,
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => $respostas->resposta,
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta]['quantidade'] ++;

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 11){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    $resposta_iso = null;

                    if($respostas->resposta != 'Não' && !empty($respostas->resposta)){
                        $resposta_iso = 'Sim';
                    }else if($respostas->resposta == 'Não'){
                        $resposta_iso = 'Não';
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)] = [
                            'resposta' => ($resposta_iso),
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => ($resposta_iso),
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    if(!empty($respostas->resposta)){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)]['quantidade'] ++;
                    }

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 10){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    $resposta_iso = null;

                    if($respostas->resposta != 'Não' && !empty($respostas->resposta)){
                        $resposta_iso = 'Sim';
                    }else if($respostas->resposta == 'Não'){
                        $resposta_iso = 'Não';
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)] = [
                            'resposta' => ($resposta_iso),
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => ($resposta_iso),
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)]['quantidade'] ++;

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 12){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    $resposta_iso = null;

                    if($respostas->resposta != 'Não' && !empty($respostas->resposta)){
                        $resposta_iso = 'Sim';
                    }else if($respostas->resposta == 'Não'){
                        $resposta_iso = 'Não';
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)] = [
                            'resposta' => ($resposta_iso),
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => ($resposta_iso),
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][($resposta_iso)]['quantidade'] ++;

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 9){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta] = [
                            'resposta' => $respostas->resposta,
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => $respostas->resposta,
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta]['quantidade'] ++;

                }else if($respostas->score_fornecedor_formulario_tipo_respostas_id == 7){

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta] = [
                            'pergunta' => $respostas->pergunta,
                            'resposta' => [],
                        ];
                    }

                    if(!isset($retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta])){
                        $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta] = [
                            'resposta' => $respostas->resposta,
                            'quantidade_resposta' => 0,
                            'quantidade' => 0,
                            'soma_respostas' => 0,
                            'media_respostas' => 0,
                            'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                            'filtro' => encrypt([
                                'pergunta' => $respostas->pergunta,
                                'id_pergunta' => $respostas->score_fornecedor_formulario_id,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                                'resposta' => $respostas->resposta,
                                'tipo_resposta' => $respostas->score_fornecedor_formulario_tipo_respostas_id,
                                'id' => (!empty($id_fornecedor)) ? $id_fornecedor : null,
                            ]),
                        ];
                    }

                    $retorno[$respostas->grupo_pergunta]['pergunta'][$respostas->pergunta]['resposta'][$respostas->resposta]['quantidade'] ++;

                }

            }

        });

        foreach($retorno as $grupo => $perguntas){
            if($perguntas['tipo_resposta'] == 1){
                foreach($perguntas['pergunta'] as $key => $pergunta){
                    foreach($pergunta['resposta'] as $key_Resposta => $resposta){
                        if($retorno[$grupo]['pergunta'][$key]['resposta'][$key_Resposta]['soma_respostas'] > 0){
                            $retorno[$grupo]['pergunta'][$key]['resposta'][$key_Resposta]['media_respostas'] = $retorno[$grupo]['pergunta'][$key]['resposta'][$key_Resposta]['soma_respostas'] / $retorno[$grupo]['pergunta'][$key]['resposta'][$key_Resposta]['quantidade_resposta'];
                        }
                    }
                }
            }
        }

        $total = $score->count();
        $total += (!empty($score_nota->unique('notas_importadas_compra_id'))) ? $score_nota->unique('notas_importadas_compra_id')->count() : 0;

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno,
                'total' => $total
            ]
        ]);
    }

    public function modalListaFornecedores(Request $request){
        $campos = $request->only(['filtro','total']);
        
        try{
            $filtro = decrypt($campos['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => [],
            ];
            return response()->json($return);
        }

        $data_inicio = Carbon::createFromFormat('Y-m-d', substr($filtro['data_inicio'],0,10))->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('Y-m-d', substr($filtro['data_fim'],0,10))->format('Y-m-d 23:59:59');
        
        $score_nota = NotasImportadasCompra::whereBetween('created_at',[$data_inicio,$data_fim])
        ->where('score',true)
        ->whereHas('scoreRespondido' , function($query) use ($filtro,$campos){
            if($campos['total'] == "false"){
                if($filtro['tipo_resposta'] == 10 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 11 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 12 && $filtro['resposta'] != 'Não'){
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where('resposta', '<>', 'Não');
                }else{
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where(('resposta'), 'ilike', $filtro['resposta']);
                }
            }else if($campos['total'] == "true"){
                $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta']);
            }
        })
        ->with(['scoreRespondido' => function($query) use ($filtro,$campos){
            if($campos['total'] == "false"){
                if($filtro['tipo_resposta'] == 10 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 11 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 12 && $filtro['resposta'] != 'Não'){
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where('resposta', '<>', 'Não');
                }else{
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where(('resposta'), 'ilike', $filtro['resposta']);
                }
                
            }else if($campos['total'] == "true"){
                $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta']);
            }
        },'fornecedor']);

        $score = ScoreFornecedore::whereBetween('created_at',[$data_inicio,$data_fim])->whereHas('scoreFornecedores' , function($query) use ($filtro,$campos){
            if($campos['total'] == "false"){
                if($filtro['tipo_resposta'] == 10 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 11 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 12 && $filtro['resposta'] != 'Não'){
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where('resposta', '<>', 'Não');
                }else{
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where(('resposta'), 'ilike', $filtro['resposta']);
                }
            }else if($campos['total'] == "true"){
                $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta']);
            }
        })
        ->with(['fornecedor','scoreFornecedores' => function($query) use ($filtro,$campos){
            if($campos['total'] == "false"){
                if($filtro['tipo_resposta'] == 10 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 11 && $filtro['resposta'] != 'Não' || $filtro['tipo_resposta'] == 12 && $filtro['resposta'] != 'Não'){
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where('resposta', '<>', 'Não');
                }else{
                    $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta'])
                    ->where(('resposta'), 'ilike', $filtro['resposta']);
                }
                
            }else if($campos['total'] == "true"){
                $query->where('score_fornecedor_formulario_id', $filtro['id_pergunta']);
            }
        }]);

        if(!empty($filtro['id'])){
            $score_nota->where('fornecedor_id',$filtro['id']);
            $score->where('fornecedor_nasajon_id',$filtro['id']);
        }

        $score_nota = $score_nota->get();
        $score = $score->get();
        $retorno = [];

        $score_nota->each(function($query) use (&$retorno){
            $retorno[] = [
                'fornecedor' => $query->fornecedor->nome.' - '.$query->fornecedor->cnpj_cpf,
                'estado' =>  $query->fornecedor->uf,
                'data' => parserDataEHora($query->created_at),
                'id_nota' => $query->nota_id,
                'regime_trinutário' => (!empty($query->tiposimples)) ? $this->tipo_simples[$query->tiposimples] : '',
                'nota' => $query->documento_numero,
                'id_formulario' => null,
                'id_formulario_nota' => encrypt($query->scoreRespondido[0]->notas_importadas_compra_id)
            ];
        });

        $score->each(function($query) use (&$retorno){
            $retorno[] = [
                'fornecedor' => $query->fornecedor->nome.' - '.$query->fornecedor->cnpj_cpf,
                'estado' =>  $query->fornecedor->uf,
                'id_nota' => null,
                'nota' => null,
                'data' => parserDataEHora($query->created_at),
                'regime_trinutário' => (!empty($query->tiposimples)) ? $this->tipo_simples[$query->tiposimples] : '',
                'id_formulario' => encrypt($query->id),
                'id_formulario_nota' => null
            ];
        });

        return view('programs.score_fornecedores_consulta.modal.listagem_fornecedores')->with(['retorno' => $retorno]);
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

        $respondido = ScoreFornecedoresFormularioRespondido::where('score_fornecedores_id',$id)->get();

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
        
        return view('programs.score_fornecedores_consulta.modal.score_respondido')->with(['respondido' => $respondido,'formulario' => $formulario_Array,'active' => true,'active_menu' => true]);
    }
}
