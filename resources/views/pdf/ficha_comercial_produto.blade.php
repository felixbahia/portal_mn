@extends('layouts.page-dialog')
@section('content')
<div id="app">
    <div id="detalhes_ficha_comercial" name="detalhes_ficha_comercial" class="ficha_comercial_detalhes" style="
    border-right-color: #d4d4d4 !important;
    border-right: 1px;
    border-right-style: solid;">
        <div style="text-align: right"><img src="{{ $logo_tipo }}" width="80"></div>
        <h4><b>Grupo:</b> {{ $produto['grupo']}}</h4></br>
        <label><h5>{{ $produto['produto']}}</h5></label></br>
        <hr>
        <table class="table table-striped">
            <tbody>
                <tr>
                    <td><b>Característica:</b> {{ $produto['caracteristica']}}</td>
                    <td><b>Composição:</b> {{ $produto['composicao_nasajon']}}</td>
                    <td><b>Encolhimento %:</b> {{ $produto['encolhimento']}}</td>
                </tr>
                <tr>
                    <td><b>Peças de:</b> {{ $produto['pecas_de']}}</td>
                    <td><b>Origem:</b> {{ $produto['origem']}}</td>
                    <td><b>NCM:</b> {{ $produto['ncm']}}</td>
                </tr>
                <tr>
                    <td><b>Gramatura G/M² (+/- 5%):</b> {{ $produto['gramatura']}}</td>
                    <td><b>Gramatura Linear G/M:</b> {{ $produto['gramatura_linear']}}</td>
                    <td><b>Larg. (+/- 2CM):</b> {{ $produto['largura']}}</td>
                </tr>
                <tr>
                    <td><b>Rendimento:</b> {{ $produto['rendimento']}}</td>
                    <td><b>EAN:</b> {{ $produto['ean']}}</td>
                    <td><b>Unidade:</b> {{ $produto['unidade']}}</td>
                </tr>
                @if(!empty($produto['titulo_trama']) || !empty($produto['titulo_urdume']))
                    <tr>
                        <td><b>Título Trama:</b> {{ $produto['titulo_trama']}}</td>
                        <td><b>Título Urdume:</b> {{ $produto['titulo_urdume']}}</td>
                        <td></td>
                    </tr>
                @endif
                <tr>
                    <td><b>Peso Bruto:</b> {{ $produto['peso_bruto']}}</td>
                    <td><b>Instrução de Lavagem:</b></td>
                    <td><img src="{{ $produto['img_instrucoes_lavagem'] }}" class="img_instrucoes_lavagem" style="width: 150px;height: 26px;"></td>
                </tr>
                @if(!empty($produto['composicao']))
                    <tr>
                        <td colspan="3"><b>Composição:</b></td>
                    </tr>
                    @foreach($produto['composicao'] as $composicao)
                        <tr>
                            <td>{{ $composicao['codigo']}}</td>
                            <td>{{ $composicao['descricao']}}</td>
                            <td>{{ $composicao['quantidade']}}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>        
        <div id="desenho-exibicao-div" >
            <a href="{{ $produto['imagem'] }}" id="desenho-link" target="_blank">
                <div id="desenho-container-div">
                    <img src="{{ $produto['imagem'] }}" id="desenho-exibicao">
                </div>
            </a>
        </div>
        <div class="row position-relative float-right">
            <strong>
                “Nossos ensaios apontam para o encolhimento informado nessa ficha técnica; contudo, devido a possíveis variações no processo de produção do tecido, pode ocorrer uma oscilação de 2% para mais ou para menos nesse parâmetro.
Sugerimos testes prévios na peça confeccionada para uma maior segurança e padronização do produto final.
Não nos responsabilizamos pelo uso dos nossos produtos fora das especificações que constam nesse manual.”
            </strong>
        </div>
    </div>
</div>
@endsection