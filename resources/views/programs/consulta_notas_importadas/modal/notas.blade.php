@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_analise_artigos_abertura">
            <thead>
                <tr>
                    <th class="arquivos">Nota</th>
                    <th>Fornecedor</th>
                    <th>Cliente</th>
                    <th>Emissão</th>
                    <th>Natureza de Operação</th>
                    <th>Valor</th>
                    <th>Pedido</th>
                    <th>Compra Confirmada</th>
                    <th>Lançada</th>
                    <th>Tipo de Frete</th>
                    <th>Processo Devolução</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td class="arquivos">
                        @if($item['tipo'] == 'nfe')
                            <a href='#' onclick="mostrarNota('{{ $item['id'] }}')">{{ $item['nota'] }}</a>
                        @else
                            <a href='#' onclick="showCteDetalhesNasajon('{{ $item['id'] }}')">{{ $item['nota'] }}</a>
                        @endif
                        <a href='#' onclick="abreXML('{{ $item['id'] }}')"><i class='btn-icon-xml' data-toggle="tooltip" data-placement="right" title="" data-original-title="XML"></i></a>
                        <a href='#' onclick="abrePDF('{{ $item['id'] }}')"> <i class="btn-pdf" data-toggle="tooltip" data-placement="right" title="" data-original-title="PDF"></i></a>
                    </td>
                    <td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $item['fornecedor'] }}">{{ $item['fornecedor'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $item['destinatario'] }}">{{ $item['destinatario'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $item['data_emissao'] }}">{{ $item['data_emissao'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $item['natureza'] }}">{{ $item['natureza'] }}</div></div></td>
                    <td>{{ $item['valor'] }}</td>
                    <td><a href='#' onclick="showPedidos('{{ $item['estabelecimento'] }}','{{ $item['nome_transportadora'] }}')">{{ $item['pedido_compra'] }}</a></td>
                    <td>{{ $item['compraconfirmada'] }}</td>
                    <td>{{ $item['lancada'] }}</td>
                    <td>{{ $item['tipo_frete'] }}</td>
                    <td>{{ $item['devolucao'] }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ $total['valor'] }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_dialog_artigos = [];
    table_dialog_artigos = $('#table_analise_artigos_abertura')
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
        "scrollY": "70vh",
        "scrollCollapse": true,
        "ordering": true,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Notas Importadas',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 5){
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
                "targets": [5,6],
            },
            {
                "width": "20%",
                "targets": [1,2],
            },
            {
                "width": "8%",
                "targets": "arquivos",
            },
            {
                "width": "15%",
                "targets": [4],
            },
            {
                "class": "text_date", 
                "targets": [3],
            },
        ]
    });
    table_dialog_artigos.on('draw', function () {
        $(document).find(".bt-open-view-notas").off("click");
        $(document).find(".bt-open-view-notas").on("click", function(event){
            event.stopPropagation();
            showNotasDetalhesNasajon($(this));
        });
    });
    $('[data-toggle="tooltip"]').tooltip();
    setTimeout(function(){
        table_dialog_artigos.draw();
    }, 200);
});

function mostrarNota($id){
    $.ajax({
        url: '{{ route('modal.notas.importadas')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id
        },
        success: function(body){
            createModal("nota_detalhes_importada_entrada", "Detalhes da nota", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })
        }
    });
}


function showCteDetalhesNasajon($id){
    $.ajax({
        url: '{{ route('notas_importadas.modal.notas.cte')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id,
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da CTE", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })

        }
    });
}

function showPedidos(estabelecimento,fornecedor){
    $.ajax({
        url: '{{ route('pedidos_compras.modal.exibirpedidos')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            estabelecimento: estabelecimento,
            fornecedor: fornecedor,
        },
        success: function(body){
            createModal("pedidos_nfe", "Pedidos em Aberto - "+fornecedor+" -", body, 'modal-lg');
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

function abreXML($id){
    var id = $id;
    $('<form action="{{ route('notas_importadas.modal.notas.download_xml') }}" method="POST" target="_blank">\
    <input type="hidden" name="_token" value="{{ csrf_token() }}">\
    <input type="hidden" name="id" value="'+id+'">\
    </form>').appendTo('body').submit().remove();
}

function abrePDF($id){
    $('<form action="{{ route('consulta_notas_importadas.pdf') }}" method="POST" target="_blank">\
        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
        <input type="hidden" name="id" value="'+$id+'">\
    </form>').appendTo('body').submit().remove();
        
}
</script>
@endsection
