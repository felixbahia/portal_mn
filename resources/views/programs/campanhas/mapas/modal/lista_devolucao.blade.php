@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-titulo-cheques-vinculados">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Pedido</th>
                <th class="tb_number">Nota De Venda</th>
                <th class="tb_number">Nota De Devolução</th>
                <th>Cliente</th>
                <th class="sort-date">Data</th>
                <th class="tb_number">Valor</th>
            </tr>
        </thead>
        <tbody>
        	@foreach($retorno['pedido'] as $value)
        	<tr>
                <td>{{ $value['estabelecimento'] }}</td>
                <td><a href="#" class="btn-create" data-toggle="tooltip" data-placement="top" onclick="abrirPedido('{{ $value["numero_pedido"] }}');">{{ $value["numero_nasajon"] }}</a></td>
                <td><a href="#" class="btn-create" data-toggle="tooltip" data-placement="top" onclick="abrirNota('{{ $value["id_nota"] }}','{{ $value["numero_nota"] }}');">{{ $value["numero_nota"] }}</a></td>
                <td><a href="#" class="btn-create" data-toggle="tooltip" data-placement="top" onclick="showNotasDetalhes('{{ $value["id_nota_devolucao"] }}');">{{ $value["nota_devolucao"] }}</a></td>
                <td>{{ $value["cliente"] }}</td>
                <td>{{ $value["data"] }}</td>
                <td><b>{{ $value["valor"] }}</b><i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="O valor se refere a soma de todos os produtos da campanha vendidos pela nota." style="color: black;"></i></td>
        	</tr>
        	@endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan='6'>Total:</td>
                <td>{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script>
    table_filters_dialog_cheques = $('#table-filters-dialog-titulo-cheques-vinculados').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "pageLength": 15,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    });

    function abrirPedido($id){
        $.ajax({
            url: "{{ route("pedido_portal.detalhes") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido_id: $id
            },
            success: function(callback){
                $title = "Detalhes do pedido: "+$id; 
                $body = callback;
                $class = "modal-lg";
                createModal("view_pedido", $title, $body, $class);
            }
        });
    }

    function showNotasDetalhes($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }

    function abrirNota($id, $nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id_nota: $id,
                link_pedido: true,
                origem: 'NASAJON'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota: " + $nota, body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }
        });
    }
</script>
@endSection
