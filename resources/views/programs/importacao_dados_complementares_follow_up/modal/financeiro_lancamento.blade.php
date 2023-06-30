@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
    @if($importacaoObj->pago == false)
        <li class="nav-item">
            @if($inicio == 'lancamento')
            <a class="nav-link active" id="financeiro_lancamento-lancamento-tab" data-toggle="tab" href="#financeiro_lancamento_lancamento" role="tab" aria-controls="financeiro_lancamento_lancamento" aria-selected="false">Lançamento</a>
            @else
            <a class="nav-link" id="financeiro_lancamento-lancamento-tab" data-toggle="tab" href="#financeiro_lancamento_lancamento" role="tab" aria-controls="financeiro_lancamento_lancamento" aria-selected="false">Lançamento</a>
            @endif
        </li>
    @endif
	<li class="nav-item">
        @if($inicio == 'historico' || $importacaoObj->pago == true)
        <a class="nav-link active" id="financeiro_lancamento-historico-tab" data-toggle="tab" href="#financeiro_lancamento_historico" role="tab" aria-controls="financeiro_lancamento_historico" aria-selected="false">Histórico</a>
        @else
        <a class="nav-link" id="financeiro_lancamento-historico-tab" data-toggle="tab" href="#financeiro_lancamento_historico" role="tab" aria-controls="financeiro_lancamento_historico" aria-selected="false">Histórico</a>
        @endif
    </li>
    @if($importacaoObj->pago == false)
        <li class="nav-item">
            <a class="nav-link" id="financeiro_lancamento-previsto-tab" data-toggle="tab" href="#financeiro_lancamento_previsto" role="tab" aria-controls="financeiro_lancamento_historico" aria-selected="false">Previsto</a>
        </li>
    @endif
