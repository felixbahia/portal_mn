@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="filtro-campos" >
            <div class="row filtro-campos">
                <div class="col-lg-3">
                    {{ Form::text('marca', '', ['id' => 'marca', 'placeholder' => 'Marca', 'maxlength' => '250']) }}	  
                </div>
                <div class="col-lg-3">
                    {{ Form::text('linha', '', ['id' => 'linha', 'placeholder' => 'Linha', 'maxlength' => '250']) }}
                </div>
                <div class="col-lg-3">
                    {{ Form::text('grupo', '', ['id' => 'grupo', 'placeholder' => 'Grupo', 'maxlength' => '250']) }}
                </div>
                <div class="col-lg-3">
                    {{ Form::text('subgrupo', '', ['id' => 'subgrupo', 'placeholder' => 'Subgrupo', 'maxlength' => '250']) }}
                </div>
            </div>
            <div class="row filtro-campos">
                <div class="col-lg-4">
                    {{ Form::text('produto', '', ['id' => 'produto', 'placeholder' => 'Código de produto', 'maxlength' => '250']) }}
                </div>
                <div class="col-lg-4">
                    {{ Form::text('nome', '', ['id' => 'nome', 'placeholder' => 'Nome Produto', 'maxlength' => '250']) }}
                </div>
                <div class="col-lg-2">
                    <select name="segmentos" id="segmentos">
                        <option value="">Segmentos</option>
                        @foreach($segmentos as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    {{ Form::select('status', ['' => 'Status do produto','true' => 'Ativo', 'false' => 'Inativo'], '', ['id' => 'status']) }}
                </div>
            </div>
        </div>
    </div>
	<div class="content-buttons filtro-campos">
	    <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
	    <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
	</div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-info">
        <thead>
            <tr>
                <th>Marca</th>
                <th>Linha</th>
                <th>Grupo</th>
                <th>Subgrupo</th>
                <th>Cód. Produto</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Largura</th>
                <th>Gramatura<br/>Linear</th>
                <th>Rendimento</th>
                <th>Editar</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

	$(document).ready(function(){
		$('#btn-filterform').on('click', function(){
			filtro();
		});

		$('#btn-create').on('click', function(){
            showModalAdd();
		});


        $(document).find("#marca").autocomplete(optionsAutoCompleteMarca());
		$(document).find("#linha").autocomplete(optionsAutoCompleteLinha());
		$(document).find("#grupo").autocomplete(optionsAutoCompleteGrupo());
        $(document).find("#subgrupo").autocomplete(optionsAutoCompleteSubgrupo());
        $(document).find("#nome").autocomplete(optionsAutoCompleteProdutoNasajon());

	})
    
    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\" onclick=\"showModalEdit('"+$this.cod_produto+"', '"+$this.nome+"')\"></a>";
        return html;
    }
    function createBtDelete($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\" onclick=\"deleteAlert('"+$this.id+"', '"+$this.nome+"', '"+$this.cod_produto+"')\"></a>";
        return html;
    }

    function optionsAutoCompleteProdutoNasajon(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produtos_nasajon.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

    function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteSubgrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteLinha(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.linha.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteMarca(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.marca.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

    table_filters = $('#table-filters-info').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        {{--"processing": true,
        "serverSide": true,
        "orderMulti": false,
        "ajax": {
            "url": "{{ route('analise.produto.infoadicional.filter') }}",
            "type": "POST",
            "data": function ( d ) {
                d.codigo = $('#codigo').val();
                d.nome = $('#nome').val();
                d.marca = $('#marca').val();
                d.linha = $('#linha').val();
                d.grupo = $('#grupo').val();
                d.subgrupo = $('#subgrupo').val();
                d._token = "{{ csrf_token() }}";
            },
            "dataSrc": function ( json ) {
                json.data = parserDataJson(json.data);
                $('[data-toggle="popover"]').off('show.bs.popover');
                $('[data-toggle="popover"]').popover('hide');
                return json.data;
            }
        },
        "drawCallback": function(settings) {
            $('[data-toggle="popover"]').popover({
                container: 'body',
                html: true,
                show: true,
                template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
            });
            $('[data-toggle="popover"]').on('show.bs.popover', function () {
                var $this = $(this);
                $('.popover').not($this).each(function(){
                    $("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
                });
                $("body").on("keyup", function(e){
                    if(e.keyCode == 27){
                        $($this).popover('hide');
                    }
                });
            });
        },--}}
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
                "targets": [7,8],
                "className": 'number_format',
            },
        ],
        "order": [[0, "asc"], [1, "asc"], [2, "asc"], [3, "asc"], [4, "asc"]]   

    });

	function filtro(){
		
		table_filters.clear().draw();

        form = $("#form_filter");
        $data = form.serialize();
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

		$.ajax({
			url: '{{ route('analise.produto.infoadicional.filter')}}',
			type: 'POST',
			data: $data,
			success: function(data){
				
				produtos = [];

				for (var fields in data){

					temp_array = [
						data[fields].marca,
						data[fields].linha,
                        data[fields].grupo,
                        data[fields].subgrupo,
						data[fields].cod_produto,
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[fields].nome +  "''>" + data[fields].nome + "</div></div>",
                        data[fields].status,
						data[fields].largura,
                        data[fields].gramatura,
                        data[fields].rendimento,
						createBtEdit(data[fields]),
						createBtDelete(data[fields])
					];

					produtos.push(temp_array)
				}

				table_filters.rows.add(produtos).draw();

			},
            error: function(data){
                
                var errors = data.responseJSON.errors;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }

            }
		});
	}

	function showModalEdit($cod_produto, $nome_produto){

		$.ajax({
			url: '{{ route('analise.produto.infoadicional.telaEdicao') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				cod_produto: $cod_produto
			},
			success: function(data){
				if(data.status == 'error'){
					message('Atenção',data.message);
				}else{
					createModal('modal_editar', "Editar detalhes de: " + $nome_produto, data, '');

					$(document).find('#modal_editar').find("#salvar").on('click', function(){
						salvar_edicao();
					});
				}
			},
            error: function(callback){
				message('Atenção', callback.responseJSON.message);
            }
		});
	}

    function showModalAdd(){

        $.ajax({
            url: '{{ route('analise.produto.infoadicional.telaAdicao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(data){
                createModal('modal_adicionar', "Adicionar detalhe à produto", data, '');

                $(document).find('#modal_adicionar').find("#salvar").on('click', function(){
                    salvar_adicao();
                })

                $(document).find("#modal_adicionar").find("#bt-search-produto").on('click', function(){
                    
                    $.ajax({
                        url: '{{ route('analise.produto.infoadicional.produtosSemDetalhes') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data){
                            createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                        }
                    });

                });
            }
        });
    }

    function salvar_adicao(){

        $data = $(document).find('#cadInfoAdicional').serialize();
        form = $(document).find("#cadInfoAdicional");
        
        $.ajax({
            url: '{{ route('analise.produto.infoadicional.salvar_adicao') }}',
            type: 'POST',
            data: $data,
            success: function(){
                $(document).find("#modal_adicionar").modal('hide');
                message('Sucesso', "Informações salvas com sucesso");
                filtro();
            },
            error: function(data){
                var errors = data.responseJSON.errors;
                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        })      
    }
    

    function showErrorsInputs(form, input, message){
        if (input == 'cod_produto'){
            var $input = $(form).find("#descricao_modal");
            $input.after("<label class='error-message' for='descricao'>"+message+"</label>");
            $input.addClass('error-input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

    function deleteAlert($id, $nome, $cod_produto){

        var $class = "dialog_option_deletar";
        var $name_option_ok = "deletar";
    
        $(document).off("deletar");
        $(document).on("deletar", function(){
            excluir($id, $cod_produto);
            return null;
        });

    	message_option("Apagar informações de " + $nome + "?", "As informações adicionais do produto serão apagadas", $class, $name_option_ok);

    }

    function excluir($id, $cod_produto){

        $.ajax({
            url: '{{ route('analise.produto.infoadicional.excluir') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id,
                cod_produto: $cod_produto
            },
            success: function(){
                table_filters.clear().draw();
            },
            error: function(){
                $title = "Erro";
                $text = "Ocorreu um erro ao excluir este produto.";
                $class = "excluir_erro_mensagem";

                message($title, $text, $class);
            }
        });
    }

@endsection
</script>
