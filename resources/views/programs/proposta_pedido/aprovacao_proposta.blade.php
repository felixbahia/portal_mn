@extends('layouts.app-deslogado')

@section('content')
<form action="" name="form_aprovcao_proposta" id="form_aprovcao_proposta"  onsubmit="return false;">
    <div class="row m-4">
        <div class="col-lg-12 border-bottom mb-4">
            <h5><b>Pedido nº</b> {!!  $pedido['id']  !!} - <b>Cliente:</b> {!! $pedido['cliente'] !!} - {!! $pedido['cpf_cnpj']  !!} - <b>Data do pedido:</b> {!! $pedido['data_pedido'] !!} - <b>Vendedor:</b> {!! $pedido['vendedor'] !!}</h5>
        </div>
        <div class="col-lg-12">
            <div class="row border-bottom">
                <div class="col-sm-3">
                    <b>Estabelecimento:</b><br>
                    {!! $pedido['estabelecimento'] !!}
                </div>
                <div class="col-sm-3">
                    <b>Status do pedido:</b><br>
                    @if(in_array($pedido['status']['id'], [7]))
                        Cancelado
                    @elseif(in_array($pedido['status']['id'], [1,14,15]))
                        {!! $pedido['status']['descricao'] !!}
                    @else
                        Aprovado
                    @endif
                </div>
            </div>
            <div class="row border-bottom">
                <div class="col-sm-3">
                    <b>Previsão de entrega:</b><br>
                    {!! $pedido['data_previsao_entrega'] !!}
                </div>
                <div class="col-sm-6">
                    <b>Condição de pagamento:</b><br>
                    {!! $pedido['condicao_pagamento_descr'] !!}
                </div>
            </div>
            <div class="row border-bottom">
                <div class="col-sm-6">
                    <b>Transportadora:</b><br>
                    {!! $pedido['transportadora']['nome'] !!}
                </div>
                <div class="col-sm-3">
                    <b>Tipo de Frete:</b><br>
                    {!! $pedido['transportadora']['tipo_frete'] !!}
                </div>
                @if($pedido['transportadora']['valor_frete'] !== "0,00")
                <div class="col-sm-3">
                    <b>Valor do Frete</b><br>
                    {!! $pedido['transportadora']['valor_frete'] !!}
                </div>
                @endif
            </div>
            @if(!empty($pedido['transportadora_redespacho']['nome']))
            <div class="row border-bottom">
                <div class="col-sm-6">
                    <b>Transportadora Redespacho:</b><br>
                    {!! $pedido['transportadora_redespacho']['nome'] !!}
                </div>
                <div class="col-sm-3">
                    <b>Tipo de Frete:</b><br>
                    {!! $pedido['transportadora_redespacho']['tipo_frete'] !!}
                </div>
                @if($pedido['transportadora_redespacho']['valor_frete'] !== "0,00")
                <div class="col-sm-3">
                    <b>Valor do Frete</b><br>
                    {!! $pedido['transportadora_redespacho']['valor_frete'] !!}
                </div>
                @endif
            </div>
            @endif
            <div class="row border-bottom">
                <div class="col-sm-3">
                    <b>Peso Total: </b><br>
                    {!! $pedido['peso_total'] !!}
                </div>
                <div class="col-sm-3">
                    <b>Valor Total: </b><br>
                    {!! $pedido['total'] !!}
                </div>
            </div>
            <div class="row border-bottom">
                <div class="col-sm-3">
                    <b>Criado Por: </b><br>
                    {!! $pedido['criado_por'] !!}
                </div>
                <div class="col-sm-3">
                    <b>Criado Em: </b><br>
                    {!! $pedido['criado_em'] !!}
                </div>
            </div>
        </div>
    </div>

    <div class="content-table mb-3">
        <table class="table table-striped table-filter-pedido-itens table-not-edit responsiva" id="table-filters-pedidos-itens">
            <thead>
                <tr>
                    <th>Cód.</th>
                    <th>Desc.</th>
                    <th>Qtd</th>
                    <th>Peso</th>
                    <th>Pr. Un.</th>
                    <th>Pr. tot.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pedido['itens'] as $value)
                <tr>
                    <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{!! $value['cod_produto'] !!}'>{!! $value['cod_produto'] !!}</div></div></td>
                    <td style="white-space: pre-wrap;">{!! $value['descricao'] !!}</td>
                    <td class='number_format'>{!! parserValor($value['quantidade']) !!}</td>
                    <td class='number_format'>{!! parserValor($value['peso']) !!}</td>
                    <td class='number_format'>{!! parserValor($value['preco_unitario']) !!}</td>
                    <td class='number_format'>{!! parserValor($value['valor_total']) !!}</td>
                </tr>
                @endforeach

            </tbody>
            <tfoot>
            </tfoot>
        </table>
    </div>

    <div class="container w-100 mw-100" style="clear: both;">
        <div class='row mr-3'>
            <div class="col-sm-8"></div>
            <div class='col-sm-2'>Total dos Produtos</div>
            <div class='col-sm-2 text-right'>{!! $pedido['valor_total_itens'] !!}</div>
        </div>

        <div class='row mr-3'>
            <div class="col-sm-8"></div>
            <div class='col-sm-2'>Total de Frete</div>
            <div class='col-sm-2 text-right'>{!! $pedido['valor_frete'] !!}</div>
        </div>

        <div class='row mr-3'>
            <div class="col-sm-8"></div>
            <div class='col-sm-2 border-top'><b>Total do Pedido</b></div>
            <div class='col-sm-2 text-right border-top'><b>{!! $pedido['valor_total_nota'] !!}</b></div>
        </div>
    </div>

    @if ($pedido['status']['id'] == 14)
        @csrf
        {!! Form::hidden('id', $pedido['id'], ['id' => 'id']) !!}
        <div class="center_content mt-1" style="text-align: center;" id="button-center">
            {{ Form::submit('Aprovar', array('class' => 'btn btn-primary', 'id' => 'btn-aceito', 'name' => 'btn-aceito')) }}
            {{ Form::button('Recusar', array('class' => 'btn btn-danger', 'id' => 'btn-recuso')) }}
        </div> 
    @endif
</form>
@endsection

@section('script-footer')

    $(document).ready(function(){
        form_modal = $(document).find("#form_aprovcao_proposta");

        form_modal.find("#btn-aceito").off('click');
        form_modal.find("#btn-aceito").on('click', function(){
            aceitar(form_modal);
        });

        form_modal.find("#btn-recuso").off('click');
        form_modal.find("#btn-recuso").on('click', function(){
            recusar(form_modal);
        });

    });

    table_filters_pedidos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": true,
        "scrollY": "35vh",
       	"responsive": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
    };

	table_filters_pedido_itens = $(document).find("#table-filters-pedidos-itens").DataTable(table_filters_pedidos_options);

	table_filters_pedido_itens.columns.adjust();

    function recusar(form_modal){
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('pedido_portal.recusar_proposta') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                message("Atenção", "Proposta recusada!");
                window.location.reload(true);
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
                var dados = callback.responseJSON;
            }
        });
    }

    function aceitar(form_modal){
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('pedido_portal.aprovar_proposta') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                message("Atenção", "Proposta aprovada com sucesso!");
                window.location.reload(true);
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
                var dados = callback.responseJSON;
            }
        });
    }
@endsection