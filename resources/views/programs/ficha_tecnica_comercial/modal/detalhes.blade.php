@extends('layouts.page-dialog')
@section('content')
<div id="app_comercial">
    @if(!empty($estabelecimentos))  
        <div id="detalhes_ficha_comercial" name="detalhes_ficha_comercial" @if($estilo_layout) class="ficha_comercial_detalhes_mobile" @else class="ficha_comercial_detalhes" style="
        border-right-color: #d4d4d4 !important;
        border-right: 1px;
        border-right-style: solid;" @endif>
    @else
        <div id="detalhes_ficha_comercial" name="detalhes_ficha_comercial" style="
        border-right-color: #d4d4d4 !important;
        border-right: 1px;
        border-right-style: solid;">
    @endif
        <h4><b>Grupo:</b> {{ $produto['grupo']}}{!!$botao_pdf!!}</h4></br>
        @if($estilo_layout)
            <label><h6>{{ $produto['produto']}}</h6></label>&nbsp;&nbsp;&nbsp;{!!$carrinho!!}</br>
            <p><label><b>Característica:</b> {{ $produto['caracteristica']}}</label></br></p>
            <p><label style="width: 40%;"><b>Composição:</b> {{ $produto['composicao_nasajon']}}</label><label><b>Encolhimento %:</b> {{ $produto['encolhimento']}}</label><br></p>
            <p><label style="width: 40%;"><b>Peças de:</b> {{ $produto['pecas_de']}}</label><label><b>Origem:</b> {{ $produto['origem']}}</label><br></p>
            <p><label style="width: 40%;"><b>NCM:</b> {{ $produto['ncm']}}</label><label><b>Gramatura G/M² (+/- 5%):</b> {{ $produto['gramatura']}}</label></br></p>
            <p><label style="width: 40%;"><b>Gramatura Linear G/M:</b> {{ $produto['gramatura_linear']}}</label><label><b>Larg. (+/- 2CM):</b> {{ $produto['largura']}}</label></br></p>
            <p><label style="width: 40%;"><b>Rendimento MT/KG:</b> {{ $produto['rendimento']}}</label><label><b>EAN:</b> {{ $produto['ean']}}</label></br></p>
            @if(!empty($produto['titulo_trama']) || !empty($produto['titulo_urdume'])) <p><label style="width: 40%;"><b>Título Trama:</b> {{ $produto['titulo_trama']}}</label><label><b>Título Urdume:</b> {{ $produto['titulo_urdume']}}</label></br></p> @endif
            <p><label style="width: 40%;"><b>Unidade:</b> {{ $produto['unidade']}}</label><label><b>Peso Bruto:</b> {{ $produto['peso_bruto']}}</label></br></p>
            <p><label style="width: 30%;"><b>Instrução de Lavagem:</b></label><label><img src="{{ $produto['img_instrucoes_lavagem'] }}" class="img_instrucoes_lavagem" style="width: 150px;height: 26px;"></label></br></p>
        @else
            <label><h5>{{ $produto['produto']}}</h5></label>&nbsp;&nbsp;&nbsp;{!!$carrinho!!}</br>
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
                        <td><b>Larg.(+/-2CM):</b> {{ $produto['largura']}}</td>
                    </tr>
                    <tr>
                        <td><b>Rendimento MT/KG:</b> {{ $produto['rendimento']}}</td>
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
        @endif
        
        <div id="desenho-exibicao-div-comercial" >
            <a href="{{ $produto['imagem'] }}" id="desenho-link" target="_blank">
                <div id="desenho-container-div"  @if($estilo_layout) style="margin: 5px !important;" @endif>
                    <img src="{{ $produto['imagem'] }}" id="desenho-exibicao">
                </div>
            </a>
        </div>
        <div class="row">
                <strong>
                    “Nossos ensaios apontam para o encolhimento informado nessa ficha técnica; contudo, devido a possíveis variações no processo de produção do tecido, pode ocorrer uma oscilação de 2% para mais ou para menos nesse parâmetro.
