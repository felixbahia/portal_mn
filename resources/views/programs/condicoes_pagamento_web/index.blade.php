@extends('layouts.app')

@section('content-filter')
	<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
	    @csrf
	    <h3>Listagem de {{ CustomView::programaName() }}</h3>
	    <div class="content-fields">
	        <div class="col-lg-2">
	            <input type="text" name="descricao" id="descricao" value="" placeholder="Digite parte da descrição" maxlength="250" />
	        </div>
            <div class="col-lg-2">
                <select name="liberado_representante" id="liberado_representante_pesquisa">
                    <option value=''>Visibilidade</option>
                    <option value="true">Todos</option>
                    <option value="false">Restrito</option>
                </select>
            </div>
	    </div>
	    <div class="content-buttons">
	        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
	        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
            <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
	    </div>
	</form>
@endsection

@section('content')
	<div class="content-table">
	    <table class="table table-striped" id="table-filters-condicoes">
	        <thead>
	            <tr>
	                <th>Código</th>
	                <th>Descrição</th>
	                <th>Vencimentos</th>
					<th class="td_numeber">Média</th>
					<th>Visibilidade</th>
                    <th class="td_html">Liberado para</th>
                    <th>Ativo</th>
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

		table_filters.draw();

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        $("#btn-create").on("click", function(){
        	modalCriarNovo();
        });

        table_filters.on('draw', function(){
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                modalEditar($(this).data('id'));
            });
            
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event) {
                event.stopPropagation();
                modalExcluir($(this).data('id'));
            });
        });
	});

    table_filters = $('#table-filters-condicoes').DataTable({
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
            }        },
        "columnDefs": [
            {
                "targets": [-2, -1],
                "orderable": false,
            },
            {
                "targets": "td_numeber",
                "className": "number_format",
                "width": "10% !important"
            },
            {
                "targets":  "td_html",
                "type":     "html",
                "widh":     "100px"
            },
        ],
        "order": [[ 1, "asc" ]]
    });
    table_filters.on('draw', function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    table_filters_dias_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        'paging': false,
        "orderMulti": false,
        "scrollY": "20vh",
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
            }        },
        "columnDefs": [
            {
                "targets": -1,
                "orderable": false,
            }
        ],
        "order": [[ 0, 'asc' ]]
    };

	function filterAjax(data_form){
        var $return;
        var form = $("#form_filter");
        table_filters.clear().draw();

        form.find('.error-message').remove();
        
        $.ajax({
            url: "{{ route('condicoes_pagamento_web.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].id,
                            data[field].descricao,
                            data[field].vencimentos,
                            data[field].media,
                            data[field].liberado_representante,
                            data[field].clientes,
                            data[field].ativo,
                            createBtEdit(data[field]),
                            createBtDelete(data[field]),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw();
                }
            },
            error: function(data){
                var errors = data.responseJSON.errors;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"popover\" data-trigger='hover' title=\"Editar\"></a>";
        return html;
    }

    function createBtDelete($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-delete\" data-toggle=\"popover\" data-trigger='hover' title=\"Apagar\"></a>";

        return html;
    }

    function createBtDeleteLista(){

        var html = "<a href='#' class=\"bt-delete\" data-toggle=\"popover\" data-trigger='hover' title=\"Apagar\" onclick=\"deletarLinha(this)\"></a>";

        return html;
    }

    function deletarLinha(element){
        $(element).parents('table').DataTable().row( $(element).parents('tr') ).remove().draw();
        
    }

	function modalCriarNovo(){
		$.ajax({
			url: '{{ Route('condicoes_pagamento_web.form_nova_condicao') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}'
			},
			success: function(data){
				createModal("modal_criar_novo", "Criar nova condição de pagamento", data);
			}
		});
	}

    function modalEditar($id){
        $.ajax({
            url: '{{ Route('condicoes_pagamento_web.editar_condicao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(data){
                createModal("modal_editar", "Editar condição de pagamento", data);
            }
        });
    }

    function modalExcluir($id){
        $.ajax({
            url: '{{route('condicoes_pagamento_web.excluir_condicao')}}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(data){
                createModal('modal_excluir', 'Excluir condição', data);

                $(document).find("#modal_excluir").find("#btn-delete").on("click", function(event){
                    event.stopPropagation();
                    excluir($id);
                });

                $(document).find("#modal_excluir").find("#btn-cancel-delete").on("click", function(event){
                    event.stopPropagation();
                    $(document).find("#modal_excluir").modal("hide");
                });
            }
        });
        
    }


    function showModalCliente(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_searsh_show").find('tbody').find("tr").off("click");
                        $(document).find("#cliente_searsh_show").find('tbody').find("tr").on("click", function(event){
                            returnDados($(this), event);
                        });
                    });
                    $(document).find("#cliente_searsh_show").find(".bt-selected").on("click", function(){
                        returnDados($(this));
                    });
                });
            }
        });
    }

    function returnDados($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_searsh_show").modal("hide");
        $(document).find("#codigo").val($dados.find("td").eq(0).text());
        codParaNome($(document).find("#codigo").val());
    }

    function codParaNome($id){
        form = $(document).find('#cadCondicao');
        
        form.find('.error-message').remove();
        $(document).find('#codigo').removeClass('error-input');
        $(document).find('#nome').removeClass('error-input');
        $(document).find('#bt-search').removeClass('error-input');

        $.ajax({
            url: '{{ route('cliente.codparanome') }}',
            type: 'post',
            dataType: 'json',
            data: {_token: '{{ csrf_token() }}', codigo: $id},
            success: function(callback){
                $(document).find("#nome").val(callback.response.nome);
                $(document).find('#codigo').data('oldvalue', $id);

            },
            error: function(data) {
                $(document).find('#add-clientes-button').after("<label class='error-message' for='bt-search'>Cliente não encontrado</label>");
                $(document).find('#codigo').addClass('error-input');
                $(document).find('#nome').addClass('error-input');
                $(document).find('#bt-search').addClass('error-input');
                
                $(document).find("#codigo").val($(document).find('#codigo').data('oldvalue'));
                $(document).find("#codigo").focus();

                setTimeout(function(){
                    $(document).find('#codigo').removeClass('error-input');
                    $(document).find('#nome').removeClass('error-input');
                    $(document).find('#bt-search').removeClass('error-input');
                    $(document).find("#cod_cliente_group").find('.error-message').fadeOut('slow', function(){ $('this').remove(); });
                }, 2000)
            }
        });
    }

    function addCliente(){

        if ($(document).find('#codigo').val().length > 0 && $(document).find('#nome').val().length > 0){

            nova_linha = [$(document).find('#codigo').val(), "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+$(document).find('#nome').val()+"\">"+$(document).find('#nome').val()+"</div></div>"+"<inpyt type=\"hidden\" name=\"clientes[]\" id=\"clientes\" value=\""+$(document).find('#codigo').val()+"\">", createBtDeleteLista()];

            if($(document).find("#table-filters-clientes").find("td").filter(function() { return $(this).text() === $(document).find('#codigo').val(); }).length == 0 ){

                $(document).find("#table-filters-clientes").DataTable().row.add(nova_linha).draw();

                $(document).find('#codigo').val('');
                $(document).find('#nome').val('');
                
            }
            else{
                $(document).find('#success_button_cliente').after("<label class='error-message' for='bt-search'>Cliente já está na lista</label>");
                $(document).find('#codigo').addClass('error-input');
                $(document).find('#nome').addClass('error-input');
                $(document).find('#bt-search').addClass('error-input');

                setTimeout(function(){
                    $(document).find('#codigo').removeClass('error-input');
                    $(document).find('#nome').removeClass('error-input');
                    $(document).find('#bt-search').removeClass('error-input');
                    $(document).find("#cod_cliente_group").find('.error-message').fadeOut('slow', function(){ $('this').remove(); });
                }, 2000)
            }

        }

    }

    function loadVencimentos($id){
        $(document).find('#dias').text('');

        $.ajax({
            url: '{{ route('condicoes_pagamento_web.retorna_vencimentos') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', CODVCT: $id},
            success: function(data){
                $(document).find('#dias').text(data.vencimentos);
                if ($(document).find('#descricao_modal').val().length == 0){
                    $(document).find('#descricao_modal').val(data.descricao);
                }
            }
        });
        
    }
    function loadParcelas($id){
        $.ajax({
            url: '{{ route('condicoes_pagamento_web.retorna_vencimentos') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', CODVCT: $id, nasajon: true},
            success: function(data){
                $(document).find('#descricao_modal').val(data.descricao);
            }
        });
        
    }

    function excluir($id){

        $.ajax({
            url: '{{ route('condicoes_pagamento_web.excluir') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}',
                id: $id},
            success: function(data){
                message("Atenção", "Condição de pagamento excluída com sucesso!");
                $("#table-filters-condicoes").DataTable().row($("#table-filters-condicoes").find("tr td:nth-child(1)").filter(function() { return $(this).text() == data.id; }).parent('tr')).remove().draw();
                $(document).find("#modal_excluir").modal('hide');

            }
        });        

    }

@endsection