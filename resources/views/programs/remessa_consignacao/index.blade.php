@extends('layouts.app')

@section('content')
<div class="content-filter">
	<div class="form-row">
		<div class="form-group col-12">
			{{ Form::label('arquivo', 'Arquivo') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
			<div class="input-group mb-3">
				{!! Form::file('arquivo',  ['id' => 'arquivo', 'class' => 'form-control']) !!}
				<div class="input-group-append">
					{{ Form::button('Enviar o arquivo', array('class' => 'btn btn-primary float-right', 'id' => 'enviar_arquivo')) }}
				</div>
			</div>
		</div>
		<div class="form-group col-12">
			<div class="progress" id="progress_content">
				<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
				</div>
		</div>
	</div>
	<form action="#" name="form_remessa_consignacao" id="form_remessa_consignacao">
		@csrf
		{{ Form::hidden('id', '', ['id' => 'id']) }}
		{{ Form::hidden('pedido_id', '', ['id' => 'pedido_id']) }}
		<div class="form-row">
			<div class="form-group col-12">
				{{ Form::label('cliente_nome', 'Cliente') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				<div class="input-group" id="cod_cliente_group">
					{{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
					<span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
				</div>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-8">
				{{ Form::label('transportadora_nome', 'Transportadora') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				<div class="input-group" id="cod_transportadora_group">
					{{ Form::text('transportadora_nome', '', ['id' => 'transportadora_nome', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
					<span class="input-group-addon border rounded-right" id="bt-search-transportador" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportador" class="bt-view m-2"></i></span>
				</div>
			</div>
			<div class="form-group col-2">
				{{ Form::label('transportadora_tipo_frete', 'Tipo de frete', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::select('transportadora_tipo_frete', $tipo_frete, '', ['id' => 'transportadora_tipo_frete', 'class' => 'form-control essencial']) }}
			</div>
			<div class="form-group col-sm-2">
				{{ Form::label('valor_frete', 'Valor do Frete', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::text('valor_frete', '', ['id' => 'valor_frete', 'class' => 'form-control text-right']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="content-table p-0">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pedidos-itens">
					<thead>
						<tr>
							<th class="td_codigo_produto">Código</th>
							<th>Descrição</th>
							<th class="tb_number td_quantidade">Quantidade</th>
							<th class="tb_number td_preco">Preço unitário</th>
							<th class="tb_number td_total">Valor total</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4" class="number_format"><b>Total:</b></td>
							<td class="number_format" id="total_pedido"></td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<div class="col-12 mt-2" id="button-bottom">
			{{ Form::button('Gerar Pedido', array('class' => 'btn btn-success float-right', 'id' => 'btn-calcular')) }}
		</div> 
	</form>
</div>
@endsection
@section('script-footer')
	init();

	function init(){
		initFunctionsOn();
		initAutoCompletes();
		$(document).find("#valor_frete").parent().hide();
        $(document).find("#valor_frete").maskMoney({thousands:'', decimal:','});


		table_filters_produtos_options = {
			"searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"pageLength": -1,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
            "scrollY": "35vh",
            "autoWidth": false,
			"language": {
				"decimal":        ",",
				"thousands":      ".",
				"emptyTable":     "Nenhum produto inserido",
				"infoPostFix":    "",
				"loadingRecords": "Carregando...",
				"processing":     "Processando...",
				"zeroRecords":    "Nenhum  produto inserido",
				"paginate": {
					"first":      "<<",
					"last":       ">>",
					"next":       ">",
					"previous":   "<"
				}
			},
			"columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {
                    'targets': ['td_quantidade', 'td_preco', 'td_total'],
                    'width': '200px'
                },
                {
                    'targets': 'td_codigo_produto',
                    'width': '100px'
                },
                
			],
			"order": [[ 1, 'asc' ]]
		};
		table_produtos = '';
		table_produtos = $(document).find('#table-filters-pedidos-itens').DataTable(table_filters_produtos_options);
		table_produtos.draw();
    }

    function exibirValorFrete(){
        if ($(document).find('#transportadora_tipo_frete').val() == 'C'){
            $(document).find("#valor_frete").parent().show();
            $(document).find("#valor_frete").addClass('essencial')
        }
        else {
            $(document).find("#valor_frete").parent().hide();
            $(document).find("#valor_frete").removeClass('essencial');
        }
    }

	function enviarArquivo(){
		var progressElem = $(document).find('#progress_content');
		progressElem.hide();

        var form = $(document).find("#form_remessa_consignacao");
        
        var formdata = new FormData();

        formdata.append('_token', $(document).find("input[name='_token']").val());
        formdata.append('pedido_id', $(document).find("#pedido_id").val());
        formdata.append('arquivo', $(document).find('#arquivo').prop('files')[0], $(document).find('#arquivo').val());
		
		$.ajax({
            data: formdata,
			type: 'POST',
			dataType: 'json',
            processData: false,
            contentType: false,
			url: "{{ route('remessa_consignacao.enviar_arquivo') }}",
			cache: false,
			error: function (xhr, ajaxOptions, thrownError) {
				alert(xhr.responseText);
			},
			xhr: function () {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function (evt) {
					if (evt.lengthComputable) {
						var percentComplete = evt.loaded / evt.total;
						progressElem.find(".progress-bar").css('width', Math.round(percentComplete * 100) + "%");
					}
				}, false);
				return xhr;
			},
			beforeSend: function () {
				progressElem.show();
				table_produtos.clear().draw();
			},
			complete: function () {
				progressElem.hide();
			},
			success: function (callback) {
				if(callback.status == 'success'){
					dados = callback.response;
					table_produtos.clear().draw();
					produtos = dados.produtos;

					$(document).find("#id").val(dados.id);
					if(produtos.length > 0){
						var contador_item = 0;
						var fields_filter = [];
						for(var field in produtos){
							var temp_field = [
								produtos[field].produto_codigo+'<input type="hidden" name="item['+contador_item+'][produto]" id="item_'+contador_item+'_produto" value="'+produtos[field].produto_codigo+'" />',
								produtos[field].descricao,
								produtos[field].quantidade+'<input type="hidden" name="item['+contador_item+'][quantidade]" id="item_'+contador_item+'_quantidade" value="'+produtos[field].quantidade+'" />',
								produtos[field].preco+'<input type="hidden" name="item['+contador_item+'][preco]" id="item_'+contador_item+'_preco" value="'+produtos[field].preco+'" />',
								produtos[field].preco_total+'<input type="hidden" name="item['+contador_item+'][preco_total]" id="item_'+contador_item+'_preco_total" value="'+produtos[field].preco_total+'" />'
							];
							fields_filter.push(temp_field);
							contador_item++;
						}
						table_produtos.rows.add(fields_filter).draw().nodes();
					}
				}
			}
		});
	}

	function initFunctionsOn(){
		$(document).find('#enviar_arquivo').off('click');
		$(document).find('#enviar_arquivo').on('click', function(){
			enviarArquivo();
		});

        $(document).find('#transportadora_tipo_frete').off('change');
        $(document).find('#transportadora_tipo_frete').on('change', function(){
            exibirValorFrete();
			gravarPedido();
		});

	}

	function initAutoCompletes(){
        $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());
        $(document).find("#transportadora_nome").autocomplete(optionsAutoCompleteTransportador());
	}

    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
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
                $(document).find("#cliente_nome").val(ui.item.label);
				gravarPedido();
                return false;
            }
        };
    }

    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora_nome").val(ui.item.label);
				gravarPedido();
                return false;
            }
        };
    }

	function gravarPedido(){
        var $form = $(document).find("#form_remessa_consignacao");
		$(document).find('label.error-message').remove();
		$.ajax({
            data: $form.serialize(),
			type: 'POST',
			url: "{{ route('remessa_consignacao.salvar_pedido') }}",
			success: function (callback) {
				if(callback.status == 'success'){
					dados = callback.response;
					produtos = dados.produtos;

					$(document).find("#pedido_id").val(dados.id);
					if(produtos.length > 0){
						var contador_item = 0;
						var fields_filter = [];
						produtos.forEach(camposProdutos);
					}
				}
			},
			error: function (callback) {
				if((callback.responseJSON)){
					var data = callback.responseJSON.error;
					$.each(data, function(index, el) {
						$form.find('input[name="'+index+'"], select[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
						$form.find('input[name="'+index+'"], select[name="'+index+'"]').eq(0).addClass('error');
					});
					$form.find('input.error').eq(0).focus();
				}
			}
		});
	}
	function camposProdutos(element, index, array) {
		console.log("a[" + index + "] = " + element);
		$campo_quantidade = $(document).find("#item_"+index+"_quantidade");
		console.log($campo_quantidade);
			{{-- var temp_field = [
				produtos[field].produto_codigo+'<input type="hidden" name="item['+contador_item+'][produto]" id="item_'+contador_item+'_produto" value="'+produtos[field].produto_codigo+'" />',
				produtos[field].descricao,
				produtos[field].quantidade+'<input type="hidden" name="item['+contador_item+'][quantidade]" id="item_'+contador_item+'_quantidade" value="'+produtos[field].quantidade+'" />',
				produtos[field].preco+'<input type="hidden" name="item['+contador_item+'][preco]" id="item_'+contador_item+'_preco" value="'+produtos[field].preco+'" />',
				produtos[field].preco_total+'<input type="hidden" name="item['+contador_item+'][preco_total]" id="item_'+contador_item+'_preco_total" value="'+produtos[field].preco_total+'" />'
			];
			fields_filter.push(temp_field);
			contador_item++;
		} --}}
	}
@endsection
