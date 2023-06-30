@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id="importacao-dados_gerais-tab" data-toggle="tab" href="#importacao_dados_gerais" role="tab" aria-controls="importacao_dados_gerais" aria-selected="false">Dados Gerais</a>
    </li>
	<li class="nav-item">
        <a class="nav-link" id="importacao-produtos-tab" data-toggle="tab" href="#importacao_produtos" role="tab" aria-controls="importacao_produtos" aria-selected="false">Produtos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-follow_up-lead_time-tab" data-toggle="tab" href="#importacao_follow_up_lead_time" role="tab" aria-controls="importacao_follow_up_lead_time" aria-selected="false">Follow Up/Lead Time</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-dados_adicionais_embarque-tab" data-toggle="tab" href="#importacao_dados_adicionais_embarque" role="tab" aria-controls="importacao_dados_adicionais_embarque" aria-selected="false">Dados Adicionais Embarque</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-documentos_processos-tab" data-toggle="tab" href="#importacao_documentos_processos" role="tab" aria-controls="importacao_documentos_processos" aria-selected="false">Documentos do Processo</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-financeiro-tab" data-toggle="tab" href="#importacao_financeiro" role="tab" aria-controls="importacao_financeiro" aria-selected="false">Financeiro</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-custo-realizado-x-previsto-tab" data-toggle="tab" href="#importacao_custo_realizado_x_previsto" role="tab" aria-controls="importacao_custo_realizado_x_previsto" aria-selected="false">Custos</a>
    </li>
