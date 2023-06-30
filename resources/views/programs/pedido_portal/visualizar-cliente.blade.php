@extends('layouts.app-deslogado')

@section('content')
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
			<div class="col-sm-6">
                <b>Cliente:</b><br>
                {!! $pedido['cliente'] !!} - {!! $pedido['cpf_cnpj']  !!}
			</div>
			<div class="col-sm-3">
				<b>Status do pedido:</b><br>
				{!! $pedido['status']['descricao'] !!}
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
		<!-- <div class="row">
			<div class="col-sm-12">
				<b>Observação: </b><br>
				{!! $pedido['observacao'] !!}
			</div>
		</div> -->
	</div>
</div>

<div class="content-table mb-3">
	<table class="table table-striped table-filter-pedido-itens table-not-edit responsiva" id="table-filters-pedidos-itens">
        <thead>
            <tr>
                <th>Grupo</th>
                <th>Código</th>
                <th>Descrição</th>
                <th>Marca</th>
                <th>Linha</th>
                <th>Quantidade</th>
                <th>Preço unitário</th>
                <th>Valor total</th>
            </tr>
        </thead>
        <tbody>
        	@foreach ($pedido['itens'] as $value)
        	<tr>
        		<td>{!! $value['grupo'] !!}</td>
        		<td>{!! $value['cod_produto'] !!}</td>
        		<td>{!! $value['descricao'] !!}</td>
        		<td>{!! $value['marca'] !!}</td>
        		<td>{!! $value['linha'] !!}</td>
        		<td class='number_format'>{!! parserValor($value['quantidade']) !!}</td>
        		<td class='number_format'>{!! parserValor($value['preco_unitario']) !!}</td>
        		<td class='number_format'>{!! parserValor($value['valor_total']) !!}</td>
        	</tr>
        	@endforeach

        </tbody>
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
            <div class='col-sm-2'>Desconto em nota</div>
            <div class='col-sm-2 text-right'>{!! $pedido['valor_desconto'] !!}</div>

        </div>

        <div class='row mr-3'>
            <div class="col-sm-8"></div>
            <div class='col-sm-2 border-top'><b>Total do Pedido</b></div>
            <div class='col-sm-2 text-right border-top'><b>{!! $pedido['valor_total_nota'] !!}</b></div>
        </div>
        
    </div>
</div>

@endsection

@section('script-footer')

    $(document).ready(function(){

        table_filters_pedido_itens.on('responsive-display', function( e, datatable, row, showHide){
            if (showHide){

            }
        })
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
       	"responsive": true,
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

@endsection