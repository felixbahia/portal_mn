<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\ComprasNasajon;
use App\FornecedorNasajon;
use App\Importacao;
use App\ProdutoEspecificacao;
use App\ImportacaoDocumento;
use App\ImportacaoItem;
use App\ImportacaoEmbarque;
use App\ImportacaoCusto;
use App\ImportacaoFinanceiro;
use App\ImportacaoFinanceiroLancamento;
use App\ImportacaoValorPadrao;
use App\ImportacaoFollowUp;
use App\ImportacaoFollowUpHistoricoAprovacao;
use App\ImportacaoFinanceiroPrevisto;
use App\Red;
use App\RedsImportacaoFinanceiroLancamento;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ImportacaoRequest;
use App\Http\Requests\ImportacaoFinanceiroLancamentoRequest;
use App\Http\Requests\ImportacaoFinanceiroLancamentoRedAdicionarRequest;

class ImportacaoDadoComplemetarFollowUpController extends Controller
{
    public $path = 'public/importacao/';
    
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ImportacaoDadosComplementaresFollowUp") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ImportacaoDadosComplementaresFollowUp');

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.importacao_dados_complementares_follow_up.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function modalEditar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        $importacaoObj = Importacao::find($id);

        $dados_follow = [
            'tipo_cor' => '',
            'envio_cor_data_previsao' => '',
            'envio_cor_data_envio' => '',
            'envio_cor_data_recebido' => '',
            'quality_sample_aprovacao' => '',
            'quality_sample_data_previsao_envio' => '',
            'quality_sample_data_envio' => '',
            'quality_sample_data_recebido' => '',
            'quality_sample_data_previsao_aprovacao' => '',
            'quality_sample_data_aprovacao' => '',
            'laboratorio_aprovacao' => '',
            'laboratorio_data_previsao_envio' => '',
            'laboratorio_data_envio' => '',
            'laboratorio_data_recebido' => '',
            'laboratorio_data_aprovacao' => '',
            'tempo_producao_previsao_termino' => '',
            'tempo_producao_termino' => '',
            'amostra_embarque_aprovacao' => '',
            'amostra_embarque_data_previsao_envio' => '',
            'amostra_embarque_data_envio' => '',
            'amostra_embarque_data_recebido' => '',
            'amostra_embarque_data_previsao_aprovacao' => '',
            'amostra_embarque_data_aprovacao' => '',
            'autorizacao_embarque_data_previsao_envio' => '',
            'autorizacao_embarque_data_envio' => '',
            'envio_cor_data_revisao' => '',
            'quality_sample_transportadora' => '',
            'quality_sample_awb' => '',
            'amostra_embarque_transportadora' => '',
            'amostra_embarque_awb' => '', 
        ];

