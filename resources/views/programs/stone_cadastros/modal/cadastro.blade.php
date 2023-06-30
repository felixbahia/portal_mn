@extends('layouts.page-dialog')

@section('content')
<form action="#" name='form-stone_cadastro' id='form-stone_cadastro' onsubmit="return false;" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('estabelecimento', 'Estabelecimento', ['class'=>'input-label']) !!}
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
        </div>
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('razao_social', 'Razão Social', ['class'=>'input-label']) !!}
            {!! Form::text('razao_social', '', ['id' => 'razao_social', 'class' => 'form-control', 'maxlength' => '150']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nome_fantasia', 'Nome Fantasia', ['class'=>'input-label']) !!}
            {!! Form::text('nome_fantasia', '', ['id' => 'nome_fantasia', 'class' => 'form-control', 'maxlength' => '150']) !!}
        </div>
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('cnpj', 'CNPJ', ['class'=>'input-label']) !!}
            {!! Form::text('cnpj', '', ['id' => 'cnpj', 'class' => 'form-control cnpj', 'maxlength' => '50']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('stone_code', 'Stone Code', ['class'=>'input-label']) !!}
            {!! Form::text('stone_code', '', ['id' => 'stone_code', 'class' => 'form-control', 'maxlength' => '30']) !!}
        </div>
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('partner_stone', 'Partner Stone', ['class'=>'input-label']) !!}
            {!! Form::text('partner_stone', '', ['id' => 'partner_stone', 'class' => 'form-control', 'maxlength' => '30']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('descricao', 'Descrição', ['class'=>'input-label']) !!}
            {!! Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'maxlength' => '50']) !!}
        </div>
    </div>
</form>

<div class="row mt-2">
    <div class="col-sm text-right" id='enviar-div'>
        {!! Form::button('Salvar', ['id' => 'btn_enviar', 'class' => 'btn btn-success', 'form' => 'form-stone_cadastro']) !!}
    </div>
</div>

<script>
    $(document).ready(function(){
        $(document).find('#btn_enviar').on('click', function(){
            cadastrarMaquininha();
        });
		$(document).find('#form-stone_cadastro').find('.cnpj').mask('99.999.999/9999-99');
    });

    function cadastrarMaquininha(){

        var form = $(document).find('#form-stone_cadastro');
        var dados = form.serialize();
        console.log(dados);
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("stone_cadastro.cadastrar") }}',
            method: 'POST',
            data: dados,
            success: function(data){
                if(data.status == 'sucess'){
                    $(document).find('#cadastro-maquininha').modal('hide');
                    buscarMaquininhas();
                    message('Atenção', data.message);
                }else{
                    message('Atenção', 'Erro ao cadastrar, consulte o setor responsável.');
                }
            },
            error: function(callback){
                if(callback.responseJSON.message != ''){
                    message('Atenção', callback.responseJSON.message);
                    return false;
                }
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalNovo(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalNovo(form, input, message){
        var inputexplode = input.split(".");
		if(inputexplode.length > 1){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
			message = message;
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
		}else if(input.match(/produtos/i) != null){
            $input = $(document).find(".valor_parcial:not([disabled])").eq(input.split('.')[1]);
        }
        else if(input == 'valor_parcial'){
            $input = form.find(".valor_parcial_radio").parent().parent();
        }else{
            var $input = form.find("input[name='"+input+"'], select[name='"+input+"'], textarea[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }


</script>
@endsection