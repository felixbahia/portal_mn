@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-dialog-pedido" id="table-filters-dialog">
        <thead>
            <tr>
                <th>Estabel</th>
                <th class="tb_number">Pedido</th>
                <th>Cliente</th>
                <th class="sort-date">Emissão</th>
                <th class="tb_number">Valor</th>
                <th>Forma de Pagamento</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($dados as $key => $value)
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="">{!! $value["estabelecimento"] !!}&nbsp;&nbsp;</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title=""><a href="#" id="bt_link" onclick="showItens('{!! $value["pedido_number"] !!}', '{!! $value["origem"] !!}', '')">{!! $value["pedido"] !!}</a></div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{!! $value["cliente_codigo"] !!} - {!! $value["cliente"] !!}">{!! $value["cliente"] !!}</div></div></td>
                <td>{!! $value["emissao"] !!}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="">{!! $value["valor"] !!}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="">{!! $value["condicao_pagamento"] !!}&nbsp;&nbsp;</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="">@if(!empty($value["status"])){!! $value["status"] !!}@else &nbsp; @endif</div></div></td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number">
                    <span>Total: {!! $total !!}</span>
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
                "lengthChange": false,
                "info": false,
                "scrollY": $height,
                "scrollCollapse": true,
                "paging": false,
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
                    { "class": "tb_number", type: 'number', targets: "tb_number" },
                    { "class": "tb_date", targets: "sort-date" }
                ]
            });
            $('[data-toggle="tooltip"]').tooltip();
        }, 100);
        $('#table-filters-dialog').find("td").find("div").find("div").on('mouseover', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && $($this).attr('data-original-title') === ""){
                $this.attr('data-original-title', $this.text());
                $this.attr('title', $this.text());
                $('[data-toggle="tooltip"]').tooltip();
                $($this).tooltip('show');
            }
        });
    });
    function showItens($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido"
        
        if($origem!='nasajon'){
            title+=": "+ $pedido;
        }

        $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido, estabelecimento: $estabelecimento},
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
            }
        });
    }
</script>
@endsection
