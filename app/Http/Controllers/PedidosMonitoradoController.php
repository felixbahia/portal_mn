<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

use App\PedidosMonitorado;
use App\RegrasSeparacaoEmail;
use App\RegrasSeparacao;
use App\PedidoPortal;
use App\PedidoVenda;
use App\AprovacaoDePedido;

use Auth;

use App\Http\Controllers\EmailController;

class PedidosMonitoradoController extends Controller
{
    private $status_pedido_faturado = 6;
    private $status_pedido_cancelado = 9;


    public function indexSeparacao(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\PedidosMonitorado") === false){
            //return abort(403);
        }
        $request->session()->flash('model', 'App\PedidosMonitorado');
        return view("programs.monitoracao.separacao.index");
    }

    public function filterSeparacao(Request $request){
        $fields = $request->only(['data_inicio', 'data_fim']);

        $estabelecimentos_busca = [1, 2, 3, 4, 5, 6, 7, 8];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0, 0, 0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->setTime(0, 0, 0);
        
        $estabelecimentos = returnEmpresasNasajonView();

        $codigo_transportador_retira = '0001';
        
        $PedidoPortalObj = PedidosMonitorado::with('pedidoCompleto')
            ->whereHas('pedidoCompleto', function($query) use ($data_inicio, $data_fim, $estabelecimentos_busca, $codigo_transportador_retira){
                $query->whereIn('status_pedido', [2, 3, 4])
                    ->whereBetween('data_pedido', [$data_inicio, $data_fim])
                    ->whereIn('estabelecimento', $estabelecimentos_busca)
                    ->where('transportadora', intval($codigo_transportador_retira));
            })
            ->get();

        $response = [];
        foreach($PedidoPortalObj as $pedido){
            if(!isset($response[$pedido->estabelecimento])){
                $response[$pedido->estabelecimento] = [
                    'estabelecimento' => $pedido->estabelecimento,
                    'estabelecimento_nome' => $estabelecimentos[intval($pedido->estabelecimento)],
                    'pedidos_emitidos' => 0,
                    'pedidos_em_aprovacao' => 0,
                    'pedidos_faturados_no_prazo' => 0,
                    'pedidos_faturados_fora_do_prazo' => 0,
                    'pedidos_em_aberto_no_prazo' => 0,
                    'pedidos_em_aberto_fora_do_prazo' => 0                    
                ];
            }
            $response[$pedido->estabelecimento]['pedidos_emitidos']++;
        }
        $AprovacaoDePedidoObj = AprovacaoDePedido::with('pedido')
            ->whereHas('pedido', function($query) use ($data_inicio, $data_fim, $estabelecimentos_busca, $codigo_transportador_retira){
                $query->whereBetween('data_pedido', [$data_inicio, $data_fim])
                    ->whereIn('estabelecimento', $estabelecimentos_busca)
                    ->where('transportadora', intval($codigo_transportador_retira));
            })
            ->get();
        foreach($AprovacaoDePedidoObj as $aprovacao_pedido){
            $pedido = $aprovacao_pedido->pedido;
            if(!isset($response[$pedido->estabelecimento])){
                $response[$pedido->estabelecimento] = [
                    'estabelecimento' => $pedido->estabelecimento,
                    'estabelecimento_nome' => $estabelecimentos[intval($pedido->estabelecimento)],
                    'pedidos_emitidos' => 0,
                    'pedidos_em_aprovacao' => 0,
                    'pedidos_faturados_no_prazo' => 0,
                    'pedidos_faturados_fora_do_prazo' => 0,
                    'pedidos_em_aberto_no_prazo' => 0,
                    'pedidos_em_aberto_fora_do_prazo' => 0
                ];
            }
            $response[$pedido->estabelecimento]['pedidos_em_aprovacao']++;
        }

        $PedidosMonitoradoObj = PedidosMonitorado::with('pedidoCompleto')
            ->whereHas('pedidoCompleto', function($query) use ($data_inicio, $data_fim, $estabelecimentos_busca){
                $query->whereBetween('data_pedido', [$data_inicio, $data_fim])
                    ->whereIn('estabelecimento', $estabelecimentos_busca);
            })
            ->where('status_pedido_monitorado', 3)
            ->get();
        foreach($PedidosMonitoradoObj as $pedido){
            if(!isset($response[$pedido->estabelecimento])){
                $response[$pedido->estabelecimento] = [
                    'estabelecimento' => $pedido->estabelecimento,
                    'estabelecimento_nome' => $estabelecimentos[intval($pedido->estabelecimento)],
                    'pedidos_emitidos' => 0,
                    'pedidos_em_aprovacao' => 0,
                    'pedidos_faturados_no_prazo' => 0,
                    'pedidos_faturados_fora_do_prazo' => 0,
                    'pedidos_em_aberto_no_prazo' => 0,
                    'pedidos_em_aberto_fora_do_prazo' => 0
                ];
            }
            if($pedido->quantidade_alertas > 0){
                $response[$pedido->estabelecimento]['pedidos_faturados_fora_do_prazo']++;
            }else{
                $response[$pedido->estabelecimento]['pedidos_faturados_no_prazo']++;
            }
        }

        $PedidosMonitoradoObj = PedidosMonitorado::with('pedidoCompleto')
            ->whereHas('pedidoCompleto', function($query) use ($data_inicio, $data_fim, $estabelecimentos_busca){
                $query->whereBetween('data_pedido', [$data_inicio, $data_fim])
                    ->whereIn('estabelecimento', $estabelecimentos_busca)
                    ->whereIn('status_pedido', [3, 4]);
            })
            ->where('status_pedido_monitorado', 2)
            ->get();
        foreach($PedidosMonitoradoObj as $pedido){
            if(!isset($response[$pedido->estabelecimento])){
                $response[$pedido->estabelecimento] = [
                    'estabelecimento' => $pedido->estabelecimento,
                    'estabelecimento_nome' => $estabelecimentos[intval($pedido->estabelecimento)],
                    'pedidos_emitidos' => 0,
                    'pedidos_em_aprovacao' => 0,
                    'pedidos_faturados_no_prazo' => 0,
                    'pedidos_faturados_fora_do_prazo' => 0,
                    'pedidos_em_aberto_no_prazo' => 0,
                    'pedidos_em_aberto_fora_do_prazo' => 0
                ];
            }
            if($pedido->quantidade_alertas > 0){
                $response[$pedido->estabelecimento]['pedidos_em_aberto_fora_do_prazo']++;
            }else{
                $response[$pedido->estabelecimento]['pedidos_em_aberto_no_prazo']++;
            }
        }
        foreach($response as $estabelecimento => $dados){
            foreach($dados as $key => $value){
                if(empty($value)){
                    $response[$estabelecimento][$key] = '';
                }
            }
        }

        $return = [
            'status' => 'success',
            'message' => '',
            'response' => $response,
            'error' => []
        ];
        return response()->json($return);
    }

    public function monitorar(){
        $pedidos_monitorados = PedidosMonitorado::with('pedidoCompleto')
            ->where('status_pedido_monitorado', '!=', 3)
            ->whereHas('pedidoCompleto', function($query){
                $query->where('status_pedido', 3);
            })
            ->get();
        $regras_separacao = $this->getRegrasSeparacao();
        $pedidos_envio_notificacao = [];

        foreach ($pedidos_monitorados as $key => $pedido_monitorado) {
            if(!isset($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)])){
                continue;
            }
            $informacoesPedido = $this->inforcoesPecasPedidoPrologos($pedido_monitorado->pedidoCompleto);
            if(is_null($informacoesPedido) || is_null($informacoesPedido['pedido'])){
                continue;
            }
            if(!empty($informacoesPedido['pedido']->DATA_FIM)){
                $date_fim = date('Y-m-d', strtotime($informacoesPedido['pedido']->DATA_FIM)).' '.$informacoesPedido['pedido']->HORA_FIM.':00';
            }else{
                $date_fim = date('Y-m-d H:i:00');
            }
            $aprovacao = new Carbon($pedido_monitorado->aprovacao_data_hora);
            $faturamento = new Carbon($date_fim);
            $diff_dates = $aprovacao->diffInMinutes($faturamento);

            $regra_validar = null;
            foreach($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)] as $regra){
                if($regra->quantidade_pecas == $informacoesPedido['pecas']){
                    $regra_validar = $regra;
                }
            }
            if(is_null($regra_validar)){
                $regra_validar = end($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)]);
            }

            if($diff_dates >= $regra_validar->tempo){
                if(intval($pedido_monitorado->quantidade_alertas) === 0){
                    $this->sendEmail(str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT), $regra_validar, $pedido_monitorado->pedidoCompleto);
                }
                $pedido_monitorado->quantidade_alertas++;
            }
            if(!is_null($informacoesPedido['pedido']) && intval($informacoesPedido['pedido']->SITATUAL) === $this->status_pedido_cancelado){
                $pedido_monitorado->delete();
            }else{
                if(!is_null($informacoesPedido['pedido']) && intval($informacoesPedido['pedido']->SITATUAL) === $this->status_pedido_faturado){
                    $pedido_monitorado->status_pedido_monitorado = 3;
                    $pedido_monitorado->faturamento_data_hora = $date_fim;
                    $pedido_monitorado->faturamento_user = $informacoesPedido['user_faturamento']->NOME ?? '';
                    $pedido_monitorado->tempo_separacao = $diff_dates;
                }
                $pedido_monitorado->save();
            }
        }
    }

    private function sendEmail($estabelecimento, $regra, PedidoPortal $Pedido){
		$EmailObj = new EmailController();
		$email_send = [];
		$variaveis = [
            'pedido' => $Pedido->id,
            'pedido_gerado' => $Pedido->pedido_gerado,
            'link_pedido' => route('pedido_portal.detalhesDeslogado', ['hash' => encrypt(["pedido_id" => $Pedido->id])])
		];
		$returnEmail = $EmailObj->sendEmailToken($estabelecimento, "alerta_separacao_regra", $email_send, $variaveis);
    }

    private function getRegrasSeparacao(){
        $RegrasSeparacaoObj = RegrasSeparacao::all();
        $regras_separacao = [];
        foreach ($RegrasSeparacaoObj as $key => $regra) {
            $regras_separacao[$regra->estabelecimento][] = $regra;
        }
        return $regras_separacao;
    }

    private function inforcoesPecasPedidoPrologos(PedidoPortal $PedidoPortal){
        if(empty($PedidoPortal->pedido_gerado)){
            return null;
        }
        $pecas = 0;
        $dados_dum = null;
        $user_faturamento = null;
        $PedidoVenda = PedidoVenda::where('NUMPED', $PedidoPortal->pedido_gerado)
            ->where('ESTABEL', str_pad($PedidoPortal->estabelecimento, 2, 0, STR_PAD_LEFT))
            ->first();
        if(!is_null($PedidoVenda)){
            if(intval($PedidoVenda->SITATUAL) === $this->status_pedido_faturado){
                $date_fim = strtotime($PedidoVenda->DATA_FIM);
                $tabela_dum = 'DUM'.str_pad($PedidoPortal->estabelecimento, 2, 0, STR_PAD_LEFT).'_'.date('ym', $date_fim).'2';
                $dados_dum = DB::connection('srv_prologos')
                    ->table($tabela_dum)
                    ->where('NUMDOC', $PedidoVenda->NUMULTDUE)
                    ->first();
                if(!is_null($dados_dum)){
                    if(intval($PedidoPortal->estabelecimento) === 1){
                        $user_faturamento = $this->getNameUserAlmirante($dados_dum->CODUSU);
                        // $pecas = $this->getCountPecasAlmirante($PedidoPortal->pedido_gerado);
                    }
                    elseif(intval($PedidoPortal->estabelecimento) === 2){
                        $user_faturamento = $this->getNameUserBotelho($dados_dum->CODUSU);
                        // $pecas = $this->getCountPecasBotelho($PedidoPortal->pedido_gerado);
                    }
                }
            }
        }
        
        $pecas = $PedidoPortal->itens_pedido->count();
        $return = [
            'pecas' => $pecas,
            'pedido' => $PedidoVenda,
            'dum' => $dados_dum,
            'user_faturamento' => $user_faturamento
        ];
        return $return;
    }

    private function getNameUserAlmirante($cod_user){
        return DB::connection('srv_almirante')
            ->table('PLUSUI')
            ->where('CODIGO', $cod_user)
            ->first();
    }

    private function getNameUserBotelho($cod_user){
        return DB::connection('srv_botelho')
            ->table('PLUSUI')
            ->where('CODIGO', $cod_user)
            ->first();
    }

    private function getCountPecasAlmirante($numero_pedido){
        return DB::connection('srv_almirante')
            ->table('TBVOL1')
            ->where('NUMPED', $numero_pedido)
            ->count();
    }

    private function getCountPecasBotelho($numero_pedido){
        return DB::connection('srv_botelho')
            ->table('TBVOL1')
            ->where('NUMPED', $numero_pedido)
            ->count();
    }

    public function modalAbetrura(Request $request){
        $fields = $request->only(['estabelecimento', 'coluna', 'data_inicio', 'data_fim']);
        if($fields['coluna'] !== 'no_prazo' && $fields['coluna'] !== 'fora_do_prazo'){
            abort(404);
        }
        $estabelecimentos_busca = [1, 2];
        $estabelecimentos = returnEmpresasNasajonView();

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0, 0, 0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->setTime(0, 0, 0);
        
        $PedidosMonitoradoObj = PedidosMonitorado::with('pedidoCompleto')
            ->whereHas('pedidoCompleto', function($query) use ($data_inicio, $data_fim, $fields){
                $query->whereBetween('data_pedido', [$data_inicio, $data_fim])
                    ->where('estabelecimento', $fields['estabelecimento']);
            })
            ->where('status_pedido_monitorado', 3);
        if($fields['coluna'] === 'no_prazo'){
            $PedidosMonitoradoObj->whereNull('quantidade_alertas');
        }else{
            $PedidosMonitoradoObj->where('quantidade_alertas', '>', 0);
        }
        $PedidosMonitoradoObj = $PedidosMonitoradoObj->get();
        $retorno = [];
        $regras_separacao = $this->getRegrasSeparacao();
        foreach($PedidosMonitoradoObj as $pedido_monitorado){
            $regra_validar = null;
            foreach($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)] as $regra){
                if($regra->quantidade_pecas == $pedido_monitorado->pedidoCompleto->itens_pedido->count()){
                    $regra_validar = $regra;
                }
            }
            if(is_null($regra_validar)){
                $regra_validar = end($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)]);
            }

            $emissao_data_hora = new Carbon($pedido_monitorado->emissao_data_hora);
            $aprovacao_data_hora = new Carbon($pedido_monitorado->aprovacao_data_hora);
            $faturamento_data_hora = new Carbon($pedido_monitorado->faturamento_data_hora);
            $aprovacao_tempo = $emissao_data_hora->diffInMinutes($aprovacao_data_hora);
            $tempo_total = $emissao_data_hora->diffInMinutes($faturamento_data_hora);
            $tempo_diff = $pedido_monitorado->tempo_separacao - $regra_validar->tempo;
            
            if($tempo_diff <= 0){
                $tempo_diff = '';
            }
            $retorno[] = [
                'estabelecimento' => $pedido_monitorado->estabelecimento,
                'estabelecimento_nome' => $estabelecimentos[intval($pedido_monitorado->estabelecimento)],
                'pedido' => $pedido_monitorado->pedidoCompleto->pedido_gerado,
                'numero_pecas' => $pedido_monitorado->pedidoCompleto->itens_pedido->count(),
                'regra_sla' => $regra_validar->tempo,
                'aprovacao' => [
                    'inicio' => $emissao_data_hora->format('d/m/y H:i'),
                    'fim' => $aprovacao_data_hora->format('d/m/y H:i'),
                    'tempo' => $aprovacao_tempo
                ],
                'separacao' => [
                    'inicio' => '',
                    'fim' => '',
                    'tempo' => ''
                ],
                'faturamento' => [
                    'inicio' => $aprovacao_data_hora->format('d/m/y H:i'),
                    'fim' => $faturamento_data_hora->format('d/m/y H:i'),
                    'tempo' => $pedido_monitorado->tempo_separacao
                ],
                'tempo_total' => $tempo_total,
                'tempo_diff' => $tempo_diff
            ];
        }
        return view("programs.monitoracao.separacao.modal.abertura")->with(['retorno' => $retorno]);
    }

    public function modalAbetruraSeparacao(Request $request){
        $fields = $request->only(['estabelecimento', 'coluna', 'data_inicio', 'data_fim']);
        if($fields['coluna'] !== 'no_prazo' && $fields['coluna'] !== 'fora_do_prazo'){
            abort(404);
        }
        $estabelecimentos_busca = [1, 2];
        $estabelecimentos = returnEmpresasNasajonView();

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0, 0, 0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->setTime(0, 0, 0);
        
        $PedidosMonitoradoObj = PedidosMonitorado::with('pedidoCompleto.pedidoNasajon')
            ->whereHas('pedidoCompleto', function($query) use ($data_inicio, $data_fim, $fields){
                $query->whereBetween('data_pedido', [$data_inicio, $data_fim])
                    ->where('estabelecimento', $fields['estabelecimento'])
                    ->whereIn('status_pedido', [3, 4]);
            })
            ->where('status_pedido_monitorado', 2);
        if($fields['coluna'] === 'no_prazo'){
            $PedidosMonitoradoObj->whereNull('quantidade_alertas');
        }else{
            $PedidosMonitoradoObj->where('quantidade_alertas', '>', 0);
        }
        $PedidosMonitoradoObj = $PedidosMonitoradoObj->get();
        $retorno = [];
        $regras_separacao = $this->getRegrasSeparacao();
        foreach($PedidosMonitoradoObj as $pedido_monitorado){
            $regra_validar = null;
            if(!empty($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)])){
                foreach($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)] as $regra){
                    if($regra->quantidade_pecas == $pedido_monitorado->pedidoCompleto->itens_pedido->count()){
                        $regra_validar = $regra;
                    }
                }

                if(is_null($regra_validar)){
                    $regra_validar = end($regras_separacao[str_pad($pedido_monitorado->estabelecimento, 2, 0, STR_PAD_LEFT)]);
                }
            }
            
            
            // dd();

            $emissao_data_hora = new Carbon($pedido_monitorado->emissao_data_hora);
            $aprovacao_data_hora = new Carbon($pedido_monitorado->aprovacao_data_hora);
            $faturamento_data_hora = new Carbon($pedido_monitorado->faturamento_data_hora);
            $aprovacao_tempo = $emissao_data_hora->diffInMinutes($aprovacao_data_hora);
            $tempo_total = $emissao_data_hora->diffInMinutes($faturamento_data_hora);
            if(is_null($regra_validar)){
                $tempo_diff = '';
            }else{
                $tempo_diff = $pedido_monitorado->tempo_separacao - $regra_validar->tempo;
            }
            
            if($tempo_diff <= 0){
                $tempo_diff = '';
            }
            $retorno[] = [
                'estabelecimento' => $pedido_monitorado->estabelecimento,
                'estabelecimento_nome' => $estabelecimentos[intval($pedido_monitorado->estabelecimento)],
                'pedido' => "<a href=\"#\" id=\"bt_link\" onclick=\"showItens('".$pedido_monitorado->pedidoCompleto->pedidoNasajon->id."', 'nasajon', '".$estabelecimentos[intval($pedido_monitorado->estabelecimento)]."')\">".$pedido_monitorado->pedidoCompleto->pedido_gerado."</a>",
                'numero_pecas' => $pedido_monitorado->pedidoCompleto->itens_pedido->count(),
                'regra_sla' => is_null($regra_validar)? '' : $regra_validar->tempo,
                'aprovacao' => [
                    'inicio' => $emissao_data_hora->format('d/m/y H:i'),
                    'fim' => $aprovacao_data_hora->format('d/m/y H:i'),
                    'tempo' => $aprovacao_tempo
                ],
                'separacao' => [
                    'inicio' => '',
                    'fim' => '',
                    'tempo' => ''
                ],
                'faturamento' => [
                    'inicio' => $aprovacao_data_hora->format('d/m/y H:i'),
                    'fim' => !empty($pedido_monitorado->faturamento_data_hora) ? $faturamento_data_hora->format('d/m/y H:i') : '',
                    'tempo' => $pedido_monitorado->tempo_separacao
                ],
                'tempo_total' => $tempo_total,
                'tempo_diff' => $tempo_diff
            ];
        }
        return view("programs.monitoracao.separacao.modal.abertura_separacao")->with(['retorno' => $retorno]);
    }
    
}
