@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-filters-dialog">
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Descrição</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($produtos as $produto)
                <tr>
                    <td>{{ $produto['codigo'] }}</td>
                    <td>{{ $produto['descricao'] }}</td>
                    <td>
                        <a
                            href="#"
                            class="bt-estoque2 bt-estoque-portal-estabelecimento"
                            data-url="{{ route('produto.movimento_estoque.movimento_portal_estabelecimentos') }}"
                            data-title="Movimento de Estoque Portal"
                            data-codigo="{{ $produto['codigo'] }}"
                            data-toggle="tooltip"
                            data-placement="left"
                            title="Movimento de Estoque Portal"
                            style="float: left;"
                        >
                            <i class="far fa-list-alt" style="color: #000;float: left;font-size: 20px;"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
    table_dialog = [];
    var $height = 200;
    $(document).ready(function () {
        $(document).find(".bt-estoque-portal-estabelecimento").off("click");
        $(document).find(".bt-estoque-portal-estabelecimento").on("click", function(event){
            event.stopPropagation();
            showModalMovimentoEstoqueEstabelecimentos($(this));
        });
        setTimeout(function(){
            $height = $(document).find(".modal-body:visible").height() - 100;
            $.fn.dataTable.ext.errMode = 'throw';
            $.fn.dataTable.moment('DD/MM/YYYY');
            table_dialog = $(document).find("#table-filters-dialog").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "scrollY": $height,
                "scrollCollapse": true,
                "paging": false,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        footer: true,
                        customize: function ( xlsx ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        },
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            },
                            format: {
                                body: function ( data, row, column, node ) {
                                    return (column === 7 || column === 7 || column === 7 || column === 7 || column === 7 ) ?
                                        data.replace( /[$.]/g, '' ).replace( /[$,]/g, '.' ).toLocaleString('pt-BR') :
                                        data;
                                }
                            }
                        }
                    },
                ],
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
                    { "class": "tb_date", "type": "date", targets: "sort-date" }
                ],
                "order": [0, 'asc']
            });
            $('[data-toggle="tooltip"]').tooltip();
        }, 250);
    });

    function showModalMovimentoEstoqueEstabelecimentos($this){
        var url = $($this).data("url");
        var $codigo = $($this).data("codigo");
        var title = $($this).data("title") + ' - ' + $codigo;
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                codigo: $codigo,
            },
            success: function(body){
                createModal('modal_movimento_estoque_estabelecimento', title, body, "modal-lg");
                var modal = $("#modal_movimento_estoque_estabelecimento");
            }
        });
    }

</script>
@endsection 