        if(empty($importacaoObj->followUpDetalhes)){
            $importacaoFollowUpObj = new ImportacaoFollowUp;
            $importacaoFollowUpObj->importacaos_id = $id;
            $importacaoFollowUpObj->created_by = Auth::id();
            $importacaoFollowUpObj->save();
        }else{
            $importacaoFollowUpObj = $importacaoObj->followUpDetalhes;
            $dados_follow['tipo_cor'] = empty($importacaoObj->followUpDetalhes->tipo_cor)? '' : $importacaoObj->followUpDetalhes->tipo_cor; 
            $dados_follow['envio_cor_data_previsao'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_previsao)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_previsao->format('d/m/Y'); 
            $dados_follow['envio_cor_data_envio'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_envio)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_envio->format('d/m/Y'); 
            $dados_follow['envio_cor_data_recebido'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_recebido)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_recebido->format('d/m/Y'); 
            $dados_follow['quality_sample_aprovacao'] = empty($importacaoObj->followUpDetalhes->quality_sample_aprovacao)? '' : $importacaoObj->followUpDetalhes->quality_sample_aprovacao; 
            $dados_follow['quality_sample_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['quality_sample_data_envio'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_envio)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_envio->format('d/m/Y'); 
            $dados_follow['quality_sample_data_recebido'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_recebido)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_recebido->format('d/m/Y'); 
            $dados_follow['quality_sample_data_previsao_aprovacao'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_previsao_aprovacao)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_previsao_aprovacao->format('d/m/Y'); 
            $dados_follow['quality_sample_data_aprovacao'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_aprovacao)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_aprovacao->format('d/m/Y'); 
            $dados_follow['laboratorio_aprovacao'] = empty($importacaoObj->followUpDetalhes->laboratorio_aprovacao)? '' : $importacaoObj->followUpDetalhes->laboratorio_aprovacao; 
            $dados_follow['laboratorio_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['laboratorio_data_envio'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_envio)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_envio->format('d/m/Y'); 
            $dados_follow['laboratorio_data_recebido'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_recebido)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_recebido->format('d/m/Y'); 
            $dados_follow['laboratorio_data_aprovacao'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_aprovacao)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_aprovacao->format('d/m/Y'); 
            $dados_follow['tempo_producao_previsao_termino'] = empty($importacaoObj->followUpDetalhes->tempo_producao_previsao_termino)? '' : $importacaoObj->followUpDetalhes->tempo_producao_previsao_termino->format('d/m/Y'); 
            $dados_follow['tempo_producao_termino'] = empty($importacaoObj->followUpDetalhes->tempo_producao_termino)? '' : $importacaoObj->followUpDetalhes->tempo_producao_termino->format('d/m/Y'); 
            $dados_follow['amostra_embarque_aprovacao'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_aprovacao)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_aprovacao; 
            $dados_follow['amostra_embarque_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_envio'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_envio)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_envio->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_recebido'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_recebido)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_recebido->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_previsao_aprovacao'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_aprovacao)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_aprovacao->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_aprovacao'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_aprovacao)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_aprovacao->format('d/m/Y'); 
            $dados_follow['autorizacao_embarque_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->autorizacao_embarque_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->autorizacao_embarque_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['autorizacao_embarque_data_envio'] = empty($importacaoObj->followUpDetalhes->autorizacao_embarque_data_envio)? '' : $importacaoObj->followUpDetalhes->autorizacao_embarque_data_envio->format('d/m/Y'); 
            $dados_follow['envio_cor_data_revisao'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_revisao)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_revisao->format('d/m/Y');
            $dados_follow['quality_sample_transportadora'] = empty($importacaoObj->followUpDetalhes->quality_sample_transportadora)? '' : $importacaoObj->followUpDetalhes->quality_sample_transportadora;
            $dados_follow['quality_sample_awb'] = empty($importacaoObj->followUpDetalhes->quality_sample_awb)? '' : $importacaoObj->followUpDetalhes->quality_sample_awb;
            $dados_follow['amostra_embarque_transportadora'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_transportadora)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_transportadora;
            $dados_follow['amostra_embarque_awb'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_awb)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_awb; 
        }

        $produtos = [];
        foreach($importacaoObj->itens as $item){ 
            $produtos[$item->produto_codigo] = $item->produto_codigo.' - '.$item->detalhesProduto->descricao;
        }

        $dados = [
            'id' => encrypt($id),
            'fornecedor' => $importacaoObj->fornecedor->nome.' - '.$importacaoObj->fornecedor->cnpj_cpf,
            'proforma' => $importacaoObj->numero_proforma,
            'pcmn' => $importacaoObj->pedido_compras,
            'referencia' => $importacaoObj->referencia,
            'data_proforma' => parserData($importacaoObj->data_proforma),
            'data_previsao_carta_programa' => empty($importacaoObj->data_previsao_carta_programa)? '' : parserData($importacaoObj->data_previsao_carta_programa),
            'respresentante' => empty($importacaoObj->respresentante)? '' : $importacaoObj->respresentante->nome.' - '.$importacaoObj->respresentante->cnpj_cpf,
            'dados_follow' => $dados_follow,
            'tipos_cores' => $this->dadosTipoCores(),
            'tipo_aprovacoes_simples' => $this->dadosSimplesAprovacao(),
            'tipo_aprovacoes_parcial' => $this->dadosAprovacaoParcial(),
            'id_follow_up' => encrypt($importacaoFollowUpObj->id),
            'produtos' => $produtos,
        ];

        return view('programs.importacao_dados_complementares_follow_up.modal.editar')->with(['dados' => $dados]);
    }
    
    public function editar(ImportacaoRequest $request){
        $fields = $request->only('id', "envio_das_cores","envio_cor_data_previsao","envio_cor_data_envio","envio_cor_data_recebido","quality_sample_aprovacao","quality_sample_data_previsao_envio","quality_sample_data_envio","quality_sample_data_recebido","quality_sample_data_previsao_aprovacao","quality_sample_data_aprovacao","laboratorio_data_aprovacao","laboratorio_data_previsao_envio","laboratorio_data_envio","laboratorio_data_recebido","laboratorio_data_aprovacao","tempo_producao_previsao","tempo_producao_termino","amostra_embarque_aprovacao","amostra_embarque_previsao","amostra_embarque_enviado","amostra_embarque_data_recebido","aprovacao_amostra_embarque_previsao","amostra_embarque_data_envio","autorizacao_embarque_previsao","autorizacao_embarque_data_envio","envio_cor_data_revisao","quality_sample_transportadora", "quality_sample_awb","amostra_embarque_transportadora","amostra_embarque_awb");

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function modalFinanceiroLancamento(Request $request) {
        $fields = $request->only(['id', 'inicio']);

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $lancamentos = [];
        $previstos = [];

        $importacaoObj = Importacao::find($id);

        $icms_saida_previsto = 0;

        $data_embarque_previsao = empty($importacaoObj->data_embarque_previsao)? '' : parserData($importacaoObj->data_embarque_previsao->addDays(60));
        $data_embarque_realizado = empty($importacaoObj->data_embarque_realizado)? '' : parserData($importacaoObj->data_embarque_realizado->addDays(60));

        $data_chegada_porto_previsao = empty($importacaoObj->data_chegada_porto_previsao)? '' : parserData($importacaoObj->data_chegada_porto_previsao->addDays(3));
        $data_chegada_porto_realizado = empty($importacaoObj->data_chegada_porto_realizado)? '' : parserData($importacaoObj->data_chegada_porto_realizado->addDays(3));

        $data_pagamento_parcela = !empty($data_embarque_realizado)? $data_embarque_realizado : $data_embarque_previsao;
        $data_pagamento_imposto = !empty($data_chegada_porto_realizado)? $data_chegada_porto_realizado : $data_chegada_porto_previsao;

        $data_pagamento_imposto = empty($data_pagamento_imposto)? parserData($importacaoObj->pedidoCompras->previsao_entrega->addDays(3)) : $data_pagamento_imposto;

        $total = [
            'cambio_valor_total' => 0,
            'real_valor_total' => 0,
            'quantidade' => 0,
            'quantidade_realizada' => 0,
            'preco_fob' => 0,
            'preco_contabil' => 0,
            'diferenca_preco' => 0,
        ];

        $total['preco_fob'] += empty($importacaoObj->frete_fornecedor)? 0 : $importacaoObj->frete_fornecedor;

        $parcelas = 0;
        $previstos = [];
        $lancamento_imposto = false;
        $lancamento_adiantamento = false;
        $lancamento_cartao_x = false;
        $associacao = [];
        if(!empty($importacaoObj->financeiro)){
            if(!empty($importacaoObj->financeiro->lancamentos)){
                foreach($importacaoObj->financeiro->lancamentos as $lancamento){
                    if($lancamento->previsto === false){
                        $lancamentos[] = [
                            "id" => encrypt($lancamento->id),
                            "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                            "cambio_data" => parserData($lancamento->cambio_data),
                            "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                            "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                            "real_valor" => parserValor($lancamento->real_valor),
                            "banco" => $lancamento->banco,
                            "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                            "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                            "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                            "modalidade" => str_replace("_", " ", $lancamento->modalidade),
                        ];
    
                        $total['cambio_valor_total'] += empty($lancamento->cambio_valor)? 0 : $lancamento->cambio_valor;
                        $total['real_valor_total'] += empty($lancamento->real_valor)? 0 : $lancamento->real_valor;
                    }else{
                        if(in_array($lancamento->modalidade, ['a_prazo', 'a_vista'])){
                            $index = 'parcela';
                        }else{
                            $index = $lancamento->modalidade;
                        }
                        
                        if($index == 'parcela'){
                            $previstos[$index][] = [
                                "id" => encrypt($lancamento->id),
                                "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                                "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                                "real_valor" => parserValor($lancamento->real_valor),
                                "banco" => $lancamento->banco,
                                "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                                "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                                "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                                "modalidade" => 'Pagamento Fornecedor',
                                "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                            ];

                            $parcelas++;
                        }else{
                            $previstos[$index] = [
                                "id" => encrypt($lancamento->id),
                                "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                                "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                                "real_valor" => parserValor($lancamento->real_valor),
                                "banco" => $lancamento->banco,
                                "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                                "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                                "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                                "modalidade" => str_replace("_", " ", $lancamento->modalidade),
                                "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                            ];
                        }

                        if($lancamento->pago === false){
                            $associacao[$lancamento->id] = ucfirst(str_replace("_", " ", $lancamento->modalidade));
                        }
                        
                        if($lancamento->modalidade === 'imposto'){
                            $lancamento_imposto = true;
                        }else if($lancamento->modalidade === 'antecipado'){
                            $lancamento_adiantamento = true;
                        }else if($lancamento->modalidade === 'carta_x'){
                            $lancamento_cartao_x = true;
                        }
                    }                    
                }
            }
        }else{
            $importacaoFinanceiroObj = new ImportacaoFinanceiro;
            $importacaoFinanceiroObj->importacaos_id = $importacaoObj->id;            
            $importacaoFinanceiroObj->debito_credito = null;
            $importacaoFinanceiroObj->save();

            $importacaoObj = Importacao::find($id);
        }
        
        if($importacaoObj->pago == false){
            if($lancamento_imposto === false){ 
                $arquivos = [];
                foreach($importacaoObj->documentos as $documento){
                    $arquivos['arquivo_'.$documento->tipo][] = Storage::url($documento->caminho);
                }
            
                foreach($importacaoObj->pedidoComprasItens as $item){       
                    $total['quantidade'] += $item->quantidade;
                    $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
                    $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
                    if(!empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                        $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
                    }
                }
    
                $ii = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->ii;
                $ipi = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->ipi;
                $pis = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->pis;
                $cofins = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->cofins;
                $afrmm = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->afrmm;
                $taxa_siscomex = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->taxa_siscomex;
                $sda = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->sda;
                $honorarios = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->honorarios;
                $expediente = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->expediente;
                $valor_li = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->valor_li;
                $agencia_maritima = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->agencia_maritima;
                $armazem = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->armazem;
                $laudo = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->laudo;
                $outras_despesas = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->outras_despesas;
                $icms_saida = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->icms_saida;
                $seguro = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->seguro;
                $transporte_rodoviario = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->transporte_rodoviario;
        
                $total_imposto = $ii + $pis + $ipi + $cofins + $taxa_siscomex + $agencia_maritima + $afrmm + $taxa_siscomex + $sda + $honorarios + $expediente + $valor_li + $laudo + $seguro + $armazem + $outras_despesas + $transporte_rodoviario;
    
                $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();
    
                $total_cambio_previsto = empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $total['preco_fob'] * $importacaoObj->valorPadrao->dolar_referencia;
                $total_cambio_realizado = empty($importacaoObj->financeiro->valorCambioTotal->total)? '' : parserValor($importacaoObj->financeiro->valorCambioTotal->total);
           
                $ii_previsto = $total_cambio_previsto * 26 / 100;
                $pis_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->pis / 100;
                $cofins_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->cofins / 100;
                $taxa_siscomex_previsto = empty($importacaoObj->valorPadrao->taxa_siscomex)? 0 : $importacaoObj->valorPadrao->taxa_siscomex;
                $agencia_maritima_previsto = empty($importacaoValorPadraoObj->agencia_maritima)? 0 : $importacaoValorPadraoObj->agencia_maritima;
                $afrmm_previsto = $agencia_maritima_previsto * 0.25;
                $taxa_siscomex_previsto = empty($importacaoObj->valorPadrao->taxa_siscomex)? 0 : $importacaoObj->valorPadrao->taxa_siscomex;
                $sda_previsto = empty($importacaoObj->valorPadrao->sda)? 0 : $importacaoObj->valorPadrao->sda;
                $honorarios_previsto = empty($importacaoObj->valorPadrao->honorarios)? 0 : $importacaoObj->valorPadrao->honorarios;
                $expediente_previsto = empty($importacaoObj->valorPadrao->expediente)? 0 : $importacaoObj->valorPadrao->expediente;
                $valor_li_previsto = empty($importacaoObj->valorPadrao->valor_li)? 0 : $importacaoObj->valorPadrao->valor_li;
                $agencia_maritima_previsto = empty($importacaoObj->valorPadrao->agencia_maritima)? 0 : $importacaoObj->valorPadrao->agencia_maritima;
                $laudo_previsto = empty($importacaoObj->valorPadrao->laudo)? 0 : $importacaoObj->valorPadrao->laudo;
                $seguro_previsto = empty($importacaoObj->valorPadrao->seguro)? 0 : $importacaoObj->valorPadrao->seguro;
                $armazenagem_previsto = empty($importacaoObj->valorPadrao->armazenagem)? 0 : $importacaoObj->valorPadrao->armazenagem;
                $outras_despesas_previsto = empty($importacaoObj->valorPadrao->outras_despesas)? 0 : $importacaoObj->valorPadrao->outras_despesas;
                $transporte_rodoviario_previsto = empty($importacaoValorPadraoObj->frete_rodoviario)? 0 : $importacaoValorPadraoObj->frete_rodoviario;
    
                $total_previsto = $ii_previsto + $pis_previsto + $cofins_previsto + $taxa_siscomex_previsto + $afrmm_previsto + $taxa_siscomex_previsto + $sda_previsto + $honorarios_previsto + $expediente_previsto + $valor_li_previsto + $agencia_maritima_previsto + $laudo_previsto + $seguro_previsto + $armazenagem_previsto + $outras_despesas_previsto + $transporte_rodoviario_previsto;
    
                $cambio_data =  empty($data_pagamento_imposto)? null : Carbon::createFromFormat('d/m/Y', $data_pagamento_imposto)->setTime(0,0,0);
    
                $importacaoFinanceiroLancamentoObj = new ImportacaoFinanceiroLancamento;
                $importacaoFinanceiroLancamentoObj->importacao_financeiros_id = $importacaoObj->financeiro->id;
                $importacaoFinanceiroLancamentoObj->cambio_data = $cambio_data;
                $importacaoFinanceiroLancamentoObj->cambio_valor = 0;
                $importacaoFinanceiroLancamentoObj->real_taxa = 0;
                $importacaoFinanceiroLancamentoObj->real_valor = (!empty($dados['arquivos']['arquivo_nf_importacao']))? $total_imposto : $total_previsto;
                $importacaoFinanceiroLancamentoObj->banco = '';
                $importacaoFinanceiroLancamentoObj->fechamento_tipo = 'imposto';
                $importacaoFinanceiroLancamentoObj->cambio_numero_contrato = '';
                $importacaoFinanceiroLancamentoObj->banco_numero_contrato = '';
                $importacaoFinanceiroLancamentoObj->modalidade = 'imposto';
                $importacaoFinanceiroLancamentoObj->pago = false;
                $importacaoFinanceiroLancamentoObj->previsto = true;
                $importacaoFinanceiroLancamentoObj->created_by = Auth::id();   
                $importacaoFinanceiroLancamentoObj->save();
    
                $previstos['imposto'] = [
                    "id" => encrypt($importacaoFinanceiroLancamentoObj->id),
                    "importacao_financeiros_id" => encrypt($importacaoFinanceiroLancamentoObj->importacao_financeiros_id),
                    "cambio_data" => empty($importacaoFinanceiroLancamentoObj->cambio_data)? '' : parserData($importacaoFinanceiroLancamentoObj->cambio_data),
                    "cambio_valor" => empty($importacaoFinanceiroLancamentoObj->cambio_valor)? '' : parserValor($importacaoFinanceiroLancamentoObj->cambio_valor),
                    "real_taxa" => empty($importacaoFinanceiroLancamentoObj->real_taxa)? '' : parserValor4CasasDecimais($importacaoFinanceiroLancamentoObj->real_taxa),
                    "real_valor" => parserValor($importacaoFinanceiroLancamentoObj->real_valor),
                    "banco" => $importacaoFinanceiroLancamentoObj->banco,
                    "fechamento_tipo" => str_replace("_", " ", $importacaoFinanceiroLancamentoObj->fechamento_tipo),
                    "cambio_numero_contrato" => $importacaoFinanceiroLancamentoObj->cambio_numero_contrato,
                    "banco_numero_contrato" => $importacaoFinanceiroLancamentoObj->banco_numero_contrato,
                    "modalidade" => str_replace("_", " ", $importacaoFinanceiroLancamentoObj->modalidade),
                    "baixa_data" => "",
                ];
            }else if(empty($previstos['imposto']['baixa_data'])){
                $arquivos = [];
                foreach($importacaoObj->documentos as $documento){
                    $arquivos['arquivo_'.$documento->tipo][] = Storage::url($documento->caminho);
                }
            
                foreach($importacaoObj->pedidoComprasItens as $item){       
                    $total['quantidade'] += $item->quantidade;
                    $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
                    $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
                    if(!empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                        $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
                    }
                }
    
                $ii = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->ii;
                $ipi = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->ipi;
                $pis = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->pis;
                $cofins = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->cofins;
                $afrmm = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->afrmm;
                $taxa_siscomex = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->taxa_siscomex;
                $sda = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->sda;
                $honorarios = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->honorarios;
                $expediente = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->expediente;
                $valor_li = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->valor_li;
                $agencia_maritima = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->agencia_maritima;
                $armazem = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->armazem;
                $laudo = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->laudo;
                $outras_despesas = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->outras_despesas;
                $icms_saida = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->icms_saida;
                $seguro = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->seguro;
                $transporte_rodoviario = empty($importacaoObj->custos)? 0 : $importacaoObj->custos->transporte_rodoviario;
        
                $total_imposto = $ii + $pis + $ipi + $cofins + $taxa_siscomex + $agencia_maritima + $afrmm + $taxa_siscomex + $sda + $honorarios + $expediente + $valor_li + $laudo + $seguro + $armazem + $outras_despesas + $transporte_rodoviario;
    
                $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();
    
                $total_cambio_previsto = empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $total['preco_fob'] * $importacaoObj->valorPadrao->dolar_referencia;
                $total_cambio_realizado = empty($importacaoObj->financeiro->valorCambioTotal->total)? '' : parserValor($importacaoObj->financeiro->valorCambioTotal->total);
           
                $ii_previsto = $total_cambio_previsto * 26 / 100;
                $pis_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->pis / 100;
                $cofins_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->cofins / 100;
                $taxa_siscomex_previsto = empty($importacaoObj->valorPadrao->taxa_siscomex)? 0 : $importacaoObj->valorPadrao->taxa_siscomex;
                $agencia_maritima_previsto = empty($importacaoValorPadraoObj->agencia_maritima)? 0 : $importacaoValorPadraoObj->agencia_maritima;
                $afrmm_previsto = $agencia_maritima_previsto * 0.25;
                $taxa_siscomex_previsto = empty($importacaoObj->valorPadrao->taxa_siscomex)? 0 : $importacaoObj->valorPadrao->taxa_siscomex;
                $sda_previsto = empty($importacaoObj->valorPadrao->sda)? 0 : $importacaoObj->valorPadrao->sda;
                $honorarios_previsto = empty($importacaoObj->valorPadrao->honorarios)? 0 : $importacaoObj->valorPadrao->honorarios;
                $expediente_previsto = empty($importacaoObj->valorPadrao->expediente)? 0 : $importacaoObj->valorPadrao->expediente;
                $valor_li_previsto = empty($importacaoObj->valorPadrao->valor_li)? 0 : $importacaoObj->valorPadrao->valor_li;
                $agencia_maritima_previsto = empty($importacaoObj->valorPadrao->agencia_maritima)? 0 : $importacaoObj->valorPadrao->agencia_maritima;
                $laudo_previsto = empty($importacaoObj->valorPadrao->laudo)? 0 : $importacaoObj->valorPadrao->laudo;
                $seguro_previsto = empty($importacaoObj->valorPadrao->seguro)? 0 : $importacaoObj->valorPadrao->seguro;
                $armazenagem_previsto = empty($importacaoObj->valorPadrao->armazenagem)? 0 : $importacaoObj->valorPadrao->armazenagem;
                $outras_despesas_previsto = empty($importacaoObj->valorPadrao->outras_despesas)? 0 : $importacaoObj->valorPadrao->outras_despesas;
                $transporte_rodoviario_previsto = empty($importacaoValorPadraoObj->frete_rodoviario)? 0 : $importacaoValorPadraoObj->frete_rodoviario;
    
                $total_previsto = $ii_previsto + $pis_previsto + $cofins_previsto + $taxa_siscomex_previsto + $afrmm_previsto + $taxa_siscomex_previsto + $sda_previsto + $honorarios_previsto + $expediente_previsto + $valor_li_previsto + $agencia_maritima_previsto + $laudo_previsto + $seguro_previsto + $armazenagem_previsto + $outras_despesas_previsto + $transporte_rodoviario_previsto;
    
                $cambio_data =  empty($data_pagamento_imposto)? null : Carbon::createFromFormat('d/m/Y', $data_pagamento_imposto)->setTime(0,0,0);
    
                $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::find(decrypt($previstos['imposto']['id']));
                $importacaoFinanceiroLancamentoObj->cambio_data = $cambio_data;
                $importacaoFinanceiroLancamentoObj->real_valor = (!empty($dados['arquivos']['arquivo_nf_importacao']))? $total_imposto : $total_previsto;  
                $importacaoFinanceiroLancamentoObj->save();
            }
            
            if($lancamento_adiantamento === false){
                $cambio_data = null;
    
                $importacaoFinanceiroLancamentoObj = new ImportacaoFinanceiroLancamento;
                $importacaoFinanceiroLancamentoObj->importacao_financeiros_id = $importacaoObj->financeiro->id;
                $importacaoFinanceiroLancamentoObj->cambio_data = null;
                $importacaoFinanceiroLancamentoObj->cambio_valor = empty($total['preco_fob'])? 0 : $total['preco_fob'] * 0.10;
                $importacaoFinanceiroLancamentoObj->real_taxa = 0;
                $importacaoFinanceiroLancamentoObj->real_valor = 0;
                $importacaoFinanceiroLancamentoObj->banco = '';
                $importacaoFinanceiroLancamentoObj->fechamento_tipo = 'cambio_pronto';
                $importacaoFinanceiroLancamentoObj->cambio_numero_contrato = '';
                $importacaoFinanceiroLancamentoObj->banco_numero_contrato = '';
                $importacaoFinanceiroLancamentoObj->modalidade = 'antecipado';
                $importacaoFinanceiroLancamentoObj->pago = false;
                $importacaoFinanceiroLancamentoObj->previsto = true;
                $importacaoFinanceiroLancamentoObj->created_by = Auth::id();   
                $importacaoFinanceiroLancamentoObj->save();
    
                $previstos['antecipado'] = [
                    "id" => encrypt($importacaoFinanceiroLancamentoObj->id),
                    "importacao_financeiros_id" => encrypt($importacaoFinanceiroLancamentoObj->importacao_financeiros_id),
                    "cambio_data" => empty($importacaoFinanceiroLancamentoObj->cambio_data)? '' : parserData($importacaoFinanceiroLancamentoObj->cambio_data),
                    "cambio_valor" => empty($importacaoFinanceiroLancamentoObj->cambio_valor)? '' : parserValor($importacaoFinanceiroLancamentoObj->cambio_valor),
                    "real_taxa" => empty($importacaoFinanceiroLancamentoObj->real_taxa)? '' : parserValor4CasasDecimais($importacaoFinanceiroLancamentoObj->real_taxa),
                    "real_valor" => parserValor($importacaoFinanceiroLancamentoObj->real_valor),
                    "banco" => $importacaoFinanceiroLancamentoObj->banco,
                    "fechamento_tipo" => str_replace("_", " ", $importacaoFinanceiroLancamentoObj->fechamento_tipo),
                    "cambio_numero_contrato" => $importacaoFinanceiroLancamentoObj->cambio_numero_contrato,
                    "banco_numero_contrato" => $importacaoFinanceiroLancamentoObj->banco_numero_contrato,
                    "modalidade" => str_replace("_", " ", $importacaoFinanceiroLancamentoObj->modalidade),
                    "baixa_data" => "",
                ];
            }
    
            if($lancamento_cartao_x === false){
                if(empty($total['preco_contabil'])){
                    foreach($importacaoObj->pedidoComprasItens as $item){       
                        $total['quantidade'] += $item->quantidade;
                        $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
                        $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
                        if(!empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                            $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
                        }
                    }
                }
    
                if($total['preco_contabil'] > $total['preco_fob']){
                    $importacaoFinanceiroLancamentoObj = new ImportacaoFinanceiroLancamento;
                    $importacaoFinanceiroLancamentoObj->importacao_financeiros_id = $importacaoObj->financeiro->id;
                    $importacaoFinanceiroLancamentoObj->cambio_data = null;
                    $importacaoFinanceiroLancamentoObj->cambio_valor = $total['preco_contabil'] - ($total['preco_fob']);
                    $importacaoFinanceiroLancamentoObj->real_taxa = 0;
                    $importacaoFinanceiroLancamentoObj->real_valor = 0;
                    $importacaoFinanceiroLancamentoObj->banco = '';
                    $importacaoFinanceiroLancamentoObj->fechamento_tipo = 'cambio_pronto';
                    $importacaoFinanceiroLancamentoObj->cambio_numero_contrato = '';
                    $importacaoFinanceiroLancamentoObj->banco_numero_contrato = '';
                    $importacaoFinanceiroLancamentoObj->modalidade = 'carta_x';
                    $importacaoFinanceiroLancamentoObj->pago = false;
                    $importacaoFinanceiroLancamentoObj->previsto = true;
                    $importacaoFinanceiroLancamentoObj->created_by = Auth::id();   
                    $importacaoFinanceiroLancamentoObj->save();
        
                    $previstos['carta_x'] = [
                        "id" => encrypt($importacaoFinanceiroLancamentoObj->id),
                        "importacao_financeiros_id" => encrypt($importacaoFinanceiroLancamentoObj->importacao_financeiros_id),
                        "cambio_data" => empty($importacaoFinanceiroLancamentoObj->cambio_data)? '' : parserData($importacaoFinanceiroLancamentoObj->cambio_data),
                        "cambio_valor" => empty($importacaoFinanceiroLancamentoObj->cambio_valor)? '' : parserValor($importacaoFinanceiroLancamentoObj->cambio_valor),
                        "real_taxa" => empty($importacaoFinanceiroLancamentoObj->real_taxa)? '' : parserValor4CasasDecimais($importacaoFinanceiroLancamentoObj->real_taxa),
                        "real_valor" => parserValor($importacaoFinanceiroLancamentoObj->real_valor),
                        "banco" => $importacaoFinanceiroLancamentoObj->banco,
                        "fechamento_tipo" => str_replace("_", " ", $importacaoFinanceiroLancamentoObj->fechamento_tipo),
                        "cambio_numero_contrato" => $importacaoFinanceiroLancamentoObj->cambio_numero_contrato,
                        "banco_numero_contrato" => $importacaoFinanceiroLancamentoObj->banco_numero_contrato,
                        "modalidade" => str_replace("_", " ", $importacaoFinanceiroLancamentoObj->modalidade),
                        "baixa_data" => "",
                    ];
                }
                
            }else{
                if(empty($total['preco_contabil'])){
                    foreach($importacaoObj->pedidoComprasItens as $item){       
                        $total['quantidade'] += $item->quantidade;
                        $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
                        $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
                        if(!empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                            $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
                        }
                    }
                }
    
                if($total['preco_contabil'] <= $total['preco_fob']){
                    $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::select();
                    $importacaoFinanceiroLancamentoObj->where('importacao_financeiros_id', $importacaoObj->financeiro->id);
                    $importacaoFinanceiroLancamentoObj->where('modalidade', 'carta_x');
                    $importacaoFinanceiroLancamentoObj->first();

                    if(!empty($importacaoFinanceiroLancamentoObj)){
                        $importacaoFinanceiroLancamentoObj->delete();
                    }
                }
            }
        }else{
            $previstos['antecipado']['cambio_valor'] = "";
        }

        $parcelas = empty($parcelas)? '' : $parcelas;

        $modalidades = [
            'antecipado' => 'Antecipado',
            'a_vista' => 'À Vista',
            'a_prazo' => 'À Prazo',
            'imposto' => 'Imposto',
        ];

        $tipo_fechamento = [
            'cambio_futuro' => 'Câmbio Futuro',
            'cambio_pronto' => 'Câmbio Pronto',
            'carta_credito' => 'Carta Crédito',
            'credito_processo' => 'Crédito de Processos 3º(Crédito/Débito Di)',
            'finimp' => 'Finimp',
            'imposto' => 'Imposto',
        ];

        $total['cambio_valor_total'] = empty($total['cambio_valor_total'])? '' : parserValor($total['cambio_valor_total']);
        $total['real_valor_total'] = empty($total['real_valor_total'])? '' : parserValor($total['real_valor_total']);

        if(empty($total['preco_contabil'])){
            foreach($importacaoObj->pedidoComprasItens as $item){       
                $total['quantidade'] += $item->quantidade;
                $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
                $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
                if(!empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                    $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
                }
            }
        }

        $data_embarque_previsao = empty($importacaoObj->data_embarque_previsao)? '' : parserData($importacaoObj->data_embarque_previsao);
        $data_embarque_realizado = empty($importacaoObj->data_embarque_realizado)? '' : parserData($importacaoObj->data_embarque_realizado);

        $data_chegada_porto_previsao = empty($importacaoObj->data_chegada_porto_previsao)? '' : parserData($importacaoObj->data_chegada_porto_previsao);
        $data_chegada_porto_realizado = empty($importacaoObj->data_chegada_porto_realizado)? '' : parserData($importacaoObj->data_chegada_porto_realizado);

        $data_pagamento_parcela = !empty($data_embarque_realizado)? $data_embarque_realizado : $data_embarque_previsao;
        $data_pagamento_imposto = !empty($data_chegada_porto_realizado)? $data_chegada_porto_realizado : $data_chegada_porto_previsao;

        $data_pagamento_imposto = empty($data_pagamento_imposto)? parserData($importacaoObj->pedidoCompras->previsao_entrega) : $data_pagamento_imposto;

        $previsao_chegada = false;
        $previsao_chegada_porto = false;
        $realizado_chegada_porto = false;
        $data_embarque_realizado = empty($importacaoObj->data_embarque_realizado)? '' : parserData($importacaoObj->data_embarque_realizado);
        if(empty($data_embarque_realizado)){
            $data_embarque_realizado = empty($importacaoObj->data_embarque_previsao)? '' : parserData($importacaoObj->data_embarque_previsao);
        }

        if(!empty($importacaoObj->data_chegada_porto_realizado)){
            $realizado_chegada_porto = true;
        }else if(!empty($importacaoObj->data_chegada_porto_previsao)){
            $previsao_chegada_porto = true;
        }else{
            $previsao_chegada = true;
        }

        return view('programs.importacao_dados_complementares_follow_up.modal.financeiro_lancamento')->with(
            [
                'modalidades' => $modalidades, 
                'tipo_fechamento' => $tipo_fechamento, 
                'id' => $fields['id'], 
                'lancamentos' => $lancamentos, 
                'inicio' => $fields['inicio'], 
                'previstos' => $previstos, 
                'parcelas' => $parcelas, 
                'data_pagamento_imposto' => $data_pagamento_imposto, 
                'icms_saida_previsto' => $icms_saida_previsto, 
                'data_pagamento_parcela' => $data_pagamento_parcela,
                'total' => $total,
                'associacao' => $associacao,
                'valor_total_parcela_fornecedor' => empty($total['preco_fob'])? '' : parserValor($total['preco_fob'] - parserNumber($previstos['antecipado']['cambio_valor'])),
                'preco_fob_total_codigo' => empty($total['preco_fob'])? 0 : $total['preco_fob'],
                'preco_fob_total' => empty($total['preco_fob'])? '' : parserValor($total['preco_fob']),
                'data_embarque_realizado' => $data_embarque_realizado,
                'previsao_chegada' => $previsao_chegada,
                'previsao_chegada_porto' => $previsao_chegada_porto,
                'realizado_chegada_porto' => $realizado_chegada_porto,
                'importacaoObj' => $importacaoObj,
                'frete_fornecedor' => empty($importacaoObj->frete_fornecedor)? '0,00' : parserValor($importacaoObj->frete_fornecedor),
            ]
        );
    }

    public function adicionarFinanceiroLancamento(ImportacaoFinanceiroLancamentoRequest $request){
        $fields = $request->only('id','data_cambio','modalidade','valor_cambio','taxa','valor','banco','tipo_fechamento','numero_contrato_cambio','numero_contrato_banco', 'red_adicionado', 'associacao');
        
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoObj = Importacao::find($id);

        $importacaoFinanceiroObj = ImportacaoFinanceiro::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoFinanceiroObj)){
            $importacaoFinanceiroObj = new ImportacaoFinanceiro;
            $importacaoFinanceiroObj->importacaos_id = $importacaoObj->id;
            $importacaoFinanceiroObj->created_by = Auth::id();
            $importacaoFinanceiroObj->save();
        }

        $cambio_data = Carbon::createFromFormat('d/m/Y', $fields['data_cambio'])->setTime(0,0,0);

        $importacaoFinanceiroLancamentoObj = new ImportacaoFinanceiroLancamento;
        $importacaoFinanceiroLancamentoObj->importacao_financeiros_id = $importacaoFinanceiroObj->id;
        $importacaoFinanceiroLancamentoObj->cambio_data = $cambio_data;
        $importacaoFinanceiroLancamentoObj->cambio_valor = parserNumber($fields['valor_cambio']);
        $importacaoFinanceiroLancamentoObj->real_taxa = parserNumber($fields['taxa']);
        $importacaoFinanceiroLancamentoObj->real_valor = parserNumber($fields['valor']);
        $importacaoFinanceiroLancamentoObj->banco = $fields['banco'];
        $importacaoFinanceiroLancamentoObj->fechamento_tipo = $fields['tipo_fechamento'];
        $importacaoFinanceiroLancamentoObj->cambio_numero_contrato = $fields['numero_contrato_cambio'];
        $importacaoFinanceiroLancamentoObj->banco_numero_contrato = $fields['numero_contrato_banco'];
        $importacaoFinanceiroLancamentoObj->modalidade = $fields['modalidade'];
        $importacaoFinanceiroLancamentoObj->created_by = Auth::id();      
        $importacaoFinanceiroLancamentoObj->save();

        $importacaoFinanceiroLancamentoPrevistoObj = ImportacaoFinanceiroLancamento::find($fields['associacao']);
        $importacaoFinanceiroLancamentoPrevistoObj->pago = true;
        $importacaoFinanceiroLancamentoPrevistoObj->baixa_data = $cambio_data;
        $importacaoFinanceiroLancamentoPrevistoObj->importacao_financeiro_lancamentos_id_baixa = $importacaoFinanceiroLancamentoObj->id;
        $importacaoFinanceiroLancamentoPrevistoObj->updated_by = Auth::id();
        $importacaoFinanceiroLancamentoPrevistoObj->save();

        if(!empty($fields['red_adicionado'])){
            $red_adicionado = decrypt($fields['red_adicionado']);
        }else{
            $red_adicionado = []; 
        }

        foreach($red_adicionado as $index => $red){
            $redsImportacaoFinanceiroLancamento = new RedsImportacaoFinanceiroLancamento;
            $redsImportacaoFinanceiroLancamento->reds_id = $red['id'];
            $redsImportacaoFinanceiroLancamento->importacao_financeiro_lancamentos_id = $importacaoFinanceiroLancamentoObj->id;
            $redsImportacaoFinanceiroLancamento->utilizado_valor = parserNumber($red['valor_utilizado']);
            $redsImportacaoFinanceiroLancamento->created_by = Auth::id();
            $redsImportacaoFinanceiroLancamento->save();

            $redObj = Red::find($red['id']);
            $redObj->saldo = $redObj->saldo - parserNumber($red['valor_utilizado']);
            $redObj->save();
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function getEditarFinanceiroLancamento(Request $request){
        $fields = $request->only(['id']);

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::find($id);

        $importacaoFinanceiroObj = ImportacaoFinanceiro::find($importacaoFinanceiroLancamentoObj->importacao_financeiros_id);
        
        $associacao = [];
        $baixa_id = '';
        
        if(!empty($importacaoFinanceiroObj->lancamentos)){
            foreach($importacaoFinanceiroObj->lancamentos as $lancamento){
                if($lancamento->previsto === true){
                    if(in_array($lancamento->modalidade, ['a_prazo', 'a_vista'])){
                        $index = 'parcela';
                    }else{
                        $index = $lancamento->modalidade;
                    }
                    if($index == 'parcela'){
                        if($lancamento->pago === false || $lancamento->importacao_financeiro_lancamentos_id_baixa == $importacaoFinanceiroLancamentoObj->id){
                            $associacao[] = [
                                'id' => $lancamento->id,
                                'valor' => $lancamento->parcela."º Parcela Fornecedor",
                            ];
                        }
                    }else{
                        if($lancamento->pago === false|| $lancamento->importacao_financeiro_lancamentos_id_baixa == $importacaoFinanceiroLancamentoObj->id){
                            $associacao[] = [
                                'id' => $lancamento->id,
                                'valor' => $lancamento->modalidade === 'antecipado'? 'Antecipação' : ucfirst(str_replace("_", " ", $lancamento->modalidade))
                            ];
                        }
                    }
                    if($lancamento->importacao_financeiro_lancamentos_id_baixa == $importacaoFinanceiroLancamentoObj->id){
                        $baixa_id = $lancamento->id;
                    }
                    
                }
            }
        }

        $reds = [];
        $total_red = [
            'saldo' => 0,
            'valor_utilizado' => 0,
        ];
        if(!empty($importacaoFinanceiroLancamentoObj->detalhesRedsUtilizado)){
            foreach($importacaoFinanceiroLancamentoObj->detalhesRedsUtilizado as $red){
                $reds[$red->detalhesRed->numero_documento] = [
                    'id' => $red->reds_id,
                    'numero_documento' => $red->detalhesRed->numero_documento,
                    'valor_utilizado' => parserValor($red->utilizado_valor),
                    'saldo' => parserValor($red->detalhesRed->saldo),
                ];
                $total_red['saldo'] += $red->detalhesRed->saldo;
                $total_red['valor_utilizado'] += $red->utilizado_valor;
            }
        }
        $reds = empty($reds)? '' : $reds;
        $total_red['saldo'] = empty($total_red['saldo'])? '' : parserValor($total_red['saldo']);
        $total_red['valor_utilizado'] = empty($total_red['valor_utilizado'])? '' : parserValor($total_red['valor_utilizado']);
        
        $lancamento = [
            "id" => encrypt($importacaoFinanceiroLancamentoObj->id),
            "importacao_financeiros_id" => encrypt($importacaoFinanceiroLancamentoObj->importacao_financeiros_id),
            "cambio_data" => parserData($importacaoFinanceiroLancamentoObj->cambio_data),
            "cambio_valor" => parserValor($importacaoFinanceiroLancamentoObj->cambio_valor),
            "real_taxa" => parserValor4CasasDecimais($importacaoFinanceiroLancamentoObj->real_taxa),
            "real_valor" => parserValor($importacaoFinanceiroLancamentoObj->real_valor),
            "banco" => $importacaoFinanceiroLancamentoObj->banco,
            "fechamento_tipo" => $importacaoFinanceiroLancamentoObj->fechamento_tipo,
            "cambio_numero_contrato" => $importacaoFinanceiroLancamentoObj->cambio_numero_contrato,
            "banco_numero_contrato" => $importacaoFinanceiroLancamentoObj->banco_numero_contrato,
            "modalidade" => $importacaoFinanceiroLancamentoObj->modalidade,
            "red_documento" => empty($importacaoFinanceiroLancamentoObj->redDetalhes)? '' : $importacaoFinanceiroLancamentoObj->redDetalhes->numero_documento,
            "red_saldo" => empty($importacaoFinanceiroLancamentoObj->redDetalhes)? '' : parserValor($importacaoFinanceiroLancamentoObj->redDetalhes->saldo + $importacaoFinanceiroLancamentoObj->red_valor),
            "red_taxa_cambio" => empty($importacaoFinanceiroLancamentoObj->redDetalhes)? '' : parserValor($importacaoFinanceiroLancamentoObj->redDetalhes->taxa_cambio),
            "red_valor_utilizado" => empty($importacaoFinanceiroLancamentoObj->redDetalhes)? '' : parserValor($importacaoFinanceiroLancamentoObj->red_valor),
            "associacao" => $associacao,
            "baixa_id" => $baixa_id,
            "red_dados" => empty($reds)? '' : encrypt($reds),
            "red_tabela" => $reds,
            "total_red" => $total_red,
        ];

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $lancamento
        ];

        return response()->json($response);
    }

    public function editarFinanceiroLancamento(ImportacaoFinanceiroLancamentoRequest $request){
        $fields = $request->only('id', 'id_lancamento', 'data_cambio','modalidade','valor_cambio','taxa','valor','banco','tipo_fechamento','numero_contrato_cambio','numero_contrato_banco', 'red_valor_utilizado', 'red_adicionado');

        try{
            $id_lancamento = decrypt($fields['id_lancamento']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $cambio_data = Carbon::createFromFormat('d/m/Y', $fields['data_cambio'])->setTime(0,0,0);

        $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::find($id_lancamento);

        $importacaoFinanceiroLancamentoObj->cambio_data = $cambio_data;
        $importacaoFinanceiroLancamentoObj->cambio_valor = parserNumber($fields['valor_cambio']);
        $importacaoFinanceiroLancamentoObj->real_taxa = parserNumber($fields['taxa']);
        $importacaoFinanceiroLancamentoObj->real_valor = parserNumber($fields['valor']);
        $importacaoFinanceiroLancamentoObj->banco = $fields['banco'];
        $importacaoFinanceiroLancamentoObj->fechamento_tipo = $fields['tipo_fechamento'];
        $importacaoFinanceiroLancamentoObj->cambio_numero_contrato = $fields['numero_contrato_cambio'];
        $importacaoFinanceiroLancamentoObj->banco_numero_contrato = $fields['numero_contrato_banco'];
        $importacaoFinanceiroLancamentoObj->modalidade = $fields['modalidade'];
        $importacaoFinanceiroLancamentoObj->updated_by = Auth::id();
        $importacaoFinanceiroLancamentoObj->pago = true;
        $importacaoFinanceiroLancamentoObj->save();

        if(!empty($fields['red_adicionado'])){
            $red_adicionado = decrypt($fields['red_adicionado']);
        }else{
            $red_adicionado = []; 
        }

        if(!empty($importacaoFinanceiroLancamentoObj->detalhesRedsUtilizado)){
            foreach($importacaoFinanceiroLancamentoObj->detalhesRedsUtilizado as $red){
                $utilizado_valor = $red->utilizado_valor;

                $red->detalhesRed->saldo = $red->detalhesRed->saldo + $utilizado_valor;
                $red->detalhesRed->save();
                
                $red->save();
                $red->delete();
            }
        }

        foreach($red_adicionado as $index => $red){
            $redsImportacaoFinanceiroLancamento = new RedsImportacaoFinanceiroLancamento;
            $redsImportacaoFinanceiroLancamento->reds_id = $red['id'];
            $redsImportacaoFinanceiroLancamento->importacao_financeiro_lancamentos_id = $importacaoFinanceiroLancamentoObj->id;
            $redsImportacaoFinanceiroLancamento->utilizado_valor = parserNumber($red['valor_utilizado']);
            $redsImportacaoFinanceiroLancamento->created_by = Auth::id();
            $redsImportacaoFinanceiroLancamento->save();

            $redObj = Red::find($red['id']);
            $redObj->saldo = $redObj->saldo - parserNumber($red['valor_utilizado']);
            $redObj->save();
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function deletarFinanceiroLancamento(Request $request){
        $fields = $request->only('id');
        
        try{
            $id_lancamento = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::find($id_lancamento);

        if(!empty($importacaoFinanceiroLancamentoObj->detalhesRedsUtilizado)){
            foreach($importacaoFinanceiroLancamentoObj->detalhesRedsUtilizado as $red){
                $utilizado_valor = $red->utilizado_valor;

                $red->detalhesRed->saldo = $red->detalhesRed->saldo + $utilizado_valor;
                $red->detalhesRed->save();
                
                $red->save();
                $red->delete();
            }
        }

        $importacaoFinanceiroLancamentoObj->deleted_by = Auth::id();
        $importacaoFinanceiroLancamentoObj->save();
        $importacaoFinanceiroLancamentoObj->delete();

        $importacaoFinanceiroLancamentoPrevistoObj = ImportacaoFinanceiroLancamento::select()->where('importacao_financeiro_lancamentos_id_baixa', $id_lancamento)->first();
        if(!empty($importacaoFinanceiroLancamentoPrevistoObj)){
            $importacaoFinanceiroLancamentoPrevistoObj->pago = false;
            $importacaoFinanceiroLancamentoPrevistoObj->baixa_data = null;
            $importacaoFinanceiroLancamentoPrevistoObj->importacao_financeiro_lancamentos_id_baixa = null;
            $importacaoFinanceiroLancamentoPrevistoObj->updated_by = Auth::id();
            $importacaoFinanceiroLancamentoPrevistoObj->save();
        }        

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function carregarTabelaFinanceiroLancamento(Request $request){
        $fields = $request->only(['id']);

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $lancamentos = [];
        $previstos = [];
        $total = [
            'cambio_valor' => 0,
            'real_taxa' => 0,
            'real_valor' => 0,
            'cambio_dolar' => 0,
        ];
        $lancamento_imposto = false;
        $lancamento_adiantamento = false;
        $lancamento_cartao_x = false;
        $lancamento_antecipacao_segundaria = false;
        $associacao = [];

        $importacaoObj = Importacao::find($id);
        $quantidade_antecipacao_previsto = 0;
        
        if(!empty($importacaoObj->financeiro)){
            if(!empty($importacaoObj->financeiro->lancamentos)){
                foreach($importacaoObj->financeiro->lancamentos as $lancamento){
                    if($lancamento->previsto === false){
                        $lancamentos[] = [
                            "id" => encrypt($lancamento->id),
                            "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                            "cambio_data" => parserData($lancamento->cambio_data),
                            "cambio_valor" => parserValor($lancamento->cambio_valor),
                            "real_taxa" => parserValor4CasasDecimais($lancamento->real_taxa),
                            "real_valor" => parserValor($lancamento->real_valor),
                            "banco" => $lancamento->banco,
                            "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                            "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                            "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                            "modalidade" => str_replace("_", " ", $lancamento->modalidade),
                            "pago" => $lancamento->pago,
                        ];
    
                        $total['cambio_valor'] += $lancamento->cambio_valor;
                        $total['real_taxa'] += $lancamento->real_taxa;
                        $total['real_valor'] += $lancamento->real_valor;
                        $total['cambio_dolar'] += empty($lancamento->cambio_valor)? 0 : $lancamento->real_valor/$lancamento->cambio_valor;
                    }else if($lancamento->modalidade === 'antecipacao_segundaria'){
                        $index = 'antecipacao_segundaria';
                        $previstos[$index][] = [
                            "id" => encrypt($lancamento->id),
                            "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                            "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                            "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                            "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                            "real_valor" => parserValor($lancamento->real_valor),
                            "banco" => $lancamento->banco,
                            "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                            "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                            "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                            "modalidade" => $lancamento->parcela.'º Antecipação',
                            "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                            "pago" => $lancamento->pago,
                        ];

                        if($lancamento->pago === false){
                            $associacao[] = [
                                'id' => $lancamento->id,
                                'valor' => $lancamento->parcela.'º Antecipação',
                                'cambio_data' => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                'cambio_valor' => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                'real_valor' => empty($lancamento->real_valor)? '' : parserValor($lancamento->real_valor),
                            ];
                        }
                        $quantidade_antecipacao_previsto++;
                        $lancamento_antecipacao_segundaria = true;
                    }else{
                        if($lancamento->cambio_valor > 0 || $lancamento->real_valor > 0){
                            if(in_array($lancamento->modalidade, ['a_prazo', 'a_vista'])){
                                $index = 'parcela';
                            }else{
                                $index = $lancamento->modalidade;
                                if($lancamento->modalidade === 'antecipado'){
                                    $quantidade_antecipacao_previsto++;
                                }
                            }
                            if($index == 'parcela'){
                                $previstos[$index][] = [
                                    "id" => encrypt($lancamento->id),
                                    "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                                    "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                    "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                    "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                                    "real_valor" => empty($lancamento->real_valor)? '' : parserValor($lancamento->real_valor),
                                    "banco" => $lancamento->banco,
                                    "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                                    "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                                    "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                                    "modalidade" => $lancamento->parcela."º Parcela Fornecedor",
                                    "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                                    "pago" => $lancamento->pago,
                                ];
    
                                if($lancamento->pago === false){
                                    $associacao[] = [
                                        'id' => $lancamento->id,
                                        'valor' => $lancamento->parcela."º Parcela Fornecedor",
                                        'cambio_data' => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                        'cambio_valor' => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                        'real_valor' => empty($lancamento->real_valor)? '' : parserValor($lancamento->real_valor),
                                    ];
                                }
                            }else{
                                $previstos[$index] = [
                                    "id" => encrypt($lancamento->id),
                                    "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                                    "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                    "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                    "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                                    "real_valor" => parserValor($lancamento->real_valor),
                                    "banco" => $lancamento->banco,
                                    "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                                    "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                                    "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                                    "modalidade" => $lancamento->modalidade === 'antecipado'? 'Antecipação' : str_replace("_", " ", $lancamento->modalidade),
                                    "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                                    "pago" => $lancamento->pago,
                                ];
    
                                if($lancamento->pago === false){
                                    $associacao[] = [
                                        'id' => $lancamento->id,
                                        'valor' => $lancamento->modalidade === 'antecipado'? 'Antecipação' : ucfirst(str_replace("_", " ", $lancamento->modalidade)),
                                        'cambio_data' => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                        'cambio_valor' => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                        'real_valor' => empty($lancamento->real_valor)? '' : parserValor($lancamento->real_valor),
                                    ];
                                }                         
                            } 
                            
                            if($lancamento->modalidade === 'imposto'){
                                $lancamento_imposto = true;
                            }else if($lancamento->modalidade === 'antecipado'){
                                $lancamento_adiantamento = true;
                            }else if($lancamento->modalidade === 'carta_x'){
                                $lancamento_cartao_x = true;
                            }else if($lancamento->modalidade === 'antecipacao_segundaria'){
                                $lancamento_antecipacao_segundaria = true;
                            }
                        }
                    }
                }
            }
        }

        $total['cambio_valor'] = empty($total['cambio_valor'])? '' : parserValor($total['cambio_valor']);
        $total['real_taxa'] = empty($total['real_taxa'])? '' : parserValor4CasasDecimais($total['real_taxa']);
        $total['real_valor'] = empty($total['real_valor'])? '' : parserValor($total['real_valor']);
        $total['cambio_dolar'] = empty($total['cambio_dolar'])? '' : parserValor($total['cambio_dolar']);;

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'lancamentos' => $lancamentos,
                'total' => $total,
                'previstos' => $previstos,
                'lancamento_imposto' => $lancamento_imposto,
                'lancamento_adiantamento' => $lancamento_adiantamento,
                'lancamento_cartao_x' => $lancamento_cartao_x,
                'associacao' => $associacao,
                'lancamento_antecipacao_segundaria' => $lancamento_antecipacao_segundaria,
                'quantidade_antecipacao_previsto' => $quantidade_antecipacao_previsto,
            ]
        ];

        return response()->json($response);
    }

    public function modalDetalhes(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $importacaoObj = Importacao::find($id);

        if(!empty($importacaoValorPadraoObj)){
            if(empty($importacaoObj->importacao_valor_padraos_id)){
                $importacaoObj->importacao_valor_padraos_id = $importacaoValorPadraoObj->id;
                $importacaoObj->save();

                $importacaoCustoObj = ImportacaoCusto::where('importacaos_id', $importacaoObj->id)->first();
                if(empty($importacaoCustoObj)){
                    $importacaoCustoObj = new ImportacaoCusto;
                    $importacaoCustoObj->importacaos_id = $importacaoObj->id;
                }
                
                if(empty($importacaoCustoObj->pis)){
                    $importacaoCustoObj->pis = $importacaoValorPadraoObj->pis;
                }
                if(empty($importacaoCustoObj->cofins)){
                    $importacaoCustoObj->cofins = $importacaoValorPadraoObj->cofins;
                }
                if(empty($importacaoCustoObj->taxa_siscomex)){
                    $importacaoCustoObj->taxa_siscomex = $importacaoValorPadraoObj->taxa_siscomex;
                }
                if(empty($importacaoCustoObj->sda)){
                    $importacaoCustoObj->sda = $importacaoValorPadraoObj->sda;
                }
                if(empty($importacaoCustoObj->honorarios)){
                    $importacaoCustoObj->honorarios = $importacaoValorPadraoObj->honorarios;
                }
                if(empty($importacaoCustoObj->expediente)){
                    $importacaoCustoObj->expediente = $importacaoValorPadraoObj->expediente;
                }
                if(empty($importacaoCustoObj->armazem)){
                    $importacaoCustoObj->armazem = $importacaoValorPadraoObj->armazenagem;
                }
                if(empty($importacaoCustoObj->laudo)){
                    $importacaoCustoObj->laudo = $importacaoValorPadraoObj->laudo;
                }

                $importacaoCustoObj->save();
            }
        }

        $itens = [];
        $index = 0;
        $total = [
            'quantidade' => 0,
            'quantidade_realizada' => 0,
            'preco_fob' => 0,
            'preco_contabil' => 0,
            'diferenca_preco' => 0,
        ];
        if(count($importacaoObj->itens) == 0){
            foreach($importacaoObj->pedidoComprasItens as $item){
                $importacaoItemObj = new ImportacaoItem;
                $importacaoItemObj->importacaos_id = $importacaoObj->id;
                $importacaoItemObj->produto_codigo = $item->cod_produto;
                $importacaoItemObj->valor_contabil_unitario = 0;
                $importacaoItemObj->estabelecimento_codigo = $importacaoObj->estabelecimento_codigo;
                $importacaoItemObj->numero_proforma = $importacaoObj->numero_proforma;
                $importacaoItemObj->pedido_compras = $importacaoObj->pedido_compras;
                $importacaoItemObj->nota_uuid_nasajon = $item->id_nota;
                $importacaoItemObj->save();
            }
        }

        foreach($importacaoObj->pedidoComprasItens as $item){       
            $index++;
            if(isset($item->produto->foto) && Storage::exists('public/produto_fotos/' . $item->produto->foto->filename) && Storage::exists('public/produto_fotos/' . $item->produto->foto->thumb_filename)){
                $foto= "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url('public/produto_fotos/' . $item->produto->foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $item->produto->foto->filename) . "\" class=\"btn-foto-estoque thumb ml-2 mt-1\"></a>"; 
            }
            else{
                $foto = "";
            }
            
            $itens[] = [
                'item' => $index,
                'codigo' => $item->cod_produto,
                'produto' => empty($item->produto)? '' : $item->produto->descricao,
                'composicao' => $item->produtoNasajon->composicao,
                'gramatura_gm2' => empty($item->produto->produtoGrupo)? '' : $item->produto->produtoGrupo->gramatura_gm2,
                'largura' => empty($item->produto->produtoGrupo)? '' : $item->produto->produtoGrupo->largura,
                'gramatura_gml' => '',
                'rendimento' => '',
                'instrucao_lavagem' => '',
                'quantidade' => parserValor4CasasDecimais($item->quantidade),
                'quantidade_realizada' => $item->situacao_item == 'Cancelado'? '' : parserValor4CasasDecimais($item->quantidade - $item->quantidade_restante),
                'preco_unitario' => parserValor4CasasDecimais($item->preco_compra_unitario),
                'preco_total' => parserValor4CasasDecimais($item->quantidade * $item->preco_compra_unitario),
                'foto' => $foto,
                'status' => $item->situacao_item,
                'valor_contabil_unitario' => empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? '' : parserValor4CasasDecimais($item->produtoDetalhesImportacao->valor_contabil_unitario),
                'valor_contabil_total' =>  empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? '' : parserValor4CasasDecimais($item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade),
            ];

            $total['quantidade'] += $item->quantidade;
            $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
            $total['preco_fob'] += $item->quantidade * $item->preco_compra_unitario;
            $total['preco_contabil'] += empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
        }

        $amostra_embarque_aprovacao = '';
        if(!empty($importacaoObj->amostra_embarque_aprovacao)){
            $amostra_embarque_aprovacao = $importacaoObj->amostra_embarque_aprovacao? 'Não' : 'Sim';
        }

        $aprovado_embarque_produto_codigo = '';
        $aprovado_embarque_produto = '';
        if(!empty($importacaoObj->aprovadoEmbarqueProdutoDetalhes)){
            $aprovado_embarque_produto_codigo = $importacaoObj->aprovadoEmbarqueProdutoDetalhes->codigo_produto;
            $aprovado_embarque_produto = $importacaoObj->aprovadoEmbarqueProdutoDetalhes->descricao;
        }

        $total['quantidade'] = empty($total['quantidade'])? '' : parserValor($total['quantidade']);
        $total['quantidade_realizada'] = empty($total['quantidade_realizada'])? '' : parserValor($total['quantidade_realizada']);
        $total['preco_fob'] = empty($total['preco_fob'])? '' : parserValor($total['preco_fob']);
        $total['preco_contabil'] = empty($total['preco_contabil'])? '' : parserValor($total['preco_contabil']);

        $dados_follow = [];
        $tipo = $this->dadosTipoCores();
        $tipo_aprovacoes_simples = $this->dadosSimplesAprovacao();
        $tipo_aprovacoes_parcial = $this->dadosAprovacaoParcial();
        if(empty($importacaoObj->followUpDetalhes)){
            $importacaoFollowUpObj = new ImportacaoFollowUp;
            $importacaoFollowUpObj->importacaos_id = $id;
            $importacaoFollowUpObj->created_by = Auth::id();
            $importacaoFollowUpObj->save();
        }else{
            foreach($importacaoObj->followUpDetalhesItens as $importacao_follow_up){
                $dados_follow[] = [
                    'tipo_cor' => empty($importacao_follow_up->tipo_cor)? '' : $tipo[$importacao_follow_up->tipo_cor],
                    'envio_cor_data_previsao' => empty($importacao_follow_up->envio_cor_data_previsao)? '' : $importacao_follow_up->envio_cor_data_previsao->format('d/m/Y'), 
                    'envio_cor_data_envio' => empty($importacao_follow_up->envio_cor_data_envio)? '' : $importacao_follow_up->envio_cor_data_envio->format('d/m/Y'), 
                    'envio_cor_data_recebido' => empty($importacao_follow_up->envio_cor_data_recebido)? '' : $importacao_follow_up->envio_cor_data_recebido->format('d/m/Y'), 
                    'quality_sample_aprovacao' => empty($importacao_follow_up->quality_sample_aprovacao)? '' : $tipo_aprovacoes_simples[$importacao_follow_up->quality_sample_aprovacao], 
                    'quality_sample_data_previsao_envio' => empty($importacao_follow_up->quality_sample_data_previsao_envio)? '' : $importacao_follow_up->quality_sample_data_previsao_envio->format('d/m/Y'), 
                    'quality_sample_data_envio' => empty($importacao_follow_up->quality_sample_data_envio)? '' : $importacao_follow_up->quality_sample_data_envio->format('d/m/Y'), 
                    'quality_sample_data_recebido' => empty($importacao_follow_up->quality_sample_data_recebido)? '' : $importacao_follow_up->quality_sample_data_recebido->format('d/m/Y'), 
                    'quality_sample_data_previsao_aprovacao' => empty($importacao_follow_up->quality_sample_data_previsao_aprovacao)? '' : $importacao_follow_up->quality_sample_data_previsao_aprovacao->format('d/m/Y'), 
                    'quality_sample_data_aprovacao' => empty($importacao_follow_up->quality_sample_data_aprovacao)? '' : $importacao_follow_up->quality_sample_data_aprovacao->format('d/m/Y'), 
                    'laboratorio_aprovacao' => empty($importacao_follow_up->laboratorio_aprovacao)? '' : $tipo_aprovacoes_parcial[$importacao_follow_up->laboratorio_aprovacao], 
                    'laboratorio_data_previsao_envio' => empty($importacao_follow_up->laboratorio_data_previsao_envio)? '' : $importacao_follow_up->laboratorio_data_previsao_envio->format('d/m/Y'), 
                    'laboratorio_data_envio' => empty($importacao_follow_up->laboratorio_data_envio)? '' : $importacao_follow_up->laboratorio_data_envio->format('d/m/Y'), 
                    'laboratorio_data_recebido' => empty($importacao_follow_up->laboratorio_data_recebido)? '' : $importacao_follow_up->laboratorio_data_recebido->format('d/m/Y'), 
                    'laboratorio_data_aprovacao' => empty($importacao_follow_up->laboratorio_data_aprovacao)? '' : $importacao_follow_up->laboratorio_data_aprovacao->format('d/m/Y'), 
                    'tempo_producao_previsao_termino' => empty($importacao_follow_up->tempo_producao_previsao_termino)? '' : $importacao_follow_up->tempo_producao_previsao_termino->format('d/m/Y'), 
                    'tempo_producao_termino' => empty($importacao_follow_up->tempo_producao_termino)? '' : $importacao_follow_up->tempo_producao_termino->format('d/m/Y'), 
                    'amostra_embarque_aprovacao' => empty($importacao_follow_up->amostra_embarque_aprovacao)? '' : $tipo_aprovacoes_parcial[$importacao_follow_up->amostra_embarque_aprovacao], 
                    'amostra_embarque_data_previsao_envio' => empty($importacao_follow_up->amostra_embarque_data_previsao_envio)? '' : $importacao_follow_up->amostra_embarque_data_previsao_envio->format('d/m/Y'), 
                    'amostra_embarque_data_envio' => empty($importacao_follow_up->amostra_embarque_data_envio)? '' : $importacao_follow_up->amostra_embarque_data_envio->format('d/m/Y'), 
                    'amostra_embarque_data_recebido' => empty($importacao_follow_up->amostra_embarque_data_recebido)? '' : $importacao_follow_up->amostra_embarque_data_recebido->format('d/m/Y'), 
                    'amostra_embarque_data_previsao_aprovacao' => empty($importacao_follow_up->amostra_embarque_data_previsao_aprovacao)? '' : $importacao_follow_up->amostra_embarque_data_previsao_aprovacao->format('d/m/Y'), 
                    'amostra_embarque_data_aprovacao' => empty($importacao_follow_up->amostra_embarque_data_aprovacao)? '' : $importacao_follow_up->amostra_embarque_data_aprovacao->format('d/m/Y'), 
                    'autorizacao_embarque_data_previsao_envio' => empty($importacao_follow_up->autorizacao_embarque_data_previsao_envio)? '' : $importacao_follow_up->autorizacao_embarque_data_previsao_envio->format('d/m/Y'), 
                    'autorizacao_embarque_data_envio' => empty($importacao_follow_up->autorizacao_embarque_data_envio)? '' : $importacao_follow_up->autorizacao_embarque_data_envio->format('d/m/Y'), 
                    'envio_cor_data_revisao' => empty($importacao_follow_up->envio_cor_data_revisao)? '' : $importacao_follow_up->envio_cor_data_revisao->format('d/m/Y'),
                    'quality_sample_transportadora' => empty($importacao_follow_up->quality_sample_transportadora)? '' : $importacao_follow_up->quality_sample_transportadora,
                    'quality_sample_awb' => empty($importacao_follow_up->quality_sample_awb)? '' : $importacao_follow_up->quality_sample_awb,
                    'amostra_embarque_transportadora' => empty($importacao_follow_up->amostra_embarque_transportadora)? '' : $importacao_follow_up->amostra_embarque_transportadora,
                    'amostra_embarque_awb' => empty($importacao_follow_up->amostra_embarque_awb)? '' : $importacao_follow_up->amostra_embarque_awb,
                    'produto' => $importacao_follow_up->produto_codigo.' - '.$importacao_follow_up->detalhesProduto->descricao,
                ];
            }
        }

        $dados = [
            'id' => encrypt($id),
            'fornecedor' => $importacaoObj->fornecedor->nome.' - '.$importacaoObj->fornecedor->cnpj_cpf,
            'proforma' => $importacaoObj->numero_proforma,
            'pcmn' => $importacaoObj->pedido_compras,
            'referencia' => $importacaoObj->referencia,
            'data_proforma' => parserData($importacaoObj->data_proforma),
            'data_previsao_carta_programa' => empty($importacaoObj->data_previsao_carta_programa)? '' : parserData($importacaoObj->data_previsao_carta_programa),
            'id_nota' => encrypt($importacaoObj->pedidoCompras->id_nota),
            'estabelecimento_codigo' => $importacaoObj->estabelecimento_codigo,
            'data_previsao_recebimento' => parserData($importacaoObj->pedidoCompras->previsao_entrega),
            'respresentante' => empty($importacaoObj->respresentante)? '' : $importacaoObj->respresentante->nome.' - '.$importacaoObj->respresentante->cnpj_cpf,
            'itens' => $itens,
            'data_carga_pronta_previsao' => empty($importacaoObj->data_carga_pronta_previsao)? '' : parserData($importacaoObj->data_carga_pronta_previsao),
            'data_carga_pronta_realizado' => empty($importacaoObj->data_carga_pronta_realizado)? '' : parserData($importacaoObj->data_carga_pronta_realizado),
            'data_embarque_previsao' => empty($importacaoObj->data_embarque_previsao)? '' : parserData($importacaoObj->data_embarque_previsao),
            'data_embarque_realizado' => empty($importacaoObj->data_embarque_realizado)? '' : parserData($importacaoObj->data_embarque_realizado),
            'data_chegada_porto_previsao' => empty($importacaoObj->data_chegada_porto_previsao)? '' : parserData($importacaoObj->data_chegada_porto_previsao),
            'data_chegada_porto_realizado' => empty($importacaoObj->data_chegada_porto_realizado)? '' : parserData($importacaoObj->data_chegada_porto_realizado),
            'data_di_previsao' => empty($importacaoObj->data_di_previsao)? '' : parserData($importacaoObj->data_di_previsao),
            'data_di_realizado' => empty($importacaoObj->data_di_realizado)? '' : parserData($importacaoObj->data_di_realizado),
            'data_devolucao_cntr_previsao' => empty($importacaoObj->data_devolucao_cntr_previsao)? '' : parserData($importacaoObj->data_devolucao_cntr_previsao),
            'data_devolucao_cntr_realizado' => empty($importacaoObj->data_devolucao_cntr_realizado)? '' : parserData($importacaoObj->data_devolucao_cntr_realizado),
            'data_quality_sample_enviado' => empty($importacaoObj->data_quality_sample_enviado)? '' : parserData($importacaoObj->data_quality_sample_enviado),
            'data_quality_sample_recebido' => empty($importacaoObj->data_quality_sample_recebido)? '' : parserData($importacaoObj->data_quality_sample_recebido),
            'data_handlooms_enviado' => empty($importacaoObj->data_handlooms_enviado)? '' : parserData($importacaoObj->data_handlooms_enviado),
            'data_handlooms_recebido' => empty($importacaoObj->data_handlooms_recebido)? '' : parserData($importacaoObj->data_handlooms_recebido),
            'data_strike_off_enviado' => empty($importacaoObj->data_strike_off_enviado)? '' : parserData($importacaoObj->data_strike_off_enviado),
            'data_strike_off_recebido' => empty($importacaoObj->data_strike_off_recebido)? '' : parserData($importacaoObj->data_strike_off_recebido),
            'data_amostra_embarque_enviado' => empty($importacaoObj->data_amostra_embarque_enviado)? '' : parserData($importacaoObj->data_amostra_embarque_enviado),
            'data_amostra_embarque_recebido' => empty($importacaoObj->data_amostra_embarque_recebido)? '' : parserData($importacaoObj->data_amostra_embarque_recebido),
            'amostra_embarque_aprovacao' => $amostra_embarque_aprovacao,
            'aprovado_embarque_produto_codigo' => $aprovado_embarque_produto_codigo,
            'aprovado_embarque_produto' => $aprovado_embarque_produto,
            'tempo_producao_termino' => empty($importacaoObj->tempo_producao_termino)? '' : $importacaoObj->tempo_producao_termino,
            'total' => $total,
            'dados_follow' => $dados_follow,
        ];

        return view('programs.importacao_dados_complementares_follow_up.modal.detalhes')->with(['dados' => $dados]);
    }

    public function dadosTipoCores(){
        $tipos_cores = [
            'cores_unicas' => 'CORES UNICAS', 
            'estampado' => 'ESTAMPADO', 
            'fio_tinto' => 'FIO TINTO'
        ];

        return $tipos_cores;
    }

    public function dadosSimplesAprovacao(){
        $aprovacoes = [
            'aprovado' => 'Aprovado', 
            'reprovado' => 'Reprovado'
        ];

        return $aprovacoes;
    }

    public function dadosAprovacaoParcial(){
        $aprovacoes = [
            'aprovado_total' => 'Aprovado Total', 
            'aprovado_parcial' => 'Aprovado Parcial', 
            'reprovado' => 'Reprovado'
        ];

        return $aprovacoes;
    }

    public function modalHistoricoAprovacao(Request $request){
        $fields = $request->only('tipo', 'id_follow_up', 'produto_codigo');

        try{
            $id_follow_up = decrypt($fields['id_follow_up']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query = ImportacaoFollowUpHistoricoAprovacao::select();
        $query->where('importacao_follow_up_id', $id_follow_up);
        $query->where('tipo', $fields['tipo']);
        if(empty($fields['produto_codigo'])){
            $query->whereNull('produto_codigo');
        }else{
            $query->where(function ($query) use($fields){
                $query->where('produto_codigo', $fields['produto_codigo']);
                $query->orWhereNull('produto_codigo');
            });
        }
        $result = $query->get();

        $retorno = [];
        foreach($result as $historico){
            if($historico->tipo == 'quality_sample'){
                $status_aprovacaos = $this->dadosSimplesAprovacao(); 
            }else{
                $status_aprovacaos = $this->dadosAprovacaoParcial(); 
            }
            
            $retorno[] = [
                'data' => $historico->data->format('d/m/Y'),
                'status' => $status_aprovacaos[$historico->aprovado],
            ];
        }

        return view('programs.importacao_dados_complementares_follow_up.modal.historico_aprovacao')->with(['historicos' => $retorno]);
    }

    public function carregarDados(Request $request){
        $fields = $request->only('id','produto_codigo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoFollowUpObj = ImportacaoFollowUp::select();
        if(empty($fields['produto_codigo'])){
            $importacaoFollowUpObj->whereNull('produto_codigo');
        }else{
            $importacaoFollowUpObj->where('produto_codigo', $fields['produto_codigo']);
        }
        $importacaoFollowUpObj->where('importacaos_id', $id);
        $importacaoFollowUpObj = $importacaoFollowUpObj->first();

        $dados_follow['tipo_cor'] = empty($importacaoFollowUpObj->tipo_cor)? '' : $importacaoFollowUpObj->tipo_cor; 
        $dados_follow['envio_cor_data_previsao'] = empty($importacaoFollowUpObj->envio_cor_data_previsao)? '' : $importacaoFollowUpObj->envio_cor_data_previsao->format('d/m/Y'); 
        $dados_follow['envio_cor_data_envio'] = empty($importacaoFollowUpObj->envio_cor_data_envio)? '' : $importacaoFollowUpObj->envio_cor_data_envio->format('d/m/Y'); 
        $dados_follow['envio_cor_data_recebido'] = empty($importacaoFollowUpObj->envio_cor_data_recebido)? '' : $importacaoFollowUpObj->envio_cor_data_recebido->format('d/m/Y'); 
        $dados_follow['quality_sample_aprovacao'] = empty($importacaoFollowUpObj->quality_sample_aprovacao)? '' : $importacaoFollowUpObj->quality_sample_aprovacao; 
        $dados_follow['quality_sample_data_previsao_envio'] = empty($importacaoFollowUpObj->quality_sample_data_previsao_envio)? '' : $importacaoFollowUpObj->quality_sample_data_previsao_envio->format('d/m/Y'); 
        $dados_follow['quality_sample_data_envio'] = empty($importacaoFollowUpObj->quality_sample_data_envio)? '' : $importacaoFollowUpObj->quality_sample_data_envio->format('d/m/Y'); 
        $dados_follow['quality_sample_data_recebido'] = empty($importacaoFollowUpObj->quality_sample_data_recebido)? '' : $importacaoFollowUpObj->quality_sample_data_recebido->format('d/m/Y'); 
        $dados_follow['quality_sample_data_previsao_aprovacao'] = empty($importacaoFollowUpObj->quality_sample_data_previsao_aprovacao)? '' : $importacaoFollowUpObj->quality_sample_data_previsao_aprovacao->format('d/m/Y'); 
        $dados_follow['quality_sample_data_aprovacao'] = empty($importacaoFollowUpObj->quality_sample_data_aprovacao)? '' : $importacaoFollowUpObj->quality_sample_data_aprovacao->format('d/m/Y'); 
        $dados_follow['laboratorio_aprovacao'] = empty($importacaoFollowUpObj->laboratorio_aprovacao)? '' : $importacaoFollowUpObj->laboratorio_aprovacao; 
        $dados_follow['laboratorio_data_previsao_envio'] = empty($importacaoFollowUpObj->laboratorio_data_previsao_envio)? '' : $importacaoFollowUpObj->laboratorio_data_previsao_envio->format('d/m/Y'); 
        $dados_follow['laboratorio_data_envio'] = empty($importacaoFollowUpObj->laboratorio_data_envio)? '' : $importacaoFollowUpObj->laboratorio_data_envio->format('d/m/Y'); 
        $dados_follow['laboratorio_data_recebido'] = empty($importacaoFollowUpObj->laboratorio_data_recebido)? '' : $importacaoFollowUpObj->laboratorio_data_recebido->format('d/m/Y'); 
        $dados_follow['laboratorio_data_aprovacao'] = empty($importacaoFollowUpObj->laboratorio_data_aprovacao)? '' : $importacaoFollowUpObj->laboratorio_data_aprovacao->format('d/m/Y'); 
        $dados_follow['tempo_producao_previsao_termino'] = empty($importacaoFollowUpObj->tempo_producao_previsao_termino)? '' : $importacaoFollowUpObj->tempo_producao_previsao_termino->format('d/m/Y'); 
        $dados_follow['tempo_producao_termino'] = empty($importacaoFollowUpObj->tempo_producao_termino)? '' : $importacaoFollowUpObj->tempo_producao_termino->format('d/m/Y'); 
        $dados_follow['amostra_embarque_aprovacao'] = empty($importacaoFollowUpObj->amostra_embarque_aprovacao)? '' : $importacaoFollowUpObj->amostra_embarque_aprovacao; 
        $dados_follow['amostra_embarque_data_previsao_envio'] = empty($importacaoFollowUpObj->amostra_embarque_data_previsao_envio)? '' : $importacaoFollowUpObj->amostra_embarque_data_previsao_envio->format('d/m/Y'); 
        $dados_follow['amostra_embarque_data_envio'] = empty($importacaoFollowUpObj->amostra_embarque_data_envio)? '' : $importacaoFollowUpObj->amostra_embarque_data_envio->format('d/m/Y'); 
        $dados_follow['amostra_embarque_data_recebido'] = empty($importacaoFollowUpObj->amostra_embarque_data_recebido)? '' : $importacaoFollowUpObj->amostra_embarque_data_recebido->format('d/m/Y'); 
        $dados_follow['amostra_embarque_data_previsao_aprovacao'] = empty($importacaoFollowUpObj->amostra_embarque_data_previsao_aprovacao)? '' : $importacaoFollowUpObj->amostra_embarque_data_previsao_aprovacao->format('d/m/Y'); 
        $dados_follow['amostra_embarque_data_aprovacao'] = empty($importacaoFollowUpObj->amostra_embarque_data_aprovacao)? '' : $importacaoFollowUpObj->amostra_embarque_data_aprovacao->format('d/m/Y'); 
        $dados_follow['autorizacao_embarque_data_previsao_envio'] = empty($importacaoFollowUpObj->autorizacao_embarque_data_previsao_envio)? '' : $importacaoFollowUpObj->autorizacao_embarque_data_previsao_envio->format('d/m/Y'); 
        $dados_follow['autorizacao_embarque_data_envio'] = empty($importacaoFollowUpObj->autorizacao_embarque_data_envio)? '' : $importacaoFollowUpObj->autorizacao_embarque_data_envio->format('d/m/Y'); 
        $dados_follow['envio_cor_data_revisao'] = empty($importacaoFollowUpObj->envio_cor_data_revisao)? '' : $importacaoFollowUpObj->envio_cor_data_revisao->format('d/m/Y');
        $dados_follow['quality_sample_transportadora'] = empty($importacaoFollowUpObj->quality_sample_transportadora)? '' : $importacaoFollowUpObj->quality_sample_transportadora;
        $dados_follow['quality_sample_awb'] = empty($importacaoFollowUpObj->quality_sample_awb)? '' : $importacaoFollowUpObj->quality_sample_awb;
        $dados_follow['amostra_embarque_transportadora'] = empty($importacaoFollowUpObj->amostra_embarque_transportadora)? '' : $importacaoFollowUpObj->amostra_embarque_transportadora;
        $dados_follow['amostra_embarque_awb'] = empty($importacaoFollowUpObj->amostra_embarque_awb)? '' : $importacaoFollowUpObj->amostra_embarque_awb; 

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' =>  $dados_follow,
        ];

        return response()->json($response);
    }

    public function salvarMudanca(Request $request){
        $fields = $request->only('id', 'campo', 'valor', 'tipo', 'produto_codigo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        if($fields['tipo'] === 'data'){
            if(!empty($fields['valor'])){
                $data_valor = Carbon::createFromFormat('d/m/Y', $fields['valor'])->setTime(0,0,0);
            }else{
                $data_valor = null;
            }
        }

        $importacaoFollowUpObj = ImportacaoFollowUp::select();
        $importacaoFollowUpObj->where('importacaos_id', $id);
        if(empty($fields['produto_codigo'])){
            $importacaoFollowUpUnicoObj = ImportacaoFollowUp::select();
            $importacaoFollowUpUnicoObj->where('importacaos_id', $id);
            $importacaoFollowUpUnicoObj = $importacaoFollowUpUnicoObj->first();
            if($fields['campo'] == 'envio_das_cores'){
                $importacaoFollowUpObj->update(['tipo_cor' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'envio_cor_data_previsao'){
                $importacaoFollowUpObj->update(['envio_cor_data_previsao' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'envio_cor_data_envio'){
                $importacaoFollowUpObj->update(['envio_cor_data_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'envio_cor_data_recebido'){
                $importacaoFollowUpObj->update(['envio_cor_data_recebido' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_aprovacao'){
                $importacaoFollowUpObj->update(['quality_sample_aprovacao' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_data_previsao_envio'){
                $importacaoFollowUpObj->update(['quality_sample_data_previsao_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_data_envio'){
                $importacaoFollowUpObj->update(['quality_sample_data_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_data_recebido'){
                $importacaoFollowUpObj->update(['quality_sample_data_recebido' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_data_previsao_aprovacao'){
                $importacaoFollowUpObj->update(['quality_sample_data_previsao_aprovacao' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_data_aprovacao'){
                $importacaoFollowUpObj->update(['quality_sample_data_aprovacao' => $data_valor, 'updated_by' => Auth::id()]);
                if(!empty($importacaoFollowUpUnicoObj->quality_sample_aprovacao) && !empty($data_valor)){
                    $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
                    $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpUnicoObj->id;
                    $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'quality_sample';
                    $importacaoFollowUpHistoricoAprovacaoObj->aprovado = $importacaoFollowUpUnicoObj->quality_sample_aprovacao;
                    $importacaoFollowUpHistoricoAprovacaoObj->data = $data_valor;
                    $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
                    $importacaoFollowUpHistoricoAprovacaoObj->save();
                }
            }else if($fields['campo'] == 'laboratorio_aprovacao'){
                $importacaoFollowUpObj->update(['laboratorio_aprovacao' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'laboratorio_data_envio'){
                $importacaoFollowUpObj->update(['laboratorio_data_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'laboratorio_data_recebido'){
                $importacaoFollowUpObj->update(['laboratorio_data_recebido' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'laboratorio_data_aprovacao'){
                $importacaoFollowUpObj->update(['laboratorio_data_aprovacao' => $data_valor, 'updated_by' => Auth::id()]);
                if(!empty($importacaoFollowUpUnicoObj->laboratorio_aprovacao) && !empty($data_valor)){
                    $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
                    $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpUnicoObj->id;
                    $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'laboratorio';
                    $importacaoFollowUpHistoricoAprovacaoObj->aprovado = $importacaoFollowUpUnicoObj->laboratorio_aprovacao;
                    $importacaoFollowUpHistoricoAprovacaoObj->data = $data_valor;
                    $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
                    $importacaoFollowUpHistoricoAprovacaoObj->save();
                }
            }else if($fields['campo'] == 'tempo_producao_previsao_termino'){
                $importacaoFollowUpObj->update(['tempo_producao_previsao_termino' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'tempo_producao_termino'){
                $importacaoFollowUpObj->update(['tempo_producao_termino' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_aprovacao'){
                $importacaoFollowUpObj->update(['amostra_embarque_aprovacao' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_data_previsao_envio'){
                $importacaoFollowUpObj->update(['amostra_embarque_data_previsao_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_data_envio'){
                $importacaoFollowUpObj->update(['amostra_embarque_data_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_enviado'){
                $importacaoFollowUpObj->update(['amostra_embarque_data_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_data_recebido'){
                $importacaoFollowUpObj->update(['amostra_embarque_data_recebido' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_data_previsao_aprovacao'){
                $importacaoFollowUpObj->update(['amostra_embarque_data_previsao_aprovacao' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_data_aprovacao'){
                $importacaoFollowUpObj->update(['amostra_embarque_data_aprovacao' => $data_valor, 'updated_by' => Auth::id()]);
                if(!empty($importacaoFollowUpUnicoObj->amostra_embarque_aprovacao) && !empty($data_valor)){
                    $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
                    $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpUnicoObj->id;
                    $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'amostra_embarque';
                    $importacaoFollowUpHistoricoAprovacaoObj->aprovado = $importacaoFollowUpUnicoObj->amostra_embarque_aprovacao;
                    $importacaoFollowUpHistoricoAprovacaoObj->data = $data_valor;
                    $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
                    $importacaoFollowUpHistoricoAprovacaoObj->save();
                }
            }else if($fields['campo'] == 'autorizacao_embarque_data_previsao_envio'){
                $importacaoFollowUpObj->update(['autorizacao_embarque_data_previsao_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'autorizacao_embarque_data_envio'){
                $importacaoFollowUpObj->update(['autorizacao_embarque_data_envio' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'envio_cor_data_revisao'){
                $importacaoFollowUpObj->update(['envio_cor_data_revisao' => $data_valor, 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_transportadora'){
                $importacaoFollowUpObj->update(['quality_sample_transportadora' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'quality_sample_awb'){
                $importacaoFollowUpObj->update(['quality_sample_awb' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_transportadora'){
                $importacaoFollowUpObj->update(['amostra_embarque_transportadora' => $fields['valor'], 'updated_by' => Auth::id()]);
            }else if($fields['campo'] == 'amostra_embarque_awb'){
                $importacaoFollowUpObj->update(['amostra_embarque_awb' => $fields['valor'], 'updated_by' => Auth::id()]);
            }
        }else{
            $importacaoFollowUpObj->where('produto_codigo', $fields['produto_codigo']);
            $importacaoFollowUpObj = $importacaoFollowUpObj->first();
            if($fields['campo'] == 'envio_das_cores'){
                $importacaoFollowUpObj->tipo_cor = $fields['valor'];
            }else if($fields['campo'] == 'envio_cor_data_previsao'){
                $importacaoFollowUpObj->envio_cor_data_previsao = $data_valor;
            }else if($fields['campo'] == 'envio_cor_data_envio'){
                $importacaoFollowUpObj->envio_cor_data_envio = $data_valor;
            }else if($fields['campo'] == 'envio_cor_data_recebido'){
                $importacaoFollowUpObj->envio_cor_data_recebido = $data_valor;
            }else if($fields['campo'] == 'quality_sample_aprovacao'){
                $importacaoFollowUpObj->quality_sample_aprovacao = $fields['valor'];
            }else if($fields['campo'] == 'quality_sample_data_previsao_envio'){
                $importacaoFollowUpObj->quality_sample_data_previsao_envio = $data_valor;
            }else if($fields['campo'] == 'quality_sample_data_envio'){
                $importacaoFollowUpObj->quality_sample_data_envio = $data_valor;
            }else if($fields['campo'] == 'quality_sample_data_recebido'){
                $importacaoFollowUpObj->quality_sample_data_recebido = $data_valor;
            }else if($fields['campo'] == 'quality_sample_data_previsao_aprovacao'){
                $importacaoFollowUpObj->quality_sample_data_previsao_aprovacao = $data_valor;
            }else if($fields['campo'] == 'quality_sample_data_aprovacao'){
                $importacaoFollowUpObj->quality_sample_data_aprovacao = $data_valor;
                $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
                $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
                $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'quality_sample';
                $importacaoFollowUpHistoricoAprovacaoObj->aprovado = $importacaoFollowUpObj->quality_sample_aprovacao;
                $importacaoFollowUpHistoricoAprovacaoObj->data = $data_valor;
                $importacaoFollowUpHistoricoAprovacaoObj->produto_codigo = $fields['produto_codigo'];
                $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
                $importacaoFollowUpHistoricoAprovacaoObj->save();
            }else if($fields['campo'] == 'laboratorio_aprovacao'){
                $importacaoFollowUpObj->laboratorio_aprovacao = $fields['valor'];
            }else if($fields['campo'] == 'laboratorio_data_previsao_envio'){
                $importacaoFollowUpObj->laboratorio_data_previsao_envio = $data_valor;
            }else if($fields['campo'] == 'laboratorio_data_envio'){
                $importacaoFollowUpObj->laboratorio_data_envio = $data_valor;
            }else if($fields['campo'] == 'laboratorio_data_recebido'){
                $importacaoFollowUpObj->laboratorio_data_recebido = $data_valor;
            }else if($fields['campo'] == 'laboratorio_data_aprovacao'){
                $importacaoFollowUpObj->laboratorio_data_aprovacao = $data_valor;
                $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
                $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
                $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'laboratorio';
                $importacaoFollowUpHistoricoAprovacaoObj->aprovado = $importacaoFollowUpObj->laboratorio_aprovacao;
                $importacaoFollowUpHistoricoAprovacaoObj->data = $data_valor;
                $importacaoFollowUpHistoricoAprovacaoObj->produto_codigo = $fields['produto_codigo'];
                $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
                $importacaoFollowUpHistoricoAprovacaoObj->save();
            }else if($fields['campo'] == 'tempo_producao_previsao_termino'){
                $importacaoFollowUpObj->tempo_producao_previsao_termino = $data_valor;
            }else if($fields['campo'] == 'tempo_producao_termino'){
                $importacaoFollowUpObj->tempo_producao_termino = $data_valor;
            }else if($fields['campo'] == 'amostra_embarque_aprovacao'){
                $importacaoFollowUpObj->amostra_embarque_aprovacao = $fields['valor'];
            }else if($fields['campo'] == 'amostra_embarque_data_previsao_envio'){
                $importacaoFollowUpObj->amostra_embarque_data_previsao_envio = $data_valor;
            }else if($fields['campo'] == 'amostra_embarque_data_envio'){
                $importacaoFollowUpObj->amostra_embarque_data_envio = $data_valor;
            }else if($fields['campo'] == 'amostra_embarque_enviado'){
                $importacaoFollowUpObj->amostra_embarque_data_envio = $data_valor;
            }else if($fields['campo'] == 'amostra_embarque_data_recebido'){
                $importacaoFollowUpObj->amostra_embarque_data_recebido = $data_valor;
            }else if($fields['campo'] == 'amostra_embarque_data_previsao_aprovacao'){
                $importacaoFollowUpObj->amostra_embarque_data_previsao_aprovacao = $data_valor;
            }else if($fields['campo'] == 'amostra_embarque_data_aprovacao'){
                $importacaoFollowUpObj->amostra_embarque_data_aprovacao = $data_valor;
                $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
                $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
                $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'amostra_embarque';
                $importacaoFollowUpHistoricoAprovacaoObj->aprovado = $importacaoFollowUpObj->amostra_embarque_aprovacao;
                $importacaoFollowUpHistoricoAprovacaoObj->data = $data_valor;
                $importacaoFollowUpHistoricoAprovacaoObj->produto_codigo = $fields['produto_codigo'];
                $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
                $importacaoFollowUpHistoricoAprovacaoObj->save();
            }else if($fields['campo'] == 'autorizacao_embarque_data_previsao_envio'){
                $importacaoFollowUpObj->autorizacao_embarque_data_previsao_envio = $data_valor;
            }else if($fields['campo'] == 'autorizacao_embarque_data_envio'){
                $importacaoFollowUpObj->autorizacao_embarque_data_envio = $data_valor;
            }else if($fields['campo'] == 'envio_cor_data_revisao'){
                $importacaoFollowUpObj->envio_cor_data_revisao = $data_valor;
            }else if($fields['campo'] == 'quality_sample_transportadora'){
                $importacaoFollowUpObj->quality_sample_transportadora = $fields['valor'];
            }else if($fields['campo'] == 'quality_sample_awb'){
                $importacaoFollowUpObj->quality_sample_awb = $fields['valor'];
            }else if($fields['campo'] == 'amostra_embarque_transportadora'){
                $importacaoFollowUpObj->amostra_embarque_transportadora = $fields['valor'];
            }else if($fields['campo'] == 'amostra_embarque_awb'){
                $importacaoFollowUpObj->amostra_embarque_awb = $fields['valor'];
            }
            $importacaoFollowUpObj->updated_by = Auth::id();
            $importacaoFollowUpObj->save();
        }
        
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $fields,
        ];

        return response()->json($response);
    }

    public function carregarResumo(Request $request){
        $fields = $request->only('id');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $tipo = $this->dadosTipoCores();
        $tipo_aprovacoes_simples = $this->dadosSimplesAprovacao();
        $tipo_aprovacoes_parcial = $this->dadosAprovacaoParcial();

        $importacaoFollowUpObj = ImportacaoFollowUp::select();
        $importacaoFollowUpObj->with(['detalhesProduto']);
        $importacaoFollowUpObj->whereNotNull('produto_codigo');
        $importacaoFollowUpObj->where('importacaos_id', $id);
        $importacaoFollowUpObj = $importacaoFollowUpObj->get();

        $dados_follow = [];
        foreach($importacaoFollowUpObj as $importacao_follow_up){
            $dados_follow[] = [
                'tipo_cor' => empty($importacao_follow_up->tipo_cor)? '' : $tipo[$importacao_follow_up->tipo_cor],
                'envio_cor_data_previsao' => empty($importacao_follow_up->envio_cor_data_previsao)? '' : $importacao_follow_up->envio_cor_data_previsao->format('d/m/Y'), 
                'envio_cor_data_envio' => empty($importacao_follow_up->envio_cor_data_envio)? '' : $importacao_follow_up->envio_cor_data_envio->format('d/m/Y'), 
                'envio_cor_data_recebido' => empty($importacao_follow_up->envio_cor_data_recebido)? '' : $importacao_follow_up->envio_cor_data_recebido->format('d/m/Y'), 
                'quality_sample_aprovacao' => empty($importacao_follow_up->quality_sample_aprovacao)? '' : $tipo_aprovacoes_simples[$importacao_follow_up->quality_sample_aprovacao], 
                'quality_sample_data_previsao_envio' => empty($importacao_follow_up->quality_sample_data_previsao_envio)? '' : $importacao_follow_up->quality_sample_data_previsao_envio->format('d/m/Y'), 
                'quality_sample_data_envio' => empty($importacao_follow_up->quality_sample_data_envio)? '' : $importacao_follow_up->quality_sample_data_envio->format('d/m/Y'), 
                'quality_sample_data_recebido' => empty($importacao_follow_up->quality_sample_data_recebido)? '' : $importacao_follow_up->quality_sample_data_recebido->format('d/m/Y'), 
                'quality_sample_data_previsao_aprovacao' => empty($importacao_follow_up->quality_sample_data_previsao_aprovacao)? '' : $importacao_follow_up->quality_sample_data_previsao_aprovacao->format('d/m/Y'), 
                'quality_sample_data_aprovacao' => empty($importacao_follow_up->quality_sample_data_aprovacao)? '' : $importacao_follow_up->quality_sample_data_aprovacao->format('d/m/Y'), 
                'laboratorio_aprovacao' => empty($importacao_follow_up->laboratorio_aprovacao)? '' : $tipo_aprovacoes_parcial[$importacao_follow_up->laboratorio_aprovacao], 
                'laboratorio_data_previsao_envio' => empty($importacao_follow_up->laboratorio_data_previsao_envio)? '' : $importacao_follow_up->laboratorio_data_previsao_envio->format('d/m/Y'), 
                'laboratorio_data_envio' => empty($importacao_follow_up->laboratorio_data_envio)? '' : $importacao_follow_up->laboratorio_data_envio->format('d/m/Y'), 
                'laboratorio_data_recebido' => empty($importacao_follow_up->laboratorio_data_recebido)? '' : $importacao_follow_up->laboratorio_data_recebido->format('d/m/Y'), 
                'laboratorio_data_aprovacao' => empty($importacao_follow_up->laboratorio_data_aprovacao)? '' : $importacao_follow_up->laboratorio_data_aprovacao->format('d/m/Y'), 
                'tempo_producao_previsao_termino' => empty($importacao_follow_up->tempo_producao_previsao_termino)? '' : $importacao_follow_up->tempo_producao_previsao_termino->format('d/m/Y'), 
                'tempo_producao_termino' => empty($importacao_follow_up->tempo_producao_termino)? '' : $importacao_follow_up->tempo_producao_termino->format('d/m/Y'), 
                'amostra_embarque_aprovacao' => empty($importacao_follow_up->amostra_embarque_aprovacao)? '' : $tipo_aprovacoes_parcial[$importacao_follow_up->amostra_embarque_aprovacao], 
                'amostra_embarque_data_previsao_envio' => empty($importacao_follow_up->amostra_embarque_data_previsao_envio)? '' : $importacao_follow_up->amostra_embarque_data_previsao_envio->format('d/m/Y'), 
                'amostra_embarque_data_envio' => empty($importacao_follow_up->amostra_embarque_data_envio)? '' : $importacao_follow_up->amostra_embarque_data_envio->format('d/m/Y'), 
                'amostra_embarque_data_recebido' => empty($importacao_follow_up->amostra_embarque_data_recebido)? '' : $importacao_follow_up->amostra_embarque_data_recebido->format('d/m/Y'), 
                'amostra_embarque_data_previsao_aprovacao' => empty($importacao_follow_up->amostra_embarque_data_previsao_aprovacao)? '' : $importacao_follow_up->amostra_embarque_data_previsao_aprovacao->format('d/m/Y'), 
                'amostra_embarque_data_aprovacao' => empty($importacao_follow_up->amostra_embarque_data_aprovacao)? '' : $importacao_follow_up->amostra_embarque_data_aprovacao->format('d/m/Y'), 
                'autorizacao_embarque_data_previsao_envio' => empty($importacao_follow_up->autorizacao_embarque_data_previsao_envio)? '' : $importacao_follow_up->autorizacao_embarque_data_previsao_envio->format('d/m/Y'), 
                'autorizacao_embarque_data_envio' => empty($importacao_follow_up->autorizacao_embarque_data_envio)? '' : $importacao_follow_up->autorizacao_embarque_data_envio->format('d/m/Y'), 
                'envio_cor_data_revisao' => empty($importacao_follow_up->envio_cor_data_revisao)? '' : $importacao_follow_up->envio_cor_data_revisao->format('d/m/Y'),
                'quality_sample_transportadora' => empty($importacao_follow_up->quality_sample_transportadora)? '' : $importacao_follow_up->quality_sample_transportadora,
                'quality_sample_awb' => empty($importacao_follow_up->quality_sample_awb)? '' : $importacao_follow_up->quality_sample_awb,
                'amostra_embarque_transportadora' => empty($importacao_follow_up->amostra_embarque_transportadora)? '' : $importacao_follow_up->amostra_embarque_transportadora,
                'amostra_embarque_awb' => empty($importacao_follow_up->amostra_embarque_awb)? '' : $importacao_follow_up->amostra_embarque_awb,
                'produto' => $importacao_follow_up->produto_codigo.' - '.$importacao_follow_up->detalhesProduto->descricao,
            ];
        }
        
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' =>  $dados_follow,
        ];

        return response()->json($response);
    }

    public function adicionarFinanceiroPrevisto(Request $request){
        $fields = $request->only('id', 'data_adiantamento', 'valor_adiantamento', 'data_parcela', 'valor_parcela', 'data_previsto_imposto', 'valor_previsto_imposto', 'data_carta_x', 'valor_carta_x', 'data_antecipacao_array', 'valor_parcela_array');
        
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoObj = Importacao::find($id);

        $data_adiantamento = empty($fields['data_adiantamento'])? null : Carbon::createFromFormat('d/m/Y', $fields['data_adiantamento'])->setTime(0,0,0);

        $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::select()->where('importacao_financeiros_id', $importacaoObj->financeiro->id)->where('modalidade', 'antecipado')->where('previsto', true)->first();
        $importacaoFinanceiroLancamentoObj->cambio_data = $data_adiantamento;
        $importacaoFinanceiroLancamentoObj->cambio_valor = empty($fields['valor_adiantamento'])? 0 : parserNumber($fields['valor_adiantamento']);
        $importacaoFinanceiroLancamentoObj->updated_by = Auth::id();   
        $importacaoFinanceiroLancamentoObj->save();

        if(!empty($fields['data_previsto_imposto']) || !empty($fields['valor_previsto_imposto'])){
            $data_previsto_imposto = empty($fields['data_previsto_imposto'])? null : Carbon::createFromFormat('d/m/Y', $fields['data_previsto_imposto'])->setTime(0,0,0);

            $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::select()->where('importacao_financeiros_id', $importacaoObj->financeiro->id)->where('modalidade', 'imposto')->where('previsto', true)->first();
            $importacaoFinanceiroLancamentoObj->cambio_data = $data_previsto_imposto;
            $importacaoFinanceiroLancamentoObj->real_valor = parserNumber($fields['valor_previsto_imposto']);
            $importacaoFinanceiroLancamentoObj->updated_by = Auth::id();   
            $importacaoFinanceiroLancamentoObj->save();
        }

        if(!empty($fields['data_carta_x']) || !empty($fields['valor_carta_x'])){
            $data_carta_x = empty($fields['data_carta_x'])? null : Carbon::createFromFormat('d/m/Y', $fields['data_carta_x'])->setTime(0,0,0);

            $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::select()->where('importacao_financeiros_id', $importacaoObj->financeiro->id)->where('modalidade', 'carta_x')->where('previsto', true)->first();
            $importacaoFinanceiroLancamentoObj->cambio_data = $data_carta_x;
            $importacaoFinanceiroLancamentoObj->cambio_valor = parserNumber($fields['valor_carta_x']);
            $importacaoFinanceiroLancamentoObj->updated_by = Auth::id();   
            $importacaoFinanceiroLancamentoObj->save();
        }

        $menor_id = '';
        if(!empty($fields['data_parcela']) || !empty($fields['valor_parcela'])){
            foreach($fields['data_parcela'] as $index => $data_parcela){
                $data = empty($data_parcela)? null : Carbon::createFromFormat('d/m/Y', $data_parcela)->setTime(0,0,0);

                $importacaoFinanceiroLancamentoObj = new ImportacaoFinanceiroLancamento;
                $importacaoFinanceiroLancamentoObj->importacao_financeiros_id = $importacaoObj->financeiro->id;
                $importacaoFinanceiroLancamentoObj->cambio_data = $data;
                $importacaoFinanceiroLancamentoObj->cambio_valor = empty($fields['valor_parcela'][$index])? 0 : parserNumber($fields['valor_parcela'][$index]);
                $importacaoFinanceiroLancamentoObj->modalidade = 'a_prazo';
                $importacaoFinanceiroLancamentoObj->pago = false;
                $importacaoFinanceiroLancamentoObj->previsto = true;
                $importacaoFinanceiroLancamentoObj->parcela = $index + 1;
                $importacaoFinanceiroLancamentoObj->updated_by = Auth::id();   
                $importacaoFinanceiroLancamentoObj->save();

                $menor_id = empty($menor_id)? $importacaoFinanceiroLancamentoObj->id : $menor_id;
            }    
        }

        if(empty($menor_id)){
            $deletar = ImportacaoFinanceiroLancamento::select()->where('importacao_financeiros_id', $importacaoObj->financeiro->id)->where('modalidade', 'a_prazo')->where('previsto', true)->get();
        }else{
            $deletar = ImportacaoFinanceiroLancamento::select()->where('id', '<', $menor_id)->where('importacao_financeiros_id', $importacaoObj->financeiro->id)->where('modalidade', 'a_prazo')->where('previsto', true)->get();
        }

        foreach($deletar as $value){
            $value->deleted_by = Auth::id();
            $value->delete();
            $value->save();
        }

        $ultima_parcela = 0;
        if(!empty($fields['data_antecipacao_array']) || !empty($fields['valor_parcela_array'])){
            foreach($fields['data_antecipacao_array'] as $index_antecipado => $data_parcela_antecipado){
                $data = empty($data_parcela_antecipado)? null : Carbon::createFromFormat('d/m/Y', $data_parcela_antecipado)->setTime(0,0,0);

                $importacaoFinanceiroLancamentoObj = ImportacaoFinanceiroLancamento::select()
                    ->where('importacao_financeiros_id', $importacaoObj->financeiro->id)
                    ->where('modalidade', 'antecipacao_segundaria')
                    ->where('previsto', true)
                    ->where('parcela', $index_antecipado + 2)
                    ->first();
                if(empty($importacaoFinanceiroLancamentoObj)){
                    $importacaoFinanceiroLancamentoObj = new ImportacaoFinanceiroLancamento;
                    $importacaoFinanceiroLancamentoObj->pago = false;
                    $importacaoFinanceiroLancamentoObj->previsto = true;
                }                
                $importacaoFinanceiroLancamentoObj->importacao_financeiros_id = $importacaoObj->financeiro->id;
                $importacaoFinanceiroLancamentoObj->cambio_data = $data;
                $importacaoFinanceiroLancamentoObj->cambio_valor = empty($fields['valor_parcela_array'][$index_antecipado])? 0 : parserNumber($fields['valor_parcela_array'][$index_antecipado]);
                $importacaoFinanceiroLancamentoObj->modalidade = 'antecipacao_segundaria';
                $importacaoFinanceiroLancamentoObj->parcela = $index_antecipado + 2;
                $importacaoFinanceiroLancamentoObj->updated_by = Auth::id();   
                $importacaoFinanceiroLancamentoObj->save();

                $ultima_parcela = $index_antecipado + 2;
            }    
        }
        
        $deletar = ImportacaoFinanceiroLancamento::select()
            ->where('importacao_financeiros_id', $importacaoObj->financeiro->id)
            ->where('modalidade', 'antecipacao_segundaria')
            ->where('previsto', true)
            ->where('parcela', '>', $ultima_parcela)
            ->get();
        foreach($deletar as $value){
            $value->deleted_by = Auth::id();
            $value->delete();
            $value->save();
        }
            
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function adicionarRed(ImportacaoFinanceiroLancamentoRedAdicionarRequest $request){
        $fields = $request->only('red_adicionado', 'reds', 'red_documento', 'red_saldo', 'red_taxa_cambio', 'red_valor_utilizado');

        if(!empty($fields['red_adicionado'])){
            $red_adicionado = decrypt($fields['red_adicionado']);
        }else{
            $red_adicionado = []; 
        }

        $reds = [];

        $redObj = Red::select();
        $redObj->where('numero_documento', $fields['red_documento']);
        $redObj = $redObj->first();

        $red_id = $redObj->id;
        $red_valor = empty($fields['red_valor_utilizado'])? 0 : parserNumber($fields['red_valor_utilizado']);

        foreach($red_adicionado as $index => $red){
            $reds[$index] = [
                'id' => $red['id'],
                'numero_documento' => $red['numero_documento'],
                'valor_utilizado' => $red['valor_utilizado'],
                'saldo' => $red['saldo'],
            ];
        }

        if(empty($reds[$fields['red_documento']])){
            $reds[$fields['red_documento']] = [
                'id' => $redObj->id,
                'numero_documento' => $fields['red_documento'],
                'valor_utilizado' => $fields['red_valor_utilizado'],
                'saldo' => parserValor($redObj->saldo - $red_valor),
            ];
        }else{
            $valor_anterior = parserNumber($reds[$fields['red_documento']]['valor_utilizado']) + $red_valor;
            $reds[$fields['red_documento']] = [
                'id' => $redObj->id,
                'numero_documento' => $fields['red_documento'],
                'valor_utilizado' => parserValor($valor_anterior),
                'saldo' => parserValor($redObj->saldo - $valor_anterior),
            ];
        }
        
        $total = [
            'saldo' => 0,
            'valor_utilizado' => 0,
        ];
        foreach($reds as $key => $red){
            $total['saldo'] += parserNumber($red['saldo']);
            $total['valor_utilizado'] += parserNumber($red['valor_utilizado']);
        }
        
        $total['saldo'] = empty($total['saldo'])? '' : parserValor($total['saldo']);
        $total['valor_utilizado'] = empty($total['valor_utilizado'])? '' : parserValor($total['valor_utilizado']);
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tabela' => $reds,
                'reds' => empty($reds)? '' : encrypt($reds),
                'total' => $total
            ],
        ]);
    }

    public function deletarRed(Request $request){
        $fields = $request->only('red_adicionado', 'numero_documento');

        if(!empty($fields['red_adicionado'])){
            $red_adicionado = decrypt($fields['red_adicionado']);
        }else{
            $red_adicionado = []; 
        }

        $reds = [];

        foreach($red_adicionado as $index => $red){
            $reds[$index] = [
                'id' => $red['id'],
                'numero_documento' => $red['numero_documento'],
                'valor_utilizado' => $red['valor_utilizado'],
                'saldo' => $red['saldo'],
            ];
        }

        unset($reds[$fields['numero_documento']]);
        
        $total = [
            'saldo' => 0,
            'valor_utilizado' => 0,
        ];
        foreach($reds as $key => $red){
            $total['saldo'] += parserNumber($red['saldo']);
            $total['valor_utilizado'] += parserNumber($red['valor_utilizado']);
        }
        
        $total['saldo'] = empty($total['saldo'])? '' : parserValor($total['saldo']);
        $total['valor_utilizado'] = empty($total['valor_utilizado'])? '' : parserValor($total['valor_utilizado']);
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tabela' => $reds,
                'reds' => empty($reds)? '' : encrypt($reds),
                'total' => $total
            ],
        ]);
    }
}
