@extends('layouts.app-book-virtual')
@section('content')
<div class="container-sm">
    <div class="content-book">
        <div class="header-book">
            <div class="row margin-head"> 
                <div class="col-0">
                    <a href="{{ route('book_virtual_exibicao_new.index') }}" name="btn_home" id="btn_home" title="Página Incial" class="bt-home-header"></a>
                </div>
                <div class="pos-f-t">
                    <div class="collapse" id="navbarToggleExternalContent">
                        <div class="bg-light p-4">
                            <form action="#" name="form_grupo_mobile" id="form_grupo_mobile" onsubmit="return false">
                                @csrf
                                {{ Form::hidden('busca', 'false', ['id' => 'busca']) }}
                                {!! Form::hidden('segmentos_id', $segmentos_id, ['id' => 'segmentos_id']) !!}
                                {{ Form::hidden('busca_navegacao', $busca_navegacao, ['id' => 'busca_navegacao']) }}
                                <div class="form-row">       
                                    <div class="col-lg-2"> 
                                        {!! Form::text('grupo', $grupo, ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo', 'maxlength' => '255']) !!}
                                    </div>
                                    <div class="col-lg-2">
                                        {{ Form::text('marca', $marca, ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca', 'maxlength' => '255']) }}
                                    </div>
                                    <div class="col-lg-2">
                                        {{ Form::text('linha', $linha, ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha', 'maxlength' => '255']) }}
                                    </div>
                                    <div class="col-lg-2">
                                        {{ Form::text('codigo_produto', $codigo_produto, ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código', 'maxlength' => '255']) }}
                                    </div>
                                    <div class="col-lg-2"> 
                                        {!! Form::select('tipo_venda', $tipo_vendas, $tipo_venda, ['id' => 'forma_pagamento', 'class' => 'form-control', 'placeholder' => 'Tipo Venda']) !!}
                                    </div>
                                    <div class="col-lg-2"> 
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
                            </form>
                        </div>
                    </div>
                    <nav class="navbar navbar-light bg-light">
                        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarToggleExternalContent" aria-controls="navbarToggleExternalContent" aria-expanded="false" aria-label="Alterna navegação">
                        <span class="navbar-toggler-icon"></span>
                        </button>
                    </nav>
                </div>
                <div class="col col-lg-2">
                    <span id='contador'>&nbsp&nbsp{{ $contador }}</span>
                    <i name="btn_carrinho" id="btn_carrinho" data-cliente="{{ $cliente }}" title="Carrinho" class="bt-carrinho-header"></i>
                </div>
            </div>
        </div>
        <div class="row justify-content-center" id='div-cards'>
            <div class="clearfix-galeria"></div>
        </div> 
    </div> 
    <div class="footer" id="footer"></div>
</div>

@endsection

@section('script-footer')
    $(document).ready( function () {
        
        filtroGrupo($(document).find('#form_grupo_mobile').serialize());

        $(document).find('#btn-filterform').on("click", function(){
            $(document).find('#segmentos_id').val('');
            $(document).find('#busca').val('true');
            busca($("#form_grupo_mobile").serialize());
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
        form = $(document).find('#form_grupo_mobile');
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
                                        +'<i data-id="" data-title='+data[field].descricao+' class="bt-pdf-icon-branco">'+'</i>'
                                        +data[field].descricao
                                    +'</div>'
                                +'</div>'
                            +'</div>';
                        }
                    }

                    footer = '<div class="container-book">'
                        +'<hr/>'
                        +'<img src="{{ URL::asset('images/logomn.jpg') }}">'
                        +'<span class="span1">'
                            +'<span class="span2">'
                                +'<i class="bt-map-footer-mobile">'+'</i>'
                                +'<i class="bt-phone-footer-mobile">'+'</i>'
                                +'<a href="mailto:contato@tecidosmn.com.br">'+'<i class="bt-at-footer-mobile">'+'</i>'+'</a>'
                                +'<a href="https://tecidosmn.com.br/" target="_blank">'+'<i class="bt-globe-footer-mobile">'+'</i>'+'</a>'
                                +'<a href="https://www.instagram.com/mntecidos/" target="_blank">'+'<i class="bt-instagram-footer-mobile">'+'</i>'+'</a>'
                            +'</span>'
                        +'</span>'
                    +'</div>'

                    $(document).find('#footer').append(footer);
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
        data_form = $(document).find('#form_grupo_mobile').serialize();
        form = $(document).find('#form_grupo_mobile');
        
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
