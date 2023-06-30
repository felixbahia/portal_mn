@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
	<div class="content-table">
	    <table class="table table-striped" id="table-dialog-historico_aprovacao">
	        <thead>
	            <tr>
	                <th>Data</th>
	                <th>Status</th>
	            </tr>
	        </thead>
	        <tbody>
                @foreach($historicos as $historico)
                    <tr>
                        <td class="tb_date">{{$historico['data']}}</td>
                        <td>{{$historico['status']}}</td>
                    </tr>
                @endforeach
	        </tbody>
	    </table>
	</div>
</div>
<script>
    table_histrorico_dolar = $(document).find('#table-dialog-historico_aprovacao').DataTable({
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