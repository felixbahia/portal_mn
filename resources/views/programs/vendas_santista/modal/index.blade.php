@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filters-vendas-santista" id="table-filters-vendas-santista" style="width:100%">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th>Representante</th>
                <th class='number_format'>Série</th>
                <th class='number_format'>Nota</th>
                <th class='date_format'>Emissão</th>
                <th>Produto</th>
                <th class='number_format'>QTD</th>
                <th class='number_format'>Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados as $item)
            <tr>
                <td>{!! $item['estabelecimento'] !!}</td>
                <td>{!! $item['cliente'] !!}</td>
                <td>{!! $item['representante'] !!}</td>
                <td>{{ $item['serie'] }}</td>
                <td><a href='#' onclick="showNotasDetalhesNasajon('{{ $item['nota_id'] }}')">{{ $item['nota'] }}</a></td>
                <td>{{ $item['emissao'] }}</td>
                <td>{!! $item['produto'] !!}</td>
                <td>{{ $item['quantidade'] }}</td>
                <td>{{ $item['valor'] }}</td>
                <td>{!! $item['status'] !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>

    $(document).ready(function(){
        table_filters_vendas_santista.on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
        });

        setTimeout( function(){
            table_filters_vendas_santista.columns.adjust().draw();
        }, 500);
    });

    table_filters_vendas_santista = $(document).find('#table-filters-vendas-santista').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
                'targets': [3, 4, 5, 7, 8],
                'width': '5%'
            },
            {
                'targets': [0, 1, 2, 6],
                'width': '15%'
            },
            {
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
        ]
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