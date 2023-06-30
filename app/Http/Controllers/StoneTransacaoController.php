<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\StoneTransacoesPedido;
use App\StoneCadastroMaquininha;
use App\StoneErroTransacoesPedido;
use App\User;
use App\StoneTransacaoParcelamento;
use App\CartoesContratosNasajon;
use App\CartoesMeiosEletronicosNasajon;
use App\CartoesOperadorasNasajon;
use App\CartoesBandeirasNasajon;
use App\PedidoBloqueadoPagamentoNasajon;
use App\ContasNasajon;
use App\PedidoFormaPagamentoNasajon;
use App\StoneTransacoesPagamentosRestante;
use App\PedidoPortal;
use App\StoneAuthentication;
use App\StoneRetornoTransacoesAvulsa;
use App\StonePedidoOrder;
use App\CondicoesPagamentoWeb;

use App\Http\Controllers\EmailController;
use App\Http\Controllers\AprovacaoDePedidoController;
use Carbon\Carbon;
use Exception;

use function GuzzleHttp\json_encode;

class StoneTransacaoController extends Controller
{
    private $chave_privada = '';
    private $token = '';
    private $url_cretate_pre_transacao = '';
    private $url_token = '';
    private $url_status_pre_transacao = '';
    private $url_status_transacao = '';
    private $url_consulta_transacao = '';
    private $url_consulta_pre_transacao = '';
    private $url_desativa_pre_transacao = '';
    private $url_connect_20_base = '';
    private $cnpj_operadora = '16.501.555/0001-57';
    private $operacao_cartao_nasajon = [
        'nao_identificado' => 'c4b95a55-71f1-4047-936f-8dced97cbc12'
    ];
    private $meios_eletronicos = [
        'stone' => 'Stone'
    ];

    private $estabelecimentos_maquininha = [];/*

    *
    * Formadas de pagamentos com o UUID usados no lançamento manual
    */
    private $forma_pagamento_descricao = [
        'cartao_credito' => 'c41decd7-935f-449a-9a60-fd1a2330661b',
        'cartao_debito' => 'b0444787-b579-422d-af2a-ce691cbff825',
    ];

    public function __construct(){
        $this->getUrls();
        $this->gerarToken();
        $this->pegaEstabelecimentosMaquininha();
    }

    private function getUrls(){
        $this->chave_privada = config('stone.chave_privada');
        $this->url_token = config('stone.url_token');
        $this->url_cretate_pre_transacao = config('stone.url_cretate_pre_transacao');
        $this->url_status_pre_transacao = config('stone.url_status_pre_transacao');
        $this->url_status_transacao = config('stone.url_status_transacao');
        $this->url_consulta_transacao = config('stone.url_consulta_transacao');
        $this->url_consulta_pre_transacao = config('stone.url_consulta_pre_transacao');
        $this->url_desativa_pre_transacao = config('stone.url_desativa_pre_transacao');
        $this->url_connect_20_base = config('stone.url_connect_20');
    }

    private function gerarToken(){
        try{
            $client = new \GuzzleHttp\Client(['http_errors' => false]);
            $request = $client->request('GET', $this->url_token, [
                'headers' => [
                    'authorization' => $this->chave_privada,
                ]
            ]);

            $response = json_decode($request->getBody()->getContents());

            if($response->success == true){
                return $this->token = $response->token;
            }else{
                return "erro";
            }
        }catch(Exception $e){
            return "erro";
        }
    }

    private function pegaEstabelecimentosMaquininha(){
        $estabelecimentos = StoneCadastroMaquininha::select('estabelecimento')->get()->toArray();
        if(empty($estabelecimentos)){
            return;
        }
        foreach($estabelecimentos as $estabelecimento){
            $this->estabelecimentos_maquininha[$estabelecimento['estabelecimento']] = $estabelecimento['estabelecimento'];
        }
    }

