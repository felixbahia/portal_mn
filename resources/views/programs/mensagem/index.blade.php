@extends('layouts.app')

@section('content-filter')
	<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
	    @csrf
	    <h3>Listagem de {{ CustomView::programaName() }}</h3>
	    <div class="content-fields">
            <div class="col-lg-2">
                {{ Form::select('tipo_usuario', $tipo_usuario, '', ['id' => 'tipo_usuario_busca', 'class' => 'form-control', 'maxlength' => '40']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_busca_inicial', '',['id' => 'data_busca_inicial', 'class' => 'data', 'placeholder' => 'Data Busca DD/MM/AAAA','maxlength' => '10']) }}
            </div>
            
            <div class="col-lg-2">
                {{ Form::text('data_busca_final', '',['id' => 'data_busca_final', 'class' => 'data', 'placeholder' => 'Data Busca DD/MM/AAAA','maxlength' => '10']) }}
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
	                <th class="td_html">Tipo de Usuário</th>
	                <th class="tb_date">Início</th>
                    <th class="tb_date">Fim</th>
                    <th>Editar</th>
                    <th>Excluir</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
@endsection

@section('script-footer')
	$(document).ready(function(){
        $('.data').mask('00/00/0000');

        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 100,
            autoHide: true
        });

		table_filters = $('#table-campanha').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
            "orderMulti": false,
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
                }        },
            "columnDefs": [
                {
                    "class": "tb_date", 
                    "targets": "tb_date"
                },
                {
                    "targets":  "td_html",
                    "type":     "html",
                    "widh":     "100px"
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
            url: "{{ route('mensagem.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                var dados = data.response.response;
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + dados[field].titulo + "''>" +dados[field].titulo+ "</div></div>",
                            "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + dados[field].tipo_usuario+ "''>" +dados[field].tipo_usuario+ "</div></div>",
                            dados[field].data_inicio,
                            dados[field].data_fim,
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
			url: '{{ Route('mensagem.modal.adicionar') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}'
			},
			success: function(data){
				createModal("modal_criar_nova_mensagem", "Criar Nova Mensagem de Campanha", data,"");
			}
		});
	}

    function modalEditarMensagem($id){
        $.ajax({
            url: '{{ Route('mensagem.modal.editar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(data){
                createModal("modal_editar_mensagem", "Editar Mensagem de Campanha", data,"");
            }
        });
    }

    function excluir($id){
        $.ajax({
            url: '{{ route('mensagem.excluir') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}',
                id: $id},
            success: function(data){
                message("Atenção", "Campanha excluída com sucesso!");
            }
        });        
    }

@endsection