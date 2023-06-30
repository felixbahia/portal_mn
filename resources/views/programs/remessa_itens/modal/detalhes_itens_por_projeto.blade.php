@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-necessidade_compras">
        <thead>
            <th>Facção</th>
            <th>Produto a enviar</th>
            <th class="tb_number">Qtde a enviar</th>
            <th class="tb_number">Estoque</th>
            <th class="tb_number">Compras</th>
        </thead>
        <tbody>
            @foreach ($itens_remessa as $projeto)
                @foreach ($projeto as $faccao)
                    @foreach ($faccao as $item_remessa)
                    <tr>
                        <td>{{ $item_remessa['faccao'] }}</td>
                        <td>{{ $item_remessa['produto_a_enviar'] }}</td>
                        <td class="tb_number">{{ $item_remessa['qtde_a_enviar'] }}</td>
                        <td class="tb_number">{{ $item_remessa['estoque'] }}</td>
                        <td class="tb_number">{{ $item_remessa['compras'] }}</td>
                    </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
@endsection