@extends('layouts.app-print')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <h5><b>Projeto nº</b> {{ $dados['id'] }} - <b>Nome</b> {{ $dados['nome_projeto'] }}</h5>
        <h5>Fornecedor: {{ $dados['fornecedor'] }}</h5>
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
@if(!empty($str_pedidos_compra) || !empty($str_pedidos_remessa))
<hr>
<div class="row ">
    @if(!empty($str_pedidos_compra))
    <div class="col-sm-2">
        <b>Pedido Compras: </b><br>
        <div class="text-right">{{ $str_pedidos_compra }}</div>
    </div>
    @endif
    @if(!empty($str_pedidos_remessa))
    <div class="col-sm-2">
        <b>Pedido Remessa: </b><br>
        <div class="text-right">{{ $str_pedidos_remessa }}</div>
    </div>
    @endif
</div>
@endif
<hr>
@if($dados['quantidade_total_produto'] > 0)

<div class="content-dialog-table">
    <h5>Produtos</h5>
    <table class="table table-striped" id="table-itens-print">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descricao</th>
                <th>Detalhe de Produção</th>
                <th class="tb_number">Quantidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados['produtos'] as $produto)
            <tr>
                <td>{{ $produto['codigo'] }}</td>
                <td>{{ $produto['nome'] }}</td>
                <td>{{ $produto['detalhes'] }}</td>
                <td class="tb_number">{{ $produto['quantidade'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td class="tb_number">Total:</td>
            <td class="tb_number">{{ $dados['quantidade_total_produto'] }}</td>
        </tfoot>
    </table>
</div>
@endif
@if($dados['quantidade_total_tecido'] > 0)

<div class="content-dialog-table">
    <h5>Tecido</h5>
    <table class="table table-striped" id="table-itens-print">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descricao</th>
                <th>Detalhe de Produção</th>
                <th class="tb_number">Quantidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados['tecidos'] as $tecido)
            <tr>
                <td>{{ $tecido['codigo'] }}</td>
                <td>{{ $tecido['nome'] }}</td>
                <td>{{ $tecido['detalhes'] }}</td>
                <td class="tb_number">{{ $tecido['quantidade'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td class="tb_number">Total:</td>
            <td class="tb_number">{{ $dados['quantidade_total_tecido'] }}</td>
        </tfoot>
    </table>
</div>
@endif

<div class="content-dialog-table">
    @if(!empty($dados['faccoes']))
        <h5>Serviços</h5>
        <table class="table table-striped" id="table-dialog-faccoes">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Ref. Produto</th>
                    <th>Tipo de Serviço</th>
                    <th class="tb_number">Custo Unitário</th> 
                    <th class="tb_number">Quantidade</th>
                    <th class="tb_number">Custo Total</th> 
                </tr>
            </thead>
            <tbody>
                @foreach($dados['faccoes'] as $faccao)
                <tr>
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
                    <td></td>
                    @if(empty($faccao['tecido']))
                    <td></td>
                    @else
                    <td></td>
                    @endif
                    <td></td>
                    <td class="tb_number">Total: </td>
                    <td class="tb_number">{{ $dados['total_faccao'] }}</td>
                    <td class="tb_number">{{ $dados['custo_total_servico'] }}</td> 
                </tr>
            </tfoot>
        </table>
    @endif
</div>

<div class="content-dialog-table">
    <h5>Consumo</h5>
    <br>
    @foreach($produtos_consumo as $produto_consumo)
        <h7><b>Produto</b>: {{ $produto_consumo['produto'] }} - <b>Quantidade:</b> {{ $produto_consumo['quantidade'] }}</h7>
        <br>
        <table class="table table-striped" id="table-dialog-faccoes">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Descrição</th>
                    <th class="tb_number">Consumo por Peça</th> 
                    <th class="tb_number">Consumo Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($produto_consumo['tecido'] as $tecido_consumo)
                    <tr>
                        <td>{{ $tecido_consumo['codigo_produto'] }}</td>
                        <td>{{ $tecido_consumo['descricao'] }}</td>
                        <td class="tb_number">{{ $tecido_consumo['consumo_unitario'] }}</td>
                        <td class="tb_number">{{ $tecido_consumo['consumo_total'] }}</td>
                    </tr>
                @endforeach
                @foreach($produto_consumo['insumo'] as $insumo_consumo)
                    <tr>
                        <td>{{ $insumo_consumo['codigo_produto'] }}</td>
                        <td>{{ $insumo_consumo['descricao'] }}</td>
                        <td class="tb_number">{{ $insumo_consumo['consumo_unitario'] }}</td>
                        <td class="tb_number">{{ $insumo_consumo['consumo_total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
            </tfoot>
        </table>
        <hr>
    @endforeach
    @foreach($projeto_tecidos_consumo as $tecido)
        <h7><b>Tecido</b>: {{ $tecido['tecido'] }} - <b>Quantidade:</b> {{ $tecido['quantidade'] }}</h7>
        <br>
        <table class="table table-striped" id="table-dialog-faccoes">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Descrição</th>
                    <th class="tb_number">Consumo por Peça</th> 
                    <th class="tb_number">Consumo Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $tecido['codigo_tecido_consumo'] }}</td>
                    <td>{{ $tecido['descricao_tecido_consumo'] }}</td>
                    <td class="tb_number">{{ $tecido['consumo_unitario'] }}</td>
                    <td class="tb_number">{{ $tecido['consumo_total'] }}</td>
                </tr>
            </tbody>
            <tfoot>
            </tfoot>
        </table>
        <hr>
    @endforeach
</div>

<script>
    window.print();
    setTimeout(function () {
            window.close(); 
    }, 200);
</script>
@endsection