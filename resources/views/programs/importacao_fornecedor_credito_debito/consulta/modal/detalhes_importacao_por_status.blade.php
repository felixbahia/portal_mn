@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-dados">
        <thead>
            <tr>
                <th>Proforma</th>
                <th class="tb_number">PCMN</th>
                <th>Referência</th>
                <th class="tb_date">Data da Proforma</th>
                <th class="tb_number">Custo Previsto</th>
                <th class="tb_number">Custo Realizado</th>
                <th class="td_acao"></th>
            </tr>            
        </thead>
        <tbody>
            @foreach ($linhas as $linha)
            <tr>
                <td>{{$linha['proforma']}}</td>
                <td class="tb_number"><a href='#' onclick="mostrarPedidoComprasDetalhes('{{$linha['id_pedido']}}')">{{$linha['pcmn']}}</a></td>
                <td>{{$linha['referencia']}}</td>
                <td class="tb_date">{{$linha['data']}}</td>
                <td class="tb_number">{{$linha['custo_previsto']}}</td>
                <td class="tb_number">{{$linha['custo_realizado']}}</td>
                <td class="td_acao"><a href="#" class="bt-view" data-toggle='tooltip' data-html='true' data-id="{{$linha['id']}}" title='Visualizar' onclick="modalDetalhesImportacao($(this))"></a></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td class="tb_number"></td>
                <td></th>
                <td class="tb_number"></td>
                <td class="tb_number">{{$total['custo_previsto']}}</td>
                <td class="tb_number">{{$total['custo_realizado']}}</td>
                <td class="td_acao"></th>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-dados').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
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
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });
    
    function modalDetalhesImportacao($value){
        var $id = $value.data("id");
    
        var title = 'Detalhes do Importação';
        esconderPopoverTooltip();
        $.ajax({
            url: '{{ route('importacao.modal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id 
            },
            success: function (data){
                createModal('detalhes_projeto', title, data, 'modal-lg');
            }
        });
    }

    function mostrarPedidoComprasDetalhes(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
            },
            success: function(body){
                createModal("modal_compras_detalhes", "Detalhe do Pedido Compras", body, 'modal-lg');
                var modal = $("#modal_compras_detalhes");
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