</ul>
<form action="{{ route('importacao.dados_complementares_follow_up.adicionar_financeiro_lancamento') }}" id="frm_financeiro_lancamento_add" name="frm_financeiro_lancamento_add" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $id, ['id' => 'id']) !!}
    {!! Form::hidden('id_lancamento', '', ['id' => 'id_lancamento']) !!}
    <div class="tab-content pt-3" id="ImportacaoHeaderContainer">
        @if($importacaoObj->pago == false)
            @if($inicio == 'lancamento')
            <div class="tab-pane show active" id="financeiro_lancamento_lancamento" role="tabpanel" aria-labelledby="dados-tab">
            @else
            <div class="tab-pane" id="financeiro_lancamento_lancamento" role="tabpanel" aria-labelledby="dados-tab">
            @endif
                <div class="content-dialog-table">
                    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-dialog_lancamento_futuro">
                        <thead>
                            <th>Modalidade</th>
                            <th class="tb_date">Data Previsão de Pagamento</th>
                            <th class="tb_number">Valor Câmbio US$</th>
                            <th class="tb_number">Valor R$</th>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <hr>
                    <br>
                    <div class="form-row">
                        <div class="form-group  col-sm-6">
                            {{ Form::label('associacao', 'Pagamento') }}
                            {!! Form::select('associacao', $associacao, '', ['id' => 'associacao', 'class' => 'form-control', 'placeholder' => 'Pagamento']) !!}
                        </div>
                    </div>
                    <div class="show-on-pagamento">
                        <div class="form-row">
                            <div class="form-group  col-sm-12">
                                <div class="show-on-normal">
                                    {{ Form::label('data_cambio', 'Data Câmbio') }}
                                </div>
                                <div class="show-on-imposto show-on-carta_x">
                                    {{ Form::label('data_cambio', 'Data Pagamento') }}
                                </div>
                                {!! Form::text('data_cambio', '', ['id' => 'data_cambio', 'class' => 'form-control data', 'placeholder' => 'Data Câmbio']) !!}
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group  col-sm-6 show-on-normal">
                                {{ Form::label('tipo_fechamento', 'Tipo Fechamento') }}
                                {!! Form::select('tipo_fechamento', $tipo_fechamento, 'cambio_pronto', ['id' => 'tipo_fechamento', 'class' => 'form-control', 'placeholder' => 'Tipo Fechamento']) !!}
                            </div>
                            <div class="form-group  col-sm-6 show-on-normal">
                                {{ Form::label('modalidade', 'Modalidade') }}
                                {!! Form::select('modalidade', $modalidades, '', ['id' => 'modalidade', 'class' => 'form-control', 'placeholder' => 'Modalidade']) !!}
                            </div>
                        </div>                    
                        <div class="show-on-normal show-on-carta_x">
                            <div class="form-row">
                                <div class="form-group  col-sm-12">
                                    {{ Form::label('valor_cambio', 'Valor Câmbio') }}
                                    {!! Form::text('valor_cambio', '', ['id' => 'valor_cambio', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Câmbio']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="show-on-normal show-on-carta_x">
                            <div class="form-row">
                                <div class="form-group  col-sm-12">
                                    {{ Form::label('taxa', 'Taxa R$') }}
                                    {!! Form::text('taxa', '', ['id' => 'taxa', 'class' => 'form-control decimal_quatro_casas text-right', 'placeholder' => 'Taxa R$']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group  col-sm-12">
                                {{ Form::label('valor', 'Valor R$') }}
                                {!! Form::text('valor', '', ['id' => 'valor', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor R$']) !!}
                            </div>
                        </div>
                        <div class="show-on-normal">
                            <div class="form-row">
                                <div class="form-group  col-sm-12">
                                    {{ Form::label('banco', 'Banco') }}
                                    {!! Form::text('banco', '', ['id' => 'banco', 'class' => 'form-control', 'placeholder' => 'Banco']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="show-on-normal">
                            <div class="form-row">
                                <div class="form-group  col-sm-12">
                                    {{ Form::label('numero_contrato_cambio', 'Nº Contrato de Câmbio') }}
                                    {!! Form::text('numero_contrato_cambio', '', ['id' => 'numero_contrato_cambio', 'class' => 'form-control text-right', 'placeholder' => 'Nº Contrato de Câmbio']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="show-on-normal">
                            <div class="form-row">
                                <div class="form-group  col-sm-12">
                                    {{ Form::label('numero_contrato_banco', 'Nº Contrato de Bacen') }}
                                    {!! Form::text('numero_contrato_banco', '', ['id' => 'numero_contrato_banco', 'class' => 'form-control text-right', 'placeholder' => 'Nº Contrato de Bacen']) !!}
                                </div>
                            </div>
                        </div>
                        <div id="reds_dados" name="reds_dados">
                            {!! Form::hidden('red_adicionado', '', ['id' => 'red_adicionado']) !!}
                            {!! Form::hidden('reds', '', ['id' => 'reds']) !!}
                            <div class="show-on-normal">
                                <div class="form-row">
                                    <div class="form-group  col-sm-3">
                                        {{ Form::label('red_documento', 'Hedge Documento') }}
                                        <div class="input-group">
                                            {!! Form::text('red_documento', '', ['id' => 'red_documento', 'class' => 'form-control', 'placeholder' => 'Hedge Documento']) !!}
                                            <span class="input-group-addon border rounded-right" id="bt-search-red_documento"><i class="bt-view m-2"></i></span>
                                        </div>
                                    </div>
                                    <div class="form-group  col-sm-3">
                                        {{ Form::label('red_saldo', 'Hedge Saldo') }}
                                        {!! Form::text('red_saldo', '', ['id' => 'red_saldo', 'class' => 'form-control decimal text-right', 'placeholder' => 'Hedge Saldo', 'disabled']) !!}
                                    </div>
                                    <div class="form-group  col-sm-3">
                                        {{ Form::label('red_taxa_cambio', 'Hedge Taxa Câmbio') }}
                                        {!! Form::text('red_taxa_cambio', '', ['id' => 'red_taxa_cambio', 'class' => 'form-control decimal_quatro_casas text-right', 'placeholder' => 'Hedge Taxa Câmbio', 'disabled']) !!}
                                    </div>
                                    <div class="form-group  col-sm-3">
                                        {{ Form::label('red_valor_utilizado', 'Hedge Valor A Ser Utilizado') }}
                                        <div class="input-group" id="usuario_group">
                                            {!! Form::text('red_valor_utilizado', '', ['id' => 'red_valor_utilizado', 'class' => 'form-control decimal text-right', 'placeholder' => 'Hedge Valor A Ser Utilizado']) !!}
                                            <span class="input-group-addon border-right border-top border-bottom rounded-right btn-line-add-span">
                                                <i class="btn-line-add rounded-right" id="btn-add_red"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="content-dialog-table">
                                    <div class="content-table">
                                        <table class="table table-striped" id="table-importacao_financeiro_lancamento-reds">
                                            <thead>
                                                <th>Hedge Documento</th>
                                                <th class="tb_number">Valor Hedge Utilizado</th>
                                                <th class="tb_number">Valor Hedge Saldo</th>
                                                <th class="td_acao"></th>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                            <tfoot>
                                                <td class="tb_number">Total:</td>
                                                <td class="tb_number" id='total_reds_utilizado' name='total_reds_utilizado'></td>
                                                <td class="tb_number" id='total_reds_saldo' name='total_reds_saldo'></td>
                                                <td class="td_acao"></td>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="show-on-adicionar">
                            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}
                        </div>
                        <div class="show-on-editar">
                            {{ Form::button('Editar', array('class' => 'btn btn-success float-right', 'id' => 'bt_editar')) }}
                            <div class="float-right">&nbsp;&nbsp;&nbsp;</div>
                            {{ Form::button('Cancelar', array('class' => 'btn btn-danger float-right', 'id' => 'bt_cancelar')) }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if($inicio == 'historico' || $importacaoObj->pago == true)
        <div class="tab-pane show active" id="financeiro_lancamento_historico" role="tabpanel" aria-labelledby="dados-tab">
        @else
        <div class="tab-pane" id="financeiro_lancamento_historico" role="tabpanel" aria-labelledby="dados-tab">
        @endif
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
                        <th></th>
                        <th></th>
                    </thead>
                    <tbody>
                        @foreach($lancamentos as $lancamento)
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
                            <td><a href="#" data-route="{{route('importacao.dados_complementares_follow_up.buscar_editar_financeiro_lancamento')}}" data-id="{{$lancamento['id']}}" class="bt-edit" data-toggle="tooltip" data-placement="top" title="Editar" onclick="getEditarLancamento($(this), $(this).parents('tr'))"></a></td>
                            <td><a href="#" data-route="{{route('importacao.dados_complementares_follow_up.buscar_editar_financeiro_lancamento')}}" data-id="{{$lancamento['id']}}" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir" onclick="excluirLacamento($(this), $(this).parents('tr'))"></a></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <td class="tb_number">Total</td>
                        <td class="tb_number" id="cambio_valor_total">{{$total['cambio_valor_total']}}</td>
                        <td></td>
                        <td class="tb_number" id="real_taxa_total"></td>
                        <td class="tb_number" id="real_valor_total">{{$total['real_valor_total']}}</td>
                        <td></td>
                        <td></td>
                        <td class="tb_number"></td>
                        <td class="tb_number"></td>
                        <td></td>
                        <td></td>
                    </tfoot>
                </table>
            </div>
        </div>
        @if($importacaoObj->pago == false)
            <div class="tab-pane" id="financeiro_lancamento_previsto" role="tabpanel" aria-labelledby="dados-tab">
                <div class="form-row">
                    <div class="form-group  col-sm-3">
                        {{ Form::label('preco_fob_financeiro', 'Valor Total Fornecedor(US$)') }}<a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Valor Total Fornecedor = Valor FOB Produtos + Frete Fornecedor" style="color: black;"></a>
                        {!! Form::text('preco_fob_financeiro', $preco_fob_total, ['id' => 'preco_fob_financeiro', 'class' => 'form-control text-right', 'placeholder' => '', "disabled"]) !!}
                    </div>
                    <div class="form-group  col-sm-3">
                        {{ Form::label('etd_financeiro', 'Embarque ETD') }}
                        {!! Form::text('etd_financeiro', $data_embarque_realizado, ['id' => 'etd_financeiro', 'class' => 'form-control text-right', 'placeholder' => '', "disabled"]) !!}
                    </div>
                    <div class="form-group  col-sm-3">
                        @if($realizado_chegada_porto)
                            {{ Form::label('eta_financeiro', 'Chegada no Porto ETA') }}
                        @elseif($previsao_chegada_porto)
                            {{ Form::label('eta_financeiro', 'Previsão Chegada no Porto ETA') }}
                        @else
                            {{ Form::label('eta_financeiro', 'Previsão Chegada') }}
                        @endif
                        {!! Form::text('eta_financeiro', $data_pagamento_imposto, ['id' => 'preco_fob_financeiro', 'class' => 'form-control text-right', 'placeholder' => '', "disabled"]) !!}
                    </div>
                    <div class="form-group  col-sm-3">
                        {{ Form::label('parcela_antecipacao_previsto', 'QTD. Antecipação') }}
                        {!! Form::text('parcela_antecipacao_previsto', '', ['id' => 'parcela_antecipacao_previsto', 'class' => 'form-control text-right', 'placeholder' => 'QTD. Antecipação']) !!}
                    </div>
                </div>
                <div class="content-dialog-table">
                    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-dialog_historico_previsto">
                        <thead>
                            <th>Modalidade</th>
                            <th class="tb_date">Data Previsão de Pagamento</th>
                            <th class="tb_number">Valor Câmbio US$</th>
                            <th class="tb_number">Valor R$</th>
                            <th class="tb_date">Data Efetivação</th>
                        </thead>
                        <tbody>
                            @if(!empty($previstos['antecipado']))
                            <tr>
                                <td>{{$previstos['antecipado']['modalidade']}}</td>
                                <td class="tb_date">{!! Form::text('data_adiantamento', $previstos['antecipado']['cambio_data'], ['id' => 'data_adiantamento', 'class' => 'form-control data', 'placeholder' => 'Data Adiantamento']) !!}</td>
                                <td class="tb_number">{!! Form::text('valor_adiantamento', $previstos['antecipado']['cambio_valor'], ['id' => 'valor_adiantamento', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Adiantamento U$']) !!}</td>
                                <td class="tb_number"></td>
                                <td>{{$previstos['antecipado']['baixa_data']}}</td>
                            </tr>
                            @endif
                            @if(!empty($previstos['imposto']))
                            <tr>
                                <td>{{$previstos['imposto']['modalidade']}}<a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="top" title="" data-original-title="Imposto = II + PIS + Cofins + Taxa Siscomex + AFRMM + SDA + Expediente + Valor Li + Agência Marítima + Laudo + Seguro + Armazenagem + Transporte Rodoviário" style="color: black;"></a></td>
                                <td class="tb_date">{!! Form::text('data_previsto_imposto', $previstos['imposto']['cambio_data'], ['id' => 'data_previsto_imposto', 'class' => 'form-control data', 'placeholder' => 'Data Imposto']) !!}</td>
                                <td class="tb_number">{{$previstos['imposto']['cambio_valor']}}</td>
                                <td class="tb_number">{!! Form::text('valor_previsto_imposto', $previstos['imposto']['real_valor'], ['id' => 'valor_previsto_imposto', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Imposto R$']) !!}</td>
                                <td>{{$previstos['imposto']['baixa_data']}}</td>
                            </tr>
                            @endif
                            
                            @if(!empty($previstos['carta_x']))
                            <tr>
                                <td>{{$previstos['carta_x']['modalidade']}}</td>
                                <td class="tb_date">{!! Form::text('data_carta_x', $previstos['carta_x']['cambio_data'], ['id' => 'data_carta_x', 'class' => 'form-control data', 'placeholder' => 'Data Carta X']) !!}</td>
                                <td class="tb_number">{!! Form::text('valor_carta_x', $previstos['carta_x']['cambio_valor'], ['id' => 'valor_carta_x', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Carta X U$']) !!}</td>
                                <td class="tb_number"></td>
                                <td>{{$previstos['carta_x']['baixa_data']}}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    <hr>
                    <br>    
                    <div class="form-row">
                        <div class="form-group  col-sm-6">
                            {{ Form::label('parcela_previsto', 'Qtd. Parcela Fornecedor') }}
                            {!! Form::text('parcela_previsto', $parcelas, ['id' => 'parcela_previsto', 'class' => 'form-control text-right', 'placeholder' => 'Quantidade Parcela']) !!}
                        </div>
                        <div class="form-group  col-sm-6">
                            {{ Form::label('valor_total_parcela_fornecedor', 'Valor Saldo Fornecedor(US$)') }}
                            {!! Form::text('valor_total_parcela_fornecedor', $valor_total_parcela_fornecedor, ['id' => 'valor_total_parcela_fornecedor', 'class' => 'form-control text-right', 'placeholder' => '', "disabled"]) !!}
                        </div>
                    </div>
                    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-dialog_historico_previsto_parcelas">
                        <thead>
                            <th>Modalidade</th>
                            <th class="tb_date">Data Previsão de Pagamento</th>
                            <th class="tb_number">Valor Câmbio US$</th>
                            <th class="tb_date">Data Efetivação</th>
                        </thead>
                        <tbody>
                            @if(!empty($previstos['parcela']))
                                @foreach($previstos['parcela'] as $index => $parcela)
                                <tr>
                                    <td>{{$parcela['modalidade']}}</td>
                                    <td class="tb_date">{!! Form::text('data_parcela[]', $parcela['cambio_data'], ['id' => 'data_parcela_', 'class' => 'form-control data', 'placeholder' => 'Data Adiantamento']) !!}</td>
                                    <td class="tb_number">{!! Form::text('valor_parcela[]', $parcela['cambio_valor'], ['id' => 'valor_parcela_', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Adiantamento U$']) !!}</td>
                                    <td>{{$parcela['baixa_data']}}</td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>              
                <div class="show-on-adicionar">
                    {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar_previsto')) }}
                </div>
            </div>
        @endif        
    </div>
</form>
<script type="text/javascript">

    $(function(){
        form_financeiro_lancamento_add = $(document).find('#frm_financeiro_lancamento_add'); 

        form_financeiro_lancamento_add.find('.show-on-pagamento').hide();

        form_financeiro_lancamento_add.find('.show-on-adicionar').show();   
        form_financeiro_lancamento_add.find('.show-on-editar').hide();   

        carregarTabelaFinanceiroLancamento();

        form_financeiro_lancamento_add.find("#bt_salvar").off('click');
        form_financeiro_lancamento_add.find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form_financeiro_lancamento_add = $(document).find('#frm_financeiro_lancamento_add');
            var url = form_financeiro_lancamento_add.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form_financeiro_lancamento_add.serialize(),
                method: 'POST',
                success: function(callback){
                    limparCamposFinanceiroLancamento();
                    message("Atenção", "Dados salvos com sucesso!");
                    carregarTabelaFinanceiroLancamento();
                },
                error: function(callback){
                    hide_loader();
                    var errors = callback.responseJSON.error;
                    form_financeiro_lancamento_add.find('.error-message').remove();
                    form_financeiro_lancamento_add.find('div, input, select, textarea').each(function(){
                        if($(this).hasClass("error-input")){
                            $(this).removeClass("error-input")
                        }
                    });
                    for(var field in errors){
                        showErrorsInputs(form_financeiro_lancamento_add, field, errors[field])
                    }
                    if(form_financeiro_lancamento_add.find('.error-message').length){
                        form_financeiro_lancamento_add.find('.error-message').eq(0).focus();
                    }
                }
            });
        });

        form_financeiro_lancamento_add.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_financeiro_lancamento_add.find('.data').mask('00/00/0000');
        
        form_financeiro_lancamento_add.find(".decimal").maskMoney({thousands:'.', decimal:','});
        form_financeiro_lancamento_add.find(".decimal_quatro_casas").maskMoney({thousands:'.', decimal:',', precision: 4});

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
                { "class": "tb_date", targets: "tb_date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 1, 'asc' ]]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_historico_lancamento.draw();

        table_filters_dialog_historico_previsto = $('#table-dialog_historico_previsto').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "paging": false,
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
                { "class": "tb_date", targets: "tb_date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 1, 'asc' ]]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_historico_previsto.draw();

        table_filters_dialog_historico_previsto_parcelas = $('#table-dialog_historico_previsto_parcelas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "paging": false,
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
                { "class": "tb_date", targets: "tb_date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 0, 'asc' ]]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_historico_previsto_parcelas.draw();

        table_filters_dialog_lancamento_futuro= $('#table-dialog_lancamento_futuro').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 6,
            "paging": true,
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
                { "class": "tb_date", targets: "tb_date" },
                { "class": "tb_icone", targets: "icone"}
            ]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_lancamento_futuro.draw();

        table_filters_dialog_lancamento_reds = $('#table-importacao_financeiro_lancamento-reds').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 6,
            "paging": false,
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
                { "class": "tb_date", targets: "tb_date" },
                { "class": "tb_icone", targets: "icone"}
            ]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_lancamento_reds.draw();

        form_financeiro_lancamento_add.find("#bt_editar").off('click');
        form_financeiro_lancamento_add.find("#bt_editar").on('click', function(event){
            event.stopPropagation();
            editarLacamento();
        });
        form_financeiro_lancamento_add.find("#bt_cancelar").off('click');
        form_financeiro_lancamento_add.find("#bt_cancelar").on('click', function(event){
            event.stopPropagation();
            cancelarEdicao();
        });

        form_financeiro_lancamento_add.find("#bt_salvar_previsto").off('click');
        form_financeiro_lancamento_add.find("#bt_salvar_previsto").on('click', function(event){
            event.stopPropagation();
            var form_financeiro_lancamento_add = $(document).find('#frm_financeiro_lancamento_add');

            $.ajax({
                url: "{{ route('importacao.dados_complementares_follow_up.adicionar_financeiro_previsto') }}",
                dataType: 'json',
                data: form_financeiro_lancamento_add.serialize(),
                method: 'POST',
                success: function(callback){
                    message("Atenção", "Dados salvos com sucesso!");
                    carregarTabelaFinanceiroLancamento();
                },
                error: function(callback){
                    hide_loader();
                    var errors = callback.responseJSON.error;
                    form_financeiro_lancamento_add.find('.error-message').remove();
                    form_financeiro_lancamento_add.find('div, input, select, textarea').each(function(){
                        if($(this).hasClass("error-input")){
                            $(this).removeClass("error-input")
                        }
                    });
                    for(var field in errors){
                        showErrorsInputs(form_financeiro_lancamento_add, field, errors[field])
                    }
                    if(form_financeiro_lancamento_add.find('.error-message').length){
                        form_financeiro_lancamento_add.find('.error-message').eq(0).focus();
                    }
                }
            });
        });

        form_financeiro_lancamento_add.find("#parcela_previsto").off("keyup");
        form_financeiro_lancamento_add.find("#parcela_previsto").on('keyup', function(){
            geracaoParcela(form_financeiro_lancamento_add);
        });

        form_financeiro_lancamento_add.find("#parcela_antecipacao_previsto").off("keyup");
        form_financeiro_lancamento_add.find("#parcela_antecipacao_previsto").on('keyup', function(){
            geracaoParcelaAntecipacao(form_financeiro_lancamento_add);
        });

        form_financeiro_lancamento_add.find("#red_documento").autocomplete(optionsAutoCompleteRedDocumento());

        form_financeiro_lancamento_add.find("#bt-search-red_documento").on('click', function(){
            showModalBuscarRedModal(form_financeiro_lancamento_add);
        });

        form_financeiro_lancamento_add.find("#valor_cambio").off("keyup");
        form_financeiro_lancamento_add.find("#valor_cambio").on('keyup', function(){
            valor_cambio = form_financeiro_lancamento_add.find("#valor_cambio").val();
            taxa = form_financeiro_lancamento_add.find("#taxa").val();

            if(valor_cambio == "" || $.isEmptyObject(valor_cambio) || taxa == "" || $.isEmptyObject(taxa)){

            }else{
                valor_cambio = valor_cambio.replace(/\./g,"").replace(/\,/g, ".");
                valor_cambio = parseFloat(valor_cambio);

                taxa = taxa.replace(/\./g,"").replace(/\,/g, ".");
                taxa = parseFloat(taxa);

                valor = valor_cambio * taxa;

                form_financeiro_lancamento_add.find("#valor").val(numberToReal(valor.toFixed(2)));
            }
        });

        form_financeiro_lancamento_add.find("#taxa").off("keyup");
        form_financeiro_lancamento_add.find("#taxa").on('keyup', function(){
            valor_cambio = form_financeiro_lancamento_add.find("#valor_cambio").val();
            taxa = form_financeiro_lancamento_add.find("#taxa").val();

            if(valor_cambio == "" || $.isEmptyObject(valor_cambio) || taxa == "" || $.isEmptyObject(taxa)){

            }else{
                valor_cambio = valor_cambio.replace(/\./g,"").replace(/\,/g, ".");
                valor_cambio = parseFloat(valor_cambio);

                taxa = taxa.replace(/\./g,"").replace(/\,/g, ".");
                taxa = parseFloat(taxa);

                valor = valor_cambio * taxa;

                form_financeiro_lancamento_add.find("#valor").val(numberToReal(valor.toFixed(2)));
            }
        });

        form_financeiro_lancamento_add.find("#associacao").off("change");
        form_financeiro_lancamento_add.find("#associacao").on('change', function(){
            if(form_financeiro_lancamento_add.find("#associacao").val() == ""){
                form_financeiro_lancamento_add.find('.show-on-pagamento').hide();
            }else{
                form_financeiro_lancamento_add.find('.show-on-pagamento').show();

                if(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('regiao') === "Imposto"){
                    form_financeiro_lancamento_add.find('.show-on-imposto').show(); 
                    form_financeiro_lancamento_add.find('.show-on-normal').hide();
                    form_financeiro_lancamento_add.find("#tipo_fechamento").val('');
                }else if(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('regiao') === "Carta x"){
                    form_financeiro_lancamento_add.find('.show-on-normal').hide();  
                    form_financeiro_lancamento_add.find('.show-on-carta_x').show();
                    form_financeiro_lancamento_add.find("#tipo_fechamento").val('');
                }else{
                    form_financeiro_lancamento_add.find('.show-on-imposto').hide(); 
                    form_financeiro_lancamento_add.find('.show-on-normal').show();
                    form_financeiro_lancamento_add.find("#tipo_fechamento").val('cambio_pronto');
                }

                form_financeiro_lancamento_add.find("#data_cambio").val(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('data_cambio'));
                form_financeiro_lancamento_add.find("#valor_cambio").val(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('valor_cambio'));
                form_financeiro_lancamento_add.find("#valor").val(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('valor_real'));
            }            
        });

        form_financeiro_lancamento_add.find("#btn-add_red").off('click');
        form_financeiro_lancamento_add.find("#btn-add_red").on('click', function(){
            adicionarRed(form_financeiro_lancamento_add);
        });
        temp_array_imposto = [];
        temp_array_adiantamento = [];
        temp_array_cartao_x = [];
    });

    function showErrorsInputs(form_financeiro_lancamento_add, input, message){
        if(input.localeCompare('red_documento') == 0){
            var $input = $(form_modal_composicao).find("#bt-search-red_documento");
            $(form_modal_composicao).find("input[name='red_documento']").addClass('error-input');
        }else if(input.localeCompare('red_valor_utilizado') == 0){
            var $input = $(form_modal_composicao).find("#btn-add_red");
            $(form_modal_composicao).find("input[name='red_valor_utilizado']").addClass('error-input');
        }else{
            var $input = $(form_modal_composicao).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparCamposFinanceiroLancamento(){
        form_financeiro_lancamento_add.find("#id_lancamento").val('');
        form_financeiro_lancamento_add.find("#data_cambio").val('');
        form_financeiro_lancamento_add.find("#modalidade").val('');
        form_financeiro_lancamento_add.find("#valor_cambio").val('');
        form_financeiro_lancamento_add.find("#taxa").val('');
        form_financeiro_lancamento_add.find("#valor").val('');
        form_financeiro_lancamento_add.find("#banco").val('');
        form_financeiro_lancamento_add.find("#tipo_fechamento").val('');
        form_financeiro_lancamento_add.find("#numero_contrato_cambio").val('');
        form_financeiro_lancamento_add.find("#numero_contrato_banco").val('');
        form_financeiro_lancamento_add.find("#red_documento").val('');
        form_financeiro_lancamento_add.find("#red_saldo").val('');
        form_financeiro_lancamento_add.find("#red_taxa_cambio").val('');
        form_financeiro_lancamento_add.find("#red_valor_utilizado").val('');

        form_financeiro_lancamento_add.find('.show-on-normal').show();
        form_financeiro_lancamento_add.find('.show-on-imposto').hide();

        form_financeiro_lancamento_add.find('.show-on-adicionar').show();
        form_financeiro_lancamento_add.find('.show-on-editar').hide();

        form_financeiro_lancamento_add.find('.show-on-pagamento').hide();

        table_filters_dialog_lancamento_reds.clear().draw();

        form_financeiro_lancamento_add.find("#reds").val('');
        form_financeiro_lancamento_add.find("#red_adicionado").val('');
        $(document).find('#total_reds_utilizado').html('');
        $(document).find('#total_reds_saldo').html('');
    }

    function getEditarLancamento($this, obj){
        var id = $this.data("id");
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.buscar_editar_financeiro_lancamento') }}",
            dataType: 'json',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id
            },
            success: function(data){
                table_filters_dialog_historico_lancamento.row(obj).remove().draw();

                form_financeiro_lancamento_add.find("#id_lancamento").val(data.response.id);
                form_financeiro_lancamento_add.find("#data_cambio").val(data.response.cambio_data);
                form_financeiro_lancamento_add.find("#modalidade").val(data.response.modalidade);
                form_financeiro_lancamento_add.find("#valor_cambio").val(data.response.cambio_valor);
                form_financeiro_lancamento_add.find("#taxa").val(data.response.real_taxa);
                form_financeiro_lancamento_add.find("#valor").val(data.response.real_valor);
                form_financeiro_lancamento_add.find("#banco").val(data.response.banco);
                form_financeiro_lancamento_add.find("#tipo_fechamento").val(data.response.fechamento_tipo);
                form_financeiro_lancamento_add.find("#numero_contrato_cambio").val(data.response.cambio_numero_contrato);
                form_financeiro_lancamento_add.find("#numero_contrato_banco").val(data.response.banco_numero_contrato);
                form_financeiro_lancamento_add.find("#red_documento_editar").val(data.response.red_documento);
                form_financeiro_lancamento_add.find("#red_documento").val(data.response.red_documento);
                form_financeiro_lancamento_add.find("#red_saldo").val(data.response.red_saldo);
                form_financeiro_lancamento_add.find("#red_taxa_cambio").val(data.response.red_taxa_cambio);
                form_financeiro_lancamento_add.find("#red_valor_utilizado").val(data.response.red_valor_utilizado);

                CalculoValorUtilizado();

                $(document).find("#financeiro_lancamento-lancamento-tab").tab("show");

                form_financeiro_lancamento_add.find('.show-on-adicionar').hide();
                form_financeiro_lancamento_add.find('.show-on-editar').show();

                if(data.response.modalidade == 'imposto'){
                    form_financeiro_lancamento_add.find('.show-on-normal').hide();
                    form_financeiro_lancamento_add.find('.show-on-imposto').show();
                }else{
                    form_financeiro_lancamento_add.find('.show-on-normal').show();
                    form_financeiro_lancamento_add.find('.show-on-imposto').hide();
                }

                $("#associacao option").remove();
                $("#associacao").append("<option value='' selected>Pagamento</option>");
                for (var i in data.response.associacao){
                    $("#associacao").append("<option value='"+data.response.associacao[i].id+"' data-regiao='"+ data.response.associacao[i].valor +"'>" + data.response.associacao[i].valor + "</option>");
                }

                form_financeiro_lancamento_add.find('#associacao').val(data.response.baixa_id);

                form_financeiro_lancamento_add.find('.show-on-pagamento').show();

                if(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('regiao') === "Imposto"){
                    form_financeiro_lancamento_add.find('.show-on-imposto').show(); 
                    form_financeiro_lancamento_add.find('.show-on-normal').hide();       
                }else if(form_financeiro_lancamento_add.find("#associacao").find("option:selected").data('regiao') === "Carta x"){
                    form_financeiro_lancamento_add.find('.show-on-normal').hide();  
                    form_financeiro_lancamento_add.find('.show-on-carta_x').show(); 
                }else{
                    form_financeiro_lancamento_add.find('.show-on-imposto').hide(); 
                    form_financeiro_lancamento_add.find('.show-on-normal').show();  
                }

                linhas = [];
                table_filters_dialog_lancamento_reds.clear().draw();
                for (var posicao in data.response.red_tabela){
                    linha = [
                        data.response.red_tabela[posicao].numero_documento,
                        data.response.red_tabela[posicao].valor_utilizado,
                        data.response.red_tabela[posicao].saldo,
                        createBtnDeleteRed(data.response.red_tabela[posicao].numero_documento),
                    ];
                    linhas.push(linha)
                }
                table_filters_dialog_lancamento_reds.rows.add(linhas).draw();

                form_financeiro_lancamento_add.find("#reds").val(data.response.red_dados);
                form_financeiro_lancamento_add.find("#red_adicionado").val(data.response.red_dados);

                form_financeiro_lancamento_add.find("#red_documento").val('');
                form_financeiro_lancamento_add.find("#red_saldo").val('');
                form_financeiro_lancamento_add.find("#red_taxa_cambio").val('');
                form_financeiro_lancamento_add.find("#red_valor_utilizado").val('');

                $(document).find('#total_reds_utilizado').html(data.response.total_red.valor_utilizado);
                $(document).find('#total_reds_saldo').html(data.response.total_red.saldo);

                esconderPopoverTooltip();
            },
            error: function(data){
                message("Atenção", "Erro!");
            }
        });

        esconderPopoverTooltip();
    }

    function esconderPopoverTooltip(){
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('[data-toggle="popover"]').popover('hide');
    }

    function cancelarEdicao(){
        form_financeiro_lancamento_add.find('.show-on-adicionar').show();
        form_financeiro_lancamento_add.find('.show-on-editar').hide();
        form_financeiro_lancamento_add.find('.show-on-normal').show();
        form_financeiro_lancamento_add.find('.show-on-imposto').hide();
        limparCamposFinanceiroLancamento();
        esconderPopoverTooltip();
        carregarTabelaFinanceiroLancamento()
    }

    function editarLacamento(){
        var form_financeiro_lancamento_add = $(document).find('#frm_financeiro_lancamento_add');
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.editar_financeiro_lancamento') }}",
            dataType: 'json',
            data: form_financeiro_lancamento_add.serialize(),
            method: 'POST',
            success: function(callback){
                limparCamposFinanceiroLancamento();
                message("Atenção", "Dados alterado com sucesso!");
                carregarTabelaFinanceiroLancamento();
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form_financeiro_lancamento_add.find('.error-message').remove();
                form_financeiro_lancamento_add.find('div, input, select, textarea').each(function(){
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                for(var field in errors){
                    showErrorsInputs(form_financeiro_lancamento_add, field, errors[field])
                }
                if(form_financeiro_lancamento_add.find('.error-message').length){
                    form_financeiro_lancamento_add.find('.error-message').eq(0).focus();
                }
            }
        });
    }

    function excluirLacamento($this, obj){
        var id = $this.data("id");
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.deletar_financeiro_lancamento') }}",
            dataType: 'json',
            data: {
                _token: '{{csrf_token()}}',
                id: id
            },
            method: 'POST',
            success: function(callback){
                limparCamposFinanceiroLancamento();
                message("Atenção", "Dados excluído com sucesso!");
                carregarTabelaFinanceiroLancamento();
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form_financeiro_lancamento_add.find('.error-message').remove();
                form_financeiro_lancamento_add.find('div, input, select, textarea').each(function(){
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                for(var field in errors){
                    showErrorsInputs(form_financeiro_lancamento_add, field, errors[field])
                }
                if(form_financeiro_lancamento_add.find('.error-message').length){
                    form_financeiro_lancamento_add.find('.error-message').eq(0).focus();
                }
            }
        });
    }

    function carregarTabelaFinanceiroLancamento(){
        var form_financeiro_lancamento_add = $(document).find('#frm_financeiro_lancamento_add');
        $.ajax({
            url: "{{ route('importacao.dados_complementares_follow_up.carregar_tabela_financeiro_lancamento') }}",
            dataType: 'json',
            data: form_financeiro_lancamento_add.serialize(),
            method: 'POST',
            success: function(data){
                linhas = [];

                table_filters_dialog_historico_lancamento.clear().draw();
            
                for (var fields in data.response.lancamentos){
                    temp_array = [
                        data.response.lancamentos[fields].cambio_data,
                        data.response.lancamentos[fields].cambio_valor,
                        data.response.lancamentos[fields].modalidade,
                        data.response.lancamentos[fields].real_taxa,
                        data.response.lancamentos[fields].real_valor,
                        data.response.lancamentos[fields].banco,
                        data.response.lancamentos[fields].fechamento_tipo,
                        data.response.lancamentos[fields].cambio_numero_contrato,
                        data.response.lancamentos[fields].banco_numero_contrato,
                        createdBtnEditFinanceiroLancamento(data.response.lancamentos[fields]),
                        createdBtnDeleteFinanceiroLancamento(data.response.lancamentos[fields])
                    ];
                    linhas.push(temp_array)
                }
                table_filters_dialog_historico_lancamento.rows.add(linhas).draw();

                linhas = [];
                table_filters_dialog_historico_previsto_parcelas.clear().draw();

                for (var fields in data.response.previstos.parcela){
                    temp_array = [
                        data.response.previstos.parcela[fields].modalidade,
                        '<input id="data_parcela_'+fields+'" class="form-control data" placeholder="Data da '+fields+'º Parcela" maxlength="10" name="data_parcela[]" type="text" value="'+data.response.previstos.parcela[fields].cambio_data+'" autocomplete="off"">',
                        '<input id="valor_parcela_'+fields+'" class="form-control decimal text-right" placeholder="Valor da '+fields+'º Parcela" maxlength="20" name="valor_parcela[]" data-indice='+fields+' type="text" value="'+data.response.previstos.parcela[fields].cambio_valor+'">',
                        data.response.previstos.parcela[fields].baixa_data,
                    ];
                    linhas.push(temp_array)
                }
                table_filters_dialog_historico_previsto_parcelas.rows.add(linhas).draw();

                linhas = [];
                table_filters_dialog_historico_previsto.clear().draw();

                if(data.response.lancamento_imposto == true){
                    temp_array_imposto = [
                        data.response.previstos.imposto.modalidade+'<a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="top" title="" data-original-title="Imposto = II + PIS + Cofins + Taxa Siscomex + AFRMM + SDA + Expediente + Valor Li + Agência Marítima + Laudo + Seguro + Armazenagem + Transporte Rodoviário" style="color: black;"></a>',
                        '<input id="data_previsto_imposto" class="form-control data" placeholder="Data Imposto" maxlength="10" name="data_previsto_imposto" type="text" value="'+data.response.previstos.imposto.cambio_data+'" autocomplete="off"">',
                        data.response.previstos.imposto.cambio_valor,
                        '<input id="valor_previsto_imposto" class="form-control decimal text-right" placeholder="Valor Imposto" name="valor_previsto_imposto" type="text" value="'+data.response.previstos.imposto.real_valor+'" autocomplete="off"">',
                        data.response.previstos.imposto.baixa_data,
                    ];
                    linhas.push(temp_array_imposto)
                }
                if(data.response.lancamento_adiantamento == true){
                    temp_array_adiantamento = [
                        data.response.previstos.antecipado.modalidade,
                        '<input id="data_adiantamento" class="form-control data" placeholder="Data Antecipado" maxlength="10" name="data_adiantamento" type="text" value="'+data.response.previstos.antecipado.cambio_data+'" autocomplete="off"">',
                        '<input id="valor_adiantamento" class="form-control decimal text-right" placeholder="Valor Antecipado" name="valor_adiantamento" type="text" value="'+data.response.previstos.antecipado.cambio_valor+'" autocomplete="off" onkeyup="CalculoFobFinanceiroLancamento()"">',
                        "",
                        data.response.previstos.antecipado.baixa_data,
                    ];
                    linhas.push(temp_array_adiantamento)
                }
                if(data.response.lancamento_cartao_x == true){
                    temp_array_cartao_x = [
                        data.response.previstos.carta_x.modalidade,
                        '<input id="data_carta_x" class="form-control data" placeholder="Data Carta X" maxlength="10" name="data_carta_x" type="text" value="'+data.response.previstos.carta_x.cambio_data+'" autocomplete="off"">',
                        '<input id="valor_carta_x" class="form-control decimal text-right" placeholder="Valor Carta X" name="valor_carta_x" type="text" value="'+data.response.previstos.carta_x.cambio_valor+'" autocomplete="off"">',
                        "",
                        data.response.previstos.carta_x.baixa_data,
                    ];
                    linhas.push(temp_array_cartao_x)
                }
                linhas_antecipacao_segundaria = [];
                if(data.response.lancamento_antecipacao_segundaria == true){
                    for (var fields in data.response.previstos.antecipacao_segundaria){
                        temp_array_antecipacao_segundaria = [
                            data.response.previstos.antecipacao_segundaria[fields].modalidade,
                            '<input id="data_antecipacao_array_'+[fields]+'" class="form-control data" placeholder="Data da '+data.response.previstos.antecipacao_segundaria[fields].modalidade+'" maxlength="10" name="data_antecipacao_array[]" type="text" value="'+data.response.previstos.antecipacao_segundaria[fields].cambio_data+'" autocomplete="off"">',
                            '<input id="valor_parcela_array_'+[fields]+'" class="form-control decimal text-right" placeholder="Valor '+data.response.previstos.antecipacao_segundaria[fields].modalidade+'" name="valor_parcela_array[]" type="text" value="'+data.response.previstos.antecipacao_segundaria[fields].cambio_valor+'" autocomplete="off" onkeyup="CalculoFobFinanceiroLancamento()"">',
                            "",
                            data.response.previstos.antecipacao_segundaria[fields].baixa_data,
                        ];
                        linhas_antecipacao_segundaria.push(temp_array_antecipacao_segundaria);
                    }
                }
                table_filters_dialog_historico_previsto.rows.add(linhas).draw();
                table_filters_dialog_historico_previsto.rows.add(linhas_antecipacao_segundaria).draw();

                linhas = [];
                table_filters_dialog_lancamento_futuro.clear().draw();
                if(data.response.lancamento_imposto == true){
                    if(data.response.previstos.imposto.pago == false){
                        temp_array = [
                            data.response.previstos.imposto.modalidade,
                            data.response.previstos.imposto.cambio_data,
                            data.response.previstos.imposto.cambio_valor,
                            data.response.previstos.imposto.real_valor,
                        ];
                        linhas.push(temp_array);
                    }                    
                }
                if(data.response.lancamento_adiantamento == true){
                    if(data.response.previstos.antecipado.pago == false){
                        temp_array = [
                            data.response.previstos.antecipado.modalidade,
                            data.response.previstos.antecipado.cambio_data,
                            data.response.previstos.antecipado.cambio_valor,
                            "",
                            data.response.previstos.antecipado.baixa_data,
                        ];
                        linhas.push(temp_array);
                    }
                    
                }
                if(data.response.lancamento_cartao_x == true){
                    if(data.response.previstos.carta_x.pago == false){
                        temp_array = [
                            data.response.previstos.carta_x.modalidade,
                            data.response.previstos.carta_x.cambio_data,
                            data.response.previstos.carta_x.cambio_valor,
                            "",
                            data.response.previstos.carta_x.baixa_data,
                        ];
                        linhas.push(temp_array);
                    }
                }
                if(data.response.lancamento_antecipacao_segundaria == true){
                    for (var fields in data.response.previstos.antecipacao_segundaria){
                        if(data.response.previstos.antecipacao_segundaria[fields].pago == false){
                            temp_array = [
                                data.response.previstos.antecipacao_segundaria[fields].modalidade,
                                data.response.previstos.antecipacao_segundaria[fields].cambio_data,
                                data.response.previstos.antecipacao_segundaria[fields].cambio_valor,
                                data.response.previstos.antecipacao_segundaria[fields].baixa_data,
                            ];
                            linhas.push(temp_array);
                        }                        
                    }
                }
                for (var fields in data.response.previstos.parcela){
                    if(data.response.previstos.parcela[fields].pago == false){
                        temp_array = [
                            data.response.previstos.parcela[fields].modalidade,
                            data.response.previstos.parcela[fields].cambio_data,
                            data.response.previstos.parcela[fields].cambio_valor,
                            data.response.previstos.parcela[fields].baixa_data,
                        ];
                        linhas.push(temp_array);
                    }
                }

                table_filters_dialog_lancamento_futuro.rows.add(linhas).draw();

                $("#associacao option").remove();
                $("#associacao").append("<option value='' selected>Pagamento</option>");
                for (var i in data.response.associacao){
                    $("#associacao").append("<option value='"+data.response.associacao[i].id+"' data-regiao='"+ data.response.associacao[i].valor +"' data-data_cambio='"+ data.response.associacao[i].cambio_data +"' data-valor_cambio='"+ data.response.associacao[i].cambio_valor +"' data-valor_real='"+ data.response.associacao[i].real_valor +"'>" + data.response.associacao[i].valor + "</option>");
                }

                $(document).find('#cambio_valor_total').html(data.response.total.cambio_valor);
                $(document).find('#real_taxa_total').html(data.response.total.real_taxa);
                $(document).find('#real_valor_total').html(data.response.total.real_valor);
                $(document).find('#parcela_antecipacao_previsto').val(data.response.quantidade_antecipacao_previsto);
                CalculoFobFinanceiroLancamento();
                if(data.response.total.cambio_valor == ''){
                    valor_fob_pago = 0;
                }else{
                    valor_fob_pago = data.response.total.cambio_valor;
                }

                form_modal_importacao.find("#valor_fob_pago").val(valor_fob_pago);
                form_modal_importacao.find("#total_cambio").val(data.response.total.real_valor);
                atualizarValorDevido();

                form_financeiro_lancamento_add.find('.data').datepicker({ 
                    format: 'dd/mm/yyyy',
                    zIndex: 2000,
                    language: 'pt-BR',
                    autoHide: true,
                });
                form_financeiro_lancamento_add.find('.data').mask('00/00/0000');

                form_financeiro_lancamento_add.find(".decimal").maskMoney({thousands:'.', decimal:','});
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form_financeiro_lancamento_add.find('.error-message').remove();
                form_financeiro_lancamento_add.find('div, input, select, textarea').each(function(){
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                for(var field in errors){
                    showErrorsInputs(form_financeiro_lancamento_add, field, errors[field])
                }
                if(form_financeiro_lancamento_add.find('.error-message').length){
                    form_financeiro_lancamento_add.find('.error-message').eq(0).focus();
                }
            }
        });
    }

    function createdBtnEditFinanceiroLancamento($this){
        var html = "<td><a href=\"#\"  data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"getEditarLancamento($(this), $(this).parents('tr'))\"></a></td>";
        
        return html;
    }

    function createdBtnDeleteFinanceiroLancamento($this){
        var html = "<td><a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"excluirLacamento($(this), $(this).parents('tr'))\"></a></td>";
        
        return html;
    }

    function geracaoParcela(form_financeiro_lancamento_add){
        parcelas = form_financeiro_lancamento_add.find("#parcela_previsto").val();
        table_filters_dialog_historico_previsto_parcelas.clear().draw();
        valor_total_parcela_fornecedor = form_financeiro_lancamento_add.find("#valor_total_parcela_fornecedor").val();

        linhas = [];

        if(parcelas == "" || $.isEmptyObject(parcelas)){
            table_filters_dialog_historico_previsto_parcelas.clear().draw();
        }else{
            html = "";
            if(valor_total_parcela_fornecedor == "" || $.isEmptyObject(valor_total_parcela_fornecedor)){
                valor_total_parcela_fornecedor = "";
            }else {
                valor_total_parcela_fornecedor = valor_total_parcela_fornecedor.replace(/\./g,"").replace(/\,/g, ".");
                valor_total_parcela_fornecedor = parseFloat(valor_total_parcela_fornecedor);
                valor_total_parcela_fornecedor = valor_total_parcela_fornecedor / parcelas;
                valor_total_parcela_fornecedor = numberToReal(valor_total_parcela_fornecedor.toFixed(2)); 
            }
            for(var i = 1; i <= parcelas; i++){
                if(parcelas == 1){
                    temp_array = [
                        '1º Parcela Fornecedor',
                        '<input id="data_parcela_'+i+'" class="form-control data" placeholder="Data da '+i+'º Parcela" maxlength="10" name="data_parcela[]" type="text" value="" autocomplete="off"">',
                        '<input id="valor_parcela_'+i+'" class="form-control decimal text-right" placeholder="Valor da '+i+'º Parcela" maxlength="20" name="valor_parcela[]" data-indice='+i+' type="text" value="'+valor_total_parcela_fornecedor+'">',
                        '',
                    ];
                }else{
                    temp_array = [
                        i+'º Parcela Fornecedor',
                        '<input id="data_parcela_'+i+'" class="form-control data" placeholder="Data da '+i+'º Parcela" maxlength="10" name="data_parcela[]" type="text" value="" autocomplete="off"">',
                        '<input id="valor_parcela_'+i+'" class="form-control decimal text-right" placeholder="Valor da '+i+'º Parcela" maxlength="20" name="valor_parcela[]" data-indice='+i+' type="text" value="'+valor_total_parcela_fornecedor+'">',
                        '',
                    ];
                }
                
                linhas.push(temp_array)
            }

            table_filters_dialog_historico_previsto_parcelas.rows.add(linhas).draw();  

            form_financeiro_lancamento_add.find("#parcelas").html(html);

            form_financeiro_lancamento_add.find('.data').datepicker({ 
                format: 'dd/mm/yyyy',
                zIndex: 2000,
                language: 'pt-BR',
                autoHide: true,
            });
            form_financeiro_lancamento_add.find('.data').mask('00/00/0000');

            form_financeiro_lancamento_add.find(".decimal").maskMoney({thousands:'.', decimal:','});
        }
    }

    function optionsAutoCompleteRedDocumento(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('red.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_financeiro_lancamento').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_financeiro_lancamento_add.find('#red_documento').val(ui.item.value);
                form_financeiro_lancamento_add.find('#red_saldo').val(ui.item.saldo);
                form_financeiro_lancamento_add.find('#red_taxa_cambio').val(ui.item.taxa_cambio);

                CalculoValorUtilizado();

                return false;
            }
        };
    }

    function showModalBuscarRedModal(form_financeiro_lancamento_add){
        $.ajax({
            url: '{{ route('red.modal.buscar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_red", "Buscar Hedge", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosBuscarRedModal($(this), form_financeiro_lancamento_add);
                    });

                });
            }
        });
    }

    function returnDadosBuscarRedModal($dados, form_financeiro_lancamento_add){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_red").modal("hide");
        
        form_financeiro_lancamento_add.find('#red_documento').val($dados.find("td").eq(0).text());
        form_financeiro_lancamento_add.find('#red_saldo').val($dados.find("td").eq(4).text());
        form_financeiro_lancamento_add.find('#red_taxa_cambio').val($dados.find("td").eq(2).text());

        CalculoValorUtilizado();
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

    function adicionarRed(form_financeiro_lancamento_add){
        data_form_financeiro_lancamento_add = form_financeiro_lancamento_add.serialize();
        $.ajax({
            url: '{{ route('importacao.dados_complementares_follow_up.adicionar_red') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_financeiro_lancamento_add,
            async: false,
            success: function (data){
                linhas = [];
                table_filters_dialog_lancamento_reds.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        data.response.tabela[posicao].numero_documento,
                        data.response.tabela[posicao].valor_utilizado,
                        data.response.tabela[posicao].saldo,
                        createBtnDeleteRed(data.response.tabela[posicao].numero_documento),
                    ];
                    linhas.push(linha)
                }
                table_filters_dialog_lancamento_reds.rows.add(linhas).draw();

                form_financeiro_lancamento_add.find("#reds").val(data.response.reds);
                form_financeiro_lancamento_add.find("#red_adicionado").val(data.response.reds);

                form_financeiro_lancamento_add.find("#red_documento").val('');
                form_financeiro_lancamento_add.find("#red_saldo").val('');
                form_financeiro_lancamento_add.find("#red_taxa_cambio").val('');
                form_financeiro_lancamento_add.find("#red_valor_utilizado").val('');

                $(document).find('#total_reds_utilizado').html(data.response.total.valor_utilizado);
                $(document).find('#total_reds_saldo').html(data.response.total.saldo);
            },
            error: function (callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form_financeiro_lancamento_add.find('.error-message').remove();
                form_financeiro_lancamento_add.find('div, input, select, textarea').each(function(){
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                for(var field in errors){
                    showErrorsInputs(form_financeiro_lancamento_add, field, errors[field])
                }
                if(form_financeiro_lancamento_add.find('.error-message').length){
                    form_financeiro_lancamento_add.find('.error-message').eq(0).focus();
                }
            }
        });
    }

    function createBtnDeleteRed($numero_documento){
        var $html = "<a href=\"#\" data-numero_documento=\""+$numero_documento+"\" data-modal=\"\" data-title_modal=\"Excluir Hedge\" class=\"bt-delete\" data-placement=\"top\" title=\"Excluir\" onclick=\"deletarRed($(this))\"></a>";
        return $html;
    }

    function deletarRed($this){
        var numero_documento = $($this).data("numero_documento");
        var red_adicionado = form_financeiro_lancamento_add.find("#red_adicionado").val();
        $.ajax({
            url: '{{ route('importacao.dados_complementares_follow_up.deletar_red') }}',
            type: 'POST',
            dataType: 'json',
            data:  {
                _token: "{{ csrf_token() }}", 
                red_adicionado: red_adicionado,
                numero_documento: numero_documento
            },
            async: false,
            success: function (data){
                linhas = [];
                table_filters_dialog_lancamento_reds.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        data.response.tabela[posicao].numero_documento,
                        data.response.tabela[posicao].valor_utilizado,
                        data.response.tabela[posicao].saldo,
                        createBtnDeleteRed(data.response.tabela[posicao].numero_documento),
                    ];
                    linhas.push(linha)
                }
                table_filters_dialog_lancamento_reds.rows.add(linhas).draw();

                form_financeiro_lancamento_add.find("#reds").val(data.response.reds);
                form_financeiro_lancamento_add.find("#red_adicionado").val(data.response.reds);

                form_financeiro_lancamento_add.find("#red_documento").val('');
                form_financeiro_lancamento_add.find("#red_saldo").val('');
                form_financeiro_lancamento_add.find("#red_taxa_cambio").val('');
                form_financeiro_lancamento_add.find("#red_valor_utilizado").val('');

                $(document).find('#total_reds_utilizado').html(data.response.total.valor_utilizado);
                $(document).find('#total_reds_saldo').html(data.response.total.saldo);
            },
            error: function (data){

            }
        });
    }

    function CalculoFobFinanceiroLancamento(){
        valor_total_parcela = {{$preco_fob_total_codigo}};
        valor_adiantamento = form_financeiro_lancamento_add.find("#valor_adiantamento").val();
        parcelas = form_financeiro_lancamento_add.find("#parcela_antecipacao_previsto").val();
        valor_parcela_array = 0;
        if(parcelas > 1){
            for(var i = 0; i <= parcelas; i++){
                verificar_valor = form_financeiro_lancamento_add.find("#valor_parcela_array_"+i).val();
                if(verificar_valor == "" || $.isEmptyObject(verificar_valor)){
                    valor_parcela_array += 0;
                }else{
                    verificar_valor = verificar_valor.replace(/\./g,"").replace(/\,/g, ".");
                    verificar_valor = parseFloat(verificar_valor);
                    valor_parcela_array += verificar_valor;
                }
            }
        }
        if(valor_adiantamento == "" || $.isEmptyObject(valor_adiantamento) || parcelas == "0"){
            valor_adiantamento = 0;

            valor = valor_total_parcela - (valor_adiantamento + valor_parcela_array);

            form_financeiro_lancamento_add.find("#valor_total_parcela_fornecedor").val(numberToReal(valor.toFixed(2)));
            form_financeiro_lancamento_add.find("#valor_adiantamento").val("");
        }else{
            valor_adiantamento = valor_adiantamento.replace(/\./g,"").replace(/\,/g, ".");
            valor_adiantamento = parseFloat(valor_adiantamento);

            valor = valor_total_parcela - (valor_adiantamento + valor_parcela_array);

            form_financeiro_lancamento_add.find("#valor_total_parcela_fornecedor").val(numberToReal(valor.toFixed(2)));
        }
    }

    function CalculoValorUtilizado(){
        valor_cambio = form_financeiro_lancamento_add.find("#valor_cambio").val();
        red_saldo = form_financeiro_lancamento_add.find("#red_saldo").val();
        
        if(valor_cambio == "" || $.isEmptyObject(valor_cambio) || red_saldo == "" || $.isEmptyObject(red_saldo)){
            form_financeiro_lancamento_add.find("#red_valor_utilizado").val('');
        }else{
            valor_cambio = valor_cambio.replace(/\./g,"").replace(/\,/g, ".");
            valor_cambio = parseFloat(valor_cambio);

            red_saldo = red_saldo.replace(/\./g,"").replace(/\,/g, ".");
            red_saldo = parseFloat(red_saldo);

            menor = '';
            if(red_saldo > valor_cambio){
                menor = form_financeiro_lancamento_add.find("#valor_cambio").val();
            }else{
                menor = form_financeiro_lancamento_add.find("#red_saldo").val();
            }

            form_financeiro_lancamento_add.find("#red_valor_utilizado").val(menor);
        }
    }

    function geracaoParcelaAntecipacao(form_financeiro_lancamento_add){
        parcelas = form_financeiro_lancamento_add.find("#parcela_antecipacao_previsto").val();
        table_filters_dialog_historico_previsto.clear().draw();

        linhas = [];

        if(parcelas == "" || $.isEmptyObject(parcelas) || parcelas == "1"){
            if(!$.isEmptyObject(temp_array_imposto)){
                linhas.push(temp_array_imposto)
            }
            if(!$.isEmptyObject(temp_array_adiantamento)){
                linhas.push(temp_array_adiantamento)
            }else{
                temp_array_adiantamento = [
                    "Antecipação",
                    '<input id="data_adiantamento" class="form-control data" placeholder="Data Antecipado" maxlength="10" name="data_adiantamento" type="text" value="" autocomplete="off"">',
                    '<input id="valor_adiantamento" class="form-control decimal text-right" placeholder="Valor Antecipado" name="valor_adiantamento" type="text" value="" autocomplete="off" onkeyup="CalculoFobFinanceiroLancamento()"">',
                    "",
                    "",
                ];
                linhas.push(temp_array_adiantamento)
            }
            if(!$.isEmptyObject(temp_array_cartao_x)){
                linhas.push(temp_array_cartao_x)
            }

            table_filters_dialog_historico_previsto.rows.add(linhas).draw();           
        }else if(parcelas == "0"){
            if(!$.isEmptyObject(temp_array_imposto)){
                linhas.push(temp_array_imposto)
            }
            if(!$.isEmptyObject(temp_array_adiantamento)){
                linhas.push(temp_array_adiantamento)
            }
            if(!$.isEmptyObject(temp_array_cartao_x)){
                linhas.push(temp_array_cartao_x)
            }

            table_filters_dialog_historico_previsto.rows.add(linhas).draw();  

            form_financeiro_lancamento_add.find("#valor_adiantamento").val("");   
            form_financeiro_lancamento_add.find("#data_adiantamento").val("");

            CalculoFobFinanceiroLancamento();
        }else{
            if(!$.isEmptyObject(temp_array_imposto)){
                linhas.push(temp_array_imposto)
            }
            if(!$.isEmptyObject(temp_array_adiantamento)){
                linhas.push(temp_array_adiantamento)
            }else{
                temp_array_adiantamento = [
                    "Antecipação",
                    '<input id="data_adiantamento" class="form-control data" placeholder="Data Antecipado" maxlength="10" name="data_adiantamento" type="text" value="" autocomplete="off"">',
                    '<input id="valor_adiantamento" class="form-control decimal text-right" placeholder="Valor Antecipado" name="valor_adiantamento" type="text" value="" autocomplete="off" onkeyup="CalculoFobFinanceiroLancamento()"">',
                    "",
                    "",
                ];
                linhas.push(temp_array_adiantamento)
            }
            if(!$.isEmptyObject(temp_array_cartao_x)){
                linhas.push(temp_array_cartao_x)
            }
            table_filters_dialog_historico_previsto.rows.add(linhas).draw();
            
            linhas = [];
            inicial = linhas_antecipacao_segundaria.length + 2;
            if(inicial > parcelas){
                for(var i = 0; i < parcelas-1; i++){
                    linhas.push(linhas_antecipacao_segundaria[i]);
                }
                table_filters_dialog_historico_previsto.rows.add(linhas).draw();
            }else{
                table_filters_dialog_historico_previsto.rows.add(linhas_antecipacao_segundaria).draw();
                for(var i = inicial; i <= parcelas; i++){
                    temp_array = [
                        i+'º Antecipação',
                        '<input id="data_antecipacao_array_'+i+'" class="form-control data" placeholder="Data da '+i+'º Antecipação" maxlength="10" name="data_antecipacao_array[]" type="text" value="" autocomplete="off"">',
                        '<input id="valor_parcela_array_'+i+'" class="form-control decimal text-right" placeholder="Valor da '+i+'º Antecipação" maxlength="20" name="valor_parcela_array[]" data-indice='+i+' type="text" value="">',
                        '',
                        '',
                    ];
                    
                    linhas.push(temp_array);
                }
                table_filters_dialog_historico_previsto.rows.add(linhas).draw();
            }
            
        }

        

        form_financeiro_lancamento_add.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_financeiro_lancamento_add.find('.data').mask('00/00/0000');

        form_financeiro_lancamento_add.find(".decimal").maskMoney({thousands:'.', decimal:','});
    }
</script>
@endsection