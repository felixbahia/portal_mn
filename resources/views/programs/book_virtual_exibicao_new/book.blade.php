@extends('layouts.app-book-virtual')
@section('content')
<div class="content-book">
    <div class="container-sm">
        <form action="#" name="form_desenho" id="form_desenho" onsubmit="return false">
            @csrf
            {{ Form::hidden('busca', 'false', ['id' => 'busca']) }}
            {{ Form::hidden('id', $id, ['id' => 'id']) }}
            {{ Form::hidden('codigo_desenho', '', ['id' => 'codigo_desenho']) }}
            {{ Form::hidden('codigos_produtos', $codigos_produtos, ['id' => 'codigos_produtos']) }}
            {{ Form::hidden('grupo_navegacao', $grupo_navegacao, ['id' => 'grupo_navegacao']) }}
            {{ Form::hidden('busca_navegacao', $busca_navegacao, ['id' => 'busca_navegacao']) }}
            {{ Form::hidden('codigo_produto_busca', $codigo_produto_busca, ['id' => 'codigo_produto_busca']) }}
            <div class="header-book">
                <span>&nbsp&nbsp<span id='contador'>{{ $contador }}</span></span>
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
                    @if(!empty($grupo_navegacao) && !empty($busca_navegacao))
                        @if($grupo_navegacao == 'codigo')
                            <a href="{{ route('book_virtual_exibicao_new.index') }}">{{ CustomView::programaName() }} > </a><a href="javascript:history.back()"><strong>{{ $busca_navegacao }}</strong></a>
                        @else
                            <a href="{{ route('book_virtual_exibicao_new.index') }}">{{ CustomView::programaName() }} > </a><a href="javascript:history.back()">{{ $busca_navegacao }} > </a><strong>{{ $grupo_navegacao }}</strong>
                        @endif
                    @else
                        <a href="{{ route('book_virtual_exibicao_new.index') }}">{{ CustomView::programaName() }} > </a><strong>{{ $grupo_navegacao }}</strong>
                    @endif
                </div>
            </div>
        </div>
        <div class="row" id='div-cards'>
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
    $(document).ready(function(){
        filtroBookDesenho($(document).find('#form_desenho').serialize());

        $(document).find('#btn-filterform').on("click", function(){
            $(document).find('#busca').val('true');
            if(($(document).find('#grupo').val().length) > 0){
                buscaFiltroFormDesenho($(this));
            }else{
                filtroBookDesenho($(document).find('#form_desenho').serialize());
            }
        });

        tamanho_tabela_cards();

        $(window).resize(function(){
            tamanho_tabela_cards();
        });
        

        $(document).find('#grupo').autocomplete(autoCompleteOptionsGrupo('grupo'));
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

    function filtroBookDesenho(data_form){
        form = $(document).find('#form_desenho');
        $(document).find('#div-cards').html('');
        $.ajax({
            url: "{{ route('book_virtual_exibicao_new.book_desenho') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    if(callback.response.grupo.book_pdf.length > 0){
                        var html_grupo = '<div class="col-lg-3">'
                            +'<div class="img-container-grupo" style="background-image: url('+callback.response.grupo.imagem+');">'
                                +'<i class="bt-clique-aqui" data-imagem='+callback.response.grupo.imagem_zoom+' data-grupo='+callback.response.grupo.nome+' id="modal-zoom">'+'</i>'
                            +'</div>'
                        +'</div>'
                        +'<div class="col-lg-7">'
                            +'<div class="titulo-grupo">'
                                +'<h3>'+callback.response.grupo.nome+'</h3>'
                            +'</div>'
                            +'<div class="table-responsive-lg">'
                                +'<table class="table table-striped">'
                                    +'<tbody>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Característica: '+'</b>'+callback.response.grupo.caracteristicas+'</td>'
                                            +'<td>'+'<b>'+'Composição: '+'</b>'+callback.response.grupo.composicao+'</td>'
                                            +'<td>'+'<b>'+'Encolhimento %: '+'</b>'+callback.response.grupo.encolhimento+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Peças de: '+'</b>'+callback.response.grupo.pecas+'</td>'
                                            +'<td>'+'<b>'+'Origem: '+'</b>'+callback.response.grupo.origem+'</td>'
                                            +'<td>'+'<b>'+'NCM: '+'</b>'+callback.response.grupo.ncm+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Gramatura G/M²  (+/- 5%): '+'</b>'+callback.response.grupo.gramatura_gm2+'</td>'
                                            +'<td>'+'<b>'+'Gramatura Linear G/M: '+'</b>'+callback.response.grupo.gramatura_linear+'</td>'
                                            +'<td>'+'<b>'+'Larg. (+/- 2CM): '+'</b>'+callback.response.grupo.largura+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Rendimento MT/KG: '+'</b>'+callback.response.grupo.rendimento+'</td>'
                                            +'<td>'+'<b>'+'EAN: '+'</b>'+callback.response.grupo.ean+'</td>'
                                            +'<td>'+'<b>'+'Unidade: '+'</b>'+callback.response.grupo.unidade+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Peso Bruto: '+'</b>'+callback.response.grupo.peso_bruto+'</td>'
                                            +'<td>'+'<b>'+'Instrução de Lavagem: '+'</b>'+'</td>'
                                            +'<td>'+'<img src='+callback.response.grupo.img_instrucoes_lavagem+' class="img_instrucoes_lavagem" style="width: 290px;height: 50px;">'+'</td>'
                                        +'</tr>'
                                    +'</tbody>'
                                +'</table>'
                            +'</div>'
                        +'</div>'
                        +'<div class="col-lg-0">'
                            +'<div class="margin-icon">'
                                +'<button data-id='+callback.response.grupo.id+' data-desenho="" data-title='+callback.response.grupo.nome+' class="button-pdf">'+'<span class="texto-button">'+'PDF'+'</span>'+'</button>'
                                +'<button data-id='+callback.response.grupo.id+' data-title='+callback.response.grupo.nome+' id="button-ficha">'+'<span class="texto-button">'+'FICHA&nbspTÉCNICA'+'</span>'+'</button>'
                            +'</div>'
                        +'</div>'
                        +'<div class="col-lg-12">'
                            +'<span class="texto-cor-variacao">'+'*Este produto pode conter variações de cor de acordo com a tela utilizada'+'</span>'
                        +'</div>';
                    }else{
                        var html_grupo = '<div class="col-lg-3">'
                            +'<div class="img-container-grupo" style="background-image: url('+callback.response.grupo.imagem+');">'
                                +'<i class="bt-clique-aqui" data-imagem='+callback.response.grupo.imagem_zoom+' data-grupo='+callback.response.grupo.nome+' id="modal-zoom">'+'</i>'
                            +'</div>'
                        +'</div>'
                        +'<div class="col-lg-7">'
                            +'<div class="titulo-grupo">'
                                +'<h3>'+callback.response.grupo.nome+'</h3>'
                            +'</div>'
                            +'<div class="table-responsive-lg">'
                                +'<table class="table table-striped">'
                                    +'<tbody>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Característica: '+'</b>'+callback.response.grupo.caracteristicas+'</td>'
                                            +'<td>'+'<b>'+'Composição: '+'</b>'+callback.response.grupo.composicao+'</td>'
                                            +'<td>'+'<b>'+'Encolhimento %: '+'</b>'+callback.response.grupo.encolhimento+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Peças de: '+'</b>'+callback.response.grupo.pecas+'</td>'
                                            +'<td>'+'<b>'+'Origem: '+'</b>'+callback.response.grupo.origem+'</td>'
                                            +'<td>'+'<b>'+'NCM: '+'</b>'+callback.response.grupo.ncm+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Gramatura G/M²  (+/- 5%): '+'</b>'+callback.response.grupo.gramatura_gm2+'</td>'
                                            +'<td>'+'<b>'+'Gramatura Linear G/M: '+'</b>'+callback.response.grupo.gramatura_linear+'</td>'
                                            +'<td>'+'<b>'+'Larg. (+/- 2CM): '+'</b>'+callback.response.grupo.largura+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Rendimento MT/KG: '+'</b>'+callback.response.grupo.rendimento+'</td>'
                                            +'<td>'+'<b>'+'EAN: '+'</b>'+callback.response.grupo.ean+'</td>'
                                            +'<td>'+'<b>'+'Unidade: '+'</b>'+callback.response.grupo.unidade+'</td>'
                                        +'</tr>'
                                        +'<tr>'
                                            +'<td>'+'<b>'+'Peso Bruto: '+'</b>'+callback.response.grupo.peso_bruto+'</td>'
                                            +'<td>'+'<b>'+'Instrução de Lavagem: '+'</b>'+'</td>'
                                            +'<td>'+'<img src='+callback.response.grupo.img_instrucoes_lavagem+' class="img_instrucoes_lavagem" style="width: 290px;height: 50px;">'+'</td>'
                                        +'</tr>'
                                    +'</tbody>'
                                +'</table>'
                            +'</div>'
                        +'</div>'
                        +'<div class="col-lg-0">'
                            +'<div class="margin-icon">'
                                +'<button data-desenho="" data-title='+callback.response.grupo.nome+' class="button-pdf">'+'<span class="texto-button">'+'PDF'+'</span>'+'</button>'
                                +'<button data-id='+callback.response.grupo.id+' data-title='+callback.response.grupo.nome+' id="button-ficha">'+'<span class="texto-button">'+'FICHA&nbspTÉCNICA'+'</span>'+'</button>'
                            +'</div>'
                        +'</div>'
                        +'<div class="col-lg-12">'
                            +'<span class="texto-cor-variacao">'+'*Este produto pode conter variações de cor de acordo com a tela utilizada'+'</span>'
                        +'</div>';
                    }
                    $(document).find('#div-cards').append(html_grupo);
                    $(document).find(".button-pdf").off("click");
                    $(document).find(".button-pdf").on("click", function(event){
                        event.stopPropagation();
                        modalPDFDesenho($(this));
                    });
                    $(document).find("#button-ficha").off("click");
                    $(document).find("#button-ficha").on("click", function(event){
                        event.stopPropagation();
                        pdfFichaTecnica($(this));
                    });
                    $(document).find("#modal-zoom").off("click");
                    $(document).find("#modal-zoom").on("click", function(event){
                        event.stopPropagation();
                        modalZoomDesenho($(this));
                    });


                    var data = callback.response.desenhos;
                    var html_desenhos = '';
                    if(data.length > 0){
                        for(var field in data){ 
                            if(data[field].pdf_desenho.length > 0){
                                html_desenhos += '<div class="galeria-imagem-md">'
                                    +'<div class="img-galeria-md">'
                                        +'<a href="#" data-codigo_desenho='+data[field].codigo_desenho+' data-codigos_produtos="'+data[field].codigos_produtos+'" class="produtos">'
                                            +'<img src='+data[field].imagem_desenho+'/>'
                                        +'</a>'
                                            +'<div class="descricao-galeria-desenho">'
                                                +'<span>'+data[field].codigo_desenho+'</span>'
                                                +'<i data-desenho='+data[field].codigo_desenho+' data-title='+data[field].codigo_desenho+' data-id="" class="bt-pdf-icon-azul-sm">'+'</i>'
                                            +'</div>'
                                        +'</div>'
                                    +'</div>';
                            }else{
                                html_desenhos += '<div class="galeria-imagem-md">'
                                    +'<div class="img-galeria-md">'
                                        +'<a href="#" data-codigo_desenho='+data[field].codigo_desenho+' data-codigos_produtos="'+data[field].codigos_produtos+'" class="produtos">'
                                            +'<img src='+data[field].imagem_desenho+'/>'
                                        +'</a>'
                                            +'<div class="descricao-galeria-desenho">'
                                                +'<span>'+data[field].codigo_desenho+'</span>'
                                                +'<i class="bt-pdf-icon-azul-sm">'+'</i>'
                                            +'</div>'
                                        +'</div>'
                                    +'</div>';
                            } 
                        }
                    }else{
                        html_desenhos = '<div class="mx-auto">'
                                +'<h4>Nenhum desenho encontrado!</h4>'
                            +'</div>';
                    }
                    $(document).find('#div-cards').append(html_desenhos);

                    $(document).find(".produtos").off("click");
                    $(document).find(".produtos").on("click", function(event){
                        event.stopPropagation();
                        $(document).find('#codigo_desenho').val($(this).data('codigo_desenho'));
                        $(document).find('#codigos_produtos').val($(this).data('codigos_produtos'));
                        buscaFiltroFormDesenho($(this));
                    });
                    $(document).find(".bt-pdf-icon-azul-sm").off("click");
                    $(document).find(".bt-pdf-icon-azul-sm").on("click", function(event){
                        event.stopPropagation();
                        modalPDFDesenho($(this));
                    });
                }
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function autoCompleteOptionsGrupo($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.grupo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3
        };
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
        var title = 'Editar Carrinho '+$($this).data("cliente");;
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

    function modalZoomDesenho($this){
        var title = 'Grupo '+$($this).data("grupo");
        var data_form = $(document).find('#form_desenho').serialize()
        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao_new.modal.zoom') }}',
            data: {
                _token: '{{ csrf_token() }}',
                imagem: $($this).data("imagem")
            },
            method: 'POST',
            success: function(body){
                createModal("modal_zoom", title, body, 'modal-md');
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }  

    function buscaFiltroFormDesenho($this){
        data_form = $(document).find('#form_desenho').serialize();
        form = $(document).find('#form_desenho');
        
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

    function modalAdicionarCarrinho($this){
        var title = "Adicionar Carrinho {{ $cliente }}";
        var carrinho = 'true';
        var informar_cliente = false;
        var produto_codigo = $($this).data("produto_codigo");

        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao_new.modal.adicionar') }}',
            data: {
                _token: "{{ csrf_token() }}",
                 carrinho: carrinho,
                 produto_codigo: produto_codigo,
                 id : "{{ $pedido_id }}",
                 dados_cliente_id : "{{ $dados_cliente_id }}",
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


    function atualizarProdutoBook(codigo_produto){
        $(document).find("#"+codigo_produto).remove();
        soma_contador = parseInt($(document).find('#contador').html()) + 1;
        $(document).find('#contador').html(soma_contador);
    }

    function modalPDFDesenho($this){
        var title = $($this).data("title");

        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao_new.modal.pdf') }}',
            data: {
                _token: "{{ csrf_token() }}",
                 produto_grupos_id: $($this).data("id"),
                 desenho : $($this).data("desenho")
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

    function pdfFichaTecnica($this){
        id = $($this).data("id");

        $('<form action="{{ route('book_virtual_exibicao_new.pdf_ficha') }}" method="POST" target="_blank">\
        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
        <input type="hidden" name="id" value="'+id+'">\
        </form>').appendTo('body').submit().remove();
    }

@endsection
