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
        <a class="nav-link" id="importacao-custo-tab" data-toggle="tab" href="#importacao_custo" role="tab" aria-controls="importacao_custo" aria-selected="false">Custo Realizado</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-custo-realizado-x-previsto-tab" data-toggle="tab" href="#importacao_custo_realizado_x_previsto" role="tab" aria-controls="importacao_custo_realizado_x_previsto" aria-selected="false">Custo Realizado X Previsto</a>
    </li>
</ul>

<form action="" name="form_importacao_edt" id="form_importacao_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', '', ['id' => 'id']) !!}
    {!! Form::hidden('outras_despesas', '', ['id' =>'outras_despesas']) !!}
    {!! Form::hidden('total_cambio', '', ['id' =>'total_cambio']) !!}
    {!! Form::hidden('embarque_realizado', '', ['id' => 'embarque_realizado']) !!}
    {!! Form::hidden('chegada_porto_realizado', '', ['id' => 'chegada_porto_realizado']) !!}
    <div class="tab-content pt-3" id="ImportacaoHeaderContainer">
        <div class="tab-pane show active" id="importacao_dados_gerais" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-6">
                    {!! Form::label('numero_proforma', 'Proforma', []) !!}
                    <div class="input-group">
                        {{ Form::text('numero_proforma', '', ['id' => 'numero_proforma', 'class' => 'form-control input-label', 'placeholder' => 'Proforma']) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-proforma-busca" data-route="{{ route("importacao.modal.buscar_proforma") }}"><i id="bt-view-proforma" class="bt-view m-2"></i></span>
                    </div>
                </div>
                <div class="form-group col-sm-6">
                    {!! Form::label('pedido_compras', 'PCMN', []) !!}
                    <div class="input-group">
                        {{ Form::text('pedido_compras', '', ['id' => 'pedido_compras', 'class' => 'form-control input-label', 'placeholder' => 'Pedido de Compras']) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-pedido_compras-busca" data-route="{{ route("importacao.modal.buscar_proforma") }}"><i id="bt-view-proforma" class="bt-view m-2"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {{ Form::label('fornecedor', 'Fornecedor', []) }} 
                    <div class="input-group">
                        {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedor($(this))"]) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-3">
                    {!! Form::label('referencia', 'Referência', []) !!}
                    {{ Form::text('referencia', '', ['id' => 'referencia', 'class' => 'form-control input-label', 'placeholder' => 'Referência', 'maxlength' => 10, "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) }}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('data_proforma', 'Data da Proforma', []) !!}
                    {{ Form::text('data_proforma', '', ['id' => 'data_proforma', 'class' => 'form-control input-label', 'placeholder' => 'Data da Proforma', 'readonly']) }}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('previsao_carta_programa', 'Previsão da Carta Programa', []) !!}
                    {{ Form::text('previsao_carta_programa', '', ['id' => 'previsao_carta_programa', 'class' => 'form-control input-label data', 'placeholder' => 'Previsão da Carta Programa', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) }}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('previsao_recebimento', 'Previsão de Recebimento', []) !!}
                    <div class=" mt-2">
                        <div class="previsao_recebimento_modificacao"></div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {{ Form::label('respresentante', 'Representante', []) }} 
                    <div class="input-group">
                        {{ Form::text('respresentante', '', ['id' => 'respresentante', 'class' => 'form-control input-label', 'placeholder' => 'Representante', 'onkeyup' => "optionsFornecedor($(this))", "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-respresentante-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_carta_programada', 'Carta Programa(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_carta_programada[]', ['id'=>'arquivo_carta_programada', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
        </div>
        <div class="tab-pane" id="importacao_produtos" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <table class="table table-striped table-filter-dialog" id="table-filters-produtos-proforma">
                    <thead>
                        <tr>
                            <th class="tb_number">Item nº</th>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Composição</th>
                            <th class="tb_number">Gramat. GM2</th>
                            <th class="tb_number">Larg.</th>
                            <th>Gramat. GML</th>
                            <th>Rendimento</th>
                            <th>Inst. de Lavagem</th>
                            <th class="tb_number">QTD</th>
                            <th class="tb_number">Pr. FOB Un.</th>
                            <th class="tb_number">Pr. FOB Tot.</th>
                            <th class="tb_number">Pr. Cont. Un.</th>
                            <th class="tb_number">Pr. Cont. Total</th>
                            <th class="tb_number">Val. Rateio Real.</th>
                            <th class="tb_number">Val. Rateio Prev.</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number"></td>
                            <td class="tb_number"></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number" id="valor_quantidade_total"></td>
                            <td class="tb_number"></td>
                            <td class="tb_number" id="valor_fob_total_total"></td>
                            <td class="tb_number"></td>
                            <td class="tb_number" id="valor_contabil_total_total"></td>
                            <td class="tb_number"></td>
                            <td class="tb_number"></td>
                        </tr>
                    </tfoot>
                </table>
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
                    <td>{!! Form::text('carga_pronta_previsao', '', ['id' => 'carga_pronta_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Carga Pronta Previsão', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                    <td>{!! Form::text('carga_pronta_realizado', '', ['id' => 'carga_pronta_realizado', 'class' => 'form-control input-label data', 'placeholder' => 'Carga Pronta Realizado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                </tr>
                <tr>
                    <td>Embarque ETD</td>
                    <td>{!! Form::text('embarque_previsao', '', ['id' => 'embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Embarque ETD Previsão', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                    <td><div class="data_embarque_realizado"></div></td>
                </tr>
                <tr>
                    <td>Chegada no Porto ETA</td>
                    <td>{!! Form::text('chegada_porto_previsao', '', ['id' => 'chegada_porto_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Chegada no Porto ETA Previsão', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                    <td><div class="data_chegada_porto_realizado"></div></td>
                </tr>  
                <tr>
                    <td>Data DI</td>
                    <td>{!! Form::text('data_di_previsao', '', ['id' => 'data_di_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Data DI Previsão', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                    <td>{!! Form::text('data_di_realizado', '', ['id' => 'data_di_realizado', 'class' => 'form-control input-label data', 'placeholder' => 'Data DI Realizado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                </tr>  
                <tr>
                    <td>Devolução CNTR</td>
                    <td>{!! Form::text('devolucao_cntr_previsao', '', ['id' => 'devolucao_cntr', 'class' => 'form-control input-label data', 'placeholder' => 'Devolução CNTR Previsão', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                    <td>{!! Form::text('devolucao_cntr_realizado', '', ['id' => 'devolucao_cntr', 'class' => 'form-control input-label data', 'placeholder' => 'Devolução CNTR Realizado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}</td>
                </tr>
            </table>
            <hr>
            <br/>
            <br/>
            <b>{!! Form::label('envio_cores', 'Envio das Cores', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('envio_das_cores', 'Tipo de Cor', []) !!}
                    {!! Form::select('envio_das_cores', $dados['tipos_cores'], '', ['id' => 'envio_das_cores', 'class' => 'form-control input-label', 'placeholder' => 'Tipo de Cor']) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_das_cores_previsao', 'Previsão do Envio', []) !!}
                    {!! Form::text('envio_das_cores_previsao', '', ['id' => 'envio_das_cores_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Previsão', 'readonly']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_das_cores_enviado', 'Envio', []) !!}
                    {!! Form::text('envio_das_cores_enviado', '', ['id' => 'envio_das_cores_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Enviado']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_das_cores_recebido', 'Recebido', []) !!}
                    {!! Form::text('envio_das_cores_recebido', '', ['id' => 'envio_das_cores_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Recebido']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_das_cores_revisao', 'Revisão', []) !!}
                    {!! Form::text('envio_das_cores_revisao', '', ['id' => 'envio_das_cores_revisao', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Revisão']) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('quality_sample', 'Quality Sample', ['']) !!}</b>
            <br/>
            <div class="row">   
                <div class="form-group col-sm-2">
                    {!! Form::label('quality_sample_previsao', 'Previsão do Envio', []) !!}
                    {!! Form::text('quality_sample_previsao', '', ['id' => 'quality_sample_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Quality Sample Previsão' , 'readonly']) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('quality_sample_enviado', 'Envio', []) !!}
                    {!! Form::text('quality_sample_enviado', '', ['id' => 'quality_sample_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Quality Sample Enviado']) !!}
                </div>
                
                <div class="form-group col-sm-3">
                    {!! Form::label('quality_sample_transportadora', 'Transportadora', []) !!}
                    {!! Form::text('quality_sample_transportadora', '', ['id' => 'quality_sample_transportadora', 'class' => 'form-control input-label', 'placeholder' => 'Quality Sample Transportadora']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('quality_sample_awb', 'AWB', []) !!}
                    {!! Form::text('quality_sample_awb', '', ['id' => 'quality_sample_awb', 'class' => 'form-control input-label', 'placeholder' => 'Quality Sample AWB']) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('quality_sample_recebido', 'Recebido', []) !!}
                    {!! Form::text('quality_sample_recebido', '', ['id' => 'quality_sample_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Quality Sample Recebido']) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('aprovacao_quality_sample', 'Status da Aprovação', []) !!}
                    {!! Form::select('aprovacao_quality_sample', $dados['tipo_aprovacoes_simples'], '', ['id' => 'aprovacao_quality_sample', 'class' => 'form-control input-label', 'placeholder' => 'Status da Aprovação']) !!}
                </div> 
                <div class="form-group col-sm-3">
                    {!! Form::label('aprovacao_quality_sample_previsao', 'Previsão Aprovação', []) !!}
                    {!! Form::text('aprovacao_quality_sample_previsao', '', ['id' => 'aprovacao_quality_sample_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Quality Sample Previsão', 'readonly']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('aprovacao_quality_sample_previsao', 'Data Aprovação', []) !!}
                    {!! Form::text('aprovacao_quality_sample_realizado', '', ['id' => 'aprovacao_quality_sample_realizado', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Quality Sample Realizado']) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('laboratorio', 'Laboratório(Lab Dip/ Handlooms / Strike Off)', ['id' => 'laboratorio']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_previsao', 'Previsão do Envio', []) !!}
                    {!! Form::text('laboratorio_previsao', '', ['id' => 'laboratorio_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Laboratório(Lab Dip/ Handlooms / Strike Off) Previsão' , 'onchange' => "salvarEmMudanca($(this))", 'readonly']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_enviado', 'Envio', []) !!}
                    {!! Form::text('laboratorio_enviado', '', ['id' => 'laboratorio_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Laboratório(Lab Dip/ Handlooms / Strike Off) Enviado' , 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_recebido', 'Recebido', []) !!}
                    {!! Form::text('laboratorio_recebido', '', ['id' => 'laboratorio_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Laboratório(Lab Dip/ Handlooms / Strike Off) Recebido' , 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('aprovacao_laboratorio', 'Status da Aprovação', []) !!}
                    {!! Form::select('aprovacao_laboratorio', $dados['tipo_aprovacoes_parcial'], '', ['id' => 'aprovacao_laboratorio', 'class' => 'form-control input-label', 'placeholder' => 'Status da Aprovação']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('aprovacao_laboratorio_realizado', 'Data Aprovação', []) !!}
                    {!! Form::text('aprovacao_laboratorio_realizado', '', ['id' => 'aprovacao_laboratorio_realizado', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Laboratório Realizado' , 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('termino_producao', 'Termino da Produção', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('tempo_producao_previsao', 'Previsão do Termino', []) !!}
                    {!! Form::text('tempo_producao_previsao', '', ['id' => 'tempo_producao_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Tempo de Produção Previsão' , 'onchange' => "salvarEmMudanca($(this))", 'readonly']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('tempo_producao', 'Termino', []) !!}
                    {!! Form::text('tempo_producao', '', ['id' => 'tempo_producao', 'class' => 'form-control input-label data', 'placeholder' => 'Tempo de Produção' , 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('amostra_embarque', 'Amostra Embarque', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-2">
                    {!! Form::label('amostra_embarque_previsao', 'Previsão', []) !!}
                    {!! Form::text('amostra_embarque_previsao', '', ['id' => 'amostra_embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Amostra Embarque Previsão', 'readonly']) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('amostra_embarque_enviado', 'Envio', []) !!}
                    {!! Form::text('amostra_embarque_enviado', '', ['id' => 'amostra_embarque_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Amostra Embarque Enviado']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('amostra_embarque_transportadora', 'Transportadora', []) !!}
                    {!! Form::text('amostra_embarque_transportadora', '', ['id' => 'amostra_embarque_transportadora', 'class' => 'form-control input-label', 'placeholder' => 'Amostra Embarque Transportadora']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('amostra_embarque_awb', 'AWB', []) !!}
                    {!! Form::text('amostra_embarque_awb', '', ['id' => 'amostra_embarque_awb', 'class' => 'form-control input-label', 'placeholder' => 'Amostra Embarque AWB']) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('amostra_embarque_recebido', 'Recebido', []) !!}
                    {!! Form::text('amostra_embarque_recebido', '', ['id' => 'amostra_embarque_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Amostra Embarque Recebido' ]) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('aprovacao_amostra_embarque', 'Status da Aprovação', []) !!}
                    {!! Form::select('aprovacao_amostra_embarque', $dados['tipo_aprovacoes_parcial'], '', ['id' => 'aprovacao_amostra_embarque', 'class' => 'form-control input-label', 'placeholder' => 'Status da Aprovação']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('aprovacao_amostra_embarque_previsao', 'Previsão da Aprovação', []) !!}
                    {!! Form::text('aprovacao_amostra_embarque_previsao', '', ['id' => 'aprovacao_amostra_embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Amostra Embarque Previsão','readonly']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('aprovacao_amostra_embarque_enviado', 'Aprovação', []) !!}
                    {!! Form::text('aprovacao_amostra_embarque_enviado', '', ['id' => 'aprovacao_amostra_embarque_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Amostra Embarque Enviado']) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('termino_producao', 'Autorização de Embarque', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('autorizacao_embarque_previsao', 'Previsão do Envio', []) !!}
                    {!! Form::text('autorizacao_embarque_previsao', '', ['id' => 'autorizacao_embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Autorizacao de Embarque Previsão' , 'onchange' => "salvarEmMudanca($(this))", 'readonly']) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('autorizacao_embarque_enviado', 'Envio', []) !!}
                    {!! Form::text('autorizacao_embarque_enviado', '', ['id' => 'autorizacao_embarque_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Autorizacao de Embarque Enviado' , 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
        </div>
        <div class="tab-pane" id="importacao_dados_adicionais_embarque" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('porto_origem', 'Porto Origem', []) !!}
                    {{ Form::text('porto_origem', '', ['id' => 'porto_origem', 'class' => 'form-control input-label', 'placeholder' => 'Porto Origem', "data-tipo" => "string", 'onchange' => "salvarEmMudancaEmbarque($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('porto_destino', 'Porto Destino', []) !!}
                    {{ Form::text('porto_destino', '', ['id' => 'porto_destino', 'class' => 'form-control input-label', 'placeholder' => 'Porto Destino', "data-tipo" => "string", 'onchange' => "salvarEmMudancaEmbarque($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    {!! Form::label('agente_compra', 'Agente de Compras', []) !!}
                    {{ Form::text('agente_compra', '', ['id' => 'agente_compra', 'class' => 'form-control input-label', 'placeholder' => 'Agente de Compras', "data-tipo" => "string", 'onchange' => "salvarEmMudancaEmbarque($(this))"]) }}
                </div>
                <div class="form-group col-sm-6">
                    {!! Form::label('armador', 'Armador', []) !!}
                    {{ Form::text('armador', '', ['id' => 'armador', 'class' => 'form-control input-label', 'placeholder' => 'Armador', "data-tipo" => "string", 'onchange' => "salvarEmMudancaEmbarque($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-3">
                    {!! Form::label('booking', 'Booking', []) !!}
                </div>
                <div class="form-group col-sm-9">
                    {!! Form::label('backing_confirmation', 'Backing Conf.', ['class' => 'form-check-input']) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-3">
                    {{ Form::label('etd_booking', 'ETD', []) }}
                </div>
                <div class="form-group col-sm-9">
                    {{ Form::checkbox('etd_booking', 'etd_booking', '', ['class' => 'form-check-input', 'id'=>'etd_booking']) }}
                    {{ Form::label('label_etd_booking', 'Confirmado ', ['id' => 'label_etd_booking', 'class'=>'form-check-label']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-3">
                    {{ Form::label('eta_booking', 'ETA', []) }}
                </div>
                <div class="form-group col-sm-9">
                    {{ Form::checkbox('eta_booking', 'eta_booking', '', ['class' => 'form-check-input', 'id'=>'eta_booking']) }}
                    {{ Form::label('label_eta_booking', 'Confirmado ', ['id' => 'label_eta_booking', 'class'=>'form-check-label']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    {!! Form::label('numero_bl', 'Nº B/L', []) !!}
                    {{ Form::text('numero_bl', '', ['id' => 'numero_bl', 'class' => 'form-control input-label', 'placeholder' => 'Nº B/L', "data-tipo" => "string", 'onchange' => "salvarEmMudancaEmbarque($(this))"]) }}
                </div>
                <div class="form-group col-sm-6">
                    {!! Form::label('cycle_time', 'Cycle Time', []) !!}
                    {{ Form::text('cycle_time', '', ['id' => 'cycle_time', 'class' => 'form-control input-label text-right dias', 'placeholder' => 'Cycle Time', 'readonly']) }}
                </div>
            </div>
        </div>
        <div class="tab-pane" id="importacao_documentos_processos" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_proforma', 'Proforma(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_proforma[]', ['id'=>'arquivo_proforma', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {{ Form::label('arquivo_conciliator_invoice', 'Comercial Invoice(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_conciliator_invoice[]', ['id'=>'arquivo_conciliator_invoice', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {{ Form::label('arquivo_packing_list', 'Packing List(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_packing_list[]', ['id'=>'arquivo_packing_list', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">

                    {{ Form::label('arquivo_bl', 'B/L(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_bl[]', ['id'=>'arquivo_bl', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_contrato_cambio', 'Contrato de Câmbio(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_contrato_cambio[]', ['id'=>'arquivo_contrato_cambio', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_nf_importacao', 'NF de Importação(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_nf_importacao[]', ['id'=>'arquivo_nf_importacao', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_exoneracao', 'Exoneração(Tamanho máximo 5 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_exoneracao[]', ['id'=>'arquivo_exoneracao', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_nf_remessa', 'NF Remessa(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_nf_remessa[]', ['id'=>'arquivo_nf_remessa', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_di', 'DI(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_di[]', ['id'=>'arquivo_di', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_ci', 'CI(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_ci[]', ['id'=>'arquivo_ci', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_fechamento_processo', 'Fechamento do Processo(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_fechamento_processo[]', ['id'=>'arquivo_fechamento_processo', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_armazenagem', 'Armazenagem(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_armazenagem[]', ['id'=>'arquivo_armazenagem', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('arquivo_afrmm', 'AFRMM(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_afrmm[]', ['id'=>'arquivo_afrmm', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                </div>
            </div>
        </div>
        <div class="tab-pane" id="importacao_financeiro" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('data_embarque_financeiro', 'Data Embarque', []) !!}
                    {{ Form::text('data_embarque_financeiro', '', ['id' => 'data_embarque_financeiro', 'class' => 'form-control data input-label', 'placeholder' => 'Data Embarque', "data-tipo" => "data", 'onchange' => "salvarEmMudancaFinanceiro($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('valor_fob_pago', 'Valor FOB Pago', []) !!}
                    <div class="input-group">
                        {{ Form::text('valor_fob_pago', '', ['id' => 'valor_fob_pago', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Valor FOB Pago', 'readonly']) }}
                        <span class="input-group-addon border rounded-right" data-toggle="tooltip" data-placement="top" title="Lançamentos" id="bt-lancamentos"><i id="bt-view-lancamentos" class="bt-plus-direita m-2"></i></span>
                        <span class="input-group-addon border rounded-right" data-toggle="tooltip" data-placement="top" title="Histórico De Lançamento" id="bt-view_historico_lancamento"><i id="bt-view-historico_lancamento" class="bt-list m-2"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('valor_fob_devido', 'Valor FOB Devido', []) !!}
                    {{ Form::text('valor_fob_devido', '', ['id' => 'valor_fob_devido', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Valor FOB Devido', 'readonly']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('valor_contabil_financeiro', 'Valor Contábil', []) !!}
                    {{ Form::text('valor_contabil_financeiro', '', ['id' => 'valor_contabil_financeiro', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Valor Contábil', 'readonly']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {!! Form::label('debito_credito_financeiro', 'Débito/Crédito', ['id' => 'label_debito_credito_financeiro']) !!}
                    {{ Form::text('debito_credito_financeiro', '', ['id' => 'debito_credito_financeiro', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Débito/Crédito', 'readonly']) }}
                </div>
            </div>
        </div>
        <div class="tab-pane" id="importacao_custo" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('ii', 'II', []) !!}
                    {{ Form::text('ii', '', ['id' => 'ii', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'II', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('ipi', 'IPI', []) !!}
                    {{ Form::text('ipi', '', ['id' => 'ipi', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'ipi', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('pis', 'PIS', []) !!}
                    {{ Form::text('pis', '', ['id' => 'pis', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'PIS', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('cofins', 'COFINS', []) !!}
                    {{ Form::text('cofins', '', ['id' => 'cofins', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'COFINS', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('afrmm', 'AFRMM', []) !!}
                    {{ Form::text('afrmm', '', ['id' => 'afrmm', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'AFRMM', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('taxa_siscomex', 'Taxa Siscomex', []) !!}
                    {{ Form::text('taxa_siscomex', '', ['id' => 'taxa_siscomex', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Taxa Siscomex', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('sda', 'SDA', []) !!}
                    {{ Form::text('sda', '', ['id' => 'sda', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'SDA', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('honorarios', 'Honorários', []) !!}
                    {{ Form::text('honorarios', '', ['id' => 'honorarios', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Honorários', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('expediente', 'Expediente', []) !!}
                    {{ Form::text('expediente', '', ['id' => 'expediente', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Expediente', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('valor_li', 'Valor Li', []) !!}
                    {{ Form::text('valor_li', '', ['id' => 'valor_li', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Valor Li', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('agencia_maritima', 'Agência Marítima', []) !!}
                    {{ Form::text('agencia_maritima', '', ['id' => 'agencia_maritima', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Agência Marítima']) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('armazem', 'Armazenagem', []) !!}
                    {{ Form::text('armazem', '', ['id' => 'armazem', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Armazém', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('laudo', 'Laudo', []) !!}
                    {{ Form::text('laudo', '', ['id' => 'laudo', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Laudo', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('icms_saida', 'ICMS Saída', []) !!}
                    {{ Form::text('icms_saida', '', ['id' => 'icms_saida', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'ICMS Saída', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('transporte_rodoviario', 'Transporte Rodoviário', []) !!}
                    {{ Form::text('transporte_rodoviario', '', ['id' => 'transporte_rodoviario', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Transporte Rodoviário', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('seguro', 'Seguro', []) !!}
                    {{ Form::text('seguro', '', ['id' => 'seguro', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Seguro', "data-tipo" => "numerico", 'onchange' => "salvarEmMudancaCusto($(this))"]) }}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('descricao_outras_despesas', 'Outras Despesas', []) !!}
                    {{ Form::text('descricao_outras_despesas', '', ['id' => 'descricao_outras_despesas', 'class' => 'form-control input-label', 'placeholder' => 'Descrição Outras Despesas']) }}
                </div>
                <div class="form-group col-sm-4">
                    </br>
                    <div class="input-group" id="usuario_group">
                        {{ Form::text('outras_despesas_valor', '', ['id' => 'outras_despesas_valor', 'class' => 'form-control  decimal text-right input-label', 'placeholder' => 'Valor Outras Despesas']) }}
                        <span class="input-group-addon border-right border-top border-bottom rounded-right btn-line-add-span">
                            <i class="btn-line-add rounded-right" id="btn-add_despesa"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped" id="table-outras_despesas">
                        <thead>
                            <th>Descrição Outra Despesa</th>
                            <th class="tb_number">Valor</th>
                            <th class="td_acao"></th>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number" id='outras_despesas_total'></td>
                            <td class="td_acao"></td>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="importacao_custo_realizado_x_previsto" role="tabpanel" aria-labelledby="dados-tab">
            <table class="table">
                <tr>
                    <th class="text-center"></th>
                    <th class="text-center">Realizado</th>
                    <th class="text-center">Previsto</th>
                    <th class="text-center">Diferença</th>
                </tr>
                <tr>
                    <td>Total Cambio(R$)</td>
                    <td class="text-right border-right" id="total_cambio_realizado"></td>
                    <td class="text-right border-right" id="total_cambio_previsto"></td>
                    <td class="text-right border-right" id="total_cambio_diferenca"></td>
                </tr>
                <tr>
                    <td>II</td>
                    <td class="text-right border-right" id="ii_realizado"></td>
                    <td class="text-right border-right" id="ii_previsto"></td>
                    <td class="text-right border-right" id="ii_diferenca"></td>
                </tr>
                <tr>
                    <td>IPI</td>
                    <td class="text-right border-right" id="ipi_realizado"></td>
                    <td class="text-right border-right" id="ipi_previsto"></td>
                    <td class="text-right border-right" id="ipi_diferenca"></td>
                </tr> 
                <tr>
                    <td>PIS</td>
                    <td class="text-right border-right" id="pis_realizado"></td>
                    <td class="text-right border-right" id="pis_previsto"></td>
                    <td class="text-right border-right" id="pis_diferenca"></td>
                </tr> 
                <tr>
                    <td>COFINS</td>
                    <td class="text-right border-right" id="cofins_realizado"></td>
                    <td class="text-right border-right" id="cofins_previsto"></td>
                    <td class="text-right border-right" id="cofins_diferenca"></td>
                </tr> 
                <tr>
                    <td>AFRMM</td>
                    <td class="text-right border-right" id="afrmm_realizado"></td>
                    <td class="text-right border-right" id="afrmm_previsto"></td>
                    <td class="text-right border-right" id="afrmm_diferenca"></td>
                </tr> 
                <tr>
                    <td>Taxa Siscomex</td>
                    <td class="text-right border-right" id="taxa_siscomex_realizado"></td>
                    <td class="text-right border-right" id="taxa_siscomex_previsto"></td>
                    <td class="text-right border-right" id="taxa_siscomex_diferenca"></td>
                </tr> 
                <tr>
                    <td>SDA</td>
                    <td class="text-right border-right" id="sda_realizado"></td>
                    <td class="text-right border-right" id="sda_previsto"></td>
                    <td class="text-right border-right" id="sda_diferenca"></td>
                </tr> 
                <tr>
                    <td>Honorários</td>
                    <td class="text-right border-right" id="honorarios_realizado"></td>
                    <td class="text-right border-right" id="honorarios_previsto"></td>
                    <td class="text-right border-right" id="honorarios_diferenca"></td>
                </tr> 
                <tr>
                    <td>Expediente</td>
                    <td class="text-right border-right" id="expediente_realizado"></td>
                    <td class="text-right border-right" id="expediente_previsto"></td>
                    <td class="text-right border-right" id="expediente_diferenca"></td>
                </tr> 
                <tr>
                    <td>Valor Li</td>
                    <td class="text-right border-right" id="valor_li_realizado"></td>
                    <td class="text-right border-right" id="valor_li_previsto"></td>
                    <td class="text-right border-right" id="valor_li_diferenca"></td>
                </tr> 
                <tr>
                    <td>Agência Marítima</td>
                    <td class="text-right border-right" id="agencia_maritima_realizado"></td>
                    <td class="text-right border-right" id="agencia_maritima_previsto"></td>
                    <td class="text-right border-right" id="agencia_maritima_diferenca"></td>
                </tr> 
                <tr>
                    <td>Armazenagem</td>
                    <td class="text-right border-right" id="armazenagem_realizado"></td>
                    <td class="text-right border-right" id="armazenagem_previsto"></td>
                    <td class="text-right border-right" id="armazenagem_diferenca"></td>
                </tr> 
                <tr>
                    <td>Laudo</td>
                    <td class="text-right border-right" id="laudo_realizado"></td>
                    <td class="text-right border-right" id="laudo_previsto"></td>
                    <td class="text-right border-right" id="laudo_diferenca"></td>
                </tr> 
                <tr>
                    <td>ICMS Saída</td>
                    <td class="text-right border-right" id="icms_saida_realizado"></td>
                    <td class="text-right border-right" id="icms_saida_previsto"></td>
                    <td class="text-right border-right" id="icms_saida_diferenca"></td>
                </tr>
                <tr>
                    <td>Transporte Rodoviário</td>
                    <td class="text-right border-right" id="transporte_rodoviario_realizado"></td>
                    <td class="text-right border-right" id="transporte_rodoviario_previsto"></td>
                    <td class="text-right border-right" id="transporte_rodoviario_diferenca"></td>
                </tr> 
                <tr>
                    <td>Seguro</td>
                    <td class="text-right border-right" id="seguro_realizado"></td>
                    <td class="text-right border-right" id="seguro_previsto"></td>
                    <td class="text-right border-right" id="seguro_diferenca"></td>
                </tr> 
                <tr>
                    <td>Outras Despesas</td>
                    <td class="text-right border-right" id="outras_despesas_realizado"></td>
                    <td class="text-right border-right" id="outras_despesas_previsto"></td>
                    <td class="text-right border-right" id="outras_despesas_diferenca"></td>
                </tr>
                <tr>
                    <td>Total</td>
                    <td class="text-right border-right" id="total_realizado"></td>
                    <td class="text-right border-right" id="total_previsto"></td>
                    <td class="text-right border-right" id="total_diferenca"></td>
                </tr>  
            </table>
        </div>
        <div class="col-sm-12 mt-5" id="button-bottom">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
        </div> 
    </div>
</form>
<script>
    produto_array = [];
    total_preco_fob = "0,00";
    quantidade_total = 0;
    ii_previsto = "";
    ipi_previsto = "";
    pis_previsto = "";
    cofins_previsto = "";
    afrmm_previsto = "";
    taxa_siscomex_previsto = "";
    sda_previsto = "";
    honorarios_previsto = "";
    expediente_previsto = "";
    valor_li_previsto = "";
    agencia_maritima_previsto = "";
    laudo_previsto = "";
    icms_saida_previsto = "";
    seguro_previsto = "";
    armazenagem_previsto = "";
    outras_despesas_previsto = "";
    total_previsto = "";
    total_cambio_previsto = "";
    transporte_rodoviario_previsto = "";

    $(document).ready( function () {
        form_modal_importacao = $(document).find('#form_importacao_edt');

        form_modal_importacao.find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores", "fornecedor");
        });
        form_modal_importacao.find("#bt-search-respresentante-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores", "respresentante");
        });
        form_modal_importacao.find("#bt-search-proforma-busca").on("click", function(){
            showModalBuscarProforma($(this).data("route"), "Lista de Proformas");
        });
        form_modal_importacao.find("#bt-search-pedido_compras-busca").off("click");
        form_modal_importacao.find("#bt-search-pedido_compras-busca").on("click", function(){
            showModalBuscarProforma($(this).data("route"), "Lista de Proformas");
        });
        form_modal_importacao.find("#numero_proforma").off("change");
        form_modal_importacao.find("#numero_proforma").on("change", function(){
            returnDadosComplemetaresProforma();
        });
        form_modal_importacao.find("#pedido_compras").off("change");
        form_modal_importacao.find("#pedido_compras").on("change", function(){
            returnDadosComplemetaresPCMN();
        });
        
        form_modal_importacao.find("#bt-view_historico_lancamento").off("click");
        form_modal_importacao.find("#bt-view_historico_lancamento").on("click", function () {
            showModalFinanceiroLancamento('historico');
        });

        form_modal_importacao.find("#bt-view-lancamentos").off("click");
        form_modal_importacao.find("#bt-view-lancamentos").on("click", function () {
            showModalFinanceiroLancamento('lancamento');
        });

        form_modal_importacao.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_modal_importacao.find('.data').mask('00/00/0000');
        form_modal_importacao.find(".dias").mask('000');

        form_modal_importacao.find(".decimal").maskMoney({thousands:'.', decimal:','});

        form_modal_importacao.find("#numero_proforma").autocomplete(optionsAutoCompleteProforma());
        form_modal_importacao.find("#aprovado_embarque_produto").autocomplete(optionsAutoCompleteProduto(form_modal_importacao));

        table_produtos_proforma_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "45vh",
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

        form_modal_importacao.find("#bt-buscar-produto").off("click");
        form_modal_importacao.find("#bt-buscar-produto").on("click", function(){
            showModalProdutoEdt();
        });

        form_modal_importacao.find("#aprovado_embarque_produto_codigo").off('blur');
        form_modal_importacao.find("#aprovado_embarque_produto_codigo").on('blur', function(){
            if(form_modal_importacao.find("#aprovado_embarque_produto_codigo").val() != ''){
                retornarDescricaoProduto(form_modal_importacao);
            }
        });

        form_modal_importacao.find("#btn-salvar").off('click');
        form_modal_importacao.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_importacao.serialize());
        });

        form_modal_importacao.find("#btn-add_despesa").off('click');
        form_modal_importacao.find("#btn-add_despesa").on('click', function(){
            adicionarDepesas(form_modal_importacao);
        });

        $(document).find("#importacao-custo-realizado-x-previsto-tab").off("click");
        $(document).find("#importacao-custo-realizado-x-previsto-tab").on("click", function(){
            resultado(form_modal_importacao);
        });

        form_modal_importacao.find("#etd_booking").off('click');
        form_modal_importacao.find("#etd_booking").on('click', function(){
            form_modal_importacao.find("#label_etd_booking").html('Confirmado');
            form_modal_importacao.find('#embarque_realizado').val('');
            form_modal_importacao.find('.data_embarque_realizado').html('');

            if(form_modal_importacao.find("#etd_booking").is(':checked')){
                if(form_modal_importacao.find("#carga_pronta_realizado").val() != "" && !$.isEmptyObject(form_modal_importacao.find("#carga_pronta_realizado").val())){
                    showModalDataEtdEta('embarque_etd');
                    form_modal_importacao.find("#etd_booking").prop('checked', false);
                }else{
                    message("Atenção", "Precisa informar a data Carga Pronta Realizada antes!");
                    $(document).find("#importacao-follow_up-lead_time-tab").tab("show");
                    form_modal_importacao.find("#etd_booking").prop('checked', false);
                }
            }

            calculoCycleTime()
        });

        form_modal_importacao.find("#eta_booking").off('click');
        form_modal_importacao.find("#eta_booking").on('click', function(){
            form_modal_importacao.find("#label_eta_booking").html('Confirmado');
            form_modal_importacao.find('.data_chegada_porto_realizado').html('');
            form_modal_importacao.find('#chegada_porto_realizado').val('');

            if(form_modal_importacao.find("#eta_booking").is(':checked')){
                if(form_modal_importacao.find("#embarque_realizado").val() != "" && !$.isEmptyObject(form_modal_importacao.find("#embarque_realizado").val())){
                    showModalDataEtdEta('chegada_eta');
                    form_modal_importacao.find("#eta_booking").prop('checked', false);
                }else if(form_modal_importacao.find("#carga_pronta_realizado").val() == "" || $.isEmptyObject(form_modal_importacao.find("#carga_pronta_realizado").val())){
                    message("Atenção", "Precisa informar a data Carga Pronta Realizada antes!");
                    $(document).find("#importacao-follow_up-lead_time-tab").tab("show");
                    form_modal_importacao.find("#eta_booking").prop('checked', false);
                }else{
                    message("Atenção", "Precisa confirmar o ETD do Booking antes!");
                    form_modal_importacao.find("#eta_booking").prop('checked', false);
                }
            }
            
            calculoCycleTime()
        });

        calculoCycleTime()

        $(document).find("#importacao-produtos-tab").off("click");
        $(document).find("#importacao-produtos-tab").on("click", function(){
            calculoRateio();
        });

        form_modal_importacao.find("#envio_das_cores").off("change");
        form_modal_importacao.find("#envio_das_cores").on("change", function(){
            previsacaoFollowUp(form_modal_importacao);
        });
        
        form_modal_importacao.find("#quality_sample_recebido").off("change");
        form_modal_importacao.find("#quality_sample_recebido").on("change", function(){
            previsaoAprovacaoQualitySample(form_modal_importacao); 
        }); 
         
        form_modal_importacao.find("#aprovacao_laboratorio").off("change");
        form_modal_importacao.find("#aprovacao_laboratorio").on("change", function(){
            previsaoTerminoProducao(form_modal_importacao); 
            previsaoAmostraEmbarque(form_modal_importacao);
        }); 
        
        form_modal_importacao.find("#aprovacao_laboratorio_realizado").off("change");
        form_modal_importacao.find("#aprovacao_laboratorio_realizado").on("change", function(){
            previsaoTerminoProducao(form_modal_importacao); 
            previsaoAmostraEmbarque(form_modal_importacao);
        }); 

        form_modal_importacao.find("#amostra_embarque_recebido").off("change");
        form_modal_importacao.find("#amostra_embarque_recebido").on("change", function(){
            previsaoAprovacaoAmostraEmbarque(form_modal_importacao); 
            previsaoAutorizacaoEmbarque(form_modal_importacao);
        }); 

        $(document).find("#importacao-produtos-tab").off("click");
        $(document).find("#importacao-produtos-tab").on("click", function(){
            setTimeout(function(){
                table_produtos_proforma.draw(false);
            }, 100);
        });
    });

    function inserirDados(data_form_modal_importacao){
        var formData = new FormData($(document).find('#form_importacao_edt')[0]);
        $.ajax({
            url: "{{ route('importacao.adicionar') }}", 
            dataType: 'json',
            data: formData,
            method: 'POST',
            processData: false,
            contentType: false,
            success: function(callback){
                $(form_modal_importacao).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
                message("Atenção", "Dados salvo com Sucesso!");
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function limparMesagemErroEdt(){      
        var form_modal_importacao = $("#form_importacao_edt");
        form_modal_importacao.find('.error-message').remove();
        form_modal_importacao.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroEdt(json_error){
        var form_modal_importacao = $("#form_importacao_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_importacao, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdt(form_modal_importacao, input, message){
        if(input.localeCompare('fornecedor') == 0){
            var $input = $(form_modal_importacao).find("#bt-search-fornecedor-busca");
            $(form_modal_importacao).find("input[name='fornecedor']").addClass('error-input');
        }else if(input.localeCompare('numero_proforma') == 0){
            var $input = form_modal_importacao.find("#bt-search-proforma-busca");
            $(form_modal_importacao).find("input[name='numero_proforma']").addClass('error-input');
        }else if(input.localeCompare('respresentante') == 0){
            var $input = form_modal_importacao.find("#bt-search-respresentante-busca");
            $(form_modal_importacao).find("input[name='respresentante']").addClass('error-input');
        }else if(input.localeCompare('chegada_porto_realizado') == 0){
            var $input = form_modal_importacao.find("#label_eta_booking");
        }else if(input.localeCompare('embarque_realizado') == 0){
            var $input = form_modal_importacao.find("#label_etd_booking");
        }else{
            if(input.indexOf("arquivo") != -1){
                var $input = $(form_modal_importacao).find("input[name='"+input+"[]'], select[name='"+input+"']");
            }else{
                var $input = $(form_modal_importacao).find("input[name='"+input+"'], select[name='"+input+"']");
            }
            
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');

        abaComErro(input);
    }

    function showModalFornecedor(url, title, campo){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedor($(this), campo);
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedor($this, campo){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        form_modal_importacao.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }

    function optionsFornecedor($this){
        esconderPopoverTooltip();
        $this.autocomplete(optionsAutoCompleteFornecedor($this));   
    }

    function optionsAutoCompleteFornecedor($this){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('fornecedor.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_importacao_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum fornecedor encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $this.val(ui.item.label);
                return false;
            }
        };
    }

    function showModalBuscarProforma(url, title){
        form_modal_importacao = $(document).find("#form_importacao_edt");
        fornecedor = form_modal_importacao.find("#fornecedor").val();
        $.ajax({
            url: url,
            method: 'POST',
            data:{
                _token: '{{csrf_token()}}',
                fornecedor: fornecedor
            },
            success: function(body){
                createModal("proforma_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#proforma_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#proforma_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosBuscarProforma($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosBuscarProforma($this, campo){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#proforma_search_show").modal("hide");
        form_modal_importacao.find("#numero_proforma").val($this.find("td").eq(3).text());
        returnDadosComplemetaresProforma();
    }

    function createLinkModificarData($this){
        var html = "";
        if($this.id_nota !== ""){
            html = "<a href='#' onclick=\"showModalData('" 
            + $this.id_nota + "', '"
            + $this.estabelecimento + "', '"
            + $(document).find("#form_importacao_edt").find('#data_proforma').val() + "" 
            + "')\">" + $this.previsao_entrega + "</a>";
        }
        return html;
    }

    function showModalData(id, unidade, data){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.modal')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                unidade: unidade,
                data: data
            },
            success: function(body){
                createModal("alterar-data-modal", "Alterar data de previsão de recebimento", body, 'as-das-das');
    
                var tamanho = 240;
                $(document).find('#alterar-data-modal').find('.modal-dialog').css('max-height', tamanho+'px');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }
    
        });
    }

    function optionsAutoCompleteProforma(){
        $(document).find(".error-message").remove();
        form_modal_importacao = $(document).find("#form_importacao_edt");
        fornecedor = form_modal_importacao.find("#fornecedor").val();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.fornecedor = fornecedor;
                $.post("{{ route('importacao.autocomplete_proforma') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_importacao_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum fornecedor encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal_importacao.find('#numero_proforma').val(ui.item.label);
                returnDadosComplemetaresProforma();
                return false;
            }
        };
    }

    function returnDadosComplemetaresProforma(){
        form_modal_importacao = $(document).find("#form_importacao_edt");
        data_form_modal_importacao = form_modal_importacao.serialize();
        $.ajax({
            url: '{{ route('importacao.retorno_dados_proforma')}}',
            data: data_form_modal_importacao,
            method: 'POST',
            success: function(callback){
                table_produtos_proforma.clear();
                
                if(form_modal_importacao.find('#fornecedor').val() == '' || $.isEmptyObject(form_modal_importacao.find('#fornecedor'))){
                    form_modal_importacao.find('#fornecedor').val(callback.response.fornecedor_nome+" - "+callback.response.fornecedor_cnpj);
                }
                form_modal_importacao.find('#pedido_compras').val(callback.response.numero_pedido);
                form_modal_importacao.find('#data_proforma').val(callback.response.data_compra);
                form_modal_importacao.find('.previsao_recebimento_modificacao').html(createLinkModificarData(callback.response));

                var linhas = [];
                produto_array = [];
                quantidade_total = 0;
                for(var field in callback.response.tabela_produtos){
                    var linha = [
                        callback.response.tabela_produtos[field].item,
                        callback.response.tabela_produtos[field].codigo,
                        changeTextOverflow(callback.response.tabela_produtos[field].produto),
                        changeTextOverflow(callback.response.tabela_produtos[field].composicao),
                        callback.response.tabela_produtos[field].gramatura_gm2,
                        callback.response.tabela_produtos[field].largura,
                        callback.response.tabela_produtos[field].gramatura_gml,
                        callback.response.tabela_produtos[field].rendimento,
                        callback.response.tabela_produtos[field].instrucao_lavagem,
                        callback.response.tabela_produtos[field].quantidade,
                        callback.response.tabela_produtos[field].preco_unitario,
                        callback.response.tabela_produtos[field].preco_total,
                        campoPrecoContabil(callback.response.tabela_produtos[field]),
                        divValorTotalContabil(callback.response.tabela_produtos[field]),
                        divValorRateio(callback.response.tabela_produtos[field]),
                        divValorRateioPrevisto(callback.response.tabela_produtos[field]),
                    ];
                    linhas.push(linha);
                    produto_array.push(callback.response.tabela_produtos[field].codigo);

                    quantidade = callback.response.tabela_produtos[field].quantidade;
                    quantidade = quantidade.replace(/\./g,"").replace(/\,/g, ".");
                    quantidade = parseFloat(quantidade);

                    quantidade_total += quantidade;
                }

                $('.dataTables_scrollFootInner').find("#valor_quantidade_total").html(callback.response.total.quantidade);
                $('.dataTables_scrollFootInner').find("#valor_fob_total_total").html(callback.response.total.preco_fob);
                form_modal_importacao.find("#valor_fob_devido").val(callback.response.total.preco_fob);

                table_produtos_proforma.rows.add(linhas).draw();
                form_modal_importacao.find(".decimal").maskMoney({thousands:'.', decimal:','});

                salvaProforma();
                calculoRateio();
            },
            error: function(callback){

            }
        });
    }
    
    function returnDadosComplemetaresPCMN(){
        form_modal_importacao = $(document).find("#form_importacao_edt");
        data_form_modal_importacao = form_modal_importacao.serialize();
        $.ajax({
            url: '{{ route('importacao.retorno_dados_proforma')}}',
            data: data_form_modal_importacao,
            method: 'POST',
            success: function(callback){
                table_produtos_proforma.clear();
                
                if(form_modal_importacao.find('#fornecedor').val() == '' || $.isEmptyObject(form_modal_importacao.find('#fornecedor'))){
                    form_modal_importacao.find('#fornecedor').val(callback.response.fornecedor_nome+" - "+callback.response.fornecedor_cnpj);
                }
                form_modal_importacao.find('#numero_proforma').val(callback.response.proforma);
                form_modal_importacao.find('#pedido_compras').val(callback.response.numero_pedido);
                form_modal_importacao.find('#data_proforma').val(callback.response.data_compra);
                form_modal_importacao.find('.previsao_recebimento_modificacao').html(createLinkModificarData(callback.response));

                var linhas = [];
                produto_array = [];
                quantidade_total = 0;
                for(var field in callback.response.tabela_produtos){
                    var linha = [
                        callback.response.tabela_produtos[field].item,
                        callback.response.tabela_produtos[field].codigo,
                        changeTextOverflow(callback.response.tabela_produtos[field].produto),
                        changeTextOverflow(callback.response.tabela_produtos[field].composicao),
                        callback.response.tabela_produtos[field].gramatura_gm2,
                        callback.response.tabela_produtos[field].largura,
                        callback.response.tabela_produtos[field].gramatura_gml,
                        callback.response.tabela_produtos[field].rendimento,
                        callback.response.tabela_produtos[field].instrucao_lavagem,
                        callback.response.tabela_produtos[field].quantidade,
                        callback.response.tabela_produtos[field].preco_unitario,
                        callback.response.tabela_produtos[field].preco_total,
                        campoPrecoContabil(callback.response.tabela_produtos[field]),
                        divValorTotalContabil(callback.response.tabela_produtos[field]),
                        divValorRateio(callback.response.tabela_produtos[field]),
                        divValorRateioPrevisto(callback.response.tabela_produtos[field]),
                    ];
                    linhas.push(linha);
                    produto_array.push(callback.response.tabela_produtos[field].codigo);

                    quantidade = callback.response.tabela_produtos[field].quantidade;
                    quantidade = quantidade.replace(/\./g,"").replace(/\,/g, ".");
                    quantidade = parseFloat(quantidade);

                    quantidade_total += quantidade;
                }

                $('.dataTables_scrollFootInner').find("#valor_quantidade_total").html(callback.response.total.quantidade);
                $('.dataTables_scrollFootInner').find("#valor_fob_total_total").html(callback.response.total.preco_fob);
                form_modal_importacao.find("#valor_fob_devido").val(callback.response.total.preco_fob);

                table_produtos_proforma.rows.add(linhas).draw();
                form_modal_importacao.find(".decimal").maskMoney({thousands:'.', decimal:','});

                salvaProforma();
                calculoRateio();
            },
            error: function(callback){

            }
        });
    }

    function changeTextOverflow(dados){
        $dados = "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+dados+"\">"+dados+"</div></div>";
        return $dados;
    }

    function campoPrecoContabil(dados){
        html = '<td><input id="valor_contabil_unitario_'+dados.codigo+'" data-codigo="'+dados.codigo+'" data-quantidade="'+dados.quantidade+'" class="form-control decimal_quatro_casas text-right" placeholder="Valor Contábil" maxlength="20" name="valor_contabil_unitario[]" type="text" value="" autocomplete="off" onkeyup="valorContabilUnitarioTotal($(this))"></td>';

        return html;
    }

    function divValorTotalContabil(dados){
        html = '<div id="valor_contabil_total_'+dados.codigo+'" name="valor_contabil_total_'+dados.codigo+'"></div>';

        return html;
    }
    
    function divValorRateio(dados){
        html = '<div id="valor_rateio_'+dados.codigo+'" name="valor_rateio_'+dados.codigo+'"></div>';

        return html;
    }

    function divValorRateioPrevisto(dados){
        html = '<div id="valor_rateio_previsto_'+dados.codigo+'" name="valor_rateio_previsto_'+dados.codigo+'"></div>';

        return html;
    }

    function showModalProdutoEdt(){
        esconderPopoverTooltip();
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosProdutoEdt($(this).parent('tr'));
                        });
                    });
                });
            }
        });
    }

    function returnDadosProdutoEdt($dados){
		form_modal_importacao = $(document).find("#form_importacao_edt");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");

        form_modal_importacao.find('#aprovado_embarque_produto_codigo').val($dados.find("td").eq(1).text());
        form_modal_importacao.find('#aprovado_embarque_produto').val($dados.find("td").eq(2).text());
    }

    function retornarDescricaoProduto(form_modal_importacao){
        esconderPopoverTooltip();
        aprovado_embarque_produto_codigo = form_modal_importacao.find('#aprovado_embarque_produto_codigo').val();
        $.ajax({
            url: '{{ route('lancamento_projeto.produto.retorna_descricao')}}',
            data: {
                _token: '{{ csrf_token() }}',
                produto_codigo : aprovado_embarque_produto_codigo
            },
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoEdt(produto, form_modal_importacao);
            },
            error: function(callback){
                if(form_modal_importacao.find('#aprovado_embarque_produto_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_importacao.find('#aprovado_embarque_produto_codigo').val("")
                    form_modal_importacao.find('#aprovado_embarque_produto_codigo').focus();
                    form_modal_importacao.find("#aprovado_embarque_produto").val("");
                    limparMesagemErroEdt(form_modal_importacao);
                    mensagemErroEdt(dados, form_modal_importacao);
                }
            }
        });
    }

    function dadosRetornoEdt(produto, form_modal_importacao){
        if(form_modal_importacao.find('#aprovado_embarque_produto_codigo').val() === ''){
            form_modal_importacao.find('#aprovado_embarque_produto').focus();
        }else{
            form_modal_importacao.find('#aprovado_embarque_produto').val(produto.nome);
            form_modal_importacao.find("#aprovado_embarque_produto_codigo").val(produto.codigo);
        }
    }

    function optionsAutoCompleteProduto(form_modal_importacao){
        esconderPopoverTooltip();
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_modal_importacao.find("#tecido_estabelecimento").val();
                $.post("{{ route('produto.tecido.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_importacao_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    form_modal_importacao.find("#aprovado_embarque_produto").val('');
                    form_modal_importacao.find("#aprovado_embarque_produto_codigo").val("");
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal_importacao.find("#aprovado_embarque_produto").val(ui.item.label);
                form_modal_importacao.find("#aprovado_embarque_produto_codigo").val(ui.item.value);
                return false;
            }
        };
    }
    
    function valorContabilUnitarioTotal($this){
        var codigo = $this.data("codigo");
        var quantidade = $this.data("quantidade");
        var valor_contabil = $this.val();
        var valor_contabil_total = 0;

        quantidade = quantidade.replace(/\./g,"").replace(/\,/g, ".");
        quantidade = parseFloat(quantidade);

        if(valor_contabil == "" || $.isEmptyObject(valor_contabil)){
            valor_contabil = 0;
        }else{
            valor_contabil = valor_contabil.replace(/\./g,"").replace(/\,/g, ".");
            valor_contabil = parseFloat(valor_contabil);
        }

        valor_contabil_total = valor_contabil * quantidade;

        if(valor_contabil_total == 0){
            valor_contabil_total = '';
        }else{
            valor_contabil_total = numberToReal(valor_contabil_total.toFixed(2));
        }

        form_modal_importacao.find("#valor_contabil_total_"+codigo).html(valor_contabil_total);
        
        valor_contabil_total_total = 0;

        produto_array.forEach(function imprimir(item){
            quantidade = form_modal_importacao.find("#valor_contabil_unitario_"+item).data("quantidade");
            valor_contabil = form_modal_importacao.find("#valor_contabil_unitario_"+item).val();

            quantidade = quantidade.replace(/\./g,"").replace(/\,/g, ".");
            quantidade = parseFloat(quantidade);

            if(valor_contabil == "" || $.isEmptyObject(valor_contabil)){
                valor_contabil = 0;
            }else{
                valor_contabil = valor_contabil.replace(/\./g,"").replace(/\,/g, ".");
                valor_contabil = parseFloat(valor_contabil);
            }

            valor_contabil_total = valor_contabil * quantidade;
            valor_contabil_total_total = valor_contabil_total_total + valor_contabil_total;
        });
        
        if(valor_contabil_total_total == 0){
            valor_contabil_total_total = '';
        }else{
            valor_contabil_total_total = numberToReal(valor_contabil_total_total.toFixed(2));
        }

        $('.dataTables_scrollFootInner').find('#valor_contabil_total_total').html(valor_contabil_total_total);
        form_modal_importacao.find("#valor_contabil_financeiro").val(valor_contabil_total_total);
        
        valor_contabil_financeiro = form_modal_importacao.find("#valor_contabil_financeiro").val();
        valor_fob_devido = form_modal_importacao.find("#valor_fob_devido").val();

        if(valor_contabil_financeiro == "" || $.isEmptyObject(valor_contabil_financeiro)){
            valor_contabil_financeiro = 0;
        }else{
            valor_contabil_financeiro = valor_contabil_financeiro.replace(/\./g,"").replace(/\,/g, ".");
            valor_contabil_financeiro = parseFloat(valor_contabil_financeiro);
        }

        if(valor_fob_devido == "" || $.isEmptyObject(valor_fob_devido)){
            valor_fob_devido = 0;
        }else{
            valor_fob_devido = valor_fob_devido.replace(/\./g,"").replace(/\,/g, ".");
            valor_fob_devido = parseFloat(valor_fob_devido);
        }

        if(valor_contabil_financeiro > valor_fob_devido){
            form_modal_importacao.find("#label_debito_credito_financeiro").html('Crédito');
            form_modal_importacao.find("#debito_credito_financeiro").attr({placeholder:"Crédito"});
            form_modal_importacao.find("#debito_credito_financeiro").val(numberToReal((valor_contabil_financeiro - valor_fob_devido).toFixed(2)));
        }else{
            form_modal_importacao.find("#label_debito_credito_financeiro").html('Débito');
            form_modal_importacao.find("#debito_credito_financeiro").attr({placeholder:"Débito"});
            form_modal_importacao.find("#debito_credito_financeiro").val(numberToReal((valor_fob_devido - valor_contabil_financeiro).toFixed(2)));
        }
    }

    function numberToReal(valor) {
        if(valor == "" || $.isEmptyObject(valor) || valor == 0){
            return '';
        }else{
            var numero = valor.split('.');
            numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
            return numero.join(',');
        }
    }

    function showModalFinanceiroLancamento($inicio){
        var id = form_modal_importacao.find("#id").val();
        $.ajax({
            url: '{{ route('importacao.dados_complementares_follow_up.modal.financeiro_lancamento') }}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                inicio: $inicio,
            },
            method: 'POST',
            success: function(body){
                var title = 'Lançamento Financeiro';
                createModal('modal_financeiro_lancamento', title, body, 'modal-lg');
            }
        });
    }

    function atualizarValorDevido(){
        valor_fob_devido = total_preco_fob;
        valor_fob_pago = form_modal_importacao.find("#valor_fob_pago").val();

        if(valor_fob_pago == "" || $.isEmptyObject(valor_fob_pago)){
            valor_fob_pago = 0;
        }else{
            valor_fob_pago = valor_fob_pago.replace(/\./g,"").replace(/\,/g, ".");
            valor_fob_pago = parseFloat(valor_fob_pago);
        }

        if(valor_fob_devido == "" || $.isEmptyObject(valor_fob_devido)){
            valor_fob_devido = 0;
        }else{
            valor_fob_devido = valor_fob_devido.replace(/\./g,"").replace(/\,/g, ".");
            valor_fob_devido = parseFloat(valor_fob_devido);
        }

        valor_fob_devido = valor_fob_devido - valor_fob_pago;

        valor_fob_devido = numberToReal(valor_fob_devido.toFixed(2))

        form_modal_importacao.find("#valor_fob_devido").val(valor_fob_devido);
    }

    function salvaProforma(){
        if(form_modal_importacao.find('#id').val() == ''){
            var formData = new FormData($(document).find('#form_importacao_edt')[0]);
            $.ajax({
                url: "{{ route('importacao.salvamento_proforma') }}", 
                dataType: 'json',
                data: formData,
                method: 'POST',
                processData: false,
                contentType: false,
                success: function(callback){
                    form_modal_importacao.find('#id').val(callback.response.id);
                    ii_previsto = callback.response.custo_previsto.ii_previsto;
                    ipi_previsto = callback.response.custo_previsto.ipi_previsto;
                    pis_previsto = callback.response.custo_previsto.pis_previsto;
                    cofins_previsto = callback.response.custo_previsto.cofins_previsto;
                    afrmm_previsto = callback.response.custo_previsto.afrmm_previsto;
                    taxa_siscomex_previsto = callback.response.custo_previsto.taxa_siscomex_previsto;
                    sda_previsto = callback.response.custo_previsto.sda_previsto;
                    honorarios_previsto = callback.response.custo_previsto.honorarios_previsto;
                    expediente_previsto = callback.response.custo_previsto.expediente_previsto;
                    valor_li_previsto = callback.response.custo_previsto.valor_li_previsto;
                    agencia_maritima_previsto = callback.response.custo_previsto.agencia_maritima_previsto;
                    laudo_previsto = callback.response.custo_previsto.laudo_previsto;
                    icms_saida_previsto = callback.response.custo_previsto.icms_saida_previsto;
                    seguro_previsto = callback.response.custo_previsto.seguro_previsto;
                    armazenagem_previsto = callback.response.custo_previsto.armazenagem_previsto;
                    outras_despesas_previsto = callback.response.custo_previsto.outras_despesas_previsto;
                    total_previsto = callback.response.custo_previsto.total_previsto;
                    total_cambio_previsto = callback.response.custo_previsto.total_cambio_previsto;
                    transporte_rodoviario_previsto = callback.response.custo_previsto.transporte_rodoviario_previsto;

                    form_modal_importacao.find("#ii").val(ii_previsto);
                    form_modal_importacao.find("#ipi").val(ipi_previsto);
                    form_modal_importacao.find("#pis").val(pis_previsto);
                    form_modal_importacao.find("#cofins").val(cofins_previsto);
                    form_modal_importacao.find("#afrmm").val(afrmm_previsto);
                    form_modal_importacao.find("#taxa_siscomex").val(taxa_siscomex_previsto);
                    form_modal_importacao.find("#sda").val(sda_previsto);
                    form_modal_importacao.find("#honorarios").val(honorarios_previsto);
                    form_modal_importacao.find("#expediente").val(expediente_previsto);
                    form_modal_importacao.find("#valor_li").val(valor_li_previsto);
                    form_modal_importacao.find("#agencia_maritima").val(agencia_maritima_previsto);
                    form_modal_importacao.find("#armazem").val(armazenagem_previsto);
                    form_modal_importacao.find("#laudo").val(laudo_previsto);
                    form_modal_importacao.find("#icms_saida").val(icms_saida_previsto);
                    form_modal_importacao.find("#seguro").val(seguro_previsto);
                    form_modal_importacao.find("#transporte_rodoviario").val(transporte_rodoviario_previsto);
                },
                error: function(callback){
                    var dados = callback.responseJSON;
                    limparMesagemErroEdt();
                    mensagemErroEdt(dados);
                }
            });
        }
    }

    function calculoRateio(){
        ii = form_modal_importacao.find("#ii").val();
        ipi = form_modal_importacao.find("#ipi").val();
        pis = form_modal_importacao.find("#pis").val();
        cofins = form_modal_importacao.find("#cofins").val();
        afrmm = form_modal_importacao.find("#afrmm").val();
        taxa_siscomex = form_modal_importacao.find("#taxa_siscomex").val();
        sda = form_modal_importacao.find("#sda").val();
        honorarios = form_modal_importacao.find("#honorarios").val();
        expediente = form_modal_importacao.find("#expediente").val();
        valor_li = form_modal_importacao.find("#valor_li").val();
        agencia_maritima = form_modal_importacao.find("#agencia_maritima").val();
        armazem = form_modal_importacao.find("#armazem").val();
        laudo = form_modal_importacao.find("#laudo").val();
        icms_saida = form_modal_importacao.find("#icms_saida").val();
        seguro = form_modal_importacao.find("#seguro").val();
        descricao_outras_despesas = form_modal_importacao.find("#descricao_outras_despesas").val();
        outras_despesas = form_modal_importacao.find("#outras_despesas").val();
        total_cambio_realizado = form_modal_importacao.find("#total_cambio_realizado").val();

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

        custo_total = ii + ipi + pis + cofins + afrmm + taxa_siscomex + sda + honorarios + expediente + valor_li + agencia_maritima + armazem + laudo + icms_saida + seguro + outras_despesas + total_cambio_realizado;

        if(total_previsto == "" || $.isEmptyObject(total_previsto)){
            total_previsto = 0;
        }else{
            total_previsto = total_previsto.replace(/\./g,"").replace(/\,/g, ".");
            total_previsto = parseFloat(total_previsto);
        }

        produto_array.forEach(function imprimir(item){
            quantidade = form_modal_importacao.find("#valor_contabil_unitario_"+item).data("quantidade");

            if(quantidade == "" || $.isEmptyObject(quantidade)){
                quantidade = 0;
            }else{
                quantidade = quantidade.replace(/\./g,"").replace(/\,/g, ".");
                quantidade = parseFloat(quantidade);
            }

            porcetagem_quantidade = quantidade/quantidade_total;

            custo_parcial = custo_total * porcetagem_quantidade;

            valor_rateio = custo_parcial / quantidade;

            valor_rateio = numberToReal(valor_rateio.toFixed(2));

            form_modal_importacao.find("#valor_rateio_"+item).html(valor_rateio);

            custo_parcial_previsto = total_previsto * porcetagem_quantidade;

            valor_rateio_previsto = custo_parcial_previsto / quantidade;

            valor_rateio_previsto = numberToReal(valor_rateio_previsto.toFixed(2));

            form_modal_importacao.find("#valor_rateio_previsto_"+item).html(valor_rateio_previsto);
        });

        total_previsto = numberToReal(total_previsto.toFixed(2));
    }

    function adicionarDepesas(form_modal_importacao){
        data_form_modal_importacao = form_modal_importacao.serialize();
        $.ajax({
            url: '{{ route('importacao.adicionar_outras_depesas') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_modal_importacao,
            success: function (data){
                linhas = [];
                table_outras_depesas.clear().draw();
                for (var posicao in data.response.outras_despesas){
                    linha = [
                        ajusteTamanhoTable(data.response.outras_despesas[posicao].descricao),
                        data.response.outras_despesas[posicao].valor,
                        createBtnDeleteDespesa(data.response.outras_despesas[posicao].id),
                    ];
                    linhas.push(linha)
                }
                table_outras_depesas.rows.add(linhas).draw();
                form_modal_importacao.find('#outras_despesas').val(data.response.total);
                form_modal_importacao.find('#descricao_outras_despesas').val('');
                form_modal_importacao.find('#outras_despesas_valor').val('');
                calculoRateio();
                $('.dataTables_scrollFootInner').find('#outras_despesas_total').html(numberToReal(data.response.total.toFixed(2)));
            },
            error: function (data){

            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";
    
        return $html;
    }
    
    function createBtnDeleteDespesa($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir Despesa\" class=\"bt-delete\" data-placement=\"top\" title=\"Excluir\" onclick=\"excluirDespesa($(this), $(this).parents('tr'))\"></a>";
        return $html;
    }

    function resultado(form_modal_importacao){
        ii = form_modal_importacao.find("#ii").val();
        ipi = form_modal_importacao.find("#ipi").val();
        pis = form_modal_importacao.find("#pis").val();
        cofins = form_modal_importacao.find("#cofins").val();
        afrmm = form_modal_importacao.find("#afrmm").val();
        taxa_siscomex = form_modal_importacao.find("#taxa_siscomex").val();
        sda = form_modal_importacao.find("#sda").val();
        honorarios = form_modal_importacao.find("#honorarios").val();
        expediente = form_modal_importacao.find("#expediente").val();
        valor_li = form_modal_importacao.find("#valor_li").val();
        agencia_maritima = form_modal_importacao.find("#agencia_maritima").val();
        armazem = form_modal_importacao.find("#armazem").val();
        laudo = form_modal_importacao.find("#laudo").val();
        icms_saida = form_modal_importacao.find("#icms_saida").val();
        seguro = form_modal_importacao.find("#seguro").val();
        outras_despesas = form_modal_importacao.find("#outras_despesas").val();
        total_cambio_realizado = form_modal_importacao.find("#total_cambio").val();
        transporte_rodoviario = form_modal_importacao.find("#transporte_rodoviario").val();

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

        custo_total = ii + ipi + pis + cofins + afrmm + taxa_siscomex + sda + honorarios + expediente + valor_li + agencia_maritima + armazem + laudo + icms_saida + seguro + outras_despesas + total_cambio_realizado + transporte_rodoviario;
        
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
        if(transporte_rodoviario_previsto == "" || $.isEmptyObject(transporte_rodoviario_previsto)){
            transporte_rodoviario_previsto = 0;
        }else{
            transporte_rodoviario_previsto = transporte_rodoviario_previsto.replace(/\./g,"").replace(/\,/g, ".");
            transporte_rodoviario_previsto = parseFloat(transporte_rodoviario_previsto);
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
        laudo_diferenca = laudo - laudo_previsto;
        icms_saida_diferenca = icms_saida - icms_saida_previsto;
        seguro_diferenca = seguro - seguro_previsto;
        armazenagem_diferenca = armazem - armazenagem_previsto;
        transporte_rodoviario_diferenca = transporte_rodoviario - transporte_rodoviario_previsto;
        outras_despesas_diferenca = outras_despesas - outras_despesas_previsto;
        total_diferenca = custo_total - total_previsto;
        total_cambio_diferenca = total_cambio_realizado - total_cambio_previsto;

        form_modal_importacao.find("#total_cambio_realizado").html(form_modal_importacao.find("#total_cambio").val());
        form_modal_importacao.find("#ii_realizado").html(form_modal_importacao.find("#ii").val());
        form_modal_importacao.find("#ipi_realizado").html(form_modal_importacao.find("#ipi").val());
        form_modal_importacao.find("#pis_realizado").html(form_modal_importacao.find("#pis").val());
        form_modal_importacao.find("#cofins_realizado").html(form_modal_importacao.find("#cofins").val());
        form_modal_importacao.find("#afrmm_realizado").html(form_modal_importacao.find("#afrmm").val());
        form_modal_importacao.find("#taxa_siscomex_realizado").html(form_modal_importacao.find("#taxa_siscomex").val());
        form_modal_importacao.find("#sda_realizado").html(form_modal_importacao.find("#sda").val());
        form_modal_importacao.find("#honorarios_realizado").html(form_modal_importacao.find("#honorarios").val());
        form_modal_importacao.find("#expediente_realizado").html(form_modal_importacao.find("#expediente").val());
        form_modal_importacao.find("#valor_li_realizado").html(form_modal_importacao.find("#valor_li").val());
        form_modal_importacao.find("#agencia_maritima_realizado").html(form_modal_importacao.find("#agencia_maritima").val());
        form_modal_importacao.find("#laudo_realizado").html(form_modal_importacao.find("#laudo").val());
        form_modal_importacao.find("#icms_saida_realizado").html(form_modal_importacao.find("#icms_saida").val());
        form_modal_importacao.find("#transporte_rodoviario_realizado").html(form_modal_importacao.find("#transporte_rodoviario").val());
        form_modal_importacao.find("#seguro_realizado").html(form_modal_importacao.find("#seguro").val());
        form_modal_importacao.find("#armazenagem_realizado").html(form_modal_importacao.find("#armazem").val());
        form_modal_importacao.find("#outras_despesas_realizado").html(form_modal_importacao.find("#outras_despesas").val());
        form_modal_importacao.find("#total_realizado").html(numberToReal(custo_total.toFixed(2)));

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
        transporte_rodoviario_previsto = numberToReal(transporte_rodoviario_previsto.toFixed(2));
        outras_despesas_previsto = numberToReal(outras_despesas_previsto.toFixed(2));
        total_previsto = numberToReal(total_previsto.toFixed(2));
        total_cambio_previsto = numberToReal(total_cambio_previsto.toFixed(2));

        form_modal_importacao.find("#ii_previsto").html(ii_previsto);
        form_modal_importacao.find("#ipi_previsto").html(ipi_previsto);
        form_modal_importacao.find("#pis_previsto").html(pis_previsto);
        form_modal_importacao.find("#cofins_previsto").html(cofins_previsto);
        form_modal_importacao.find("#afrmm_previsto").html(afrmm_previsto);
        form_modal_importacao.find("#taxa_siscomex_previsto").html(taxa_siscomex_previsto);
        form_modal_importacao.find("#sda_previsto").html(sda_previsto);
        form_modal_importacao.find("#honorarios_previsto").html(honorarios_previsto);
        form_modal_importacao.find("#expediente_previsto").html(expediente_previsto);
        form_modal_importacao.find("#valor_li_previsto").html(valor_li_previsto);
        form_modal_importacao.find("#agencia_maritima_previsto").html(agencia_maritima_previsto);
        form_modal_importacao.find("#laudo_previsto").html(laudo_previsto);
        form_modal_importacao.find("#icms_saida_previsto").html(icms_saida_previsto);
        form_modal_importacao.find("#seguro_previsto").html(seguro_previsto);
        form_modal_importacao.find("#armazenagem_previsto").html(armazenagem_previsto);
        form_modal_importacao.find("#transporte_rodoviario_previsto").html(transporte_rodoviario_previsto);
        form_modal_importacao.find("#outras_despesas_previsto").html(outras_despesas_previsto);
        form_modal_importacao.find("#total_previsto").html(total_previsto);
        form_modal_importacao.find("#total_cambio_previsto").html(total_cambio_previsto + '<span class="input-group-addon border rounded-right" data-toggle="tooltip" data-placement="top" title="Histórico De Dolar Referência" id="bt-view_historico_dolar_padrao"><i id="bt-view-historico_dolar_padrao" class="bt-list m-2" onclick="modalDolarReferencia()"></i></span>');

        form_modal_importacao.find("#ii_diferenca").html(numberToReal(ii_diferenca.toFixed(2)));
        form_modal_importacao.find("#ipi_diferenca").html(numberToReal(ipi_diferenca.toFixed(2)));
        form_modal_importacao.find("#pis_diferenca").html(numberToReal(pis_diferenca.toFixed(2)));
        form_modal_importacao.find("#cofins_diferenca").html(numberToReal(cofins_diferenca.toFixed(2)));
        form_modal_importacao.find("#afrmm_diferenca").html(numberToReal(afrmm_diferenca.toFixed(2)));
        form_modal_importacao.find("#taxa_siscomex_diferenca").html(numberToReal(taxa_siscomex_diferenca.toFixed(2)));
        form_modal_importacao.find("#sda_diferenca").html(numberToReal(sda_diferenca.toFixed(2)));
        form_modal_importacao.find("#honorarios_diferenca").html(numberToReal(honorarios_diferenca.toFixed(2)));
        form_modal_importacao.find("#expediente_diferenca").html(numberToReal(expediente_diferenca.toFixed(2)));
        form_modal_importacao.find("#valor_li_diferenca").html(numberToReal(valor_li_diferenca.toFixed(2)));
        form_modal_importacao.find("#agencia_maritima_diferenca").html(numberToReal(agencia_maritima_diferenca.toFixed(2)));
        form_modal_importacao.find("#laudo_diferenca").html(numberToReal(laudo_diferenca.toFixed(2)));
        form_modal_importacao.find("#icms_saida_diferenca").html(numberToReal(icms_saida_diferenca.toFixed(2)));
        form_modal_importacao.find("#seguro_diferenca").html(numberToReal(seguro_diferenca.toFixed(2)));
        form_modal_importacao.find("#armazenagem_diferenca").html(numberToReal(armazenagem_diferenca.toFixed(2)));
        form_modal_importacao.find("#transporte_rodoviario_diferenca").html(numberToReal(transporte_rodoviario_diferenca.toFixed(2)));
        form_modal_importacao.find("#outras_despesas_diferenca").html(numberToReal(outras_despesas_diferenca.toFixed(2)));
        form_modal_importacao.find("#total_diferenca").html(numberToReal(total_diferenca.toFixed(2)));
        form_modal_importacao.find("#total_cambio_diferenca").html(numberToReal(total_cambio_diferenca.toFixed(2)));
    }

    function modalDolarReferencia(){
        esconderPopoverTooltip();
        $.ajax({
            url: '{{ route('importacao.modal.historico_dolar_refencia') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_historico_dolar_referencia", "Histórico Dolar Referência", data, '');
                var modal = $(document).find("#modal_historico_dolar_referencia");
                $(document).ready( function () {
                    table_histrorico_dolar.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosDolar($(this).parent('tr'));
                        });
                    });
                });
            }
        });
    }

    function returnDadosDolar($dados){
		form_modal_importacao = $(document).find("#form_importacao_edt");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_historico_dolar_referencia").modal("hide");
    }

    function showModalDataEtdEta(tipo){
        if(tipo == 'embarque_etd'){
            title = 'Data Embarque ETD';
        }else{
            title = 'Data Chegada no Porto ETA';
        }

        $.ajax({
            url: '{{ route('importacao.modal.alteracao_data_etd_eta')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                tipo: tipo
            },
            success: function(body){
                createModal("alterar_data_modal", title, body, '');
    
                var tamanho = 240;
                $(document).find('#alterar_data_modal').find('.modal-dialog').css('max-height', tamanho+'px');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }
    
        });
    }

    function calculoCycleTime(){
        var data_embarque_realizado = form_modal_importacao.find("#embarque_realizado").val();
        var data_chegada_porto_realizado = form_modal_importacao.find("#chegada_porto_realizado").val();
        
        if(data_embarque_realizado == "" || $.isEmptyObject(data_embarque_realizado)
            || data_chegada_porto_realizado == "" || $.isEmptyObject(data_chegada_porto_realizado)){
                form_modal_importacao.find("#cycle_time").val('');
        }else{          
            data_embarque_realizado_split = data_embarque_realizado.split('/');

            dia_embarque_realizado = data_embarque_realizado_split[0]; 
            mes_embarque_realizado = data_embarque_realizado_split[1];
            ano_embarque_realizado = data_embarque_realizado_split[2]; 

            data_embarque_realizado = new Date(ano_embarque_realizado, mes_embarque_realizado - 1, dia_embarque_realizado);

            data_chegada_porto_realizado_split = data_chegada_porto_realizado.split('/');

            dia_chegada_porto_realizado = data_chegada_porto_realizado_split[0]; 
            mes_chegada_porto_realizado = data_chegada_porto_realizado_split[1];
            ano_chegada_porto_realizado = data_chegada_porto_realizado_split[2]; 

            data_chegada_porto_realizado = new Date(ano_chegada_porto_realizado, mes_chegada_porto_realizado - 1, dia_chegada_porto_realizado);

            diferenca = Math.abs(data_chegada_porto_realizado.getTime() - data_embarque_realizado.getTime());
            diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24));

            form_modal_importacao.find("#cycle_time").val(diferenca_dias);
        }

    }

    function salvarEmMudanca($this){
        esconderPopoverTooltip();
        var campo = $this.attr("name");
        var valor = $this.val();
        var tipo = $this.data("tipo");
        var nome_exibicao = $this.attr("placeholder");
        id = form_modal_importacao.find("#id").val();
        limparMesagemErroEdt(form_modal_importacao);
        $.ajax({
            url: '{{ route('importacao.adicionar_mudanca_valor')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                campo: campo,
                valor: valor,
                tipo: tipo,
                nome_exibicao: nome_exibicao,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){

            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt(form_modal_importacao);
                for(var field in dados.error){
                    showErrorsInputsEdt(form_modal_importacao, campo, dados.error[field]);
                }
            }
        });
    }
    
    function salvarEmMudancaEmbarque($this){
        esconderPopoverTooltip();
        var campo = $this.attr("name");
        var valor = $this.val();
        var tipo = $this.data("tipo");
        var nome_exibicao = $this.attr("placeholder");
        if(campo == 'etd_booking' || campo == 'eta_booking'){
            if($this.is(':checked')){
                valor = true;
            }else{
                valor = "";
            }
        }
        id = form_modal_importacao.find("#id").val();
        limparMesagemErroEdt(form_modal_importacao);
        $.ajax({
            url: '{{ route('importacao.adicionar_mudanca_valor_embarque')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                campo: campo,
                valor: valor,
                tipo: tipo,
                nome_exibicao: nome_exibicao,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt(form_modal_importacao);
                mensagemErroEdt(dados, form_modal_importacao);
            }
        });
    }

    function salvarEmMudancaFinanceiro($this){
        esconderPopoverTooltip();
        var campo = $this.attr("name");
        var valor = $this.val();
        var tipo = $this.data("tipo");
        var nome_exibicao = $this.attr("placeholder");
        id = form_modal_importacao.find("#id").val();
        limparMesagemErroEdt(form_modal_importacao);
        $.ajax({
            url: '{{ route('importacao.adicionar_mudanca_valor_financeiro')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                campo: campo,
                valor: valor,
                tipo: tipo,
                nome_exibicao: nome_exibicao,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt(form_modal_importacao);
                mensagemErroEdt(dados, form_modal_importacao);
            }
        });
    }
    
    function salvarEmMudancaCusto($this){
        esconderPopoverTooltip();
        var campo = $this.attr("name");
        var valor = $this.val();
        var tipo = $this.data("tipo");
        var nome_exibicao = $this.attr("placeholder");
        id = form_modal_importacao.find("#id").val();
        limparMesagemErroEdt(form_modal_importacao);
        $.ajax({
            url: '{{ route('importacao.adicionar_mudanca_valor_custo')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                campo: campo,
                valor: valor,
                tipo: tipo,
                nome_exibicao: nome_exibicao,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt(form_modal_importacao);
                mensagemErroEdt(dados, form_modal_importacao);
            }
        });
    }

    function salvarEmMudancaProdutoContábil($this){
        esconderPopoverTooltip();
        var campo = $this.attr("name");
        var valor = $this.val();
        var codigo = $this.data("codigo");
        var quantidade = $this.data("quantidade");
        id = form_modal_importacao.find("#id").val();
        limparMesagemErroEdt(form_modal_importacao);
        $.ajax({
            url: '{{ route('importacao.adicionar_mudanca_valor_custo')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                campo: campo,
                valor: valor,
                codigo: codigo,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt(form_modal_importacao);
                mensagemErroEdt(dados, form_modal_importacao);
            }
        });
    }

    function abaComErro(campo){
        var campos_dados_gerais = ['numero_proforma', 'pedido_compras', 'fornecedor', 'referencia', 'data_proforma', 'previsao_carta_programa', 'respresentante', 'arquivo_carta_programada'];
        var campos_produtos = ['valor_contabil_unitario'];
        var campos_follow_up_lead_time = ['carga_pronta_previsao', 'carga_pronta_realizado', 'embarque_previsao', 'chegada_porto_previsao', 'data_di_previsao', 'data_di_realizado', 'devolucao_cntr_previsao', 'devolucao_cntr_realizado', 'quality_sample_enviado', 'quality_sample_recebido', 'handlooms_enviado', 'handlooms_recebido', 'strike_off_enviado', 'strike_off_recebido', 'amostra_embarque_enviado', 'amostra_embarque_recebido', 'aprovacao_amostra_embarque', 'aprovado_embarque_produto_codigo', 'tempo_producao'];
        var campos_embarque = ['porto_origem', 'porto_destino', 'agente_compra', 'armador', 'numero_bl', 'embarque_realizado', 'chegada_porto_realizado'];
        var campos_documentos = ['arquivo_proforma', 'arquivo_conciliator_invoice', 'arquivo_packing_list', 'arquivo_bl', 'arquivo_contrato_cambio', 'arquivo_nf_importacao', 'arquivo_exoneracao', 'arquivo_nf_remessa', 'arquivo_di', 'arquivo_ci', 'arquivo_fechamento_processo', 'arquivo_armazenagem', 'arquivo_afrmm'];
        var campos_financeiro = ['data_embarque_financeiro'];
        var campos_custos = ['ii', 'ipi', 'pis', 'cofins', 'afrmm', 'taxa_siscomex', 'sda', 'honorarios', 'expediente', 'valor_li', 'agencia_maritima', 'armazem', 'laudo', 'icms_saida', 'transporte_rodoviario', 'seguro'];
        
        procurar = campos_dados_gerais.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-dados_gerais-tab").tab("show");
        }

        procurar = campos_produtos.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-produtos-tab").tab("show");
        }

        procurar = campos_follow_up_lead_time.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-follow_up-lead_time-tab").tab("show");
        }

        procurar = campos_embarque.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-dados_adicionais_embarque-tab").tab("show");
        }

        procurar = campos_documentos.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-documentos_processos-tab").tab("show");
        }

        procurar = campos_financeiro.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-financeiro-tab").tab("show");
        }

        procurar = campos_custos.find(element => element == campo);
        if(!$.isEmptyObject(procurar)){
            $(document).find("#importacao-custo-tab").tab("show");
        }
    }

    function excluirDespesa($this, linha){
        esconderPopoverTooltip();
        var $id = $this.data("id");
        $.ajax({
            url: '{{ Route("importacao.excluir_outras_depesas") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token()}}',
                id: $id
            },
            success: function(data){
                table_outras_depesas.row(linha).remove().draw();
                form_modal_importacao.find('#outras_despesas').val(numberToReal(data.response.total.toFixed(2)));
                $('.dataTables_scrollFootInner').find('#outras_despesas_total').html(numberToReal(data.response.total.toFixed(2)));
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function previsacaoFollowUp(form_modal_importacao){
        previsaoEnvioCores(form_modal_importacao);
        previsaoQualitySample(form_modal_importacao);
        previsaoLaboratorio(form_modal_importacao);
    }

    function adicionaZero(numero){
        if (numero <= 9) 
            return "0" + numero;
        else
            return numero; 
    }

    function previsaoEnvioCores(form_modal_importacao){
        var data_proforma = form_modal_importacao.find("#data_proforma").val();
        var envio_das_cores = form_modal_importacao.find("#envio_das_cores").val();

        data_proforma_split = data_proforma.split('/');

        dia_data_proforma_split = data_proforma_split[0]; 
        mes_data_proforma_split = data_proforma_split[1];
        ano_data_proforma_split = data_proforma_split[2]; 

        data_proforma = new Date(ano_data_proforma_split, mes_data_proforma_split - 1, dia_data_proforma_split);
    
        if(envio_das_cores == '' || $.isEmptyObject(envio_das_cores)){
            form_modal_importacao.find("#envio_das_cores_previsao").val('');
        }else{
            if(envio_das_cores == 'cores_unicas'){
                intervalo_dias = 5;
            }else{
                intervalo_dias = 10;
            }
    
            data_proforma.setDate(data_proforma.getDate() + intervalo_dias);
            data_formatada = adicionaZero((data_proforma.getDate() )) + "/" + adicionaZero((data_proforma.getMonth() + 1)) + "/" + data_proforma.getFullYear(); 
    
            form_modal_importacao.find("#envio_das_cores_previsao").val(data_formatada);
        }
    }

    function previsaoQualitySample(form_modal_importacao){
        var data_proforma = form_modal_importacao.find("#data_proforma").val();
        var envio_das_cores = form_modal_importacao.find("#envio_das_cores").val();

        data_proforma_split = data_proforma.split('/');

        dia_data_proforma_split = data_proforma_split[0]; 
        mes_data_proforma_split = data_proforma_split[1];
        ano_data_proforma_split = data_proforma_split[2]; 

        data_proforma = new Date(ano_data_proforma_split, mes_data_proforma_split - 1, dia_data_proforma_split);
    
        if(envio_das_cores == '' || $.isEmptyObject(envio_das_cores)){
            form_modal_importacao.find("#quality_sample_previsao").val('');
        }else{
            intervalo_dias = 15;

            data_proforma.setDate(data_proforma.getDate() + intervalo_dias);
            data_formatada = adicionaZero((data_proforma.getDate() )) + "/" + adicionaZero((data_proforma.getMonth() + 1)) + "/" + data_proforma.getFullYear(); 
    
            form_modal_importacao.find("#quality_sample_previsao").val(data_formatada);
        }
    }

    function previsaoAprovacaoQualitySample(form_modal_importacao){
        var quality_sample_recebido = form_modal_importacao.find("#quality_sample_recebido").val();

        if(quality_sample_recebido == '' || $.isEmptyObject(quality_sample_recebido)){
            form_modal_importacao.find("#aprovacao_quality_sample_previsao").val('');
        }else{
            intervalo_dias = 2;

            quality_sample_recebido_split = quality_sample_recebido.split('/');

            dia_quality_sample_recebido_split = quality_sample_recebido_split[0]; 
            mes_quality_sample_recebido_split = quality_sample_recebido_split[1];
            ano_quality_sample_recebido_split = quality_sample_recebido_split[2]; 

            quality_sample_recebido = new Date(ano_quality_sample_recebido_split, mes_quality_sample_recebido_split - 1, dia_quality_sample_recebido_split);
    
            quality_sample_recebido.setDate(quality_sample_recebido.getDate() + intervalo_dias);
            quality_sample_recebido = adicionaZero((quality_sample_recebido.getDate() )) + "/" + adicionaZero((quality_sample_recebido.getMonth() + 1)) + "/" + quality_sample_recebido.getFullYear(); 
    
            form_modal_importacao.find("#aprovacao_quality_sample_previsao").val(quality_sample_recebido);
        }
    }

    function previsaoLaboratorio(form_modal_importacao){
        var envio_das_cores_previsao = form_modal_importacao.find("#envio_das_cores_previsao").val();
        var envio_das_cores = form_modal_importacao.find("#envio_das_cores").val();

        if(envio_das_cores_previsao == '' || $.isEmptyObject(envio_das_cores_previsao)){
            form_modal_importacao.find("#laboratorio_previsao").val('');
        }else{
            if(envio_das_cores == 'cores_unicas'){
                intervalo_dias = 15;
            }else{
                intervalo_dias = 20;
            }

            envio_das_cores_previsao_split = envio_das_cores_previsao.split('/');

            dia_envio_das_cores_previsao_split = envio_das_cores_previsao_split[0]; 
            mes_envio_das_cores_previsao_split = envio_das_cores_previsao_split[1];
            ano_envio_das_cores_previsao_split = envio_das_cores_previsao_split[2]; 

            laboratorio_previsao = new Date(ano_envio_das_cores_previsao_split, mes_envio_das_cores_previsao_split - 1, dia_envio_das_cores_previsao_split);
    
            laboratorio_previsao.setDate(laboratorio_previsao.getDate() + intervalo_dias);
            laboratorio_previsao = adicionaZero((laboratorio_previsao.getDate() )) + "/" + adicionaZero((laboratorio_previsao.getMonth() + 1)) + "/" + laboratorio_previsao.getFullYear(); 
    
            form_modal_importacao.find("#laboratorio_previsao").val(laboratorio_previsao);
        }
    }

    function previsaoTerminoProducao(form_modal_importacao){
        var status_aprovacao_laboratorio = form_modal_importacao.find("#aprovacao_laboratorio").val();
        var envio_das_cores = form_modal_importacao.find("#envio_das_cores").val();
        var data_aprovacao_laboratorio = form_modal_importacao.find("#aprovacao_laboratorio_realizado").val();

        if(status_aprovacao_laboratorio == 'reprovado' || status_aprovacao_laboratorio == '' || envio_das_cores == '' || data_aprovacao_laboratorio == '' || $.isEmptyObject(status_aprovacao_laboratorio) || $.isEmptyObject(envio_das_cores) || $.isEmptyObject(data_aprovacao_laboratorio)){
            form_modal_importacao.find("#tempo_producao_previsao").val();
        }else{
            if(envio_das_cores == 'cores_unicas' || envio_das_cores == 'estampado'){
                intervalo_dias = 35;
            }else{
                intervalo_dias = 60;
            }

            data_aprovacao_laboratorio_split = data_aprovacao_laboratorio.split('/');

            dia_data_aprovacao_laboratorio_split = data_aprovacao_laboratorio_split[0]; 
            mes_data_aprovacao_laboratorio_split = data_aprovacao_laboratorio_split[1];
            ano_data_aprovacao_laboratorio_split = data_aprovacao_laboratorio_split[2]; 

            tempo_producao_previsao = new Date(ano_data_aprovacao_laboratorio_split, mes_data_aprovacao_laboratorio_split - 1, dia_data_aprovacao_laboratorio_split);
    
            tempo_producao_previsao.setDate(tempo_producao_previsao.getDate() + intervalo_dias);
            tempo_producao_previsao = adicionaZero((tempo_producao_previsao.getDate() )) + "/" + adicionaZero((tempo_producao_previsao.getMonth() + 1)) + "/" + tempo_producao_previsao.getFullYear(); 

            form_modal_importacao.find("#tempo_producao_previsao").val(tempo_producao_previsao);
        }
    }

    function previsaoAmostraEmbarque(form_modal_importacao){
        var tempo_producao_previsao = form_modal_importacao.find("#tempo_producao_previsao").val();

        if(tempo_producao_previsao == '' || $.isEmptyObject(tempo_producao_previsao)){
            form_modal_importacao.find("#amostra_embarque_previsao").val();
        }else{
            intervalo_dias = 7;

            tempo_producao_previsao_split = tempo_producao_previsao.split('/');

            dia_tempo_producao_previsao_split = tempo_producao_previsao_split[0]; 
            mes_tempo_producao_previsao_split = tempo_producao_previsao_split[1];
            ano_tempo_producao_previsao_split = tempo_producao_previsao_split[2];
            
            amostra_embarque_previsao = new Date(ano_tempo_producao_previsao_split, mes_tempo_producao_previsao_split - 1, dia_tempo_producao_previsao_split);
    
            amostra_embarque_previsao.setDate(amostra_embarque_previsao.getDate() + intervalo_dias);
            amostra_embarque_previsao = adicionaZero((amostra_embarque_previsao.getDate() )) + "/" + adicionaZero((amostra_embarque_previsao.getMonth() + 1)) + "/" + amostra_embarque_previsao.getFullYear(); 

            form_modal_importacao.find("#amostra_embarque_previsao").val(amostra_embarque_previsao);
        }
    }

    function previsaoAprovacaoAmostraEmbarque(form_modal_importacao){
        var amostra_embarque_recebido = form_modal_importacao.find("#amostra_embarque_recebido").val();

        if(amostra_embarque_recebido == '' || $.isEmptyObject(amostra_embarque_recebido)){
            form_modal_importacao.find("#aprovacao_amostra_embarque_previsao").val('');
        }else{
            intervalo_dias = 2;

            amostra_embarque_recebido_split = amostra_embarque_recebido.split('/');

            dia_amostra_embarque_recebido_split = amostra_embarque_recebido_split[0]; 
            mes_amostra_embarque_recebido_split = amostra_embarque_recebido_split[1];
            ano_amostra_embarque_recebido_split = amostra_embarque_recebido_split[2]; 

            aprovacao_amostra_embarque_previsao = new Date(ano_amostra_embarque_recebido_split, mes_amostra_embarque_recebido_split - 1, dia_amostra_embarque_recebido_split);
    
            aprovacao_amostra_embarque_previsao.setDate(aprovacao_amostra_embarque_previsao.getDate() + intervalo_dias);
            aprovacao_amostra_embarque_previsao = adicionaZero((aprovacao_amostra_embarque_previsao.getDate() )) + "/" + adicionaZero((aprovacao_amostra_embarque_previsao.getMonth() + 1)) + "/" + aprovacao_amostra_embarque_previsao.getFullYear(); 
    
            form_modal_importacao.find("#aprovacao_amostra_embarque_previsao").val(aprovacao_amostra_embarque_previsao);
        }
    }

    function previsaoAutorizacaoEmbarque(form_modal_importacao){
        aprovacao_amostra_embarque_previsao = form_modal_importacao.find("#aprovacao_amostra_embarque_previsao").val();

        if(aprovacao_amostra_embarque_previsao == '' || $.isEmptyObject(amostra_embarque_recebido)){
            form_modal_importacao.find("#autorizacao_embarque_previsao").val('');
        }else{
            intervalo_dias = 1;

            aprovacao_amostra_embarque_previsao_split = aprovacao_amostra_embarque_previsao.split('/');

            dia_aprovacao_amostra_embarque_previsao_split = aprovacao_amostra_embarque_previsao_split[0]; 
            mes_aprovacao_amostra_embarque_previsao_split = aprovacao_amostra_embarque_previsao_split[1];
            ano_aprovacao_amostra_embarque_previsao_split = aprovacao_amostra_embarque_previsao_split[2];
            
            autorizacao_embarque_previsao = new Date(ano_aprovacao_amostra_embarque_previsao_split, mes_aprovacao_amostra_embarque_previsao_split - 1, dia_aprovacao_amostra_embarque_previsao_split);
    
            autorizacao_embarque_previsao.setDate(autorizacao_embarque_previsao.getDate() + intervalo_dias);
            autorizacao_embarque_previsao = adicionaZero((autorizacao_embarque_previsao.getDate() )) + "/" + adicionaZero((autorizacao_embarque_previsao.getMonth() + 1)) + "/" + autorizacao_embarque_previsao.getFullYear(); 
    
            form_modal_importacao.find("#autorizacao_embarque_previsao").val(autorizacao_embarque_previsao);
        }

    }

    function modalHistoricoAprovacao($this){
        esconderPopoverTooltip();

        var title = $this.data("title");
        var tipo = $this.data("tipo");
        var id_follow_up = form_modal_importacao.find("#id_follow_up").val();

        $.ajax({
            url: '{{ route('importacao.dados_complementares_follow_up.modal.historico_aprovacao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tipo: tipo,
                id_follow_up: id_follow_up,
            },
            success: function (data){
                createModal("modal_historico_aprovacao", title, data, '');
                var modal = $(document).find("#modal_historico_aprovacao");
            }
        });
    }
</script>
@endsection