<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use App\OcorrenciasDeEntrega;
use App\NotasNasajon;
use App\ClienteNasajon;
use App\PesquisaSatisfacaoCliente;
use App\PesquisaSatisfacaoClienteNota;
use App\PedidoPortal;

class PesquisaSatisfacaoClienteController extends Controller
{
    private function cnpjINtercompany(){
        $clientesNasajonIntercompany = ClienteNasajon::where('cpf_cnpj', 'like', '06.311.274%')
                ->orWhere('cpf_cnpj', 'like', '05.075.884%')->get()->pluck('cpf_cnpj');
        return $clientesNasajonIntercompany;
    }

    public function verificaNotasSaida(){
        set_time_limit(6000);
        ini_set('memory_limit','3096M');
        $cnpj = $this->cnpjINtercompany();
        $hoje = Carbon::now();
        $data_inicial = Carbon::now()->subDays(116)->format('Y-m-d 00:00:00');
        $data_anterior = Carbon::now()->subDays(120)->format('Y-m-d 23:59:59');
        
        $data_inicial_atual = Carbon::now()->format('Y-m-d 00:00:00');
        $data_anterior_atual = Carbon::now()->subDays(5)->format('Y-m-d 23:59:59');
        $array_relacao_pedidos_portal = [];
        $pedidos_portal = '';
        
        $notas_saida = NotasNasajon::whereNotIn("cliente_documento", $cnpj)
        ->where(function($query) use ($data_anterior,$data_inicial,$data_inicial_atual,$data_anterior_atual){
            $query->whereBetween('emissao',[$data_anterior,$data_inicial])
            ->orWhereBetween('emissao',[$data_inicial_atual,$data_anterior_atual]);
        })
        ->get();
        
        $id_nota_saida = $notas_saida->pluck('id');

        if(!empty($id_nota_saida)){
            $ocorrencia_entrega = OcorrenciasDeEntrega::whereDoesntHave('notasPesquisaSatisfacao', function($query) use ($id_nota_saida){
                $query->whereIn('nota_nasajon_id',$id_nota_saida);
            })
            ->where('codigo_ocorrencia','001')
            ->select('nota_id','codigo_ocorrencia')
            ->distinct()
            ->limit(64000)
            ->get()
            ->pluck('nota_id')
            ->toArray();
        }else{
            $ocorrencia_entrega = null;
        }
        
        if(empty($ocorrencia_entrega) || count($ocorrencia_entrega) <= 0){
            return 'erro';
        }
        
        $pedidos_portal = PedidoPortal::whereIn(DB::raw('CONCAT(pedido_gerado, \' - \', LPAD(cast(estabelecimento as varchar),2,\'0\'), \' - \',codigo_operacao, \' - \',cod_cliente)'),$array_relacao_pedidos_portal)
        ->select(DB::raw('CONCAT(pedido_gerado, \' - \', LPAD(cast(estabelecimento as varchar),2,\'0\'), \' - \',codigo_operacao, \' - \',cod_cliente) as relacao_pedido'),'email_comprador')
        ->get();
        
        $dados_cliente = [];
        $notas_enviadas = PesquisaSatisfacaoClienteNota::where(function($query) use ($data_anterior,$data_inicial,$data_inicial_atual,$data_anterior_atual){
            $query->whereBetween('created_at',[$data_anterior,$data_inicial])
            ->orWhereBetween('created_at',[$data_inicial_atual,$data_anterior_atual]);
        })
        ->get()
        ->pluck('nota_nasajon_id');
        
        $notas_saida = NotasNasajon::with(['cliente','pedido','pesquisaSatisfacaoCliente','pesquisaSatisfacaoNota'])
        ->where(function($query) use ($ocorrencia_entrega){
            $query->orWhereIn('id',$ocorrencia_entrega)
            ->orWhere('transportadora_nome','RETIRA');
        })
        ->whereNotIn('id',$notas_enviadas)
        ->where(function($query) use ($data_anterior,$data_inicial,$data_inicial_atual,$data_anterior_atual){
            $query->whereBetween('emissao',[$data_anterior,$data_inicial])
            ->orWhereBetween('emissao',[$data_inicial_atual,$data_anterior_atual]);
        })
        ->get();

        unset($ocorrencia_entrega);
        
        $notas_saida->each(function($query_notas) use (&$array_relacao_pedidos_portal,&$pedidos_portal){

            if(!empty($query_notas->pedido->numero)){   
                $array_relacao_pedidos_portal[] = [
                    $query_notas->pedido->numero.' - '.$query_notas->pedido->estabelecimento_codigo.' - '.$query_notas->pedido->operacao_codigo.' - '.$query_notas->pedido->cliente_codigo
                ];
            }

        });
        
        $notas_saida->each(function($query) use (&$dados_cliente,&$pedidos_portal,$hoje){
            $email = '';
            if(!empty($query->cliente->email)){
                $email = $query->cliente->email;
            }else if(!empty($query->pedido)){
                $busca_email_pedido = $query->pedido->numero.' - '.$query->pedido->estabelecimento_codigo.' - '.$query->pedido->operacao_codigo.' - '.$query->pedido->cliente_codigo;
                $email = (isset($pedidos_portal->where('relacao_pedido',$busca_email_pedido)->email_comprador)) ? $pedidos_portal->where('relacao_pedido',$busca_email_pedido)->email_comprador : null;
            }

            if(empty($query->pesquisaSatisfacaoCliente)){
                if(!empty($email)){
                    if(!isset($dados_cliente[$query->cliente_documento])){
                        $dados_cliente[$query->cliente_documento] = [
                            'cliente_documento' => $query->cliente_documento,
                            'cliente_nome' => $query->cliente_nome,
                            'email' => $email,
                            'notas_informacoes' => [],
                            'ultimo_envio' => ''
                        ];
                    }
                    $dados_cliente[$query->cliente_documento]['notas_informacoes'][] = [
                        'nota_id' => $query->id,
                        'nota_numero' => $query->numero,
                    ];
                }
            }else{
                $data_ultimo_envio = Carbon::parse($query->pesquisaSatisfacaoCliente->created_at)->addDays(120);

                if(!empty($email) && $hoje->gte($data_ultimo_envio)){
                    if(!isset($dados_cliente[$query->cliente_documento])){
                        $dados_cliente[$query->cliente_documento] = [
                            'cliente_documento' => $query->cliente_documento,
                            'cliente_nome' => $query->cliente_nome,
                            'email' => $email,
                            'notas_informacoes' => [],
                            'ultimo_envio' => ''
                        ];
                    }
                    $dados_cliente[$query->cliente_documento]['notas_informacoes'][] = [
                        'nota_id' => $query->id,
                        'nota_numero' => $query->numero,
                    ];
                }
            }
        });
        
        unset($notas_saida);

        $emailControllerObj = new EmailController;
        $link = '';
        
        foreach($dados_cliente as $dados){
            $notas = '';

            $pesquisa_satisfacao = new PesquisaSatisfacaoCliente;
            $pesquisa_satisfacao->documento = $dados['cliente_documento'];
            $pesquisa_satisfacao->nome = $dados['cliente_nome'];
            $pesquisa_satisfacao->email = $dados['email'];
            $pesquisa_satisfacao->created_by = 1;
            $pesquisa_satisfacao->save();
            $pesquisa_satisfacao->link_formulario = md5($pesquisa_satisfacao->id);
            $pesquisa_satisfacao->save();

            $link = md5($pesquisa_satisfacao->id);

            foreach($dados['notas_informacoes'] as $numero){

                if(!empty($notas)){
                    $notas.=' / ';
                }

                $notas .= $numero['nota_numero'];

                $pesquisa_satisfacao_nota = new PesquisaSatisfacaoClienteNota;
                $pesquisa_satisfacao_nota->numero = $numero['nota_numero'];
                $pesquisa_satisfacao_nota->nota_nasajon_id = $numero['nota_id'];
                $pesquisa_satisfacao_nota->pesquisa_satisfacao_clientes_id = $pesquisa_satisfacao->id;
                $pesquisa_satisfacao_nota->created_by = 1;
                $pesquisa_satisfacao_nota->save();
            }

            $email = "<p>Prezado(a) Cliente,</p><br>";
            $email .= "<p>Estamos empenhados em melhorar a <b>qualidade</b> do nosso <b>atendimento</b></p><br>";
            $email .= "<p>Por isso, gostaríamos de saber como foi sua experiência com as compras na MN Tecidos sobre a(s) Nota(s) fiscal(is) nº ".$notas.".</p><br>";
            $email .= "<p>Você levará <b>apenas um minuto para responder</b> nossa pesquisa e nos ajudará a melhorar nossos produtos e serviços.</p><br>";
            $email .= "<p>Contamos com a sua participação!</p><br>";
            $email .= '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.route("formulario.pesquisa",$link).'" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="25%" strokecolor="#FF8C00" fillcolor="#FF8C00"><w:anchorlock/> <center style="color:#191970;font-family:sans-serif;font-size:13px;font-weight:bold;">PARTICIPE!</center> </v:roundrect> <![endif]--><a href="'.route("formulario.pesquisa",$link).'" style="background-color:#FF8C00;border:1px solid #FF8C00;border-radius:10px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">PARTICIPE!</a></div></center>';
            $mail_envio = $emailControllerObj->sendEmailToken('00', 'pesquisa_satisfacao', [$dados['email']] , ['nome_cliente' => $dados['cliente_nome'],'corpo' => $email]);
        }

        return;
    }

