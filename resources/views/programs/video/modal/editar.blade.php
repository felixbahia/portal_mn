@extends('layouts.page-dialog')
@section('content')
<form action="#" method="post" id="form_editar" name="form_editar" class="form_editar" onsubmit="return false">
	@csrf
	{{ Form::hidden('id',$dados['id'],['id' => 'id']) }}
	<div class="form-row">
		<div class="form-group col-sm-12">
			{{ Form::label('descricao', 'Titulo', []) }}
			{{ Form::text('descricao', $dados['descricao'], ['class' => 'form-control', 'id' => 'descricao', 'maxlength' => '100']) }}

		</div>
		<div class="form-group col-sm-12">
			{{ Form::label('modulo', 'Modulo', []) }}
			{!! Form::select('modulos', $modulos, $dados['modulos_id'], ['id' => 'modulos', 'class' => 'form-control', 'placeholder' => 'Todos']) !!}
		</div>
	</div>
	<div class="form-row">
		<div class="form-group col-sm-12">
			{{ Form::label('arquivo', 'Arquivo') }}
            {!! Form::file('arquivo[]', ['id'=>'arquivo', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
			<a href='{{ $dados['link_arquivo'] }}' target="blank" title="Documento HTML"><i class="btn-download thumb ml-2 mt-1"></i> {{ $dados['nome_arquivo'] }}</a>
		</div>
	</div>
	<div class="form-row">
		<div class="form-group col-sm-12">
			{{ Form::label('', 'Adicionar Perfil') }}
			<div class="input-group">
				{{ Form::text('perfil', '', ['id' => 'perfil', 'class' => 'form-control', 'disabled' => 'disabled']) }}
				{{ Form::hidden('perfil_id', '', ['id' => 'perfil_id'])}}
				<span class="input-group-addon border" id="bt-search-perfil"><i class="bt-view m-2"></i></span>
				<div class="input-group-append" id="success_button_cliente">
					<button class="input-group-addon btn btn-success" id="add-perfil-button">+</button>
				</div>
			</div>
		</div>
	</div>
	<div class="form-row add-clientes">
		<div class="col-sm-12 table-container">
			<table class="table table-striped content-dialog-table" id="table-modal-editar">
				<thead>
					<tr>
						<th>Perfil</th>
						<th class="display_none"></th>
						<th>Excluir</th>
					</tr>
				</thead>
				<tbody>
					@foreach($dados['perfil'] as $perfil)
						<tr>
							<td>{{ $perfil['nome'] }}</td>
							<td>{{ $perfil['id'] }}</td>
							<td><a href='#' class="bt-delete" data-toggle="popover" data-trigger='hover' title="Apagar" onclick="deletarLinha(this)"></a></td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-12 mt-4">
			{{ Form::submit('Salvar', array('id' => 'editar', 'class' => 'btn btn-primary float-right')) }}
		</div>
	</div>
</form>
<script>
	$(document).ready( function(){
		form_modal_editar = $(document).find('#form_editar');
		form_modal_editar.find('#table-modal-editar').DataTable(table_editar_perfil).draw();

		form_modal_editar.find("#editar").on("click", function(){
			editar(form_modal_editar);
		});

		form_modal_editar.find("#add-perfil-button").on("click", function(){
			adicionarPerfil(form_modal_editar);
		});

		form_modal_editar.find("#bt-search-perfil").on("click", function(){
			showModalPerfil();
		});
	});

	function adicionarPerfil(form_modal_editar){
		if($(form_modal_editar).find('#perfil').val() ==  ''){
			message('Atenção','Nenhum tipo de usuario selecionado','error');
			return false;
		}
		
		if($(form_modal_editar).find("#table-modal-editar").find("td").filter(function() { return $(this).text() == $(form_modal_editar).find('#perfil').val(); }).length == 0 ){
			nova_linha = [
				$(form_modal_editar).find('#perfil').val(),
				$(form_modal_editar).find('#perfil_id').val(),
				botaoExcluirLinha()
			];
			$(form_modal_editar).find("#table-modal-editar").DataTable().row.add(nova_linha).draw();
			$(form_modal_editar).find('#perfil').val('');
			$(form_modal_editar).find('#perfil_id').val('');

		}else{
			form_modal_editar.find('#table-modal-editar').eq(0).parent().append('<label class="error-message error-">Já adicionado</label>'); 
			form_modal_editar.find('#table-modal-editar').eq(0).addClass('error');
		}
	}

	function showModalPerfil(){
		$.ajax({
			url: '{{ route('perfil.modal.busca') }}',
			method: 'GET',
			success: function(body){
				createModal("perfil_busca_modal", "Perfil", body, 'modal-lg');
				$(document).ready( function () {
					table_dialog_perfis.on('draw', function () {
						$(document).find("#perfil_busca_modal").find('tbody').find("tr").off("click");
						$(document).find("#perfil_busca_modal").find('tbody').find("tr").on("click", function(event){
							selecionaPerfil($(this), event);
						});
					});
					$(document).find("#perfil_busca_modal").find(".bt-selected").on("click", function(){
						selecionaPerfil($(this));
					});
				});
			}
		});
	}

	function selecionaPerfil($dados){
		if($dados.find("td").eq(0).hasClass('dataTables_empty')){
			return false;
		}
		$(document).find("#perfil_busca_modal").modal("hide");
		$(document).find("#perfil").val($dados.find("td").eq(0).text());
		$(document).find("#perfil_id").val($dados.find("td").eq(1).text());
	}

	function botaoExcluirLinha(){
		var html = "<a href='#' class=\"bt-delete\" data-toggle=\"popover\" data-trigger='hover' title=\"Apagar\" onclick=\"deletarLinha(this)\"></a>";
	return html;
	}

	function deletarLinha(element){
		$(element).parents('table').DataTable().row( $(element).parents('tr') ).remove().draw();
	}

	table_editar_perfil = {
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		'paging': false,
		"orderMulti": false,
		"scrollY": "20vh",
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
				"targets": -1,
				"orderable": false,
			},
			{
				"targets": [ 'display_none' ],
				"class": "display_none"
			},
		],
		"order": [[ 0, 'asc' ]]
	};

	function editar($form){
		var vencimentos = [];
		var perfil = [];
		var data = new FormData($form[0]);
		$($form).find("#table-modal-editar > tbody").find("tr").each( function(){
			if (!$(this).find("td:eq(0)").hasClass('dataTables_empty')){
				perfil.push($(this).find("td:eq(1)").text());
			}
		});
		if(perfil.length  > 0){
			$.each(perfil, function(k, value){
				data.append('perfils[]', value);
			});
		}

		$('label.error-message').remove();

		$.ajax({
			url: '{{ route('video.editar')}}',
			type: 'POST',
			data: data,
			processData: false,
			contentType: false,
			success: function(data){
				if(data.status === 'success'){
					filtro($(document).find("#form_editar").serialize());
					
					$(document).find('#modal_edit_video').modal('hide');
				}else if(data.status === 'error'){
					message('Atenção',data.message,'error');
				}   
			},
			error: function(callback){
				var retorno = callback.responseJSON.error;
				$.each(retorno, function(index, el) {
					if(index != 'perfils'){
						$form.find('#'+index).eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
						$form.find('#'+index).eq(0).addClass('error');
					}else{
						$form.find('#table-modal-editar').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
						$form.find('#table-modal-editar').eq(0).addClass('error');
					}
				});
				$form.find('input.error').eq(0).focus();
			}
		});
	}

</script>
@endsection
