@extends('layouts.page-dialog')

@section('content')
<form action="{{ route("score_fornecedor_nota.salvar") }}" method="post" id="form_score_fornecedor" name="form_score_fornecedor" onsubmit="return false">
	@csrf
	<ul class="nav nav-tabs" id="menu_score" role="tablist">
		{{ Form::hidden('array_lancamento', $array_lancamento) }}
        @foreach ($formulario as $key_grupo => $menu)
            <li class="nav-item">
                <a class="nav-link @if($active_menu == true) active @endif" id="{{ $menu['id'] }}-tab" data-toggle="tab" href="#{{ $menu['id'] }}aba" role="tab" aria-controls="{{ $key_grupo }}">{{ $key_grupo }}</a>
            </li>
			{{ $active_menu = false }}
        @endforeach
		<li class="nav-item">
			<a class="nav-link" id="documentos-tab" data-toggle="tab" href="#cliente_novo_documentos" role="tab" aria-controls="cliente_novo_documentos" aria-selected="false">Documentos</a>
		</li>
	</ul>

	<div class="tab-content" id="ScoreFornecedor">
		
		@foreach ($formulario as $key => $item)

			<div class="tab-pane @if($active == true) active @endif" id="{{ $item['id'] }}aba" role="tabpanel" aria-labelledby="{{ $item['id'] }}-tab">
			{{ $active = false }}

			@foreach ($item['pergunta'] as $key_pergunta => $pergunta)
				<div class="content-tab">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="col-md-12">
									{{ $pergunta['pergunta'] }}
								</div>
								{{ Form::hidden('pergunta['.$pergunta['id'].']', $pergunta['pergunta']) }}
								{{ Form::hidden('grupo_pergunta['.$pergunta['id'].']', $key) }}
								{{ Form::hidden('tipo_resposta['.$pergunta['id'].']', $pergunta['tipo_resposta_id']) }}
								<div id="{{'erro-perunta-pesquisa-'.$pergunta['id']}}">
								</div>
							</div>
							@if($item['tipo_resposta'][$key_pergunta] == 'Pontuação')
								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '00', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '00', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '01', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '01', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '02', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '02', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '03', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '03', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '04', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '04', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '05', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '05', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '06', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '06', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '07', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '07', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '08', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '08', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '09', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '09', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '10', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '10', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Classificação')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Ótimo', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Ótimo', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Bom', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Bom', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Aceitável', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Aceitável', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Ruim', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Ruim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Péssimo', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Péssimo', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Sim - Não')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Mês - Ano')

								<div class='row mb-2'>
									<div class="col-lg-3">
										{{ Form::text('resposta['.$pergunta['id'].']', '',['id' => 'resposta['.$pergunta['id'].']', 'class' => 'data_month', 'placeholder' => 'MM/AAAA','maxlength' => '20']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Número')

								<div class='row mb-2'>
									<div class="col-lg-3">
										{{ Form::number('resposta['.$pergunta['id'].']', '', ['min' => '0', 'max' => '100000', 'class' => 'form-control']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Geral')

								<div class='row mb-2'>
									<div class="col-lg-3">
										{{ Form::text('resposta['.$pergunta['id'].']', '', ['maxlength' => '150', 'class' => 'form-control']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Frete')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Cif', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Cif', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Fob', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Fob', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Instabilidade Preço')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Conf. Mercado', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Conf. Mercado', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Aumento Constante', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Aumento Constante', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Tabela Especial MN', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Tabela Especial MN', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Categoria - Produto')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Tecido', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Tecido', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Aviamento', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Aviamento', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Produto Acabado', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Produto Acabado', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Uso e Consumo', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Uso e Consumo', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Ficha Técnica')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3 div_file_ficha_tecnica{{$pergunta['id']}} d-none">
										{{ Form::file('file['.$pergunta['id'].']', ['id'=>'file['.$pergunta['id'].']'], null) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Certificado Iso')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3 div_certificado_iso{{$pergunta['id']}} d-none">
										{{ Form::textarea('iso['.$pergunta['id'].']', '', ['class' => 'form form-control mt-2', 'id' => 'iso['.$pergunta['id'].']', 'col' => '5', 'rows' => '4', 'maxlength' => '200', 'placeholder' => 'Coloque aqui os certificados.']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'ABVTEX')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3 div_abvtex{{$pergunta['id']}} d-none">
										<div class="form-check ml-3 ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Básico', '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Básico', ['class'=>'form-check-label']) }}
										</div>
										<div class="form-check ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Bronze', '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Bronze', ['class'=>'form-check-label']) }}
										</div>
										<div class="form-check ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Prata', '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Prata', ['class'=>'form-check-label']) }}
										</div>
										<div class="form-check ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Ouro', '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Ouro', ['class'=>'form-check-label']) }}
										</div>
									</div>
								</div>

							@endif

						</div>
					</div>
				</div>

				@endforeach
				{{ Form::button('Próximo', ['class' => 'btn btn-info float-right', 'id' => 'btn_proximo_documento'])}}

			</div>

		@endforeach

		<div class="tab-pane" id="cliente_novo_documentos" role="tabpanel" aria-labelledby="documentos-tab">
			<div class="content-tab">
				<div class="row">
					<div class="col-md-12">
						<h3><center>Anexar Documentos</center></h3>
					</div>
				</div>
				<div class="adicionar-elemento">
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						{{ Form::button('Adicionar Documento', ['class' => 'btn btn-success', 'id' => 'btn_adicionar_documento']) }}
					</div>
				</div>
			</div>
			{{ Form::submit('Salvar', ['class' => 'btn btn-success float-right mb-3', "id"=>"bt_salvar"]) }}
		</div>

	</div>
    
</form>
<script type="text/javascript">
	$(function(){
		$(document).find('#form_score_fornecedor').find("#btn_proximo_documento").off("click");
		$(document).find('#form_score_fornecedor').find("#btn_proximo_documento").on("click", function(){
			$(document).find('#documentos-tab').tab('show');
		});

		@foreach ($formulario as $key => $item)
			@if($item['id_proximo'])
				$(document).find('#form_score_fornecedor').find("#btn_proximo_{{ $item['id_proximo'] }}").off("click");
				$(document).find('#form_score_fornecedor').find("#btn_proximo_{{ $item['id_proximo'] }}").on("click", function(){
					$(document).find('#{{ $item['id_proximo'] }}-tab').tab('show');
				});
			@endif
			@foreach ($item['pergunta'] as $key_pergunta => $pergunta)
				@if($item['tipo_resposta'][$key_pergunta] == 'Ficha Técnica')
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').off("click");
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').on("click", function(){
						if($(this).val() == "Sim"){
							$(document).find('#form_score_fornecedor').find('.div_file_ficha_tecnica{{ $pergunta['id']}}').removeClass('d-none');
						}else{
							$(document).find('#form_score_fornecedor').find('.div_file_ficha_tecnica{{ $pergunta['id']}}').addClass('d-none');
						}
					});
				@endif
				@if($item['tipo_resposta'][$key_pergunta] == 'Mês - Ano')
					$("#form_score_fornecedor").find(".data_month").mask("99/9999");
					$("#form_score_fornecedor").find(".data_month").datepicker({
						language: 'pt-BR',
						format: 'mm/yyyy',
						endDate: new Date(),
						zIndex: 100,
						autoHide: true
					});
				@endif
				@if($item['tipo_resposta'][$key_pergunta] == 'Certificado Iso')
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').off("click");
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').on("click", function(){
						if($(this).val() == "Sim"){
							$(document).find('#form_score_fornecedor').find('.div_certificado_iso{{ $pergunta['id']}}').removeClass('d-none');
						}else{
							$(document).find('#form_score_fornecedor').find('.div_certificado_iso{{ $pergunta['id']}}').addClass('d-none');
							$(document).find('#form_score_fornecedor').find('input[name="iso[{{ $pergunta['id'] }}]"]').val('');
						}
					});
				@endif
				@if($item['tipo_resposta'][$key_pergunta] == 'ABVTEX')
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').off("click");
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').on("click", function(){
						if($(this).val() == "Sim"){
							$(document).find('#form_score_fornecedor').find('.div_abvtex{{ $pergunta['id']}}').removeClass('d-none');
						}else if($(this).val() == "Não"){
							$(document).find('#form_score_fornecedor').find('.div_abvtex{{ $pergunta['id']}}').addClass('d-none');
						}
					});
				@endif
			@endforeach
		@endforeach

		$(document).find('#form_score_fornecedor').find("#bt_salvar").off('click');
		$(document).find('#form_score_fornecedor').find("#bt_salvar").on('click', function(event){
	
			var form = $(document).find('#form_score_fornecedor');
			var form_data = new FormData(form[0]);

			form.find('.error-message').remove();
			form.find('div, input, select').each(function(){
				if($(this).hasClass("is-invalid")){
					$(this).removeClass("is-invalid")
				}
				if($(this).hasClass("error-input")){
					$(this).removeClass("error-input")
				}
			});

	        var url = form.attr("action");
			
	        $.ajax({
	            url: url,
	            dataType: 'json',
				data: form_data,
				processData: false,
				contentType: false,
	            method: 'POST',
	            success: function(callback){
					filterDados();
	                if(callback.status === "success"){
	                	$(document).find('#form_score_fornecedor').parents(".modal").modal("hide");
	                	message("Atenção", callback.message);
	                } else {
	                	message("Atenção", callback.message);
	                }
	            },
	            error: function(callback){
					hide_loader();
					
					var errors = callback.responseJSON.error;
					
					for(var field in errors){
						var inputexplode = field.split(".");

						if(inputexplode[0] == 'pergunta'){	
							message('Atenção', 'Escolha pelo menos uma resposta.');
							break; 
						}else{
							showErrorsInputs(form, field, errors[field]);
						}
					}
				}
	        });
		});

		var x = 1;
		var max_fields = 20;

		$('#btn_adicionar_documento').click (function(e){
			e.preventDefault(); 
			if (x < max_fields)
			{
                var anterior = 0;
				var conteudo = '<div id="adicionar-documento-div-'+x+'" class="remove'+x+'">'+
					'<div class="row">'+
						'<div class="form-group col-md-3">'+
							'{{ Form::label("descricao_documento", "Descrição Documento") }}'+
							'<input type="text" id="descricao_documento['+x+']" name="descricao_documento['+x+']" class="form-control campo_descricao'+x+'"  placeholder="Escreva o Documento" maxlength="50">'+
						'</div>'+
						'<div class="form-group col-md-3">'+
							'{{ Form::label("label_documento", "Documento") }}'+
							'<input type="file" id="documento['+x+']" name="documento['+x+']" class="form-control campo_arquivo'+x+'">'+
						'</div>'+
						'<div class="form-group col-md-3">'+
							'<br>'+
							'<input type="button" id="remove'+x+'" class="form-group btn btn-danger remove_documento" value="Remover">'+
						'</div>'+
					'</div>'+
				'</div>';

				if(x > 1){
                    anterior = x - 1;
                    for(var i = 1; i < x; i++){
                        var descricao = $(".campo_descricao"+i).val();
                        var documento = $(".campo_arquivo"+i).val();
                        
                        if(descricao == '' || documento == ''){
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

		$('.adicionar-elemento').on("click",".remove_documento",function(e) {
			e.preventDefault();
			var anterior = x - 2;
            $(document).find("#remove"+anterior).show();
            if(x > 1){
                x --; 
            }
			var id = $(this).attr('id');
			$('.'+ id).remove();
		});
	});

function showErrorsInputs(form, input, message){

	var inputexplode = input.split(".");
	console.log(inputexplode);
	if(inputexplode){
		if(inputexplode[1]){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
		}else{
			input = inputexplode[0]+"[1]";
		}
		console.log(input);
		message = message;
		var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
		$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
	}
	
	if(input === 'documento'){
		input = inputexplode[0]+"[]";
		var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
		$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
	}
}
</script>
@endsection
