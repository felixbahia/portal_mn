@extends('layouts.page-dialog')

@section('content')

<div class='tab-content mb-3'>
    <b>Marca: </b> {{ $produto['marca'] }} &nbsp;&nbsp; <b>Linha: </b> {{ $produto['linha'] }} &nbsp;&nbsp; <b>Grupo: </b> {{ $produto['grupo'] }} &nbsp;&nbsp; <b>Subgrupo: </b> {{ $produto['subgrupo'] }}&nbsp;&nbsp; <b>Data Criação: </b> {{ $produto['ficha_tecnica_criacao'] }}<br><br>
    <b>Código: </b> {{ $produto['codigo_produto'] }} &nbsp;&nbsp; <b>Produto: </b> {{ $produto['descricao'] }} &nbsp;&nbsp; <b>Unidade: </b> {{ $produto['unidade'] }}&nbsp;&nbsp; <b>Data Última Atualização: </b> {{ $produto['ficha_tecnica_update'] }}
</div>
<div class='tab-content'>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id="ficha-tecnica-consulmo-tab" data-toggle="tab" href="#ficha-tecnica-composicao" role="tab" aria-controls="ficha-tecnica-composicao" aria-selected="false">Composição</a>
        </li>
        @if(!empty($info_adicional))
        <li class="nav-item">
            <a class="nav-link" id='ficha-tecnica-lavagem-tab' data-toggle="tab" href="#ficha-tecnica-lavagem" role="tab" aria-controls="ficha-tecnica-lavagem" aria-selected="false">Referêcia de Peça e Lavagem</a>
        </li>
        @endif
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-montagem-tab" data-toggle="tab" href="#ficha-tecnica-montagem" role="tab" aria-controls="ficha-tecnica-montagem" aria-selected="false">Montagem</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-etiqueta-tab" data-toggle="tab" href="#ficha-tecnica-etiqueta" role="tab" aria-controls="ficha-tecnica-etiqueta" aria-selected="false">Etiqueta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-medidas-tab" data-toggle="tab" href="#ficha-tecnica-medidas" role="tab" aria-controls="ficha-tecnica-medidas" aria-selected="false">Medidas</a>
        </li>
        <li class="nav-item">
                <a class="nav-link" id="ficha-tecnica-sequencia-tab" data-toggle="tab" href="#ficha-tecnica-sequencia" role="tab" aria-controls="ficha-tecnica-sequencia" aria-selected="false">Sequência Operacional</a>
            </li>
    </ul>

    <div class="tab-content pt-3" id="LancamentoProjetoHeaderContainer">
        <div class="tab-pane show active" id="ficha-tecnica-composicao" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <div class="content-table">
                    <h6>Composição</h6>
                    <table class="table table-striped table-filters-composicao" id="table-filters-composicao">
                        <thead>
                            <tr>
                                <th class='td_modal_grupo'>Grupo</th>
                                <th class='td_modal_linha'>Linha</th>
                                <th class='td_modal_codigo'>Código</th>
                                <th class='td_modal_descricao'>Descrição</th>
                                <th class="tb_number">Consumo por unidade padrão</th>
                                <th class="tb_number">Custo Contábil Unitário</th>
                                <th class="tb_number">Custo Contábil Total</th>
                                <th class="tb_number">Custo Gerencial Unitário</th>
                                <th class="tb_number">Custo Gerencial Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($tecidos as $tecido)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['grupo'] }}">{{ $tecido['grupo'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['linha'] }}">{{ $tecido['linha'] }}</div></div></td>
                            <td class="td_codigo_produto">{{ $tecido['codigo'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['descricao'] }}">{{ $tecido['descricao'] }}</div></div></td>
                            <td class="tb_number td_preco td_consumo">{{ $tecido['consumo'] }}</td>
                            @if($exibicao_custo_fixo)
                                <td class="tb_number td_preco td_custo_unitario">{{ $tecido['custo'] }}</td>
                            @else
                                <td class="tb_number td_preco td_custo_unitario"><input type='text' class='text-right custo-editavel' size="5" data-id='{{ $tecido['id'] }}' value='{{ $tecido['custo'] }}'></td>
                            @endif
                            <td class="tb_number td_preco td_custo_total">{{ $tecido['custo_total'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_unitario">{{ $tecido['custo_gerencial'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_total">{{ $tecido['custo_gerencial_total'] }}</td>
                        </tr>
                        @endforeach
                        @foreach($insumos as $insumo)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['grupo'] }}">{{ $insumo['grupo'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['linha'] }}">{{ $insumo['linha'] }}</div></div></td>

                            <td>{{ $insumo['codigo'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['descricao'] }}">{{ $insumo['descricao'] }}</div></div></td>
                            <td class="tb_number td_preco td_consumo">{{ $insumo['consumo'] }}</td>
                            @if($exibicao_custo_fixo)
                                <td class="tb_number td_preco td_custo_unitario">{{ $insumo['custo'] }}</td>
                            @else
                                <td class="tb_number td_preco td_custo_unitario"><input type='text' class='text-right custo-editavel' size="5" data-id='{{ $insumo['id'] }}' value='{{ $insumo['custo'] }}'></td>
                            @endif
                            <td class="tb_number td_preco td_custo_total">{{ $insumo['custo_total'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_unitario">{{ $insumo['custo_gerencial'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_total">{{ $insumo['custo_gerencial_total'] }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <td class='td_modal_grupo'></td>
                            <td class='td_modal_linha'></td>
                            <td class='td_modal_codigo'></td>
                            <td class='td_modal_descricao'></td>
                            <td class="tb_number"></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number">{{ $custo_composicao_total }}</td>
                            <td class="tb_number"></td>
                            <td class="tb_number">{{ $custo_gerencial_total }}</td>
                        </tfoot>
                    </table>

                </div>
            </div>

            <div class="content-dialog-table">
                <div class="content-table">
                    <h6>Mão de obra</h6>
                    <table class="table table-striped table-filters-projeto-servico" id="table-filters-projeto-servico">
                        <thead>
                            <tr>
                                <th class='td_modal_grupo'>Grupo</th>
                                <th class='td_modal_linha'>Linha</th>
                                <th class='td_modal_codigo'>Código</th>
                                <th class='td_modal_descricao'>Descrição</th>
                                <th class="tb_number">Consumo por unidade padrão</th>
                                <th class="tb_number">Custo Gerencial Unitário</th>
                                <th class="tb_number">Custo Gerencial Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servicos as $servico)
                                <tr>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $servico['grupo'] }}">{{ $servico['grupo'] }}</div></div></td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $servico['linha'] }}">{{ $servico['linha'] }}</div></div></td>
                                    <td>{{ $servico['codigo'] }}</td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $servico['descricao'] }}">{{ $servico['descricao'] }}</div></div></td>
                                    <td class="tb_number td_preco td_consumo">{{ $servico['consumo'] }}</td>
                                    @if($exibicao_custo_fixo)
                                        <td class="tb_number td_preco td_custo_unitario">{{ $servico['custo_gerencial'] }}</td>
                                    @else 
                                    <td class="tb_number td_preco td_custo_unitario"><input type='text' class='text-right custo-editavel' size="5" data-id='{{ $servico['id'] }}' value='{{ $servico['custo_gerencial'] }}'></td>
                                    @endif
                                    <td class="tb_number td_preco td_custo_total">{{ $servico['custo_gerencial_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td class='td_modal_grupo'></td>
                            <td class='td_modal_linha'></td>
                            <td class='td_modal_codigo'></td>
                            <td class='td_modal_descricao'></td>
                            <td class="tb_number"></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number">{{ $custo_gerencial_servicos_total }}</td>
                        </tfoot>
                    </table>
                    
                    <hr>

                    <h5 class='float-right'><span id='total_custo_produto'>{!! $produto['custo'] !!}</span></h5>
                </div>
            </div>
        </div>

        @if(!empty($info_adicional))
        <div class="tab-pane" id="ficha-tecnica-lavagem" role="tabpanel" aria-labelledby="dados-tab">
            @if(isset($info_adicional['imagem_produto']))
                {!! $info_adicional['imagem_produto'] !!}
            @endif
            @if(isset($info_adicional['lavagem']))
            <b>Lavagem:</b>
            <p>{{ $info_adicional['lavagem'] }}</p>
            @endif
            @if(isset($info_adicional['encolhimento']))
            <b>Encolhimento:</b>
            <p>{{ $info_adicional['encolhimento'] }}</p>
            @endif

        </div>
        @endif

        <div class="tab-pane" id="ficha-tecnica-montagem" role="tabpanel" aria-labelledby="dados-tab">
            <div class="row">
                @if(empty($montagem))
                <div class="col-sm-12 text-center m-3 p-1 ficha-cadastral-sem-img">
                    NENHUM REGISTRO ENCONTRADO
                </div>
                @else
                @foreach($montagem as $linha)
                <div class="col-sm-3 text-center">
                    {!! $linha['imagem'] !!}
                </div>
                @endforeach
                @endif
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-etiqueta" role="tabpanel" aria-labelledby="dados-tab">
            <div class="row">
                @if(empty($etiquetas))
                <div class="col-sm-12 text-center m-3 p-1 ficha-cadastral-sem-img">
                    NENHUM REGISTRO ENCONTRADO
                </div>
                @else
                @foreach($etiquetas as $linha)
                <div class="col-sm-3 text-center">
                    {!! $linha['imagem'] !!}
                </div>
                @endforeach
                @endif
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-medidas" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped table-filters-ficha-tecnica-medidas" id="table-filters-ficha-tecnica-medidas">
                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Descrição</th>
                                <th class="tb_number">P</th>
                                <th class="tb_number">M</th>
                                <th class="tb_number">G</th>
                                <th class="tb_number">GG</th>
                                <th class="tb_number">XG</th>
                                <th class="tb_number">XGG</th>
                                <th class="tb_number">Tolerância</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach($medidas as $medida)
                                <tr>
                                    <td>{{ $medida['ordem'] }}</td>
                                    <td>{{ $medida['medida_descricao'] }}</td>
                                    <td>{{ $medida['medida_p'] }}</td>
                                    <td>{{ $medida['medida_m'] }}</td>
                                    <td>{{ $medida['medida_g'] }}</td>
                                    <td>{{ $medida['medida_gg'] }}</td>
                                    <td>{{ $medida['medida_xg'] }}</td>
                                    <td>{{ $medida['medida_xgg'] }}</td>
                                    <td>{{ $medida['tolerancia'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-sequencia" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped table-filters-ficha-tecnica-operacao" id="table-filters-ficha-tecnica-operacao">
                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Operação</th>
                                <th>Tipo de Ponto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sequencia_operacional as $operacao)
                                <tr>
                                    <td>{{ $operacao['ordem'] }}</td>
                                    <td>{{ $operacao['operacao'] }}</td>
                                    <td>{{ $operacao['tipo_ponto'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>
<script>
    $(document).ready( function () {

        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $(document).find('#modal_ficha_tecnica_exibir').on('shown.bs.modal', function(){
            table_filters_composicao.columns.adjust().draw();
            table_filters_servicos.columns.adjust().draw();
        });

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover',
            placement: 'right',
            template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

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
                            $(this).children('#fancybox-img').css({'cursor': 'pointer'});
                        })
                        .on('click', function(){
                            window.open($(this).children('#fancybox-img').prop('src'));
                        });
                }
            }
        );

        $(document).find('.custo-editavel').maskMoney({thousands:'', decimal:','});

        $(document).find('.custo-editavel').on('change', function(){
            recalculaPreco(this);
        });
    });

    table_filters_composicao = $(document).find('#table-filters-composicao').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollCollapse": true,
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
            { "width": "25%", "targets": "td_modal_descricao"},
            { "width": "25%", "targets": "td_modal_grupo"},
            { "width": "5%", "targets": "tb_number" }
        ]
    });

    table_filters_servicos = $(document).find('#table-filters-projeto-servico').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollCollapse": true,
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
            { "width": "25%", "targets": "td_modal_descricao"},
            { "width": "25%", "targets": "td_modal_grupo"},
            { "width": "5%", "targets": "tb_number" }
        ]
    });

    table_filters_medidas = $(document).find('#table-filters-ficha-tecnica-medidas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollCollapse": true,
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
            { "width": "5%", "targets": "tb_number" }
        ]
    });

    table_filters_operacao = $(document).find('#table-filters-ficha-tecnica-operacao').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollCollapse": true,
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
            { "width": "5%", "targets": "tb_number" }
        ]
    });

    function recalculaPreco(elemento){
        var custo = Number($(elemento).val().replace(',', '.'));
        var consumo = Number($(elemento).parent().parent().find('.td_consumo').html().replace('.', '').replace(',', '.'));

        var custo_total_consumo = (custo * consumo).toFixed(2).replace('.', ',');

        $(elemento).parent().parent().find('.td_custo_total').html(custo_total_consumo);

        var total_composicao = 0;
        var total_mao_de_obra = 0;

        $(document).find('#table-filters-composicao').find('.td_custo_total').each(function(index){
            total_composicao += Number($(this).html().replace('.', '').replace(',', '.'));
        });

        $(document).find('#table-filters-projeto-servico').find('.td_custo_total').each(function(index){
            total_mao_de_obra += Number($(this).html().replace('.', '').replace(',', '.'));
        });
        
        var total = total_composicao + total_mao_de_obra;
        
        total_composicao = total_composicao.toFixed(2).replace('.', ',');
        total_mao_de_obra = total_mao_de_obra.toFixed(2).replace('.', ','); 
        total = total.toFixed(2).replace('.', ',');

        $(document).find('#custo-composicao-total').html('Total: ' +total_composicao);
        $(document).find('#custo-servico-total').html('Total: ' + total_mao_de_obra);
        $(document).find('#total_custo_produto').html('Custo total do produto: ' + total);

    }
</script>
@endsection
