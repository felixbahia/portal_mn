@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs" id="PedidoOrcamentoTabs" role="tablist">
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
                        <div class='row mb-2'>
                            <div class="form-check ml-3 ml-3">
                                @if($item['tipo_resposta'][$key_pergunta] == 'Ficha Técnica')
                                    @if(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta != 'Não' && !empty($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta))
                                        {{ Form::hidden('file_has['.$pergunta['id'].']', 'true') }}
                                        <a href='{{  Storage::url($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) }}'  class="btn-download thumb ml-1 mt-1" target="_blank"></a>Download Ficha Técnica Cadastrada
                                    @elseif(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) && $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta == 'Não')
                                        Não
                                    @elseif(empty($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta))
                                    @endif
                                @else
                                    {{ Form::label(isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) ? $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta : '' , isset($respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta) ? $respondido->where('pergunta',$pergunta['pergunta'])->first()->resposta : '', ['class'=>'form-check-label']) }}
                                @endif
                            </div>
                        </div>
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
            @endif

        </div>

    @endforeach
</div>
<script type="text/javascript">

	$(function(){
		@foreach ($formulario as $key => $item)
			@if($item['id_proximo'])
				$(document).find("#btn_proximo_{{ $item['id_proximo'] }}").off("click");
				$(document).find("#btn_proximo_{{ $item['id_proximo'] }}").on("click", function(){
					$(document).find('#{{ $item['id_proximo'] }}-tab').tab('show');
				});
			@endif
		@endforeach
    });
</script>
@endsection
