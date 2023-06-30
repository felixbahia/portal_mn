<meta http-equiv="Content-Type" content="application/pdf; charset=utf-8"/>
<style>
body{
    color: #FFF;
    background: linear-gradient(to right, #003554 0%, #005c8d 100%);
}

.info-book{
    width: 100%;
    color: #FFF;
    margin: auto;
}

.texto-book{
    overflow: auto;
    margin: auto;
    width: 50%;
    height: 100%;
}

#desenho-container-div{
    margin: 20px auto;
    width: 450px;
    height: 639px;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
}

#produto-container-div{
    float: left;
    display: block;
    margin: 0 10px 10px 20px;
    width: 300px;
    height: 300px;
    page-break-inside: avoid;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
}

.texto-imagem-book{
    display: flex;
    color: black;
    background: rgba(255,255,255,0.75);
    width: 10px;
    height: 10px;
}

#logo{
    width: 100px;
    margin: 10px;
}

</style>

<body>

    <htmlpageheader name="header">
        <div style="text-align: right"><img id='logo' src="{{ asset('images/logotipo.png') }}"></div>
    </htmlpageheader>

    <htmlpagefooter name="footer">
        <hr>
        <div style="margin-bottom: 10px; text-align: right">Gerado {{ date('d/m/Y H:i:s') }}</div>
    </htmlpagefooter>

    <setpageheader name="header" value="on" show-this-page="1">
    <setpagefooter name="footer" value="on" show-this-page="1">
    @if(isset($artigo_nome) && !empty($artigo_nome))
    <div id='titulos-book'>
        <h2>Book Virtual</h2>
        Book {{ $numero_book }}
        <h3>{{ $artigo_nome }}</h3>
    </div>
        
    <div class='info-book'>
        <div class='info-titulo'>
            <h4>Informações do artigo</h4>
        </div>
        <hr>
        <div class='texto-book'>
            <p>
                <b>Artigo:</b> {{ $artigo_numero }}<br>
                @if(!empty($segmento))<b>Segmento:</b> {{ $segmento }}<br>@endif
                @if(!empty($caracteristicas))<b>Características:</b> {{ $caracteristicas }}<br>@endif
                @if(!empty($pecas))<b>Peças de:</b> {{ $pecas }}<br>@endif
                @if(!empty($origem))<b>Origem:</b> {{ $origem }}<br>@endif
            </p>
    
            @if(!empty($img_instrucoes_lavagem))
            <img src="{{ $img_instrucoes_lavagem }}" class="img_instrucoes_lavagem">
            @endif
    
            <p>
                @if(isset($produtos[0]['gramatura']) && !empty($produtos[0]['gramatura']))<b>Gramatura:</b> {{ $produtos[0]['gramatura'] }}<br>@endif
                @if(isset($produtos[0]['largura']) && !empty($produtos[0]['largura']))<b>Largura:</b> {{ $produtos[0]['largura'] }}<br>@endif
                @if(isset($produtos[0]['composicao']) && !empty($produtos[0]['composicao']))<b>Composição:</b> {{ $produtos[0]['composicao'] }}<br>@endif
                @if(isset($produtos[0]['rendimento']) && !empty($produtos[0]['rendimento']))<b>Rendimento:</b> {{ $produtos[0]['rendimento'] }}@endif
            </p>
        </div>  
    </div>
    @else
    <div id='titulos-book'>
        <h2>Book Virtual</h2>
    </div>

    <div class='info-book'>
        <div class='info-titulo'>
            <h4>Resultado das Buscas:</h4>
        </div>
        <hr>
    
        <div class='texto-book'>
            <p>
                @if(isset($filtros['grupo']) && !is_null($filtros['grupo']))<b>Grupo:</b> {{ $filtros['grupo'] }}<br>@endif
                @if(isset($filtros['marca']) && !is_null($filtros['marca']))<b>Marca:</b> {{ $filtros['marca'] }}<br>@endif
                @if(isset($filtros['linha']) && !is_null($filtros['Linha']))<b>Linha:</b> {{ $filtros['linha'] }}<br>@endif
                @if(isset($filtros['descricao']) && !is_null($filtros['descricao']))<b>Descrição do Produto:</b> {{ $filtros['descricao'] }}<br>@endif
                @if(isset($filtros['codigo_produto']) && !is_null($filtros['codigo_produto']))<b>Código do Produto:</b> {{ $filtros['codigo_produto'] }}<br>@endif
            </p>
        </div>
    </div>  
    @endif

    
    <pagebreak>
    @foreach($desenhos as $desenho_key => $desenho)
        
        @if(isset($artigo_nome) && !empty($artigo_nome))
        <h4>Desenho</h4>
        @else
        <h4>Artigo</h4>
        @endif
        <hr>
        <div id="desenho-container-div" style="background-image: url('{{ $desenho['imagem'] }}');">
            <div class="texto-imagem-book">
                @if(isset($desenho['codigo_desenho']) && !empty($desenho['codigo_desenho']))
                    Desenho: {{ $desenho['codigo_desenho'] }}<br>
                @endif
                @if(isset($desenho['artigo']) && !empty($desenho['artigo']))
                    Artigo: {{ $desenho['artigo'] }}<br>
                @endif
                @if(isset($desenho['estoque']) && !empty($desenho['estoque']))
                    Estoque: {{ $desenho['estoque'] }}<br>
                @endif
                @if(!empty($desenho['ncm']))
                    NCM: {{ $desenho['ncm'] }}
                @endif
            </div>
        </div>

        <pagebreak>

    @php
        
        $x = 0;

    @endphp

    <div>
        @foreach($produtos as $produto_key => $produto)

            @if((empty($desenho['codigo_desenho']) && empty($desenho['id'])) || (!empty($desenho['codigo_desenho']) && strpos($produto['codigo_produto'], $artigo_numero . $desenho['codigo_desenho']) === 0) || (!empty($desenho['id']) && $produto['id'] == $desenho['id']))
            
                @if($x == 6 && $produto_key != count($produto))
                <pagebreak>
                @php
                    $x = 0;
                @endphp
                @endif

                @if($x == 0)
                <h4>Produtos</h4>
                <hr>
                @endif

                @php
                    $x++;
                @endphp

                <div id="produto-container-div" style="background-image: url('{{ $produto['imagem'] }}');">
                    <div class="texto-imagem-book">
                        @if(isset($produto['codigo_produto']) && !empty($produto['codigo_produto']))
                            {{ $produto['codigo_produto'] }}<br>
                        @endif
                        @if(isset($produto['ncm']) && !empty($produto['ncm']))
                            NCM: {{ $produto['ncm'] }}<br>
                        @endif
                        @if(isset($produto['estoque']) && !empty($produto['estoque']))
                            Estoque: {{ $produto['estoque'] }}<br>
                        @endif
                    </div>
                </div>
            @endif

        @endforeach
            @if($x < 6 && $desenho_key+1 != count($desenhos))
                <pagebreak>
                @php
                    $x = 0;
                @endphp
            @endif
    </div>
    @endforeach

</body>