    public function corrigeFormaPagamento(){
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $transacoes_correcao = StoneTransacoesPedido::get();
        
        $transacoes_correcao->each(function($query) use ($client){
            $transacao = StoneTransacoesPedido::find($query->id);
            try{
                $request_transacao = $client->request('GET', $this->url_consulta_transacao.$transacao->pre_transaction_id, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'authorization' => 'Bearer '.$this->token,
                    ],
                ]);
                
                $response = json_decode($request_transacao->getBody()->getContents());
                
                if(isset($response->success) && $response->success == true){
                    $transacao->payment_type = $response->transaction->payment_type;
                    $transacao->save();    

                }
            }catch(Exception $e){
                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = 'Pedido '.$transacao->pedido->id.' erro ao corrigir forma de pagamento - '.(string)$e->getMessage();
                $erro_transacao->pedido_id = $transacao->pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = $transacao->maquininha->id;
                $erro_transacao->created_by = 1;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$transacao->pedido->id.' erro ao consultar transação - '.(string)$e->getMessage()
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }
        });

    }

    public function integrarTransacoesManuaisNasajon(){
        set_time_limit(600);
        ini_set('memory_limit','2048M');

        $id_forma_pagamento_nasajon = [
            'cartao_credito' => 'c41decd7-935f-449a-9a60-fd1a2330661b',
            'cartao_debito' => 'b0444787-b579-422d-af2a-ce691cbff825',
            'dinheiro' => '04f167be-2b90-427d-ba3d-eb712c0e938b',
            'usar_credito' => 'f6661441-835e-41a5-88a5-9db2224aad4d'
        ];
        
        $transacoes = StoneTransacoesPedido::with('pedido.pedidoNasajon')
        ->whereHas('pagamentosParciais', function($query) use ($id_forma_pagamento_nasajon){
            $query->whereIn('forma_pagamento',$id_forma_pagamento_nasajon)
            ->where('api_nasajon',false);
        })
        ->where('pago',true)
        ->get();

        $transacoes->each(function($query) use ($id_forma_pagamento_nasajon){
            if(!empty($query->pedido->pedidoNasajon->situacao_descricao) && $query->pedido->pedidoNasajon->situacao_descricao == 'Faturado'){
                $this->verificarPagamentosParciais($query->id,$id_forma_pagamento_nasajon,null);
            }
        });

        $transacoes_restante = StoneTransacoesPagamentosRestante::with('pedido.pedidoNasajon')
        ->whereHas('pagamentosParciais', function($query) use ($id_forma_pagamento_nasajon){
            $query->whereIn('forma_pagamento',$id_forma_pagamento_nasajon)
            ->where('api_nasajon',false);
        })
        ->get();

        $transacoes_restante->each(function($query) use ($id_forma_pagamento_nasajon){
            if(!empty($query->pedido->pedidoNasajon->situacao_descricao) && $query->pedido->pedidoNasajon->situacao_descricao == 'Faturado'){
                $this->verificarPagamentosParciais($query->id,$id_forma_pagamento_nasajon,true);
            }
        });
    }

    private function verificarPagamentosParciais($id,$id_forma_pagamento_nasajon,$restante = null){
        set_time_limit(600);
        ini_set('memory_limit','2048M');

        if($restante == true){
            $transacoes_paciais = StoneTransacoesPagamentosRestante::with(['pedido','pedido.pedidoNasajon','pagamentosParciais.transacaoRestanteStone.pedido.pedidoNasajon','maquininha'])
            ->find($id);
        }else{
            $transacoes_paciais = StoneTransacoesPedido::with(['pedido','pedido.pedidoNasajon','pagamentosParciais.transacaoStone.pedido.pedidoNasajon','maquininha'])
            ->find($id);
        }
        
        $this->excluirTitulosNasajon($transacoes_paciais->id);
        
        $transacoes_paciais->pagamentosParciais->each(function($query) use ($id_forma_pagamento_nasajon){
            if(in_array($query->forma_pagamento,$id_forma_pagamento_nasajon) && !empty($query->transacaoStone->pedido)){
                
                if($query->forma_pagamento == 'c41decd7-935f-449a-9a60-fd1a2330661b' || $query->forma_pagamento == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $sql_registrar_pagamento = "select * from integracoes.api_pedidovenda_registrarpagamento_v2 (
                        '".$query->transacaoStone->pedido->pedidoNasajon->id."',
                        '".$query->forma_pagamento."',
                        '".$query->parcelamento."',
                        ".$query->valor."
                    );";   
                }else if($query->forma_pagamento == '04f167be-2b90-427d-ba3d-eb712c0e938b' || $query->forma_pagamento == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                    $sql_registrar_pagamento = "select * from integracoes.api_pedidovenda_registrarpagamento_v2 (
                        '".$query->transacaoStone->pedido->pedidoNasajon->id."',
                        '".$query->forma_pagamento."',
                        'afb58082-7fd6-488f-bc3d-f20ca963c88d',
                        ".$query->valor."
                    );";  
                }

                $id_pagamento = '';

                try{
                    $sql_nasajon = DB::connection('nasajon')->select($sql_registrar_pagamento);
                    $mensagem_nasajon = $sql_nasajon[0]->mensagem;
                    $mensagem_nasajon = json_decode($mensagem_nasajon, true);

                    if($mensagem_nasajon['codigo'] != 'OK'){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.$mensagem_nasajon['mensagem'];
                        $erro_transacao->pedido_id = $query->transacaoStone->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoStone->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.$mensagem_nasajon['mensagem']
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    }

                    $id_pagamento = $mensagem_nasajon['mensagem'];

                }catch(\Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.(string)$e->getMessage();
                    $erro_transacao->pedido_id = $query->transacaoStone->pedido->id;
                    $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoStone->maquininha->id;
                    $erro_transacao->created_by = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento" - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                }
                
                $sql_atualizar_cartao_query = "";
                
                if($query->forma_pagamento == 'c41decd7-935f-449a-9a60-fd1a2330661b' || $query->forma_pagamento == 'b0444787-b579-422d-af2a-ce691cbff825'){ 
                    $sql_atualizar_cartao_query = "select * from integracoes.api_pedidovenda_atualizarcartao_v2 (
                        '".$query->transacaoStone->pedido->pedidoNasajon->id."',
                        '".$id_pagamento."',
                        '".$query->codigo_autoriazacao."',
                        '".$query->data_autorizacao."',
                        '".$query->documento_cartao."',
                        '".$query->contrato_cartao."',
                        '".$query->cnpj_operadora."',
                        '".$query->meio_eletronico."',
                        '".$query->operadora."',
                        '".$query->bandeira."',
                        ".(int)$query->tipo_operacao."
                    );"; 

                    try{
                        $sql_atualizar_cartao = DB::connection('nasajon')->select($sql_atualizar_cartao_query);
                        $mensagem_nasajon = $sql_atualizar_cartao[0]->mensagem;
                        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                        
                        if($mensagem_nasajon['codigo'] != 'OK'){
                            $erro_transacao = new StoneErroTransacoesPedido;
                            $erro_transacao->erro_msg = 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.$mensagem_nasajon['mensagem'];
                            $erro_transacao->pedido_id = $query->transacaoStone->pedido->id;
                            $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoStone->maquininha->id;
                            $erro_transacao->created_by = 1;
                            $erro_transacao->save();

                            $EmailObj = new EmailController();
                            $email_send = [];
                            $variaveis = [
                                'erro' => 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.$mensagem_nasajon['mensagem']
                            ];
                            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        }

                        $retorno_uuid_pagamento = $mensagem_nasajon['mensagem'];
                    }catch(\Exception $e){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.(string)$e->getMessage();
                        $erro_transacao->pedido_id = $query->transacaoStone->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoStone->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$query->transacaoStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.(string)$e->getMessage()
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    }  
                }

                $query->query_api_nasajon = $sql_registrar_pagamento.' ; '.$sql_atualizar_cartao_query;
                $query->api_nasajon = true;
                $query->save();

            }else if(in_array($query->forma_pagamento,$id_forma_pagamento_nasajon) && !empty($query->transacaoRestanteStone->pedido)){
                
                if($query->forma_pagamento == 'c41decd7-935f-449a-9a60-fd1a2330661b' || $query->forma_pagamento == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $sql_registrar_pagamento = "select * from integracoes.api_pedidovenda_registrarpagamento_v2(
                        '".$query->transacaoRestanteStone->pedido->pedidoNasajon->id."',
                        '".$query->forma_pagamento."',
                        '".$query->parcelamento."',
                        ".$query->valor."
                    );";   
                }else if($query->forma_pagamento == '04f167be-2b90-427d-ba3d-eb712c0e938b' || $query->forma_pagamento == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                    $sql_registrar_pagamento = "select * from integracoes.api_pedidovenda_registrarpagamento_v2(
                        '".$query->transacaoRestanteStone->pedido->pedidoNasajon->id."',
                        '".$query->forma_pagamento."',
                        'afb58082-7fd6-488f-bc3d-f20ca963c88d',
                        ".$query->valor."
                    );";  
                }

                $id_pagamento = '';

                try{
                    $sql_nasajon = DB::connection('nasajon')->select($sql_registrar_pagamento);
                    $mensagem_nasajon = $sql_nasajon[0]->mensagem;
                    $mensagem_nasajon = json_decode($mensagem_nasajon, true);

                    if($mensagem_nasajon['codigo'] != 'OK'){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.$mensagem_nasajon['mensagem'];
                        $erro_transacao->pedido_id = $query->transacaoRestanteStone->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoRestanteStone->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.$mensagem_nasajon['mensagem']
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    }

                    $id_pagamento = $mensagem_nasajon['mensagem'];

                }catch(\Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.(string)$e->getMessage();
                    $erro_transacao->pedido_id = $query->transacaoRestanteStone->pedido->id;
                    $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoRestanteStone->maquininha->id;
                    $erro_transacao->created_by = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento_v2" - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                }

                $sql_atualizar_cartao_query = "";

                if($query->forma_pagamento == 'c41decd7-935f-449a-9a60-fd1a2330661b' || $query->forma_pagamento == 'b0444787-b579-422d-af2a-ce691cbff825'){ 
                    $sql_atualizar_cartao_query = "select * from integracoes.api_pedidovenda_atualizarcartao_v2(
                        '".$query->transacaoRestanteStone->pedido->pedidoNasajon->id."',
                        '".$id_pagamento."',
                        '".$query->codigo_autoriazacao."',
                        '".$query->data_autorizacao."',
                        '".$query->documento_cartao."',
                        '".$query->contrato_cartao."',
                        '".$query->cnpj_operadora."',
                        '".$query->meio_eletronico."',
                        '".$query->operadora."',
                        '".$query->bandeira."',
                        ".(int)$query->tipo_operacao."
                    );"; 

                    try{
                        $sql_atualizar_cartao = DB::connection('nasajon')->select($sql_atualizar_cartao_query);
                        $mensagem_nasajon = $sql_atualizar_cartao[0]->mensagem;
                        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                        
                        if($mensagem_nasajon['codigo'] != 'OK'){
                            $erro_transacao = new StoneErroTransacoesPedido;
                            $erro_transacao->erro_msg = 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.$mensagem_nasajon['mensagem'];
                            $erro_transacao->pedido_id = $query->transacaoRestanteStone->pedido->id;
                            $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoRestanteStone->maquininha->id;
                            $erro_transacao->created_by = 1;
                            $erro_transacao->save();

                            $EmailObj = new EmailController();
                            $email_send = [];
                            $variaveis = [
                                'erro' => 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.$mensagem_nasajon['mensagem']
                            ];
                            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        }

                        $retorno_uuid_pagamento = $mensagem_nasajon['mensagem'];
                    }catch(\Exception $e){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.(string)$e->getMessage();
                        $erro_transacao->pedido_id = $query->transacaoRestanteStone->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $query->transacaoRestanteStone->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$query->transacaoRestanteStone->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao_v2" - '.(string)$e->getMessage()
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    }  
                }

                $query->query_api_nasajon = $sql_registrar_pagamento.' ; '.$sql_atualizar_cartao_query;
                $query->api_nasajon = true;
                $query->save();
            }
        });

        $sql_atualizar_titulos = "select * from integracoes.api_pedidovenda_atualizar_financeiro_v2 (
            '".$transacoes_paciais->pedido->pedidoNasajon->id."'
        );"; 

        try{
            $sql_atualizar_titulos = DB::connection('nasajon')->select($sql_atualizar_titulos);
            $mensagem_nasajon = $sql_atualizar_titulos[0]->mensagem;
            $mensagem_nasajon = json_decode($mensagem_nasajon, true);

            if($mensagem_nasajon['codigo'] != 'OK'){
                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = 'Pedido '.$transacoes_paciais->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizar_financeiro_v2" - '.$mensagem_nasajon['mensagem'];
                $erro_transacao->pedido_id = $transacoes_paciais->pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = $transacoes_paciais->maquininha->id;
                $erro_transacao->created_by = 1;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$transacoes_paciais->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizar_financeiro_v2" - '.$mensagem_nasajon['mensagem']
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }

            $retorno_uuid_pagamento = $mensagem_nasajon['mensagem'];
        }catch(\Exception $e){
            $erro_transacao = new StoneErroTransacoesPedido;
            $erro_transacao->erro_msg = 'Pedido '.$transacoes_paciais->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizar_financeiro_v2" - '.(string)$e->getMessage();
            $erro_transacao->pedido_id = $transacoes_paciais->pedido->id;
            $erro_transacao->stone_cadastro_maquininha_id = $transacoes_paciais->maquininha->id;
            $erro_transacao->created_by = 1;
            $erro_transacao->save();

            $EmailObj = new EmailController();
            $email_send = [];
            $variaveis = [
                'erro' => 'Pedido '.$transacoes_paciais->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.(string)$e->getMessage()
            ];
            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
        }  
        $transacoes_paciais->api_nasajon = true;
        $transacoes_paciais->save();
    }

    public function verificaTransacoesAutomaticas(){
        set_time_limit(600);
        ini_set('memory_limit','2048M');

        $inicio_periodo = Carbon::now()->subDays(12);
        $fim_periodo = Carbon::now();

        $transacoes = StoneTransacoesPedido::whereBetween('created_at',[$inicio_periodo,$fim_periodo])
        ->where('pago',true)
        ->whereHas('orders.retornoTransacoes',function($query){
            $query->whereNull('api_nasajon')
            ->where('data_status','paid');
        })
        ->with('pedido.pedidoNasajon')
        ->get();

        $transacoes->each(function($query){
            if(!empty($query->pedido->pedidoNasajon->situacao_descricao) && $query->pedido->pedidoNasajon->situacao_descricao == 'Faturado'){
                $this->integracaoNasajonTrancacoesAutomaticasConnect20($query->id);
            }
        });
    }
    
    public function desbloquearPedido($id){
        $transacoes_pendentes = StoneTransacoesPedido::with(['maquininha.configuracaoMaquininha','pedido.pedidoNasajon'])
        ->find($id);

        $verifica_pedido_bloqueado = PedidoBloqueadoPagamentoNasajon::where('id_docfis',$transacoes_pendentes->pedido->pedidoNasajon->id)->exists();
                        
        if($verifica_pedido_bloqueado == true){
            $sql_api_validacao = "select * from integracoes.desbloquear_pedido('".$transacoes_pendentes->pedido->pedidoNasajon->id."')";

            try{
                $insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
            }catch(\Exception $e){
                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = 'Pedido '.$transacoes_pendentes->pedido->id.' erro ao liberar pedido manual - '.(string)$e->getMessage();
                $erro_transacao->pedido_id = $transacoes_pendentes->pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = $transacoes_pendentes->maquininha->id;
                $erro_transacao->created_by = 1;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$transacoes_pendentes->pedido->id.' erro ao consultar transação - '.(string)$e->getMessage()
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }
        }

        $transacoes_pendentes->pago = true;
        $transacoes_pendentes->save();

        $pedido = PedidoPortal::find($transacoes_pendentes->pedido->id);
        $pedido->status_pedido = 3;
        $pedido->save();
    }

    public function baixaTituloCredito($titulo_credito_uuid_nasajon, $conta_uuid_nasajon, $data_baixa, $valor){
        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $observacao_baixa_credito = "Baixa Total de Título de Crédito pelo Portal, usuário: Sistema data: ".date('Y-m-d H:i:s').".";
        $quitartitulo = 'true';

        $sql_baixa_titulo = "select * from integracoes.api_baixartituloreceber(
            '".$titulo_credito_uuid_nasajon."',
            '".$conta_uuid_nasajon."',
            '".$data_baixa->format('Y-m-d')."',
            ".$valor.",
            0.0,
            0.0,
            0.0, 
            0.0,
            0.0,
            0.0,
            '".$observacao_baixa_credito."',
            '".$usuario_cadastro_uuid."',
            ".$quitartitulo."
        );";

        try{
            $baixar_nasajon = DB::connection('nasajon')->select($sql_baixa_titulo);
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }

    /******
     * Envia o pedido presencial cartão para a maquininha com a Connect 2.0
     */
    public function enviarPedidoConnect20(PedidoPortal $pedido,$forcar_envio = false){
        $verifica_duplicidade = StoneTransacoesPedido::where('pedido_id',$pedido->id)->first();

        if(isset($verifica_duplicidade->id) && $forcar_envio === false){
            return;
        }

        $estabelecimento = str_pad($pedido->estabelecimento,2,'0',STR_PAD_LEFT);
        $estabelecimentos_cadastrados = $this->pegarEstabelecimentoAutenticacao();
        $maquininhas = StoneAuthentication::select();
        $maquininhas->where('producao',true);
        
        if(in_array($estabelecimento,$estabelecimentos_cadastrados) && isset($pedido->usuario_detalhes->tipo_usuario_id) && in_array($pedido->usuario_detalhes->tipo_usuario_id,[16,14,19])){
            $maquininhas->where('estabelecimento_codigo',$estabelecimento);
        }
        
        $maquininhas = $maquininhas->get();

        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $retorno_transacao = [];
        $informacao_display = (!empty($pedido->nome_comprador)) ? 'Ped '.$pedido->id.' - '.substr($pedido->nome_comprador, 0, 5) : 'Ped '.$pedido->id;
        $chaves = [];
        $serial = [];
        $itens = [];
        
        $pedido->itens_pedido->each(function($query) use (&$itens){
            $itens[] = [
                'amount' => (int) number_format($query->valor_total * 100, 0, "", ""),
                'description' => (!empty($query->info_produtoNasjon->especificacao)) ? $query->info_produtoNasjon->especificacao : 'Não encontrado.',
                'quantity' => 1,
                'code' => $query->cod_produto
            ];
        });

        /**
         * Pega a chave secreta para fazer a requisição e enviar o pedido
         */
        foreach($maquininhas as $chave){
            if(!isset($chaves[$chave->chave_secreta])){
                $chaves[$chave->chave_secreta] = [
                    'chave' => $chave->chave_secreta.':',
                    'serial' => [],
                    'stone_code' => $chave->stone_code
                ];
            }

            $chaves[$chave->chave_secreta]['serial'][] = $chave->serial;
        }

        foreach($chaves as $chave){
            $chave_encrypt  = $this->gerarChave64Bits($chave['chave']);

            try{
                $request_pre_transacao = $client->request('POST', $this->url_connect_20_base, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'authorization' => 'Basic '.$chave_encrypt,
                    ],
                    'json' => [
                        'customer' => [
                            'name' => (!empty($pedido->nome_comprador)) ? $pedido->nome_comprador : 'Sem Nome',
                            'email' => (!empty($pedido->email_comprador)) ? $pedido->email_comprador : 'sememail@semail.com',
                        ],
                        'items' => $itens,
                        'closed' => false,
                        'poi_payment_settings' => [
                            'visible' => 'true',
                            'display_name' => $informacao_display,
                            'print_order_receipt' => false,
                            'devices_serial_number' => $chave['serial']
                        ],
                    ],
                ]);
                $retorno_transacao[] = [   
                    'pedido' => $pedido->id,
                    'maquininha' => $chave['stone_code'],
                    'retorno' => json_decode($request_pre_transacao->getBody()->getContents())
                ];
            }catch(Exception $e){
                $stone_transacao = new StoneTransacoesPedido;
                $stone_transacao->pedido_id = $pedido->id;
                $stone_transacao->stone_cadastro_maquininha_id = 1;
                $stone_transacao->created_by = (isset(Auth::user()->id)) ? Auth::user()->id : $pedido->criadoPor->id;
                $stone_transacao->save();

                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = (string)$e->getMessage();
                $erro_transacao->created_by = (isset(Auth::user()->id)) ? Auth::user()->id : 1;
                $erro_transacao->pedido_id = $pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = 1;
                $erro_transacao->save();

                $pedido->status_pedido = 16;
                $pedido->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$pedido->id.' erro ao enviar transação - '.(string)$e->getMessage()
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }
        }

        foreach($retorno_transacao as $retorno){
            if(empty($retorno['retorno'])){
                continue;
            }
            $maquininha = StoneCadastroMaquininha::where('stone_code',$retorno['maquininha'])->first();

            if(!empty($retorno['retorno']->id)){
                $stone_transacao = new StoneTransacoesPedido;
                $stone_transacao->pedido_id = $retorno['pedido'];
                $stone_transacao->stone_cadastro_maquininha_id = (!empty($maquininha)) ? $maquininha->id : 1;
                $stone_transacao->pre_transaction_token = $retorno['retorno']->code;
                $stone_transacao->pre_transaction_id = $retorno['retorno']->id;
                $stone_transacao->pos_serial_number = $retorno['retorno']->poi_payment_settings->devices_serial_number[0];
                $stone_transacao->created_by = $pedido->criadoPor->id;
                $stone_transacao->save();

                $orders_pedido = new StonePedidoOrder;
                $orders_pedido->stone_transacoes_pedido_id = $stone_transacao->id;
                $orders_pedido->order_id = $retorno['retorno']->id;
                $orders_pedido->tipo = 'primeira_transacao';
                $orders_pedido->save();

            }else{
                $stone_transacao = new StoneTransacoesPedido;
                $stone_transacao->pedido_id = $pedido->id;
                $stone_transacao->stone_cadastro_maquininha_id = $maquininha->id;
                $stone_transacao->created_by = (isset(Auth::user()->id)) ? Auth::user()->id : $pedido->criadoPor->id;
                $stone_transacao->save();

                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = (string)$retorno['retorno'];
                $erro_transacao->created_by = (isset(Auth::user()->id)) ? Auth::user()->id : $pedido->criadoPor->id;
                $erro_transacao->stone_cadastro_maquininha_id = $maquininha->id;
                $erro_transacao->pedido_id = $pedido->id;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$pedido->id.' erro ao enviar pedido a maquininha - '.(string)$retorno['retorno']
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            
            }
        }


    }

    public function retornoApiConnect(Request $request){
        $retorno = $request->all();

        if(isset($retorno['data']['order']['id']) && !empty($retorno['id']) && !empty($retorno['data']['order']['id'])){
            $transacao = StoneTransacoesPedido::with('pedido','orders','retornoTransacaoAvulsa')
            ->whereHas('orders',function($query) use ($retorno){
                    $query->where('order_id',$retorno['data']['order']['id']);
            })
            ->first();

            $retorno_transacao = new StoneRetornoTransacoesAvulsa;

            if(!empty($transacao) && isset($retorno['data']['status']) && !empty($retorno['data']['status']) && $retorno['data']['status'] === 'paid'){
                $verifica_transacoes = StoneRetornoTransacoesAvulsa::where('data_code','ilike',$retorno['data']['code'])
                ->where('order_id','ilike',$retorno['data']['order']['id'])
                ->first();

                if(!empty($verifica_transacoes)){
                    return;
                }

                try{
                    $retorno_transacao->pedido_id = $transacao->pedido->id;
                    $retorno_transacao->stone_transacoes_pedido_id = $transacao->id;
                    $retorno_transacao->id_web_hook = $retorno['id'];
                    $retorno_transacao->account_id = $retorno['account']['id'];
                    $retorno_transacao->account_name = $retorno['account']['name'];
                    $retorno_transacao->type = $retorno['type'];
                    $retorno_transacao->data_id = $retorno['data']['id'];
                    $retorno_transacao->data_code = $retorno['data']['code'];
                    $retorno_transacao->data_amount = $retorno['data']['amount'];
                    $retorno_transacao->data_paid_amount = $retorno['data']['paid_amount'];
                    $retorno_transacao->data_status = $retorno['data']['status'];
                    $retorno_transacao->data_created_at = $retorno['data']['created_at'];
                    $retorno_transacao->order_id = $retorno['data']['order']['id'];
                    $retorno_transacao->order_code = $retorno['data']['order']['code'];
                    $retorno_transacao->order_amount = $retorno['data']['order']['amount'];
                    $retorno_transacao->order_closed = $retorno['data']['order']['closed'];
                    $retorno_transacao->order_currency = $retorno['data']['order']['currency'];
                    $retorno_transacao->order_status = $retorno['data']['order']['status'];
                    $retorno_transacao->customer_id = $retorno['data']['customer']['id'];
                    $retorno_transacao->customer_name = $retorno['data']['customer']['name'];
                    $retorno_transacao->metadata_scheme_name = $retorno['data']['metadata']['scheme_name'];
                    $retorno_transacao->metadata_account_funding_source = $retorno['data']['metadata']['account_funding_source'];
                    $retorno_transacao->metadata_autorization_code = $retorno['data']['metadata']['authorization_code'];
                    $retorno_transacao->metadata_account_holder_name = $retorno['data']['metadata']['account_holder_name'];
                    $retorno_transacao->metadata_initiator_transaction_key = $retorno['data']['metadata']['initiator_transaction_key'];
                    $retorno_transacao->metadata_installment_quantity = (empty($retorno['data']['metadata']['installment_quantity'])) ? 1 : $retorno['data']['metadata']['installment_quantity'];
                    $retorno_transacao->metadata_installment_type = $retorno['data']['metadata']['installment_type'];
                    $retorno_transacao->metadata_terminal_serial_number = $retorno['data']['metadata']['terminal_serial_number'];
                    $retorno_transacao->metadata_transaction_time = $retorno['data']['metadata']['transaction_timestamp'];
                    $retorno_transacao->json = json_encode($retorno);
                    $retorno_transacao->integracao_pedido = true;
                    $retorno_transacao->save();
                }catch(Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = (string)$e->getMessage();
                    $erro_transacao->created_by = 1;
                    $erro_transacao->pedido_id = $transacao->pedido->id;
                    $erro_transacao->stone_cadastro_maquininha_id = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'Pedido '.$transacao->pedido->id.' erro ao gravar webhook - '.$retorno['id'].' - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    return;
                }

                /**
                 * Verifica se o pagamento é antes ou depois da separação e se está pago 100%
                 */
                $pedido_portal = PedidoPortal::find($transacao->pedido->id);
                $valor_pedido = (int)str_replace([',','.'],'',parserValor($pedido_portal->valor_total_produtos)) / 100;
                
                if($transacao->pago === null){// não existe qualquer pagamento
                    $transacao = StoneTransacoesPedido::with('retornoTransacaoAvulsa')
                    ->whereHas('orders',function($query) use ($retorno){
                            $query->where('order_id',$retorno['data']['order']['id']);
                    })
                    ->first();

                    $valor_pago = ($transacao->retornoTransacaoAvulsa->where('data_status','paid')->sum('data_paid_amount') > 0) ? $transacao->retornoTransacaoAvulsa->where('data_status','paid')->sum('data_paid_amount') / 100 : 0;
                    $diferenca = $valor_pago - $valor_pedido;

                    if($diferenca == 0 || $diferenca > -1 && $diferenca < 1){// diferença paga inferior a 0.99
                        $transacao->pago = false;
                        $transacao->save();

                        $integrar_nadajon = new AprovacaoDePedidoController;
                        $integrar_nadajon->processaIntegracaoPedidoNasajon($pedido_portal,null);

                        $this->finalizarTransacao($pedido_portal,$retorno_transacao->order_id,'paid');
                    }
                }else if($transacao->pago === false){//recebimento da diferença
                    $pagamento_restante = StoneTransacoesPagamentosRestante::with(['pedido','orders' => function($query){
                        $query->where('tipo','pagamento_restante')
                        ->with('retornoTransacoes');
                    }])
                    ->whereHas('orders',function($query) use ($retorno){
                        $query->where('tipo','pagamento_restante');
                    })
                    ->where('pre_transaction_id',$retorno['data']['order']['id'])
                    ->first();

                    $valor_restante = (int)$pagamento_restante->transaction_amount;
                    $valor_pago_restante = 0;

                    $pagamento_restante->orders->each(function($query) use (&$valor_pago_restante){
                        foreach($query->retornoTransacoes as $transacao){
                            if($transacao->data_status === 'paid'){
                                $valor_pago_restante += $transacao->data_paid_amount;
                            }
                        }
                    });

                    $diferenca = $valor_pago_restante - $valor_restante;

                    if($diferenca == 0 || $diferenca > -1 && $diferenca < 1){// diferença paga inferior a 0.99

                        $pagamento_restante->status_pre_transacao = '1';
                        $pagamento_restante->pago = true;
                        $pagamento_restante->save();

                        $pedido_portal = PedidoPortal::find($pagamento_restante->pedido->id);
                        $pedido_portal->status_pedido = 3;
                        $pedido_portal->save();

                        $this->finalizarTransacao($pedido_portal,$retorno['data']['order']['id'],'paid');
                    }

                }

            }else if(empty($transacao) && isset($retorno['data']['status']) && !empty($retorno['data']['status']) && $retorno['data']['status'] === 'paid'){
                try{
                    $retorno_transacao->pedido_id = null;
                    $retorno_transacao->stone_transacoes_pedido_id = null;
                    $retorno_transacao->id_web_hook = $retorno['id'];
                    $retorno_transacao->account_id = $retorno['account']['id'];
                    $retorno_transacao->account_name = $retorno['account']['name'];
                    $retorno_transacao->type = $retorno['type'];
                    $retorno_transacao->data_id = $retorno['data']['id'];
                    $retorno_transacao->data_code = $retorno['data']['code'];
                    $retorno_transacao->data_amount = $retorno['data']['amount'];
                    $retorno_transacao->data_paid_amount = $retorno['data']['paid_amount'];
                    $retorno_transacao->data_status = $retorno['data']['status'];
                    $retorno_transacao->data_created_at = $retorno['data']['created_at'];
                    $retorno_transacao->order_id = $retorno['data']['order']['id'];
                    $retorno_transacao->order_code = $retorno['data']['order']['code'];
                    $retorno_transacao->order_amount = $retorno['data']['order']['amount'];
                    $retorno_transacao->order_closed = $retorno['data']['order']['closed'];
                    $retorno_transacao->order_currency = $retorno['data']['order']['currency'];
                    $retorno_transacao->order_status = $retorno['data']['order']['status'];
                    $retorno_transacao->customer_id = $retorno['data']['customer']['id'];
                    $retorno_transacao->customer_name = $retorno['data']['customer']['name'];
                    $retorno_transacao->metadata_scheme_name = $retorno['data']['metadata']['scheme_name'];
                    $retorno_transacao->metadata_account_funding_source = $retorno['data']['metadata']['account_funding_source'];
                    $retorno_transacao->metadata_autorization_code = $retorno['data']['metadata']['authorization_code'];
                    $retorno_transacao->metadata_account_holder_name = $retorno['data']['metadata']['account_holder_name'];
                    $retorno_transacao->metadata_initiator_transaction_key = $retorno['data']['metadata']['initiator_transaction_key'];
                    $retorno_transacao->metadata_installment_quantity = $retorno['data']['metadata']['installment_quantity'];
                    $retorno_transacao->metadata_installment_type = $retorno['data']['metadata']['installment_type'];
                    $retorno_transacao->metadata_terminal_serial_number = $retorno['data']['metadata']['terminal_serial_number'];
                    $retorno_transacao->metadata_transaction_time = $retorno['data']['metadata']['transaction_timestamp'];
                    $retorno_transacao->json = json_encode($retorno);
                    $retorno_transacao->integracao_pedido = true;
                    $retorno_transacao->save();
                }catch(Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = (string)$e->getMessage();
                    $erro_transacao->created_by = 1;
                    $erro_transacao->pedido_id = 1000;
                    $erro_transacao->stone_cadastro_maquininha_id = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'erro ao gravar webhook - '.$retorno['id'].' - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    return;
                }
            }else if(!empty($transacao) && isset($retorno['data']['status']) && !empty($retorno['data']['status']) && $retorno['data']['status'] === 'canceled'){
                try{
                    $retorno_transacao->pedido_id = $transacao->pedido->id;
                    $retorno_transacao->stone_transacoes_pedido_id = $transacao->id;
                    $retorno_transacao->id_web_hook = $retorno['id'];
                    $retorno_transacao->account_id = $retorno['account']['id'];
                    $retorno_transacao->account_name = $retorno['account']['name'];
                    $retorno_transacao->type = $retorno['type'];
                    $retorno_transacao->data_id = $retorno['data']['id'];
                    $retorno_transacao->data_code = $retorno['data']['code'];
                    $retorno_transacao->data_amount = $retorno['data']['amount'];
                    $retorno_transacao->data_paid_amount = 0;
                    $retorno_transacao->data_status = $retorno['data']['status'];
                    $retorno_transacao->data_created_at = $retorno['data']['created_at'];
                    $retorno_transacao->order_id = $retorno['data']['order']['id'];
                    $retorno_transacao->order_code = $retorno['data']['order']['code'];
                    $retorno_transacao->order_amount = $retorno['data']['order']['amount'];
                    $retorno_transacao->order_closed = $retorno['data']['order']['closed'];
                    $retorno_transacao->order_currency = $retorno['data']['order']['currency'];
                    $retorno_transacao->order_status = $retorno['data']['order']['status'];
                    $retorno_transacao->customer_id = (!empty($retorno['data']['customer']['id'])) ? $retorno['data']['customer']['id'] : '';
                    $retorno_transacao->customer_name = (!empty($retorno['data']['customer']['name'])) ? $retorno['data']['customer']['name'] : '';
                    $retorno_transacao->metadata_scheme_name = 'Sem Cartão';
                    $retorno_transacao->save();
                }catch(Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = (string)$e->getMessage();
                    $erro_transacao->created_by = 1;
                    $erro_transacao->pedido_id = 1000;
                    $erro_transacao->stone_cadastro_maquininha_id = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'erro ao gravar webhook - '.$retorno['id'].' - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    return;
                }
            }else if(empty($transacao) && isset($retorno['data']['status'])  && !empty($retorno['data']['status']) && $retorno['data']['status'] === 'canceled'){
                try{
                    $retorno_transacao->pedido_id = null;
                    $retorno_transacao->stone_transacoes_pedido_id = null;
                    $retorno_transacao->id_web_hook = $retorno['id'];
                    $retorno_transacao->account_id = $retorno['account']['id'];
                    $retorno_transacao->account_name = $retorno['account']['name'];
                    $retorno_transacao->type = $retorno['type'];
                    $retorno_transacao->data_id = $retorno['data']['id'];
                    $retorno_transacao->data_code = $retorno['data']['code'];
                    $retorno_transacao->data_amount = $retorno['data']['amount'];
                    $retorno_transacao->data_paid_amount = 0;
                    $retorno_transacao->data_status = $retorno['data']['status'];
                    $retorno_transacao->data_created_at = $retorno['data']['created_at'];
                    $retorno_transacao->order_id = $retorno['data']['order']['id'];
                    $retorno_transacao->order_code = $retorno['data']['order']['code'];
                    $retorno_transacao->order_amount = $retorno['data']['order']['amount'];
                    $retorno_transacao->order_closed = $retorno['data']['order']['closed'];
                    $retorno_transacao->order_currency = $retorno['data']['order']['currency'];
                    $retorno_transacao->order_status = $retorno['data']['order']['status'];
                    $retorno_transacao->customer_id = (!empty($retorno['data']['customer']['id'])) ? $retorno['data']['customer']['id'] : '';
                    $retorno_transacao->customer_name = (!empty($retorno['data']['customer']['name'])) ? $retorno['data']['customer']['name'] : '';
                    $retorno_transacao->metadata_scheme_name = 'Sem Cartão';
                    $retorno_transacao->save();
                }catch(Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = (string)$e->getMessage();
                    $erro_transacao->created_by = 1;
                    $erro_transacao->pedido_id = 1000;
                    $erro_transacao->stone_cadastro_maquininha_id = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'erro ao gravar webhook - '.$retorno['id'].' - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    return;
                }
            }

            return;
        }

        $erro_transacao = new StoneErroTransacoesPedido;
        $erro_transacao->erro_msg = 'retorno '.(string)json_encode($retorno);
        $erro_transacao->pedido_id = null;
        $erro_transacao->stone_cadastro_maquininha_id = 1;
        $erro_transacao->created_by = 1;
        $erro_transacao->save();

        $EmailObj = new EmailController();
        $email_send = [];
        $variaveis = [
            'erro' => 'Erro ao receber weeb hook '.(string)json_encode($retorno)
        ];

        if(isset($retorno_transacao) && empty($retorno_transacao)){
            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
        }
    }

    public function verificarValorSeparacao(){
        $inicio_periodo = Carbon::now()->subDays(9);
        $fim_periodo = Carbon::now();

        $transacoes = StoneTransacoesPedido::where('pago',false)
        ->whereHas('pedido',function($query){
            $query->whereNotNull('pedido_gerado');
        })
        ->whereBetween('created_at',[$inicio_periodo,$fim_periodo])
        ->with(['pedido.pedidoNasajon.notaEmAberto','pedido.pedidoNasajon.nota','retornoTransacaoAvulsa' => function($query){
            $query->where('data_status','paid')
            ->distinct();
        },'pagamentosParciais','pagamentoRestante.pagamentosParciais'])
        ->get();
        
        $transacoes->each(function($query){
            $valor_separacao = 0;

            if(!empty($query->pedido->cod_cliente) && $query->pedido->cod_cliente == '0000010069999' && $query->pedido->pedidoNasajon->situacao_descricao == 'Faturado' ||
            !empty($query->pedido->cod_cliente) && $query->pedido->cod_cliente == '0000010069999' && $query->pedido->pedidoNasajon->situacao_descricao == 'Em Faturamento' ||
            !empty($query->pedido->cod_cliente) && $query->pedido->cod_cliente == '0000010069999' && $query->pedido->pedidoNasajon->situacao_descricao == 'Liquidado'){
                if(!empty($query->pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                    $valor_separacao = floatVal($query->pedido->pedidoNasajon->valorTotalSeparacao->separacao);
                }
            }else if(isset($query->pedido->pedidoNasajon->notaEmAberto->valor)){
                $valor_separacao = floatVal($query->pedido->pedidoNasajon->notaEmAberto->valor);
            }else if(isset($query->pedido->pedidoNasajon->nota->valor)){
                $valor_separacao = floatVal($query->pedido->pedidoNasajon->nota->valor);
            }
            
            if($valor_separacao > 0){
                $valor_pago = 0;

                if($query->pagamento_parcial === true){
                    $valor_pago = $query->pagamentosParciais->sum('valor');
                }

                if($query->pagamento_restante === true){
                    if($query->pagamentoRestante->pagamento_parcial === true){
                        $valor_pago += $query->pagamentoRestante->pagamentosParciais->sum('valor');
                    }
                }

                if(!empty($query->retornoTransacaoAvulsa->sum('data_paid_amount'))){
                    $valor_pago += (float)($query->retornoTransacaoAvulsa->sum('data_paid_amount') / 100);
                }

                $diferenca = $valor_pago - $valor_separacao;
                
                if($diferenca == 0 || $diferenca > -0.05 && $diferenca < 0.05){// diferença inferior a $0.99
                    $pedido = PedidoPortal::find($query->pedido->id);
                    $pedido->status_pedido = 3;
                    $pedido->save();

                    $query->pago = true;
                    $query->status_pre_transacao = 1;
                    $query->save();

                    $this->desbloquearPedido($query->id);
                }else if($diferenca >= 0.05 && $query->credito === null){//gerar crédito
                    $credito = $diferenca;
                    $observacao_credito = "Título de Crédito Gerado Automático pelo Sistema, pela diferença de separação do pedido: ".$query->pedido->pedidoNasajon->numero." no estabelecimento: ".$query->pedido->pedidoNasajon->estabelecimento_codigo;
                    $estabelecimento_uuid_nasajon = $query->pedido->pedidoNasajon->estabelecimento;
                    $cliente_uuid_nasajon = $query->pedido->pedidoNasajon->cliente;
                    $data_emissao = Carbon::parse($query->pedido->pedidoNasajon->emissao);
                    $data_emissao_baixa = Carbon::now();
                    $data_vencimento = Carbon::now()->addYear();
                    $numero_titulo = $query->pedido->id;
                    $contasNasajonObj = ContasNasajon::select()->where('codigo','ilike', '000')->first();
                    $conta_uuid_nasajon = $contasNasajonObj->conta;
                    $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
                    $pedidoFormaPagamentoNasajonObj = PedidoFormaPagamentoNasajon::select()->where('formapagamento_descricao', 'ilike', 'Usar Crédito')->first();
                    $forma_pagamento_uuid_nasajon = $pedidoFormaPagamentoNasajonObj->formapagamento;

                    $sql_titulo_credito = "select * from integracoes.api_titulorecebermovo(
                        uuid_generate_v4(), /* id */
                        '{$estabelecimento_uuid_nasajon}', /* estabelecimento */
                        '{$cliente_uuid_nasajon}', /* cliente */
                        '{$credito}', /* valor */
                        '{$data_emissao->format('Y-m-d')}', /* emissao */
                        '{$data_vencimento->format('Y-m-d')}', /* vencimento */
                        '{$numero_titulo}.1CRD', /* numero */
                        '{$forma_pagamento_uuid_nasajon}', /* forma pagamento */
                        '{$conta_uuid_nasajon}', /* conta */
                        NULL, /* layout */
                        NULL, /* data_multa */
                        0.0, /* percentual multa */
                        0.0, /* juros diarios */
                        '{$usuario_cadastro_uuid}', /* usuario */
                        '{$observacao_credito}', /* observacao */
                        true /* tipo título crédito */
                    );";

                    try{
                        $titulo_credito = DB::connection('nasajon')->select($sql_titulo_credito);
                        $mensagem_nasajon = $titulo_credito[0]->mensagem;
                        $mensagem_nasajon = json_decode($mensagem_nasajon, true);  
                        $titulo_credito_uuid_nasajon = $mensagem_nasajon['mensagem'];
                    }catch(\Exception $e){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$query->pedido->id.' erro ao gerar crédito automático - '.(string)$e->getMessage();
                        $erro_transacao->pedido_id = $query->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $query->pedido->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$query->pedido->id.' erro ao gerar crédito automático - '.(string)$e->getMessage()
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    }
                    
                    $this->baixaTituloCredito($titulo_credito_uuid_nasajon,$conta_uuid_nasajon,$data_emissao_baixa,$credito);

                    $query->credito = true;
                    $query->pago = true;
                    $query->status_pre_transacao = 1;
                    $query->save();
    
                    $pedido = PedidoPortal::find($query->pedido->id);
                    $pedido->status_pedido = 3;
                    $pedido->save();

                    $this->desbloquearPedido($query->id);

                    if(!empty($query->pedido->email_comprador)){
                        $EmailObj = new EmailController();
                        $email_send = [
                            $query->pedido->email_comprador
                        ];
                        $variaveis = [
                            'cliente' => $query->pedido->nome_comprador,
                            'pedido' => $query->pedido->pedidoNasajon->numero,
                            'valor' => parserValor($credito)
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "credito_gerado_diferenca_pagamento", $email_send, $variaveis);
                    }
                }else if($diferenca <= -0.05 && $query->pagamento_restante === null){//gerar diferença maior
                    $saldo = abs($diferenca);

                    $estabelecimento = str_pad($query->pedido->estabelecimento,2,'0',STR_PAD_LEFT);
                    $estabelecimentos_cadastrados = $this->pegarEstabelecimentoAutenticacao();
                    $maquininhas = StoneAuthentication::select();
                    $maquininhas->where('producao',true);
                    
                    if(in_array($estabelecimento,$estabelecimentos_cadastrados) && isset($query->pedido->usuario_detalhes->tipo_usuario_id) && in_array($query->pedido->usuario_detalhes->tipo_usuario_id,[16,14,19])){
                        $maquininhas->where('estabelecimento_codigo',$estabelecimento);
                    }
                    
                    $client = new \GuzzleHttp\Client(['http_errors' => false]);
                    $maquininhas = $maquininhas->get();

                    foreach($maquininhas as $chave){
                        if(!isset($chaves[$chave->chave_secreta])){
                            $chaves[$chave->chave_secreta] = [
                                'chave' => $chave->chave_secreta.':',
                                'serial' => [],
                                'stone_code' => $chave->stone_code
                            ];
                        }
            
                        $chaves[$chave->chave_secreta]['serial'][] = $chave->serial;
                    }
            
                    foreach($chaves as $chave){
                        $chave_encrypt  = $this->gerarChave64Bits($chave['chave']);
                        $itens[] = [
                            'amount' => (int) number_format($saldo * 100, 0, "", ""),
                            'description' => 'Direfença Ped. '.$query->pedido->id,
                            'quantity' => 1,
                            'code' => '000000000000000000000'
                        ];
                        $informacao_display = (!empty($query->pedido->nome_comprador)) ? 'Ped '.$query->pedido->id.' - '.substr($query->pedido->nome_comprador, 0, 5) : 'Ped '.$query->pedido->id.' - S/N';

                        try{
                            $request_pre_transacao = $client->request('POST', $this->url_connect_20_base, [
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'authorization' => 'Basic '.$chave_encrypt,
                                ],
                                'json' => [
                                    'customer' => [
                                        'name' => (!empty($query->pedido->nome_comprador)) ? $query->pedido->nome_comprador : 'Sem Nome',
                                        'email' => (!empty($query->pedido->email_comprador)) ? $query->pedido->email_comprador : 'sememail@semail.com',
                                    ],
                                    'items' => $itens,
                                    'closed' => false,
                                    'poi_payment_settings' => [
                                        'visible' => 'true',
                                        'print_order_receipt' => false,
                                        'display_name' => $informacao_display,
                                        'devices_serial_number' => $chave['serial']
                                    ],
                                ],
                            ]);

                            $retorno_transacao[] = [   
                                'pedido' => $query->pedido->id,
                                'maquininha' => $chave['stone_code'],
                                'retorno' => json_decode($request_pre_transacao->getBody()->getContents())
                            ];
                        }catch(Exception $e){
            
                            $erro_transacao = new StoneErroTransacoesPedido;
                            $erro_transacao->erro_msg = (string)$e->getMessage();
                            $erro_transacao->created_by = (isset(Auth::user()->id)) ? Auth::user()->id : 1;
                            $erro_transacao->pedido_id = $query->pedido->id;
                            $erro_transacao->stone_cadastro_maquininha_id = 1;
                            $erro_transacao->save();
            
                            $query->pedido->status_pedido = 16;
                            $query->pedido->save();

                            $EmailObj = new EmailController();
                            $email_send = [];
                            $variaveis = [
                                'erro' => 'Pedido '.$query->pedido->id.' erro ao enviar transação de pagamento restante - '.(string)$e->getMessage()
                            ];
                            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        }

                    }

                    foreach($retorno_transacao as $retorno){
                        if(empty($retorno['retorno'])){
                            continue;
                        }
                        $maquininha = StoneCadastroMaquininha::where('stone_code',$retorno['maquininha'])->first();
            
                        if(!empty($retorno['retorno']->id)){
                            $stone_transacao = new StoneTransacoesPagamentosRestante;
                            $stone_transacao->pedido_id = $query->pedido->id;
                            $stone_transacao->stone_transacoes_pedido_id = $query->id;
                            $stone_transacao->stone_cadastro_maquininha_id = $query->maquininha->id;
                            $stone_transacao->pre_transaction_token = $retorno['retorno']->code;
                            $stone_transacao->pre_transaction_id = $retorno['retorno']->id;
                            $stone_transacao->transaction_amount = $retorno['retorno']->amount;
                            $stone_transacao->pago = false;
                            $stone_transacao->created_by = 1;
                            $stone_transacao->save();

                            $orders_pedido = new StonePedidoOrder;
                            $orders_pedido->stone_transacoes_pedido_id = $query->id;
                            $orders_pedido->order_id = $retorno['retorno']->id;
                            $orders_pedido->tipo = 'pagamento_restante';
                            $orders_pedido->save();
            
                        }else{
            
                            $erro_transacao = new StoneErroTransacoesPedido;
                            $erro_transacao->erro_msg = (string)$retorno['retorno'];
                            $erro_transacao->created_by = $query->pedido->criadoPor->id;
                            $erro_transacao->stone_cadastro_maquininha_id = $maquininha->id;
                            $erro_transacao->pedido_id = $query->pedido->id;
                            $erro_transacao->save();
            
                            $EmailObj = new EmailController();
                            $email_send = [];
                            $variaveis = [
                                'erro' => 'Pedido '.$query->pedido->id.' erro ao enviar diferença pedido a maquininha - '.(string)json_encode($retorno['retorno'])
                            ];
                            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        
                        }

                    }

                    $query->pagamento_restante = true;
                    $query->save();

                    $pedido = PedidoPortal::find($query->pedido->id);
                    $pedido->status_pedido = 9;
                    $pedido->save();
                }

            }
        });
        
    }

    private function pegarEstabelecimentoAutenticacao(){
        $estabelecimentos = StoneAuthentication::get()->pluck('estabelecimento_codigo')->toArray();
        return $estabelecimentos;
    }

    private function gerarChave64Bits($chave){
        return base64_encode($chave);
    }

    public function finalizarTransacao(PedidoPortal $pedido = null,$order,$status){
        $maquininhas = StoneAuthentication::select();
        $maquininhas->where('producao',true);

        $chave_pedido = StoneRetornoTransacoesAvulsa::with('pedidoStone')->where('order_id',$order)->first();

        if(!empty($chave_pedido->pedidoStone->pos_serial_number) && !empty($pedido)){
            $estabelecimento = str_pad($pedido->estabelecimento,2,'0',STR_PAD_LEFT);
            $maquininhas->where('estabelecimento_codigo',$estabelecimento);
        }

        $maquininhas = $maquininhas->get();

        foreach($maquininhas as $retorno_maquininha){
            $chave = $this->gerarChave64Bits($retorno_maquininha->chave_secreta.':');

            $client = new \GuzzleHttp\Client(['http_errors' => false]);
            
            $request = $client->request('PATCH', $this->url_connect_20_base.$order.'/closed', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'authorization' => 'Basic '.$chave,
                ],
                'json' => [
                    'status' => $status
                ],
            ]);

            $retorno = json_decode($request->getBody()->getContents());
            
            if(isset($retorno->id) && !empty($retorno->id)){
                $retornos_connect = StoneRetornoTransacoesAvulsa::where('order_id',$order)->get();

                $retornos_connect->each(function($query){
                    $query->finalizado = true;
                    $query->save();
                });

            }else if(isset($retorno->message) && $retorno->message != 'Order not found.' && $retorno->message != 'This order is closed.'){
                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Transação não finalizada, pedido '.$pedido->id.', mensagem: <br>'.(string)json_encode($retorno)
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }
        }
    }

    public function integracaoNasajonTrancacoesAutomaticasConnect20($id){
        set_time_limit(600);
        ini_set('memory_limit','2048M');

        $transacoes = StoneTransacoesPedido::with(['pedido.pedidoNasajon','maquininha','pedido.condicao_pagamento_detalhes','orders.retornoTransacoes' => function($query){
            $query->where('data_status','paid')
            ->distinct();
        }])->find($id);
        
        $this->excluirTitulosNasajon($id);

        if(empty($transacoes->orders[0])){
            return;
        }

        $transacoes->orders->each(function($query) use ($transacoes){
            foreach($query->retornoTransacoes as $retorno){
                if($retorno->metadata_account_funding_source === 'Credit' || $retorno->metadata_account_funding_source === 'credit'){
                    $forma_pagamento_nasajon = $this->forma_pagamento_descricao['cartao_credito'];
                    $parcelamento = $this->parcelasPagamentoStone((string)$retorno->metadata_installment_quantity);
                }else if($retorno->metadata_account_funding_source === 'Debit' || $retorno->metadata_account_funding_source === 'debit'){
                    $forma_pagamento_nasajon = $this->forma_pagamento_descricao['cartao_debito'];
                    $parcelamento = $this->parcelasPagamentoStone('debit');
                }else if($retorno->metadata_account_funding_source === 'Prepaid' || $retorno->metadata_account_funding_source === 'prepaid'){
                    $forma_pagamento_nasajon = $this->forma_pagamento_descricao['cartao_credito'];
                    $parcelamento = $this->parcelasPagamentoStone((string)$retorno->metadata_installment_quantity);
                }

                $valor = ($retorno->data_paid_amount / 100);
                $sql_registrar_pagamento = "select * from integracoes.api_pedidovenda_registrarpagamento_v2(
                    '".$transacoes->pedido->pedidoNasajon->id."',
                    '".$forma_pagamento_nasajon."',
                    '".$parcelamento."',
                    ".$valor."
                );";   

                $id_pagamento = '';

                try{
                    $sql_nasajon = DB::connection('nasajon')->select($sql_registrar_pagamento);
                    $mensagem_nasajon = $sql_nasajon[0]->mensagem;
                    $mensagem_nasajon = json_decode($mensagem_nasajon, true);

                    if($mensagem_nasajon['codigo'] != 'OK'){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento" - '.$mensagem_nasajon['mensagem'];
                        $erro_transacao->pedido_id = $transacoes->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento" - '.$mensagem_nasajon['mensagem']
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    }

                    $id_pagamento = $mensagem_nasajon['mensagem'];
                }catch(\Exception $e){
                    $erro_transacao = new StoneErroTransacoesPedido;
                    $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento" - '.(string)$e->getMessage();
                    $erro_transacao->pedido_id = $transacoes->pedido->id;
                    $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                    $erro_transacao->created_by = 1;
                    $erro_transacao->save();

                    $EmailObj = new EmailController();
                    $email_send = [];
                    $variaveis = [
                        'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_registrarpagamento" - '.(string)$e->getMessage()
                    ];
                    $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                }

                if(!empty($id_pagamento)){
                    $data_transacao = Carbon::parse($retorno->metadata_transaction_time)->format('Y-m-d');
                    
                    $bandeiras_nasajon = [  
                        'Visa' => 'be5ffccb-86b1-422c-a1c2-0130f15992d7',
                        'MasterCard' => '11228c0d-e9d1-4ea0-8e3e-bc53077b6362',
                        'AmericanExpress' => '9a60b8bf-35cc-488f-b93e-52670c4b1ddb',
                        'Elo' => 'b0f50491-0b45-4880-80f5-470e3a4e8fc3',
                        'Hipercard' => '6d657fc3-5fa0-4343-b0f8-d4b924ba10ee',
                        'SoroCred' => '3cbf18c0-7f12-4813-9b85-b83f606a3a9f',
                        'DinersClub' => '1ad5d636-a67b-417d-93c0-c77ee0f25e12',
                        'Cabal' => '235cf0a2-2b0d-4f7a-b6ea-5e80339e6576'
                    ];
                    if(empty($bandeiras_nasajon[$retorno->metadata_scheme_name])){
                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$transacoes->pedido->id.' Bandeira não registrada, transação id: '.$retorno->id
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);

                        $retorno->api_nasajon = null;
                        $retorno->save();

                        $transacoes->api_nasajon = null;
                        $transacoes->titulos_excluidos = null;
                        $transacoes->save();

                        return;
                    }

                    $bandeira = $bandeiras_nasajon[$retorno->metadata_scheme_name];

                    $tipo_pagamento = '';

                    if($retorno->metadata_account_funding_source == 'Debit'){
                        $tipo_pagamento = 1;
                    }else if($retorno->metadata_installment_quantity == '1'){
                        $tipo_pagamento = 2;
                    }else{
                        $tipo_pagamento = 3;
                    }

                    $id_operadora = CartoesOperadorasNasajon::whereIn('operadoracartao',$this->operacao_cartao_nasajon)->first();
                    $id_bandeira = CartoesBandeirasNasajon::where('bandeiracartao',$bandeira)->first();
                    $id_meio_eletronico = CartoesMeiosEletronicosNasajon::whereIn('codigo',$this->meios_eletronicos)->first();

                    $contrato_cartao_nasajon = CartoesContratosNasajon::where('estabelecimento',$transacoes->pedido->estabelecimentoDetalhes->estabelecimento)
                    ->where('bandeiracartao',$id_bandeira->bandeiracartao)
                    ->where('tipooperacao',$tipo_pagamento)
                    ->where('meioeletronicocartao',$id_meio_eletronico->meioeletronicocartao)
                    ->first();

                    $sql_atualizar_cartao_query = "select * from integracoes.api_pedidovenda_atualizarcartao_v2(
                        '".$transacoes->pedido->pedidoNasajon->id."',
                        '".$id_pagamento."',
                        '".$transacoes->transaction_authorization_code."',
                        '".$data_transacao."',
                        '".$retorno->data_id."',
                        '".$contrato_cartao_nasajon->contratocartao."',
                        '".$this->cnpj_operadora."',
                        '".$id_meio_eletronico->meioeletronicocartao."',
                        '".$id_operadora->operadoracartao."',
                        '".$id_bandeira->bandeiracartao."',
                        ".$tipo_pagamento."
                    );"; 

                    try{
                        $sql_atualizar_cartao = DB::connection('nasajon')->select($sql_atualizar_cartao_query);
                        $mensagem_nasajon = $sql_atualizar_cartao[0]->mensagem;
                        $mensagem_nasajon = json_decode($mensagem_nasajon, true);

                        if($mensagem_nasajon['codigo'] != 'OK'){
                            $erro_transacao = new StoneErroTransacoesPedido;
                            $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.$mensagem_nasajon['mensagem'];
                            $erro_transacao->pedido_id = $transacoes->pedido->id;
                            $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                            $erro_transacao->created_by = 1;
                            $erro_transacao->save();

                            $EmailObj = new EmailController();
                            $email_send = [];
                            $variaveis = [
                                'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.$mensagem_nasajon['mensagem']
                            ];
                            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        }

                        $retorno_uuid_pagamento = $mensagem_nasajon['mensagem'];
                    }catch(\Exception $e){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.(string)$e->getMessage();
                        $erro_transacao->pedido_id = $transacoes->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.(string)$e->getMessage()
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        return 'erro';
                    }

                    $sql_atualizar_titulos = "select * from integracoes.api_pedidovenda_atualizar_financeiro_v2 (
                        '".$transacoes->pedido->pedidoNasajon->id."'
                    );"; 

                    try{
                        $sql_atualizar_titulos = DB::connection('nasajon')->select($sql_atualizar_titulos);
                        $mensagem_nasajon = $sql_atualizar_titulos[0]->mensagem;
                        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                        
                        if($mensagem_nasajon['codigo'] != 'OK'){
                            $erro_transacao = new StoneErroTransacoesPedido;
                            $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.$mensagem_nasajon['mensagem'];
                            $erro_transacao->pedido_id = $transacoes->pedido->id;
                            $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                            $erro_transacao->created_by = 1;
                            $erro_transacao->save();

                            $EmailObj = new EmailController();
                            $email_send = [];
                            $variaveis = [
                                'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.$mensagem_nasajon['mensagem']
                            ];
                            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                        }

                        $retorno_uuid_pagamento = $mensagem_nasajon['mensagem'];
                    }catch(\Exception $e){
                        $erro_transacao = new StoneErroTransacoesPedido;
                        $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.(string)$e->getMessage();
                        $erro_transacao->pedido_id = $transacoes->pedido->id;
                        $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                        $erro_transacao->created_by = 1;
                        $erro_transacao->save();

                        $EmailObj = new EmailController();
                        $email_send = [];
                        $variaveis = [
                            'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_atualizarcartao" - '.(string)$e->getMessage()
                        ];
                        $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                    } 
                    
                    $retorno->api_nasajon = (!empty($retorno_uuid_pagamento)) ? true : null;
                    $retorno->retorno_api_nasajon = (isset($sql_registrar_pagamento) && !empty($sql_atualizar_cartao_query)) ? $sql_registrar_pagamento.'; '.(string)$sql_atualizar_cartao_query : '';
                    $retorno->save();
                }
            }

            $transacoes->api_nasajon = true;
            $transacoes->save();
        });
    }

    private function excluirTitulosNasajon($id){
        $transacoes = StoneTransacoesPedido::with(['pedido.pedidoNasajon','maquininha'])->find($id);

        if(empty($transacoes) || $transacoes->titulos_excluidos === true){
            return;
        }

        $sql_excluir_pagamento_nasajon = "select * from integracoes.api_pedidovenda_excluirpagamento_v2(
            '".$transacoes->pedido->pedidoNasajon->id."'
        );";

        try{
            $sql_nasajon = DB::connection('nasajon')->select($sql_excluir_pagamento_nasajon);
            $mensagem_nasajon = $sql_nasajon[0]->mensagem;
            $mensagem_nasajon = json_decode($mensagem_nasajon, true);

            if($mensagem_nasajon['codigo'] != 'OK'){
                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_excluirpagamento_v2" - '.$mensagem_nasajon['mensagem'];
                $erro_transacao->pedido_id = $transacoes->pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
                $erro_transacao->created_by = 1;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_excluirpagamento_v2" - '.$mensagem_nasajon['mensagem']
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }

        }catch(\Exception $e){
            $erro_transacao = new StoneErroTransacoesPedido;
            $erro_transacao->erro_msg = 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_excluirpagamento_v2" - '.(string)$e->getMessage();
            $erro_transacao->pedido_id = $transacoes->pedido->id;
            $erro_transacao->stone_cadastro_maquininha_id = $transacoes->maquininha->id;
            $erro_transacao->created_by = 1;
            $erro_transacao->save();

            $EmailObj = new EmailController();
            $email_send = [];
            $variaveis = [
                'erro' => 'Pedido '.$transacoes->pedido->id.' erro ao atualizar cartão no pedido presencial automático, na API "api_pedidovenda_excluirpagamento_v2" - '.(string)$e->getMessage()
            ];
            $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);

            return;
        }

        $transacoes->titulos_excluidos = true;
        $transacoes->save();
    }

    private function parcelasPagamentoStone($forma){
        $parcelas = '';
        switch($forma){
            case 'debit':
                $parcelas = '99e8ff10-c34a-4391-a735-d5d78a41d737';
                break;
            case '1':
                $parcelas = '3d9a8487-6239-4d03-b655-42b0cd0b00f1';
                break;
            case '2':
                $parcelas = '2d1151f6-de2f-43c4-ae22-992d723cd790';
                break;
            case '3':
                $parcelas = '156fa8f2-58cb-4e88-9524-c8b5a3a58503';
                break;
            case '4':
                $parcelas = '2c99b1f5-78ce-40bf-939a-a00fe6544931';
                break;
            case '5':
                $parcelas = '4d5cd094-c1b7-4f00-9720-88a8b2a99345';
                break;
            case '6':
                $parcelas = '37ed99c6-2256-4779-a28c-9c851e23ea33';
                break;
            case '7':
                $parcelas = 'a1039440-6c5a-4b65-af24-99a3d1d9da88';
                break;
            case '':
                $parcelas = '3d9a8487-6239-4d03-b655-42b0cd0b00f1';
                break;
        }

        return $parcelas;
    }

    public function monitorarTransacoesAbertas(){
        $inicio_periodo = Carbon::now()->subDays(2);
        $fim_periodo = Carbon::now();

        $transacoes = StoneTransacoesPedido::where(function($query){
            $query->whereNull('pago')
            ->orWhere('pago', false);
        })
        ->whereBetween('created_at',[$inicio_periodo,$fim_periodo])
        ->with('orders.retornoTransacoes','pedido.usuario_detalhes')
        ->whereHas('pedido',function($query){
            $query->where('status_pedido','<>',7);
        })
        ->withTrashed()
        ->get();

        $transacoes->each(function($query){
            if($query->pago === null && !empty($query->pedido)){
                if(!empty($query->orders[0])){
                    $hora_transacao = null;
                    $data_atual = null;
                    
                    $query->orders->each(function($query_order) use ($query,&$pago,&$hora_transacao,&$data_atual){
                        if(!empty($query_order->retornoTransacoes[0])){
                            $cancelado = true;
                            foreach($query_order->retornoTransacoes as $transacoes){
                                if($transacoes->data_status == 'paid'){
                                    $cancelado = false;
                                }
                            }

                            $hora_transacao = Carbon::parse($query_order->created_at)->addHours(6);
                            $data_atual = Carbon::now();

                            if($data_atual->gt($hora_transacao) && $cancelado === true && !empty($query->pedido)){
                                $this->finalizarTransacao($query->pedido,$query_order->order_id,'canceled');
                            }

                        }else if(empty($query_order->retornoTransacoes[0])){
                            $hora_transacao = Carbon::parse($query_order->created_at)->addHours(6);
                            $data_atual = Carbon::now();
                            
                            if($data_atual->gt($hora_transacao) && !empty($query->pedido)){
                                $this->finalizarTransacao($query->pedido,$query_order->order_id,'canceled');
                            }
                        }
                        
                        $hora_transacao = Carbon::parse($query_order->created_at)->addHours(6);
                        $data_atual = Carbon::now();
                    });
                    
                    if(!empty($data_atual) && !empty($hora_transacao) && $data_atual->gt($hora_transacao)){
                        $pedido = PedidoPortal::whereNull('pedido_gerado')
                        ->where('id',$query->pedido->id)
                        ->first();

                        if(!empty($pedido)){
                            $pedido->status_pedido = 7;
                            $pedido->save();

                            $EmailObj = new EmailController();
                            $email_send = [
                                $query->pedido->email_comprador
                            ];
                            $variaveis = [
                                'nome' => $query->pedido->usuario_detalhes->name,
                                'pedido' => $query->pedido->id
                            ];
        

                            $pedido = PedidoPortal::whereNull('pedido_gerado')
                            ->where('id',$query->pedido->id)
                            ->first();

                            if(!empty($pedido) && $query->pedido->status_pedido === 9){
                                $returnEmail = $EmailObj->sendEmailToken('00', "stone:fechamento_transacao", $email_send, $variaveis);
                                $pedido->status_pedido = 7;
                                $pedido->save();
                            }
                        }
                    }

                }else{
                    $hora_transacao = Carbon::parse($query->created_at)->addHours(6);
                    $data_atual = Carbon::now();

                    if($data_atual->gt($hora_transacao)){
                        $EmailObj = new EmailController();
                        $email_send = [
                            $query->pedido->email_comprador
                        ];
                        $variaveis = [
                            'nome' => $query->pedido->usuario_detalhes->name,
                            'pedido' => $query->pedido->id
                        ];
    

                        $pedido = PedidoPortal::whereNull('pedido_gerado')
                        ->where('id',$query->pedido->id)
                        ->first();

                        if(!empty($pedido) && $query->pedido->status_pedido === 9){
                            $returnEmail = $EmailObj->sendEmailToken('00', "stone:fechamento_transacao", $email_send, $variaveis);
                            $pedido->status_pedido = 7;
                            $pedido->save();
                        }

                        if(!empty($query->pedido)){
                            $this->finalizarTransacao($query->pedido,$query->pre_transaction_id,'canceled');
                        }
                    }
                }

            }else if($query->pago === false && !empty($query->pedido)){
                $query->orders->each(function($query_order) use ($query){
                    if(!empty($query_order->retornoTransacoes[0])){
                        $hora_transacao = Carbon::parse($query->created_at)->addHours(6);
                        $data_atual = Carbon::now();

                        if($data_atual->gt($hora_transacao)){
                            $EmailObj = new EmailController();
                            $email_send = [
                                $query->pedido->email_comprador
                            ];

                            $variaveis = [
                                'nome' => $query->pedido->usuario_detalhes->name,
                                'pedido' => $query->pedido->id
                            ];
        
                            //$returnEmail = $EmailObj->sendEmailToken('00', "stone:aviso_pagamento_diferenca", $email_send, $variaveis);
                        }
                        
                    }
                });

            }else if(empty($query->pedido)){
                $hora_transacao = Carbon::parse($query->created_at)->addHours(6);
                $data_atual = Carbon::now();

                if(!empty($query->orders[0])){
                    foreach($query->orders as $retorno){
                        $cancelado = true;

                        foreach($retorno->retornoTransacoes as $transacoes){
                            if($transacoes->data_status == 'paid'){
                                $cancelado = false;
                            }
                        }

                        if($data_atual->gte($hora_transacao) && $cancelado){
                            $this->finalizarTransacao(null,$query->pre_transaction_id,'canceled');
                        }
                    }
                }
            }

        });
    }

    public function verificaEnvioPagamentosDuplicados(){
        $inicio_periodo = Carbon::now()->subDays(1);
        $fim_periodo = Carbon::now();

        $transacoes = StoneTransacoesPedido::whereBetween('created_at',[$inicio_periodo,$fim_periodo])
        ->where(function($query){
            $query->whereNull('pago')
            ->orWhere('pago', false);
        })
        ->with('pedido')
        ->whereHas('pedido',function($query){
            $query->where('status_pedido','<>',7);
        })
        ->get();

        $pedidos_envio_duplicado = [];
        $verifica_duplicado = [];

        foreach($transacoes as $transacoes_retorno){
            if(!isset($verifica_duplicado[$transacoes_retorno->pedido_id])){
                $verifica_duplicado[$transacoes_retorno->pedido_id] = $transacoes_retorno->pedido_id;
            }else{
                if($transacoes_retorno->pago === false){
                    if(!isset($pedidos_envio_duplicado[$transacoes_retorno->pedido_id])){
                        $pedidos_envio_duplicado[$transacoes_retorno->pedido_id] = [
                            'pedido' => $transacoes_retorno->pedido_id
                        ];
                        $this->finalizarTransacao(null,$transacoes_retorno->pre_transaction_id,'paid');
                    }
                }
            }
        }

        foreach($transacoes as $transacoes_retornar){
            if($transacoes_retornar->pago === null && isset($pedidos_envio_duplicado[$transacoes_retornar->pedido_id])){
                $this->finalizarTransacao(null,$transacoes_retornar->pre_transaction_id,'canceled');
                $transacoes_retornar->delete();
            }
        }
    }
}
