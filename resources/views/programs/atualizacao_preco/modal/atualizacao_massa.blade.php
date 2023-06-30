@extends('layouts.page-dialog')

@section('content')
<form action="#" id="form_atualizacao_massa" name="form_atualizacao_massa" onsubmit="return false">
    @csrf
    <div>
        <div class="form-row">
            <div class="form-group col-sm-6">
                {{ Form::label('grupo', 'Grupo', []) }}
                {{ Form::text('grupo', 'Todos', ['id' => 'grupo', 'class' => 'form-control', 'maxlength' => '200']) }}
            </div>
            <div class="form-group col-sm-6">
                {{ Form::label('subgrupo', 'Sub-Grupo', []) }}
                {{ Form::text('subgrupo', 'Todos', ['id' => 'subgrupo', 'class' => 'form-control', 'maxlength' => '200']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-6">
                {{ Form::label('marca', 'Marca', []) }}
                {{ Form::text('marca', 'Todos', ['id' => 'marca', 'class' => 'form-control', 'maxlength' => '200']) }}
            </div>
            <div class="form-group col-sm-6">
                {{ Form::label('linha', 'Linha', []) }}
                {{ Form::text('linha', 'Todos', ['id' => 'linha', 'class' => 'form-control', 'maxlength' => '200']) }}
            </div>
        </div>
        <hr class="my-4">

        <div class="form-row">
            <div class="form-group col-sm-6">
                {{ Form::label('preco_novo', 'Preço Novo', []) }}
                {{ Form::text('preco_novo', '', ['id' => 'preco_novo', 'class' => 'form-control valor', 'maxlength' => '200']) }}
            </div>
            <div class="form-group col-sm-6">
                {{ Form::label('porcentagem', 'Porcentagem', []) }}
                {{ Form::text('porcentagem', '', ['id' => 'porcentagem', 'class' => 'form-control porcentagem', 'maxlength' => '200']) }}
            </div>
        </div>
</div>
<div class="col-sm-12 mt-5" id="button-bottom">
    <button type="button" id="atualizar_produtos" class="btn btn-success float-right">Atualizar produtos</button>
</div>
</form>
<script>
    table_dialog_produtos_em_massa = $("#table-produtos-em-massa").DataTable({
        "searching": false,
        "paging": true,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
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
        "order": [[ 0, "desc" ]]
    });
    $(document).ready( function () {
        $(document).find("#form_atualizacao_massa").find("#marca").autocomplete(optionsAutoCompleteMarcaDialog());
        $(document).find("#form_atualizacao_massa").find("#grupo").autocomplete(optionsAutoCompleteGrupoDialog());
        $(document).find("#form_atualizacao_massa").find("#subgrupo").autocomplete(optionsAutoCompleteSubgrupoDialog());
        $(document).find("#form_atualizacao_massa").find("#linha").autocomplete(optionsAutoCompleteLinhaDialog());
        $(document).find('.valor').maskMoney({allowZero: false, thousands:'.', decimal:','});
        $(document).find('.porcentagem').maskMoney({allowNegative: true, thousands:'.', decimal:','});
        
        $(document).find("#form_atualizacao_massa").find("#atualizar_produtos").off("click");
        $(document).find("#form_atualizacao_massa").find("#atualizar_produtos").on("click", function(){
            atualizaDados();
        });

    });
    function atualizaDados(){
        form = $(document).find('#form_atualizacao_massa');
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('atualizacao_preco.salvar_atualizacao_massa') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){
                    $(document).find('#modal_atualizacao_massa').modal('hide');
                    message("Atenção", "Produtos atualizados com sucesso");
                }
            },
            error: function(callback){
                console.log(callback.responseJSON);
                if(callback.responseJSON.error){
                    var errors = callback.responseJSON.error;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else if(callback.responseJSON.message != ''){
                    message("Atenção", callback.responseJSON.message);
                }else{
                    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    
        var $select = $(form).find("select[name='"+input+"']");
        $select.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $select.addClass('error-input');
    }

    function optionsAutoCompleteGrupoDialog(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_atualizacao_massa').css('z-index')) + 1));
			},
			select: function( event, ui ) {
			}
		};
	}

	function optionsAutoCompleteSubgrupoDialog(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_atualizacao_massa').css('z-index')) + 1));
			},
			select: function( event, ui ) {
			}
		};
	}

	function optionsAutoCompleteLinhaDialog(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.linha.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_atualizacao_massa').css('z-index')) + 1));
			},
			select: function( event, ui ) {
			}
		};
	}

	function optionsAutoCompleteMarcaDialog(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.marca.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_atualizacao_massa').css('z-index')) + 1));
			},
			select: function( event, ui ) {
			}
		};
    }
    
</script>
@endsection