<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Auth;
use Exeption;

use App\PedidoPortal;
use App\TempoEsperaSeparacaoPedido;
use App\NasajonEstabelecimento;

use App\Http\Requests\TempoEsperaPedidoFiltroRequest;
use App\Http\Requests\TempoEsperaPedidoCadastrarRequest;

class TempoEsperaPedidoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TempoEsperaPedido") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TempoEsperaPedido');
     
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.tempo_espera_pedido.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtro(TempoEsperaPedidoFiltroRequest $request){
        $campos = $request->only(['estabelecimento','numero_pedido','data_inicio','data_fim']);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_inicio'])->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_fim'])->format('Y-m-d 23:59:59');

        $pedido_portal = PedidoPortal::whereBetween('created_at',[$data_inicio,$data_fim])
        ->whereNotNull('pedido_gerado')
        ->whereNotIn('status_pedido',[5,7,6,2])
        ->with(['pedidoNasajon','tempoEspera','cliente']);

        if(!empty($campos['estabelecimento'])){
            $pedido_portal->where('estabelecimento',str_pad($campos['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($campos['numero_pedido'])){
            $pedido_portal->where('id',$campos['numero_pedido']);
        }

        $pedido_portal = $pedido_portal->get();

        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

        $pedido_portal->each(function($query) use (&$retorno,$estabelecimentos){
            $cliente = (!empty($query->cliente->nome)) ? $query->cliente->nome.' '.$query->cliente->cpf_cnpj : '';
            $status = '';

            if(!empty($query->pedidoNasajon) && $query->pedidoNasajon->situacao_descricao ===  'Faturado' ||
            !empty($query->pedidoNasajon) && $query->pedidoNasajon->situacao_descricao ===  'Em Faturamento'){
                $status = 'ra_finalizada';
            }else if(!empty($query->tempoEspera) && $query->tempoEspera->seperacao_finalizada === true){
                $status = 'tempo_manual_finalizado';
            }else if(!empty($query->tempoEspera) && $query->tempoEspera->seperacao_finalizada === false){
                $status = 'em_andamento';
            }

            $retorno[] = [
                'estabelecimento' => $estabelecimentos[(int)$query->estabelecimento],
                'pedido' => $query->id,
                'pedido_nasajon' => (!empty($query->pedidoNasajon->numero)) ? $query->pedidoNasajon->numero : '',
                'nota_id' => (!empty($query->pedidoNasajon->notafiscal_id)) ? $query->pedidoNasajon->notafiscal_id : '',
                'nota_numero' => (!empty($query->pedidoNasajon->notafiscal_numero)) ? $query->pedidoNasajon->notafiscal_numero : '',
                'ra' => (!empty($query->pedidoNasajon->num_ra)) ? $query->pedidoNasajon->num_ra : '',
                'cliente' => $cliente,
                'data' => (!empty($query->pedidoNasajon->emissao)) ? parserData($query->pedidoNasajon->emissao) : '',
                'valor' => (!empty($query->pedidoNasajon->valor)) ? parserValor($query->pedidoNasajon->valor) : '',
                'status' => $status,
                'tempo_manual_inicio' => (!empty($query->tempoEspera->inicio_separacao_manual)) ? parserDataEHora($query->tempoEspera->inicio_separacao_manual) : '',
                'tempo_manual_fim' => (!empty($query->tempoEspera->fim_separacao_manual)) ? parserDataEHora($query->tempoEspera->fim_separacao_manual) : '',
                'finalizacao_ra' => (!empty($query->tempoEspera->fim_separacao_nasajon)) ? parserDataEHora($query->tempoEspera->fim_separacao_nasajon) : '',
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno'=> $retorno
            ]
        ],200);
    }

    public function modalCadastrarTempoEspera(Request $request){
        $id = $request->only(['pedido_id']);
        
        $pedido = PedidoPortal::with(['cliente'])->find($id['pedido_id']);
        $cliente = (!empty($pedido->cliente->nome)) ? $pedido->cliente->nome.' '.$pedido->cliente->cpf_cnpj : '';

        return view('programs.tempo_espera_pedido.modal.cadastrar')->with(['cliente' => $cliente, 'id' => $pedido->id]);
    }

    public function cadastrarTempoEspera(TempoEsperaPedidoCadastrarRequest $request){
        $campos = $request->only(['pedido','data_inicio','data_fim']);

        $data_inicio = Carbon::createFromFormat('Y-m-d H:i', $campos['data_inicio'])->format('Y-m-d H:i');
        $data_fim = Carbon::createFromFormat('Y-m-d H:i', $campos['data_fim'])->format('Y-m-d H:i');

        $pedido = PedidoPortal::with(['pedidoNasajon'])->findOrFail($campos['pedido']);

        if(empty($pedido->pedidoNasajon)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido Nasajon Não Encontrado.',
                'error' => [],
                'response' => []
            ],200);
        }

        $pedido_cadastrado = TempoEsperaSeparacaoPedido::where('pedido_id',$campos['pedido'])->first();

        if(!empty($pedido_cadastrado)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido Já Cadastrado.',
                'error' => [],
                'response' => []
            ],200);
        }

        try{
            $tempo_espera = new TempoEsperaSeparacaoPedido;
            $tempo_espera->pedido_id = $pedido->id;
            $tempo_espera->pedido_nasajon = $pedido->pedidoNasajon->numero;
            $tempo_espera->id_pedido_nasajon = $pedido->pedidoNasajon->id;
            $tempo_espera->inicio_separacao_manual = $data_inicio;
            $tempo_espera->fim_separacao_manual = $data_fim;
            $tempo_espera->seperacao_finalizada = false;
            $tempo_espera->created_by = Auth::id();
            $tempo_espera->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Tempo registrado com sucesso.',
                'error' => [],
                'response' => []
            ],200);
        }catch(Exeption $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => [],
                'response' => []
            ],422);
        }
    }

    public function monitorarTempoEspera(Request $request){
        $estabelecimento = $request->only(['estabelecimento']);
        $estabelecimento = ltrim($estabelecimento['estabelecimento'],'0');

        $data_fim = Carbon::now();
        $data_inicio = Carbon::now()->subDays(1);
        
        $pedidos_lancados_abertos = TempoEsperaSeparacaoPedido::with(['pedidoNasajon.confirmacaoRetiraNota','pedidoNasajon.confirmacaoRetiraCupom'])
        ->whereBetween('created_at',[$data_inicio,$data_fim])
        ->where('seperacao_finalizada',false)
        ->orderBy('created_at','desc')
        ->whereHas('pedidoPortal',function($query) use ($estabelecimento){
            $query->where('estabelecimento',$estabelecimento);
        })
        ->get();

        $retorno = [];

        $pedidos_lancados_abertos->each(function($query) use (&$retorno){
            $agora = Carbon::now();

            if(!empty($query->pedidoNasajon) && $query->pedidoNasajon->situacao_descricao ===  'Em Faturamento'){
                $status = 'nao_conluido';
                
                if(empty($query->em_faturamento_nasajon)){
                    $query->em_faturamento_nasajon = $agora;
                    $query->seperacao_finalizada = false;
                    $query->save();
                }
            }else if(!empty($query->pedidoNasajon) && $query->pedidoNasajon->situacao_descricao ===  'Faturado'){
                $status = 'finalizado';
                if(empty($query->fim_separacao_nasajon)){
                    $query->fim_separacao_nasajon = $agora;
                    $query->seperacao_finalizada = false;
                    $query->save();
                }

                if(empty($query->em_faturamento_nasajon)){
                    $query->em_faturamento_nasajon = $agora;
                    $query->seperacao_finalizada = false;
                    $query->save();
                }

                if(!empty($query->pedidoNasajon->confirmacaoRetiraNota) ||!empty($query->pedidoNasajon->confirmacaoRetiraCupom)){
                    $query->seperacao_finalizada = true;
                    $query->save();
                }
            }else{
                $status = 'nao_conluido';
            }

            $inicio = '';
            $em_faturamento = '';
            $faturado = '';

            if(empty($query->em_faturamento_nasajon) && empty($query->fim_separacao_nasajon)){
                $inicio = '<div style="background-color: #C0C0C0; color: #363636;">'.Carbon::parse($query->fim_separacao_manual)->format('H:i').'</div>';
            }else if(!empty($query->em_faturamento_nasajon) || !empty($query->fim_separacao_nasajon)){
                $inicio = '<div style="background-color: #008000; color: #98FB98;">SEPARADO</div>';
            }

            if(!empty($query->em_faturamento_nasajon) && empty($query->fim_separacao_nasajon) || !empty($query->pedidoNasajon) && $query->pedidoNasajon->situacao_descricao ===  'Em Faturamento'){
                $em_faturamento = '<div style="background-color: #3D70F9; color: #fff;">AGUARDANDO</div>';
            }else if(!empty($query->em_faturamento_nasajon) && !empty($query->fim_separacao_nasajon) || !empty($query->pedidoNasajon) && $query->pedidoNasajon->situacao_descricao ===  'Faturado'){
                $em_faturamento = '<div style="background-color: #008000; color: #98FB98;">FATURADO</div>';
            }else{
                $em_faturamento = '<div style="background-color: #fff; color: #000000;"> - </div>';
            }

            if(!empty($query->fim_separacao_nasajon)){
                $faturado = '<div style="background-color: #3D70F9; color: #fff;">AGUARDANDO</div>';
            }else{
                $faturado = '<div style="background-color: #fff; color: #000000;"> - </div>';
            }

            $retorno[] = [
                'pedido_nasajon' => '<div style="background-color: #fff; color: #002076;">'.$query->pedidoNasajon->numero.'</div>',
                'status' => $status,
                'inicio' => $inicio,
                'em_faturamento_nasajon' => $em_faturamento,
                'fim_separacao_nasajon' => $faturado,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'T',
            'error' => [],
            'response' => [
                'pedidos' => $retorno
            ]
        ],200);
    }

    public function tempoEspera($estabelecimento){

        $estabelecimento = NasajonEstabelecimento::where('codigo',$estabelecimento)->first();
        $estabelecimento_descricao  = (!empty($estabelecimento)) ? $estabelecimento->descricao : '';
        $codigo  = (!empty($estabelecimento)) ? $estabelecimento->codigo : '';

        if(empty($codigo)){
            return abort(403);
        }

        return view('programs.tempo_espera_pedido.tempo_espera')->with(['estabelecimento' => $estabelecimento_descricao,'codigo' => $codigo ]);
    }
}
