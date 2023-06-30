@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-dialog footer-pequeno" id="table-fornecedores-atrasos">
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th>Aberto</th>
                    <th>Vencido</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($retorno as $dado)
                    <tr>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['fornecedor'] }}">{{ $dado['fornecedor'] }}</div></div></td>
                        <td>
                            <a href="#" class="modal-titulos-fornecedor-aberto" data-route="{{ route('titulos_apagar.modal.titulos') }}" data-fornecedor='true' data-abertura-geral='{{ $aberturageral }}' data-abertura='aberto' data-total='false' data-busca='{{ $dado["fornecedor"] }}' data-title-modal="{{  ($aberturageral == 'false') ? $dado['estabelecimento'] : 'TODOS' }} - TITULOS FATURADOS VENCIDOS -  {{  $dado['fornecedor']   }}" data-filter="{{ $dado['filter'] }}" >{{ $dado['aberto'] }}</a>
                        </td>
                        <td>
                            <a href="#" class="modal-titulos-fornecedor-vencido" data-route="{{ route('titulos_apagar.modal.titulos') }}" data-fornecedor='true' data-abertura-geral='{{ $aberturageral }}' data-abertura='vencido' data-total='false' data-busca='{{ $dado["fornecedor"] }}' data-title-modal="{{  ($aberturageral == 'false') ? $dado['estabelecimento'] : 'TODOS' }} - TITULOS FATURADOS VENCIDOS -  {{  $dado['fornecedor']   }}" data-filter="{{ $dado['filter'] }}" >{{ $dado['vencido'] }}</a>
                        </td>
                        <td>
                            <a href="#" class="modal-titulos-fornecedor-total" data-route="{{ route('titulos_apagar.modal.titulos') }}" data-fornecedor='true' data-abertura-geral='{{ $aberturageral }}' data-abertura='total' data-total='false' data-busca='{{ $dado["fornecedor"] }}' data-title-modal="{{  ($aberturageral == 'false') ? $dado['estabelecimento'] : 'TODOS' }} - TITULOS FATURADOS TOTAL -  {{  $dado['fornecedor']   }}" data-filter="{{ $dado['filter'] }}" >{{ $dado['total'] }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <td>Total</td>
                <td>
                    <a href="#" class="modal-titulos-fornecedor-aberto-total" data-route="{{ route('titulos_apagar.modal.titulos') }}" data-fornecedor='true' data-abertura-geral='{{ $aberturageral }}' data-abertura='aberto' data-total='true' data-busca='' data-title-modal="TODOS - TITULOS FATURADOS ABERTOS" data-filter="{{ $dado['filter'] }}" >{{ $total['aberto'] }}</a>
                </td>
                <td>
                    <a href="#" class="modal-titulos-fornecedor-vencido-total" data-route="{{ route('titulos_apagar.modal.titulos') }}" data-abertura='vencido'  data-abertura-geral='{{ $aberturageral }}' data-fornecedor='true' data-total='true' data-busca='' data-title-modal="TODOS - TITULOS FATURADOS VENCIDOS" data-filter="{{ $dado['filter'] }}" >{{ $total['vencido'] }}</a>
                </td>
                <td>
                    <a href="#" class="modal-titulos-fornecedor-geral-total" data-route="{{ route('titulos_apagar.modal.titulos') }}" data-fornecedor='true'  data-abertura-geral='{{ $aberturageral }}' data-abertura='total' data-total='true' data-busca='' data-title-modal="TODOS - TITULOS FATURADOS TOTAL" data-filter="{{ $dado['filter'] }}" >{{ $total['total'] }}</a>
                </td>
            </tfoot>  
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_fornecedores_atrasos = $('#table-fornecedores-atrasos')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
    }).DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "orderMulti": true,
    "paging": true,
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
            "targets": [1,2,3]
        },
    ],
    "order": [[1,'desc'],],
    });

    table_fornecedores_atrasos.on('draw', function () {
        $(document).find(".modal-titulos-atrasos").off("click");
        $(document).find(".modal-titulos-atrasos").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
        $(document).find(".modal-titulos-fornecedor-aberto").off("click");
        $(document).find(".modal-titulos-fornecedor-aberto").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
        $(document).find(".modal-titulos-fornecedor-vencido").off("click");
        $(document).find(".modal-titulos-fornecedor-vencido").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
        $(document).find(".modal-titulos-fornecedor-total").off("click");
        $(document).find(".modal-titulos-fornecedor-total").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
        $(document).find(".modal-titulos-fornecedor-aberto-total").off("click");
        $(document).find(".modal-titulos-fornecedor-aberto-total").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
        $(document).find(".modal-titulos-fornecedor-vencido-total").off("click");
        $(document).find(".modal-titulos-fornecedor-vencido-total").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
        $(document).find(".modal-titulos-fornecedor-geral-total").off("click");
        $(document).find(".modal-titulos-fornecedor-geral-total").on("click", function(event){
            event.stopPropagation();
            showModalOpen($(this));
        });
    });

    table_fornecedores_atrasos.draw();
});

function showModalOpen($this){
    var $url = $($this).data("route");
    var $filter = $($this).data("filter");
    var $title = $($this).data("title-modal");
    var $abertura = $($this).data("abertura");
    var $total = $($this).data("total");
    var $fornecedor = $($this).data("fornecedor");
    var $busca = $($this).data("busca");
    var $aberturageral = $($this).data("abertura-geral");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", filters: $filter, abertura : $abertura, total : $total, fornecedor : $fornecedor,  busca : $busca, aberturageral : $aberturageral},
        success: function(body){
            createModal("analise_atrasos_equipe_fornecedor", $title, body, 'modal-lg');
        }
    });
}
</script>
@endsection        
