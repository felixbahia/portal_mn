@extends('layouts.app-lista-preco')

@section('content')
<div>
    @if(isset($filtro) && isset($filtro))
        <i><a class="link-book-virtual" href="{{ route('book_virtual_exibicao', ['categoria' => $categoria, 'filter' => $filtro]) }}"> << Voltar para a lista de books virtuais</a> </i>
    @else
        <i><a class="link-book-virtual" href="{{ route('book_virtual_exibicao.index') }}"> << Voltar para a tela principal</a> </i>
    @endif
</div>

<div id='pagina'>
    <div id='titulos-book'>
        <div class='mx-auto text-center'>
            <h4>Nenhum produto encontrado</h4>
        </div>
    </div>
    <div class="row justify-content-left border busca-book">
        <div id='busca_avancada' class="col mt-1 pl-1 bt-view">
            Escolha uma categoria ou faça uma busca
        </div>
    </div>
    <div id='busca_avancada_expandir' class="row justify-content-left border busca-book-avancada">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="busca-book-tab" data-toggle="tab" href="#busca-book" role="tab" aria-controls="busca-book" aria-selected="true">Por book</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="busca-produto-tab" data-toggle="tab" href="#busca-produto" role="tab" aria-controls="busca-produto" aria-selected="false">Por Item</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="busca-book" role="tabpanel" aria-labelledby="busca-book">
                <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
                    @csrf
                    <div class="col">
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('categoria_id', 'Categoria') !!}
                            {!! Form::select('categoria', $categorias, '', ['id' => 'categoria_id', 'class' => 'form-control', 'placeholder' => 'Categoria']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('segmentos_id', 'Segmento') !!}
                            {!! Form::select('segmentos_id', $segmentos, '', ['id' => 'segmentos_id', 'class' => 'form-control', 'placeholder' => 'Segmento']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('tipo_material', 'Tipo de Material') !!}
                            {!! Form::select('tipo_material', $tipos_materiais, '', ['id' => 'tipo_material', 'class' => 'form-control', 'placeholder' => 'Tipo de material']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('num_book', 'Número do book') !!}
                            {!! Form::text('num_book', '', ['id' => 'num_book', 'class' => 'form-control', 'placeholder' => 'Número do Book']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('artigo', 'Número do artigo') !!}
                            {!! Form::text('artigo', '', ['id' => 'artigo', 'class' => 'form-control', 'placeholder' => 'Número do artigo']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('artigo', 'Nome do artigo') !!}
                            {!! Form::text('nome', '', ['id' => 'nome', 'class' => 'form-control', 'placeholder' => 'Nome do artigo']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('codigo_produto', 'Código do Produto') !!}
                            {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) !!}
                        </div>
                        <div>
                            {!! Form::button('Buscar', ['id' => 'buscar', 'class' => 'btn btn-success']) !!}
                        </div>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="busca-produto" role="tabpanel" aria-labelledby="busca-produto-tab">
                <form action="#" name="form_filter_produto" id="form_filter_produto" onsubmit="return false;">
                    @csrf
                    <div class="col">
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('grupo', 'Grupo') !!}
                            {!! Form::text('grupo', $filtros['grupo'], ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('marca', 'Marca') !!}
                            {!! Form::text('marca', $filtros['marca'], ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('linha', 'Linha') !!}
                            {!! Form::text('linha', $filtros['linha'], ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('descricao', 'Descrição do produto') !!}
                            {!! Form::text('descricao', $filtros['descricao'], ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Descrição do produto']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('codigo_produto', 'Código do produto') !!}
                            {!! Form::text('codigo_produto', $filtros['codigo_produto'], ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código do produto']) !!}
                        </div>
                        <div>
                            {!! Form::button('Buscar', ['id' => 'buscar_produto', 'class' => 'btn btn-success']) !!}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $(document).find('#busca_avancada').click(function(){
            $(document).find('#busca_avancada_expandir').slideToggle();
        });

        $(document).find('#buscar').on('click', function(){
            buscaAvancada();
        })

        $(document).find('#buscar_produto').on('click', function(){
            buscaProduto();
        })
    });

    function buscaAvancada(){

        data_form = $(document).find('#form_filter').serialize();

        $.ajax({
            url: "{{ route('book_virtual_exibicao.gerar_link_busca') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            complete: function(){
                $(document).find('#busca_avancada_expandir').slideToggle();
            },
            success: function(data){
                window.location.assign(data.response.link)
            }
        });
    }

    function buscaProduto(){

        form = $(document).find('#form_filter_produto');
        data_form = form.serialize();

        $.ajax({
            url: "{{ route('book_virtual_exibicao.gerar_link_busca_produtos') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            complete: function(){
            },
            success: function(data){
                $(document).find('#busca_avancada_expandir').slideToggle();
                window.location.assign(data.response.link);
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form.find('.error-message').remove();
                form.find('.error-input').removeClass('error-input')
                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('is-invalid');
	}
@endsection