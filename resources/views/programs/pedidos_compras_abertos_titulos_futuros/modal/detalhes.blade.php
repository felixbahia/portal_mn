@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-despesas" style="width:100%">
        <thead>
            <tr>
                <th>Estabelcimento</th>
                <th>Fornecedor</th>
                <th>Pedido</th>
                <th>Previsão Chegada</th>
                <th class="tb_date">Vencimento</th>
                <th class="tb_number">Parcela</th>
                <th class="tb_number">Valor</th>
                <th class="tb_number">Valor Tit. Não Lançado</th>
                <th class="tb_number">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retorno as $valores)
                <tr>
                    <td>{{$valores['estabelecimento']}}</td> 
                    <td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $valores['fornecedor'] }}">{{$valores['fornecedor']}}</div></div></td>
                    <td><a href="#" onclick="showComissaoDetalhes('{{$valores['pedido_compras_uuid']}}', '{{$valores['estabelecimento']}}', '')">{{ $valores['pedido_numero'] }}</a></td>
                    <td class="tb_date">{{ $valores['previsao_chegada'] }}</td>
                    <td class="tb_date">{{ $valores['vencimento'] }}</td>
                    <td>{{$valores['parcela']}}</td>
                    <td>{{$valores['valor']}}</td>
                    <td>{{$valores['valor_nao_lancado']}}</td>
                    <td>{{$valores['valor_final']}}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total :</td> 
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number">{{$total['valor']}}</td>
                <td class="tb_number">{{$total['valor_nao_lancado']}}</td>
                <td class="tb_number">{{$total['valor_final']}}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var table_dialog = $('#table-dialog-despesas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "ordering": false,
            "scrollCollapse": true,
            "scrollY": "70vh",
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
                { "class": "tb_date", targets: "tb_date" }
            ],
        });

        setTimeout(function(){
            table_dialog.draw(false);
        }, 180);
    });

    function showComissaoDetalhes(id, unidade, data){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                unidade: unidade,
                data: data
            },
            success: function(body){
                createModal("1", "Detalhe do Pedido Compras", body, 'modal-lg');
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