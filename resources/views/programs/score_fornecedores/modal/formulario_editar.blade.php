@extends('layouts.page-dialog')

@section('content')
<form action="{{ route("score_fornecedores.salvar") }}" method="post" id="form_score_fornecedor" name="form_score_fornecedor" onsubmit="return false">
	@csrf
	<ul class="nav nav-tabs" id="editar_score" role="tablist">
        {{ Form::hidden('id_nota', $id) }}
        @foreach ($formulario as $key_grupo => $menu)
            <li class="nav-item">
                <a class="nav-link @if($active_menu == true) active @endif" id="{{ $menu['id'] }}-tab" data-toggle="tab" href="#{{ $menu['id'] }}-show" role="tab" aria-controls="{{ $key_grupo }}">{{ $key_grupo }}</a>
            </li>
			{{ $active_menu = false }}
        @endforeach
	</ul>

	<div class="tab-content" id="ScoreFornecedor">
		
		@foreach ($formulario as $key => $item)

			<div class="tab-pane @if($active == true) active @endif" id="{{ $item['id'] }}-show" role="tabpanel" aria-labelledby="{{ $item['id'] }}-tab">
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
										{{ Form::radio('resposta['.$pergunta['id'].']', '00', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '00') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '00', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '01', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '01') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '01', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '02', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '02') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '02', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '03', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '03') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '03', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '04', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '04') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '04', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '05', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '05') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '05', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '06', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '06') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '06', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '07', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '07') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '07', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '08', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '08') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '08', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '09', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '09') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '09', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', '10', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == '10') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], '10', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Classificação')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Ótimo', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Ótimo') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Ótimo', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Bom', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Bom') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Bom', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Aceitável', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Aceitável') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Aceitável', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Ruim', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Ruim') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Ruim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Péssimo', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$pergunta['id'], (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Péssimo') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Péssimo', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Sim - Não')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input',(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Sim') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input',(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Não') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
								</div>
							
							@elseif($item['tipo_resposta'][$key_pergunta] == 'Mês - Ano')

								<div class='row mb-2'>
									<div class="col-lg-3">
										{{ Form::text('resposta['.$pergunta['id'].']', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta)) ? $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta : "",['id' => 'resposta['.$pergunta['id'].']', 'class' => 'data_month', 'placeholder' => 'MM/AAAA','maxlength' => '20']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Número')

								<div class='row mb-2'>
									<div class="col-lg-3">
										{{ Form::number('resposta['.$pergunta['id'].']', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta)) ? $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta : '', ['min' => '0', 'max' => '100000', 'class' => 'form-control']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Geral')

								<div class='row mb-2'>
									<div class="col-lg-3">
										{{ Form::text('resposta['.$pergunta['id'].']', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta)) ? $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta : '', ['maxlength' => '150', 'class' => 'form-control']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Frete')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Cif', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Cif') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Cif', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Fob', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Fob') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Fob', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Instabilidade Preço')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Conf. Mercado', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Conf. Mercado') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Conf. Mercado', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Aumento Constante', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Aumento Constante') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Aumento Constante', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Tabela Especial MN', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Tabela Especial MN') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Tabela Especial MN', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Categoria - Produto')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Tecido', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Tecido') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Tecido', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Aviamento', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Aviamento') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Aviamento', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Produto Acabado', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Produto Acabado') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Produto Acabado', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Uso e Consumo', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Uso e Consumo') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Uso e Consumo', ['class'=>'form-check-label']) }}
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Ficha Técnica')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Não') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3 div_file_ficha_tecnica{{$pergunta['id']}} d-none">
										{{ Form::file('file['.$pergunta['id'].']', ['id'=>'file['.$pergunta['id'].']'], null) }}
									</div>
									<div class="form-check ml-3">
										
										@if(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não')
											{{ Form::hidden('file_has_ficha_tecnica['.$pergunta['id'].']', 'true') }}
											<a href='{{  Storage::url($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) }}'  class="btn-download thumb ml-1 mt-1" target="_blank"></a>Download Ficha Técnica Cadastrada
										@endif
									</div>
								</div>

							@elseif($item['tipo_resposta'][$key_pergunta] == 'Certificado Iso')

								<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', '', ['class' => 'form-check-input', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Não') ? 'checked' : '']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3 div_certificado_iso{{$pergunta['id']}} d-none">
										{{ Form::textarea('iso['.$pergunta['id'].']', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não') ? $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta : '', ['class' => 'form form-control mt-2', 'id' => 'iso['.$pergunta['id'].']', 'col' => '5', 'rows' => '4', 'maxlength' => '200', 'placeholder' => 'Coloque aqui os certificados.']) }}
									</div>
								</div>

								
							@elseif($item['tipo_resposta'][$key_pergunta] == 'ABVTEX')

							<div class='row mb-2'>
									<div class="form-check ml-3 ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Sim', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não') ? 'checked' : '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3">
										{{ Form::radio('resposta['.$pergunta['id'].']', 'Não', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Não') ? 'checked' : '', ['class' => 'form-check-input']) }}
										{{ Form::label('resposta_s'.$pergunta['id'], 'Não', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check ml-3 div_abvtex{{$pergunta['id']}} {{ (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não') ? '' : "d-none" }}">
										<div class="form-check ml-3 ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Básico', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Básico') ? 'checked' : '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Básico', ['class'=>'form-check-label']) }}
										</div>
										<div class="form-check ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Bronze', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Bronze') ? 'checked' : '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Bronze', ['class'=>'form-check-label']) }}
										</div>
										<div class="form-check ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Prata', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Prata') ? 'checked' : '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Prata', ['class'=>'form-check-label']) }}
										</div>
										<div class="form-check ml-3">
											{{ Form::radio('resposta_abvtex['.$pergunta['id'].']', 'Ouro', (isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Ouro') ? 'checked' : '', ['class' => 'form-check-input']) }}
											{{ Form::label('resposta_s'.$pergunta['id'], 'Ouro', ['class'=>'form-check-label']) }}
										</div>
									</div>
								</div>
							
							@endif

						</div>
					</div>
				</div>

				@endforeach

				@if($item['id_proximo'])
					<div class="row">
						<div class="col-sm-12">
							{{ Form::button('Próximo', ['class' => 'btn btn-info float-right', 'id' => 'btn_proximo_'.$item['id_proximo']])}}
						</div>
					</div>
				@else
					<div class="row">
						<div class="col-sm-12">
							{{ Form::submit('Salvar', ['class' => 'btn btn-success float-right mb-3', "id"=>"bt_salvar"]) }}
						</div>
					</div>
				@endif

			</div>

		@endforeach
	</div>
    
</form>
<script type="text/javascript">

	$(function(){
		$(document).find(".data_month").mask("99/9999");
		$(document).find(".data_month").datepicker({
			language: 'pt-BR',
			format: 'mm/yyyy',
			endDate: new Date(),
			zIndex: 100,
			autoHide: true
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
				
					@if(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não')
						$(document).find('#form_score_fornecedor').find('.div_file_ficha_tecnica{{ $pergunta['id']}}').removeClass('d-none');
					@endif

					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').off("click");
					$(document).find('#form_score_fornecedor').find('input[name="resposta[{{ $pergunta['id'] }}]"]').on("click", function(){
						if($(this).val() == "Sim"){
							$(document).find('#form_score_fornecedor').find('.div_file_ficha_tecnica{{ $pergunta['id']}}').removeClass('d-none');
						}else{
							$(document).find('#form_score_fornecedor').find('.div_file_ficha_tecnica{{ $pergunta['id']}}').addClass('d-none');
						}
					});

				@endif

				@if($item['tipo_resposta'][$key_pergunta] == 'Certificado Iso')

					@if(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não')
						$(document).find('#form_score_fornecedor').find('.div_certificado_iso{{ $pergunta['id']}}').removeClass('d-none');
					@endif

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
						console.log($(this).val());
						if($(this).val() == "Sim"){
							$(document).find('#form_score_fornecedor').find('.div_abvtex{{ $pergunta['id']}}').removeClass('d-none');
						}else if($(this).val() == "Não"){
							$(document).find('#form_score_fornecedor').find('.div_abvtex{{ $pergunta['id']}}').addClass('d-none');
						}
					});
				@endif
				
				@if($item['tipo_resposta'][$key_pergunta] == 'Mês - Ano')
					$(document).find(".data_month").mask("99/9999");
					$(document).find(".data_month").datepicker({
						language: 'pt-BR',
						format: 'mm/yyyy',
						endDate: new Date(),
						zIndex: 100,
						autoHide: true
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

			var erro = 0;

			$(document).find('[id^="documento["]').each(function(index){
				if($(this)[0].files[0] != undefined && $(this)[0].files[0].size > 2097152){
					showErrorsInputs(form, 'documento['+ (index+1) +']', 'O arquivo deve ser menor que 2MB');
					erro++;
				}

			});

			if(erro > 0){
				return null;
			}

	        var url = form.attr("action");
	        $.ajax({
	            url: url,
	            dataType: 'json',
				data: form_data,
				processData: false,
				contentType: false,
	            method: 'POST',
	            success: function(callback){
					filtro();
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
					var count = 0;
					
					form.find('.error-message').remove();
					form.find('div, input, radio,hidden').each(function(){
						if($(this).hasClass("is-invalid")){
							$(this).removeClass("is-invalid")
						}
						if($(this).hasClass("error-input")){
							$(this).removeClass("error-input")
						}
					});
					
					for(var field in errors){
						motrarErrosInputs(form, field, errors[field]);
					}
					if(form.find('.error-message').length){
						var id_tab = form.find('.error-message').eq(0).parents(".tab-pane").attr("aria-labelledby");
						$(document).find("#"+id_tab).tab('show');
						form.find('.error-message').eq(0).focus();
					}
				}
	        });
		});
	});

	function motrarErrosInputs(form, input, message){
		var inputexplode = input.split(".");

        if(inputexplode[0] == 'resposta'){
            var id_pergunta = 'erro-perunta-pesquisa-'+inputexplode[1];
            var $input = $(document).find("#"+id_pergunta).parent();
            $input.after("<label class='col-md-12 error-message' for='"+input+"'>"+message+"</label>");
        }else if(inputexplode[0] == 'iso'){
			var id_pergunta = 'erro-perunta-pesquisa-'+inputexplode[1];
            var $input = $(document).find("#"+id_pergunta).parent();
            $input.after("<label class='col-md-12 error-message' for='"+input+"'>"+message+"</label>");
		}else if(inputexplode[0] == 'resposta_abvtex'){
			var id_pergunta = 'erro-perunta-pesquisa-'+inputexplode[1];
            var $input = $(document).find("#"+id_pergunta).parent();
            $input.after("<label class='col-md-12 error-message' for='"+input+"'>"+message+"</label>");
		}else if(inputexplode[0] == 'file'){
			var id_pergunta = 'erro-perunta-pesquisa-'+inputexplode[1];
            var $input = $(document).find("#"+id_pergunta).parent();
            $input.after("<label class='col-md-12 error-message' for='"+input+"'>"+message+"</label>");
		}

    }

</script>
@endsection
