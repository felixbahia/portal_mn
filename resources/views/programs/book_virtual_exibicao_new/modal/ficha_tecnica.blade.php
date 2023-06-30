@extends('layouts.page-dialog')

@section('content')
<div class="row">
    <div class="content col-sm-6">
        <a href="{{ $imagem }}" class="foto-thumb"><img class="img-thumbnail" src="{{ $imagem }}" /></a>
    </div>
    <div class="content-table col-sm-6">
        <htmlpageheader name="header">
            <div style="text-align: right"><img id='logo' src="{{ URL::asset('images/logomn.jpg') }}"></div>
        </htmlpageheader>
        <sethtmlpageheader name="header" value="on" show-this-page="1" />
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
    </div>
</div>
<script>
    $('[data-toggle="tooltip"]').tooltip();
    $(document).ready(function(){
        $(document).find(".foto-thumb").fancybox(
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
    });
</script>
@endsection
