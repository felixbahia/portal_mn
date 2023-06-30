@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-modal-produto-terceiro">
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th>Kg</th>
                    <th>Metros</th>
                    <th>Outras Un.</th>
                    <th>Custo Contábil Portal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['fornecedor'] }}">{{ $dado['fornecedor'] }}</div></div></td>
                    <td class='tb_number'>{{ $dado['kg'] }}</td>
                    <td class='tb_number'>{{ $dado['metros'] }}</td>
                    <td class='tb_number'>{{ $dado['outras_unidades'] }}</td>
                    <td class='tb_number'>{{ $dado['custo_medio_contabil_portal'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total:</td>
                    <td class='tb_number'>{{ $total['KG'] }}</td>
                    <td class='tb_number'>{{ $total['metros'] }}</td>
                    <td class='tb_number'>{{ $total['outras_unidades'] }}</td>
                    <td class='tb_number'>{{ $total['custo_medio_contabil_portal'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
    $(document).ready( function () {
        table_estoque_terceiro_modal = $('#table-modal-produto-terceiro')
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
            "autoWidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: 'Análise de Produto',
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
                                if(column === 1 || column === 2 || column === 3){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 4);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }else if(column === 4){
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
                    "class": "tb_number", 
                    "targets": "tb_number"
                },{ targets: 0, "orderable": true, width: '15%'},
            ],
        });    
        table_estoque_terceiro_modal.draw();
    });
</script>
@endsection  