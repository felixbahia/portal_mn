@extends('layouts.page-dialog')
@section('content')
    <div style="overflow: hidden;">
        <div><b>Estoque disponivel no sistema</b>: {{ $estoque_disponivel }}</div>
        <hr />
        <div class="content-dialog-table">
            <table class="table table-striped table-not-edit table-not-view" id="table_produto_anaslise">
                <thead>
                    <tr>
                        <th>Endereço Inventário</th>
                        <th>Endereço ERP</th>
                        <th>Código Lido</th>
                        <th>Quantidade</th>
                        <th>Operado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($volumes as $item)
                        <tr @if($item['status']['error'] === true) class="error_inventario" @else class="success_inventario" @endif>
                            <td>{{ $item['endereco_inventario'] }}</td>
                            <td>{{ $item['endereco_erp'] }}</td>
                            <td>{{ $item['volume'] }}</td>
                            <td class="tb_number" >{{ $item['quantidade'] }}</td>
                            <td>{{ $item['operador'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection