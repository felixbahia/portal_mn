<meta http-equiv="Content-Type" content="application/pdf; charset=utf-8"/>
<style>
.grupo-container{
    position: absolute;
    width: 100%;
    height: 70%;
    margin-top: 35px;
    left: 0;
    background-position: center center;
    background-repeat: no-repeat;
}
.desenho{
    text-align: center;
}
.ficha-tecnica-titulo{
    font-family: 'Quire Sans Ligth';
    font-size:25px;
    font-weight:bold;
    color:#273C76;
}
.desenho-titulo{
    display: inline-block;
    text-align: left;
    font-family: 'Quire Sans Ligth';
    font-size:25px;
    font-weight:bold;
    color:#273C76;
}
.ficha-tecnica-texto{
    font-family: 'Quire Sans Ligth';
    font-size:20px !important;
    color:#4F5569;
}
.titulo-texto{
    font-family: 'Quire Sans Ligth';
    font-size:25px !important;
    color:#4F5569;
}
.instrucao-lavagem{
    text-align: center;
    padding: 50px;
}
.texto-descricao{
    font-family: 'Quire Sans Ligth';
    font-size: 11px !important;
    color:#4F5569;
    font-weight:bold;
}
.galeria-imagem-sm{
    display: table;
    float: left;
    width: 150px;
    height: 150px;
    padding: 30px;
}
#logo{
    width: 100px;
    margin: 10px;
}
.quebra-pagina {
     page-break-before: always; 
}
.texto-rodape{
    margin-bottom: 10px; 
    color: #223679;
    text-align: left;
    font-size: 9px !important;
    font-family: 'OpenSans-Light';
}
</style>
<htmlpageheader name="header">
        <div style="text-align: right"><img id='logo' src="{{ URL::asset('images/logomn.jpg') }}"></div>
</htmlpageheader>
<sethtmlpageheader name="header" value="on" show-this-page="1" />
<div class="row"> 
    <img class="grupo-container" src="{{ URL::asset($imagem_pdf) }}"/>
</div>
<div class="row"> 
    <p>
        <span class="ficha-tecnica-titulo">{{ $nome }}</span>
    </p>
</div>
<div class="row">
    <table class="table table-striped ficha-tecnica-texto">
        <tbody>
            <tr>
                <td><span><span style="font-weight:bold">Composição: </span>{{ $composicao }}</span></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><span><span style="font-weight:bold">Unidade: </span>{{ $unidade }}</span></td>
            </tr>
            <tr>
                <td><span><span style="font-weight:bold">Larg. (+/- 2CM): </span>{{ $largura }}</span></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><span><span style="font-weight:bold">Gramatura Linear G/m: </span>{{ $gramatura_linear }}</span></td>
            </tr>
            <tr>
                <td><span><span style="font-weight:bold">Gramatura G/m²(+/- 5%): </span>{{ $gramatura_gm2 }}</span></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><span><span style="font-weight:bold">Encolhimento: </span>{{ $encolhimento }}%</span></td>
            </tr>
            <tr>
                <td><span><span style="font-weight:bold">Rendimento MT/KG: </span>{{ $rendimento }}</span></td>
            </tr>
        </tbody>
    </table>
</div>
<div class="row">
    <div class="instrucao-lavagem">
        <p> 
        <img width="50%"  src="{{ URL::asset($img_instrucoes_lavagem) }}"/>
        </p>
    </div>
</div>
<div class="texto-rodape">
    <span>*As cores dos tecidos apresentam nuances e nos esforçamos para que as imagens reproduzam com precisão as tonalidades
    reais dos tecidos. No entanto, pequenas variações podem ocorrer devido a fatores como iluminação, configurações de tela
    e outras condições externas. Estamos sempre trabalhando para melhorar a fidelidade das cores em nossas imagens.
    </span>
</div>
<div style="margin-bottom: 10px; text-align: right">
    Gerado {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
</div>
@foreach($desenhos as $desenho)
    <sethtmlpageheader name="header" value="off" show-this-page="1" />
    <div class="quebra-pagina">
        <div class="row">
            <div class="desenho-titulo">
                <span>{{ $desenho['codigo_desenho'] }}</span>
            </div>
        </div>
        <div class="row"> 
            <div class="desenho">
                <img width="100%"  height="50%" src="{{ URL::asset($desenho['imagem_desenho_zoom']) }}"/>
            </div>
        </div>
        <div class="row">
            <div class="row justify-content-center">
                @foreach($desenho[$desenho['codigo_desenho']] as $produto)
                    <sethtmlpageheader name="header" value="off" show-this-page="1" />
                    <div class="galeria-imagem-sm">
                        <img src="{{ URL::asset($produto['imagem_cor']) }}"/>
                        <div class="texto-descricao">
                            Código: {{ $produto['codigo_produto'] }}<br/>
                            Estoque: {{ $produto['estoque_total'] }}<br/>
                            Compras: {{ $produto['compras'] }}<br/>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach   



