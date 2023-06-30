@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_notas_com_critica">
            <thead>
                <tr>
                    <th><span data-toggle="tooltip" data-placement="top" title="Ordem de devolução" data-original-title="Ordem de devolução">Nota Cliente</span></th>
                    <th class="text_long">Cliente</th>
                    <th>Natureza de Operação</th>
                    <th class='tb_number'>Valor</th>
                    <th class="text_long">Crítica</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td class='tb_number'><a href="#" data-toggle="tooltip" data-placement="top" title="Nota de Devolução" onclick="mostrarNota('{{ $item['id'] }}')">{{ $item['nota'] }}</a></td>
                    <td class="text_long"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['destinatario'] }}">{{ $item['destinatario'] }}</div></div></td>
                    <td class='tb_number'>{{ $item['natureza'] }}</td>
                    <td>{{ $item['valor'] }}</td>
                    <td class="text_long"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['destinatario'] }}">{{ $item['critica'] }}</div></div></td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td></td>
                    <td>{{ $total['valor'] }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">

$(document).ready( function () {
        table_filters_notas_critica = $("#table_notas_com_critica").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "autoWidth": false,
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
                "class": "tb_number", 
                "type": 'num-fmt', 
                "targets": "tb_number",
                'width': '1vw',
            },
            {
                "targets": "text_long",
                "width": '25vw'
            }
        ],
        "order": [[ 1, 'asc' ]]
        }
    );
    setTimeout(function(){
        table_filters_notas_critica.draw();
    }, 200);
});

function mostrarNota($id){
    $.ajax({
        url: '{{ route('modal.notas.importadas')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id
        },
        success: function(body){
            createModal("nota_detalhes_importada", "Detalhes da nota", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })
        }
    });
}

</script>
@endsection