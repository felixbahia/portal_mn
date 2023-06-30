<meta http-equiv="Content-Type" content="application/pdf; charset=utf-8"/>
<style>
.grupo-container{
    position: absolute;
    width: 100%;
    height: 50%;
    top: 0;
    left: 0;
    background-position: center center;
    background-repeat: no-repeat;
}
.ficha-tecnica-titulo{
    font-family: 'Quire Sans Ligth';
    font-size:25px;
    font-weight:bold;
    color:#273C76;
}
#logo{
    width: 100px;
    margin: 10px;
}
.img_instrucoes_lavagem{
    max-width: 100%;
}
</style>
<htmlpageheader name="header">
        <div style="text-align: right"><img id='logo' src="{{ URL::asset('images/logomn.jpg') }}"></div>
</htmlpageheader>
<sethtmlpageheader name="header" value="on" show-this-page="1" />
<div class="row"> 
    <div class="grupo-container" style="background-image: url('{{ URL::asset($imagem_pdf) }}')">
    </div>
</div>
<div class="row"> 
    <p>
        <span class="ficha-tecnica-titulo"> {{ $nome }} </span>
    </p>
    <p>
    <h2>Ficha Técnica</h2>
    <table class="table table-striped">
        <tbody>
            <tr>
                <td><b>Característica:</b> {{ $caracteristicas }}</td>
                <td><b>Composição:</b> {{ $composicao }}</td>
                <td><b>Encolhimento %:</b> {{ $encolhimento }}</td>
            </tr>
            <tr>
                <td><b>Peças de:</b> {{ $pecas }}</td>
                <td><b>Origem:</b> {{ $origem }}</td>
                <td><b>NCM:</b> {{ $ncm }}</td>
            </tr>
            <tr>
                <td><b>Gramatura G/M²  (+/- 5%):</b> {{ $gramatura_gm2 }}</td>
                <td><b>Gramatura Linear G/M:</b> {{ $gramatura_linear }}</td>
                <td><b>Larg. (+/- 2CM):</b> {{ $largura }}</td>
            </tr>
            <tr>
                <td><b>Rendimento MT/KG:</b> {{ $rendimento }}</td>
                <td><b>EAN:</b> {{ $ean }}</td>
                <td><b>Unidade:</b> {{ $unidade }}</td>
            </tr>
            <tr>
                <td><b>Peso Bruto:</b> {{ $peso_bruto }}</td>
                <td><b>Instrução de Lavagem:</b></td>
                <td><img src="{{ $img_instrucoes_lavagem }}" class="img_instrucoes_lavagem" style="width: 150px;height: 26px;"></td>
            </tr>
        </tbody>
    </table>
    </p>
</div>



