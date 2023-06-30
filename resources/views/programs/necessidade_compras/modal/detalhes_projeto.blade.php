@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-necessidade_compras">
        <thead>
            <tr>
                <th>Código</th>
                <th>Tecido/Insumo/Serviço</th>
                <th>Fornecedor</th>
                <th class="tb_number">Estoque</th>
                <th class="tb_number">Compras</th>
                <th class="tb_number">Necessidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($necessidades_compras as $necessidade_compra)
                <tr>
                    <td>{{ $necessidade_compra['codigo'] }}</td>
                    <td>{{ $necessidade_compra['produto'] }}</td>
                    <td>{{ $necessidade_compra['fornecedor'] }}</td>
                    <td class="tb_number">{{ $necessidade_compra['estoque'] }}</td>
                    <td class="tb_number">{{ $necessidade_compra['compras'] }}</td>
                    <td class="tb_number">{{ $necessidade_compra['necessidade'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection