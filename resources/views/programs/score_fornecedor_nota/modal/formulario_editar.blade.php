@extends('layouts.page-dialog')

@section('content')
<form action="{{ route("score_fornecedor_nota.salvar") }}" method="post" id="form_score_fornecedor" name="form_score_fornecedor" onsubmit="return false">
	@csrf
	<ul class="nav nav-tabs" id="editar_score" role="tablist">
		{{ Form::hidden('array_lancamento', $array_lancamento) }}
        @foreach ($formulario as $key_grupo => $menu)
            <li class="nav-item">
                <a class="nav-link @if($active_menu == true) active @endif" id="{{ $menu['id'] }}-tab" data-toggle="tab" href="#{{ $menu['id'] }}-show" role="tab" aria-controls="{{ $key_grupo }}">{{ $key_grupo }}</a>
            </li>
			{{ $active_menu = false }}
        @endforeach
		<li class="nav-item">
			<a class="nav-link" id="documentos-tab" data-toggle="tab" href="#cliente_novo_documentos" role="tab" aria-controls="cliente_novo_documentos" aria-selected="false">Documentos</a>
		</li>
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
			<div class="row">
				<div class="col-md-12">
					{{ Form::submit('Salvar', ['class' => 'btn btn-success float-right mb-3', "id"=>"bt_salvar"]) }}
				</div>
			</div>
			@if(!empty($documentos))
				<br>
				<div class="row">
					<div class="col-md-12">
						<h3><center>Anexar Documentos</center></h3>
					</div>
					<div class="content-table col-md-12">
						<table class="table table-striped table-not-edit table-not-view col-md-12" id="table-documentos">
							<thead class="col-md-12">
								<tr>
									<th>Descrição</th>
									<th>Documento</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								@foreach($documentos as $documento)
									<tr>
										<td>
											<div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $documento['descricao'] }}">{{ $documento['descricao'] }}</div></div>
										</td>
										<td>
											@if(substr($documento['documento'],strlen($documento['documento'])-3) == 'PNG' || substr($documento['documento'],strlen($documento['documento'])-3) == 'png' || substr($documento['documento'],strlen($documento['documento'])-3) == 'jpg' || substr($documento['documento'],strlen($documento['documento'])-3) == 'JPG')
												<a href="{{ $documento['documento'] }}"  class="thumb" data-toggle="popover" data-trigger="hover" aria-readonly="true" title="Documento" data-content="<img src='{{ $documento['documento'] }}' width='250' class='rounded mx-auto d-block' alt='Documento'>"><i class="btn-foto-canhoto"></i>Documento</a>
											@else
												<a href="{{ $documento['documento'] }}"  target="blank"><i class="btn-download"></i>Documento</a>
											@endif
										</td>
										<td>
											<a href="#" onclick="excluirDocumento('{{ $documento['id_documento'] }}')"  class='btn btn-danger deletaBtn'>Exluir</a>
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			@endif
		</div>

	</div>
    
</form>
<script type="text/javascript">

	$(function(){
		$(document).find('#form_score_fornecedor').find("#btn_proximo_documento").off("click");
		$(document).find('#form_score_fornecedor').find("#btn_proximo_documento").on("click", function(){
			$(document).find('#documentos-tab').tab('show');
		});
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

		setTimeout(function(){
			table_documentos.draw();
		}, 3000);
	});


table_documentos = $('#table-documentos')
	.on( 'error.dt', function ( e, settings, techNote, men ) {
		hide_loader();
		message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
	}).DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"autoWidth": false,
		"pageLength": 15,
		"sScrollY": "200px",
		"bScrollCollapse": true,
		"bPaginate": false,
		"bJQueryUI": true,
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

			$(document).find("a.thumb").fancybox(
				{
					onComplete: function(){
						$('#fancybox-content')
							.on('mouseover', function(){
								$(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
							})
							.on('mouseout', function(){
								$(this).children('#fancybox-img').css({'transform': 'scale(1)'});
							})
							.on('mousemove', function(e){
								$(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
						});
					}
				}
			);

		},
		"aoColumnDefs": [
			{ "sWidth": "10%", "aTargets": [ -1 ] }
		],
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
	}).on("click",".deletaBtn", function(e) {
		e.stop
		var tr = $(this).closest('tr');
		tr.css("background-color","red");
		tr.fadeOut(1000, function(){
			tr.remove();
		});
	});

function showErrorsInputs(form, input, message){
	var inputexplode = input.split(".");
	if(inputexplode){
		if(inputexplode[1]){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
		}else{
			input = inputexplode[0]+"[1]";
		}
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

function excluirDocumento(id_documento){
        $.ajax({
            url: '{{ route('score_fornecedor_nota.deletar_documento') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}', id : id_documento,
            },
            success: function(body){
                if(body.status = 'success'){
                    message("Atenção", "Documento Excluido com Sucesso");
                }else{
                    message("Atenção", "Erro ao processar, tente novamente mais tarde");
                }
            },
            error: function(data){
                message("Atenção", "Erro ao processar, tente novamente mais tarde");
            }
        });
    }
</script>
@endsection
