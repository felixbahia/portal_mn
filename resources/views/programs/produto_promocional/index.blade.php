@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="codigo_produto" id="codigo_produto" value="" placeholder="Código Produto" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="descricao" id="descricao" value="" placeholder="Nome Produto" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                {!! Form::select("estabelecimento", $estabelecimentos, '', ["class"=>"form-control"]) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::hidden("codigo_cliente", '', ['id' => 'codigo_cliente']) !!}
                <input type="text" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
            </div>
            <div class="col-lg-2">
                {{ Form::select("vendedor", $dropdown_usuarios, '', ["class"=>"form-control"]) }}
            </div>
            <div class="col-lg-2">
                    <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Inicial da Expiração" value="" maxlength="20">
            </div>
            <div class="col-lg-2">
                    <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Final da Expiração" value="" maxlength="20">
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2 form-group ml-4">
                {{ Form::checkbox('ativos', '1', true,  ['id' => 'ativos', 'class' => 'form-check-input']) }}
                {{ Form::label('ativos', 'Só promoções ativas', ['class' => 'form-check-label','for' => 'ativos']) }}
            </div>
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
        <table class="table table-striped" id="table-filters">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Produto</th>
                    <th>Estab</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Tipo</th>
                    <th class="number_format">Preço Real</th>
                    <th class="number_format">Porcentagem</th>
                    <th class="date_format">Data Expir.</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {
        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        $("#descricao").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $("#cliente").autocomplete(optionsAutoCompleteCliente());

        $(document).find("#btn-filterform").on("click", function(){
			filterClear();
			filterAjax();
		});

		$(document).find("#btn-clearform").on("click", function(){
			filterClear();
        });

        $(document).find("#cliente").blur(function(){
            if($(document).find("#cliente").val() == ''){
                $(document).find("#codigo_cliente").val('');
            }
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 100,
            autoHide: true
        });
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });
        $('#data_fim').on('pick.datepicker', function (e) {
            if($('#data_inicio').datepicker('getDate') > e.date){
                $('#data_inicio').val('');
            }
            $('#data_inicio').datepicker('setEndDate', e.date);
        });
        
    });
    
    function showModalCreate(){
        $.ajax({
            url: '{{ route('listaprecosprodutospromocionais.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal('modal_produto_promocional_adicionar', title, body, '');
            }
        });
    }

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_produto_promocional_edit_delete', title, body, modal_class);
                var modal = $("#modal_produto_promocional_edit_delete");
            }
        });
    }

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#table-filters-produto-promocional').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    filterAjax();
                }, 100);
            }
        };
    }

    function optionsAutoCompleteCliente(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto_promocional_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $("#codigo_cliente").val(ui.item.value);
                $("#cliente").val(ui.item.label);
                return false;
            }
        };
    }

    function filterAjax(){
		form = $(document).find("#form_filter");
		data_form = form.serialize();
        filterClear();
		$.ajax({
			url: '{{ route('listaprecosprodutospromocionais.filter')}}',
            data: data_form,
            method: 'POST',
			success: function(data){
                produtos = [];
				for (var fields in data.response){
					temp_array = [
                        data.response[fields].grupo,
                        data.response[fields].descricao,
                        data.response[fields].estabelecimento,
                        data.response[fields].cliente,
                        data.response[fields].vendedor,
                        data.response[fields].tipo_promocional,
                        data.response[fields].preco_real,
                        data.response[fields].desconto_porcentagem,
                        data.response[fields].data_expiracao,
                        createBtnEdit("{{ route('listaprecosprodutospromocionais.modal.editar') }}", data.response[fields]),
                        createBtnDelete("{{ route('listaprecosprodutospromocionais.modal.deletar') }}", data.response[fields])
                    ];
					produtos.push(temp_array)
				}

				table_filters.rows.add(produtos).draw();

			}
		});
	}

	function filterClear(){
		table_filters.clear().draw();
    }
    
    function createBtnEdit($url, $dados){
        if($dados.exibe_botao === false){
            return "";
        }
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar {{ CustomView::programaName() }}\"></a>";
        return $html;
    }

    function createBtnDelete($url, $dados){
        if($dados.exibe_botao === false){
            return "";
        }
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir {{ CustomView::programaName() }}\"></a>";
        return $html;
    }
    
@endsection