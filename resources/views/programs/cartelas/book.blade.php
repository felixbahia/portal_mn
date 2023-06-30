@extends('layouts.app-deslogado')

@section('content')
<div id="oi">
    <div class="content-modulos-home">
        <center>
            <p>ARTIGO: {{ $book['artigo'] }} - {{ $book['nome'] }} - ORIGEM : {{ $book['origem'] }}</p>
        </center>
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-info-adicionais">
                <thead>
                    <tr>
                        <th colspan="5" align="center">Caracteristicas Técnicas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @if(!empty($itens['produtos']))
                        <td align="center" valign="middle">{{ $itens['composicao'] }}</td>
                        <td align="center" valign="middle">
                            @if(!empty($itens['info_adicional']['informacoes_adicionais'])) 
                                @if(!empty($itens['info_adicional']['informacoes_adicionais']['largura']))
                                    Larg {{ $itens['info_adicional']['informacoes_adicionais']['largura'] }}
                                @endif
                                @if(!empty($itens['info_adicional']['informacoes_adicionais']['gramatura'])) 
                                    Grt {{ $itens['info_adicional']['informacoes_adicionais']['gramatura'] }} GML 
                                @endif 
                            @endif
                        </td>
                        <td align="center" valign="middle">{{ $book['caracteristicas'] }}</td>
                        <td align="center" valign="middle">Peças {{ $book['pecas'] }}</td>
                        <td align="center">
                            <a href="{{ asset($book["img_instrucoes_lavagem"]) }}" class="thumb-instrucao"><img src="{{ asset($book["thumb_instrucoes_lavagem"]) }}" border="0" alt="" /></a>
                        </td>
                        @else
                        <td align="center">Nenhum registro encontrado</td>
                        @endif
                    </tr>
                    
                </tbody>
            </table>
            <p>
        </div>
    </div>
    <div class="content-table">
        <table class="table table-striped table-produto-analise" id="table-produtos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="tb_number">Pronta<br> Entrega</th>
                    @foreach($header_meses as $header_mes)
                    <th class="tb_number">{!! $header_mes !!}</th>
                    @endforeach
                    <th class="tb_number">Futuro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itens['produtos'] as $produto)
                <tr>
                    <td>{!! $produto['codigo'] . $produto['foto'] !!}</td>
                    <td>{{ $produto['descricao'] }}</td>
                    <td class="tb_number">
                         <div class="bt-view-list" data-toggle="popover" data-trigger='hover' title="" data-content="@if(!empty($produto['estoque_dados'])) @foreach($produto['estoque_dados'] as $estoque)<p>{{ $estoque }}</p>@endforeach @endif" data-original-title="Estoque por empresa">
                            @if(!empty($produto['pronta_entrega_total']))
                                {{ $produto['pronta_entrega_total'] }}
                                @if(!empty($produto['estoque_dados']))
                                <div class="bt-view"></div>
                                @endif
                            @endif
                        </div>
                    </td>
                    @foreach($header_meses as $key => $header_mes)
                    <td class="tb_number">{{ $produto['quinzenas']['k_'.$key]['quantidade'] }}</td>
                    @endforeach 
                    <td class="tb_number">{{ $produto['futuro'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if(!empty($itens['totais']))
                <tr>
                    <td></td>
                    <td>Total:</td>
                    <td class="tb_number">{{ $itens['totais']['pronta_entrega'] }}</td>
                    @foreach($itens['totais']['quinzenas'] as $key => $header_mes)
                    <td class="tb_number">{{ $header_mes }}</td>
                    @endforeach
                    <td class="tb_number">{{ $itens['totais']['futuro'] }}</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>
@endsection
@section('script-footer')
table_filters = [];
table_produtos = [];

table_produtos = $('#table-produtos').DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "scrollX": false,
    "scrollY": "70vh",
    "scrollCollapse": true,
    "white-space": "nowrap",
    "paging": false,
    "autoWidth": true,
    "language": {
        "decimal":        ".",
        "emptyTable":     "Nenhum registro encontrado",
        "infoPostFix":    "",
        "thousands":      ",",
        "loadingRecords": "Carregando...",
        "processing":     "Processando...",
        "zeroRecords":    "Nenhum registro encontrado",
        "paginate": {
            "first":      "<<",
            "last":       ">>",
            "next":       ">",
            "previous":   "<"
        }
    },
    "columnDefs": [
        { 
            "class": "tb_date", 
            targets: "sort-date" 
        },
        { 
            targets: "foto",
            width: "100px", 
            orderable: false
        }
    ],
    "order": [[ 1, 'asc' ]]
});

$(document).ready( function () {
    
    $(document).find('a.thumb-instrucao').fancybox();
    $(document).find("a.thumb").fancybox(
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
    $('[data-toggle="popover"]').off('show.bs.popover');
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="popover"]').popover({
        container: 'body',
        html: true,
        show: true,
        template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
    });
});

function selectEstado(origem){
    $("#estado").find('option').remove();
    if (origem.length > 0){
        $.ajax({
            url: "{{ route('listagem_precos.aliquotas') }}",
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}", origem: origem},
            method: 'POST',
            success: function(data){
                $("#estado").append("<option value='' selected>Destino</option>");
                for (var i in data){
                    $("#estado").append("<option value='"+data[i].value+"' data-regiao='"+ data[i].regiao +"'>" + data[i].html + "</option>");
                }
                @if(isset($campos_salvos['estado']))
                $("#estado").val("{{ $campos_salvos['estado'] }}");
                @else
                $("#estado").val('');
                @endif
            },
            error: function(data){
                $("#aliquota").append('<option>Erro</option>').prop('disabled selected');
            }
        });
    }
    else{
        $("#aliquota").append("<option value=''>Selecione origem</option>");            
    }
}

@endsection
