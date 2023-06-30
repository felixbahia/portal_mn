@extends('layouts.app-print')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <h5>Detalhes - Projeto nº {{ $dados['id'] }} - Nome do Projeto {{ $dados['nome_projeto'] }} - Vendedor: {{ $dados['vendedor'] }}</h5>
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-5">
        <b>Estabelecimento:</b><br>
        {{ $dados['estabelecimento'] }}
    </div>
    <div class="col-sm-7">
        <b>Cliente:</b><br>
        {{ $dados['cliente_cnpj'] }} - {{ $dados['cliente_nome'] }}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-4">
        <b>Data do pedido:</b><br>
        {!! $dados['data'] !!}
    </div>
    <div class="col-sm-4">
        <b>Previsão de entrega:</b><br>
        {!! $dados['data_previsao_entrega'] !!}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-12">
        <b>Condição de pagamento:</b><br>
        {{ $dados['condicao_pagamento_descr'] }}
    </div>
</div>
<hr>
<div class="row ">
    <div class="col-sm-3">
        <b>Tipo de Frete:</b><br>
        {{ $dados['tipo_frete'] }}
    </div>
    <div class="col-sm-2">
        <b>Frete Adicional:</b><br>
        <div class="text-right">{{ $dados['frete_adicional'] }}</div>
    </div>
</div>
<hr>
<div class="row"> 
    <div class="col-sm-6">
        <b>Email do contato:</b><br>
        {!! $dados['email_comprador'] !!}
    </div>
</div>

@if(!empty($dados['pedido_venda']))
<hr>
<div class="row ">
    <div class="col-sm-2">
        <b>Pedido Venda: </b><br>
        <div class="text-right">{{ $dados['pedido_venda'] }}</div>
    </div>
</div>
@endif

