@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-historico-modal">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Cliente</th>
                    <th>Contato</th>
                    <th class='tb_number'>Título</th>
                    <th class='tb_number'>Valor cobrado</th>
                    <th class='tb_number'>Valor recuperado</th>
                    <th class='tb_number'>% de sucesso</th>
                    <th>Vendedor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($retorno as $dado)
                <tr>
                    <td>{{ $dado['usuario'] }} </td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dado['cliente'] }}'>{{ $dado['cliente'] }}</div></div> </td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dado['contato'] }}'>{{ $dado['contato'] }}</div></div> </td>
                    <td><a href="#" onclick="tituloDetalhes('{{ $dado['titulo_id'] }}')">{{ $dado['titulo_numero'] }}</a></td>
                    <td>{{ $dado['valor_cobrado'] }} </td>
                    <td>{{ $dado['valor_recuperado'] }} </td>
                    <td>{{ $dado['sucesso'] }} </td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dado['vendedor'] }}'>{{ $dado['vendedor'] }}</div></div> </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><b>Totais:</b></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><b>{{ $totais['valor_cobrado'] }}</b></td>
                    <td><b>{{ $totais['valor_recuperado'] }}</b></td>
                    <td><b>{{ $totais['sucesso'] }}</b></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
    table_filters_analitic_atraso = $('#table-historico-modal').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
        "autoWidth": false,
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
                "class": "tb_number", 
                "targets": 'tb_number',
                'width': '15%'
            },
        ],
    });

    function tituloDetalhes($id){
        $.ajax({
            data: {
                id: $id,
                _token: '{{ csrf_token() }}',
            },
            method: 'POST',
            url: "{{ route('historico_cobranca.modal.titulo') }}",
            success: function(data){
                createModal('modal-titulo', 'Detalhes do título', data, '');
            }
        });

        console.log('Ue');
    }
</script>
@endsection