@extends('layouts.app-print')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <h5>Detalhes - Pedido nº {{ $pedido['id'] }} - Vendedor: {{ $pedido['vendedor'] }}</h5>
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-2">
        <b>Estabelecimento:</b><br>
        {{ $pedido['estabelecimento'] }}
    </div>
    <div class="col-sm-6">
        <b>Cliente:</b><br>
        {{ $pedido['cliente']['nome'] }}
    </div>
    <div class="col-sm-4">
        <b>Tipo de venda:</b><br>
        {{ $pedido['tipo_venda'] }}
    </div>
</div>
@if ($pedido['conta_e_ordem'] == 'Sim' || !is_null($pedido['pedido_gerado']))
<hr>
<div class="row">
    @if ($pedido['conta_e_ordem'] == 'Sim')
    <div class="col-sm-4">
        <b>Cliente da conta e ordem</b><br>
        {{ $pedido['cliente_conta_e_ordem'] }}
    </div>
    @endif
    @if (!is_null($pedido['pedido_gerado']))
    <div class="col-sm-4">
        <b>Pedido gerado: </b><br>
        {!! $pedido['pedido_gerado'] !!}
    </div>
    @endif
</div>
@endif
<hr>
<div class="row ">
    <div class="col-sm-4">
        <b>Data do pedido:</b><br>
        {!! $pedido['data_pedido'] !!}
    </div>
    <div class="col-sm-4">
        <b>Pedido futuro?</b><br>
        {{ $pedido['pedido_futuro'] }}
    </div>
    <div class="col-sm-4">
        <b>Previsão de entrega:</b><br>
        {!! $pedido['data_previsao_entrega'] !!}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-12">
        <b>Condição de pagamento:</b><br>
        {{ $pedido['condicao_pagamento_descr'] }}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-6">
        <b>Transportadora:</b><br>
        {{ $pedido['transportadora']['nome'] }}
    </div>
    <div class="col-sm-3">
        <b>Tipo de Frete:</b><br>
        {{ $pedido['transportadora']['tipo_frete'] }}
    </div>
    <div class="col-sm-3">
        <b>Valor do Frete:</b><br>
        {{ $pedido['transportadora']['valor_frete'] }}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-6">
        <b>Transportadora Redespacho:</b><br>
        {!! $pedido['transportadora_redespacho']['nome'] !!}
    </div>
    <div class="col-sm-3">
        <b>Tipo de Frete:</b><br>
        {!! $pedido['transportadora_redespacho']['tipo_frete'] !!}
    </div>
    <div class="col-sm-3">
        <b>Valor do Frete:</b><br>
        {!! $pedido['transportadora_redespacho']['valor_frete'] !!}
    </div>
</div>
<hr>
<div class="row">
    <div class="col-sm-6">
        <b>Nome do contato:</b><br>
        {!! $pedido['nome_comprador'] !!}
    </div>    
    <div class="col-sm-6">
        <b>Email do contato:</b><br>
        {!! $pedido['email_comprador'] !!}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-6">
        <b>Observação: </b><br>
        {!! $pedido['observacao'] !!}
    </div>
    @if(isset($pedido['no_pedido_compra']))
    <div class="col-sm-3">
        <b>Pedido compra: </b><br>
        {{ $pedido['no_pedido_compra'] }}
    </div>
    @endif
</div>
<hr>
<div class="content-dialog-table">
    <table class="table table-striped" id="table-itens-print">
        <thead>
            <tr>
                <th>Código</th>
                <th>Linha</th>
                <th>Quantidade</th>
                <th>Preço unitário</th>
                <th>Valor total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido['itens'] as $value)
            <tr>
                <td>{{ $value['cod_produto'] }}</td>
                <td>{{ $value['descricao'] }}</td>
                <td>{{ $value['quantidade'] }}</td>
                <td>{{ $value['preco_unitario'] }}</td>
                <td>{{ $value['valor_total'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="m-4" style="clear: both;">
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2'>Total dos Produtos</div>
		<div class='col-sm-2 text-right'>{{ $pedido['valor_total_itens'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2'>Total de Frete</div>
		<div class='col-sm-2 text-right'>{{ $pedido['valor_frete'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2'>Desconto</div>
		<div class='col-sm-2 text-right'>{{ $pedido['valor_desconto'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2 border-top'><b>Total do Pedido</b></div>
		<div class='col-sm-2 text-right border-top'><b>{{ $pedido['valor_total_nota'] }}</b></div>
	</div>
</div>
<script>
    window.print();
    setTimeout(function () {
            window.close(); 
    }, 500);
</script>
@endsection