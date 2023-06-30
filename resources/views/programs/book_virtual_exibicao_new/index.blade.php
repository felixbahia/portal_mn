@extends('layouts.app-book-virtual')
@section('content')
<div class="content-book">
    <div class="container-lg">
        <form action="#" name="form_home" id="form_home" onsubmit="return false">
            @csrf
            {{ Form::hidden('busca', 'false', ['id' => 'busca']) }}
            <div class="header-book">
                <div class="form-row">
                    <div class="col-lg-0"> 
                        <i name="btn_home" id="btn_home" title="Página Incial" class="bt-home-header"></i>
                    </div>
                    <div class="col-lg"> 
                        {!! Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo', 'maxlength' => '255']) !!}
                    </div>
                    <div class="col-lg">
                        {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca', 'maxlength' => '255']) }}
                    </div>
                    <div class="col-lg">
                        {{ Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha', 'maxlength' => '255']) }}
                    </div>
                    <div class="col-lg">
                        {{ Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código', 'maxlength' => '255']) }}
                    </div>
                    <div class="col-lg"> 
                        {!! Form::select('tipo_venda', $tipo_vendas,'', ['id' => 'tipo_venda', 'class' => 'form-control', 'placeholder' => 'Tipo Venda']) !!}
                    </div>
                    <div class="col-lg"> 
                        {!! Form::select('campanha', $campanhas,'', ['id' => 'campanha', 'class' => 'form-control', 'placeholder' => 'Selecione a Campanha']) !!}
                    </div>
                    <div class="content">
                        <div class="content-buttons">
                            <button type="button" name="btn-filterform"  data-id="" id="btn-filterform" class="btn-filter">Buscar</button>
                            @if(empty($cliente))
                                <button type="button" name="btn-cliente"  data-title="Adicionar Cliente" id="btn-cliente" class="btn-cliente">Cliente</button>
                            @endif
                            <span id='contador'>&nbsp&nbsp{{ $contador }}</span>
                            <i name="btn_carrinho" id="btn_carrinho" data-cliente="{{ $cliente }}" title="Carrinho" class="bt-carrinho-header"></i>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-book">
                   <strong>{{ CustomView::programaName() }} </strong>
                </div>
            </div>
        </div>
        <div class="container-lg">
            <div class="content-book-home" id="menu"></div>
            <div class="cartela-de-cores" id="cartela-de-cores"></div>
        </div>
    </div>
</div>
<div class="container-lg">
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
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        filtroSegmento($(document).find('#form_home').serialize());

        $(document).find('#grupo').autocomplete(autoCompleteOptions('grupo'));
        $(document).find('#marca').autocomplete(autoCompleteOptions('marca'));
        $(document).find('#linha').autocomplete(autoCompleteOptions('linha'));

        $(document).find('#btn-filterform').on('click', function(){
            $(document).find('#busca').val('true');
            buscaGrupo($(this));
        });

        $(document).find('#btn-cliente').on('click', function(){
            modalInformarCliente($(this));
        });

        $(document).find(".bt-home-header").off("click");
        $(document).find(".bt-home-header").on("click", function(event){
            event.stopPropagation();
            window.location.reload();
        });

        $(document).find(".bt-carrinho-header").off("click");
        $(document).find(".bt-carrinho-header").on("click", function(event){
            event.stopPropagation();
            modalVisualizarCarrinho($(this));
        });

        tamanho_tabela_cards();

        $(window).resize(function(){
            tamanho_tabela_cards();
        });

    });

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

    function buscaGrupo($this){
        data_form = $(document).find('#form_home').serialize();
        form = $(document).find('#form_home');
        
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

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('is-invalid');
	}

    function tamanho_tabela_cards(){
        var position = $(document).find('#menu').offset();
        var bottom = window.scrollY + window.innerHeight;

        $(document).find('#menu').css('max-height', bottom - position.top + "px");
    }

    function filtroSegmento(data_form){
        $(document).find('#grupo').val('');
        $(document).find('#marca').val('');
        $(document).find('#linha').val('');
        $(document).find('#codigo_produto').val('');
        $(document).find('#tipo_venda').val('');

        form = $(document).find('#form_home');
        $.ajax({
            url: "{{ route('book_virtual_exibicao_new.segmentos') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var data = callback.response.segmentos;
                    var html = '';
                    var html_cores = '';
                    for(var field in data){
                        if(data[field].descricao == 'CARTELA DE CORES'){
                            html_cores = '<a href='+data[field].pdf+' target="_blank">'
                                +'<button type="button"  class="btn btn-primary" style="background-color: '+data[field].cor_codigo+'; border: '+data[field].cor_codigo+'">'    
                                    +'CARTELA DE'+'</br>'+'CORES'
                                +'</button>'
                            +'</a>&nbsp&nbsp&nbsp&nbsp'
                            +'<a href='+data[field].pdf_pro+' target="_blank">'
                                +'<button type="button"  class="btn btn-primary" style="background-color: #5690C5; border: #5690C5">'    
                                    +'LINHA MNPRO'+'</br>'+'DOWNLOAD'
                                +'</button>'
                            +'</a>';
                        }else{
                            html += '<div class="content-book-menu">'
                                +'<button type="button" class="btn btn-primary" style="background-color: '+data[field].cor_codigo+'; border: '+data[field].cor_codigo+'">'
                                    +'<a href='+data[field].route+'  class="texto-segmento">'
                                        +data[field].descricao
                                    +'</a>'
                                    +'<i data-id='+data[field].id+' data-title='+data[field].descricao+' class="bt-pdf-icon-branco">'+'</i>'
                                +'</button>'
                            +'</div>';
                        }
                    }
                    $(document).find('#cartela-de-cores').append(html_cores);
                    $(document).find('#menu').append(html);
                    $(document).find(".bt-pdf-icon-branco").off("click");
                    $(document).find(".bt-pdf-icon-branco").on("click", function(event){
                        event.stopPropagation();
                        modalPDF($(this));
                    });
                }
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
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

    function modalPDF($this){
        var title = $($this).data("title");
        console.log(title);
        if(title != 'CARTELA'){
                xhr = $.ajax({
                url: '{{ route('book_virtual_exibicao_new.modal.pdf') }}',
                data: {
                    _token: "{{ csrf_token() }}",
                    segmentos_id: $($this).data("id")
                    },
                method: 'POST',
                success: function(body){
                    createModal("modal_baixar_enviar_pdf", title, body, '');
                },
                error: function(body){
                    message("Atenção", body.responseJSON.message);
                }
            });
        }else{
            return false;
        }
    }
@endsection


