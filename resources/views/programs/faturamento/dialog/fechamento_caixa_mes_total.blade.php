@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-dialog-fechamento_caixa_mes">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_date">Data</th>
                @if($prepago == 'true')
                <th class="tb_number">Pré-Pago</th>
                @endif
                <th class="tb_number">Dinheiro</th>
                <th class="tb_number">Cartão Débito</th>
                <th class="tb_number">Cartão Crédito</th>
                <th class="tb_number">Duplicatas</th>
                <th class="tb_number">Carteira</th>
                <th class="tb_number">Usar Crédito</th>
                <th class="tb_number">Cartão BNDES</th>
                <th class="tb_number">MERCADO PAGO</th>
                @if($total['pix_feira'] > 0)
                <th class="tb_number">PIX - FEIRA</th>
                @endif
                @if($devolucao == 'true')
                <th class="tb_number">Devolução</th>
                @endif
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultado as $estabelecimento)
                @foreach($estabelecimento as $dado)
                <tr>
                    <td>{{ $dado['estabelecimento'] }}</td>
                    <td><span style="display:none">{{ $dado['data'] }}</span>{{ $dado['data_descricao'] }}</td>
                    @if($prepago == 'true')
                    <td>{{ $dado['prepago'] }}</td>
                    @endif
                    <td>{{ $dado['dinheiro'] }}</td>
                    <td>{{ $dado['cartao_debito'] }}</td>
                    <td>{{ $dado['cartao_credito'] }}</td>
                    <td>{{ $dado['duplicata'] }}</td>
                    <td>{{ $dado['carteira'] }}</td>
                    <td>{{ $dado['usar_credito'] }}</td>
                    <td>{{ $dado['cartao_bndes'] }}</td>
                    <td>{{ $dado['mercado_pago'] }}</td>
                    @if($total['pix_feira'] > 0)
                    <td>{{ $dado['pix_feira'] }}</td>
                    @endif
                    @if($devolucao == 'true')
                    <td>{{ $dado['devolucao'] }}</td>
                    @endif
                    <td>{{ $dado['total'] }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td class="tb_number">Total: </td>
                @if($prepago == 'true')
                <td class="tb_number">{{ $total['prepago'] }}</td>
                @endif
                <td class="tb_number">{{ $total['dinheiro'] }}</td>
                <td class="tb_number">{{ $total['cartao_debito'] }}</td>
                <td class="tb_number">{{ $total['cartao_credito'] }}</td>
                <td class="tb_number">{{ $total['duplicata'] }}</td>
                <td class="tb_number">{{ $total['carteira'] }}</td>
                <td class="tb_number">{{ $total['usar_credito'] }}</td>
                <td class="tb_number">{{ $total['cartao_bndes'] }}</td>
                <td class="tb_number">{{ $total['mercado_pago'] }}</td>
                @if($total['pix_feira'] > 0)
                <td class="tb_number">{{ $total['pix_feira'] }}</td>
                @endif
                @if($devolucao == 'true')
                <td class="tb_number">{{ $total['devolucao'] }}</td>
                @endif
                <td class="tb_number">{{ $total['total'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $height = $(document).find(".modal").height() - 200;
        table_fechamento_mes = $('#table-dialog-fechamento_caixa_mes').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    customize: function( xlsx ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column > 1){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
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
                    render: $.fn.dataTable.render.number( '.', ',', 2 )
                },
                { "class": "tb_date", targets: "tb_date" }
            ]
        });

        setTimeout(function(){
            table_fechamento_mes.draw(false);
        }, 300);
});
</script>
@endsection
