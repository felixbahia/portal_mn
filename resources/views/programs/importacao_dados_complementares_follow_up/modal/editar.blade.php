@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id="importacao_follow_up-follow_up-tab" data-toggle="tab" href="#importacao_follow_up_follow_up" role="tab" aria-controls="importacao_follow_up_follow_up" aria-selected="false">Follow Up</a>
    </li>
	<li class="nav-item">
        <a class="nav-link" id="importacao_follow_up-resumo-tab" data-toggle="tab" href="#importacao_follow_up_resumo" role="tab" aria-controls="importacao_follow_up_resumo" aria-selected="false">Resumo</a>
    </li>
</ul>
<form action="" name="form_importacao_edt" id="form_importacao_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
    {!! Form::hidden('numero_proforma', $dados['proforma'], ['id' => 'numero_proforma']) !!}
    {!! Form::hidden('fornecedor', $dados['fornecedor'], ['id' => 'fornecedor']) !!}
    {!! Form::hidden('respresentante', $dados['respresentante'], ['id' => 'respresentante']) !!}
    {!! Form::hidden('pedido_compras', $dados['pcmn'], ['id' => 'pedido_compras']) !!}
    {!! Form::hidden('referencia', $dados['referencia'], ['id' => 'referencia']) !!}
    {!! Form::hidden('data_proforma', $dados['data_proforma'], ['id' => 'data_proforma']) !!}
    {!! Form::hidden('id_follow_up', $dados['id_follow_up'], ['id' => 'id_follow_up']) !!}
    <div class="tab-content pt-3" id="ImportacaoHeaderContainer">
        <div class="content-filter-dialog">	
            <div class="form-row condicao_media_itens_pedido">
                <div class="col-sm-3">
                    <strong><span id="proforma_cabecalho" class='ml-2'>Proforma:</strong> {{ $dados['proforma'] }}</span>
                </div>
                <div class="col-sm-2">
                    <strong><span id="pcmn_cabecalho" class='ml-2'>PCMN:</strong> {{ $dados['pcmn'] }}</span>
                </div>
                <div class="col-sm-2">
                    <strong><span id="data_proforma_exibicao" class='ml-2'>Data:</strong> {{ $dados['data_proforma'] }}</span>
                </div>
                <div class="col-sm-4">
                    <strong>Fornecedor:   </strong>
                    <span id="fornecedor_cabecalho">{{ $dados['fornecedor'] }}<br/></span>
                </div>
                <div class="col-sm-1">
                    <a href="#" class="bt-view" data-toggle='tooltip' data-html='true' data-id="{{$dados['id']}}" title='Visualizar' onclick="abrirDetalhesImportacaoFollowUp($(this))"></a>
                </div>
            </div>
        </div>
        <hr>
        <br/>
        <br/>
        <div class="tab-pane show active" id="importacao_follow_up_follow_up" role="tabpanel" aria-labelledby="dados-tab">
            <b>{!! Form::label('produto_codigo', 'Produto', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-12">
                    {!! Form::select('produto_codigo', $dados['produtos'], '', ['id' => 'produto_codigo', 'class' => 'form-control input-label', 'placeholder' => 'Todos os Produtos']) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('envio_cores', 'Envio das Cores', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('envio_das_cores', 'Tipo de Cor', []) !!}
                    {!! Form::select('envio_das_cores', $dados['tipos_cores'], $dados['dados_follow']['tipo_cor'], ['id' => 'envio_das_cores', 'class' => 'form-control input-label', 'placeholder' => 'Tipo de Cor', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_cor_data_previsao', 'Previsão do Envio', []) !!}
                    {!! Form::text('envio_cor_data_previsao', $dados['dados_follow']['envio_cor_data_previsao'], ['id' => 'envio_cor_data_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Previsão', 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_cor_data_envio', 'Envio', []) !!}
                    {!! Form::text('envio_cor_data_envio', $dados['dados_follow']['envio_cor_data_envio'], ['id' => 'envio_cor_data_envio', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Enviado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_cor_data_recebido', 'Recebido', []) !!}
                    {!! Form::text('envio_cor_data_recebido', $dados['dados_follow']['envio_cor_data_recebido'], ['id' => 'envio_cor_data_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Recebido', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('envio_cor_data_revisao', 'Revisão', []) !!}
                    {!! Form::text('envio_cor_data_revisao', $dados['dados_follow']['envio_cor_data_revisao'], ['id' => 'envio_cor_data_revisao', 'class' => 'form-control input-label data', 'placeholder' => 'Envio Das Cores Revisão', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('quality_sample', 'Quality Sample', ['']) !!}</b>
            <br/>
            <div class="row">   
                <div class="form-group col-sm-2">
                    {!! Form::label('quality_sample_data_previsao_envio', 'Previsão do Envio', []) !!}
                    {!! Form::text('quality_sample_data_previsao_envio', $dados['dados_follow']['quality_sample_data_previsao_envio'], ['id' => 'quality_sample_data_previsao_envio', 'class' => 'form-control input-label data', 'placeholder' => 'Quality Sample Previsão' , 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('quality_sample_data_envio', 'Envio', []) !!}
                    {!! Form::text('quality_sample_data_envio', $dados['dados_follow']['quality_sample_data_envio'], ['id' => 'quality_sample_data_envio', 'class' => 'form-control input-label data', 'placeholder' => 'Quality Sample Enviado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                
                <div class="form-group col-sm-3">
                    {!! Form::label('quality_sample_transportadora', 'Transportadora', []) !!}
                    {!! Form::text('quality_sample_transportadora', $dados['dados_follow']['quality_sample_transportadora'], ['id' => 'quality_sample_transportadora', 'class' => 'form-control input-label', 'placeholder' => 'Quality Sample Transportadora', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('quality_sample_awb', 'AWB', []) !!}
                    {!! Form::text('quality_sample_awb', $dados['dados_follow']['quality_sample_awb'], ['id' => 'quality_sample_awb', 'class' => 'form-control input-label', 'placeholder' => 'Quality Sample AWB', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('quality_sample_data_recebido', 'Recebido', []) !!}
                    {!! Form::text('quality_sample_data_recebido', $dados['dados_follow']['quality_sample_data_recebido'], ['id' => 'quality_sample_data_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Quality Sample Recebido', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('quality_sample_aprovacao', 'Status da Aprovação', []) !!}
                    {!! Form::select('quality_sample_aprovacao', $dados['tipo_aprovacoes_simples'], $dados['dados_follow']['quality_sample_aprovacao'], ['id' => 'quality_sample_aprovacao', 'class' => 'form-control input-label', 'placeholder' => 'Status da Aprovação', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div> 
                <div class="form-group col-sm-3">
                    {!! Form::label('quality_sample_data_previsao_aprovacao', 'Previsão Aprovação', []) !!}
                    {!! Form::text('quality_sample_data_previsao_aprovacao', $dados['dados_follow']['quality_sample_data_previsao_aprovacao'], ['id' => 'quality_sample_data_previsao_aprovacao', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Quality Sample Previsão', 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('quality_sample_data_previsao_aprovacao', 'Data Aprovação', []) !!}
                    <div class="input-group">
                        {!! Form::text('quality_sample_data_aprovacao', $dados['dados_follow']['quality_sample_data_aprovacao'], ['id' => 'quality_sample_data_aprovacao', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Quality Sample Realizado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                        <span class="input-group-addon border rounded-right" data-toggle="tooltip" data-placement="top" title="Histórico De Aprovação Quality Sample" id="bt-view_historico_aprovacao"><i id="bt-view-historico_aprovacao" data-title="Histórico De Aprovação Quality Sample" data-tipo="quality_sample" class="bt-list m-2" onclick="modalHistoricoAprovacao($(this))"></i></span>
                    </div>
                </div>
            </div>
            <hr>
            <b>{!! Form::label('laboratorio', 'Laboratório(Lab Dip/ Handlooms / Strike Off)', ['id' => 'laboratorio']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_data_previsao_envio', 'Previsão do Envio', []) !!}
                    {!! Form::text('laboratorio_data_previsao_envio', $dados['dados_follow']['laboratorio_data_previsao_envio'], ['id' => 'laboratorio_data_previsao_envio', 'class' => 'form-control input-label data', 'placeholder' => 'Laboratório(Lab Dip/ Handlooms / Strike Off) Previsão', 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_data_envio', 'Envio', []) !!}
                    {!! Form::text('laboratorio_data_envio', $dados['dados_follow']['laboratorio_data_envio'], ['id' => 'laboratorio_data_envio', 'class' => 'form-control input-label data', 'placeholder' => 'Laboratório(Lab Dip/ Handlooms / Strike Off) Enviado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_data_recebido', 'Recebido', []) !!}
                    {!! Form::text('laboratorio_data_recebido', $dados['dados_follow']['laboratorio_data_recebido'], ['id' => 'laboratorio_data_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Laboratório(Lab Dip/ Handlooms / Strike Off) Recebido', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('laboratorio_aprovacao', 'Status da Aprovação', []) !!}
                    {!! Form::select('laboratorio_aprovacao', $dados['tipo_aprovacoes_parcial'], $dados['dados_follow']['laboratorio_aprovacao'], ['id' => 'laboratorio_aprovacao', 'class' => 'form-control input-label', 'placeholder' => 'Status da Aprovação', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('laboratorio_data_aprovacao', 'Data Aprovação', []) !!}
                    <div class="input-group">
                        {!! Form::text('laboratorio_data_aprovacao', $dados['dados_follow']['laboratorio_data_aprovacao'], ['id' => 'laboratorio_data_aprovacao', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Laboratório Realizado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                        <span class="input-group-addon border rounded-right" data-toggle="tooltip" data-placement="top" title="Histórico De Aprovação Laboratório" id="bt-view_historico_aprovacao"><i id="bt-view-historico_aprovacao" data-title="Histórico De Aprovação Laboratório" data-tipo="laboratorio" class="bt-list m-2" onclick="modalHistoricoAprovacao($(this))"></i></span>
                    </div>
                </div>
            </div>
            <hr>
            <b>{!! Form::label('termino_producao', 'Termino da Produção', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('tempo_producao_previsao', 'Previsão do Termino', []) !!}
                    {!! Form::text('tempo_producao_previsao', $dados['dados_follow']['tempo_producao_previsao_termino'], ['id' => 'tempo_producao_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Tempo de Produção Previsão' , 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('tempo_producao_termino', 'Termino', []) !!}
                    {!! Form::text('tempo_producao_termino', $dados['dados_follow']['tempo_producao_termino'], ['id' => 'tempo_producao_termino', 'class' => 'form-control input-label data', 'placeholder' => 'Tempo de Produção', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>
            <hr>
            <b>{!! Form::label('amostra_embarque', 'Amostra Embarque', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-2">
                    {!! Form::label('amostra_embarque_previsao', 'Previsão', []) !!}
                    {!! Form::text('amostra_embarque_previsao', $dados['dados_follow']['amostra_embarque_data_previsao_envio'], ['id' => 'amostra_embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Amostra Embarque Previsão', 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('amostra_embarque_enviado', 'Envio', []) !!}
                    {!! Form::text('amostra_embarque_enviado', $dados['dados_follow']['amostra_embarque_data_envio'], ['id' => 'amostra_embarque_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Amostra Embarque Enviado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('amostra_embarque_transportadora', 'Transportadora', []) !!}
                    {!! Form::text('amostra_embarque_transportadora', $dados['dados_follow']['amostra_embarque_transportadora'], ['id' => 'amostra_embarque_transportadora', 'class' => 'form-control input-label', 'placeholder' => 'Amostra Embarque Transportadora', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('amostra_embarque_awb', 'AWB', []) !!}
                    {!! Form::text('amostra_embarque_awb', $dados['dados_follow']['amostra_embarque_awb'], ['id' => 'amostra_embarque_awb', 'class' => 'form-control input-label', 'placeholder' => 'Amostra Embarque AWB', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-2">
                    {!! Form::label('amostra_embarque_data_recebido', 'Recebido', []) !!}
                    {!! Form::text('amostra_embarque_data_recebido', $dados['dados_follow']['amostra_embarque_data_recebido'], ['id' => 'amostra_embarque_data_recebido', 'class' => 'form-control input-label data', 'placeholder' => 'Amostra Embarque Recebido', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))" ]) !!}
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('amostra_embarque_aprovacao', 'Status da Aprovação', []) !!}
                    {!! Form::select('amostra_embarque_aprovacao', $dados['tipo_aprovacoes_parcial'], $dados['dados_follow']['amostra_embarque_aprovacao'], ['id' => 'amostra_embarque_aprovacao', 'class' => 'form-control input-label', 'placeholder' => 'Status da Aprovação', "data-tipo" => "string", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('aprovacao_amostra_embarque_previsao', 'Previsão da Aprovação', []) !!}
                    {!! Form::text('aprovacao_amostra_embarque_previsao', $dados['dados_follow']['amostra_embarque_data_previsao_aprovacao'], ['id' => 'aprovacao_amostra_embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Amostra Embarque Previsão','readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('amostra_embarque_data_aprovacao', 'Aprovação', []) !!}
                    <div class="input-group">
                        {!! Form::text('amostra_embarque_data_aprovacao', $dados['dados_follow']['amostra_embarque_data_aprovacao'], ['id' => 'amostra_embarque_data_envio', 'class' => 'form-control input-label data', 'placeholder' => 'Aprovação Amostra Embarque Enviado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                        <span class="input-group-addon border rounded-right" data-toggle="tooltip" data-placement="top" title="Histórico De Aprovação Amostra Embarque" id="bt-view_historico_aprovacao"><i id="bt-view-historico_aprovacao" data-title="Histórico De Aprovação Amostra Embarque" data-tipo="amostra_embarque" class="bt-list m-2" onclick="modalHistoricoAprovacao($(this))"></i></span>
                    </div>
                </div>
            </div>
            <hr>
            <b>{!! Form::label('termino_producao', 'Autorização de Embarque', ['']) !!}</b>
            <br/>
            <div class="row">
                <div class="form-group col-sm-3">
                    {!! Form::label('autorizacao_embarque_previsao', 'Previsão do Envio', []) !!}
                    {!! Form::text('autorizacao_embarque_previsao', $dados['dados_follow']['autorizacao_embarque_data_previsao_envio'], ['id' => 'autorizacao_embarque_previsao', 'class' => 'form-control input-label data', 'placeholder' => 'Autorizacao de Embarque Previsão', 'readonly', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
                <div class="form-group col-sm-3">
                    {!! Form::label('autorizacao_embarque_data_envio', 'Envio', []) !!}
                    {!! Form::text('autorizacao_embarque_data_envio', $dados['dados_follow']['autorizacao_embarque_data_envio'], ['id' => 'autorizacao_embarque_enviado', 'class' => 'form-control input-label data', 'placeholder' => 'Autorizacao de Embarque Enviado', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                </div>
            </div>

            <div class="col-sm-12 mt-5" id="button-bottom">
                {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
            </div>
        </div>
        <div class="tab-pane" id="importacao_follow_up_resumo" role="tabpanel" aria-labelledby="dados-tab">
            <div id="conteudo_resumo"></div>
        </div>
    </div>
</form>
<script>
    $(document).ready( function () {
        form_modal_importacao = $(document).find('#form_importacao_edt');

        form_modal_importacao.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_modal_importacao.find('.data').mask('00/00/0000');
        form_modal_importacao.find(".dias").mask('000');

        form_modal_importacao.find("#btn-salvar").off('click');
        form_modal_importacao.find("#btn-salvar").on('click', function(){
            editarDados(form_modal_importacao.serialize());
        });

        
        form_modal_importacao.find("#envio_das_cores").off("change");
        form_modal_importacao.find("#envio_das_cores").on("change", function(){
            previsacaoFollowUp(form_modal_importacao);
        });
        
        form_modal_importacao.find("#quality_sample_data_recebido").off("change");
        form_modal_importacao.find("#quality_sample_data_recebido").on("change", function(){
            previsaoAprovacaoQualitySample(form_modal_importacao); 
        }); 
         
        form_modal_importacao.find("#laboratorio_data_aprovacao").off("change");
        form_modal_importacao.find("#laboratorio_data_aprovacao").on("change", function(){
            previsaoTerminoProducao(form_modal_importacao); 
            previsaoAmostraEmbarque(form_modal_importacao);
        }); 
        
        form_modal_importacao.find("#laboratorio_data_aprovacao").off("change");
        form_modal_importacao.find("#laboratorio_data_aprovacao").on("change", function(){
            previsaoTerminoProducao(form_modal_importacao); 
            previsaoAmostraEmbarque(form_modal_importacao);
        }); 

        form_modal_importacao.find("#amostra_embarque_data_recebido").off("change");
        form_modal_importacao.find("#amostra_embarque_data_recebido").on("change", function(){
            previsaoAprovacaoAmostraEmbarque(form_modal_importacao); 
            previsaoAutorizacaoEmbarque(form_modal_importacao);
        }); 

        form_modal_importacao.find("#produto_codigo").off("change");
        form_modal_importacao.find("#produto_codigo").on("change", function(){
            carregarDados(form_modal_importacao);
        });

        $(document).find("#importacao_follow_up-resumo-tab").off("click");
        $(document).find("#importacao_follow_up-resumo-tab").on("click", function(){
            carregarResumo(form_modal_importacao);
        });

        previsacaoFollowUp(form_modal_importacao);
        previsaoAprovacaoQualitySample(form_modal_importacao); 
        previsaoTerminoProducao(form_modal_importacao); 
        previsaoAmostraEmbarque(form_modal_importacao);
        previsaoAprovacaoAmostraEmbarque(form_modal_importacao); 
        previsaoAutorizacaoEmbarque(form_modal_importacao);
    });

    function editarDados(data_form_modal_importacao){
        var formData = new FormData($(document).find('#form_importacao_edt')[0]);
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.editar') }}", 
            dataType: 'json',
            data: formData,
            method: 'POST',
            processData: false,
            contentType: false,
            success: function(callback){
                $(form_modal_importacao).parents('.modal').modal('hide');
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
        }else{
            if(input.indexOf("arquivo") != -1){
                var $input = $(form_modal_importacao).find("input[name='"+input+"[]'], select[name='"+input+"']");
            }else{
                var $input = $(form_modal_importacao).find("input[name='"+input+"'], select[name='"+input+"']");
            }
            
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
    
    function salvarEmMudanca($this){
        esconderPopoverTooltip();
        var campo = $this.attr("name");
        var valor = $this.val();
        var tipo = $this.data("tipo");
        var nome_exibicao = $this.attr("placeholder");
        var produto_codigo = form_modal_importacao.find("#produto_codigo").val();
        id = form_modal_importacao.find("#id").val();
        limparMesagemErroEdt(form_modal_importacao);
        $.ajax({
            url: '{{ route('importacao.dados_complementares_follow_up.salvar_mudanca')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                campo: campo,
                valor: valor,
                tipo: tipo,
                nome_exibicao: nome_exibicao,
                produto_codigo: produto_codigo,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
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
            form_modal_importacao.find("#envio_cor_data_previsao").val('');
        }else{
            if(envio_das_cores == 'cores_unicas'){
                intervalo_dias = 5;
            }else{
                intervalo_dias = 10;
            }
    
            data_proforma.setDate(data_proforma.getDate() + intervalo_dias);
            data_formatada = adicionaZero((data_proforma.getDate() )) + "/" + adicionaZero((data_proforma.getMonth() + 1)) + "/" + data_proforma.getFullYear(); 
    
            form_modal_importacao.find("#envio_cor_data_previsao").val(data_formatada);
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
            form_modal_importacao.find("#quality_sample_data_previsao_envio").val('');
        }else{
            intervalo_dias = 15;

            data_proforma.setDate(data_proforma.getDate() + intervalo_dias);
            data_formatada = adicionaZero((data_proforma.getDate() )) + "/" + adicionaZero((data_proforma.getMonth() + 1)) + "/" + data_proforma.getFullYear(); 
    
            form_modal_importacao.find("#quality_sample_data_previsao_envio").val(data_formatada);
        }
    }

    function previsaoAprovacaoQualitySample(form_modal_importacao){
        var quality_sample_data_recebido = form_modal_importacao.find("#quality_sample_data_recebido").val();

        if(quality_sample_data_recebido == '' || $.isEmptyObject(quality_sample_data_recebido)){
            form_modal_importacao.find("#quality_sample_data_previsao_aprovacao").val('');
        }else{
            intervalo_dias = 2;

            quality_sample_recebido_split = quality_sample_data_recebido.split('/');

            dia_quality_sample_recebido_split = quality_sample_recebido_split[0]; 
            mes_quality_sample_recebido_split = quality_sample_recebido_split[1];
            ano_quality_sample_recebido_split = quality_sample_recebido_split[2]; 

            quality_sample_data_recebido = new Date(ano_quality_sample_recebido_split, mes_quality_sample_recebido_split - 1, dia_quality_sample_recebido_split);
    
            quality_sample_data_recebido.setDate(quality_sample_data_recebido.getDate() + intervalo_dias);
            quality_sample_data_recebido = adicionaZero((quality_sample_data_recebido.getDate() )) + "/" + adicionaZero((quality_sample_data_recebido.getMonth() + 1)) + "/" + quality_sample_data_recebido.getFullYear(); 
    
            form_modal_importacao.find("#quality_sample_data_previsao_aprovacao").val(quality_sample_data_recebido);
        }
    }

    function previsaoLaboratorio(form_modal_importacao){
        var envio_cor_data_previsao = form_modal_importacao.find("#envio_cor_data_previsao").val();
        var envio_das_cores = form_modal_importacao.find("#envio_das_cores").val();

        if(envio_cor_data_previsao == '' || $.isEmptyObject(envio_cor_data_previsao)){
            form_modal_importacao.find("#laboratorio_data_previsao_envio").val('');
        }else{
            if(envio_das_cores == 'cores_unicas'){
                intervalo_dias = 15;
            }else{
                intervalo_dias = 20;
            }

            envio_das_cores_previsao_split = envio_cor_data_previsao.split('/');

            dia_envio_das_cores_previsao_split = envio_das_cores_previsao_split[0]; 
            mes_envio_das_cores_previsao_split = envio_das_cores_previsao_split[1];
            ano_envio_das_cores_previsao_split = envio_das_cores_previsao_split[2]; 

            laboratorio_data_previsao_envio = new Date(ano_envio_das_cores_previsao_split, mes_envio_das_cores_previsao_split - 1, dia_envio_das_cores_previsao_split);
    
            laboratorio_data_previsao_envio.setDate(laboratorio_data_previsao_envio.getDate() + intervalo_dias);
            laboratorio_data_previsao_envio = adicionaZero((laboratorio_data_previsao_envio.getDate() )) + "/" + adicionaZero((laboratorio_data_previsao_envio.getMonth() + 1)) + "/" + laboratorio_data_previsao_envio.getFullYear(); 
    
            form_modal_importacao.find("#laboratorio_data_previsao_envio").val(laboratorio_data_previsao_envio);
        }
    }

    function previsaoTerminoProducao(form_modal_importacao){
        var status_aprovacao_laboratorio = form_modal_importacao.find("#laboratorio_data_aprovacao").val();
        var envio_das_cores = form_modal_importacao.find("#envio_das_cores").val();
        var data_aprovacao_laboratorio = form_modal_importacao.find("#laboratorio_data_aprovacao").val();

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
        var amostra_embarque_data_recebido = form_modal_importacao.find("#amostra_embarque_data_recebido").val();

        if(amostra_embarque_data_recebido == '' || $.isEmptyObject(amostra_embarque_data_recebido)){
            form_modal_importacao.find("#aprovacao_amostra_embarque_previsao").val('');
        }else{
            intervalo_dias = 2;

            amostra_embarque_recebido_split = amostra_embarque_data_recebido.split('/');

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

        if(aprovacao_amostra_embarque_previsao == '' || $.isEmptyObject(amostra_embarque_data_recebido)){
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
        var produto_codigo = form_modal_importacao.find("#produto_codigo").val();

        $.ajax({
            url: '{{ route('importacao.dados_complementares_follow_up.modal.historico_aprovacao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tipo: tipo,
                id_follow_up: id_follow_up,
                produto_codigo: produto_codigo,
            },
            success: function (data){
                createModal("modal_historico_aprovacao", title, data, '');
                var modal = $(document).find("#modal_historico_aprovacao");
            }
        });
    }

    function carregarDados(form_modal_importacao){
        var formData = new FormData($(document).find('#form_importacao_edt')[0]);
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.carregar_dados') }}", 
            dataType: 'json',
            data: formData,
            method: 'POST',
            processData: false,
            contentType: false,
            success: function(callback){
                form_modal_importacao.find("#envio_das_cores").val(callback.response.tipo_cor);
                form_modal_importacao.find("#envio_cor_data_previsao").val(callback.response.envio_cor_data_previsao);
                form_modal_importacao.find("#envio_cor_data_envio").val(callback.response.envio_cor_data_envio);
                form_modal_importacao.find("#envio_cor_data_recebido").val(callback.response.envio_cor_data_recebido);
                form_modal_importacao.find("#quality_sample_aprovacao").val(callback.response.quality_sample_aprovacao);
                form_modal_importacao.find("#quality_sample_data_previsao_envio").val(callback.response.quality_sample_data_previsao_envio);
                form_modal_importacao.find("#quality_sample_data_envio").val(callback.response.quality_sample_data_envio);
                form_modal_importacao.find("#quality_sample_data_recebido").val(callback.response.quality_sample_data_recebido);
                form_modal_importacao.find("#quality_sample_data_previsao_aprovacao").val(callback.response.quality_sample_data_previsao_aprovacao);
                form_modal_importacao.find("#quality_sample_data_aprovacao").val(callback.response.quality_sample_data_aprovacao);
                form_modal_importacao.find("#laboratorio_aprovacao").val(callback.response.laboratorio_aprovacao);
                form_modal_importacao.find("#laboratorio_data_previsao_envio").val(callback.response.laboratorio_data_previsao_envio);
                form_modal_importacao.find("#laboratorio_data_envio").val(callback.response.laboratorio_data_envio);
                form_modal_importacao.find("#laboratorio_data_recebido").val(callback.response.laboratorio_data_recebido);
                form_modal_importacao.find("#laboratorio_data_aprovacao").val(callback.response.laboratorio_data_aprovacao);
                form_modal_importacao.find("#tempo_producao_previsao_termino").val(callback.response.tempo_producao_previsao_termino);
                form_modal_importacao.find("#tempo_producao_termino").val(callback.response.tempo_producao_termino);
                form_modal_importacao.find("#amostra_embarque_aprovacao").val(callback.response.amostra_embarque_aprovacao);
                form_modal_importacao.find("#amostra_embarque_data_previsao_envio").val(callback.response.amostra_embarque_data_previsao_envio);
                form_modal_importacao.find("#amostra_embarque_data_envio").val(callback.response.amostra_embarque_data_envio);
                form_modal_importacao.find("#amostra_embarque_data_recebido").val(callback.response.amostra_embarque_data_recebido);
                form_modal_importacao.find("#amostra_embarque_data_previsao_aprovacao").val(callback.response.amostra_embarque_data_previsao_aprovacao);
                form_modal_importacao.find("#amostra_embarque_data_aprovacao").val(callback.response.amostra_embarque_data_aprovacao);
                form_modal_importacao.find("#autorizacao_embarque_data_previsao_envio").val(callback.response.autorizacao_embarque_data_previsao_envio);
                form_modal_importacao.find("#autorizacao_embarque_data_envio").val(callback.response.autorizacao_embarque_data_envio);
                form_modal_importacao.find("#envio_cor_data_revisao").val(callback.response.envio_cor_data_revisao);
                form_modal_importacao.find("#quality_sample_transportadora").val(callback.response.quality_sample_transportadora);
                form_modal_importacao.find("#quality_sample_awb").val(callback.response.quality_sample_awb);
                form_modal_importacao.find("#amostra_embarque_transportadora").val(callback.response.amostra_embarque_transportadora);
                form_modal_importacao.find("#amostra_embarque_awb").val(callback.response.amostra_embarque_awb);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function carregarResumo(form_modal_importacao){
        var formData = new FormData($(document).find('#form_importacao_edt')[0]);
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.carregar_resumo') }}", 
            dataType: 'json',
            data: formData,
            method: 'POST',
            processData: false,
            contentType: false,
            success: function(callback){
                var resumo = "";
                for (var index in callback.response){
                    resumo = resumo + "<h5>"+callback.response[index].produto+" - Tipo: "+callback.response[index].tipo_cor+"</h5><br>";
                    resumo = resumo+'<table class="table">'+
                        '<tr>'+
                            '<th width="12%"></th>'+
                            '<th width="11%">Previsão Envio/Termino</th>'+
                            '<th width="11%">Enviado/Termino</th>'+
                            '<th width="11%">Transportadora</th>'+
                            '<th width="11%">AWB</th>'+
                            '<th width="11%">Recebido</th>'+
                            '<th width="11%">Revisão</th>'+
                            '<th width="11%">Data Apr.</th>'+
                            '<th width="12%">Status Apr.</th>'+
                        '</tr>'+
                        '<tr>'+
                            '<td>Envio da Cores</td>'+
                            '<td>'+callback.response[index].envio_cor_data_previsao+'</td>'+
                            '<td>'+callback.response[index].envio_cor_data_envio+'</td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td>'+callback.response[index].envio_cor_data_recebido+'</td>'+
                            '<td>'+callback.response[index].envio_cor_data_revisao+'</td>'+
                            '<td></td>'+
                            '<td></td>'+
                        '</tr>'+
                        '<tr>'+
                            '<td>Quality Sample</td>'+
                            '<td>'+callback.response[index].quality_sample_data_previsao_envio+'</td>'+
                            '<td>'+callback.response[index].quality_sample_data_envio+'</td>'+
                            '<td>'+callback.response[index].quality_sample_transportadora+'</td>'+
                            '<td>'+callback.response[index].quality_sample_awb+'</td>'+
                            '<td>'+callback.response[index].quality_sample_data_recebido+'</td>'+
                            '<td></td>'+
                            '<td>'+callback.response[index].quality_sample_data_aprovacao+'</td>'+
                            '<td>'+callback.response[index].quality_sample_aprovacao+'</td>'+
                        '</tr>'+
                        '<tr>'+
                            '<td>Laboratório</td>'+
                            '<td>'+callback.response[index].laboratorio_data_previsao_envio+'</td>'+
                            '<td>'+callback.response[index].laboratorio_data_envio+'</td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td>'+callback.response[index].laboratorio_data_recebido+'</td>'+
                            '<td></td>'+
                            '<td>'+callback.response[index].laboratorio_data_aprovacao+'</td>'+
                            '<td>'+callback.response[index].laboratorio_aprovacao+'</td>'+
                        '</tr>'+
                        '<tr>'+
                            '<td>Termino da Produção</td>'+
                            '<td>'+callback.response[index].tempo_producao_previsao_termino+'</td>'+
                            '<td>'+callback.response[index].tempo_producao_termino+'</td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                        '</tr>'+
                        '<tr>'+
                            '<td>Amostra Embarque</td>'+
                            '<td>'+callback.response[index].amostra_embarque_data_previsao_envio+'</td>'+
                            '<td>'+callback.response[index].amostra_embarque_data_envio+'</td>'+
                            '<td>'+callback.response[index].amostra_embarque_transportadora+'</td>'+
                            '<td>'+callback.response[index].amostra_embarque_awb+'</td>'+
                            '<td>'+callback.response[index].amostra_embarque_data_recebido+'</td>'+
                            '<td></td>'+
                            '<td>'+callback.response[index].amostra_embarque_data_aprovacao+'</td>'+
                            '<td>'+callback.response[index].amostra_embarque_aprovacao+'</td>'+
                        '</tr>'+
                        '<tr>'+
                            '<td>Autorização do Embarque</td>'+
                            '<td>'+callback.response[index].autorizacao_embarque_data_previsao_envio+'</td>'+
                            '<td>'+callback.response[index].autorizacao_embarque_data_envio+'</td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                            '<td></td>'+
                        '</tr>'+
                    '</table></br></br><hr>';
                }
                $(document).find("#conteudo_resumo").html(resumo);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                message("Atenção", callback.responseJSON.message);
            }
        });
    }
</script>
@endsection