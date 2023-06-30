@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-necessidade_compras">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Número do Pedido</th>
                <th>Fornecedor</th>
                <th>Situação</th>
                <th class="tb_date">Data da Emissão</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedidos as $pedido)
                <tr>
                    <td>{{ $pedido['estabelecimento'] }}</td>
                    <td class="tb_number">{{ $pedido['numero_pedido'] }}</td>
                    <td>{{ $pedido['fornecedor'] }}</td>
                    <td>{{ $pedido['situacao'] }}</td>
                    <td class="tb_date">{{ $pedido['data_emissao'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection