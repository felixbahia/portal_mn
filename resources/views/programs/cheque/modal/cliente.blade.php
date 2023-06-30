@extends("layouts.page-dialog")

@section("content")
<div class="content-dialog-table">
    <table class="table table-striped table-not-view" id="table-cheque-dialog">
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="tb_number">Banco</th>
                <th class="tb_number">Agência</th>
                <th class="tb_number">Conta</th>
                <th class="tb_number">Número do lançamento</th>
                <th class="tb_number">Saldo</th>
                <th class="tb_number">Valor</th>
                <th class="date_format">Bom para</th>
                <th>Títulos vinculados</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($cheques as $cheque)
        <tr>
            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $cheque["cliente"] }}">{{ $cheque["cliente"] }}</div></div></td>
            <td>{{ $cheque["banco"] }}</td>
            <td>{{ $cheque["agencia"] }}</td>
            <td>{{ $cheque["conta"] }}</td>
            <td>{{ $cheque["numero"] }}</td>
            <td data-order="{{ $cheque["saldo_sem_formatacao"] }}">{{ $cheque["saldo"] }}</td>
            <td data-order="{{ $cheque["valor_sem_formatacao"] }}">{{ $cheque["valor"] }}</td>
            <td class="date_format" data-order="{{ $cheque["bom_para_sem_formatacao"] }}">{{ $cheque["bom_para"] }}</td>
            <td>{!! $cheque['vinculados'] !!}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>{{ $total["saldo"] }}</td>
                <td>{{ $total["valor"] }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
    
<script>
	
	table_filters_itens = $(document).find("#table-cheque-dialog").DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"paging": false,
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
				"targets": "tb_number"
			},
		],
    });

    function showInfoTituloPrePago($id){
        var title = "Detalhes do título";
        $.ajax({
            url: '{{ route('titulos_prepago.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id
            },
            success: function(body){
                createModal("titulo-detalhes-modal", title, body, '');
            }
        });
    }
    
</script>
@endsection
