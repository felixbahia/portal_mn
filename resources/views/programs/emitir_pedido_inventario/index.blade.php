@extends('layouts.app')

@section('content-filter')
	<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
		@csrf
		<h3>Listagem de {{ CustomView::programaName() }}</h3>
		<div class="content-fields">
			<div class="col-lg-2">
				{!! Form::select('estabelecimento', returnEmpresasNasajonView(),'', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Todos os estabelecimentos']) !!}
			</div>
		</div>
		<div class="content-buttons">
			<button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
			<input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
		</div>
	</form>
@endsection
@section('content')
	<div class="content-table">
		<table class="table table-striped table-not-edit table-not-view" id="table-filters">
			<thead>
				<tr>
					<th>Estabelecimento</th>
					<th class="tb_number">Quantidade Produtos</th>
					<th class="tb_acao">Gerar Pedido</th>
					<th class="tb_acao">Separar Pedido</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
@endsection
@section('script-footer')
	$(document).ready( function () {
		table_filters.destroy();
		table_filters = $('#table-filters').DataTable({
			"searching": false,
			"lengthChange": false,
			"info": false,
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
				{ "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
				{ "orderable": false, targets: "tb_acao" },
			],
			"order": [[ 0, 'asc' ]]
		});
		$("#btn-filterform").on("click", function(){
			buscaDados($("#form_filter").serialize());
		});
		buscaDados($("#form_filter").serialize());
	});

	function buscaDados(data_form){
		table_filters.clear().draw();
		$.ajax({
			url: '{{ route('emitir_pedido_inventario.filtro') }}',
			type: 'POST',
			dataType: 'json',
			data: data_form,
			success: function(callback){
				if(callback.status === 'success'){
					var dados = callback.response;
					table_filters.clear().draw();
					var lines = [];
					for(var field in dados){
						var temp_field = [
							dados[field].estabelecimento,
							dados[field].quantidade,
                            botaoGerarPedido(dados[field]),
							''
						];
						lines.push(temp_field);
					}
					table_filters.rows.add(lines).draw().nodes();
				}
			}
		}).always(function() {
			hide_loader();
		});
	}

	function botaoGerarPedido(campos){
		var $html = '';
		$html = '<a href="#"class="bt-view" onclick="modalGerarPedido(\''+campos.criterios+'\')" data-toggle="tooltip" data-trigger="hover" title="Gerar Pedido"></a>';
		return $html;
	}

	function modalGerarPedido($criterios){
		var title = "Emitir Pedido Pelo Inventário";
		$.ajax({
			url: '{{ route('emitir_pedido_inventario.modal.gerar_pedido') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				criterios: $criterios
			},
			success: function(body){
				createModal('modal_geracao_pedidos',  title, body, 'modal-md');
			},
            error: function(callback){
                if(callback.status === 422){
                    if(callback.responseJSON.error){
                        message("Alerta", callback.responseJSON.message);
                    }else{
                        message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                    }
                }
            },
		});
	}
@endsection
