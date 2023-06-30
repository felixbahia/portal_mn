@extends('layouts.page-dialog')

@section('content')
    <div class="content-dialog-table" style='float: none;'>
        <table class='table table-striped table-filter-pedido-itens table-not-edit'>
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Grupo</th>
                    <th>Subgrupo</th>
                    <th>Linha</th>
                    @if (isset($produto['codigo_produto']) && !empty($produto['codigo_produto']))
                    <th>Código do Produto</th>
                    <th>Descrição</th>
                    @endif
                    <th>Unidade</th>
                    <th>Agrupados</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $produto['marca'] }}</td>
                    <td>{{ $produto['grupo'] }}</td>
                    <td>{{ $produto['subgrupo'] }}</td>
                    <td>{{ $produto['linha'] }}</td>
                    @if (isset($produto['codigo_produto']) && !empty($produto['codigo_produto']))
                    <td>{{ $produto['codigo_produto'] }}</td>
                    <td>{{ $produto['descricao'] }}</td>
                    @endif
                    <td>{{ $produto['unidade'] }}</td>
                    <td class='text-right'><a href="#" title='Produtos contigos' data-hash='{{ $produto['hash'] }}' onclick="modalProdutos($(this).data('hash'))">{{ $produto['produtos'] }}</a></td>
                    {{-- <td></td> --}}

                </tr>
            </tbody>
        </table>
    </div>

    <hr>
    <form action="#" method="post" name="especificacoes_atualizar" id="especificacoes_atualizar">
        @csrf
        {{ Form::hidden('hash', $produto['hash'], ['id' => 'hash']) }}
        
        <div class="form-row">
            <div class="form-group col-lg-6">
                {{ Form::label('marca', 'Marca', []) }}
                {{ Form::text('marca', $produto['marca'], ['class' => 'form-control', 'id' => 'marca_edicao']) }}
            </div>
            <div class="form-group col-lg-6">
                {{ Form::label('linha', 'Linha', []) }}
                {{ Form::text('linha', $produto['linha'], ['class' => 'form-control', 'id' => 'linha_edicao']) }}
            </div>
            <div class="form-group col-lg-6">
                {{ Form::label('grupo', 'Grupo', []) }}
                {{ Form::text('grupo', $produto['grupo'], ['class' => 'form-control', 'id' => 'grupo_edicao']) }}
            </div>
            <div class="form-group col-lg-6">
                {{ Form::label('subgrupo', 'Subgrupo', []) }}
                {{ Form::text('subgrupo', $produto['subgrupo'], ['class' => 'form-control', 'id' => 'subgrupo_edicao']) }}
            </div>
        </div>

        
        <div class="form-row">
            <div class="form-group col-sm-12 mt-4">
                {{ Form::submit('Atualizar especificações', ['class' => 'btn btn-primary float-right']) }}
            </div>
        </div>

        <div class="form-row mostrar-itens">
            <div class="col-sm-1 esconder-itens">
                <button type='button' class='btn btn-secondary' id='escolher-itens-btn'>Escolher itens para a edição</a>
            </div>
        </div>

        <div class="form-row escolher-itens">
            <div class="col-sm-3">
                <button type='button' class='btn btn-secondary' id='esconder-itens-btn'>Editar todos os itens</a>
            </div>
        </div>
        <div class="form-row escolher-itens">
            @foreach ($produto['itens'] as $item)
                <div class="col-sm-3 mt-4" style='white-space: no-wrap;'>
                    <label for='item_{{ $item['codigo_produto'] }}' class='form-control'>{{ Form::checkbox('itens[]', $item['codigo_produto'], false, ['class' => 'form-control checkbox-left', 'id' => 'item_' . $item['codigo_produto']]) }} {{ $item['codigo_produto'] }} - {{ $item['descricao'] }}</label>
                </div>
            @endforeach
        </div>
    </form>

<script>
    
    $(document).ready(function(){

        $(document).find('#especificacoes_atualizar').on('submit', function(event){
            event.preventDefault();
            enviaPrecos();
        });

        $(document).find('#marca_edicao').autocomplete(optionsAutoCompleteMarca());
        $(document).find('#linha_edicao').autocomplete(optionsAutoCompleteLinha());
        $(document).find('#grupo_edicao').autocomplete(optionsAutoCompleteGrupo());
        $(document).find('#subgrupo_edicao').autocomplete(optionsAutoCompleteSubgrupo());

        $(document).find('.escolher-itens').hide();
        $(document).find('.esconder-itens').show();

        $(document).find('#escolher-itens-btn').on('click', function(){
            $(document).find('.escolher-itens').show();
            $(document).find('.esconder-itens').hide();
        });

        $(document).find('#esconder-itens-btn').on('click', function(){
            $(document).find('.escolher-itens').hide();
            $(document).find('.esconder-itens').show();
            $(document).find('.checkbox-left').prop('checked', false);
        });

    });

    function optionsAutoComplete($name, element = null){

        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                request.term = request.term.toLowerCase(); 
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 300,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
            },
            change: function(event,ui){
                if (!ui.item) { 
                    $(this).val(''); 
                }
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_especificacoes').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_especificacoes').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_especificacoes').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_especificacoes').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

    function enviaPrecos(){

        var form = $(document).find('#especificacoes_atualizar');

        form.find('.error-message').remove();
        form.find('error-input').removeClass('error-input');

        $.ajax({
            url: "{{ route('atualizacao_preco.atualiza_especificacoes') }}",
            dataType: 'json',
            data: form.serialize(),
            method: 'POST',
            success: function(data){
                if(data.total > 1){
                    message('Sucesso', 'Atualizados ' + data.total + ' produtos');

                }
                else if(data.total == 1){
                    message('Sucesso', 'Produto atualizado');
                }

                $(document).find("#modal_especificacoes").modal('hide');
                filterAjax($('#form_filter').serialize());
            },
            error: function(data){
                if (typeof data.responseJSON.erro != 'undefined'){
                    message('Erro', data.responseJSON.erro);
                }
                else{
                    var errors = data.responseJSON.errors;
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>

@endsection