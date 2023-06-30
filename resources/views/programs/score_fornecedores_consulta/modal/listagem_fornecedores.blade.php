@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_analise_clientes_pesquisa_satisfacao">
            <thead>
            <tr>
                <th class="fornecedor">Fornecedor</th>
                <th class="tb_date">Data</th>
                <th>Estado</th>
                <th>Regime de Tributação</th>
                <th>Nota</th>
                <th>Formulario</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['fornecedor'] }}">{{ $item['fornecedor'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['data'] }}">{{ $item['data'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['estado'] }}">{{ $item['estado'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['regime_trinutário'] }}">{{ $item['regime_trinutário'] }}</div></div></td>
                    <td>
                        @if(!empty($item['id_nota']))
                            <a href='#' onclick="showNotasDetalhes('{{ $item['id_nota'] }}')">{{ $item['nota'] }}</a>
                        @endif
                    </td>
                    <td>
                        @if(!empty($item['id_formulario']))
                            <a href='#' onclick="showNotasFormulario('{{ $item['id_formulario'] }}','{{ $item['fornecedor'] }}')"> <i class="bt-view"></i> </a>
                        @elseif(!empty($item['id_formulario_nota']))
                            <a href='#' onclick="showNotasFormularioNotas('{{ $item['id_formulario_nota'] }}','{{ $item['fornecedor'] }}')"> <i class="bt-view"></i> </a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_dialog_clientes = [];
    table_dialog_clientes = $('#table_analise_clientes_pesquisa_satisfacao')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "scrollY": "50vh",
            "scrollCollapse": true,
            "ordering": true,
            "autoWidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: 'Lista de Clientes de pesquisa de satisfação',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
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
                    width: "350px",
                    "targets": "fornecedor",
                },
                {
                    "class": "tb_date",
                    "targets": "tb_date",
                }
            ]
        });
    setTimeout(function(){
        table_dialog_clientes.draw();
    }, 200);
});

function showNotasFormulario($id_nota,$fornecedor){
    $.ajax({
        url: '{{ route('score_fornecedores_consulta.modal.formulario_respondido')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id_nota: $id_nota,
        },
        success: function(body){
            createModal("formulario", "Fornulário Respondido - "+$fornecedor, body, 'modal-lg');
        }

    });
}

function showNotasFormularioNotas($id_nota,$fornecedor){
    console.log();
    $.ajax({
        url: '{{ route('score_fornecedor_nota.modal.exibir_formulario')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id_nota: $id_nota,
        },
        success: function(body){
            createModal("formulario", "Fornulário Respondido - "+$fornecedor, body, 'modal-lg');
        }

    });
}

function showNotasDetalhes($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
</script>
@endsection
