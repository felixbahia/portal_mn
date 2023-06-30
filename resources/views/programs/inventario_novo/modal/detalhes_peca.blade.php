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
                <th>Peça</th>
                <th>Saldo</th>
                <th>Quantia Lida</th>
                <th>Endereço no Sistema</th>
                <th>Endereço Lido</th>
                <th>Existe no Sistema</th>
                <th>1ª Contagem</th>
                <th>2ª Contagem</th>
                <th>3ª Contagem</th>
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
                    <td>{{$dado['fracao_codigo']}}</td>
                    <td class="tb_number">{{parserValor($dado['saldo'])}}</td>
                    <td class="tb_number">{{parserValor($dado['saldo'])}}</td>
                    <td>{{$dado['endereco']}}</td>
                    <td>{{$dado['endereco_lido']}}</td>
                    <td>{{$dado['lido']}}</td>
                    <td>{{$dado['lido_contagem_1']}}</td>
                    <td>{{$dado['lido_contagem_2']}}</td>
                    <td>{{$dado['lido_contagem_3']}}</td>
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
                                if(column == 8 || column == 9){
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