@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
	<div class="content-table">
	    <table class="table table-striped" id="table-filters-produtos-busca">
	        <thead>
	            <tr>
	                <th>Atualizado Em</th>
	                <th>Valor</th>
	            </tr>
	        </thead>
	        <tbody>
                @foreach($valores_dolar as $valor)
                    <tr>
                        <td class="tb_date">{{$valor['data']}}</td>
                        <td class="tb_number">{{$valor['valor_dolar']}}</td>
                    </tr>
                @endforeach
	        </tbody>
	    </table>
	</div>
</div>
<script>
    table_histrorico_dolar = $(document).find('#table-filters-produtos-busca').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
            ],
        },
	});
</script>
@endsection