<hr>
<div class="content-dialog-table">
    <h5>Produtos</h5>
    <table class="table table-striped" id="table-itens-print">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descricao</th>
                <th>Detalhe de Produção</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Preço Unitário</th>
                <th class="tb_number">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados['produtos'] as $produto)
            <tr>
                <td>{{ $produto['codigo'] }}</td>
                <td>{{ $produto['nome'] }}</td>
                <td>{{ $produto['detalhes'] }}</td>
                <td class="tb_number">{{ $produto['quantidade'] }}</td>
                <td class="tb_number">{{ $produto['preco_venda'] }}</td>
                <td class="tb_number">{{ $produto['valor_total'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="tb_number">Total:</td>
            <td class="tb_number">{{ $dados['total_produto'] }}</td>
        </tfoot>
    </table>
</div>

<div class="tab-pane" id="projeto_detalhes" role="tabpanel" aria-labelledby="dados-tab">
    <div class="content-dialog-table">
        @if(!empty($dados['tecidos']))
        <br>
            <h5>Tecidos</h5>
            <table class="table table-striped" id="table-dialog-tecidos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Ref. Produto</th>
                        <th class="tb_number">Consumo por Peça</th>
                        <th class="tb_number">Consumo Total</th>
                        <th class="tb_number">Preço Unitário</th>
                        <th class="tb_number">Preço Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['tecidos'] as $tecido)
                    <tr>
                        <td>{{ $tecido['codigo'] }}</td>
                        <td>{{ $tecido['descricao'] }}</td>
                        <td>{{ $tecido['referencia_produto'] }}</td>
                        <td class="tb_number">{{ $tecido['consumo_por_peca'] }}</td>
                        <td class="tb_number">{{ $tecido['consumo_total'] }}</td>
                        <td class="tb_number">{{ $tecido['custo_unitario'] }}</td>
                        <td class="tb_number">{{ $tecido['custo_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="tb_number">Total: </td>
                        <td class="tb_number">{{ $dados['total_tecido'] }}</td>	
                    </tr>
                </tfoot>
            </table>
        @endif

        @if(!empty($dados['insumos']))
            <h5>Insumos/Acessórios</h5>
            <table class="table table-striped" id="table-dialog-insumos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Ref. Produto</th>
                        <th class="tb_number">Consumo Total</th>
                        <th class="tb_number">Preço Unitário</th>
                        <th class="tb_number">Preço Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['insumos'] as $insumo)
                    <tr>
                        <td>{{ $insumo['codigo'] }}</td>
                        <td>{{ $insumo['descricao'] }}</td>
                        <td>{{ $insumo['referencia_produto'] }}</td>
                        <td class="tb_number">{{ $insumo['consumo_total'] }}</td>
                        <td class="tb_number">{{ $insumo['custo_unitario'] }}</td>
                        <td class="tb_number">{{ $insumo['custo_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="tb_number">Total: </td>
                        <td class="tb_number">{{ $dados['total_insumo'] }}</td>	
                    </tr>
                </tfoot>
            </table>
        @endif

        @if(!empty($dados['faccoes']))
            <h5>Serviços</h5>
            <table class="table table-striped" id="table-dialog-faccoes">
                <thead>
                    <tr>
                        @if(!empty($dados['com_faccao']) && (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16))
                            <th>CNPJ</th>
                            <th>Facção</th>
                        @endif
                        <th>Tipo</th>
                        <th>Ref. Produto</th>
                        <th>Tipo de Serviço</th>
                        <th class="tb_number">Preço Unitário</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Preço Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['faccoes'] as $faccao)
                    <tr>
                        
                        @if(!empty($dados['com_faccao']) && (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16))
                            <td>{{ $faccao['cnpj'] }}</td>
                            <td>{{ $faccao['faccao'] }}</td>
                        @endif
                        <td>{{ $faccao['tipo'] }}</td>
                        @if(empty($faccao['tecido']))
                        <td>{{ $faccao['referencia_produto'] }}</td>
                        @else
                        <td>{{ $faccao['tecido'] }}</td>
                        @endif
                        <td>{{ $faccao['tipo_de_servico'] }}</td>
                        <td class="tb_number">{{ $faccao['custo_unitario'] }}</td>
                        <td class="tb_number">{{ $faccao['quantidade'] }}</td>
                        <td class="tb_number">{{ $faccao['custo_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        @if(!empty($dados['com_faccao']) && (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16))
                        <td></td>
                        <td></td>
                        @endif
                        <td></td>
                        @if(empty($faccao['tecido']))
                        <td></td>
                        @else
                        <td></td>
                        @endif
                        <td></td>
                        <td></td>
                        <td class="tb_number">Total: </td>
                        <td class="tb_number">{{ $dados['total_faccao'] }}</td>	
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
</div>
<div class="m-4" style="clear: both;">
	<div class='row mr-3'>
		<div class="col-sm-7"></div>
		<div class='col-sm-3'>Preço do Tecido</div>
		<div class='col-sm-2 text-right'>{{ $dados['total_tecido'] }}</div>
    </div>
    <div class='row mr-3'>
		<div class="col-sm-7"></div>
		<div class='col-sm-3'>Preço do Insumo</div>
		<div class='col-sm-2 text-right'>{{ $dados['total_insumo'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-7"></div>
		<div class='col-sm-3'>Preço Mão de Obra</div>
		<div class='col-sm-2 text-right'>{{ $dados['total_faccao'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-7"></div>
		<div class='col-sm-3'>Frete Adicional</div>
		<div class='col-sm-2 text-right'>{{ $dados['frete_adicional'] }}</div>
    </div>
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3 border-top'><b>Valor Total do Preço</b></div>
        <div class='col-sm-2 text-right border-top'><b>{{ $dados['custo_total'] }}</b></div>
    </div>
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3 border-top'><b>Preço Unitário MN</b></div>
        <div class='col-sm-2 text-right border-top'><b>{{ $dados['custo_unitario_mn'] }}</b></div>
    </div>
</div>

<div class="m-4" style="clear: both;">
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3'>Comissao</div>
        <div class='col-sm-2 text-right'>{{ $dados['comissao'] }}</div>
    </div>
    @if(!empty($dados['desconto']))
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3'>Desconto</div>
        <div class='col-sm-2 text-right'>{{ $dados['desconto'] }}</div>
    </div>
    @endif
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3'>Quantidade Total</div>
        <div class='col-sm-2 text-right'>{{ $dados['quantidade_total'] }}</div>
    </div>
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3 border-top'><b>Valor Total do Pedido</b></div>
        <div class='col-sm-2 text-right border-top'><b>{{ $dados['total_do_pedido'] }}</b></div>
    </div>
    <div class='row mr-3'>
        <div class="col-sm-7"></div>
        <div class='col-sm-3 border-top'><b>Preço Médio de Venda</b></div>
        <div class='col-sm-2 text-right border-top'><b>{{ $dados['preco_medio_venda'] }}</b></div>
    </div>
</div>
<script>
    window.print();
    setTimeout(function () {
            window.close(); 
    }, 200);
</script>
@endsection