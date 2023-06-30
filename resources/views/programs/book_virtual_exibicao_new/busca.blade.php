@extends('layouts.app-book-virtual')
@section('content')
<div class="content-book">
    <div class="container-sm">
        <form action="#" name="form_grupo" id="form_grupo" onsubmit="return false">
            @csrf
            {{ Form::hidden('busca', 'false', ['id' => 'busca']) }}
            {!! Form::hidden('segmentos_id', $segmentos_id, ['id' => 'segmentos_id']) !!}
            {{ Form::hidden('busca_navegacao', $busca_navegacao, ['id' => 'busca_navegacao']) }}
            <div class="header-book">
                <span id='contador'>&nbsp&nbsp{{ $contador }}</span>
                <i name="btn_carrinho" id="btn_carrinho" data-cliente="{{ $cliente }}" title="Carrinho" class="bt-carrinho-header"></i>
                <div class="form-row">
                    <div class="col-lg-0"> 
                        <a href="{{ route('book_virtual_exibicao_new.index') }}" name="btn_home" id="btn_home" title="Página Incial" class="bt-home-header"></a>
                    </div>
                    <div class="col-lg"> 
                        {!! Form::text('grupo', $grupo, ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo', 'maxlength' => '255']) !!}
                    </div>
                    <div class="col-lg">
                        {{ Form::text('marca', $marca, ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca', 'maxlength' => '255']) }}
                    </div>
                    <div class="col-lg">
                        {{ Form::text('linha', $linha, ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha', 'maxlength' => '255']) }}
                    </div>
                    <div class="col-lg">
                        {{ Form::text('codigo_produto', $codigo_produto, ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código', 'maxlength' => '255']) }}
                    </div>
                    <div class="col-lg"> 
                        {!! Form::select('tipo_venda', $tipo_vendas, $tipo_venda, ['id' => 'forma_pagamento', 'class' => 'form-control', 'placeholder' => 'Tipo Venda']) !!}
                    </div>
                    <div class="col-lg"> 
                        {!! Form::select('campanha', $campanhas, $campanha, ['id' => 'campanha', 'class' => 'form-control', 'placeholder' => 'Selecione a Campanha']) !!}
                    </div>
                    <div class="content">
                        <div class="content-buttons">
                            <button type="button" name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
                            @if(empty($cliente))
                                <button type="button" name="btn-cliente"  data-title="Adicionar Cliente" id="btn-cliente" class="btn-cliente">Cliente</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-book">
                    <a href="javascript:history.back()">{{ CustomView::programaName() }} > </a><strong>{{ $busca_navegacao }}</strong>
                </div>
            </div>
        </div>
        <div class="row justify-content-center" id='div-cards'>
            <div class="clearfix-galeria"></div>
        </div> 
    </div>
</div> 
<div class="footer">
    <div class="container-book">
        <hr/>
        <img src="{{ URL::asset('images/logomn.jpg') }}">
        <span class="span1">
            <i class="bt-map-footer"></i>
            R. Doutor Carlos Botelho, 177/179 - Brás São Paulo, SP
            <span class="span3">
                <i class="bt-map-footer"></i>
                R. Almirante Barroso, 478 - Brás São Paulo, SP
            </span>
            <span class="span2">
                <a href="mailto:contato@tecidosmn.com.br"><i class="bt-at-footer"></i></a>
                <a href="https://tecidosmn.com.br/" target="_blank"><i class="bt-globe-footer"></i></a>
                <a href="https://www.instagram.com/mntecidos/" target="_blank"><i class="bt-instagram-footer"></i></a>
            </span>
        </span>
        <span class="span1">
            <i class="bt-phone-footer"></i>
            (11) 2799-6744
            <span class="span4">
                <i class="bt-phone-footer"></i>
                (11) 2791-9799
            </span>
        </span>
    </div>
</div>

@endsection

@section('script-footer')
    $(document).ready( function () {
        
        filtroGrupo($(document).find('#form_grupo').serialize());

        $(document).find('#btn-filterform').on("click", function(){
            if($(document).find('#segmentos_id').val().length > 0){
                if($(document).find('#marca').val().length > 0 || $(document).find('#linha').val().length > 0 || $(document).find('#campanha').val().length > 0 ){
                    filtroGrupo($(document).find('#form_grupo').serialize());
                }else{
                    $(document).find('#segmentos_id').val('');
                    $(document).find('#busca').val('true');
                    busca($("#form_grupo").serialize());
                }
            }else{
                $(document).find('#segmentos_id').val('');
                $(document).find('#busca').val('true');
                busca($("#form_grupo").serialize());
            }
        });

        tamanho_tabela_cards();

        $(window).resize(function(){
            tamanho_tabela_cards();
        });

        $(document).find('#grupo').autocomplete(autoCompleteOptions('grupo'));
        $(document).find('#marca').autocomplete(autoCompleteOptions('marca'));
        $(document).find('#linha').autocomplete(autoCompleteOptions('linha'));

        $(document).find(".bt-carrinho-header").off("click");
        $(document).find(".bt-carrinho-header").on("click", function(event){
            event.stopPropagation();
            modalVisualizarCarrinho($(this));
        });

        $(document).find('#btn-cliente').on('click', function(){
            modalInformarCliente($(this));
        });
    });

    function tamanho_tabela_cards(){
        var position = $(document).find('#div-cards').offset();
        var bottom = window.scrollY + window.innerHeight;

        $(document).find('#div-cards').css('max-height', bottom - position.top + "px");
    }

    function filtroGrupo(data_form){
        form = $(document).find('#form_grupo');
        $(document).find('#div-cards').html('');
        $.ajax({
            url: "{{ route('book_virtual_exibicao_new.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var data = callback.response;
                    var html = '';
                    for(var field in data){
                        if(data[field].book_pdf.length > 0){
                                html += '<div class="galeria-imagem-lg">'
                                +'<div class="img-galeria-lg">'
                                    +'<a href='+data[field].route+'>'
                                        +'<img src='+data[field].imagem+'/>'
                                    +'</a>'
                                    +'<div class="descricao-galeria-lg">'
                                        +'<i data-id='+data[field].id+' data-title='+data[field].descricao+' class="bt-pdf-icon-branco">'+'</i>'
                                        +data[field].descricao
                                    +'</div>'
                                +'</div>'
                            +'</div>';
                        }else{
                            html += '<div class="galeria-imagem-lg">'
                                +'<div class="img-galeria-lg">'
                                    +'<a href='+data[field].route+'>'
                                        +'<img src='+data[field].imagem+'/>'
                                    +'</a>'
                                    +'<div class="descricao-galeria-lg">'
                                        +'<i class="bt-pdf-icon-branco">'+'</i>'
                                        +data[field].descricao
                                    +'</div>'
                                +'</div>'
                            +'</div>';
                        }
                    }
                    $(document).find('#div-cards').append(html);
                    $(document).find(".bt-pdf-icon-branco").off("click");
                    $(document).find(".bt-pdf-icon-branco").on("click", function(event){
                        event.stopPropagation();
                        modalPDFBusca($(this));
                    });
                }
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function autoCompleteOptions($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3
        };
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('is-invalid');
	}

    function modalVisualizarCarrinho($this){
        var title = 'Editar Carrinho '+$($this).data("cliente");
        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao_new.modal.editar') }}',
            data: {
                _token: "{{ csrf_token() }}"
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

    function busca($this){
        data_form = $(document).find('#form_grupo').serialize();
        form = $(document).find('#form_grupo');
        
        $.ajax({
            url: "{{ route('book_virtual_exibicao_new.gerar_link_busca') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                window.location.assign(data.response.link)
            },
            error: function(callback){
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

    function modalInformarCliente($this){
        var title = $($this).data("title");
        var carrinho = 'true';
        var informar_cliente = true;

        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao_new.modal.adicionar') }}',
            data: {
                _token: "{{ csrf_token() }}",
                 carrinho: carrinho,
                 informar_cliente : informar_cliente
                },
            method: 'POST',
            success: function(body){
                createModal("modal_adicionar_carrinho", title, body, 'modal-lg');
            },
            error: function(body){
                message("Atenção", body.responseJSON.message);
            }
        });
    }

    function modalPDFBusca($this){
        var title = $($this).data("title");

        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao_new.modal.pdf') }}',
            data: {
                _token: "{{ csrf_token() }}",
                 produto_grupos_id: $($this).data("id")
                },
            method: 'POST',
            success: function(body){
                createModal("modal_baixar_enviar_pdf", title, body, '');
            },
            error: function(body){
                message("Atenção", body.responseJSON.message);
            }
        });
    }
@endsection
