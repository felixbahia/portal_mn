@extends('layouts.app')

@section('content-filter')
	<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
		@csrf
		<h3>{{ CustomView::programaName() }}</h3>
		<div class="content-fields">
			<div class="col-lg-2">
				{{ Form::select('perfil', $perfis, '', ['id' => 'perfil_busca', 'class' => 'form-control', 'maxlength' => '40']) }}
			</div>
		</div>
		<div class="content-buttons">
			<button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
			<input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
			<button name="btn-create" id="btn-create" class="btn-create">Nova Campanha</button>
		</div>
	</form>
@endsection

@section('content')
	<div class="content-table">
		<table class="table table-striped" id="table-campanha">
			<thead>
				<tr>
					<th>Título</th>
					<th class="td_html">Perfil</th>
					<th class="td_acao"></th>
					<th class="td_acao"></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
@endsection

@section('script-footer')
	$(document).ready(function(){
		table_filters = $('#table-campanha').DataTable({
			"searching": false,
			"lengthChange": false,
			"info": false,
			"pageLength": 15,
			"autoWidth": false,
			"orderMulti": false,
			"language": {
				"decimal": ",",
				"emptyTable": "Nenhum registro encontrado",
				"infoPostFix": "",
				"thousands": ".",
				"loadingRecords": "Carregando...",
				"processing": "Processando...",
				"zeroRecords": "Nenhum registro encontrado",
				"paginate": {
					"first":	"<<",
					"last":		">>",
					"next":		">",
					"previous":	"<"
				}
			},
			"columnDefs": [
				{
					"targets": "td_html",
					"type": "html"
				},
				{
					"targets": "td_acao",
					"class": "td_acao",
					"width": "5px",
					"orderable": false
				},
			],
			"order": [[ 1, "asc" ]]
		}).on('draw', function(){
			$(document).find(".bt-edit").off("click");
			$(document).find(".bt-edit").on("click", function(event){
				event.stopPropagation();
				modalEditarMensagem($(this).data('id'));
			});

			$(document).find('[data-toggle="tooltip"]').tooltip();
			
			$(document).find(".bt-delete").off("click");
			$(document).find(".bt-delete").on("click", function(event) {
				event.stopPropagation();
				excluir($(this).data('id'));
			});
		}).draw();

		$(document).find("#btn-filterform").on("click", function(){
			filter($(document).find("#form_filter").serialize());
		});
		$("#btn-create").on("click", function(){
			modalNovaMenmsagem();
		});

	});

	function filter(data_form){
		var $return;
		var $form = $("#form_filter");
		table_filters.clear().draw();

		$form.find('.error-message').remove();
		
		$.ajax({
			url: "{{ route('politica.cadastro.filtro') }}",
			dataType: 'json',
			data: data_form,
			method: 'POST',
			success: function(data){
				var dados = data.response.response;
				if(dados.length > 0){
					var fields_filter = [];
					for(var field in dados){
						var temp_field = [
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + dados[field].descricao + "''>" +dados[field].descricao+ "</div></div>",
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + dados[field].perfil+ "''>" +dados[field].perfil+ "</div></div>",
							createBtEdit(dados[field]),
							createBtDelete(dados[field])
						];
						fields_filter.push(temp_field);
					}
					table_filters.rows.add(fields_filter).draw();
				}
			},
			error: function(callback){
				var data = callback.responseJSON.error;
				$.each(data, function(index, el) {
					$(document).find('#form_filter').find('#'+index).eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
					$(document).find('#form_filter').find('#'+index).eq(0).addClass('error');
				});
				$form.find('input.error').eq(0).focus();
			}
		});
	}

	function createBtEdit($this){
		var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"popover\" data-trigger='hover' title=\"Editar\"></a>";
		return html;
	}

	function createBtDelete($this){
		var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-delete  deletaBtn\" data-toggle=\"popover\" data-trigger='hover' title=\"Excluir\" onclick=\"deletarLinha(this)\"></a>";

		return html;
	}

	function deletarLinha(element){
		$(element).parents('table').DataTable().row( $(element).parents('tr') ).remove().draw();
	}

	function modalNovaMenmsagem(){
		$.ajax({
			url: '{{ Route('politica.cadastro.modal.adicionar') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}'
			},
			success: function(data){
				createModal("modal_nova_politica", "Criar Nova", data,"");
			}
		});
	}

	function modalEditarMensagem($id){
		$.ajax({
			url: '{{ Route('politica.cadastro.modal.editar') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				id: $id
			},
			success: function(data){
				createModal("modal_editar_politica", "Editar", data,"");
			}
		});
	}

	function excluir($id){
		$.ajax({
			url: '{{ route('politica.cadastro.excluir') }}',
			type: 'POST',
			data: {_token: '{{ csrf_token() }}',
				id: $id},
			success: function(data){
				message("Atenção", "Politica excluída com sucesso!");
			}
		});		
	}

@endsection
