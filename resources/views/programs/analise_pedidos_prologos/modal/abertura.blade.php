@extends('layouts.page-dialog')
@section('content')
    <h4>Pedidos do estabelecimento: {{ $estabelecimento }}</h4>
    <hr />
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_produto_anaslise">
            <thead>
                <tr>
                    <th>Número do pedido</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Usuário que criou</th>
                    <th>Origem</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pedidos as $pedido)
                    <tr>
                        <td><a href="#" onclick="abrirPedidoCompleto('{{ $pedido['id'] }}', '{{ $pedido['origem'] }}')">{{ $pedido['numero_pedido'] }}</a></td>
                        <td>{{ $pedido['cliente'] }}</td>
                        <td>{{ $pedido['vendedor'] }}</td>
                        <td>{{ $pedido['usario_criou'] }}</td>
                        <td>{{ $pedido['origem'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>

    function abrirPedidoCompleto($pedido, $origem){

        if($origem == 'Prologos'){
            $origem = 'pedido';
        }

        var title = "Dados do pedido: "+$pedido;
        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {
                _token: "{{ csrf_token() }}",
                origem: $origem.toLowerCase(),
                pedido: $pedido,
                estabelecimento: '{{ $estabelecimento }}'
            },
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
                var modal = $(document).find("#itens_pedido");
            }
        });
    }
    </script>
@endsection