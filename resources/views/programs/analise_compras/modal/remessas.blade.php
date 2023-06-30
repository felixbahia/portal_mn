@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-analitic-remessas">
            <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Ano</th>
                <th class="tb_number">Mês</th>
                <th id="vendas" class="tb_number">Remessas @if(isset($remessas)) ({{ $remessas }} Meses) @endif</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($dados as $estabelecimento => $dado)
                @foreach($dado as $ano => $valor)
                    @foreach ($valor as $mes => $valores)
                        <tr>
                            <td>{{ $estabelecimento }}</td>
                            <td>{{ $ano }}</td>
                            <td>{{ $mes }}</td>
                            <td>{{ parserValor($valores) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
            </tbody>
            <tfoot>
            <td></td>
            <td></td>
            <td>Total</td>
            <td>{{ parserValor($totalRemessas) }}</td>
            </tfoot>
        </table>
    </div>
</div>
<script>
table_filters_analitic = $('#table-filters-analitic-remessas')
.on( 'error.dt', function ( e, settings, techNote, men ) {
    hide_loader();
    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
}).DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "paging": true,
    "orderMulti": false,
    "pageLength": 20,
    'rowsGroup': [0,1],
    dom: 'Bfrtip',
    buttons: [
        {
            extend: 'excelHtml5',
            text: ' ',
            title: '',
            footer: true,
            exportOptions: {
                columns: ':visible',
                format: {
                    body: function(data, row, column, node) {
                        data = $('<p>' + data + '</p>').text();
                        if(column >= 1){
                            if(data != ''){
                                numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
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
    "order": [[ 2]],
    "columnDefs": [
        {
            "class": "tb_number",
            "targets": "tb_number"
        },
    ]
});
</script>
@endsection

