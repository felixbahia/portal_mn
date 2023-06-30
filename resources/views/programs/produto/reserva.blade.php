@extends('layouts.page-dialog')

@section('content')
    <div class="row_title_pecapeca">
        <div>
            <label>Estabelecimento:</label>
            <span>{{ $dados["estabelecimento"] }}</span>
        </div>
        <div>
            <label>Código Produto:</label>
            <span>{{ $dados["codigo"] }}</span>
        </div>
        <div>
            <label>Total Reserva (Pronta entrega):</label>
            <span>{{ $dados["total_pe"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Total Reserva (Pedido Futuro):</label>
            <span>{{ $dados["total_pf"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Total Reserva:</label>
            <span>{{ $dados["total_pedidos"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Unidade:</label>
            <span>{{ $dados["unidade"] }}</span>
        </div>
    </div>
    <div class="row_table_pecapeca">
        <table class="table table-striped" id="table_reserva">
            <thead>
                <tr>
                    <th colspan="2" class="tb_number">Pedido</th>
                    <th rowspan="2" class="tb_number">Nota</th>
                    <th colspan="2" class="tb_number">Compra Vinculada</th>
                    <th rowspan="2" class="tb_date">Data do pedido</th>
                    <th rowspan="2">Cliente</th>
                    <th rowspan="2">Vendedor</th>
                    <th colspan="2">Quantidade</th>
                    <th rowspan="2">Status</th>
                    <th rowspan="2">Estoque</th>
                </tr>
                <tr>
                    <th class="tb_number">portal</th>
                    <th class="tb_number">nasajon</th>
                    <th class="tb_number nasajon_pedido">Pedido</th>
                    <th class="tb_date nasajon_pedido">Prev. Entrega</th>
                    <th class="tb_number">Pedido</th>
                    <th class="tb_number">Separada</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dados["pedidos"] as $pedido)
                <tr>
                    <td data-order="{{ $pedido["pedido"] }}">
                        @if(!empty($pedido['pedido']))
                        <a href="#" id="bt_link" onclick="showItens('{{ $pedido["id"] }}', 'portal')">{{ $pedido["pedido"] }}</a>
                        @endif
                    </td>
                    <td data-order="{{ $pedido["pedido_nasajon"] }}">
                        @if(!empty($pedido['pedido_nasajon']))
                        <a href="#" id="bt_link" onclick="showItens('{{ $pedido["id_nasajon"] }}', 'nasajon')">{{ $pedido["pedido_nasajon"] }}</a>
                        @endif
                    </td>
                    <td>
                        @if(!empty($pedido['nota']))
                        {!! $pedido["nota"] !!}
                        @endif
                    </td>
                    <td>@if(!empty($pedido['pedido_compra'])){{ $pedido['pedido_compra'] }} @endif</td>
                    <td>@if(!empty($pedido['data_previsao_entrega'])){{ $pedido['data_previsao_entrega'] }} @endif</td>
                    <td data-order="{{ $pedido["data_pedido"] }}">{{ parserData($pedido["data_pedido"]) }}</td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $pedido["cliente"] }}">{{ $pedido["cliente"] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $pedido["vendedor"] }}">{{ $pedido["vendedor"] }}</div></div></td>
                    <td>{{ $pedido["quantidade"] }}</td>
                    <td data-order="{{ $pedido["quantidade_separada"] }}"><a href="#" class="btn-view-pecas-pedidos" data-title="Produtos reservados - {{ $pedido["pedido"] }}" data-url="{{ route('produto.pecas_reserva') }}" data-estabel="{{ $dados["fields"]["estabel"] }}" data-codigo="{{ $pedido["pedido"] }}" data-codigo_item="{{ $dados["fields"]["codigo"] }}">{{ $pedido["quantidade_separada"] }}</a></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $pedido["status"] }}">{{ $pedido["status"] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $pedido["tipo_estoque"] }}">{{ $pedido["tipo_estoque"] }}</div></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
        $(document).find('[data-toggle="tooltip"]').tooltip({placement:'right'});
        
        var table_lancamentos = $("#table_reserva").DataTable({
            "searching": false,
            "autoWidth": false,
            "lengthChange": false,
            "info": false,
            "pageLength": -1,
            "scrollY":"65vh",
            "scrollCollapse": true,
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum pedido encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum pedido encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", "targets": "tb_number" },
                { "class": "tb_date", "targets": "tb_date" },
                { "targets": [0, 1, 2], 'width': '100px' },
                { "targets": [3, 4, -1], 'width': '150px' },
                { "targets": [5, 6], 'width': '250px' },
            ],
            "order": [[ 4, 'asc' ]]
        });
        function showItens($pedido, $origem){
            var title = "Dados do pedido: "+$pedido;
            xhr = $.ajax({
                url: "{{ route('pedidos_orcamentos.show') }}",
                data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido},
                method: 'POST',
                success: function(body){
                    createModal("itens_pedido", title, body, 'modal-lg');
                    var modal = $(document).find("#itens_pedido");
                    modal.css("z-index", 30);
                    $(".modal-backdrop").css("z-index", 28);

                    $("#itens_pedido").off('hidden.bs.modal');
                    $("#itens_pedido").on('hidden.bs.modal', function (e) {
                        $(".modal-backdrop").css("z-index", 13);
                        $("#itens_pedido").remove();
                    });
                }
            });
        }

        function showNotaDetalhes($id){
            var url = '{{ route('notas_nasajon.modal.exibir') }}';
            var title = 'Detalhes da nota';
            var id = $id;
            xhr = $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", id_nota: id},
                method: 'POST',
                success: function(body){
                    if(body.status === 'error'){
                        message('Erro',body.message,'');
                    }else{
                        createModal("modal_nota_entrada", title, body, 'modal-lg');
                    }
                }
            });
        }
        function showNotaDetalhesAberta($id){
            var url = '{{ route('notas_aberto_nasajon.modal') }}';
            var title = 'Detalhes da nota';
            var id = $id;
            xhr = $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", id: id},
                method: 'POST',
                success: function(body){
                    if(body.status === 'error'){
                        message('Erro',body.message,'');
                    }else{
                        createModal("modal_nota_entrada", title, body, 'modal-lg');
                    }
                }
            });
        }
        setTimeout(function(){
            table_lancamentos.draw();
        }, 400);
    </script>
@endsection