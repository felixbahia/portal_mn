@extends('layouts.app-book-virtual')
@section('content')
<div class="content-book">
    <div class="container-sm">
        <form action="#" name="form_produtos" id="form_produtos" onsubmit="return false">
            @csrf
            {{ Form::hidden('busca', 'false', ['id' => 'busca']) }}
            {{ Form::hidden('id', $id, ['id' => 'id']) }}
            {{ Form::hidden('codigo_desenho', $codigo_desenho, ['id' => 'codigo_desenho']) }}
            {{ Form::hidden('codigos_produtos', $codigos_produtos, ['id' => 'codigos_produtos']) }}
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
                            <a href="{{ route('book_virtual_exibicao_new.index') }}">{{ CustomView::programaName() }} > </a><a href="javascript:history.go(-3)"><strong>{{ $busca_navegacao }}</strong></a>
                        @else
                            <a href="{{ route('book_virtual_exibicao_new.index') }}">{{ CustomView::programaName() }} > </a><a href="javascript:history.go(-3)">{{ $busca_navegacao }} > </a><a href="javascript:history.back()">{{ $grupo_navegacao }} > </a><strong>{{ $codigo_desenho }}</strong>
                        @endif
                    @else
                        <a href="{{ route('book_virtual_exibicao_new.index') }}">{{ CustomView::programaName() }} > </a><a href="javascript:history.go(-1)">{{ $grupo_navegacao }} > </a></a><strong>{{ $codigo_desenho }}</strong>
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
    $(document).ready( function () {

        filtroDesenhoProdutos();

        $(document).find('#btn-filterform').on("click", function(){
            $(document).find('#busca').val('true');
            if($(document).find('#grupo').val().length > 0){
                $(document).find('#codigo_desenho').val('');
                $(document).find('#codigos_produtos').val('');
                buscaFiltroFormDesenhoProduto($(this));
            }else{
                filtroDesenhoProdutos();
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

    function filtroDesenhoProdutos(){
        data_form = $(document).find('#form_produtos').serialize();
        $(document).find('#div-cards').html('');
        $.ajax({
            url: "{{ route('book_virtual_exibicao_new.book_produtos') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.response.desenho.nome != undefined){
                    var html_desenho = '<div class="col-lg-3">'
                        +'<div class="img-container-grupo" style="background-image: url('+callback.response.desenho.imagem+');">'
                                +'<i class="bt-clique-aqui" data-imagem='+callback.response.desenho.imagem_zoom+' data-desenho='+callback.response.desenho.nome+' id="modal-zoom">'+'</i>'
                        +'</div>'
                    +'</div>'
                    +'<div class="col-lg-7">'
                        +'<div class="titulo-grupo">'
                            +'<h3>'+callback.response.desenho.nome+'</h3>'
                        +'</div>'
                        +'<div class="table-responsive-lg">'
                            +'<table class="table table-striped">'
                                +'<tbody>'
                                    +'<tr>'
                                        +'<td>'+'<b>'+'Característica: '+'</b>'+callback.response.desenho.caracteristicas+'</td>'
                                        +'<td>'+'<b>'+'Composição: '+'</b>'+callback.response.desenho.composicao+'</td>'
                                        +'<td>'+'<b>'+'Encolhimento %: '+'</b>'+callback.response.desenho.encolhimento+'</td>'
                                    +'</tr>'
                                    +'<tr>'
                                        +'<td>'+'<b>'+'Peças de: '+'</b>'+callback.response.desenho.pecas+'</td>'
                                        +'<td>'+'<b>'+'Origem: '+'</b>'+callback.response.desenho.origem+'</td>'
                                        +'<td>'+'<b>'+'NCM: '+'</b>'+callback.response.desenho.ncm+'</td>'
                                    +'</tr>'
                                    +'<tr>'
                                        +'<td>'+'<b>'+'Gramatura G/M²  (+/- 5%): '+'</b>'+callback.response.desenho.gramatura_gm2+'</td>'
                                        +'<td>'+'<b>'+'Gramatura Linear G/M: '+'</b>'+callback.response.desenho.gramatura_linear+'</td>'
                                        +'<td>'+'<b>'+'Larg. (+/- 2CM): '+'</b>'+callback.response.desenho.largura+'</td>'
                                    +'</tr>'
                                    +'<tr>'
                                        +'<td>'+'<b>'+'Rendimento MT/KG: '+'</b>'+callback.response.desenho.rendimento+'</td>'
                                        +'<td>'+'<b>'+'EAN: '+'</b>'+callback.response.desenho.ean+'</td>'
                                        +'<td>'+'<b>'+'Unidade: '+'</b>'+callback.response.desenho.unidade+'</td>'
                                    +'</tr>'
                                    +'<tr>'
                                        +'<td>'+'<b>'+'Peso Bruto: '+'</b>'+callback.response.desenho.peso_bruto+'</td>'
                                        +'<td>'+'<b>'+'Instrução de Lavagem: '+'</b>'+'</td>'
                                        +'<td>'+'<img src='+callback.response.desenho.img_instrucoes_lavagem+' class="img_instrucoes_lavagem" style="width: 290px;height: 50px;">'+'</td>'
                                    +'</tr>'
                                +'</tbody>'
                            +'</table>'
                        +'</div>'
                    +'</div>'
                    +'<div class="col-lg-0">'
                        +'<div class="margin-icon">'
                            +'<button data-desenho='+callback.response.desenho.nome+' data-title='+callback.response.desenho.nome+' class="button-pdf">'+'<span class="texto-button">'+'PDF'+'</span>'+'</button>'
                            +'<button data-desenho='+callback.response.desenho.nome+' data-id='+callback.response.desenho.id+' data-title='+callback.response.desenho.nome+' id="button-ficha">'+'<span class="texto-button">'+'FICHA&nbspTÉCNICA'+'</span>'+'</button>'
                        +'</div>'
                    +'</div>'
                    +'<div class="col-lg-12">'
                            +'<span class="texto-cor-variacao">'+'*Este produto pode conter variações de cor de acordo com a tela utilizada'+'</span>'
                    +'</div>';

                    $(document).find('#div-cards').append(html_desenho);
                    $(document).find(".ficha-tecnica").off("click");
                    $(document).find(".ficha-tecnica").on("click", function(event){
                        event.stopPropagation();
                        modalFichaTecnica($(this));
                    });
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
                        modalZoomProduto($(this));
                    });

                    var data = callback.response.produtos;
                    var html_produtos = '';
                    for(var field in data){ 
                        html_produtos += '<div class="galeria-imagem-md" id='+data[field].codigo_produto+'>'
                            +'<div class="img-galeria-md">'
                                    +'<img src='+data[field].imagem_cor+'/>'
                                    +'<div class="descricao-galeria-md">'
                                        +'<span>'+data[field].codigo_produto+'</span>'+'<br/>'
                                        +'Estoque: '+data[field].estoque_total+'<br/>'
                                        +'Compras: '+data[field].compras+'<br/>'
                                        +'Preço: '+data[field].preco+'<i data-produto_codigo='+data[field].codigo_produto+' class="bt-carrinho-adicionar">'+'</i>'
                                    +'</div>'
                                +'</div>'
                            +'</div>';
                    }
                    $(document).find('#div-cards').append(html_produtos);
                    $(document).find(".bt-carrinho-adicionar").off("click");
                    $(document).find(".bt-carrinho-adicionar").on("click", function(event){
                        event.stopPropagation();
                        modalAdicionarCarrinho($(this));
                    });
                }else{
                    message("Atenção", "Nenhuma estampa encontrada!");
                }
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function modalZoomProduto($this){
        var title = 'Desenho '+$($this).data("desenho");
        var data_form = $(document).find('#form_produtos').serialize()
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

    function buscaFiltroFormDesenhoProduto($this){
        data_form = $(document).find('#form_produtos').serialize();
        form = $(document).find('#form_produtos');
        
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
        desenho = $($this).data("desenho");

        $('<form action="{{ route('book_virtual_exibicao_new.pdf_ficha') }}" method="POST" target="_blank">\
        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
        <input type="hidden" name="id" value="'+id+'">\
        <input type="hidden" name="desenho" value="'+desenho+'">\
        </form>').appendTo('body').submit().remove();
    }

@endsection
