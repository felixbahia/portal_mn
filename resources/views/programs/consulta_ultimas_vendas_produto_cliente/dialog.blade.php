@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-filters-prd-cli">
            <thead>
                <tr>
                    <th>Estabelecimento</th>
                    <th class="sort-date">Data Emissão</th>
                    <th>Pedido</th>
                    <th>Nota</th>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="tb_number">Quantidade</th>
                    <th class="tb_number">Preço Unitário</th>
                    <th class="tb_number">Preço Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itens as $item)
                    <tr>
                        <td>{{ $item['estabelecimento'] }}</td>
                        <td class="sort-date">{{ $item['data'] }}</td>
                        <td><a href="#" id="bt_link" onclick="showItensDialog('{{ $item['pedido_id'] }}', '{{ $item['pedido_origem'] }}', '{{ $item['estabelecimento'] }}')">{{ $item['pedido'] }}</a></td>
                        <td><a href="#" id="bt_link" onclick="showNotasDetalhes('{{ $item['id_nota'] }}')">{{ $item['nota'] }}</a>
                        <td>{{ $item['codigo'] }}</td>
                        <td>{{ $item['descricao'] }}</td>
                        <td class="tb_number">{{ $item['quantidade'] }}</td>
                        <td class="tb_number">{{ $item['preco_unitario'] }}</td>
                        <td class="tb_number">{{ $item['preco_total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    
    function showItensDialog($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido: "+$pedido;
        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {
                _token: "{{ csrf_token() }}",
                origem: $origem,
                pedido: $pedido,
                estabelecimento: $estabelecimento
            },
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })
            }
        });
    }

    function showNotasDetalhes($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                link_pedido: true,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }

        });

    }
</script>
@endsection 
