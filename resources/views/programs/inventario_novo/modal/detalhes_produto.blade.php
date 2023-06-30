@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-despesas" style="width:100%">
        <thead>
            <tr>
                <th>Estabelcimento</th>
                <th>Código</th>
                <th>Descrição</th>
                <th>Grupo</th>
                <th>Marca</th>
                <th>Linha</th>
                <th>Unidade</th>
                <th class="tb_number">Qtde. Est.</th>
                <th class="tb_number">Qtde. Soma Pç.</th>
                <th class="tb_number">Vol. Pç.</th>
                <th class="tb_number">1a. Cont. Qtde.</th>
                <th class="tb_number">2a. Cont. Qtde.</th>
                <th class="tb_number">3a. Cont. Qtde.</th>
                <th class="tb_number">Dif. Qtde. Est. (1a.-Sld.)</th>
                <th class="tb_number">Dif. Qtde. Est. (2a.-Sld.)</th>
                <th class="tb_number">Dif. Qtde. Est. (3a.-Sld.)</th>
                <th class="tb_number">Dif. Qtde. Soma Pç. (1a.-Sld.)</th>
                <th class="tb_number">Dif. Qtde. Soma Pç. (2a.-Sld.)</th>
                <th class="tb_number">Dif. Qtde. Soma Pç. (3a.-Sld.)</th>
                <th class="tb_number">1a. Cont. Vol.</th>
                <th class="tb_number">2a. Cont. Vol.</th>
                <th class="tb_number">3a. Cont. Vol.</th>
                <th class="tb_number">Dif. Vol. (1a.-Sld.)</th>
                <th class="tb_number">Dif. Vol. (2a.-Sld.)</th>
                <th class="tb_number">Dif. Vol. (3a.-Sld.)</th>
                <th class="tb_number">Estoque Quantidade</th>
                <th class="tb_number">Estoque Volume</th>
                <th class="tb_number">1ª Contagem Quantidade</th>
                <th class="tb_number">1ª Contagem Volume</th>
                <th class="tb_number">Diferença Quantidade</th>
                <th class="tb_number">Diferença Volume</th>
                <th class="tb_number">2ª Contagem Quantidade</th>
                <th class="tb_number">2ª Contagem Volume</th>
                <th class="tb_number">Diferença Quantidade</th>
                <th class="tb_number">Diferença Volume</th>
                <th class="tb_number">3ª Contagem Quantidade</th>
                <th class="tb_number">3ª Contagem Volume</th>
                <th class="tb_number">Diferença Quantidade</th>
                <th class="tb_number">Diferença Volume</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $dado)
                <tr>
                    <td>{{$dado['estabelecimento_posse']}}</td>
                    <td>{{$dado['produto_codigo']}}</td> 
                    <td>{{$dado['produto_descricao']}}</td> 
                    <td>{{$dado['grupo']}}</td> 
                    <td>{{$dado['marca']}}</td>
                    <td>{{$dado['linha']}}</td>
                    <td>{{$dado['unidade']}}</td>
                    <td class="tb_number">{{$dado['saldo_estoque']}}</td>
                    <td class="tb_number">{{$dado['saldo']}}</td>
                    <td class="tb_number">{{$dado['volume']}}</td>
                    <td class="tb_number">{{$dado['contagem_1_estoque']}}</td>
                    <td class="tb_number">{{$dado['contagem_2_estoque']}}</td>
                    <td class="tb_number">{{$dado['contagem_3_estoque']}}</td>
                    <td class="tb_number">{{$dado['diferenca_1_saldo_estoque']}}</td>
                    <td class="tb_number">{{$dado['diferenca_2_saldo_estoque']}}</td>
                    <td class="tb_number">{{$dado['diferenca_3_saldo_estoque']}}</td>
                    <td class="tb_number">{{$dado['diferenca_1_estoque']}}</td>
                    <td class="tb_number">{{$dado['diferenca_2_estoque']}}</td>
                    <td class="tb_number">{{$dado['diferenca_3_estoque']}}</td>
                    <td class="tb_number">{{$dado['contagem_1_volume']}}</td>
                    <td class="tb_number">{{$dado['contagem_2_volume']}}</td>
                    <td class="tb_number">{{$dado['contagem_3_volume']}}</td>                    
                    <td class="tb_number">{{$dado['diferenca_1_volume']}}</td>                    
                    <td class="tb_number">{{$dado['diferenca_2_volume']}}</td>
                    <td class="tb_number">{{$dado['diferenca_3_volume']}}</td>    
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var table_dialog = $('#table-dialog-despesas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "orderMulti": false,
            "autowidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    customize: function( xlsx ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        $('row c[r^="C"]', sheet).attr( 's', '2' );
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column >= 7){
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
                    "targets": ($('#table-filters-produtos thead th').length - 1),
                    "orderable": false
                },
                {
                    "targets": ($('#table-filters-produtos thead th').length - 2),
                    "orderable": false
                },
                {
                    "targets": [6,7],
                    "class": 'number_format',
                },
                {
                    'targets': 1,
                    'width': '120px',
                    "orderable": false

                }

            ],
            "order": [[ 2, 'asc' ]]
        });

        setTimeout(function(){
            table_dialog.draw(false);
        }, 300);

        setTimeout(function(){
            table_dialog.draw(false);
        }, 400);
    });

</script>
@endsection