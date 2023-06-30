<html lang="pt-BR">
    <head>
        <title>MN Tecidos</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <!-- Styles -->
        <link href="http://localhost/css/style.css?t=72" rel="stylesheet">
        <link href="http://localhost/css/imprimir.css?t=94" rel="stylesheet">
        <style></style>
    </head>
    <body>
        <div class="header_print">
            <div class="content-logo">
                <a href="http://localhost">
                    <img src="http://localhost/images/logotipo.png" alt="" border="0">
                </a>
            </div>
        </div>
        <div id="app">
            <div class="row">
                <div class="col-sm-12">
                    <h5>Detalhes - Pedido nº {{ $pedido['id'] }} - Vendedor: {{ $pedido['vendedor'] }}</h5>
                </div>
            </div>
            <hr>
            <div class="row ">
                <div class="col-sm-2" style="max-width: 17%;box-sizing: border-box; position: relative;">
                    <b>Estabelecimento:</b><br>
                    {{ $pedido['estabelecimento'] }}
                </div>
                <br>
                <div class="col-sm-6" style="max-width: 50%;box-sizing: border-box; position: relative;">
                    <b>Cliente:</b><br>
                    {{ $pedido['cliente']['nome'] }}
                </div>
                <br>
                <div class="col-sm-4" style="max-width: 33%;box-sizing: border-box; position: relative;">
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
                <br>
                @endif
                @if (!is_null($pedido['pedido_gerado']))
                <div class="col-sm-4">
                    <b>Pedido gerado: </b><br>
                    {!! $pedido['pedido_gerado'] !!}
                </div>
                <br>
                @endif
            </div>
            @endif
            <hr>
            <div class="row ">
                <div class="col-sm-4">
                    <b>Data do pedido:</b><br>
                    {!! $pedido['data_pedido'] !!}
                </div>
                <br>
                <div class="col-sm-4">
                    <b>Pedido futuro?</b><br>
                    {{ $pedido['pedido_futuro'] }}
                </div>
                <br>
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
                <br>
                <div class="col-sm-3">
                    <b>Tipo de Frete:</b><br>
                    {{ $pedido['transportadora']['tipo_frete'] }}
                </div>
                <br>
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
                <br>
                <div class="col-sm-3">
                    <b>Tipo de Frete:</b><br>
                    {!! $pedido['transportadora_redespacho']['tipo_frete'] !!}
                </div>
                <br>
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
                <br>
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
                <br>
                @if(isset($pedido['no_pedido_compra']))
                <div class="col-sm-3">
                    <b>Pedido compra: </b><br>
                    {{ $pedido['no_pedido_compra'] }}
                </div>
                @endif
            </div>
            <hr>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="text-align: left">Código</th>
                        <th style="text-align: left">Linha</th>
                        <th style="text-align: left">Quantidade</th>
                        <th style="text-align: left">Preço unitário</th>
                        <th style="text-align: left">Valor total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedido['itens'] as $value)
                    <tr>
                        <td>{{ $value['cod_produto'] }}</td>
                        <td>{{ $value['descricao'] }}</td>
                        <td style="text-align: right; margin-left: 5px">{{ $value['quantidade'] }}</td>
                        <td style="text-align: right; margin-left: 5px">{{ $value['preco_unitario'] }}</td>
                        <td style="text-align: right">{{ $value['valor_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <br>
            <br>
            <div class="m-4" style="clear: both; float: right;">
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
        </div>
        

        <p><h5>Para continuar o pedido, favor assine o documento.</h5>
    </body>
   
</html>