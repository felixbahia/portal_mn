@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_notas">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Notas</th>
                <th>Fornecedor</th>
                <th class="tb_number">Peças</th>
                <th class="tb_number">Quantidades</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento"] }}">{{ $value["estabelecimento"] }}</div></div>
                </td>
                <td>
                    <a href="#" class="modal-nota" data-title="DETALHES DA NOTA: {{ $value["nota_numero"] }}" data-route='{{ route("controle_geral_expedicao.modal.exibir_nota_conferencia") }}' data-id_nota='{{ $value["nota_id"] }}'>{{ $value['nota_numero'] }}</a>
                </td>
               <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["fornecedor"] }}">{{ $value["fornecedor"] }}</div></div></td>
                <td>{{ $value["peca"] }}</td>
                <td>{{ $value["quantidade"] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class='text-right'>Total:</td>
                <td class='tb_number'>{{ $total['nota_numero'] }}</td>
                <td></td>
                <td class='tb_number'>{{ $total['peca'] }}</td>
                <td class='tb_number'>{{ parserQtd($total['quantidade']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_filters_dialog_notas = $('#table-filters-dialog_notas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
        "language": {
            "decimal":        ",",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" }
            ],
    });    

    table_filters_dialog_notas.on('draw', function () {
        $(document).find(".modal-nota").off("click");
        $(document).find(".modal-nota").on("click", function(event){
            event.stopPropagation();
            showModalNotaConferencia($(this));
        });
    });
    table_filters_dialog_notas.draw();
});

function showModalNotaConferencia($this){
    var $url = $($this).data("route");
    var $id_nota = $($this).data("id_nota");
    var $title = $($this).data("title");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id_nota : $id_nota},
        success: function(body){
            createModal("table-itens-nota-show", $title, body, 'modal-lg');
        }
    });
}
</script>
@endSection