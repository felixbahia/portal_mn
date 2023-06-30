@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_modal_rastreabilidade">
            <thead>
            <tr>
                <th>Ação<br></th>
                <th>Produto</th>
                <th>Código</th>
                <th>Peça</th>
                <th>Em Posse de</th>
                <th>Proprietário</th>
                <th class='tb_number'>Documento</th>
                <th>Local Estoque</th>
                <th>Endereço</th>
                <th>Situação</th>
                <th>Usuário</th>
                <th class='tb_date'>Data Criação</th>
                <th class='tb_number'>Quantidade</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['acao'] }}">{{ $item['acao'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['produto'] }}">{{ $item['produto'] }}</div></div></td>
                    <td>{{ $item['codigo'] }}</td>
                    <td>{{ $item['fracao'] }}</td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['detentor'] }}">{{ $item['detentor'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['proprietario'] }}">{{ $item['proprietario'] }}</div></div></td>
                    <td>{{ $item['documento'] }}</td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="bottom" title="" data-original-title="{{ $item['local'] }}">{{ $item['local'] }}</div></div></td>
                    <td>{{ $item['endereco'] }}</td>
                    <td>{{ $item['situacao'] }}</td>
                    <td>{{ $item['usuario'] }}</td>
                    <td>{{ $item['data_criacao'] }}</td>
                    <td>{{ $item['quantidade'] }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tfoot>   
        </table>
    </div>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_dialog_clientes = [];
    table_dialog_clientes = $('#table_modal_rastreabilidade')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "scrollY": "50vh",
            "scrollCollapse": true,
            "ordering": true,
            "autoWidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: 'Lista de Clientes de pesquisa de satisfação',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                return data;
                            },
                            footer: function(data) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },

                },
            ],
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
                    "targets": "tb_number"
                },
                { 
                    "targets": 'tb_date',
                    "class": 'tb_date',
                },
            ]
        });
        table_dialog_clientes.on('draw', function () {
            $(document).find(".bt-view-formulario").off("click");
            $(document).find(".bt-view-formulario").on("click", function(event){
                event.stopPropagation();
                showModalFormulario($(this));
        });
    });
    setTimeout(function(){
        table_dialog_clientes.draw();
    }, 200);
});
</script>
@endsection
