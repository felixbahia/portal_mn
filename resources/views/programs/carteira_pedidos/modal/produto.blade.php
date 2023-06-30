@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-dialog-pedido" id="table-filters-dialog">
        <thead>
            <tr>
                <th class="tb_estabelecimento">Estabelecimento</th>
                <th>Grupo</th>
                <th>Linha</th>
                <th>Marca</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Valor Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($dados as $key => $value)
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="">{{ $value["estabelecimento"] }}&nbsp;&nbsp;</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["grupo"] }}">{{ $value["grupo"] }}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["linha"] }}">{{ $value["linha"] }}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["marca"] }}">{{ $value["marca"] }}</div></div></td>
                <td class="tb_number">{{ $value["quantidade_faturada"] }}</td>
                <td class="tb_number">{{ $value["valor"] }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>Total:</td>
                <td class="tb_number">
                    {!! $total['total_quantidade'] !!}
                </td>
                <td class="tb_number">
                    {!! $total['total_valor'] !!}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    table_dialog = [];
    var $height = 200;
    $('#table-filters-dialog').find("td").find("div").find("div").off('mouseover');
    $(document).ready(function () {
        setTimeout(function(){
            $height = $(".modal-body").height() - 130;
            $.fn.dataTable.moment('DD/MM/YYYY');
            table_dialog = $("#table-filters-dialog").DataTable({
                "searching": false,
                "paging": true,
                "lengthChange": false,
                "info": false,
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
                    { 
                        "targets": 'tb_number',
                        "class": 'number_format',
                        "width": '5px' 
                    },
                    {
                        "targets": "tb_estabelecimento",
                        "width": '5%',
                        "orderable": false,
                    },
                    {
                        "targets": 1,
                        "width": "15%"
                    }
                ]

        });
        }, 100);
    });
</script>
@endsection
