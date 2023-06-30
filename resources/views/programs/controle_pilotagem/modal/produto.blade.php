@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-view-produto">
            <thead>
                <tr>
                    <th>Nota</th>
                    <th>Cliente</th>
                    <th>Representante</th>
                    <th>Gerente</th>
                    <th>Produto</th>
                    <th>Itens Enviados</th>
                    <th>Itens Vendidos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td>
                        @if(!empty($dado['id_nota']))
                            <a href="#" class="bt-produto-acumulado" data-route="{{ route('controle_pilotagem.modal.produto') }}" onclick="showNotasDetalhesNasajon('{{ $dado['id_nota'] }}')">{{ $dado['documento'] }}</a>
                        @else
                            {{ $dado['documento'] }}
                        @endif
                    </td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['cliente'] }}">{{ $dado['cliente'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['representante'] }}">{{ $dado['representante'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['gerente'] }}">{{ $dado['gerente'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['produto'] }}">{{ $dado['produto'] }}</div></div></td>
                    <td>{{ $dado['quantidade_produto'] }}</td>
                    <td>{{ $dado['quantidade_vendas'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total:</td>
                    <td>{{ $total['quantidade_produtos'] }}</td>
                    <td>{{ $total['quantidade_vendas'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_filters_produto = $('#table-view-produto')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 15,
        "autoWidth": false,
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
                "targets": [0,5,6],
            },
            {
                "width": "5%", 
                "targets": [0,5,6]
            },
            {
                "width": "15%", 
                "targets": [2,3]
            },
        ],
    });    
    table_filters_produto.draw();
});

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