</ul>
<form action="" name="form_importacao_edt" id="form_importacao_edt" onsubmit="return false;">
    <div class="tab-content pt-3" id="ImportacaoHeaderContainer">
        <div class="tab-pane show active" id="importacao_dados_gerais" role="tabpanel" aria-labelledby="dados-tab">
            <div class="col-lg-12">			
                <div class="row">
                    <div class="col-lg-12">
                        <h5><b>Proforma</b> {{ $dados['proforma'] }} - <b>PCMN:</b> {{ $dados['pcmn'] }}</h5>
                    </div>
                </div>
                <hr>
                <div class='pedido_detalhes_content'>
                    <div class="row">
                        <div class="col-sm-6">
                            <b>Nota Importação:</b><br>
                            {{ $dados['nota_importacao_numero'] }}
                        </div>
                        <div class="col-sm-6">
                            <b>Nota Remessa:</b><br>
                            {{ $dados['nota_remessa_numero'] }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <b>Fornecedor:</b><br>
                            {{ $dados['fornecedor'] }}
                        </div>
                        <div class="col-sm-6">
                            <b>Representante:</b><br>
                            {{ $dados['respresentante'] }}
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-12">
                            <b>Referência:</b><br>
                            {{ $dados['referencia'] }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <b>Data da Proforma:</b><br>
                            {{ $dados['data_proforma'] }}
                        </div>
                        <div class="col-sm-4">
                            <b>Previsão da Carta Programa:</b><br>
                            {{ $dados['data_previsao_carta_programa'] }}
                        </div>
                        <div class="col-sm-4">
                            <b>Previsão de Recebimento:</b><br>
                            {{ $dados['data_previsao_recebimento'] }}
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-sm-12">
                            <b>Data Embarque:</b><br>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="importacao_produtos" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <div class="content-dialog-table">
                    <table class="table table-striped table-filter-dialog" id="table-filters-produtos-proforma">
                        <thead>
                            <tr>
                                <th>Item nº</th>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Composição</th>
                                <th class="tb_number">Gramatura GM2</th>
                                <th class="tb_number">Largura</th>
                                <th>Gramatura GML</th>
                                <th>Rend.</th>
                                <th>Inst. de Lavagem</th>
                                <th>Sta.</th>
                                <th class="tb_number">QTD Prev.</th>
                                <th class="tb_number">QTD Real.</th>
                                <th class="tb_number">Preço FOB Unit.(US$)</th>
                                <th class="tb_number">Valor FOB Total(US$)</th>
                                <th class="tb_number">Preço Contábil Unit.(US$)</th>
                                <th class="tb_number">Valor Contábil Total(US$)</th>
                                <th class="tb_number">Val. Rateio Real.</th>
                                <th class="tb_number">Val. Rateio Prev.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dados['itens'] as $item)
                            <tr>
                                <td>{{ $item['item'] }}</td>
                                <td>{!! $item['foto'] !!} {!! $item['codigo'] !!}</td>
                                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $item['produto'] }}">{{ $item['produto'] }}</div></div></td>
                                <td class="tb_number"><div><div data-toggle="tooltip" data-html="true" title="{{ $item['composicao'] }}">{{ $item['composicao'] }}</div></div><</td>
                                <td class="tb_number">{{ $item['gramatura_gm2'] }}</td>
                                <td>{{ $item['largura'] }}</td>
                                <td>{{ $item['gramatura_gml'] }}</td>
                                <td>{{ $item['rendimento'] }}</td>
                                <td>{{ $item['instrucao_lavagem'] }}</td>
                                <td>{{ $item['status'] }}</td>
                                <td class="tb_number">{{ $item['quantidade'] }}</td>
                                <td class="tb_number">{{ $item['quantidade_realizada'] }}</td>
                                <td class="tb_number">{{ $item['preco_unitario'] }}</td>
                                <td class="tb_number">{{ $item['preco_total'] }}</td>
                                <td class="tb_number"><input type="hidden" name="valor_contabil_unitario_{{$item['codigo']}}" id="valor_contabil_unitario_{{$item['codigo']}}" data-codigo="{{$item['codigo']}}" data-quantidade="{{ $item['quantidade'] }}" data-preco_fob_total_unitario="{{ $item['preco_total'] }}" data-quantidade_realizada="{{ $item['quantidade_realizada'] }}" data-valor_contabil_unitario="{{$item['valor_contabil_unitario']}}"></input>{{$item['valor_contabil_unitario']}}</td>
                                <td class="tb_number">{{$item['valor_contabil_total']}}</td>
                                <td class="tb_number"><div id="valor_rateio_{{$item['codigo']}}" name="valor_rateio_{{$item['codigo']}}"></div></td>
                                <td class="tb_number"><div id="valor_rateio_previsto_{{$item['codigo']}}" name="valor_rateio_previsto_{{$item['codigo']}}"></div></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="tb_number"></td>
                                <td class="tb_number"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="tb_number">Total:</td>
                                <td class="tb_number">{{$dados['total']['quantidade']}}</td>
                                <td class="tb_number">{{$dados['total']['quantidade_realizada']}}</td>
                                <td class="tb_number"></td>
                                <td class="tb_number">{{$dados['total']['preco_fob']}}</td>
                                <td class="tb_number"></td>
                                <td class="tb_number" id="valor_contabil_total_total">{{$dados['total']['preco_contabil']}}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>

        <div class="tab-pane" id="importacao_follow_up_lead_time" role="tabpanel" aria-labelledby="dados-tab">
            <table class="table">
                <tr>
                    <td width="33%"></td>
                    <td width="33%">Previsão</td>
                    <td width="33%">Realizado</td>
                </tr>
                <tr>
                    <td>Carga Pronta</td>
                    <td>{{ $dados['data_carga_pronta_previsao'] }}</td>
                    <td>{{ $dados['data_carga_pronta_realizado'] }}</td>
                </tr>
                <tr>
                    <td>Embarque ETD</td>
                    <td>{{ $dados['data_embarque_previsao'] }}</td>
                    <td>{{ $dados['data_embarque_realizado'] }}</td>
                </tr>
                <tr>
                    <td>Chegada no Porto ETA</td>
                    <td>{{ $dados['data_chegada_porto_previsao'] }}</td>
                    <td>{{ $dados['data_chegada_porto_realizado'] }}</td>
                </tr>  
                <tr>
                    <td>Data DI</td>
                    <td>{{ $dados['data_di_previsao'] }}</td>
                    <td>{{ $dados['data_di_realizado'] }}</td>
                </tr>  
                <tr>
                    <td>Devolução CNTR</td>
                    <td>{{ $dados['data_devolucao_cntr_previsao']  }}</td>
                    <td>{{ $dados['data_devolucao_cntr_realizado']  }}</td>
                </tr>
            </table>
            <hr>
            @foreach($dados['dados_follow'] as $dados_follow)
            <h5>{{$dados_follow['produto']}} - Tipo: {{$dados_follow['tipo_cor']}}</h5><br>
            <table class="table">
                <tr>
                    <th width="12%"></th>
                    <th width="11%">Previsão Envio/Termino</th>
                    <th width="11%">Enviado/Termino</th>
                    <th width="11%">Transportadora</th>
                    <th width="11%">AWB</th>
                    <th width="11%">Recebido</th>
                    <th width="11%">Revisão</th>
                    <th width="11%">Data Apr.</th>
                    <th width="12%">Status Apr.</th>
                </tr>
                <tr>
                    <td>Envio da Cores</td>
                    <td>{{$dados_follow['envio_cor_data_previsao']}}</td>
                    <td>{{$dados_follow['envio_cor_data_envio']}}</td>
                    <td></td>
                    <td></td>
                    <td>{{$dados_follow['envio_cor_data_recebido']}}</td>
                    <td>{{$dados_follow['envio_cor_data_revisao']}}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Quality Sample</td>
                    <td>{{$dados_follow['quality_sample_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['quality_sample_data_envio']}}</td>
                    <td>{{$dados_follow['quality_sample_transportadora']}}</td>
                    <td>{{$dados_follow['quality_sample_awb']}}</td>
                    <td>{{$dados_follow['quality_sample_data_recebido']}}</td>
                    <td></td>
                    <td>{{$dados_follow['quality_sample_data_aprovacao']}}</td>
                    <td>{{$dados_follow['quality_sample_aprovacao']}}</td>
                </tr>
                <tr>
                    <td>Laboratório</td>
                    <td>{{$dados_follow['laboratorio_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['laboratorio_data_envio']}}</td>
                    <td></td>
                    <td></td>
                    <td>{{$dados_follow['laboratorio_data_recebido']}}</td>
                    <td></td>
                    <td>{{$dados_follow['laboratorio_data_aprovacao']}}</td>
                    <td>{{$dados_follow['laboratorio_aprovacao']}}</td>
                </tr>
                <tr>
                    <td>Termino da Produção</td>
                    <td>{{$dados_follow['tempo_producao_previsao_termino']}}</td>
                    <td>{{$dados_follow['tempo_producao_termino']}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Amostra Embarque</td>
                    <td>{{$dados_follow['amostra_embarque_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['amostra_embarque_data_envio']}}</td>
                    <td>{{$dados_follow['amostra_embarque_transportadora']}}</td>
                    <td>{{$dados_follow['amostra_embarque_awb']}}</td>
                    <td>{{$dados_follow['amostra_embarque_data_recebido']}}</td>
                    <td></td>
                    <td>{{$dados_follow['amostra_embarque_data_aprovacao']}}</td>
                    <td>{{$dados_follow['amostra_embarque_aprovacao']}}</td>
                </tr>
                <tr>
                    <td>Autorização do Embarque</td>
                    <td>{{$dados_follow['autorizacao_embarque_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['autorizacao_embarque_data_envio']}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
            </br>
            </br>
            <hr>
        @endforeach
        </div>

        <div class="tab-pane" id="importacao_dados_adicionais_embarque" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12">
                    <b>Porto Origem:</b><br>
                    {{ $dados['porto_origem']}}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    <b>Porto Destino:</b><br>
                    {{ $dados['porto_destino']}}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    <b>Agente de Compras:</b><br>
                    {{ $dados['agente_compra']}}
                </div>
                <div class="form-group col-sm-6">
                    <b>Armador:</b><br>
                    {{ $dados['armador']}}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    <b>Nº B/L:</b><br>
                    {{ $dados['numero_bl'] }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    <b>Cycle Time:</b><br>
                    @if(!empty($dados['cycle_time']))
                        {{ $dados['cycle_time'] }} Dias
                    @endif
                </div>
            </div>
        </div>

        <div class="tab-pane" id="importacao_documentos_processos" role="tabpanel" aria-labelledby="dados-tab">                
            @foreach ($dados['arquivos'] as $titulo_index => $tipos)
                <div class="form-row">
                    @foreach($tipos as $index => $arquivo)
                        <div class="box-exibicao-100">
                            @if($arquivo['extensao'] === 'jpg' || $arquivo['extensao'] === 'jpeg' || $arquivo['extensao'] === 'png')
                                <div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}">
                                    <a href={{ $arquivo['caminho'] }} class="foto-produto"> 
                                        <img src={{ $arquivo['caminho'] }} style="width: 100px; height: 100px">
                                    </a>
                                </div>
                            @elseif($arquivo['extensao'] === 'pdf')
                                <div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}" style="text-align: center;">
                                    <a href={{ $arquivo['caminho'] }} class="icon-pdf" target="_blank"></a>
                                </div>
                            @elseif($arquivo['extensao'] === 'xlsx' || $arquivo['extensao'] === 'xls')
                                <div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}" style="text-align: center;">
                                    <a href={{ $arquivo['caminho'] }} class="icon-excel" target="_blank"></a>
                                </div>
                            @else
                                <div data-toggle='tooltip' data-html='true' data-placement='right' title="{{$arquivo['nome']}}_{{$index}}" style="text-align: center;">
                                    <a href={{ $arquivo['caminho'] }} class="icon-documento" target="_blank"></a>
                                </div>
                            @endif
                            <div data-toggle='tooltip' data-html='true' data-placement='right' title="{{$arquivo['nome']}}_{{$index}}" style="text-align: center;">
                                <a href={{ $arquivo['caminho'] }} target="_blank">
                                    {{$arquivo['nome_resumido']}}_{{$index}}
                                </a>
                            </div>
                        </div>
                    @endforeach  
                </div>
            @endforeach
        </div>

        <div class="tab-pane" id="importacao_financeiro" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12">
                    <b>Data Embarque:</b><br>
                    {{ $dados['data_embarque'] }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-3">
                    <b>Valor FOB Pago(US$):</b><br>
                    {{$dados['lancamento_total']}}
                </div>
                <div class="form-group col-sm-3">
                    <b>Valor FOB Pago(R$):</b><br>
                    {{$dados['lancamento_real_total']}}
                </div>
                <div class="form-group col-sm-2">
                    <b>Valor FOB Devido(US$):</b><br>
                    {{ $dados['total']['preco_fob']}}
                </div>
                <div class="form-group col-sm-2">
                    <b>Valor Contábil(US$):</b><br>
                    {{ $dados['total']['preco_contabil'] }}
                </div>
                @if(parserNumber($dados['total']['preco_contabil']) > parserNumber($dados['total']['preco_fob']))
                <div class="form-group col-sm-2">
                    <b>Crédito(US$):</b><br>
                    {{ $dados['total']['diferenca_preco'] }}
                </div>
                @else
                <div class="form-group col-sm-2">
                    <b>Débito(US$):</b><br>
                    {{ $dados['total']['diferenca_preco'] }}
                </div>
                @endif
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    <b>Lançamentos:</b><br>
                    <div class="content-dialog-table">
                        <table class="table table-striped table-filter-dialog footer-pequeno" id="table-dialog_historico_lancamento">
                            <thead>
                                <th class="tb_date">Data Câmbio</th>
                                <th class="tb_number">Valor Câmbio</th>
                                <th>Modalidade</th>
                                <th class="tb_number">Taxa R$</th>
                                <th class="tb_number">Valor R$</th>
                                <th>Banco</th>
                                <th>Tipo Fechamento</th>
                                <th class="tb_number">Nº Contrato Câmbio</th>
                                <th class="tb_number">Nº Contrato Banco</th>
                            </thead>
                            <tbody>
                                @foreach($dados['lancamentos'] as $lancamento)
                                <tr>
                                    <td class="tb_date">{{$lancamento['cambio_data']}}</td>
                                    <td class="tb_number">{{$lancamento['cambio_valor']}}</td>
                                    <td>{{$lancamento['modalidade']}}</td>
                                    <td class="tb_number">{{$lancamento['real_taxa']}}</td>
                                    <td class="tb_number">{{$lancamento['real_valor']}}</td>
                                    <td>{{$lancamento['banco']}}</td>
                                    <td>{{$lancamento['fechamento_tipo']}}</td>
                                    <td class="tb_number">{{$lancamento['cambio_numero_contrato']}}</td>
                                    <td class="tb_number">{{$lancamento['banco_numero_contrato']}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <td class="tb_number">Total:</td>
                                <td class="tb_number">{{$dados['lancamento_total']}}</td>
                                <td></td>
                                <td class="tb_number"></td>
                                <td class="tb_number" id="real_valor_total">{{$dados['lancamento_real_total']}}</td>
                                <td></td>
                                <td></td>
                                <td class="tb_number"></td>
                                <td class="tb_number"></td>
                            </tfoot>
                        </table>
                    </div>
                    @if(!empty($dados['previstos']))
                        <b>Previstos:</b><br>
                        <div class="content-dialog-table">
                            <table class="table table-striped table-filter-dialog footer-pequeno" id="table-dialog_historico_lancamento_previsto">
                                <thead>
                                    <th>Modalidade</th>
                                    <th class="tb_date">Data Previsão de Pagamento</th>
                                    <th class="tb_number">Valor Câmbio</th>
                                    <th class="tb_number">Valor R$</th>
                                </thead>
                                <tbody>
                                    @foreach($dados['previstos'] as $previsto)
                                    <tr>
                                        <td>{{$previsto['modalidade']}}</td>
                                        <td class="tb_date">{{$previsto['cambio_data']}}</td>
                                        <td class="tb_number">{{$previsto['cambio_valor']}}</td>
                                        <td class="tb_number">{{$previsto['real_valor']}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tab-pane" id="importacao_custo_realizado_x_previsto" role="tabpanel" aria-labelledby="dados-tab">
            <table class="table">
                <tr>
                    <th class="text-center"></th>
                    <th class="text-center">Realizado/À Pagar</th>
                    <th class="text-center">Previsto</th>
                    <th class="text-center">Diferença</th>
                </tr>
                <tr>
                    <td>Total Cambio(R$)</td>
                    <td class="text-right border-right" id="total_cambio_realizado">{{$dados['total_cambio_realizado']}} / {{((parserNumber($dados['total']['preco_fob']) - parserNumber($dados['valor_fob_pago'])) * $dados['cotacao_dolar']) < 0? '' : parserValor(((parserNumber($dados['total']['preco_fob']) - parserNumber($dados['valor_fob_pago'])) * $dados['cotacao_dolar']))}}</td>
                    <td class="text-right border-right" id="total_cambio_previsto">{{$dados['custo_previsto']['total_cambio_previsto']}}</td>
                    <td class="text-right border-right" id="total_cambio_diferenca">{{$dados['custo_diferenca']['total_cambio_diferenca']}}</td>
                </tr>
                <tr>
                    <td>II</td>
                    <td class="text-right border-right" id="ii_realizado">{{$dados['ii']}}</td>
                    <td class="text-right border-right" id="ii_previsto">{{$dados['custo_previsto']['ii_previsto']}}</td>
                    <td class="text-right border-right" id="ii_diferenca">{{$dados['custo_diferenca']['ii_diferenca']}}</td>
                </tr>
                <tr>
                    <td>IPI</td>
                    <td class="text-right border-right" id="ipi_realizado">{{$dados['ipi']}}</td>
                    <td class="text-right border-right" id="ipi_previsto">{{$dados['custo_previsto']['ipi_previsto']}}</td>
                    <td class="text-right border-right" id="ipi_diferenca">{{$dados['custo_diferenca']['ipi_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>PIS</td>
                    <td class="text-right border-right" id="pis_realizado">{{$dados['pis']}}</td>
                    <td class="text-right border-right" id="pis_previsto">{{$dados['custo_previsto']['pis_previsto']}}</td>
                    <td class="text-right border-right" id="pis_diferenca">{{$dados['custo_diferenca']['pis_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>COFINS</td>
                    <td class="text-right border-right" id="cofins_realizado">{{$dados['cofins']}}</td>
                    <td class="text-right border-right" id="cofins_previsto">{{$dados['custo_previsto']['cofins_previsto']}}</td>
                    <td class="text-right border-right" id="cofins_diferenca">{{$dados['custo_diferenca']['cofins_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>AFRMM</td>
                    <td class="text-right border-right" id="afrmm_realizado">{{$dados['afrmm']}}</td>
                    <td class="text-right border-right" id="afrmm_previsto">{{$dados['custo_previsto']['afrmm_previsto']}}</td>
                    <td class="text-right border-right" id="afrmm_diferenca">{{$dados['custo_diferenca']['afrmm_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Taxa Siscomex</td>
                    <td class="text-right border-right" id="taxa_siscomex_realizado">{{$dados['taxa_siscomex']}}</td>
                    <td class="text-right border-right" id="taxa_siscomex_previsto">{{$dados['custo_previsto']['taxa_siscomex_previsto']}}</td>
                    <td class="text-right border-right" id="taxa_siscomex_diferenca">{{$dados['custo_diferenca']['taxa_siscomex_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>SDA</td>
                    <td class="text-right border-right" id="sda_realizado">{{$dados['sda']}}</td>
                    <td class="text-right border-right" id="sda_previsto">{{$dados['custo_previsto']['sda_previsto']}}</td>
                    <td class="text-right border-right" id="sda_diferenca">{{$dados['custo_diferenca']['sda_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Honorários</td>
                    <td class="text-right border-right" id="honorarios_realizado">{{$dados['honorarios']}}</td>
                    <td class="text-right border-right" id="honorarios_previsto">{{$dados['custo_previsto']['honorarios_previsto']}}</td>
                    <td class="text-right border-right" id="honorarios_diferenca">{{$dados['custo_diferenca']['honorarios_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Expediente</td>
                    <td class="text-right border-right" id="expediente_realizado">{{$dados['expediente']}}</td>
                    <td class="text-right border-right" id="expediente_previsto">{{$dados['custo_previsto']['expediente_previsto']}}</td>
                    <td class="text-right border-right" id="expediente_diferenca">{{$dados['custo_diferenca']['expediente_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Valor Li</td>
                    <td class="text-right border-right" id="valor_li_realizado">{{$dados['valor_li']}}</td>
                    <td class="text-right border-right" id="valor_li_previsto">{{$dados['custo_previsto']['valor_li_previsto']}}</td>
                    <td class="text-right border-right" id="valor_li_diferenca">{{$dados['custo_diferenca']['valor_li_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Agência Marítima</td>
                    <td class="text-right border-right" id="agencia_maritima_realizado">{{$dados['agencia_maritima']}}</td>
                    <td class="text-right border-right" id="agencia_maritima_previsto">{{$dados['custo_previsto']['agencia_maritima_previsto']}}</td>
                    <td class="text-right border-right" id="agencia_maritima_diferenca">{{$dados['custo_diferenca']['agencia_maritima_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Armazenagem</td>
                    <td class="text-right border-right" id="armazenagem_realizado">{{$dados['armazem']}}</td>
                    <td class="text-right border-right" id="armazenagem_previsto">{{$dados['custo_previsto']['armazenagem_previsto']}}</td>
                    <td class="text-right border-right" id="armazenagem_diferenca">{{$dados['custo_diferenca']['armazenagem_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Laudo</td>
                    <td class="text-right border-right" id="laudo_realizado">{{$dados['laudo']}}</td>
                    <td class="text-right border-right" id="laudo_previsto">{{$dados['custo_previsto']['laudo_previsto']}}</td>
                    <td class="text-right border-right" id="laudo_diferenca">{{$dados['custo_diferenca']['laudo_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>ICMS Remessa</td>
                    <td class="text-right border-right" id="icms_saida_realizado">{{$dados['icms_saida']}}</td>
                    <td class="text-right border-right" id="icms_saida_previsto">{{$dados['custo_previsto']['icms_saida_previsto']}}</td>
                    <td class="text-right border-right" id="icms_saida_diferenca">{{$dados['custo_diferenca']['icms_saida_diferenca']}}</td>
                </tr>
                <tr>
                    <td>ICMS À Pagar</td>
                    <td class="text-right border-right" id="icms_saida_realizado">{{$dados['icms_a_pagar']}}</td>
                    <td class="text-right border-right" id="icms_saida_previsto">{{$dados['custo_previsto']['icms_a_pagar_previsto']}}</td>
                    <td class="text-right border-right" id="icms_saida_diferenca">{{parserValor(parserNumber($dados['icms_a_pagar']) - parserNumber($dados['custo_previsto']['icms_a_pagar_previsto']))}}</td>
                </tr> 
                <tr>
                    <td>Transporte Rodoviário</td>
                    <td class="text-right border-right" id="transporte_rodoviario_realizado">{{$dados['transporte_rodoviario']}}</td>
                    <td class="text-right border-right" id="transporte_rodoviario_previsto">{{$dados['custo_previsto']['transporte_rodoviario_previsto']}}</td>
                    <td class="text-right border-right" id="transporte_rodoviario_diferenca">{{$dados['custo_diferenca']['transporte_rodoviario_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Seguro</td>
                    <td class="text-right border-right" id="seguro_realizado">{{$dados['seguro']}}</td>
                    <td class="text-right border-right" id="seguro_previsto">{{$dados['custo_previsto']['seguro_previsto']}}</td>
                    <td class="text-right border-right" id="seguro_diferenca">{{$dados['custo_diferenca']['seguro_diferenca']}}</td>
                </tr> 
                <tr>
                    <td>Outras Despesas</td>
                    <td class="text-right border-right" id="outras_despesas_realizado">{{$dados['outras_despesas_total']}}</td>
                    <td class="text-right border-right" id="outras_despesas_previsto">{{$dados['custo_previsto']['outras_despesas_previsto']}}</td>
                    <td class="text-right border-right" id="outras_despesas_diferenca">{{$dados['custo_diferenca']['outras_despesas_diferenca']}}</td>
                </tr>
                <tr>
                    <td>Total</td>
                    <td class="text-right border-right" id="total_realizado">{{$dados['total_custo_realizado']}}</td>
                    <td class="text-right border-right" id="total_previsto">{{$dados['custo_previsto']['total_previsto']}}</td>
                    <td class="text-right border-right" id="total_diferenca">{{$dados['custo_diferenca']['total_diferenca']}}</td>
                </tr>  
            </table>
        </div>

    </div>
</form>
<script>
    produto_array = [];
    total_preco_fob = "{{$dados['total']['preco_fob']}}";
    @foreach($dados['itens'] as $item)
        produto_array.push("{{ $item['codigo'] }}");
    @endforeach

    quantidade_total = {{parserNumber($dados['total']['quantidade'])}};
    quantidade_realizada_total = {{parserNumber($dados['total']['quantidade_realizada'])}};
    ii_previsto = "{{$dados['custo_previsto']['ii_previsto']}}";
    ipi_previsto = "{{$dados['custo_previsto']['ipi_previsto']}}";
    pis_previsto = "{{$dados['custo_previsto']['pis_previsto']}}";
    cofins_previsto = "{{$dados['custo_previsto']['cofins_previsto']}}";
    afrmm_previsto = "{{$dados['custo_previsto']['afrmm_previsto']}}";
    taxa_siscomex_previsto = "{{$dados['custo_previsto']['taxa_siscomex_previsto']}}";
    sda_previsto = "{{$dados['custo_previsto']['sda_previsto']}}";
    honorarios_previsto = "{{$dados['custo_previsto']['honorarios_previsto']}}";
    expediente_previsto = "{{$dados['custo_previsto']['expediente_previsto']}}";
    valor_li_previsto = "{{$dados['custo_previsto']['valor_li_previsto']}}";
    agencia_maritima_previsto = "{{$dados['custo_previsto']['agencia_maritima_previsto']}}";
    laudo_previsto = "{{$dados['custo_previsto']['laudo_previsto']}}";
    icms_saida_previsto = "{{$dados['custo_previsto']['icms_saida_previsto']}}";
    seguro_previsto = "{{$dados['custo_previsto']['seguro_previsto']}}";
    armazenagem_previsto = "{{$dados['custo_previsto']['armazenagem_previsto']}}";
    outras_despesas_previsto = "{{$dados['custo_previsto']['outras_despesas_previsto']}}";
    total_previsto = "{{$dados['custo_previsto']['total_previsto']}}";
    total_cambio_previsto = "{{$dados['custo_previsto']['total_cambio_previsto']}}";

    $(document).ready( function () {
        form_modal_edt = $(document).find('#form_importacao_edt');
        
        table_produtos_proforma_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum registro inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_produtos_proforma = '';
        table_produtos_proforma = $(document).find('#table-filters-produtos-proforma').DataTable(table_produtos_proforma_options);
        table_produtos_proforma.draw();

        table_outras_depesas_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "15vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum registro inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                }
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_outras_depesas = '';
        table_outras_depesas = $(document).find('#table-outras_despesas').DataTable(table_outras_depesas_options);
        table_outras_depesas.draw();

        table_filters_dialog_historico_lancamento = $('#table-dialog_historico_lancamento').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 0, 'asc' ]]
        });

        table_filters_dialog_historico_lancamento.draw();

        table_filters_dialog_historico_lancamento_previsto = $('#table-dialog_historico_lancamento_previsto').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 0, 'asc' ]]
        });

        table_filters_dialog_historico_lancamento_previsto.draw();
        
        $(document).find("#importacao-custo-realizado-x-previsto-tab").off("click");
        $(document).find("#importacao-custo-realizado-x-previsto-tab").on("click", function(){
            resultado(form_modal_edt);
        });

        $(document).find("#importacao-produtos-tab").off("click");
        $(document).find("#importacao-produtos-tab").on("click", function(){
            calculoRateio();
            setTimeout(function(){
                table_produtos_proforma.draw(false);
            }, 180);
        });
    });

    function resultado(form_modal_edt){
        ii = "{{$dados['ii']}}";
        ipi = "{{$dados['ipi']}}";
        pis = "{{$dados['pis']}}";
        cofins = "{{$dados['cofins']}}";
        afrmm = "{{$dados['afrmm']}}";
        taxa_siscomex = "{{$dados['taxa_siscomex']}}";
        sda = "{{$dados['sda']}}";
        honorarios = "{{$dados['honorarios']}}";
        expediente = "{{$dados['expediente']}}";
        valor_li = "{{$dados['valor_li']}}";
        agencia_maritima = "{{$dados['agencia_maritima']}}";
        armazem = "{{$dados['armazem']}}";
        laudo = "{{$dados['laudo']}}";
        icms_saida = "{{$dados['icms_saida']}}";
        seguro = "{{$dados['seguro']}}";
        outras_despesas = "{{$dados['outras_despesas_total']}}";
        total_cambio_realizado = "{{$dados['total_cambio_realizado']}}";
        transporte_rodoviario = "{{$dados['transporte_rodoviario']}}";
        total_cambio_a_ser_realizado = "{{((parserNumber($dados['total']['preco_fob']) - parserNumber($dados['valor_fob_pago'])) * $dados['cotacao_dolar']) < 0? '' : parserValor(((parserNumber($dados['total']['preco_fob']) - parserNumber($dados['valor_fob_pago'])) * $dados['cotacao_dolar']))}}";

        if(ii == "" || $.isEmptyObject(ii)){
            ii = 0;
        }else{
            ii = ii.replace(/\./g,"").replace(/\,/g, ".");
            ii = parseFloat(ii);
        }
        if(ipi == "" || $.isEmptyObject(ipi)){
            ipi = 0;
        }else{
            ipi = ipi.replace(/\./g,"").replace(/\,/g, ".");
            ipi = parseFloat(ipi);
        }
        if(pis == "" || $.isEmptyObject(pis)){
            pis = 0;
        }else{
            pis = pis.replace(/\./g,"").replace(/\,/g, ".");
            pis = parseFloat(pis);
        }
        if(cofins == "" || $.isEmptyObject(cofins)){
            cofins = 0;
        }else{
            cofins = cofins.replace(/\./g,"").replace(/\,/g, ".");
            cofins = parseFloat(cofins);
        }
        if(afrmm == "" || $.isEmptyObject(afrmm)){
            afrmm = 0;
        }else{
            afrmm = afrmm.replace(/\./g,"").replace(/\,/g, ".");
            afrmm = parseFloat(afrmm);
        }
        if(taxa_siscomex == "" || $.isEmptyObject(taxa_siscomex)){
            taxa_siscomex = 0;
        }else{
            taxa_siscomex = taxa_siscomex.replace(/\./g,"").replace(/\,/g, ".");
            taxa_siscomex = parseFloat(taxa_siscomex);
        }
        if(sda == "" || $.isEmptyObject(sda)){
            sda = 0;
        }else{
            sda = sda.replace(/\./g,"").replace(/\,/g, ".");
            sda = parseFloat(sda);
        }
        if(honorarios == "" || $.isEmptyObject(honorarios)){
            honorarios = 0;
        }else{
            honorarios = honorarios.replace(/\./g,"").replace(/\,/g, ".");
            honorarios = parseFloat(honorarios);
        }
        if(expediente == "" || $.isEmptyObject(expediente)){
            expediente = 0;
        }else{
            expediente = expediente.replace(/\./g,"").replace(/\,/g, ".");
            expediente = parseFloat(expediente);
        }
        if(valor_li == "" || $.isEmptyObject(valor_li)){
            valor_li = 0;
        }else{
            valor_li = valor_li.replace(/\./g,"").replace(/\,/g, ".");
            valor_li = parseFloat(valor_li);
        }
        if(agencia_maritima == "" || $.isEmptyObject(agencia_maritima)){
            agencia_maritima = 0;
        }else{
            agencia_maritima = agencia_maritima.replace(/\./g,"").replace(/\,/g, ".");
            agencia_maritima = parseFloat(agencia_maritima);
        }
        if(armazem == "" || $.isEmptyObject(armazem)){
            armazem = 0;
        }else{
            armazem = armazem.replace(/\./g,"").replace(/\,/g, ".");
            armazem = parseFloat(armazem);
        }
        if(laudo == "" || $.isEmptyObject(laudo)){
            laudo = 0;
        }else{
            laudo = laudo.replace(/\./g,"").replace(/\,/g, ".");
            laudo = parseFloat(laudo);
        }
        if(icms_saida == "" || $.isEmptyObject(icms_saida)){
            icms_saida = 0;
        }else{
            icms_saida = icms_saida.replace(/\./g,"").replace(/\,/g, ".");
            icms_saida = parseFloat(icms_saida);
        }
        if(icms_a_pagar == "" || $.isEmptyObject(icms_a_pagar)){
            icms_a_pagar = 0;
        }else{
            icms_a_pagar = icms_a_pagar.replace(/\./g,"").replace(/\,/g, ".");
            icms_a_pagar = parseFloat(icms_a_pagar);
        }
        if(seguro == "" || $.isEmptyObject(seguro)){
            seguro = 0;
        }else{
            seguro = seguro.replace(/\./g,"").replace(/\,/g, ".");
            seguro = parseFloat(seguro);
        }
        if(outras_despesas == "" || $.isEmptyObject(outras_despesas)){
            outras_despesas = 0;
        }else{
            outras_despesas = outras_despesas.replace(/\./g,"").replace(/\,/g, ".");
            outras_despesas = parseFloat(outras_despesas);
        }
        if(total_cambio_realizado == "" || $.isEmptyObject(total_cambio_realizado)){
            total_cambio_realizado = 0;
        }else{
            total_cambio_realizado = total_cambio_realizado.replace(/\./g,"").replace(/\,/g, ".");
            total_cambio_realizado = parseFloat(total_cambio_realizado);
        }
        if(transporte_rodoviario == "" || $.isEmptyObject(transporte_rodoviario)){
            transporte_rodoviario = 0;
        }else{
            transporte_rodoviario = transporte_rodoviario.replace(/\./g,"").replace(/\,/g, ".");
            transporte_rodoviario = parseFloat(transporte_rodoviario);
        }
        if(total_cambio_a_ser_realizado == "" || $.isEmptyObject(total_cambio_a_ser_realizado)){
            total_cambio_a_ser_realizado = 0;
        }else{
            total_cambio_a_ser_realizado = total_cambio_a_ser_realizado.replace(/\./g,"").replace(/\,/g, ".");
            total_cambio_a_ser_realizado = parseFloat(total_cambio_a_ser_realizado);
        }

        custo_total = ii + ipi + pis + cofins + afrmm + taxa_siscomex + sda + honorarios + expediente + valor_li + agencia_maritima + armazem + laudo + icms_a_pagar + seguro + outras_despesas + total_cambio_realizado + transporte_rodoviario;
        
        if(ii_previsto == "" || $.isEmptyObject(ii_previsto)){
            ii_previsto = 0;
        }else{
            ii_previsto = ii_previsto.replace(/\./g,"").replace(/\,/g, ".");
            ii_previsto = parseFloat(ii_previsto);
        }
        if(ipi_previsto == "" || $.isEmptyObject(ipi_previsto)){
            ipi_previsto = 0;
        }else{
            ipi_previsto = ipi_previsto.replace(/\./g,"").replace(/\,/g, ".");
            ipi_previsto = parseFloat(ipi_previsto);
        }
        if(pis_previsto == "" || $.isEmptyObject(pis_previsto)){
            pis_previsto = 0;
        }else{
            pis_previsto = pis_previsto.replace(/\./g,"").replace(/\,/g, ".");
            pis_previsto = parseFloat(pis_previsto);
        }
        if(cofins_previsto == "" || $.isEmptyObject(cofins_previsto)){
            cofins_previsto = 0;
        }else{
            cofins_previsto = cofins_previsto.replace(/\./g,"").replace(/\,/g, ".");
            cofins_previsto = parseFloat(cofins_previsto);
        }
        if(afrmm_previsto == "" || $.isEmptyObject(afrmm_previsto)){
            afrmm_previsto = 0;
        }else{
            afrmm_previsto = afrmm_previsto.replace(/\./g,"").replace(/\,/g, ".");
            afrmm_previsto = parseFloat(afrmm_previsto);
        }
        if(taxa_siscomex_previsto == "" || $.isEmptyObject(taxa_siscomex_previsto)){
            taxa_siscomex_previsto = 0;
        }else{
            taxa_siscomex_previsto = taxa_siscomex_previsto.replace(/\./g,"").replace(/\,/g, ".");
            taxa_siscomex_previsto = parseFloat(taxa_siscomex_previsto);
        }
        if(sda_previsto == "" || $.isEmptyObject(sda_previsto)){
            sda_previsto = 0;
        }else{
            sda_previsto = sda_previsto.replace(/\./g,"").replace(/\,/g, ".");
            sda_previsto = parseFloat(sda_previsto);
        }
        if(honorarios_previsto == "" || $.isEmptyObject(honorarios_previsto)){
            honorarios_previsto = 0;
        }else{
            honorarios_previsto = honorarios_previsto.replace(/\./g,"").replace(/\,/g, ".");
            honorarios_previsto = parseFloat(honorarios_previsto);
        }
        if(expediente_previsto == "" || $.isEmptyObject(expediente_previsto)){
            expediente_previsto = 0;
        }else{
            expediente_previsto = expediente_previsto.replace(/\./g,"").replace(/\,/g, ".");
            expediente_previsto = parseFloat(expediente_previsto);
        }
        if(valor_li_previsto == "" || $.isEmptyObject(valor_li_previsto)){
            valor_li_previsto = 0;
        }else{
            valor_li_previsto = valor_li_previsto.replace(/\./g,"").replace(/\,/g, ".");
            valor_li_previsto = parseFloat(valor_li_previsto);
        }
        if(agencia_maritima_previsto == "" || $.isEmptyObject(agencia_maritima_previsto)){
            agencia_maritima_previsto = 0;
        }else{
            agencia_maritima_previsto = agencia_maritima_previsto.replace(/\./g,"").replace(/\,/g, ".");
            agencia_maritima_previsto = parseFloat(agencia_maritima_previsto);
        }
        if(laudo_previsto == "" || $.isEmptyObject(laudo_previsto)){
            laudo_previsto = 0;
        }else{
            laudo_previsto = laudo_previsto.replace(/\./g,"").replace(/\,/g, ".");
            laudo_previsto = parseFloat(laudo_previsto);
        }
        if(icms_saida_previsto == "" || $.isEmptyObject(icms_saida_previsto)){
            icms_saida_previsto = 0;
        }else{
            icms_saida_previsto = icms_saida_previsto.replace(/\./g,"").replace(/\,/g, ".");
            icms_saida_previsto = parseFloat(icms_saida_previsto);
        }
        if(seguro_previsto == "" || $.isEmptyObject(seguro_previsto)){
            seguro_previsto = 0;
        }else{
            seguro_previsto = seguro_previsto.replace(/\./g,"").replace(/\,/g, ".");
            seguro_previsto = parseFloat(seguro_previsto);
        }
        if(armazenagem_previsto == "" || $.isEmptyObject(armazenagem_previsto)){
            armazenagem_previsto = 0;
        }else{
            armazenagem_previsto = armazenagem_previsto.replace(/\./g,"").replace(/\,/g, ".");
            armazenagem_previsto = parseFloat(armazenagem_previsto);
        }
        if(outras_despesas_previsto == "" || $.isEmptyObject(outras_despesas_previsto)){
            outras_despesas_previsto = 0;
        }else{
            outras_despesas_previsto = outras_despesas_previsto.replace(/\./g,"").replace(/\,/g, ".");
            outras_despesas_previsto = parseFloat(outras_despesas_previsto);
        }
        if(total_previsto == "" || $.isEmptyObject(total_previsto)){
            total_previsto = 0;
        }else{
            total_previsto = total_previsto.replace(/\./g,"").replace(/\,/g, ".");
            total_previsto = parseFloat(total_previsto);
        }
        if(total_cambio_previsto == "" || $.isEmptyObject(total_cambio_previsto)){
            total_cambio_previsto = 0;
        }else{
            total_cambio_previsto = total_cambio_previsto.replace(/\./g,"").replace(/\,/g, ".");
            total_cambio_previsto = parseFloat(total_cambio_previsto);
        }
        
        ii_diferenca = ii - ii_previsto;
        ipi_diferenca = ipi - ipi_previsto;
        pis_diferenca = pis - pis_previsto;
        cofins_diferenca = cofins - cofins_previsto;
        afrmm_diferenca = afrmm - afrmm_previsto;
        taxa_siscomex_diferenca = taxa_siscomex - taxa_siscomex_previsto;
        sda_diferenca = sda - sda_previsto;
        honorarios_diferenca = honorarios - honorarios_previsto;
        expediente_diferenca = expediente - expediente_previsto;
        valor_li_diferenca = valor_li - valor_li_previsto;
        agencia_maritima_diferenca = agencia_maritima - agencia_maritima_previsto;
        laudo_diferenca = armazem - laudo_previsto;
        icms_saida_diferenca = laudo - icms_saida_previsto;
        seguro_diferenca = icms_saida - seguro_previsto;
        armazenagem_diferenca = seguro - armazenagem_previsto;
        outras_despesas_diferenca = outras_despesas - outras_despesas_previsto;
        total_diferenca = custo_total - total_previsto;
        total_cambio_diferenca = total_cambio_realizado - total_cambio_previsto;
        custo_total_a_ser_realizado = custo_total+total_cambio_a_ser_realizado;

        console.log(custo_total);
        form_modal_edt.find("#total_cambio_realizado").html(form_modal_edt.find("#total_cambio").val());
        form_modal_edt.find("#ii_realizado").html(form_modal_edt.find("#ii").val());
        form_modal_edt.find("#ipi_realizado").html(form_modal_edt.find("#ipi").val());
        form_modal_edt.find("#pis_realizado").html(form_modal_edt.find("#pis").val());
        form_modal_edt.find("#cofins_realizado").html(form_modal_edt.find("#cofins").val());
        form_modal_edt.find("#afrmm_realizado").html(form_modal_edt.find("#afrmm").val());
        form_modal_edt.find("#taxa_siscomex_realizado").html(form_modal_edt.find("#taxa_siscomex").val());
        form_modal_edt.find("#sda_realizado").html(form_modal_edt.find("#sda").val());
        form_modal_edt.find("#honorarios_realizado").html(form_modal_edt.find("#honorarios").val());
        form_modal_edt.find("#expediente_realizado").html(form_modal_edt.find("#expediente").val());
        form_modal_edt.find("#valor_li_realizado").html(form_modal_edt.find("#valor_li").val());
        form_modal_edt.find("#agencia_maritima_realizado").html(form_modal_edt.find("#agencia_maritima").val());
        form_modal_edt.find("#laudo_realizado").html(form_modal_edt.find("#laudo").val());
        form_modal_edt.find("#icms_saida_realizado").html(form_modal_edt.find("#icms_saida").val());
        form_modal_edt.find("#seguro_realizado").html(form_modal_edt.find("#seguro").val());
        form_modal_edt.find("#armazenagem_realizado").html(form_modal_edt.find("#armazem").val());
        form_modal_edt.find("#outras_despesas_realizado").html(form_modal_edt.find("#outras_despesas").val());
        form_modal_edt.find("#total_realizado").html(numberToReal(custo_total.toFixed(2))+" / "+numberToReal(custo_total_a_ser_realizado.toFixed(2)));

        ii_previsto = numberToReal(ii_previsto.toFixed(2));
        ipi_previsto = numberToReal(ipi_previsto.toFixed(2));
        pis_previsto = numberToReal(pis_previsto.toFixed(2));
        cofins_previsto = numberToReal(cofins_previsto.toFixed(2));
        afrmm_previsto = numberToReal(afrmm_previsto.toFixed(2));
        taxa_siscomex_previsto = numberToReal(taxa_siscomex_previsto.toFixed(2));
        sda_previsto = numberToReal(sda_previsto.toFixed(2));
        honorarios_previsto = numberToReal(honorarios_previsto.toFixed(2));
        expediente_previsto = numberToReal(expediente_previsto.toFixed(2));
        valor_li_previsto = numberToReal(valor_li_previsto.toFixed(2));
        agencia_maritima_previsto = numberToReal(agencia_maritima_previsto.toFixed(2));
        laudo_previsto = numberToReal(laudo_previsto.toFixed(2));
        icms_saida_previsto = numberToReal(icms_saida_previsto.toFixed(2));
        seguro_previsto = numberToReal(seguro_previsto.toFixed(2));
        armazenagem_previsto = numberToReal(armazenagem_previsto.toFixed(2));
        outras_despesas_previsto = numberToReal(outras_despesas_previsto.toFixed(2));
        total_previsto = numberToReal(total_previsto.toFixed(2));
        total_cambio_previsto = numberToReal(total_cambio_previsto.toFixed(2));

        form_modal_edt.find("#ii_previsto").html(ii_previsto);
        form_modal_edt.find("#ipi_previsto").html(ipi_previsto);
        form_modal_edt.find("#pis_previsto").html(pis_previsto);
        form_modal_edt.find("#cofins_previsto").html(cofins_previsto);
        form_modal_edt.find("#afrmm_previsto").html(afrmm_previsto);
        form_modal_edt.find("#taxa_siscomex_previsto").html(taxa_siscomex_previsto);
        form_modal_edt.find("#sda_previsto").html(sda_previsto);
        form_modal_edt.find("#honorarios_previsto").html(honorarios_previsto);
        form_modal_edt.find("#expediente_previsto").html(expediente_previsto);
        form_modal_edt.find("#valor_li_previsto").html(valor_li_previsto);
        form_modal_edt.find("#agencia_maritima_previsto").html(agencia_maritima_previsto);
        form_modal_edt.find("#laudo_previsto").html(laudo_previsto);
        form_modal_edt.find("#icms_saida_previsto").html(icms_saida_previsto);
        form_modal_edt.find("#seguro_previsto").html(seguro_previsto);
        form_modal_edt.find("#armazenagem_previsto").html(armazenagem_previsto);
        form_modal_edt.find("#outras_despesas_previsto").html(outras_despesas_previsto);
        form_modal_edt.find("#total_previsto").html(total_previsto);
        form_modal_edt.find("#total_cambio_previsto").html(total_cambio_previsto);

        form_modal_edt.find("#ii_diferenca").html(numberToReal(ii_diferenca.toFixed(2)));
        form_modal_edt.find("#ipi_diferenca").html(numberToReal(ipi_diferenca.toFixed(2)));
        form_modal_edt.find("#pis_diferenca").html(numberToReal(pis_diferenca.toFixed(2)));
        form_modal_edt.find("#cofins_diferenca").html(numberToReal(cofins_diferenca.toFixed(2)));
        form_modal_edt.find("#afrmm_diferenca").html(numberToReal(afrmm_diferenca.toFixed(2)));
        form_modal_edt.find("#taxa_siscomex_diferenca").html(numberToReal(taxa_siscomex_diferenca.toFixed(2)));
        form_modal_edt.find("#sda_diferenca").html(numberToReal(sda_diferenca.toFixed(2)));
        form_modal_edt.find("#honorarios_diferenca").html(numberToReal(honorarios_diferenca.toFixed(2)));
        form_modal_edt.find("#expediente_diferenca").html(numberToReal(expediente_diferenca.toFixed(2)));
        form_modal_edt.find("#valor_li_diferenca").html(numberToReal(valor_li_diferenca.toFixed(2)));
        form_modal_edt.find("#agencia_maritima_diferenca").html(numberToReal(agencia_maritima_diferenca.toFixed(2)));
        form_modal_edt.find("#laudo_diferenca").html(numberToReal(laudo_diferenca.toFixed(2)));
        form_modal_edt.find("#icms_saida_diferenca").html(numberToReal(icms_saida_diferenca.toFixed(2)));
        form_modal_edt.find("#seguro_diferenca").html(numberToReal(seguro_diferenca.toFixed(2)));
        form_modal_edt.find("#armazenagem_diferenca").html(numberToReal(armazenagem_diferenca.toFixed(2)));
        form_modal_edt.find("#outras_despesas_diferenca").html(numberToReal(outras_despesas_diferenca.toFixed(2)));
        form_modal_edt.find("#total_diferenca").html(numberToReal(total_diferenca.toFixed(2)));
        form_modal_edt.find("#total_cambio_diferenca").html(numberToReal(total_cambio_diferenca.toFixed(2)));
    }

    function calculoRateio(){
        ii = "{{$dados['ii']}}";
        ipi = "{{$dados['ipi']}}";
        pis = "{{$dados['pis']}}";
        cofins = "{{$dados['cofins']}}";
        afrmm = "{{$dados['afrmm']}}";
        taxa_siscomex = "{{$dados['taxa_siscomex']}}";
        sda = "{{$dados['sda']}}";
        honorarios = "{{$dados['honorarios']}}";
        expediente = "{{$dados['expediente']}}";
        valor_li = "{{$dados['valor_li']}}";
        agencia_maritima = "{{$dados['agencia_maritima']}}";
        armazem = "{{$dados['armazem']}}";
        laudo = "{{$dados['laudo']}}";
        icms_saida = "{{$dados['icms_saida']}}";
        seguro = "{{$dados['seguro']}}";
        outras_despesas = "{{$dados['outras_despesas_total']}}";
        total_cambio_realizado = "{{$dados['total_cambio_realizado']}}";
        transporte_rodoviario = "{{$dados['transporte_rodoviario']}}";
        total_cambio_a_ser_realizado = "{{((parserNumber($dados['total']['preco_fob']) - parserNumber($dados['valor_fob_pago'])) * $dados['cotacao_dolar']) < 0? '' : parserValor(((parserNumber($dados['total']['preco_fob']) - parserNumber($dados['valor_fob_pago'])) * $dados['cotacao_dolar']))}}";
        preco_total_fob = "{{$dados['total']['preco_fob']}}";

        if(ii == "" || $.isEmptyObject(ii)){
            ii = 0;
        }else{
            ii = ii.replace(/\./g,"").replace(/\,/g, ".");
            ii = parseFloat(ii);
        }
        if(ipi == "" || $.isEmptyObject(ipi)){
            ipi = 0;
        }else{
            ipi = ipi.replace(/\./g,"").replace(/\,/g, ".");
            ipi = parseFloat(ipi);
        }
        if(pis == "" || $.isEmptyObject(pis)){
            pis = 0;
        }else{
            pis = pis.replace(/\./g,"").replace(/\,/g, ".");
            pis = parseFloat(pis);
        }
        if(cofins == "" || $.isEmptyObject(cofins)){
            cofins = 0;
        }else{
            cofins = cofins.replace(/\./g,"").replace(/\,/g, ".");
            cofins = parseFloat(cofins);
        }
        if(afrmm == "" || $.isEmptyObject(afrmm)){
            afrmm = 0;
        }else{
            afrmm = afrmm.replace(/\./g,"").replace(/\,/g, ".");
            afrmm = parseFloat(afrmm);
        }
        if(taxa_siscomex == "" || $.isEmptyObject(taxa_siscomex)){
            taxa_siscomex = 0;
        }else{
            taxa_siscomex = taxa_siscomex.replace(/\./g,"").replace(/\,/g, ".");
            taxa_siscomex = parseFloat(taxa_siscomex);
        }
        if(sda == "" || $.isEmptyObject(sda)){
            sda = 0;
        }else{
            sda = sda.replace(/\./g,"").replace(/\,/g, ".");
            sda = parseFloat(sda);
        }
        if(honorarios == "" || $.isEmptyObject(honorarios)){
            honorarios = 0;
        }else{
            honorarios = honorarios.replace(/\./g,"").replace(/\,/g, ".");
            honorarios = parseFloat(honorarios);
        }
        if(expediente == "" || $.isEmptyObject(expediente)){
            expediente = 0;
        }else{
            expediente = expediente.replace(/\./g,"").replace(/\,/g, ".");
            expediente = parseFloat(expediente);
        }
        if(valor_li == "" || $.isEmptyObject(valor_li)){
            valor_li = 0;
        }else{
            valor_li = valor_li.replace(/\./g,"").replace(/\,/g, ".");
            valor_li = parseFloat(valor_li);
        }
        if(agencia_maritima == "" || $.isEmptyObject(agencia_maritima)){
            agencia_maritima = 0;
        }else{
            agencia_maritima = agencia_maritima.replace(/\./g,"").replace(/\,/g, ".");
            agencia_maritima = parseFloat(agencia_maritima);
        }
        if(armazem == "" || $.isEmptyObject(armazem)){
            armazem = 0;
        }else{
            armazem = armazem.replace(/\./g,"").replace(/\,/g, ".");
            armazem = parseFloat(armazem);
        }
        if(laudo == "" || $.isEmptyObject(laudo)){
            laudo = 0;
        }else{
            laudo = laudo.replace(/\./g,"").replace(/\,/g, ".");
            laudo = parseFloat(laudo);
        }
        if(icms_saida == "" || $.isEmptyObject(icms_saida)){
            icms_saida = 0;
        }else{
            icms_saida = icms_saida.replace(/\./g,"").replace(/\,/g, ".");
            icms_saida = parseFloat(icms_saida);
        }
        if(seguro == "" || $.isEmptyObject(seguro)){
            seguro = 0;
        }else{
            seguro = seguro.replace(/\./g,"").replace(/\,/g, ".");
            seguro = parseFloat(seguro);
        }
        if(outras_despesas == "" || $.isEmptyObject(outras_despesas)){
            outras_despesas = 0;
        }else{
            outras_despesas = outras_despesas.replace(/\./g,"").replace(/\,/g, ".");
            outras_despesas = parseFloat(outras_despesas);
        }
        if(total_cambio_realizado == "" || $.isEmptyObject(total_cambio_realizado)){
            total_cambio_realizado = 0;
        }else{
            total_cambio_realizado = total_cambio_realizado.replace(/\./g,"").replace(/\,/g, ".");
            total_cambio_realizado = parseFloat(total_cambio_realizado);
        }
        if(transporte_rodoviario == "" || $.isEmptyObject(transporte_rodoviario)){
            transporte_rodoviario = 0;
        }else{
            transporte_rodoviario = transporte_rodoviario.replace(/\./g,"").replace(/\,/g, ".");
            transporte_rodoviario = parseFloat(transporte_rodoviario);
        }
        if(total_cambio_a_ser_realizado == "" || $.isEmptyObject(total_cambio_a_ser_realizado)){
            total_cambio_a_ser_realizado = 0;
        }else{
            total_cambio_a_ser_realizado = total_cambio_a_ser_realizado.replace(/\./g,"").replace(/\,/g, ".");
            total_cambio_a_ser_realizado = parseFloat(total_cambio_a_ser_realizado);
        }

        custo_total = ii + ipi + pis + cofins + afrmm + taxa_siscomex + sda + honorarios + expediente + valor_li + agencia_maritima + armazem + laudo + icms_a_pagar + seguro + outras_despesas + total_cambio_realizado + transporte_rodoviario + total_cambio_a_ser_realizado;
        
        if(total_previsto == "" || $.isEmptyObject(total_previsto)){
            total_previsto = 0;
        }else{
            total_previsto = total_previsto.replace(/\./g,"").replace(/\,/g, ".");
            total_previsto = parseFloat(total_previsto);
        }
        if(preco_total_fob == "" || $.isEmptyObject(preco_total_fob)){
            preco_total_fob = 0;
        }else{
            preco_total_fob = preco_total_fob.replace(/\./g,"").replace(/\,/g, ".");
            preco_total_fob = parseFloat(preco_total_fob);
        }

        produto_array.forEach(function imprimir(item){
            quantidade = form_modal_edt.find("#valor_contabil_unitario_"+item).data("quantidade");
            quantidade_realizada = form_modal_edt.find("#valor_contabil_unitario_"+item).data("quantidade_realizada");
            preco_fob_total_unitario = form_modal_edt.find("#valor_contabil_unitario_"+item).data("preco_fob_total_unitario");

            if(quantidade == "" || $.isEmptyObject(quantidade)){
                quantidade = 0;
            }else{
                quantidade = quantidade.replace(/\./g,"").replace(/\,/g, ".");
                quantidade = parseFloat(quantidade);
            }

            if(quantidade_realizada == "" || $.isEmptyObject(quantidade_realizada)){
                quantidade_realizada = 0;
            }else{
                quantidade_realizada = quantidade_realizada.replace(/\./g,"").replace(/\,/g, ".");
                quantidade_realizada = parseFloat(quantidade_realizada);
            }
            if(preco_fob_total_unitario == "" || $.isEmptyObject(preco_fob_total_unitario)){
                preco_fob_total_unitario = 0;
            }else{
                preco_fob_total_unitario = preco_fob_total_unitario.replace(/\./g,"").replace(/\,/g, ".");
                preco_fob_total_unitario = parseFloat(preco_fob_total_unitario);
            }

            porcetagem_quantidade = preco_fob_total_unitario/preco_total_fob;
            if(quantidade_realizada == 0){
                form_modal_edt.find("#valor_rateio_"+item).html('');
                form_modal_edt.find("#valor_rateio_previsto_"+item).html('');
            }else{
                porcetagem_quantidade_realizada = preco_fob_total_unitario/preco_total_fob;

                custo_parcial = custo_total * porcetagem_quantidade_realizada;

                valor_rateio = custo_parcial / quantidade_realizada;

                valor_rateio = numberToReal(valor_rateio.toFixed(4));

                form_modal_edt.find("#valor_rateio_"+item).html(valor_rateio);

                custo_parcial_previsto = total_previsto * porcetagem_quantidade;

                valor_rateio_previsto = custo_parcial_previsto / quantidade_realizada;

                valor_rateio_previsto = numberToReal(valor_rateio_previsto.toFixed(4));

                form_modal_edt.find("#valor_rateio_previsto_"+item).html(valor_rateio_previsto);
            }
        });

        total_previsto = numberToReal(total_previsto.toFixed(4));
    }
</script>
@endsection