@extends('layouts.page-dialog')

@section('content')		
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-view" id="table-filters-modal-prepago">
            <thead>
                <tr>
                    <th class='number_format'>Pedido</th>
                    <th class='date_format'>Emissão do Pedido</th>
                    <th>Cliente</th>
                    <th class='number_format'>Nota Fiscal</th>
                    <th class='date_format'>Emissão da Nota</th>
                    <th class='valores_prepago'>Valor do Título</th>
                    <th class='valores_prepago'>Valor pago</th>
                    <th class='valores_prepago'>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linhas as $linha)
                <tr>
                    <td>
                        <a href="#" data-route="{{  route('pedidos_orcamentos.modal') }}" data-id="{{ $linha['id_pedido'] }}" data-modal="modal-lg" data-title_modal="Detalhes do pedido: {{ $linha['pedido_nasajon'] }}" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModal(this);">{{ $linha['pedido_nasajon'] }}</a>
                    </td>
                    <td>{{ $linha['data_pedido_nasajon'] }}</td>
                    <td>{{ $linha['cliente'] }}</td>
                    <td>
                        <a href="#" data-id="{{ $linha['id_nota'] }}" data-modal="modal-lg" data-title_modal="Detalhes da nota: {{ $linha['nota_fiscal'] }}" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNota(this);">{{ $linha['nota_fiscal'] }}</a>
                    </td>
                    <td>{{ $linha['data_nota_fiscal'] }}</td>
                    <td>{{ $linha['valor_titulo'] }}</td>
                    <td>{{ $linha['valor_baixado'] }}</td>
                    <td>{{ $linha['saldo'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Totais:</td>
                    <td colspan='4'></td>
                    <td id='titulos_total'>{{ $totais['total_titulo'] }}</td>
                    <td id='valor_pago'>{{ $totais['total_pago'] }}</td>
                    <td id='valor_saldo_total'>{{ $totais['total_saldo'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>

    table_filters_modal_prepago = $('#table-filters-modal-prepago').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
        "language": {
            "decimal":        ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ",",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum registro encontrado",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        },
        "columnDefs": [
            {
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
            {
                "targets": 'valores_prepago',
                "className": 'valores_prepago',
            },
        ]
    });

    $(document).ready( function () {

        table_filters_modal_prepago.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
    });

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function showModalNota($this){
        var url = '{{ route('notas_nasajon.modal.exibir') }}';
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }
</script>
@endsection