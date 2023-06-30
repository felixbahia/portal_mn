@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter" id="table-titulos-historico">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th>Titulo</th>
                <th>Status</th>
                <th data-sort='YYYYMMDD' class="sort-date">Data de Emissão</th>
                <th data-sort='YYYYMMDD' class="sort-date">Data de Vencimento</th>
                <th class="tb_number">Valor Original</th>
                <th class="tb_number">Valor (Saldo)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($titulos as $titulo)
            <tr>
                <td>{!! $titulo['estabelecimento'] !!}</td>
                <td>{!! $titulo['cliente'] !!}</td>
                <td>{!! $titulo['titulo'] !!}</td>
                <td>{!! $titulo['status'] !!}</td>
                <td>{!! $titulo['data_emissao'] !!}</td>
                <td>{!! $titulo['data_vencimento'] !!}</td>
                <td>{!! $titulo['valor_original'] !!}</td>
                <td>{!! $titulo['valor_saldo'] !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script>
    table_historico_titulos_opt = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": -1,
        "processing": true,
        "orderMulti": false,
        "autoWidth": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
			{ "class": "tb_number", "type": 'num-fmt', "targets": "tb_number" },
			{ "class": "tb_date", "type": "date", "targets": "sort-date" },
            { "targets": [0, 2, 3, 4, 5, 6], 'width': '150px' },
            { "targets": [1], 'width': '200px' },
		],
		"order": [1, 'desc']
    };
    setTimeout(function(){
        table_historico_titulo = $(document).find("#table-titulos-historico").DataTable(table_historico_titulos_opt);
        table_historico_titulo.on('draw', function(){
            $(document).find('[data-toggle="tooltip"]').tooltip();
            $(document).find('[data-toggle="popover"]').popover();
        });
        $(document).find('[data-toggle="tooltip"]').tooltip();
        $(document).find('[data-toggle="popover"]').popover();
    }, 300);
</script>
@endsection
