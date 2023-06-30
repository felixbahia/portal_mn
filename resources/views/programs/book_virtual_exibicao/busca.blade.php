@extends('layouts.app-lista-preco')
@section('content')
<div>
    <i><a class="link-book-virtual" href="{{ route('book_virtual_exibicao.index') }}"> << Voltar para a lista de segmentos</a> </i>
</div>
<div class="row justify-content-left border busca-book">
    <div id='busca_avancada' class="col mt-1 pl-1 bt-view">
        Busca
    </div>
</div>
<div class='float-right col-lg-1'>
    {!! $visualizar_carrinho !!}&nbsp&nbsp
    {!! $contador !!}
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
                        {!! Form::select('categoria', $categorias, $categoria, ['id' => 'categoria', 'class' => 'form-control', 'placeholder' => 'Categoria']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('segmentos_id', 'Segmento') !!}
                        {!! Form::select('segmentos_id', $segmentos, $segmentos_id, ['id' => 'segmentos_id', 'class' => 'form-control', 'placeholder' => 'Segmento']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('familias_id', 'Acabamento') !!}
                        {!! Form::select('familias_id', $familias, $familias_id, ['id' => 'familias_id', 'class' => 'form-control', 'placeholder' => 'Acabamento']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('tipo_material', 'Tipo de Material') !!}
                        {!! Form::select('tipo_material', $tipos_materiais, $tipo_material, ['id' => 'tipo_material', 'class' => 'form-control', 'placeholder' => 'Tipo de material']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('tipo_genero', 'Gênero') !!}
                        {!! Form::select('tipo_genero', $tipo_generos, $tipo_genero, ['id' => 'tipo_genero', 'class' => 'form-control', 'placeholder' => 'Gênero']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('gramatura_tipo', 'Gramatura') !!}
                        {!! Form::select('gramatura_tipo', $tipo_gramaturas, $gramatura_tipo, ['id' => 'gramatura_tipo', 'class' => 'form-control', 'placeholder' => 'Tipo de gramatura']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('composicao', 'Composição') !!}
                        {!! Form::select('composicao', $composicaos, $composicao, ['id' => 'composicao', 'class' => 'form-control', 'placeholder' => 'Tipo de Composição']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('construcao', 'Construção') !!}
                        {!! Form::select('construcao', $construcaos, $construcao, ['id' => 'construcao', 'class' => 'form-control', 'placeholder' => 'Construção']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('sazonal', 'Sazonalidade') !!}
                        {!! Form::select('sazonal', $sazonalidades, $sazonal, ['id' => 'sazonal', 'class' => 'form-control', 'placeholder' => 'Sazonalidade']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('num_book', 'Número do book') !!}
                        {!! Form::text('num_book', $num_book, ['id' => 'num_book', 'class' => 'form-control', 'placeholder' => 'Número do Book']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('artigo', 'Número do artigo') !!}
                        {!! Form::text('artigo', $artigo, ['id' => 'artigo', 'class' => 'form-control', 'placeholder' => 'Número do artigo']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('artigo', 'Nome do artigo') !!}
                        {!! Form::text('nome', $nome, ['id' => 'nome', 'class' => 'form-control', 'placeholder' => 'Nome do artigo']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('codigo_produto', 'Código do Produto') !!}
                        {!! Form::text('codigo_produto', $codigo_produto, ['id' => 'cod_produto', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) !!}
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
                        {!! Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('marca', 'Marca') !!}
                        {!! Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('linha', 'Linha') !!}
                        {!! Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('descricao', 'Descrição do produto') !!}
                        {!! Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Descrição do produto']) !!}
                    </div>
                    <div class='form-field busca_avancada_campo'>
                        {!! Form::label('codigo_produto', 'Código do produto') !!}
                        {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código do produto']) !!}
                    </div>
                    <div>
                        {!! Form::button('Buscar', ['id' => 'buscar_produto', 'class' => 'btn btn-success']) !!}
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="content-table">
    <div class="row justify-content-center" id='div-cards'>
    </div>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        
        filterAjax($("#form_filter").serialize());

        $(document).find('#buscar').on("click", function(){
            filterAjax($("#form_filter").serialize());
            $(document).find('#busca_avancada_expandir').slideToggle();
        });

        $(document).find('#btn-clearform').on('click', function(){
            $(document).find('#div-cards').html('');
        });

        tamanho_tabela_cards();

        $(window).resize(function(){
            tamanho_tabela_cards();
        });
        
        $(document).find('#busca_avancada').click(function(){
            $(document).find('#busca_avancada_expandir').slideToggle();
        });
		$(document).on("click", function(e){
			var $conent_autocomplete = $(document).find(".ui-autocomplete");
			var $conent_autocomplete2 = $(document).find(".ui-menu-item-wrapper");
			var $conent_busca = $(document).find("#busca_avancada");
            if(
				!$(e.target).is($conent_autocomplete) &&
				!$(e.target).is($conent_autocomplete2) &&
				!$(e.target).is($conent_busca) &&
				$(e.target).parents("#busca_avancada_expandir").length == 0 &&
				$(document).find("#busca_avancada_expandir:visible").length == 1
			){
				$(document).find("#busca_avancada_expandir").slideUp();
			}
        });

        $(document).find('#buscar_produto').on('click', function(){
            buscaProduto();
        })

        $(document).find('#grupo').autocomplete(autoCompleteOptions('grupo'));
        $(document).find('#marca').autocomplete(autoCompleteOptions('marca'));
        $(document).find('#linha').autocomplete(autoCompleteOptions('linha'));
        $(document).find('#descricao').autocomplete(autoCompleteOptions('descricao'));
        $(document).find('#codigo_produto').autocomplete(autoCompleteOptions('codigo_produto'));

        $(document).find(".bt-carrinho-book").off("click");
        $(document).find(".bt-carrinho-book").on("click", function(event){
            event.stopPropagation();
            modalVisualizarCarrinho($(this));
        });
    });

    function tamanho_tabela_cards(){
        var position = $(document).find('#div-cards').offset();
        var bottom = window.scrollY + window.innerHeight;

        console.log(position.top, bottom, bottom - position.top);

        $(document).find('#div-cards').css('max-height', bottom - position.top + "px");
    }

    function filterAjax(data_form){
        var $return;
        $(document).find('#div-cards').html('');
        $.ajax({
            url: "{{ route('book_virtual_exibicao.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var data = callback.response;
                    var fields_filter = '';
                    for(var field in data){
                        fields_filter += data[field];
                    }
                    $(document).find('#div-cards').append(fields_filter);
                }
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

    function autoCompleteOptions($name){

        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.campo = $name;
                request.filter = $(document).find('#form_filter_produto').serialize();
                $.post("{{ route('book_virtual_exibicao.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
        };
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('is-invalid');
	}

    function modalVisualizarCarrinho($this){
        var title = 'Editar Carrinho '+$($this).data("cliente");
        var book_id = $($this).data("book_id");
        var filtro = $($this).data("filtro");
        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao.modal.editar') }}',
            data: {
                _token: "{{ csrf_token() }}",
                book_id: book_id,
                filtro: filtro
            },
            method: 'POST',
            success: function(body){
                if(body.status === 'success'){
                    if(body.response.itens === false){
                        message("Atenção", "Seu carrinho esta vazio!");
                    }
                }else{
                    createModal("modal_editar_carrinho", title, body, 'modal-lg');
                }
            }
        });
    }   
@endsection
