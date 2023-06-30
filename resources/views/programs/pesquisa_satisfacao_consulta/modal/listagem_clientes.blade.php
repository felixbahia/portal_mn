@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_analise_clientes_pesquisa_satisfacao">
            <thead>
            <tr>
                <th class="estabelecimento">Estabelecimento</th>
                <th class="espaco">Cliente</th>
                <th>Gerente</th>
                <th>Vendedor</th>
                <th>Data</th>
                <th class="espaco">E-mail</th>
                <th class="comentario">Comentário</th>
                <th class="nota">Notas</th>
                <th>Formulario</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['estabelecimento'] }}">{{ $item['estabelecimento'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['cliente'] }}">{{ $item['cliente'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['gerente'] }}">{{ $item['gerente'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="right" title="" data-original-title="{{ $item['vendedor'] }}">{{ $item['vendedor'] }}</div></div></td>
                    <td class="tb_date"><div><div data-toggle="tooltip" data-placement="left" data-html="true" title="" data-original-title="{{ $item['data'] }}">{{ $item['data'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="left" title="" data-original-title="{{ $item['email'] }}">{{ $item['email'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" data-placement="bottom" title="" data-original-title="{{ $item['comentario'] }}">{{ $item['comentario'] }}</div></div></td>
                    <td>
                        @foreach($item['notas_array'] as $nota)
                            <a href='#' onclick="showNotasDetalhesNasajon('{{ $nota['id'] }}')"> {{ $nota['numero'] }} </a> @if(next($item['notas_array'])) / @endif
                        @endforeach
                    </td>
                    <td>
                        @if(!empty($item['data']))
                            <a href="#" data-route="{{ route('pesquisa_satisfacao_consulta.modal.abertura_formulario') }}" data-id="{{ $item['id'] }}" data-title='Formulário Respondido Cliente - {{ $item['cliente'] }} - Nota(s) - {{ $item['notas'] }}' class='bt-view bt-view-formulario'></a>
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
                    width: "100px",
                    "targets": "estabelecimento",
                },
                {
                    "class": "tb_date",
                    "targets": "tb_date",
                },
                {
                    targets: "espaco",
                    width: '200px'
                },
                {
                    targets: "comentario",
                    width: '400px'
                },
                {
                    targets: "nota",
                    width: '100px'
                }
            ]
        });
    table_dialog_clientes.on('draw', function () {
        $(document).find(".bt-view-formulario").off("click");
        $(document).find(".bt-view-formulario").on("click", function(event){
            event.stopPropagation();
            showModalFormulario($(this));
        });
    });
    setTimeout(function(){
        table_dialog_clientes.draw();
    }, 200);
});
function showModalFormulario($this){
    var url = $($this).data("route");
    var id = $($this).data("id");
    var title = $($this).data('title');

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", id : id},
        method: 'POST',
        success: function(body){
            createModal("modal_formulario_pesquisa_cliente", title, body, 'modal-lg');
        }
    });
}

function showNotasDetalhesNasajon($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
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
