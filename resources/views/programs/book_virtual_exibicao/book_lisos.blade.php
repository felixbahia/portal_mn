@extends('layouts.app-lista-preco')

@section('content')
{!! $link_anterior !!}
{!! $link_proximo !!}
<div>
    @if(isset($filtro) && !empty($filtro) && $segmento_id != 'busca_avancada')
        <i><a class="link-book-virtual" href="{{ route('book_virtual_exibicao.busca', ['segmentos_id' => encrypt($segmento_id), 'categoria' => 'busca_avancada', 'filter' => $filtro]) }}"> << Voltar para a lista de books virtuais</a> </i>
    @elseif(isset($filtro) && !empty($filtro) && $segmento_id == 'busca_avancada')
        <i><a class="link-book-virtual" href="{{ route('book_virtual_exibicao.busca', ['segmentos_id' => 'busca_avancada', 'categoria' => $categoria, 'filter' => $filtro]) }}"> << Voltar para a lista de books virtuais</a> </i>
    @else
        <i><a class="link-book-virtual" href="{{ route('book_virtual_exibicao.index') }}"> << Voltar para a tela principal</a> </i>
    @endif
</div>

<div id='pagina'>
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
                            {{ Form::hidden('produto_codigo', '', ['id' => 'produto_codigo']) }}
                            {!! Form::label('categoria_id', 'Categoria') !!}
                            {!! Form::select('categoria', $categorias, '', ['id' => 'categoria_id', 'class' => 'form-control', 'placeholder' => 'Categoria']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('segmentos_id', 'Segmento') !!}
                            {!! Form::select('segmentos_id', $segmentos, '', ['id' => 'segmentos_id', 'class' => 'form-control', 'placeholder' => 'Segmento']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('familias_id', 'Acabamento') !!}
                            {!! Form::select('familias_id', $familias, '', ['id' => 'familias_id', 'class' => 'form-control', 'placeholder' => 'Acabamento']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('tipo_material', 'Tipo de Material') !!}
                            {!! Form::select('tipo_material', $tipos_materiais, '', ['id' => 'tipo_material', 'class' => 'form-control', 'placeholder' => 'Tipo de material']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('tipo_genero', 'Gênero') !!}
                            {!! Form::select('tipo_genero', $tipo_generos, '', ['id' => 'tipo_genero', 'class' => 'form-control', 'placeholder' => 'Gênero']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('gramatura_tipo', 'Gramatura') !!}
                            {!! Form::select('gramatura_tipo', $tipo_gramaturas, '', ['id' => 'gramatura_tipo', 'class' => 'form-control', 'placeholder' => 'Tipo de gramatura']) !!}
                            </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('composicao', 'Composição') !!}
                            {!! Form::select('composicao', $composicaos, '', ['id' => 'composicao', 'class' => 'form-control', 'placeholder' => 'Tipo de Composição']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('construcao', 'Construção') !!}
                            {!! Form::select('construcao', $construcaos, '', ['id' => 'construcao', 'class' => 'form-control', 'placeholder' => 'Construção']) !!}
                        </div>
                        <div class='form-field busca_avancada_campo'>
                            {!! Form::label('sazonal', 'Sazonalidade') !!}
                            {!! Form::select('sazonal', $sazonalidades, '', ['id' => 'sazonal', 'class' => 'form-control', 'placeholder' => 'Sazonalidade']) !!}
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
                            {!! Form::text('codigo_produto', '', ['id' => 'cod_produto', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) !!}
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
    <div id='titulos-book'>
        @if(isset($numero_book) && !empty($artigo_nome))
        <div class='float-left'>
            <p>Book {{ $numero_book }}</p>
            <h3>{{ $artigo_nome }}</h3>
        </div>
        @endif
        <div class='float-right'>
            {!! $botao_pdf !!}
        </div>
        @if(empty($dadosCliente))
            <div class='float-right col-lg-2'>
                {!! $informar_cliente !!}
            </div>
        @endif
    </div>
    <div id='book-tela'>
        <div id="desenhos-container">
            <div id="lista-desenhos-div">
                <ul id='lista-desenhos'>
                    @foreach($desenhos as $desenho)
                        @if(isset($desenho['estoque']))
                            <li class='thumb-desenho' style='background-image: url({{ $desenho['imagem'] }})' data-desenho='' data-gramatura='{{ $desenho['gramatura'] }}' data-largura='{{ $desenho['largura'] }}' data-ncm='{{ $desenho['ncm'] }}' data-rendimento='{{ $desenho['rendimento'] }}' data-composicao='{{ $desenho['composicao'] }}' data-compras='{{ $desenho['compras'] }}' data-estoque='{{ $desenho['estoque'] }}' data-cliente='{{ $desenho['cliente'] }}' data-grupo='' data-marca='' data-linha='' data-descricao=''>
                                <div class="texto-imagem-thumb-book">
                                    @if(!empty($desenho['estoque']) && $desenho['estoque'] != '0,00')Estoque: {{ $desenho['estoque'] }}@endif
                                    @if(!empty($desenho['compras']) && $desenho['compras'] != '0,00')<br>Compras: {{ $desenho['compras'] }}@endif
                                </div>    
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
            <div id="desenho-exibicao-div">
                <a href="" id="desenho-link" target="_blank">
                    <div id="desenho-container-div">
                        <div class="texto-imagem-book">
                            <div id="desenho" class='d-none'>
                                <span id="desenho-texto"></span><br>
                            </div>
                            <div id="estoque" class='d-none'>
                                Estoque: <span id="estoque-texto"></span>
                            </div>
                            <div id="compras" class='d-none'>
                                Compras: <span id="compras-texto"></span>
                            </div>
                            <div id="preco" class='d-none'>
                                Preço: <span id="preco-texto"></span>
                            </div>
                        </div>
                        <img src="" id='desenho-exibicao'>
                    </div>
                </a>
            </div>
            <div id="lista-produtos-div">
                <ul id='lista-produtos'>
                    @foreach($produtos as $produto)
                        @if(isset($produto['estoque']))
                            <li class='thumb-produto book_teste_{{ $produto['codigo_produto'] }}' style='background-image: url("{{ $produto['imagem'] }}")' 
                                data-desenho='{{ $produto['codigo_produto'] }}' 
                                data-caracteristicas='{{ $produto['caracteristicas'] }}' 
                                data-origem='{{ $produto['origem'] }}' 
                                data-gramatura='{{ $produto['gramatura'] }}' data-largura='{{ $produto['largura'] }}' 
                                data-estoque='{{ $produto['estoque'] }}' data-preco='{{ $produto['preco'] }}'
                                data-ncm='{{ $produto['ncm'] }}' data-rendimento='{{ $produto['rendimento'] }}'  
                                data-composicao='{{ $produto['composicao'] }}' data-compras='{{ $produto['compras'] }}' 
                                data-cliente='{{ $produto['cliente'] }}' 
                                @if (isset($produto['grupo']))
                                    data-grupo='{{ $produto['grupo'] }}'
                                @endif 
                                @if(isset($produto['marca']))
                                    data-marca='{{ $produto['marca'] }}' 
                                @endif 
                                @if(isset($produto['linha']))
                                    data-linha='{{ $produto['linha'] }}'
                                @endif 
                                @if(isset($produto['codigo_produto'])) 
                                    data-descricao='{{ $produto['descricao'] }}' data-codigo_produto='{{ $produto['codigo_produto'] }}' 
                                @endif>
                                <div class='texto-imagem-thumb-book'>
                                    {{ $produto['codigo_produto'] }} 
                                    @if(!empty($produto['estoque']) && $produto['estoque'] != '0,00')
                                        <br>Estoque: {{ $produto['estoque'] }}
                                    @endif 
                                    @if(!empty($produto['compras']) && $produto['compras'] != '0,00')
                                        <br> Compras: {{ $produto['compras'] }}
                                    @endif
                                    @if(!empty($produto['preco']) && $produto['preco'] != '0,00')
                                        <br> Preco: {{ $produto['preco'] }}
                                    @endif
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
        <div class='info-book'>
            <div class='info-titulo'>
                <h4>Informações do produto</h4>
            </div>
            <hr>
            <div class='texto-book'>
                <p>
                    <span id="produto_selecionado_mostrar" class="d-none"><b>Produto:</b> @if (isset($produtos[0]['codigo_produto'])) {{ $produtos[0]['codigo_produto'] }} @else {{ '' }} @endif  </span>
                    <span id="carrinho_selecionado_mostrar" class="d-none"><button type="button" id="btn-carrinho-book-selecionado" data-cliente='{{ $cliente }}' data-title="Adicionar Carrinho {{ $cliente }}" data-id="{{ $id }}" @if (isset($produtos[0]['codigo_produto'])) data-produto="{{ $produtos[0]['codigo_produto']}}" @else data-produto="" @endif  data-dados_cliente="{{ $dadosCliente }}"  data-filtro='{{ $filtro }}' data-pedido_id='{{ $pedido_id }}' class="btn btn-success"><i class="bt-carrinho-comprar"></i>&nbsp&nbsp&nbsp&nbspComprar</button><span id="btn-carrinho"></span></span><br>
                    <span id="produto_mostrar" class="d-none"><b>Produto:</b> <span id="produto-book"></span></span>
                    <span id="carrinho" class="d-none"><button type="button" id="btn-carrinho-book" data-cliente='{{ $cliente }}' data-title="Adicionar Carrinho {{ $cliente }}" data-id="{{ $id }}" data-produto="" data-dados_cliente="{{ $dadosCliente }}"  data-filtro='{{ $filtro }}' data-pedido_id='{{ $pedido_id }}' class="btn btn-success"><i class="bt-carrinho-comprar"></i>&nbsp&nbsp&nbsp&nbspComprar</button><span id="btn-carrinho"></span><br/></span><br>
                    @if(!empty($artigo_numero))<b>Artigo:</b> {{ $numero_book }} <br/>@endif
                    <span id="preco_mostrar" class="d-none"><b>Preço:</b> <span id="preco-book"></span></span><br/>
                    @if(!empty($segmento))<b>Segmento:</b> {{ $segmento }}<br>@endif
                    <span id="caracteristicas_mostrar" class="d-none"><b>Características:</b> <span id="caracteristicas-texto"></span><br/></span>
                    @if(!empty($pecas))<b>Peças de:</b> {{ $pecas }}<br>@endif
                    <span id="origem_mostrar" class="d-none"><b>Origem:</b> <span id="origem-texto"></span><br/></span>
                </p>

                @if(!empty($img_instrucoes_lavagem))
                    <img src="{{ $img_instrucoes_lavagem }}" width= "30%">
                @endif

                <p>
                    <span id="ncm" class="d-none"><b>NCM:</b> <span id="ncm-texto"></span><br></span>
                    <span id="gramatura"><b>Gramatura:</b> <span id="gramatura-texto"></span><br></span>
                    <span id="largura"><b>Largura:</b> <span id="largura-texto"></span><br></span>
                    <span id="rendimento"><b>Rendimento:</b> <span id="rendimento-texto"></span><br></span>
                    <span id="composicao" class="d-none"><b>Composição:</b> <span id="composicao-texto"></span><br/></div></span>
                    

                <p>
                    <span id="grupo_mostrar" class="d-none"><b>Grupo:</b> <span id="grupo-texto"></span><br></span>
                    <span id="marca_mostrar" class="d-none"><b>Marca:</b> <span id="marca-texto"></span><br></span>
                    <span id="linha_mostrar" class="d-none"><b>Linha:</b> <span id="linha-texto"></span><br></span>
                    <span id="descricao_mostrar" class="d-none"><b>Descrição:</b> <span id="descricao-texto"></span><br></span>
                    <span id="ean_mostrar" class="d-none"><b>EAN:</b> <span id="ean-texto"></span><br/></span>
                </p>
                </p>
            </div>  
        </div>  
    </div>
</div>

@endsection

@section('script-footer')
    var book_liso = true;

    $(document).ready(function(){

        dados($(document).find('.thumb-desenho').first());

        $('.thumb-desenho').on('click', function(){
            dados($(this));
        })

        $(document).find("#btn-carrinho-book").off("click");
        $(document).find("#btn-carrinho-book").on("click", function(event){
            event.stopPropagation();
            modalAdicionarCarrinho($(this));
        });

        $(document).find("#btn-carrinho-book-selecionado").off("click");
        $(document).find("#btn-carrinho-book-selecionado").on("click", function(event){
            event.stopPropagation();
            modalAdicionarCarrinho($(this));
        });

        $(document).find("#btn-informar-cliente").off("click");
        $(document).find("#btn-informar-cliente").on("click", function(event){
            event.stopPropagation();
            modalAdicionarCarrinho($(this), true);
        });

        $(document).find(".bt-carrinho-book").off("click");
        $(document).find(".bt-carrinho-book").on("click", function(event){
            event.stopPropagation();
            modalVisualizarCarrinho($(this));
        });
        
        $(document).find('.thumb-produto').on('click', function(){
            $(document).find('#desenho-texto').html($(this).data('desenho'));
            $(document).find('#desenho-exibicao').prop('src', $(this).css('background-image').replace(/(url\(|\)|")/g, ''));
            $(document).find('#desenho-link').prop('href', $(this).css('background-image').replace(/(url\(|\)|")/g, ''));

            if($(this).data('estoque') != '0,00' && String($(this).data('estoque')).length > 0){
                $(document).find('#estoque-texto').html($(this).data('estoque'));
                $(document).find('#estoque').removeClass('d-none');
            }
            else{
                $(document).find('#estoque').addClass('d-none');
            }

            if($(this).data('compras') != '0,00' && String($(this).data('compras')).length > 0){
                $(document).find('#compras-texto').html($(this).data('compras'));
                $(document).find('#compras').removeClass('d-none');
            }
            else{
                $(document).find('#compras').addClass('d-none');
            }

            if($(this).data('preco') != '0,00' && String($(this).data('preco')).length > 0){
                $(document).find('#preco-texto').html($(this).data('preco'));
                $(document).find('#preco').removeClass('d-none');
            }
            else{
                $(document).find('#preco').addClass('d-none');
            }
            
            $(document).find('#desenho').removeClass('d-none');

            if(String($(this).data('gramatura')).length > 0){
                $(document).find('#gramatura-texto').html($(this).data('gramatura'));
            }
            else{
                $(document).find('#gramatura-texto').html('');
            }

            if(String($(this).data('ncm')).length > 0){
                $(document).find('#ncm-texto').html($(this).data('ncm'));
                $(document).find('#ncm').removeClass('d-none');
            }
            else{
                $(document).find('#ncm').addClass('d-none');
            }

            if(String($(this).data('rendimento')).length > 0){
                $(document).find('#rendimento-texto').html($(this).data('rendimento'));
            }
            else{
                $(document).find('#rendimento-texto').html('');
            }

            if(String($(this).data('grupo')).length > 0){
                $(document).find('#grupo-texto').html($(this).data('grupo'));
                $(document).find('#grupo_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#grupo-texto').html('');
                $(document).find('#grupo_mostrar').addClass('d-none');
            }

            if(String($(this).data('marca')).length > 0){
                $(document).find('#marca-texto').html($(this).data('marca'));
                $(document).find('#marca_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#marca-texto').html('');
                $(document).find('#marca_mostrar').addClass('d-none');
            }

            if(String($(this).data('linha')).length > 0){
                $(document).find('#linha-texto').html($(this).data('linha'));
                $(document).find('#linha_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#linha-texto').html('');
                $(document).find('#linha_mostrar').addClass('d-none');
            }

            if(String($(this).data('descricao')).length > 0){
                $(document).find('#descricao-texto').html($(this).data('descricao'));
                $(document).find('#descricao_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#descricao-texto').html('');
                $(document).find('#descricao_mostrar').addClass('d-none');
            }
            if(String($(this).data('caracteristicas')).length > 0){
                $(document).find('#caracteristicas-texto').html($(this).data('caracteristicas'));
                $(document).find('#caracteristicas_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#caracteristicas-texto').html('');
                $(document).find('#caracteristicas_mostrar').addClass('d-none');
            }

            if(String($(this).data('origem')).length > 0){
                $(document).find('#origem-texto').html($(this).data('origem'));
                $(document).find('#origem_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#origem-texto').html('');
                $(document).find('#origem_mostrar').addClass('d-none');
            }

            if(String($(this).data('ean')).length > 0){
                $(document).find('#ean-texto').html($(this).data('ean'));
                $(document).find('#ean_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#ean-texto').html('');
                $(document).find('#ean_mostrar').addClass('d-none');
            }
            if(String($(this).data('preco')).length > 0){
                $(document).find('#preco-book').html($(this).data('preco'));
                $(document).find('#preco_mostrar').removeClass('d-none');
            }
            else{
                $(document).find('#preco-book').html('');
                $(document).find('#preco_mostrar').addClass('d-none');
            }
            if(String($(this).data('codigo_produto')).length > 0){
                $(document).find('#produto_codigo').val($(this).data('codigo_produto'));
                $(document).find('#produto-book').html($(this).data('codigo_produto'));
                $(document).find('#produto_selecionado_mostrar').addClass('d-none');
                $(document).find('#carrinho_selecionado_mostrar').addClass('d-none');
                $(document).find('#produto_mostrar').removeClass('d-none');
                $(document).find('#carrinho').removeClass('d-none');
            }
            else{
                $(document).find('#produto_selecionado_mostrar').removeClass('d-none');
                $(document).find('#carrinho_selecionado_mostrar').removeClass('d-none');
                $(document).find('#produto_mostrar').addClass('d-none');
                $(document).find('#carrinho').addClass('d-none');
                $(document).find('#produto-book').html('');
            }
        });

        dadosProdutos = $(document).find('.thumb-produto').first();
        if(String(dadosProdutos.data('grupo')).length > 0){
            $(document).find('#grupo-texto').html(dadosProdutos.data('grupo'));
            $(document).find('#grupo_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#grupo-texto').html('');
            $(document).find('#grupo_mostrar').addClass('d-none');
        }
        if(String(dadosProdutos.data('marca')).length > 0){
            $(document).find('#marca-texto').html(dadosProdutos.data('marca'));
            $(document).find('#marca_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#marca-texto').html('');
            $(document).find('#marca_mostrar').addClass('d-none');
        }

        if(String(dadosProdutos.data('linha')).length > 0){
            $(document).find('#linha-texto').html(dadosProdutos.data('linha'));
            $(document).find('#linha_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#linha-texto').html('');
            $(document).find('#linha_mostrar').addClass('d-none');
        }

        if(String(dadosProdutos.data('descricao')).length > 0){
            $(document).find('#descricao-texto').html(dadosProdutos.data('descricao'));
            $(document).find('#descricao_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#descricao-texto').html('');
            $(document).find('#descricao_mostrar').addClass('d-none');
        }

        if(String(dadosProdutos.data('caracteristicas')).length > 0){
            $(document).find('#caracteristicas-texto').html(dadosProdutos.data('caracteristicas'));
            $(document).find('#caracteristicas_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#caracteristicas-texto').html('');
            $(document).find('#caracteristicas_mostrar').addClass('d-none');
        }

        if(String(dadosProdutos.data('origem')).length > 0){
            $(document).find('#origem-texto').html(dadosProdutos.data('origem'));
            $(document).find('#origem_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#origem-texto').html('');
            $(document).find('#origem_mostrar').addClass('d-none');
        }

        if(String(dadosProdutos.data('ean')).length > 0){
            $(document).find('#ean-texto').html(dadosProdutos.data('ean'));
            $(document).find('#ean_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#ean-texto').html('');
            $(document).find('#ean_mostrar').addClass('d-none');
        }


        $(document).find("#desenho-link").fancybox(
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
        
        $(document).find('#buscar').on('click', function(){
            buscaAvancada();
        })

        $(document).find('#buscar_produto').on('click', function(){
            buscaProduto();
        })

        $(document).find('#grupo').autocomplete(autoCompleteOptions('grupo'));
        $(document).find('#marca').autocomplete(autoCompleteOptions('marca'));
        $(document).find('#linha').autocomplete(autoCompleteOptions('linha'));
        $(document).find('#descricao').autocomplete(autoCompleteOptions('descricao'));
        $(document).find('#codigo_produto').autocomplete(autoCompleteOptions('codigo_produto'));

        @if($adicionar_automatica_no_carrinho)
            modalAdicionarCarrinhoAutomatico($(document).find('#codigo_produto_selecionado').html());
        @endif
    });

    function dados($elemento, contador = 0){

        $dados = $elemento;

        $(document).find('#desenho-exibicao').prop('src', $elemento.css('background-image').replace(/(url\(|\)|")/g, ''));
        $(document).find('#desenho-link').prop('href', $elemento.css('background-image').replace(/(url\(|\)|")/g, ''));

        if(String($elemento.data('desenho')).length > 0){
            $(document).find('#desenho-texto').html('Desenho: ' + $elemento.data('desenho'));
            $(document).find('#desenho').removeClass('d-none');
        }
        else{
            $(document).find('#desenho').addClass('d-none');
        }
    
        if($elemento.data('estoque') != '0,00' && $elemento.data('estoque').length > 0){
            $(document).find('#estoque-texto').html($elemento.data('estoque'));
            $(document).find('#estoque').removeClass('d-none');
        }
        else{
            $(document).find('#estoque').addClass('d-none');
        }


        if($elemento.data('compras') != '0,00' && $elemento.data('compras').length > 0){
            $(document).find('#compras-texto').html($elemento.data('compras'));
            $(document).find('#compras').removeClass('d-none');
        }
        else{
            $(document).find('#compras').addClass('d-none');
        }

        if(String($elemento.data('gramatura')).length > 0){
            $(document).find('#gramatura-texto').html($elemento.data('gramatura'));
        }
        else{
            $(document).find('#gramatura-texto').html('');
        }

        if($elemento.data('largura').length > 0){
            $(document).find('#largura-texto').html($elemento.data('largura'));
        }
        else{
            $(document).find('#largura-texto').html('');
        }

        if($elemento.data('composicao').length > 0){
            $(document).find('#composicao-texto').html($elemento.data('composicao'));
            $(document).find('#composicao').removeClass('d-none');
        }
        else{
            $(document).find('#composicao').addClass('d-none');
        }

        if(String($elemento.data('ncm')).length > 0){
            $(document).find('#ncm-texto').html($elemento.data('ncm'));
            $(document).find('#ncm').removeClass('d-none');
        }
        else{
            $(document).find('#ncm').addClass('d-none');
        }

        if(String($elemento.data('rendimento')).length > 0){
            $(document).find('#rendimento-texto').html($elemento.data('rendimento'));
        }
        else{
            $(document).find('#rendimento-texto').html('');
        }

        if(String($elemento.data('codigo_produto')).length > 0){
            $(document).find('#produto_codigo').val($elemento.data('codigo_produto'));
            $(document).find('#carrinho').removeClass('d-none');
        }
        else{
            $(document).find('#carrinho').html('');
        }
        if(String($elemento.data('preco')).length > 0){
            $(document).find('#preco-book').html($elemento.data('preco'));
            $(document).find('#preco_mostrar').removeClass('d-none');
        }
        else{
            $(document).find('#preco-book').html('');
            $(document).find('#preco_mostrar').addClass('d-none');
        }
        if(String($elemento.data('codigo_produto')).length > 0){
            $(document).find('#produto_codigo').val($elemento.data('codigo_produto'));
            $(document).find('#info-produto-selecionado').addClass('d-none');
            $(document).find('#info-produto').removeClass('d-none');
        }
        else{
            $(document).find('#info-produto-selecionado').removeClass('d-none')
            $(document).find('#info-produto').addClass('d-none');
        }
        if(String($elemento.data('codigo_produto')).length > 0){
            $(document).find('#produto_codigo').val($elemento.data('codigo_produto'));
            $(document).find('#produto-book').html($elemento.data('codigo_produto'));
            $(document).find('#produto_selecionado_mostrar').addClass('d-none');
            $(document).find('#carrinho_selecionado_mostrar').addClass('d-none');
            $(document).find('#produto_mostrar').removeClass('d-none');
            $(document).find('#carrinho').removeClass('d-none');
        }
        else{
            $(document).find('#produto_selecionado_mostrar').removeClass('d-none');
            $(document).find('#carrinho_selecionado_mostrar').removeClass('d-none');
            $(document).find('#produto_mostrar').addClass('d-none');
            $(document).find('#carrinho').addClass('d-none');
            $(document).find('#produto-book').html('');
        }

        soma_contador = parseInt($(document).find('#contador').html()) + contador;
        $(document).find('#contador').html(soma_contador);


        $(document).find('#grupo-texto').html('');
        $(document).find('#grupo_mostrar').addClass('d-none');
        $(document).find('#marca-texto').html('');
        $(document).find('#marca_mostrar').addClass('d-none');
        $(document).find('#linha-texto').html('');
        $(document).find('#linha_mostrar').addClass('d-none');
        $(document).find('#descricao-texto').html('');
        $(document).find('#descricao_mostrar').addClass('d-none');
        $(document).find('#ean-texto').html('');
        $(document).find('#ean_mostrar').addClass('d-none');
        $(document).find('#btn-carrinho').html('');
        $(document).find('#carrinho').addClass('d-none');
        $(document).find('#preco_mostrar').addClass('d-none');
        $(document).find('#preco-book').html('');
        $(document).find('#produto-book').html('');
        $(document).find('#produto_mostrar').addClass('d-none');
        $(document).find('#produto_selecionado_mostrar').removeClass('d-none');
        $(document).find('#carrinho_selecionado_mostrar').removeClass('d-none');
    }

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

    function modalAdicionarCarrinho($this, informar_cliente = false){
        var produto = $($this).data("produto");
        var title = $($this).data("title");
        var pedido_id = $($this).data("pedido_id");
        var dados_cliente_id = $($this).data("dados_cliente");
        var carrinho = 'true';
        var id = $($this).data("id");
        var filtro = $($this).data("filtro");

        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao.modal.adicionar') }}',
            data: {
                _token: "{{ csrf_token() }}",
                 carrinho: carrinho,
                 produto_codigo: $(document).find('#produto_codigo').val(),
                 id : pedido_id,
                 dados_cliente_id : dados_cliente_id,
                 book_id : id,
                 filtro : filtro,
                 produto : produto,
                 informar_cliente: informar_cliente
                },
            method: 'POST',
            success: function(body){
                if(body.status === 'success'){
                    if(body.response.estoque === false){
                        message("Atenção", "Produto sem estoque pronto entrega!");
                    }
                }else{
                    createModal("modal_adicionar_carrinho", title, body, 'modal-lg');
                }  
            },
            error: function(body){
                message("Atenção", body.responseJSON.message);
            }
        });
    }  

    function modalAdicionarCarrinhoAutomatico(produto = null, informar_cliente = false){
        var title = "Adicionar Carrinho {{ $cliente }}";
        var pedido_id = '{{ $pedido_id }}';
        var dados_cliente_id = "{{ $dadosCliente }}";
        var carrinho = 'true';
        var id = "{{ $id }}";
        var filtro = '{{ $filtro }}' ;
        var informar_cliente = informar_cliente;
        var produto_codigo_automatico = "{{ $codigo_produto }}";

        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao.modal.adicionar') }}',
            data: {
                _token: "{{ csrf_token() }}",
                 carrinho: carrinho,
                 produto_codigo: produto_codigo_automatico,
                 id : pedido_id,
                 dados_cliente_id : dados_cliente_id,
                 book_id : id,
                 filtro : filtro,
                 produto : produto,
                 informar_cliente : informar_cliente
                },
            method: 'POST',
            success: function(body){
                if(body.status === 'success'){
                    if(body.response.estoque === false){
                        message("Atenção", "Produto sem estoque pronto entrega!");
                    }
                }else{
                    createModal("modal_adicionar_carrinho", title, body, 'modal-lg');
                }
            },
            error: function(body){
                message("Atenção", body.responseJSON.message);
            }
        });
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

    function atualizarProdutoBook(codigo_produto){

        $(document).find('.book_teste_'+codigo_produto).remove();

    }
@endsection