Sugerimos testes prévios na peça confeccionada para uma maior segurança e padronização do produto final.
Não nos responsabilizamos pelo uso dos nossos produtos fora das especificações que constam nesse manual.”
                </strong>
        </div>
    </div>
    @if(!empty($estabelecimentos)) 
        <div id="filtro_ficha_comercial" name="filtro_ficha_comercial" @if($estilo_layout) class="ficha_comercial_filtro_mobile" @else class="ficha_comercial_filtro" style="
            float: right !important;" @endif>
            <div id="dados_carrinhos" name="dados_carrinhos">
                <h4>Dados de Venda <a href='#' data-cliente='' data-book_id='' data-filtro='' class='bt-carrinho-book' style="color: #007fff;"></a>&nbsp;<span id='contador' style='font-size: 16px;color: black;'>0</span>@if(File::exists($logo_tipo))<img src="{{ $logo_tipo }}" width="80" style="float: right !important;">@endif</h4>
                <div id="dados_cliente" name="dados_cliente"></div>
                <div id="dados_pedido_valor" name="dados_pedido_valor"></div>
                <hr>
                <h4>Estoque/Preço</h4></br>
            </div>
            <div id="sem_dados_carrinhos" name="sem_dados_carrinhos">
                <h4>Estoque/Preço <img src="{{ $logo_tipo }}" width="80" style="float: right !important;"></h4></br>
            </div>
            <form action="#" name="form_filter_detalhes_ficha_comercial" id="form_filter_detalhes_ficha_comercial" onsubmit="return false;" style="padding: 0 30px;">
                @csrf
                
                {!! Form::hidden('estabelecimentos', $estabelecimentos, ['id' => 'estabelecimentos']) !!}
                {!! Form::hidden('estoque_produto_entrega', $estoque_produto_entrega, ['id' => 'estoque_produto_entrega']) !!}
                {!! Form::hidden('codigo', $codigo, ['id' => 'codigo']) !!}
                {!! Form::hidden('estabelecimento_descricao', $estabelecimento_descricao, ['id' => 'estabelecimento_descricao']) !!}
                
                <div class="content-fields">
                    <div class="form-row mt-3">
                        <div class="form-group col-sm-3">
                            <select name="estado" id="estado" class="form-control">
                                <option disabled selected>Selecione um estabelecimento</option>
                            </select>
                        </div>

                        @if (Auth::user()->tipo_usuario_id == 16)
                            <input type="hidden" name="coluna" id="coluna" value="coluna_a">
                        @else
                        <div class="form-group col-sm-3">
                            <select name="coluna" id="coluna"  class="form-control">
                                <option value="coluna_a" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'coluna_a') selected @endif @elseif($campos_salvos['coluna'] == 'coluna_a') selected @endif>Comissão A</option>
                                <option value="coluna_b" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'coluna_b') selected @endif @elseif($campos_salvos['coluna'] == 'coluna_b') selected @endif>Comissão B</option>
                                <option value="coluna_c" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'coluna_c') selected @endif @elseif($campos_salvos['coluna'] == 'coluna_c') selected @endif>Comissão C</option>
                                <option value="prazo_vista" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_vista') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_vista') selected @endif>À vista</option>
                                <option value="prazo_15" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_15') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_15') selected @endif>Prazo 15 dias</option>
                                <option value="prazo_30" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_30') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_30') selected @endif>Prazo 30 dias</option>
                                <option value="prazo_45" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_45') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_45') selected @endif>Prazo 45 dias</option>
                                <option value="prazo_60" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_60') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_60') selected @endif>Prazo 60 dias</option>
                                <option value="prazo_75" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_75') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_75') selected @endif>Prazo 75 dias</option>
                                <option value="prazo_90" @if(!empty($dados_carrinhos['coluna'])) @if($dados_carrinhos['coluna'] == 'prazo_90') selected @endif @elseif($campos_salvos['coluna'] == 'prazo_90') selected @endif>Prazo 90 dias</option>
                            </select>
                        </div>
                        @endif

                        <div class="form-group col-sm-3">
                            <select name="frete" id="frete"  class="form-control">
                                <option value="fob" @if(!empty($dados_carrinhos['frete'])) @if($dados_carrinhos['frete'] == 'fob') selected @endif @elseif($campos_salvos['frete'] == 'fob') selected @endif>FOB</option>
                                <option value="cif" @if(!empty($dados_carrinhos['frete'])) @if($dados_carrinhos['frete'] == 'cif') selected @endif @elseif($campos_salvos['frete'] == 'cif') selected @endif>CIF</option>
                            </select>
                        </div>
                        <div class="form-group col-sm-3">
                            <select name="tipo_cliente" id="tipo_cliente"  class="form-control">
                                <option value="normal" @if(!empty($dados_carrinhos['tipo_cliente'])) @if($dados_carrinhos['tipo_cliente'] == 'normal') selected @endif @elseif($campos_salvos['tipo_cliente'] == 'normal') selected @endif>Contribuinte</option>
                                <option value="isento" @if(!empty($dados_carrinhos['tipo_cliente'])) @if($dados_carrinhos['tipo_cliente'] == 'isento') selected @endif @elseif($campos_salvos['tipo_cliente'] == 'isento') selected @endif>Isento</option>
                            </select>
                        </div>
                    </div>
                </div>

            </form>
            @if(!empty($precos))
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped"  id="table-filters-precos_teste">
                            <thead>
                                <tr>
                                    <td colspan="12" class="text_date">Preço Pronta Entrega</td>
                                </tr>
                                <tr>
                                    <th>Estabelecimento</th>
                                    <th class="tb_number">Estoque</th>
                                    @if (Auth::user()->tipo_usuario_id != 16)
                                        <th class='comissoes tb_number' id="coluna_a">A</th>
                                        <th class='comissoes tb_number' id="coluna_b">B</th>
                                        <th class='comissoes tb_number' id="coluna_c">C</th>
                                    @endif
                                    <th class="prazo tb_number">Vista</th>
                                    <th class="prazo tb_number">15</th>
                                    <th class="prazo tb_number">30</th>
                                    <th class="prazo tb_number">45</th>
                                    <th class="prazo tb_number">60</th>
                                    <th class="prazo tb_number">75</th>
                                    <th class="prazo tb_number">90</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($precos as $index => $value)
                                    <tr>
                                        <td>{{$index}}</td>
                                        <td class="tb_number">{{$value['estoque']}}</td>
                                        @if (Auth::user()->tipo_usuario_id != 16)
                                            <td class="tb_number">{{$value['coluna_a']}}</td>
                                            <td class="tb_number">{{$value['coluna_b']}}</td>
                                            <td class="tb_number">{{$value['coluna_c']}}</td>
                                        @endif
                                        <td class="tb_number">{{$value['prazo_vista']}}</td>
                                        <td class="tb_number">{{$value['prazo_15']}}</td>
                                        <td class="tb_number">{{$value['prazo_30']}}</td>
                                        <td class="tb_number">{{$value['prazo_45']}}</td>
                                        <td class="tb_number">{{$value['prazo_60']}}</td>
                                        <td class="tb_number">{{$value['prazo_75']}}</td>
                                        <td class="tb_number">{{$value['prazo_90']}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            @if(!empty($precos_programado))
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped"  id="table-filters-precos_programado">
                            <thead>
                                <tr>
                                    <td colspan="12" class="text_date">Preço Programado</td>
                                </tr>
                                <tr>
                                    <th>Estabelecimento</th>
                                    <th class="tb_number">Estoque</th>
                                    @if (Auth::user()->tipo_usuario_id != 16)
                                        <th class='comissoes tb_number' id="coluna_a">A</th>
                                        <th class='comissoes tb_number' id="coluna_b">B</th>
                                        <th class='comissoes tb_number' id="coluna_c">C</th>
                                    @endif
                                    <th class="prazo tb_number">Vista</th>
                                    <th class="prazo tb_number">15</th>
                                    <th class="prazo tb_number">30</th>
                                    <th class="prazo tb_number">45</th>
                                    <th class="prazo tb_number">60</th>
                                    <th class="prazo tb_number">75</th>
                                    <th class="prazo tb_number">90</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($precos_programado as $index => $value)
                                    <tr>
                                        <td>{{$index}}</td>
                                        <td class="tb_number">{{$value['estoque']}}</td>
                                        @if (Auth::user()->tipo_usuario_id != 16)
                                            <td class="tb_number">{{$value['coluna_a']}}</td>
                                            <td class="tb_number">{{$value['coluna_b']}}</td>
                                            <td class="tb_number">{{$value['coluna_c']}}</td>
                                        @endif
                                        <td class="tb_number">{{$value['prazo_vista']}}</td>
                                        <td class="tb_number">{{$value['prazo_15']}}</td>
                                        <td class="tb_number">{{$value['prazo_30']}}</td>
                                        <td class="tb_number">{{$value['prazo_45']}}</td>
                                        <td class="tb_number">{{$value['prazo_60']}}</td>
                                        <td class="tb_number">{{$value['prazo_75']}}</td>
                                        <td class="tb_number">{{$value['prazo_90']}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
<script>
    $(document).ready( function () {
        verificacao_carrinho = false;
        
        init();

        $("#estado").off("change");
        $("#estado").on("change", function(){
            @if(!empty($precos))
                filterAjaxDetalhes($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            @if(!empty($precos_programado))
                filterAjaxProgramado($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            salvarCampos($("#form_filter_detalhes_ficha_comercial").serialize());
        });

        $("#coluna").off("change");
        $("#coluna").on("change", function(){
            @if(!empty($precos))
                filterAjaxDetalhes($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            @if(!empty($precos_programado))
                filterAjaxProgramado($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            salvarCampos($("#form_filter_detalhes_ficha_comercial").serialize());
        });

        $("#frete").off("change");
        $("#frete").on("change", function(){
            @if(!empty($precos))
                filterAjaxDetalhes($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            @if(!empty($precos_programado))
                filterAjaxProgramado($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            salvarCampos($("#form_filter_detalhes_ficha_comercial").serialize());
        });

        $("#tipo_cliente").off("change");
        $("#tipo_cliente").on("change", function(){
            @if(!empty($precos))
                filterAjaxDetalhes($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            @if(!empty($precos_programado))
                filterAjaxProgramado($("#form_filter_detalhes_ficha_comercial").serialize());
            @endif
            salvarCampos($("#form_filter_detalhes_ficha_comercial").serialize());
        });

        $(document).find(".bt-carrinho-book").off("click");
        $(document).find(".bt-carrinho-book").on("click", function(event){
            event.stopPropagation();
            modalVisualizarCarrinho($(this));
        });

        $(document).find(".bt-carrinho-comprar").off("click");
        $(document).find(".bt-carrinho-comprar").on("click", function(event){
            verificacao_carrinho = true;
        });

        @if($dados_carrinhos['carrinho'])
            $(document).find('#dados_carrinhos').show();
            $(document).find('#dados_carrinhos_indice').show();
            $(document).find('#sem_dados_carrinhos').hide();
            $(document).find('#dados_cliente').html("<label><b>Cliente: </b>"+"{{$dados_carrinhos['cliente']}}"+"</label>");
            $(document).find('#dados_pedido_valor').html("<label><b>Valor: </b>"+"{{$dados_carrinhos['valor']}}"+"</label>");
            $(document).find('#contador').html("{{$dados_carrinhos['contador']}}");
            $(document).find('#contador_indice').html("{{$dados_carrinhos['contador']}}");
            $("#estado").val("{{$dados_carrinhos['estado']}}");
            $("#estado").attr("disabled", "disabled");
            @if (Auth::user()->tipo_usuario_id != 16)
                $("#coluna").val("{{$dados_carrinhos['coluna']}}");
                $("#coluna").attr("disabled", "disabled");
            @endif
            $("#frete").val("{{$dados_carrinhos['frete']}}");
            $("#frete").attr("disabled", "disabled");
            $("#tipo_cliente").val("{{$dados_carrinhos['tipo_cliente']}}");
            $("#tipo_cliente").attr("disabled", "disabled");
        @else
            $(document).find('#dados_carrinhos').hide();
            $(document).find('#dados_carrinhos_indice').hide();
            $(document).find('#sem_dados_carrinhos').show();
        @endif
        
    });

    function init(){
        initTable();

        selectEstado('SP');    

        setInterval(function(){
            if(verificacao_carrinho){
                verificacaoCarrinho();
            }
        }, 10000);   
    }

    function initTable(){
        table_filters_detalhes_comercio = $('#table-filters-precos_teste').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "paging": false,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                {
                    @if (Auth::user()->tipo_usuario_id != 16)
                    "targets": ['comissoes', 'prazo'],
                    @else
                    "targets": 'prazo',
                    @endif
                    "className": 'number_format',
                    "width": "10% !important",
                    @if (Auth::user()->tipo_usuario_id != 16)
                    "visible": false
                    @endif
                },
            ],
        });

        table_filters_detalhes_comercio_programado = $('#table-filters-precos_programado').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "paging": false,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                {
                    @if (Auth::user()->tipo_usuario_id != 16)
                    "targets": ['comissoes', 'prazo'],
                    @else
                    "targets": 'prazo',
                    @endif
                    "className": 'number_format',
                    "width": "10% !important",
                    @if (Auth::user()->tipo_usuario_id != 16)
                    "visible": false
                    @endif
                },
            ],
        });

        var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
        var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60", 'prazo_75', 'prazo_90');

        @if (Auth::user()->tipo_usuario_id != 16)
            table_filters_detalhes_comercio.columns('.comissoes').visible(false);
            table_filters_detalhes_comercio.columns('.prazo').visible(false);

            table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
            table_filters_detalhes_comercio_programado.columns('.prazo').visible(false);

            if(coluna.indexOf($("#coluna").val()) != -1){

                table_filters_detalhes_comercio.columns('.comissoes').visible(false);
                table_filters_detalhes_comercio.columns('.prazo').visible(true);

                table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                table_filters_detalhes_comercio_programado.columns('.prazo').visible(true);

            }
            else if (prazo.indexOf($("#coluna").val()) != -1){

                table_filters_detalhes_comercio.columns('.prazo').visible(false);
                table_filters_detalhes_comercio.columns('.comissoes').visible(true);

                table_filters_detalhes_comercio_programado.columns('.prazo').visible(false);
                table_filters_detalhes_comercio_programado.columns('.comissoes').visible(true);

            }
        @else
            table_filters_detalhes_comercio.columns('.comissoes').visible(false);
            table_filters_detalhes_comercio.columns('.prazo').visible(true);

            table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
            table_filters_detalhes_comercio_programado.columns('.prazo').visible(true);
        @endif
    }

    function selectEstado(origem){
        $("#estado").find('option').remove();
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
                    $("#estado").val("{{$campos_salvos['estado']}}");
                @else
                    $("#estado").val('');
                @endif

                @if(!empty($dados_carrinhos['estado']))
                    $("#estado").val("{{$dados_carrinhos['estado']}}");
                @endif
            },
            error: function(data){
                $("#aliquota").append('<option>Erro</option>').prop('disabled selected');
            }
        });
    }

    function filterAjaxDetalhes(data_form){
        var $return;	    
        
        $(".buttons-excel").hide();
        $('[data-toggle="tooltip"]').tooltip('hide');
	    var form_comercial_detalhes = $("#form_filter_detalhes_ficha_comercial");

        estado = $("#estado").val();
        coluna = $("#coluna").val();
        frete = $("#frete").val();
        tipo_cliente = $("#tipo_cliente").val();
        estabelecimentos = $("#estabelecimentos").val();
        estoque_produto_entrega = $("#estoque_produto_entrega").val();
        codigo = form_comercial_detalhes.find("#codigo").val();
        estabelecimento_descricao = $("#estabelecimento_descricao").val();

        form_comercial_detalhes.find('.error-message').remove();
        form_comercial_detalhes.find('input, select').removeClass('error-input');

        $.ajax({
            url: "{{ route('ficha_tecnica_comercial.buscar_filtro_detalhes') }}",
            dataType: 'json',
            data: {
                _token: '{{csrf_token()}}',
                estado: estado, 
                coluna: coluna, 
                frete: frete, 
                tipo_cliente: tipo_cliente, 
                estabelecimentos: estabelecimentos,
                estoque_produto_entrega: estoque_produto_entrega,
                codigo: codigo,
                estabelecimento_descricao: estabelecimento_descricao,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                table_filters_detalhes_comercio.clear().draw();

                @if(!empty($precos))
                    precos = [];
                
                    for (var fields in data.response.precos){
                        temp_array = [
                            fields,
                            data.response.precos[fields].estoque,
                            data.response.precos[fields].coluna_a,
                            data.response.precos[fields].coluna_b,
                            data.response.precos[fields].coluna_c,
                            data.response.precos[fields].prazo_vista,
                            data.response.precos[fields].prazo_15,
                            data.response.precos[fields].prazo_30,
                            data.response.precos[fields].prazo_45,
                            data.response.precos[fields].prazo_60,
                            data.response.precos[fields].prazo_75,
                            data.response.precos[fields].prazo_90,
                        ];
                        precos.push(temp_array)
                    }
                    table_filters_detalhes_comercio.rows.add(precos).draw();
                @endif

                var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
                var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60", 'prazo_75', 'prazo_90');
                @if (Auth::user()->tipo_usuario_id != 16)
                    table_filters_detalhes_comercio.columns('.comissoes').visible(false);
                    table_filters_detalhes_comercio.columns('.prazo').visible(false);

                    table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                    table_filters_detalhes_comercio_programado.columns('.prazo').visible(false);

                    if(coluna.indexOf($("#coluna").val()) != -1){

                        table_filters_detalhes_comercio.columns('.comissoes').visible(false);
                        table_filters_detalhes_comercio.columns('.prazo').visible(true);

                        table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                        table_filters_detalhes_comercio_programado.columns('.prazo').visible(true);

                    }
                    else if (prazo.indexOf($("#coluna").val()) != -1){

                        table_filters_detalhes_comercio.columns('.prazo').visible(false);
                        table_filters_detalhes_comercio.columns('.comissoes').visible(true);

                        table_filters_detalhes_comercio_programado.columns('.prazo').visible(false);
                        table_filters_detalhes_comercio_programado.columns('.comissoes').visible(true);

                    }
                @else
                    table_filters_detalhes_comercio.columns('.comissoes').visible(false);
                    table_filters_detalhes_comercio.columns('.prazo').visible(true);

                    table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                    table_filters_detalhes_comercio_programado.columns('.prazo').visible(true);
                @endif
            },
            error: function(data){
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        });
    }

    function filterAjaxProgramado(data_form){
        var $return;	    
        
        $(".buttons-excel").hide();
        $('[data-toggle="tooltip"]').tooltip('hide');
	    var form_comercial_detalhes = $("#form_filter_detalhes_ficha_comercial");

        estado = $("#estado").val();
        coluna = $("#coluna").val();
        frete = $("#frete").val();
        tipo_cliente = $("#tipo_cliente").val();
        estabelecimentos = $("#estabelecimentos").val();
        estoque_produto_entrega = $("#estoque_produto_entrega").val();
        codigo = form_comercial_detalhes.find("#codigo").val();
        estabelecimento_descricao = $("#estabelecimento_descricao").val();

        form_comercial_detalhes.find('.error-message').remove();
        form_comercial_detalhes.find('input, select').removeClass('error-input');

        $.ajax({
            url: "{{ route('ficha_tecnica_comercial.buscar_filtro_detalhes_progamado') }}",
            dataType: 'json',
            data: {
                _token: '{{csrf_token()}}',
                estado: estado, 
                coluna: coluna, 
                frete: frete, 
                tipo_cliente: tipo_cliente, 
                estabelecimentos: estabelecimentos,
                estoque_produto_entrega: estoque_produto_entrega,
                codigo: codigo,
                estabelecimento_descricao: estabelecimento_descricao,
            },
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){

                table_filters_detalhes_comercio_programado.clear().draw();
                
                @if(!empty($precos_programado))
                    precos = [];
                
                    for (var fields in data.response.precos_programado){
                        temp_array = [
                            fields,
                            data.response.precos_programado[fields].estoque,
                            data.response.precos_programado[fields].coluna_a,
                            data.response.precos_programado[fields].coluna_b,
                            data.response.precos_programado[fields].coluna_c,
                            data.response.precos_programado[fields].prazo_vista,
                            data.response.precos_programado[fields].prazo_15,
                            data.response.precos_programado[fields].prazo_30,
                            data.response.precos_programado[fields].prazo_45,
                            data.response.precos_programado[fields].prazo_60,
                            data.response.precos_programado[fields].prazo_75,
                            data.response.precos_programado[fields].prazo_90,
                        ];
                        precos.push(temp_array)
                    }
                    table_filters_detalhes_comercio_programado.rows.add(precos).draw(); 
                @endif

                var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
                var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60", 'prazo_75', 'prazo_90');
                @if (Auth::user()->tipo_usuario_id != 16)
                    table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                    table_filters_detalhes_comercio_programado.columns('.prazo').visible(false);

                    if(coluna.indexOf($("#coluna").val()) != -1){
                        table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                        table_filters_detalhes_comercio_programado.columns('.prazo').visible(true);

                    }
                    else if (prazo.indexOf($("#coluna").val()) != -1){

                        table_filters_detalhes_comercio_programado.columns('.prazo').visible(false);
                        table_filters_detalhes_comercio_programado.columns('.comissoes').visible(true);

                    }
                @else
                    table_filters_detalhes_comercio_programado.columns('.comissoes').visible(false);
                    table_filters_detalhes_comercio_programado.columns('.prazo').visible(true);
                @endif
            },
            error: function(data){
                hide_loader();
                if((data.responseJSON.errors)){
                    var errors = data.responseJSON.errors;
                    form_comercial_detalhes.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form_comercial_detalhes, field, errors[field])
                    }
                }else{
                    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
                }
            }
        });
    }

    function salvarCampos(data_form){
        $.ajax({
            url: "{{ route('ficha_tecnica_comercial.salvar_filtro_detalhes') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            async: false,
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){

            },
            error: function(data){

            }
        });
    }

    function detectar_mobile() {
        var check = false; //wrapper no check
        (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
        return check;
    }

    function verificacaoCarrinho(){
        $.ajax({
            url: "{{ route('ficha_tecnica_comercial.verificar_carrinho') }}",
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}"},
            method: 'POST',
            beforeSend: function(){
                hide_loader();
            },
            success: function(data){
                if(data.response.carrinho){
                    $(document).find('#dados_carrinhos').show();
                    $(document).find('#dados_carrinhos_indice').show();      
                    $(document).find('#sem_dados_carrinhos').hide();
                    $(document).find('#dados_cliente').html("<label><b>Cliente: </b>"+data.response.cliente+"</label>");
                    $(document).find('#dados_pedido_valor').html("<label><b>Valor: </b>"+data.response.valor+"</label>");
                    $(document).find('#contador').html(data.response.contador);
                    $(document).find('#contador_indice').html(data.response.contador);
                    if(data.response.estado != ''){
                        $("#estado").val(data.response.estado);
                        $("#estado").attr("disabled", "disabled");
                    }
                    @if (Auth::user()->tipo_usuario_id != 16)
                        if(data.response.coluna != ''){
                            $("#coluna").val(data.response.coluna);
                            $("#coluna").attr("disabled", "disabled");
                        }
                    @endif
                    if(data.response.frete != ''){
                        $("#frete").val(data.response.frete);
                        $("#frete").attr("disabled", "disabled");
                    }
                    if(data.response.tipo_cliente != ''){
                        $("#tipo_cliente").val(data.response.tipo_cliente);
                        $("#tipo_cliente").attr("disabled", "disabled");
                    }

                    verificacao_carrinho = false;
                }else{
                    $(document).find('#dados_carrinhos').hide();
                    $(document).find('#dados_carrinhos_indice').hide();
                    $(document).find('#sem_dados_carrinhos').show();
                    $("#estado").removeAttr('disabled');
                    $("#coluna").removeAttr('disabled');
                    $("#frete").removeAttr('disabled');
                    $("#tipo_cliente").removeAttr('disabled');
                }

            },
            error: function(data){
                hide_loader();
                verificacao_carrinho = false;
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        });
    }
    function abrirNotaEntrada($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
</script>
@endsection