    public function reenviarPesquisaNaoRespondida(){
        set_time_limit(6000);
        ini_set('memory_limit','3096M');
        
        $data_inicial = Carbon::now()->format('Y-m-d 00:00:00');
        $data_anterior = Carbon::now()->subDays(35)->format('Y-m-d 23:59:59');
        $data_30_dias_anterior = Carbon::now()->subDays(30)->format('Y-m-d');
        $data_7_dias_anterior = Carbon::now()->subDays(7)->format('Y-m-d');

        $pesquisas_nao_respondidas =  PesquisaSatisfacaoCliente::doesntHave('formulariosRespondidos')
        ->with(['clienteNasajon','ultimoEnvio.clienteNotas','clienteNotas'])
        ->whereBetween('created_at',[$data_anterior,$data_inicial])
        ->get();

        foreach($pesquisas_nao_respondidas as $pesquisas){
            $data_envio = Carbon::parse($pesquisas->ultimoEnvio->created_at)->format('Y-m-d');
            $notas = '';

            if(empty($pesquisas->ultimoEnvio->envio_7_dias)){
                if($data_envio == $data_7_dias_anterior){
                    foreach($pesquisas->ultimoEnvio->clienteNotas as $numero){
        
                        if(!empty($notas)){
                            $notas.=' / ';
                        }
        
                        $notas .= $numero->numero;
                    }

                    $emailControllerObj = new EmailController;

                    $email = "<p>Prezado(a) Cliente,</p><br>";
                    $email .= "<p>Estamos empenhados em melhorar a <b>qualidade</b> do nosso <b>atendimento</b></p><br>";
                    $email .= "<p>Por isso, gostaríamos de saber como foi sua experiência com as compras na MN Tecidos sobre a(s) Nota(s) fiscal(is) nº ".$notas.".</p><br>";
                    $email .= "<p>Você levará <b>apenas um minuto para responder</b> nossa pesquisa e nos ajudará a melhorar nossos produtos e serviços.</p><br>";
                    $email .= "<p>Contamos com a sua participação!</p><br>";
                    $email .= '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.route("formulario.pesquisa",$pesquisas->ultimoEnvio->link_formulario).'" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="25%" strokecolor="#FF8C00" fillcolor="#FF8C00"><w:anchorlock/> <center style="color:#191970;font-family:sans-serif;font-size:13px;font-weight:bold;">PARTICIPE!</center> </v:roundrect> <![endif]--><a href="'.route("formulario.pesquisa",$pesquisas->ultimoEnvio->link_formulario).'" style="background-color:#FF8C00;border:1px solid #FF8C00;border-radius:10px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">PARTICIPE!</a></div></center>';
                    $mail_envio = $emailControllerObj->sendEmailToken('00', 'pesquisa_satisfacao', [$pesquisas->clienteNasajon->email] , ['nome_cliente' => $pesquisas->nome,'corpo' => $email]);

                    $pesquisas->ultimoEnvio->envio_7_dias = true;
                    $pesquisas->ultimoEnvio->save();
                }
            }else{
                if($data_envio == $data_30_dias_anterior){
                    foreach($pesquisas->ultimoEnvio->clienteNotas as $numero){
        
                        if(!empty($notas)){
                            $notas.=' / ';
                        }
        
                        $notas .= $numero->numero;
                    }

                    $emailControllerObj = new EmailController;

                    $email = "<p>Prezado(a) Cliente,</p><br>";
                    $email .= "<p>Estamos empenhados em melhorar a <b>qualidade</b> do nosso <b>atendimento</b></p><br>";
                    $email .= "<p>Por isso, gostaríamos de saber como foi sua experiência com as compras na MN Tecidos sobre a(s) Nota(s) fiscal(is) nº ".$notas.".</p><br>";
                    $email .= "<p>Você levará <b>apenas um minuto para responder</b> nossa pesquisa e nos ajudará a melhorar nossos produtos e serviços.</p><br>";
                    $email .= "<p>Contamos com a sua participação!</p><br>";
                    $email .= '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.route("formulario.pesquisa",$pesquisas->ultimoEnvio->link_formulario).'" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="25%" strokecolor="#FF8C00" fillcolor="#FF8C00"><w:anchorlock/> <center style="color:#191970;font-family:sans-serif;font-size:13px;font-weight:bold;">PARTICIPE!</center> </v:roundrect> <![endif]--><a href="'.route("formulario.pesquisa",$pesquisas->ultimoEnvio->link_formulario).'" style="background-color:#FF8C00;border:1px solid #FF8C00;border-radius:10px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">PARTICIPE!</a></div></center>';
                    $mail_envio = $emailControllerObj->sendEmailToken('00', 'pesquisa_satisfacao', [$pesquisas->clienteNasajon->email] , ['nome_cliente' => $pesquisas->nome,'corpo' => $email]);
                }
            }
        }

    }

}
