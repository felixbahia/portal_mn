@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-dialog footer-pequeno" id="table-clientes-atrasos">
            <thead>
                <tr>
                    <th>Representante</th>
                    <th class='tb_number'>Aberto</th>
                    <th class='tb_number'>Vencido</th>
                    <th class='tb_number'>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($retorno as $dado)
                    <tr>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['representante'] }}">{{ $dado['representante'] }}</div></div></td>
                        <td>
                            <a href="#" class="modal-titulos-aberto-representante" data-route="{{ route('analise_atrasos_equipe.modal.titulo.representantes') }}" data-abertura='aberto' data-total='{{ $saidatotal }}' data-representante='true' data-title-modal="{{ ($filter['total'] != 'true') ? $dado['estabelecimento'] : 'Todos' }} - TITULOS FATURADOS EM ABERTO -  {{  $dado['representante']   }}" data-titulo_id="{{ $dado['titulo_id'] }}" data-filter="{{ $dado['filter'] }}" >{{ $dado['aberto'] }}</a>
                        </td>
                        <td>
                            <a href="#" class="modal-titulos-vencido-representante" data-route="{{ route('analise_atrasos_equipe.modal.titulo.representantes') }}" data-abertura='vencido' data-total='{{ $saidatotal }}' data-representante='true' data-title-modal="{{  ($filter['total'] != 'true') ? $dado['estabelecimento'] : 'Todos' }} - TITULOS FATURADOS VENCIDOS -  {{  $dado['representante']   }}" data-titulo_id="{{ $dado['titulo_id'] }}" data-filter="{{ $dado['filter'] }}" >{{ $dado['vencido'] }}</a>
                        </td>
                        <td>
                            <a href="#" class="modal-titulos-total-representante" data-route="{{ route('analise_atrasos_equipe.modal.titulo.representantes') }}" data-abertura='total' data-total='{{ $saidatotal }}' data-representante='true' data-title-modal="{{  ($filter['total'] != 'true') ? $dado['estabelecimento'] : 'Todos' }} - TITULOS FATURADOS TOTAL -  {{  $dado['representante']   }}" data-titulo_id="{{ $dado['titulo_id'] }}" data-filter="{{ $dado['filter'] }}" >{{ $dado['total'] }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <td>Total</td>
                <td>
                    <a href="#" class="modal-titulos-aberto-representante-total" data-route="{{ route('analise_atrasos_equipe.modal.titulo.representantes') }}" data-abertura='aberto' data-total='{{ $saidatotal }}' data-representante='false' data-title-modal="TODOS - TITULOS FATURADOS EM ABERTO - TODOS" data-titulo_id="" data-filter="{{ $total['filter'] }}">{{ $total['aberto'] }}</a>
                </td>
                <td>
                    <a href="#" class="modal-titulos-vencido-representante-total" data-route="{{ route('analise_atrasos_equipe.modal.titulo.representantes') }}" data-abertura='vencido' data-total='{{ $saidatotal }}' data-representante='false' data-title-modal="TODOS - TITULOS FATURADOS VENCIDOS - TODOS" data-titulo_id="" data-filter="{{ $total['filter'] }}">{{ $total['vencido'] }}</a>
                </td>
                <td>
                    <a href="#" class="modal-titulos-geral-representante-total" data-route="{{ route('analise_atrasos_equipe.modal.titulo.representantes') }}" data-abertura='total' data-total='{{ $saidatotal }}' data-representante='false' data-title-modal="TODOS - TITULOS FATURADOS TOTAL - TODOS" data-titulo_id="" data-filter="{{ $total['filter'] }}">{{ $total['total'] }}</a>
                </td>
            </tfoot>  
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_clientes_atrasos_representante = $('#table-clientes-atrasos')
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
                        if(column === 6 || column === 7 || column === 8 || column === 9 || column === 10){
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
        },
    ]
    });

    table_clientes_atrasos_representante.on('draw', function () {
        $(document).find(".modal-titulos-atrasos").off("click");
        $(document).find(".modal-titulos-atrasos").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
        $(document).find(".modal-titulos-aberto-representante").off("click");
        $(document).find(".modal-titulos-aberto-representante").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
        $(document).find(".modal-titulos-vencido-representante").off("click");
        $(document).find(".modal-titulos-vencido-representante").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
        $(document).find(".modal-titulos-total-representante").off("click");
        $(document).find(".modal-titulos-total-representante").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
        $(document).find(".modal-titulos-aberto-representante-total").off("click");
        $(document).find(".modal-titulos-aberto-representante-total").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
        $(document).find(".modal-titulos-vencido-representante-total").off("click");
        $(document).find(".modal-titulos-vencido-representante-total").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
        $(document).find(".modal-titulos-geral-representante-total").off("click");
        $(document).find(".modal-titulos-geral-representante-total").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulos($(this));
        });
    });

    table_clientes_atrasos_representante.draw();
});

function showModalOpenTitulos($this){
        var $url = $($this).data("route");
        var $filter = $($this).data("filter");
        var $title = $($this).data("title-modal");
        var $titulo_id = $($this).data("titulo_id");
        var $abertura = $($this).data("abertura");
        var $total = $($this).data("total");
        var $representante = $($this).data("representante");
        $.ajax({
            url: $url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", filters: $filter, titulo_id : $titulo_id, abertura : $abertura, total : $total , representante : $representante},
            success: function(body){
                createModal("analise_atrasos_equipe_representante", $title, body, 'modal-lg');
            }
        });
    }
</script>
@endsection        
