@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-dialog-pedido" id="table-filters-pedidos-abertos">
        <thead>
            <tr>
                <th>Estabel</th>
                <th>Pedido</th>
                <th>PCMN</th>
                <th>Nota</th>
                <th>Cliente</th>
                <th class="sort-date">Emissão</th>
                <th class="tb_number">Quantidade</th>
                <th>Forma de Pagamento</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($dados as $key => $value)
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="">{!! $value["estabelecimento"] !!}&nbsp;&nbsp;</div></div></td>
                <td><a href="#" onclick="abrirItensPedido('{!! $value["pedido_id"] !!}', '{!! $value["origem"] !!}', '')">{!! $value["pedido"] !!}</a></td>
                @if(!empty($value["PCMN"])) 
                    <td>
                        <a href="#" onclick="abrirPedidoCompra('{!! $value["PCMN_id"] !!}')">{!! $value["PCMN"] !!}</a>
                    </td>
                @else
                    <td></td>
                @endif
                @if(isset($value["nota_aberto"])) 
                    <td><a href="#" onclick="abrirItensNotaAberto('{!! $value["nota_aberto_id"] !!}', 'DETALHES DA NOTA: {{ $value["nota_aberto"] }}')">{!! $value["nota_aberto"] !!}</a></td>
                @elseif(isset($value["nota_nasajon"]))
                    <td><a href="#" onclick="abrirItensNotaNasajon('{!! $value["nota_nasajon_id"] !!}', 'DETALHES DA NOTA: {{ $value["nota_nasajon"] }}')">{!! $value["nota_nasajon"] !!}</a></td>
                @else
                    <td></td>
                @endif
                <td><div><div data-toggle="tooltip" data-html="true" title="{!! $value["cliente_codigo"] !!} - {!! $value["cliente"] !!}">{!! $value["cliente"] !!}</div></div></td>
                <td>{!! $value["emissao"] !!}</td>
                <td>{!! $value["quantidade"] !!}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="">{!! $value["condicao_pagamento"] !!}&nbsp;&nbsp;</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="">@if(!empty($value["status"])){!! $value["status"] !!}@else &nbsp; @endif</div></div></td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Total:</td>
            <td class="text-right">{{ $total }}</td>
            <td></td>
            <td></td>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        setTimeout(function(){
            table_pedidos = $("#table-filters-pedidos-abertos").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
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
                    {
                        "class": "tb_number", 
                        "targets": "tb_number"
                    },
                    { 
                        "class": "tb_date",
                        "targets": "sort-date" }
                ]
            });
            $('[data-toggle="tooltip"]').tooltip();
        }, 100);
        $('#table-filters-pedidos-abertos').find("td").find("div").find("div").on('mouseover', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && $($this).attr('data-original-title') === ""){
                $this.attr('data-original-title', $this.text());
                $this.attr('title', $this.text());
                $('[data-toggle="tooltip"]').tooltip();
                $($this).tooltip('show');
            }
        });
    });
    function abrirItensPedido($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido"
        
        if($origem!='nasajon'){
            title+=": "+ $pedido;
        }

        $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido, estabelecimento: $estabelecimento},
            method: 'POST',
            success: function(body){
                createModal("itens_pedido_aberto", title, body, 'modal-lg');
            }
        });
    }

    function abrirItensNotaAberto($nota_id, $title,){
        $.ajax({
            url: "{{ route('notas_aberto_nasajon.modal') }}",
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id : $nota_id},
            success: function(body){
                createModal("modal_nota_saida_aberto", $title, body, 'modal-lg');
            }
        });
    }

    function abrirItensNotaNasajon($nota_id, $title,){
        $.ajax({
            url: "{{ route('notas_nasajon.modal.exibir') }}",
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota : $nota_id},
            success: function(body){
                createModal("modal_nota_saida", $title, body, 'modal-lg');
            }
        });
    }

    function abrirPedidoCompra(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
            },
            success: function(body){
                createModal("modal_compra_aberto", "Detalhe do Pedido Compras", body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }

        });
    }
</script>
@endsection
