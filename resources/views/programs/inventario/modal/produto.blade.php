@extends('layouts.page-dialog')
@section('content')
    <div style="overflow: hidden;">
        <div class="content-dialog-table">
            <table class="table table-striped table-not-edit table-not-view" id="table_produto_anaslise">
                <thead>
                    <tr>
                        <th>Endereço</th>
                        <th>Código Lido</th>
                        <th>Operado</th>
                        <th class="sort-date">Data e Hora</th>
                        <th style="width: 15px;max-width: 15px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produtos as $item)
                        <tr>
                            <td>{{ $item['endereco'] }}</td>
                            <td>{{ $item['codigo'] }}</td>
                            <td>{{ $item['operador'] }}</td>
                            <td>{{ $item['data_hora'] }}</td>
                            <td><a href="#" class="bt-delete" data-toggle="tooltip" data-placement="left" data-html="true" title="Excluir" onclick="modalExcluirProdutoInventario('{{ $item['id'] }}', $(this).parents('tr'))"></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
<script type="text/javascript">
    table_dialog4 = [];
    table_dialog4 = $('#table_produto_anaslise')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "orderMulti": false,
        "autoWidth": false,
        "ordering": false,
        "scrollX": false,
        "scrollY": "65vh",
        "scrollCollapse": true,
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
                "class": "tb_number", 
                "targets": "tb_number",
                render: $.fn.dataTable.render.number( '.', ',', 2 )
            },
            { "class": "tb_date", targets: "sort-date" }
        ]
    });
    $(document).ready( function () {
        setTimeout(function(){
            table_dialog4.draw();
        }, 400);
    });
    
    function modalExcluirProdutoInventario($id, $row){
        $("[data-toggle='tooltip']").tooltip('hide');
        $.ajax({
            url: '{{ route('inventario.excluir_produto') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status == 'success'){
                    table_dialog4.row($($row)).remove().draw();
                    if(table_dialog4.data().any() === false){
                        $(document).find('#modal_produto').modal('hide');
                        $(document).find('.row_produto_{{ $produto["codigo"] }}').remove();
                    }
                }else{
                    message('Atenção', callback.message);
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', data.message);
                }else{
                    message('Atenção', 'Ocorreu um erro inesperado.');
                }
            }
        });
    }
</script>
@endsection