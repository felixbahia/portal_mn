<?php

namespace App\Http\Controllers;

use App\Http\Requests\LiberacaoPilotagemAdicionarRequest;
use App\Http\Requests\LiberacaoPilotagemEditarRequest;
use App\Http\Requests\LiberacaoPilotagemFiltroAdicionarRequest;
use App\Http\Requests\LiberacaoPilotagemRecusarRequest;
use App\LancamentoDebCredVendedor;
use App\LiberacaoPilotagem;
use App\LiberacaoPilotagemHistorico;
use App\MotivoFinanceiro;
use App\NotasNasajon;
use App\TituloPagamentoNasajon;
use App\TitulosEmAbertoNasajon;
use App\User;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class LiberacaoPilotagemController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\LiberacaoPilotagem") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\LiberacaoPilotagem');
        $variaveis_view = $this->vendedorRepresente();

        return view('programs.liberacao_pilotagem.index', $variaveis_view);
    }

    public function indexAprovacao(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AprovacaoLiberacaoPilotagem") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AprovacaoLiberacaoPilotagem');
        $variaveis_view = $this->vendedorRepresente();

        return view('programs.aprovacao_liberacao_pilotagem.index', $variaveis_view);
    }

    public function filtroLiberacaoPilotagem(Request $request){
        $campo = $request->only('vendedor_representante');

        $LiberacaoPilotagemObj = LiberacaoPilotagem::with('representante', 'statusLiberacaoPilotagem');

        if(Auth::user()->tipo_usuario_id != 1){
            $LiberacaoPilotagemObj->where('created_by', Auth::id());
        }
        
        if(!empty($campo['vendedor_representante'])){
            $LiberacaoPilotagemObj->where('users_id', $campo['vendedor_representante']);
        }

        $LiberacaoPilotagem = $LiberacaoPilotagemObj->get();

        $saida = [];

        foreach($LiberacaoPilotagem as $nota){
            $saida[] = [
                'id' => encrypt($nota->id),
                'representante' => $nota->representante->codigo_representante.' - '.$nota->representante->name,
                'nota' => $nota->nota_numero,
                'nota_id' => $nota->nota_id,
                'valor_credito' => !empty($nota->valor_credito) ? '<span class="text-primary">'.parserValor($nota->valor_credito).'</span>' : '',
                'valor_desconto' => !empty($nota->valor_desconto) ? '<span class="text-danger">-'.parserValor($nota->valor_desconto).'</span>' : '',
                'comissao_atual' => !empty($nota->comissao_atual) ? parserQtd($nota->comissao_atual) : '',
                'comissao_alterada' => !empty($nota->comissao_alterada) ? parserQtd($nota->comissao_alterada) : '',
                'status' => $nota->statusLiberacaoPilotagem->status,
                'status_id' => $nota->status_liberacao_pilotagems_id,
                'status_data_hora' => parserDataEHora($nota->updated_at),
                'data_lancamento' => parserData($nota->created_at)
            ];
        } 

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'saida' => $saida
            ]
        ];
        return response()->json($response, 200);
    }

    public function filtroAprovarPilotagem(Request $request){
        $campo = $request->only('vendedor_representante');

        $LiberacaoPilotagemObj = LiberacaoPilotagem::with('representante', 'statusLiberacaoPilotagem', 'representante.vendedor_nasajon')
        ->where('status_liberacao_pilotagems_id', 1);
        
        if(!empty($campo['vendedor_representante'])){
            $LiberacaoPilotagemObj->where('users_id', $campo['vendedor_representante']);
        }

        $LiberacaoPilotagem = $LiberacaoPilotagemObj->get();

        $saida = [];

        foreach($LiberacaoPilotagem as $nota){
            $saida[] = [
                'id' => encrypt($nota->id),
                'representante' => $nota->representante->codigo_representante.' - '.$nota->representante->name,
                'nota' => $nota->nota_numero,
                'nota_id' => $nota->nota_id,
                'valor_credito' => !empty($nota->valor_credito) ? '<span class="text-primary">'.parserValor($nota->valor_credito).'</span>' : '',
                'valor_desconto' => !empty($nota->valor_desconto) ? '<span class="text-danger">-'.parserValor($nota->valor_desconto).'</span>' : '',
                'comissao_atual' => !empty($nota->comissao_atual) ? parserQtd($nota->comissao_atual) : '',
                'comissao_alterada' => !empty($nota->comissao_alterada) ? parserQtd($nota->comissao_alterada) : ''
            ];
        } 

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'saida' => $saida
            ]
        ];
        return response()->json($response, 200);

    }

    public function modalAdicionar(Request $request){
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $motivos = $this->getMotivos();
        $variaveis_view = $this->vendedorRepresente();
        $abonar_pilotagem_alterar_comissao = $this->selectAbonarPilotagemAlterarComissao();

        return view('programs.liberacao_pilotagem.modal.adicionar', $variaveis_view)->with([
            'estabelecimentos' => $estabelecimentos,
            'motivos' => $motivos,
            'abonar_pilotagem_alterar_comissao' => $abonar_pilotagem_alterar_comissao]);
    }

    public function modalEditar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }
        $LiberacaoPilotagem = LiberacaoPilotagem::find($id);
        if(empty($LiberacaoPilotagem)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pilotagem não econtrada',
                'error' => '',
                'response' => ''
            ]);
        }

        if($LiberacaoPilotagem->tipo_ajuste == 'abonar_pilotagem'){
            $saida = [
                'id' => encrypt($LiberacaoPilotagem->id),
                'estabelecimento' => (integer)$LiberacaoPilotagem->estabelecimento,
                'vendedor_representante' => $LiberacaoPilotagem->users_id,
                'abonar_pilotagem_alterar_comissao' => 'Abonar Pilotagem',
                'motivo' => $LiberacaoPilotagem->motivos_financeiros_id,
                'motivo_recusa' => $LiberacaoPilotagem->motivo_recusa,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'titulo' => '',
                'parcela' => ''
            ];

            $variaveis_view = $this->vendedorRepresente();
            $estabelecimentos = returnEmpresasNasajonView();
            $motivos = $this->getMotivos();

            return view('programs.liberacao_pilotagem.modal.editar', $variaveis_view)->with([
                'dados' => $saida,
                'estabelecimentos' => $estabelecimentos,
                'motivos' => $motivos
            ]);

        }else{
            if(empty($LiberacaoPilotagem)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comissao não econtrada',
                    'error' => '',
                    'response' => ''
                ]);
            }

            $saida = [
                'id' => encrypt($LiberacaoPilotagem->id),
                'estabelecimento' => (integer)$LiberacaoPilotagem->estabelecimento,
                'vendedor_representante' => $LiberacaoPilotagem->users_id,
                'abonar_pilotagem_alterar_comissao' => 'Alterar Comissão',
                'motivo' => $LiberacaoPilotagem->motivos_financeiros_id,
                'motivo_recusa' => $LiberacaoPilotagem->motivo_recusa,
                'titulo' => $LiberacaoPilotagem->titulo_numero,
                'parcela' => $LiberacaoPilotagem->parcela,
                'nota' => '',
            ];

            $variaveis_view = $this->vendedorRepresente();
            $estabelecimentos = returnEmpresasNasajonView();
            $motivos = $this->getMotivos();

            return view('programs.liberacao_pilotagem.modal.editar', $variaveis_view)->with([
                'dados' => $saida,
                'estabelecimentos' => $estabelecimentos,
                'motivos' => $motivos
            ]);
        }
    }

    public function modalVisualizar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $LiberacaoPilotagem = LiberacaoPilotagem::with('representante', 'motivoFinanceiro')
        ->find($id);
        if(empty($LiberacaoPilotagem)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pilotagem não econtrada',
                'error' => '',
                'response' => ''
            ]);
        }
        $estabelecimentos = returnEmpresasNasajonView();

        if($LiberacaoPilotagem->tipo_ajuste == 'abonar_pilotagem'){
            $saida = [
                'estabelecimento' => $estabelecimentos[(integer)$LiberacaoPilotagem->estabelecimento],
                'representante' =>  $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'vendedor_representante' => $LiberacaoPilotagem->users_id,
                'abonar_pilotagem_alterar_comissao' => $LiberacaoPilotagem->tipo_ajuste,
                'motivo' => $LiberacaoPilotagem->motivoFinanceiro->motivo,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'nota_id' => $LiberacaoPilotagem->nota_id,
                'valor_credito' => parserValor($LiberacaoPilotagem->valor_credito),
                'valor_desconto' => parserValor($LiberacaoPilotagem->valor_desconto),
                'data_lancamento' => parserData($LiberacaoPilotagem->created_at),
                'pedido_venda' => '',
                'pedido_venda_id' => '',
                'data_emissao' => '',
                'data_vencimento' => '',
                'titulo' => '',
                'parcela' => '',
                'valor_titulo' => '',
                'comissao_atual' => '',
                'comissao_alterada' => ''
            ];

            $variaveis_view = $this->vendedorRepresente();
            $estabelecimentos = returnEmpresasNasajonView();
            $abonar_pilotagem_alterar_comissao = $this->selectAbonarPilotagemAlterarComissao();
            $motivos = $this->getMotivos();
           
            return view('programs.liberacao_pilotagem.modal.visualizar', $variaveis_view)->with([
                'dados' => $saida,
                'estabelecimentos' => $estabelecimentos,
                'abonar_pilotagem_alterar_comissao' => $abonar_pilotagem_alterar_comissao,
                'motivos' => $motivos
            ]);
        
        }else{
            if(empty($LiberacaoPilotagem)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comissao não econtrada',
                    'error' => '',
                    'response' => ''
                ]);
            }

            $saida = [
                'estabelecimento' => $estabelecimentos[(integer)$LiberacaoPilotagem->estabelecimento],
                'representante' =>  $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'vendedor_representante' => $LiberacaoPilotagem->users_id,
                'abonar_pilotagem_alterar_comissao' => 'Alteração de comissão',
                'motivo' => $LiberacaoPilotagem->motivoFinanceiro->motivo,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'nota_id' => $LiberacaoPilotagem->nota_id,
                'motivo_recusa' => $LiberacaoPilotagem->motivo_recusa,
                'pedido_venda' => $LiberacaoPilotagem->pedido_numero,
                'pedido_venda_id' => $LiberacaoPilotagem->pedido_nasajon_id,
                'data_emissao' => parserData($LiberacaoPilotagem->titulo_data_emissao),
                'data_vencimento' => parserData($LiberacaoPilotagem->titulo_data_vencimento),
                'valor_titulo' => parserValor($LiberacaoPilotagem->titulo_valor),
                'comissao_atual' => parserQtd($LiberacaoPilotagem->comissao_atual),
                'comissao_alterada' => parserQtd($LiberacaoPilotagem->comissao_alterada),
                'titulo' => $LiberacaoPilotagem->titulo_numero,
                'parcela' => $LiberacaoPilotagem->parcela,
                'data_lancamento' => parserData($LiberacaoPilotagem->created_at),
                'valor_credito' => '',
                'valor_desconto' => '',
            ];

            return view('programs.liberacao_pilotagem.modal.visualizar')->with(['dados' => $saida]);
        }
    }

    public function filtroPilotagemAdicionar(LiberacaoPilotagemFiltroAdicionarRequest $request){
        $campo = $request->only('estabelecimento','vendedor_representante', 'abonar_pilotagem_alterar_comissao', 'nota', 'titulo', 'parcela');

        $saida = [];

        $estabelecimentos = returnEmpresasNasajonView();

        if($campo['abonar_pilotagem_alterar_comissao'] == 'abonar_pilotagem'){
            $NotasNasajon = NotasNasajon::with(['lancamentoDebCredVendedor.vendedor',
            'lancamentoDebCredVendedor' => function($query) use ($campo){
                $query->where('codigo_vendedor', $campo['vendedor_representante'])
                ->where('tipo', 'D')
                ->where('codigo_motivo', 1)
                ->whereNotNull('nota_uuid');
            }])
            ->where('numero', $campo['nota'])
            ->where('estabelecimento_codigo', str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT))
            ->first();
            
            if(isset($NotasNasajon->lancamentoDebCredVendedor)){
                $vendedor_codigo = $NotasNasajon->lancamentoDebCredVendedor->map(function($vendedor){
                    return $vendedor->vendedor->codigo_representante;
                })->unique()->implode(' ');

                $vendedor_nome = $NotasNasajon->lancamentoDebCredVendedor->map(function($vendedor){
                    return $vendedor->vendedor->name;
                })->unique()->implode(' ');

                if(!empty($vendedor_codigo)){
                    $saida[] = [
                        'estabelecimento' => $estabelecimentos[(integer)$NotasNasajon->estabelecimento_codigo],
                        'representante' => $vendedor_codigo.' - '.$vendedor_nome,
                        'nota' => $NotasNasajon->numero,
                        'nota_id' => $NotasNasajon->lancamentoDebCredVendedor->pluck('nota_uuid')->unique()->implode(' '),
                        'valor_desconto' => parserValor($NotasNasajon->lancamentoDebCredVendedor->pluck('valor')->unique()->implode(' ')),
                        'valor_credito' => '',
                        'filters' => encrypt([
                            'nota_id' => $NotasNasajon->lancamentoDebCredVendedor->pluck('nota_uuid')->unique()->implode(' '),
                            'nota' => $NotasNasajon->numero,
                            'valor_desconto' => $NotasNasajon->lancamentoDebCredVendedor->pluck('valor')->unique()->implode(' ')
                        ])
                    ];
                }
            }
        }else{
            $TituloPagamentoNasajon = TituloPagamentoNasajon::with(['comissaoVendedor','nota.pedido',
            'comissaoVendedor.usuario' => function($query) use ($campo){
                $query->where('id', $campo['vendedor_representante']);
            }])
            ->where('codigo', str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT))
            ->where('numero', $campo['titulo'])
            ->where('parcela', $campo['parcela'])
            ->first();

            if(isset($TituloPagamentoNasajon->comissaoVendedor->usuario)){
                $saida[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$TituloPagamentoNasajon->codigo],
                    'representante' => $TituloPagamentoNasajon->comissaoVendedor->usuario->codigo_representante.' - '.$TituloPagamentoNasajon->comissaoVendedor->usuario->name,
                    'nota' => $TituloPagamentoNasajon->documento_numero,
                    'nota_id' => $TituloPagamentoNasajon->documento_id,
                    'pedido_venda' => isset($TituloPagamentoNasajon->nota->pedido->numero) ? $TituloPagamentoNasajon->nota->pedido->numero : '',
                    'pedido_venda_id' => isset($TituloPagamentoNasajon->nota->pedido->id) ? $TituloPagamentoNasajon->nota->pedido->id : '',
                    'data_emissao' => parserData($TituloPagamentoNasajon->emissao),
                    'data_vencimento' => parserData($TituloPagamentoNasajon->vencimento),
                    'valor_titulo' => parserValor($TituloPagamentoNasajon->valor_titulo),
                    'comissao_atual' => parserQtd($TituloPagamentoNasajon->comissaoVendedor->percentual_comissao),
                    'comissao_alterada' => '',
                    'filters' => encrypt([
                        'data_emissao' => $TituloPagamentoNasajon->emissao,
                        'data_vencimento' => $TituloPagamentoNasajon->vencimento,
                        'valor_titulo' => $TituloPagamentoNasajon->valor_titulo,
                        'titulo' => $TituloPagamentoNasajon->numero,
                        'parcela' => $TituloPagamentoNasajon->parcela,
                        'titulo_id' => $TituloPagamentoNasajon->id_titulo,
                        'nota' => $TituloPagamentoNasajon->documento_numero,
                        'nota_id' => $TituloPagamentoNasajon->documento_id,
                        'pedido_venda' => isset($TituloPagamentoNasajon->nota->pedido->numero) ? $TituloPagamentoNasajon->nota->pedido->numero : '',
                        'pedido_venda_id' => isset($TituloPagamentoNasajon->nota->pedido->id) ? $TituloPagamentoNasajon->nota->pedido->id : '',
                        'comissao_atual' => $TituloPagamentoNasajon->comissaoVendedor->percentual_comissao
                    ])
                ];
            }

            $TitulosEmAbertoNasajon = TitulosEmAbertoNasajon::with(['revisao_vendedor_comissao','notaDetalhes.pedido',
            'revisao_vendedor_comissao.usuario' => function($query) use ($campo){
                $query->where('id', $campo['vendedor_representante']);
            }])
            ->where('codigo', str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT))
            ->where('numero', $campo['titulo'])
            ->where('parcela', $campo['parcela'])
            ->first();

            
            if(isset($TitulosEmAbertoNasajon->revisao_vendedor_comissao->usuario)){
                $saida[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$TitulosEmAbertoNasajon->codigo],
                    'representante' => $TitulosEmAbertoNasajon->revisao_vendedor_comissao->usuario->codigo_representante.' - '.$TitulosEmAbertoNasajon->revisao_vendedor_comissao->usuario->name,
                    'nota' => $TitulosEmAbertoNasajon->nota_numero,
                    'nota_id' => $TitulosEmAbertoNasajon->nota_id,
                    'pedido_venda' => isset($TitulosEmAbertoNasajon->notaDetalhes->pedido->numero) ? $TitulosEmAbertoNasajon->notaDetalhes->pedido->numero : '',
                    'pedido_venda_id' => isset($TitulosEmAbertoNasajon->notaDetalhes->pedido->id) ? $TitulosEmAbertoNasajon->notaDetalhes->pedido->id : '',
                    'data_emissao' => parserData($TitulosEmAbertoNasajon->titulo_emissao),
                    'data_vencimento' => parserData($TitulosEmAbertoNasajon->vencimento),
                    'valor_titulo' => parserValor($TitulosEmAbertoNasajon->saldotitulo),
                    'comissao_atual' => parserQtd($TitulosEmAbertoNasajon->revisao_vendedor_comissao->percentual_comissao),
                    'comissao_alterada' => '',
                    'filters' => encrypt([
                        'data_emissao' => $TitulosEmAbertoNasajon->titulo_emissao,
                        'data_vencimento' => $TitulosEmAbertoNasajon->vencimento,
                        'valor_titulo' => $TitulosEmAbertoNasajon->saldotitulo,
                        'titulo' => $TitulosEmAbertoNasajon->numero,
                        'parcela' => $TitulosEmAbertoNasajon->parcela,
                        'titulo_id' => $TitulosEmAbertoNasajon->titulo_id,
                        'nota' => $TitulosEmAbertoNasajon->nota_numero,
                        'nota_id' => $TitulosEmAbertoNasajon->nota_id,
                        'pedido_venda' => isset($TitulosEmAbertoNasajon->notaDetalhes->pedido->numero) ? $TitulosEmAbertoNasajon->notaDetalhes->pedido->numero : '',
                        'pedido_venda_id' => isset($TitulosEmAbertoNasajon->notaDetalhes->pedido->id) ? $TitulosEmAbertoNasajon->notaDetalhes->pedido->id : '',
                        'comissao_atual' => $TitulosEmAbertoNasajon->revisao_vendedor_comissao->percentual_comissao
                    ])
                ];
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'saida' => $saida
            ]
        ];
        return response()->json($response, 200);
    }

    public function filtroPilotagemEditar(Request $request){
        $campo = $request->only('id', 'abonar_pilotagem_alterar_comissao');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }
        $LiberacaoPilotagem = LiberacaoPilotagem::with('representante')
        ->find($id);

        if($campo['abonar_pilotagem_alterar_comissao'] == 'Abonar Pilotagem'){
            
            if(empty($LiberacaoPilotagem)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pilotagem não econtrada',
                    'error' => '',
                    'response' => ''
                ]);
            }

            $estabelecimentos = returnEmpresasNasajonView();

            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$LiberacaoPilotagem->estabelecimento],
                'representante' => $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'nota_id' => $LiberacaoPilotagem->nota_id,
                'valor_credito' => $LiberacaoPilotagem->valor_credito,
                'valor_desconto' => parserValor($LiberacaoPilotagem->valor_desconto),
                'filters' => encrypt([
                    'nota' => $LiberacaoPilotagem->nota_numero,
                    'nota_id' => $LiberacaoPilotagem->nota_id,
                    'valor_desconto' => $LiberacaoPilotagem->valor_desconto,
                ])
            ];
        }else{
            if(empty($LiberacaoPilotagem)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comissao não econtrada',
                    'error' => '',
                    'response' => ''
                ]);
            }

            $estabelecimentos = returnEmpresasNasajonView();

            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$LiberacaoPilotagem->estabelecimento],
                'representante' => $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'nota_id' => $LiberacaoPilotagem->nota_id,
                'pedido_venda' => $LiberacaoPilotagem->pedido_numero,
                'pedido_venda_id' => $LiberacaoPilotagem->pedido_nasajon_id,
                'data_emissao' => parserData($LiberacaoPilotagem->titulo_data_emissao),
                'data_vencimento' => parserData($LiberacaoPilotagem->titulo_data_vencimento),
                'valor_titulo' => parserValor($LiberacaoPilotagem->titulo_valor),
                'comissao_atual' => parserQtd($LiberacaoPilotagem->comissao_atual),
                'comissao_alterada' => parserQtd($LiberacaoPilotagem->comissao_alterada),
                'filters' => encrypt([
                    'nota_id' => $LiberacaoPilotagem->nota_id,
                    'nota' => $LiberacaoPilotagem->nota_numero,
                    'titulo_id' => $LiberacaoPilotagem->titulo_id,
                    'titulo' => $LiberacaoPilotagem->titulo_numero,
                    'parcela' => $LiberacaoPilotagem->parcela,
                    'pedido_venda' => $LiberacaoPilotagem->pedido_numero,
                    'pedido_venda_id' => $LiberacaoPilotagem->pedido_nasajon_id,
                    'data_emissao' => $LiberacaoPilotagem->titulo_data_emissao,
                    'data_vencimento' => $LiberacaoPilotagem->titulo_data_vencimento,
                    'valor_titulo' => $LiberacaoPilotagem->titulo_valor,
                    'comissao_atual' => $LiberacaoPilotagem->comissao_atual,
                ])
            ];

        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'saida' => $saida
            ]
        ];
        return response()->json($response, 200);
    }


    public function adicionarLiberacaoPilotagem(LiberacaoPilotagemAdicionarRequest $request){
        $campo = $request->only('estabelecimento','vendedor_representante', 'abonar_pilotagem_alterar_comissao', 
        'valor_credito', 'motivo', 'nova_comissao','filters');

        $filtro = decrypt($campo['filters']);
        $LiberacaoPilotagemObj = new LiberacaoPilotagem;

        if($campo['abonar_pilotagem_alterar_comissao'] == 'abonar_pilotagem'){
            $LancamentosDebCredVendedor = LancamentoDebCredVendedor::where('codigo_vendedor', $campo['vendedor_representante'])
            ->where('nota_uuid', $filtro['nota_id'])->first();

            $LiberacaoPilotagemObj->lancamentos_deb_cred_vendedor_id = $LancamentosDebCredVendedor->id;
            $LiberacaoPilotagemObj->estabelecimento = str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $LiberacaoPilotagemObj->users_id = $campo['vendedor_representante'];
            $LiberacaoPilotagemObj->nota_numero = $filtro['nota'];
            $LiberacaoPilotagemObj->nota_id = $filtro['nota_id'];
            $LiberacaoPilotagemObj->valor_credito =  str_replace(',', '.', $campo['valor_credito']);
            $LiberacaoPilotagemObj->valor_desconto = $filtro['valor_desconto'];
            $LiberacaoPilotagemObj->motivos_financeiros_id = $campo['motivo'];
            $LiberacaoPilotagemObj->tipo_ajuste = $campo['abonar_pilotagem_alterar_comissao'];
            $LiberacaoPilotagemObj->status_liberacao_pilotagems_id = 1;
            $LiberacaoPilotagemObj->created_by = Auth::id();
            $LiberacaoPilotagemObj->save();
        }else{
            $LiberacaoPilotagemObj->estabelecimento = str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $LiberacaoPilotagemObj->users_id = $campo['vendedor_representante'];
            $LiberacaoPilotagemObj->nota_numero = $filtro['nota'];
            $LiberacaoPilotagemObj->nota_id = $filtro['nota_id'];
            $LiberacaoPilotagemObj->parcela = $filtro['parcela'];
            $LiberacaoPilotagemObj->comissao_atual = $filtro['comissao_atual'];
            $LiberacaoPilotagemObj->comissao_alterada = str_replace(',', '.', $campo['nova_comissao']);
            $LiberacaoPilotagemObj->motivos_financeiros_id = $campo['motivo'];
            $LiberacaoPilotagemObj->pedido_numero = $filtro['pedido_venda'];
            $LiberacaoPilotagemObj->pedido_nasajon_id = $filtro['pedido_venda_id'];
            $LiberacaoPilotagemObj->titulo_numero = $filtro['titulo'];
            $LiberacaoPilotagemObj->titulo_id = $filtro['titulo_id'];
            $LiberacaoPilotagemObj->titulo_data_emissao = $filtro['data_emissao'];
            $LiberacaoPilotagemObj->titulo_data_vencimento = $filtro['data_vencimento'];
            $LiberacaoPilotagemObj->titulo_valor = $filtro['valor_titulo'];
            $LiberacaoPilotagemObj->tipo_ajuste = $campo['abonar_pilotagem_alterar_comissao'];
            $LiberacaoPilotagemObj->status_liberacao_pilotagems_id = 1;
            $LiberacaoPilotagemObj->created_by = Auth::id();
            $LiberacaoPilotagemObj->save();
        }

        $LiberacaoPilotagemHistorico = new LiberacaoPilotagemHistorico;
        $LiberacaoPilotagemHistorico->liberacao_pilotagems_id = $LiberacaoPilotagemObj->id;
        $LiberacaoPilotagemHistorico->estado = 'Enviado';
        $LiberacaoPilotagemHistorico->users_id = Auth::id();
        $LiberacaoPilotagemHistorico->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function editarLiberacaoPilotagem(LiberacaoPilotagemEditarRequest $request){
        $campo = $request->only('id','estabelecimento','vendedor_representante', 'abonar_pilotagem_alterar_comissao', 
        'valor_credito', 'motivo','nova_comissao','filters');

        try{
            $id = decrypt($campo['id']);
            $filtro = decrypt($campo['filters']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $LiberacaoPilotagem = LiberacaoPilotagem::find($id);

        if($campo['abonar_pilotagem_alterar_comissao'] == 'Abonar Pilotagem'){
            $LancamentosDebCredVendedor = LancamentoDebCredVendedor::where('codigo_vendedor', $campo['vendedor_representante'])
            ->where('nota_uuid', $filtro['nota_id'])->first();

            $LiberacaoPilotagem->lancamentos_deb_cred_vendedor_id = $LancamentosDebCredVendedor->id;
            $LiberacaoPilotagem->estabelecimento = str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $LiberacaoPilotagem->users_id = $campo['vendedor_representante'];
            $LiberacaoPilotagem->nota_numero = $filtro['nota'];
            $LiberacaoPilotagem->nota_id = $filtro['nota_id'];
            $LiberacaoPilotagem->valor_desconto = $filtro['valor_desconto'];
            $LiberacaoPilotagem->valor_credito = str_replace(',', '.', $campo['valor_credito']);
            $LiberacaoPilotagem->motivos_financeiros_id = $campo['motivo'];
            $LiberacaoPilotagem->status_liberacao_pilotagems_id = 1;
            $LiberacaoPilotagem->updated_by = Auth::id();
            $LiberacaoPilotagem->save();
        }else{
            $LiberacaoPilotagem->estabelecimento = str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $LiberacaoPilotagem->users_id = $campo['vendedor_representante'];
            $LiberacaoPilotagem->nota_numero = $filtro['nota'];
            $LiberacaoPilotagem->nota_id = $filtro['nota_id'];
            $LiberacaoPilotagem->parcela = $filtro['parcela'];
            $LiberacaoPilotagem->comissao_atual =$filtro['comissao_atual'];
            $LiberacaoPilotagem->comissao_alterada = str_replace(',', '.', $campo['nova_comissao']);
            $LiberacaoPilotagem->pedido_numero = $filtro['pedido_venda'];
            $LiberacaoPilotagem->pedido_nasajon_id = $filtro['pedido_venda_id'];
            $LiberacaoPilotagem->titulo_numero = $filtro['titulo'];
            $LiberacaoPilotagem->titulo_id = $filtro['titulo_id'];
            $LiberacaoPilotagem->titulo_data_emissao = $filtro['data_emissao'];
            $LiberacaoPilotagem->titulo_data_vencimento = $filtro['data_vencimento'];
            $LiberacaoPilotagem->titulo_valor = $filtro['valor_titulo'];
            $LiberacaoPilotagem->motivos_financeiros_id = $campo['motivo'];
            $LiberacaoPilotagem->status_liberacao_pilotagems_id = 1;
            $LiberacaoPilotagem->updated_by = Auth::id();
            $LiberacaoPilotagem->save();
        }

        $LiberacaoPilotagemHistorico = new LiberacaoPilotagemHistorico;
        $LiberacaoPilotagemHistorico->liberacao_pilotagems_id = $id;
        $LiberacaoPilotagemHistorico->estado = 'Reenviado';
        $LiberacaoPilotagemHistorico->users_id = Auth::id();
        $LiberacaoPilotagemHistorico->save();

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
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $LiberacaoPilotagem = LiberacaoPilotagem::with('representante')
        ->find($id);

        if($LiberacaoPilotagem->tipo_ajuste == 'abonar_pilotagem'){
            $saida = [
                'id' => encrypt($LiberacaoPilotagem->id),
                'vendedor_representante' => $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'abonar_pilotagem_alterar_comissao' => $LiberacaoPilotagem->tipo_ajuste,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'valor_desconto' => parserValor($LiberacaoPilotagem->valor_desconto),
                'valor_credito' => parserValor($LiberacaoPilotagem->valor_credito),
                'comissao_atual' => '',
                'comissao_alterada' => '',
            ];

            return view('programs.liberacao_pilotagem.modal.deletar')->with(['dados' => $saida]);
        }else{
            $saida = [
                'id' => encrypt($LiberacaoPilotagem->id),
                'vendedor_representante' => $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'abonar_pilotagem_alterar_comissao' => 'alterar_comissao',
                'nota' => $LiberacaoPilotagem->nota_numero,
                'valor_desconto' => '',
                'valor_credito' => '',
                'nota' => $LiberacaoPilotagem->nota_numero,
                'comissao_atual' => parserQtd($LiberacaoPilotagem->comissao_atual),
                'comissao_alterada' => parserQtd($LiberacaoPilotagem->comissao_alterada)
            ];
            return view('programs.liberacao_pilotagem.modal.deletar')->with(['dados' => $saida]);
        }
    }

    public function modalReprovarPilotagem(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $LiberacaoPilotagem = LiberacaoPilotagem::with('representante')
        ->find($id);

        if($LiberacaoPilotagem->tipo_ajuste == 'abonar_pilotagem'){
            $saida = [
                'id' => encrypt($LiberacaoPilotagem->id),
                'vendedor_representante' => $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'abonar_pilotagem_alterar_comissao' => $LiberacaoPilotagem->tipo_ajuste,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'valor_desconto' => parserValor($LiberacaoPilotagem->valor_desconto),
                'valor_credito' => parserValor($LiberacaoPilotagem->valor_credito),
                'comissao_atual' => '',
                'comissao_alterada' => '',
            ];

            return view('programs.aprovacao_liberacao_pilotagem.modal.recusar')->with(['dados' => $saida]);
        }else{
            $saida = [
                'id' => encrypt($LiberacaoPilotagem->id),
                'vendedor_representante' => $LiberacaoPilotagem->representante->codigo_representante.' - '.$LiberacaoPilotagem->representante->name,
                'abonar_pilotagem_alterar_comissao' => $LiberacaoPilotagem->tipo_ajuste,
                'nota' => $LiberacaoPilotagem->nota_numero,
                'valor_desconto' => '',
                'valor_credito' => '',
                'comissao_atual' => parserQtd($LiberacaoPilotagem->comissao_atual),
                'comissao_alterada' => parserQtd($LiberacaoPilotagem->comissao_alterada),
            ];

        }

        return view('programs.aprovacao_liberacao_pilotagem.modal.recusar')->with(['dados' => $saida]);

    }

    public function deletarPilotagem(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $LiberacaoPilotagem = LiberacaoPilotagem::find($id);
        $LiberacaoPilotagem->deleted_by = Auth::id();
        $LiberacaoPilotagem->save();
        $LiberacaoPilotagem->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function aprovarPilotagem(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }
        
        $id_usuario = Auth::id();
        $dados = [];

        $LiberacaoPilotagem = LiberacaoPilotagem::find($id);

        if($LiberacaoPilotagem->tipo_ajuste == 'abonar_pilotagem'){
            $LancamentosDebCredVendedor = new LancamentoDebCredVendedor;
            $LancamentosDebCredVendedor->data_lancamento = Carbon::now()->format('Y-m-d');
            $LancamentosDebCredVendedor->num_documento = $LiberacaoPilotagem->nota_numero;
            $LancamentosDebCredVendedor->codigo_vendedor = $LiberacaoPilotagem->users_id;
            $LancamentosDebCredVendedor->codigo_motivo = $LiberacaoPilotagem->motivos_financeiros_id;
            $LancamentosDebCredVendedor->tipo = 'C';
            $LancamentosDebCredVendedor->valor = $LiberacaoPilotagem->valor_credito;
            $LancamentosDebCredVendedor->nota_uuid = $LiberacaoPilotagem->nota_id; 
            $LancamentosDebCredVendedor->created_by = $id_usuario;
            $LancamentosDebCredVendedor->save();

            $LiberacaoPilotagem->status_liberacao_pilotagems_id = 2;
            $LiberacaoPilotagem->aprovador_id = $id_usuario;
            $LiberacaoPilotagem->save();

            $LiberacaoPilotagemHistorico = new LiberacaoPilotagemHistorico;
            $LiberacaoPilotagemHistorico->liberacao_pilotagems_id = $LiberacaoPilotagem->id;
            $LiberacaoPilotagemHistorico->estado = 'Aprovado';
            $LiberacaoPilotagemHistorico->users_id = $id_usuario;
            $LiberacaoPilotagemHistorico->save();
        }else{
            $LiberacaoPilotagem->with('representante.vendedor_nasajon')
            ->find($id);

            $request_controller = new Request([
                'vendedor' => $LiberacaoPilotagem->representante->vendedor_nasajon->id,
                'comissao' => $LiberacaoPilotagem->comissao_alterada,
                'hash' => encrypt([
                    'vendedor' => $LiberacaoPilotagem->representante->vendedor_nasajon->id,
                    'id' => $LiberacaoPilotagem->nota_id
                ])
            ]);

            $RevisaoComissaoController = new RevisaoComissaoController();
            $retorno = $RevisaoComissaoController->modificarComissaoNotaNasajon($request_controller);
            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retorno;
            }

            $LiberacaoPilotagem->status_liberacao_pilotagems_id = 2;
            $LiberacaoPilotagem->aprovador_id = $id_usuario;
            $LiberacaoPilotagem->save();

            $LiberacaoPilotagemHistorico = new LiberacaoPilotagemHistorico;
            $LiberacaoPilotagemHistorico->liberacao_pilotagems_id = $LiberacaoPilotagem->id;
            $LiberacaoPilotagemHistorico->estado = 'Aprovado';
            $LiberacaoPilotagemHistorico->users_id = $id_usuario;
            $LiberacaoPilotagemHistorico->save();
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function reprovarPilotagem(LiberacaoPilotagemRecusarRequest $request){
        $campo = $request->only('id', 'motivo_recusa');
       
        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }
        $id_usuario = Auth::id(); 
        
        $LiberacaoPilotagem = LiberacaoPilotagem::find($id);
        $LiberacaoPilotagem->status_liberacao_pilotagems_id = 3;
        $LiberacaoPilotagem->motivo_recusa = $campo['motivo_recusa'];
        $LiberacaoPilotagem->aprovador_id = Auth::id();
        $LiberacaoPilotagem->save();

        $LiberacaoPilotagemHistorico = new LiberacaoPilotagemHistorico;
        $LiberacaoPilotagemHistorico->liberacao_pilotagems_id = $LiberacaoPilotagem->id;
        $LiberacaoPilotagemHistorico->estado = 'Reprovado';
        $LiberacaoPilotagemHistorico->users_id = $id_usuario;
        $LiberacaoPilotagemHistorico->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    private function getMotivos(){
        $busca = MotivoFinanceiro::select();
        $MotivoFinanceiro = $busca->get();

        $motivos = [];
        foreach($MotivoFinanceiro as $motivo){
            $motivos[$motivo->id] = $motivo->motivo;
        }
        return $motivos;
    }

    private function selectAbonarPilotagemAlterarComissao(){
        $abonar_pilotagem_alterar_comissao = [
            'abonar_pilotagem' => 'Abonar Pilotagem',
            'alterar_comissao' => 'Alterar Comissão'
        ];

        return $abonar_pilotagem_alterar_comissao;
    }

    private function vendedorRepresente(){
        $gerentes = [];
        $vendedor_representante = [];

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;

        if(!in_array(Auth::user()->tipo_usuario_id, ["18","15", "1", "11", "13", "19","20"])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            
            $userObj = User::whereIn('id', $subordinadosObj)->get();

            foreach ($userObj as $key => $user) {
                if($user->tipo_usuario_id === "16" && !empty($user->codigo_representante) || 
                    $user->tipo_usuario_id === "12" && !empty($user->codigo_representante) || $user->id == 1){
                    $vendedor_representante[$user->id] = $user->codigo_representante.' - '.strtoupper($user->name);
                }
            }

            unset($subordinadosObj);
        } else {
            $subordinadosObj = User::with(["tipo_usuario"])->get();
            foreach ($subordinadosObj as $key => $userObj) {
                if(!empty($userObj->codigo_representante)){
                    $vendedor_representante[$userObj->id] = $userObj->codigo_representante.' - '.strtoupper($userObj->name);
                }
            }
            $gerentes[Crypt::encrypt(0)] = 'OUTROS';

            asort($vendedor_representante);
            unset($subordinadosObj);
        }

       
        if(
            Auth::user()->tipo_usuario_id !== "16" &&
            Auth::user()->tipo_usuario_id !== "12"
        ){
            $check_gerentes = true;
            $check_supervisores = true;
            $check_vendedor_representante = true;
        }
        
        $variaveis_view = [
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
            'representantes'  => $vendedor_representante,
        ];

        return $variaveis_view;
    }
}
