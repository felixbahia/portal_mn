@extends('layouts.page-dialog')

@section('content')
<form action="#" name='form-stone_cadastro' id='form-stone_cadastro' onsubmit="return false;" enctype="multipart/form-data">
    @csrf
    {{ Form::hidden('id', $id) }}
    <div class="row">
        <div class="col-sm-12">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('vinculo_maquininha', 'Vinculo 
            com a maquininha(POS).', ['class'=>'input-label']) !!}
        </div>
        <div class="row pl-5 col-md-12">
            <div class="col-12">
                <div class="form-check">
                    {{ Form::radio('vinculo', 'aberto', '', ['class' => 'form-check-input', 'id'=>'vinculo_aberto',(isset($configuracao['vinculo']) && $configuracao['vinculo'] == 'Aberto') ? 'checked' : '']) }}
                    {{ Form::label('vinculo_aberto', 'Aberto', ['class'=>'form-check-label']) }}
                    <i href="#" class="btn-informacao float-none" data-toggle="tooltip" data-placement="top" title="" data-original-title="Vinculo aberto a 
                    recepção de qualquer transação por qualquer maquininha ativa, ou seja, não importa a quantidade de PDV's/estação de trabalho e a 
                    quantidade de maquininhas disponíveis no estabelecimento, todas as maquininhas podem buscar transações de todos os PDV's, esta 
                    não trabalha com a possibilidade da maquininha receber configuração de vínculo a qualquer momento." style="color: black;"></i>
                </div>
                <div class="form-check">
                    {{ Form::radio('vinculo', 'fechado', '', ['class' => 'form-check-input', 'id'=>'vinculo_fechado',(isset($configuracao['vinculo']) && $configuracao['vinculo'] == 'Fechado') ? 'checked' : '']) }}
                    {{ Form::label('vinculo_fechado', 'Fechado', ['class'=>'form-check-label']) }}
                    <i href="#" class="btn-informacao float-none" data-toggle="tooltip" data-placement="top" title="" data-original-title="Vínculo Exclusivoentre 
                    PDV e maquininha. Nessa configuração, não é possível iniciar o processo de recepção de transações na maquininha, sem que a ela 
                    esteja configurada para o vínculo exclusivo com pré-transações que são criadas enviando seu ID de referência." style="color: black;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('desativar_lista_pendente', 'Desativa 
            a exibição da lista no caso de uma única transação pendente de pagamento.', ['class'=>'input-label']) !!}
        </div>
        <div class="row pl-5 col-md-12">
            <div class="col-12">
                <div class="form-check">
                    {{ Form::radio('desativar_lista', 'sim', '', ['class' => 'form-check-input', 'id'=>'desativar_lista_sim',(isset($configuracao['lista']) && $configuracao['lista'] == true) ? 'checked' : '']) }}
                    {{ Form::label('desativar_lista_sim', 'Sim', ['class'=>'form-check-label']) }}
                </div>
                <div class="form-check">
                    {{ Form::radio('desativar_lista', 'nao', '', ['class' => 'form-check-input',(isset($configuracao['lista']) && $configuracao['lista'] == false) ? 'checked' : 'checked', 'id'=>'desativar_lista_nao']) }}
                    {{ Form::label('desativar_lista_nao', 'Não', ['class'=>'form-check-label']) }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            {!! Form::label('travar_app', 'Travar app connect na maquininha.', ['class'=>'input-label']) !!}
        </div>
        <div class="row pl-5 col-md-12">
            <div class="col-12">
                <div class="form-check">
                    {{ Form::radio('travar_app', 'sim', '', ['class' => 'form-check-input', 'id'=>'travar_app_sim']) }}
                    {{ Form::label('travar_app_sim', 'Sim', ['class'=>'form-check-label']) }}
                </div>
                <div class="form-check">
                    {{ Form::radio('travar_app', 'nao', '', ['class' => 'form-check-input']) }}
                    {{ Form::label('travar_app_nao', 'Não', ['class'=>'form-check-label']) }}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            {!! Form::label('tempo_transacao', 'Tempo para listar transações ( máx. 21600s)', ['class'=>'input-label']) !!}
            {!! Form::number('tempo_transacao', '', ['id' => 'tempo_transacao', 'class' => 'form-control', 'maxlength' => '10']) !!}
        </div>
    </div>
    <div id="container_maquininhas" @if((isset($configuracao['vinculo']) && $configuracao['vinculo'] == 'Fechado')) @else class="d-none" @endif>
        <div class="adicionar-elemento">
            @if(isset($configuracao['vinculos_Array']))
                @foreach ($configuracao['vinculos_Array'] as $key => $vinculo)
                    <div class="row excluir_vinculo{{$key}}">
                        <div class="col-sm-2">
                            {!! Form::label('identificacao_caixa', 'Identificação do caixa', ['class'=>'input-label']) !!}
                            {!! Form::text('identificacao_caixa_existente['.($key).']', $vinculo['cashier_number'], ['id' => 'identificacao_caixa_existente['.$key.']', 'class' => 'form-control', 'maxlength' => '150','disabled']) !!}
                        </div>
                        <div class="col-sm-2">
                            {!! Form::label('identificacao_pdv', 'Identificação do PDV', ['class'=>'input-label']) !!}
                            {!! Form::text('identificacao_pdv_existente['.($key).']', $vinculo['pdv_number'], ['id' => 'identificacao_pdv_existente['.$key.']', 'class' => 'form-control', 'maxlength' => '50','disabled']) !!}
                        </div>
                        <div class="col-sm-2">
                            {!! Form::label('nome_vinculo', 'Nome no vínculo', ['class'=>'input-label']) !!}
                            {!! Form::text('nome_vinculo_existente['.($key).']', $vinculo['pos_link_label'], ['id' => 'nome_vinculo_existente['.$key.']', 'class' => 'form-control', 'maxlength' => '50','disabled']) !!}
                        </div>
                        <div class="col-sm-2">
                            {!! Form::label('serial', 'Serial', ['class'=>'input-label']) !!}
                            {!! Form::text('serial_existente['.($key).']', $vinculo['serial'], ['id' => 'serial_existente['.$key.']', 'class' => 'form-control', 'maxlength' => '50','disabled']) !!}
                        </div>
                        <div class="col-sm-3">
                            <br>
                            {!! Form::button('Excluir Vínculo', ['id' => 'btn_excluir_vinculo', 'class' => 'form-group btn btn-danger', 'onclick' => 'excluirVinculo("'.$vinculo['id'].'","excluir_vinculo'.$key.'")']) !!} 
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="row">
            <div class="form-group col-md-3">
                {{ Form::button('Adicionar Vinculo', ['class' => 'btn btn-success', 'id' => 'btn_adicionar_vinculo']) }}
            </div>
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
        $(document).find('#form-stone_cadastro input').on('change', function() {
            var vinculo = $('input[name=vinculo]:checked', '#form-stone_cadastro').val(); 
            if(vinculo == 'fechado'){
                $(document).find('#container_maquininhas').removeClass("d-none");
            }else{
                $(document).find('#container_maquininhas').addClass("d-none");
            }
        });

		var x = 1;
		var max_fields = 20;

        $('#btn_adicionar_vinculo').click (function(e){
			e.preventDefault(); 
			if (x < max_fields)
			{
                var anterior = 0;
				var conteudo = 
					'<div class="row remove'+x+'">'+
						'<div class="col-sm-2">'+
							'{{ Form::label("identificacao_caixa", "Identificação do caixa") }}'+
							'<input type="text" id="identificacao_caixa['+x+']" name="identificacao_caixa['+x+']" class="form-control campo_identificacao_caixa'+x+'" maxlength="50">'+
						'</div>'+
						'<div class="col-sm-2">'+
							'{{ Form::label("identificacao_pdv", "Identificação do PDV") }}'+
							'<input type="text" id="identificacao_pdv['+x+']" name="identificacao_pdv['+x+']" class="form-control campo_identificacao_pdv'+x+'" maxlength="50">'+
						'</div>'+
						'<div class="col-sm-2">'+
							'{{ Form::label("nome_vinculo", "Nome no vínculo") }}'+
							'<input type="text" id="nome_vinculo['+x+']" name="nome_vinculo['+x+']" class="form-control campo_nome_vinculo'+x+'" maxlength="50">'+
						'</div>'+
						'<div class="col-sm-2">'+
							'{{ Form::label("serial", "Serial") }}'+
							'<input type="text" id="serial['+x+']" name="serial['+x+']" class="form-control campo_serial'+x+'" maxlength="30">'+
						'</div>'+
						'<div class="col-sm-4">'+
							'<br>'+
							'<input type="button" id="remove'+x+'" class="form-group btn btn-danger remove_documento" value="Remover">'+
						'</div>'+
					'</div>';

				if(x > 1){
                    anterior = x - 1;
                    for(var i = 1; i < x; i++){
                        var campo_identificacao_caixa = $(".campo_identificacao_caixa"+i).val();
                        var campo_identificacao_pdv = $(".campo_identificacao_pdv"+i).val();
                        var campo_nome_vinculo = $(".campo_nome_vinculo"+i).val();
                        var campo_serial = $(".campo_serial"+i).val();
                        
                        if(campo_identificacao_caixa == '' || campo_identificacao_pdv == '' || campo_nome_vinculo == '' || campo_serial == ''){
                            message("Atenção", "Adicione documentos para adicionar mais campos!");
                            return false;
                        }
                    }
                    $(document).find("#remove"+anterior).hide();
                }
				
				$('.adicionar-elemento').append( conteudo );
				x++;
			}else{
				message('Alerta','Limite Máximo de Documentos Atingido');
			}
		});

        $(document).find('.adicionar-elemento').on("click",".remove_documento",function(e) {
			e.preventDefault();
			var anterior = x - 2;
            $(document).find("#remove"+anterior).show();
            if(x > 1){
                x --; 
            }
			var id = $(this).attr('id');
			$(document).find('.'+ id).remove();
		});
    });

    function cadastrarMaquininha(){

        var form = $(document).find('#form-stone_cadastro');
        var dados = form.serialize();
        console.log(dados);
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        if($(document).find('input[name=vinculo]:checked', '#form-stone_cadastro').val() == 'fechado' && !$(document).find('input[name^="identificacao_caixa"]').val()){
            message('Atenção', 'Para vínculo fechado é necessário adicionar vínculo.');
            return;
        }

        $.ajax({
            url: '{{ route("stone_cadastro.configuracao_salvar") }}',
            method: 'POST',
            data: dados,
            success: function(data){
                if(data.status == 'sucess'){
                    $(document).find('#configurar-maquininha').modal('hide');
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

    function excluirVinculo($id,$excluir){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('stone_cadastro.excluir_vinculo') }}',
            success: function(data){
                message('Atenção!', data.message);
                $(document).find('.'+$excluir).remove();
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

</script>
@endsection