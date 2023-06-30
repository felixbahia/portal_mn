@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_notas_lancadas">
            <thead>
                <tr>
                    <th>Nota</th>
                    <th>Emissão</th>
                    <th>Cliente</th>
                    <th>Valor</th>
                    <th>Peso</th>
                    <th>Custo</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($dados as $item)
                <tr>
                    <td>
                        <a href='#' onclick=showCteDetalhesNasajon('{{ $item["id"] }}')> {{ $item['numero'] }} </a> 
                    </td>
                    <td>{{ $item['emissao'] }}</td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['cliente'] }}">{{ $item['cliente'] }}</div></div></td>
                    <td>{{ $item['valor'] }}</td>
                    <td>{{ $item['peso'] }}</td>
                    <td>{{ $item['custo'] }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td style="font-size: 12px;">{{ $total['valor'] }}</td>
                    <td style="font-size: 12px;">{{ $total['peso'] }}</td>
                    <td style="font-size: 12px;">{{ $total['custo'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_dialog_artigos = [];
    table_dialog_artigos = $('#table_notas_lancadas')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "autoWidth": false,
        "orderMulti": false,
        "scrollCollapse": true,
        "pageLength": 15,
        "ordering": true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Notas Lançadas Transportadora X Frete',
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
                            if(column === 4){
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
                "targets": [0,3,4,5],
            },
            {
                "class": "tb_date", 
                "targets": [1],
            },
            {
                "width": "10%", 
                "targets": [0,1,3,4,5],
            },
            {
                "width": "25%", 
                "targets": [2],
            },
        ]
    });
    setTimeout(function(){
        table_dialog_artigos.draw();
    }, 200);
});


function showCteDetalhesNasajon($id){
    $.ajax({
        url: '{{ route('notas_importadas.modal.notas.cte')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id,
            link_pedido: true,
            origem: 'NASAJON'
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })

        }
    });
}

</script>
@endsection