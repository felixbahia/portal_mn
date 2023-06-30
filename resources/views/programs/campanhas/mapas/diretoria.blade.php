@extends("layouts.app")
@section("content")
<div class="filtro-campanha-meta-diretoria">
    <div class="logo-campanha">
        <div class="conteudo-logo">
            <img src="/images/campanhas-sem-filtro.png" width="250" height="340">
        </div>
    </div>
    <div class="inputs-campanha">
        <form action="#" name="form_filter_campanha" id="form_filter_campanha" onsubmit="return false;">
            @csrf
            <div class="content-fields">
                <div class="row">
                    <div>
                        {{ Form::text("nome_campanha", "", ["id" => "nome_campanha", "class" => "form-control", "placeholder" => "Indique da Campanha"]) }}
                    </div>
                    <div>
                        {{ Form::select("periodo", [], "", ["id" => "periodo_filtro", "class" => "form-control", "placeholder" => "Período"]) }}
                    </div>
                    <div class="form-group lupa-content">
                        <a href="javascript:void(0)" name="btn-filterform" id="btn-filterform" class="lupa"></a>
                    </div>
                    <div class="form-group">
                        <input type="reset" name="btn-clearform" id="btn-clearform" class="limpar" value="LIMPAR"/>
                    </div>
                </div>
            </div>
        </form>	
    </div>
</div>
<div class="campanha-placar-diretoria">
    <div class="row texto-titulo">
        <div class="texto">
            <strong style="margin-left: 20% !important">Diretoria - Acompanhe o desempenho  da campanha!</strong>
        </div>
    </div>
    <div class="placar">
        <div class="meta-vendedores"><b>META VENDEDORES E REPRESENTANTES</b></div>
        <div class="meta-gerente"><b>META GERENTES</b></div>
        <div class="barras">
            <div class="barra-direira">&nbsp</div>
            <div class="barra-esquerda">&nbsp</div>
            <div class="barra-vertical">&nbsp</div>
            <div class="balao-meta-vendedores">
                <p class="texto-meta"><b>META</b></p><br>
                <p class="texto-valor" id="meta-vendedor-valor"></p>
            </div>
            <div class="balao-meta-atingido">
                <p class="texto-meta"><b>ATINGIDO</b></p><br>
                <p class="texto-valor" id="atingido-vendedor-valor"></p>
            </div>
            <div class="balao-meta-percentual">
                <p class="texto-percentual"><b id="percentual-vendedor"></b></p>
            </div>
            <div class="balao-meta-gerentes">
                <p class="texto-meta"><b>META</b></p><br>
                <p class="texto-valor" id="meta-gerente-valor"></p>
            </div>
            <div class="balao-meta-atingido-gerentes">
                <p class="texto-meta"><b>ATINGIDO</b></p><br>
                <p class="texto-valor" id="atingido-gerente-valor"></p>
            </div>
            <div class="balao-meta-percentual-gerentes">
                <p class="texto-percentual"><b id="percentual-gerente"></b></p>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-3 text-center">
            <div class="campanha-apresentar-volume w-75">
                <div class="nomenclatura-bola">
                    <div class="texto">
                        M
                    </div>
                </div>
                <div class="valor" id="medida_metros">
                </div>
                <div class="soma">
                    +
                </div>
            </div>
        </div>
        <div class="col-lg-3 text-center">
            <div class="campanha-apresentar-volume w-75">
                <div class="nomenclatura-bola">
                    <div class="texto">
                        KG
                    </div>
                </div>
                <div class="valor" id="medida_kilos">
                </div>
                <div class="soma">
                    +
                </div>
            </div>
        </div>
        <div class="col-lg-3 text-center">
            <div class="campanha-apresentar-volume w-75">
                <div class="nomenclatura-bola">
                    <div class="texto">
                        UN
                    </div>
                </div>
                <div class="valor" id="medida_unidade">
                </div>
                <div class="soma">
                    =
                </div>
            </div>
        </div>
        <div class="col-lg-3 text-center">
            <div class="campanha-apresentar-volume w-75">
                <div class="nomenclatura-bola">
                    <div class="texto-total">
                        TOTAL
                    </div>
                </div>
                <div class="valor" id="total_soma">
                </div>
            </div>
        </div>
    </div>
</div>
<ul class="nav nav-tabs float-left ml-5 mt-2">
    <li class="nav-item float-left  campanha-nav-diretoria-tabelas rank-selecionado">
      <a class="nav-link" id='table-rank-tab' data-toggle="tab" href="#table_ranks" role="tab" aria-controls="table_ranks" aria-selected="true">Rank</a>
    </li>
    <li class="nav-item float-left  campanha-nav-diretoria-tabelas ml-1 segmentos-selecionado">
      <a class="nav-link" id='segmento-equipe-tab' data-toggle="tab" href="#segmentos_equipe" role="tab" aria-controls="segmentos_equipe" aria-selected="false">Segmento / Equipe</a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane show active" id="table_ranks" role="tabpanel" aria-labelledby="dados-tab">
        <div class="campanha-table"  style="margin-top: -7px !important">
            <table class="table" id="campanha-filtro">
                <thead>
                    <tr>
                        <th colspan="5"><div class="ultima_atualizacao"></div></th>
                    </tr>
                <tr>
                    <th scope="col" class="text-left text-secondary">POS</th>
                    <th scope="col" class="text-left text-secondary">VENDEDOR/REPRESENTANTE</th>
                    <th scope="col" class="text-left text-secondary">FAT. BRUTO</th>
                    <th scope="col" class="text-left text-secondary">DEVOLUÇÃO</th>
                    <th scope="col" class="text-left text-secondary">FAT. LÍQUIDO</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <div class="tab-pane" id="segmentos_equipe" role="tabpanel" aria-labelledby="dados-tab" style="margin-top: -1px">
        <div class="campanha-table-gerentes campanha-diretoria-segmentos-equipe pt-4">
            <div class="tabela">
                <table class="table" id="campanha-filtro-segmento" style="border: none !important; box-shadow: none;">
                    <thead>
                    <tr>
                        <th colspan="3"><div class="ultima_atualizacao"></div></th>
                    </tr>
                    <tr>
                        <th colspan="3"><b>TOP 5 DOS SEGMENTOS MAIS VENDIDOS</b></th>
                    </tr>
                    <tr>
                        <th scope="col" class="text-left text-width text-secondary">POS</th>
                        <th scope="col" class="text-left text-secondary">SEGMENTOS</th>
                        <th scope="col" class="text-right text-secondary">%</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="tabela-rank border-left border-secondary  pt-4">
                <table class="table" id="campanha-filtro-rank"  style="border: none !important; box-shadow: none;">
                    <thead>
                    <tr>
                        <th colspan="4"><div class="ultima_atualizacao"></div></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-left"><b>RANK POR EQUIPES</b></th>
                    </tr>
                    <tr>
                        <th scope="col" class="text-left text-secondary text-width">POS</th>
                        <th scope="col" class="text-left text-secondary">EQUIPES</th>
                        <th scope="col" class="text-right text-secondary">QUANTIDADE/VOLUME</th>
                        <th scope="col" class="text-right text-secondary">V. LÍQUIDO</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section("script-footer")
$(document).ready( function () {
    $(document).find('#nome_campanha').autocomplete(optionsAutoCompleteCampanha());
    $(document).find("#texto-direito").empty();
    $(document).find("#texto-esquerdo").empty();
    $(document).find('.segmentos-selecionado').css({"margin-top": "0px"}).css({"height": "45px"});
    $(document).find('.rank-selecionado').css({"margin-top": "2px"}).css({"height": "45px"});
    $(document).find(".soma").css({"margin-top": "-5%"});
    $(document).find('#nome_campanha').val('Gol de Ouro');

    setTimeout(function(){
        populaPeriodos();
        $(function(){
            function buscaPeriodo(){
                filterAjax($(document).find("#form_filter_campanha").serialize());
                filterAjaxCampanhaDiretoria($(document).find("#form_filter_campanha").serialize());
            };
            window.setTimeout(buscaPeriodo, 2000);
         });
    }, 1000);

    setInterval(function(){
        $(document).find('th').removeClass('sorting_asc');
        $(document).find('.nav-link').removeClass('active');
    }
    , 10);

    $(document).find( ".rank-selecionado" ).click(function() {
        $(document).find('.rank-selecionado').css({"margin-top": "2px"}).css({"height": "45px"});
        $(document).find('.segmentos-selecionado').css({"margin-top": "0px"}).css({"height": "45px"});
    });

    $(document).find( ".segmentos-selecionado" ).click(function() {
        $(document).find('.segmentos-selecionado').css({"margin-top": "2px"}).css({"height": "45px"});
        $(document).find('.rank-selecionado').css({"margin-top": "0px"}).css({"height": "45px"});
    });

    $(document).find( "#nome_campanha" ).keyup(function() {
        if($(document).find( "#nome_campanha" ).val().length == 0){
            limparSelect('periodo_filtro');
            $(document).find('#periodo_filtro').append("<option value=''>Período</option>");
        }
    });

    $(document).find( "#btn-clearform" ).click(function() {
        limparSelect('periodo_filtro');
        $(document).find('#periodo_filtro').append("<option value=''>Período</option>");
    });

    $(document).find("#btn-filterform").on("click", function(){
        filterAjax($(document).find("#form_filter_campanha").serialize());
        filterAjaxCampanhaDiretoria($(document).find("#form_filter_campanha").serialize());
    });

    table_filters = $('#campanha-filtro').DataTable({
        "searching": false,
        "paging": false,
        "lengthChange": false,
        "info": false,
        "orderMulti": false,
        "scrollCollapse": true,
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
                "targets": [0,1,2,3,4],
                orderable: false,
                className: 'sorting_disabled'
            },
            {
                "targets": 'sorting_asc',
                className: 'sorting_disabled'
            },
            {
                "targets": 'text-left',
                className: 'text-left'
            }
        ]
    });

    table_filters.on('draw', function () {
		$(document).find(".view-bruto-campanha").off("click");
		$(document).find(".view-bruto-campanha").on("click", function(event){
			event.stopPropagation();
			showModalBrutoDiretoria($(this));
		});

		$(document).find(".view-devolucao-campanha").off("click");
		$(document).find(".view-devolucao-campanha").on("click", function(event){
			event.stopPropagation();
			showModalDevolucaoDiretoria($(this));
		});
	});

    table_filters_campanha_segmento = $('#campanha-filtro-segmento').DataTable({
        "searching": false,
        "paging": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        "autoWidth": false,
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
                "targets": [0,1],
                orderable: false,
                className: 'sorting_disabled'
            },
            {
                "targets": 'sorting_asc',
                className: 'sorting_disabled'
            },
            {
                "targets": 'text-left',
                className: 'text-left'
            },
            {
                "targets": 'text-right',
                className: 'text-right'
            },
            {
                "targets":'text-width',
                'width': '30px'
            }
        ]
    });

    table_filters_campanha_segmento.on('draw', function () {
		$(document).find(".view-segmento-campanha").off("click");
		$(document).find(".view-segmento-campanha").on("click", function(event){
			event.stopPropagation();
			showModalSegmento($(this));
		});

	});

    table_filters_rank = $('#campanha-filtro-rank').DataTable({
        "searching": false,
        "paging": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        "autoWidth": false,
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
                "targets": 'sorting_asc',
                className: 'sorting_disabled'
            },
            {
                "targets": 'text-left',
                className: 'text-left'
            },
            {
                "targets": 'text-right',
                className: 'text-right'
            },
            {
                "targets":'text-width',
                'width': '30px'
            }
        ]
    });

    table_filters_rank.on('draw', function () {
		$(document).find(".view-equipe-campanha").off("click");
		$(document).find(".view-equipe-campanha").on("click", function(event){
			event.stopPropagation();
			showModalEquipe($(this));
		});
	});
});


function filterAjax(data_form){
    var form = $(document).find('#table-filters-campanha');
    $(document).find(".soma").css({"margin-top": "-5%"});
    $(document).find("#medida_metros").empty();
    $(document).find("#medida_kilos").empty();
    $(document).find("#medida_unidade").empty();
    $(document).find("#total_soma").empty();

    table_filters_campanha_segmento.clear().draw();
    table_filters_rank.clear().draw();

    $.ajax({
        url: "{{ route("campanha.meta.filtro_meta_diretor") }}",
        dataType: "json",
        data: data_form,
        method: "POST",
        success: function(response){
            table_filters_campanha_segmento.clear().draw();
            table_filters_rank.clear().draw();
            $(document).find("#meta-vendedor-valor").empty();
            $(document).find("#atingido-vendedor-valor").empty();
            $(document).find("#percentual-vendedor").empty();
            $(document).find("#meta-gerente-valor").empty();
            $(document).find("#atingido-gerente-valor").empty();
            $(document).find("#percentual-gerente").empty();
            $(document).find("#medida_metros").empty();
            $(document).find("#medida_kilos").empty();
            $(document).find("#medida_unidade").empty();
            $(document).find("#total_soma").empty();
            $(document).find(".soma").css({"margin-top": "-20%"});

            if(response.status == 'error'){
                $(document).find(".conteudo-logo").empty();
                $(document).find(".conteudo-logo").append('<img src="/images/campanhas-sem-filtro.png" width="250" height="340">');
                message('Atenção',response.message);
                return false;
            }

            if(response.response.retorno.logo != ""){
                $(document).find(".conteudo-logo").empty();
                $(document).find(".conteudo-logo").append(response.response.retorno.logo);
            }else{
                $(document).find(".conteudo-logo").empty();
                $(document).find(".conteudo-logo").append('<img src="/images/campanhas-sem-filtro.png" width="250" height="340">');
            }

            $(document).find("#meta-vendedor-valor").append(response.response.retorno.meta_vendedores);
            $(document).find("#atingido-vendedor-valor").append(response.response.retorno.valor);
            $(document).find("#percentual-vendedor").append(response.response.retorno.percentual_vendedores);
            $(document).find("#meta-gerente-valor").append(response.response.retorno.meta);
            $(document).find("#atingido-gerente-valor").append(response.response.retorno.valor);
            $(document).find("#percentual-gerente").append(response.response.retorno.percentual_tratado+"%");
            $(document).find("#medida_metros").append(response.response.retorno.metros_medida);
            $(document).find("#medida_kilos").append(response.response.retorno.kilo);
            $(document).find("#medida_unidade").append(response.response.retorno.unidade);
            $(document).find("#total_soma").append(response.response.retorno.metros);

            var fields_filter_segmento = [];
            var fields_filter_rank = [];
            data = response.response.retorno.segmentos;
            vendedores = response.response.retorno.vendedores;

            for(var field in data){
                var temp_field = [
                    data[field].ordem,
                    data[field].segmento,
                    data[field].percentual,
                ];

                fields_filter_segmento.push(temp_field);
            }

            table_filters_campanha_segmento.rows.add(fields_filter_segmento).draw().nodes();

            for(var field_vendedores in vendedores){
                var temp_field_vendedores = [
                    vendedores[field_vendedores].pos,
                    vendedores[field_vendedores].equipe,
                    vendedores[field_vendedores].quantidade,
                    vendedores[field_vendedores].liquido,
                ];
                fields_filter_rank.push(temp_field_vendedores);
            }

            table_filters_rank.rows.add(fields_filter_rank).draw().nodes();
        },
        error: function(callback){
            
            $(document).find(".soma").css({"margin-top": "-5%"});
            table_filters.clear().draw();
            message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
        }
    });
}

function filterAjaxCampanhaDiretoria(data_form){
    var form = $(document).find('#table-filters-campanha');
    table_filters.clear().draw();
    
    $.ajax({
        url: "{{ route("campanha.meta.filtro_meta") }}",
        dataType: "json",
        data: data_form,
        method: "POST",
        success: function(response){
            table_filters.clear().draw();
            $(document).find("#texto-direito").empty();
            $(document).find("#texto-esquerdo").empty();
            $(document).find("#periodo_legenda").empty();
            $(document).find("#medida_metros").empty();
            $(document).find("#medida_kilos").empty();
            $(document).find("#medida_unidade").empty();
            $(document).find(".ultima_atualizacao").empty();

            if(response.status == 'error'){
                $(document).find(".conteudo-logo").empty();
                $(document).find(".conteudo-logo").append('<img src="/images/campanhas-sem-filtro.png" width="250" height="340">');
                message('Atenção',response.message);
                return false;
            }

            if(response.response.retorno.logo != ""){
                $(document).find(".conteudo-logo").empty();
                $(document).find(".conteudo-logo").append(response.response.retorno.logo);
            }else{
                $(document).find(".conteudo-logo").empty();
                $(document).find(".conteudo-logo").append('<img src="/images/campanhas-sem-filtro.png" width="250" height="340">');
            }

            $(document).find("#texto-direito").append(response.response.retorno.valor);
            $(document).find("#texto-esquerdo").append(response.response.retorno.metros);
            $(document).find("#periodo_legenda").append(response.response.retorno.periodo);
            $(document).find("#medida_metros").append(response.response.retorno.metros_medida);
            $(document).find("#medida_kilos").append(response.response.retorno.kilo);
            $(document).find("#medida_unidade").append(response.response.retorno.unidade);
            $(document).find(".ultima_atualizacao").append(response.response.retorno.ultima_atualizacao);

            var fields_filter = [];
            data = response.response.retorno.vendedores;

            for(var field in data){
                var temp_field = [
                    data[field].POS,
                    data[field].vendedor_codigo,
                    data[field].bruto,
                    data[field].devolucao,
                    data[field].liquido,
                ];
                fields_filter.push(temp_field);
            }
            table_filters.rows.add(fields_filter).draw().nodes();
        },
        error: function(callback){
            table_filters.clear().draw();
            message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
            console.log(callback);
        }
    });
}

function optionsAutoCompleteCampanha(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            request.nome_campanha = $(document).find('#nome_campanha').val();
            $.post("{{ route('campanha.meta.auto_complete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find("#frm_cad_cliente_limite_credito").parents('.modal').css('z-index')) + 10));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhuma Campanha encontrada.');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#nome_campanha").val(ui.item.label);

            $.ajax({
                url: "{{ route("campanha.meta.listar_periodos") }}",
                dataType: "json",
                data: {
                    _token: '{{ csrf_token() }}',
                    nome_campanha: ui.item.label
                },
                method: "POST",
                success: function(response){
                    limparSelect('periodo_filtro');

                    if(response.length > 0){
                        for (var index = 0; index <= response.length; index++) {
                            if(typeof(response[index]) != "undefined"){
                                $(document).find('#periodo_filtro').append("<option value='" + response[index].id + "'>" + response[index].descricao + "</option>");
                            }
                         }
                    }
                },
                error: function(callback){
                    message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                }
            });

            return false;
        }
    };
}


function showModalSegmento($this){
	var segmento_id = $($this).data("segmento_id");
	var campos = $($this).data("campos");
	var descricao_segmento = $($this).data("descricao_segmento");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.segmentos_gerentes") }}",
		data: {_token: "{{ csrf_token() }}", segmento_id : segmento_id, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_segmentos", "SEGMENTO "+descricao_segmento, body, 'modal-lg');
		}
	});
}

function showModalEquipe($this){
	var unidade = $($this).data("unidade");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.equipes_gerentes") }}",
		data: {_token: "{{ csrf_token() }}", unidade : unidade, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_pedidos_valor_bruto", 'Lista de Pedidos', body, 'modal-lg');
		}
	});
}

function showModalBrutoDiretoria($this){
	var code = $($this).data("code");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.valor_bruto") }}",
		data: {_token: "{{ csrf_token() }}", code : code, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_pedidos_valor_bruto_diretoria", 'Lista de Pedidos', body, 'modal-lg');
		}
	});
}

function showModalDevolucaoDiretoria($this){
	var code = $($this).data("code");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.lista_nota_devolucao") }}",
		data: {_token: "{{ csrf_token() }}", code : code, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_devolucao_campanha_diretoria", 'Lista de Notas de Devolução', body, 'modal-lg');
		}
	});
}

function populaPeriodos(){
    $.ajax({
        url: "{{ route("campanha.meta.listar_periodos") }}",
        dataType: "json",
        data: {
            _token: '{{ csrf_token() }}',
            nome_campanha: $(document).find('#nome_campanha').val()
        },
        method: "POST",
        success: function(response){
            limparSelect('periodo_filtro');

            if(response.length > 0){
                for (var index = 0; index <= response.length; index++) {
                    if(typeof(response[index]) != "undefined"){
                        $(document).find('#periodo_filtro').append("<option value='" + response[index].id + "'>" + response[index].descricao + "</option>");
                    }
                 }

                 var ultimo_valor = $(document).find('#periodo_filtro  option:last-child').val();
                 $(document).find('#periodo_filtro option[value='+ultimo_valor+']').attr('selected','selected');
            }
        },
        error: function(callback){
            message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
        }
    });
}

function limparSelect(id){
	var selectObj = document.getElementById(id);
	var selectParentNode = selectObj.parentNode;
	var newSelectObj = selectObj.cloneNode(false);
	selectParentNode.replaceChild(newSelectObj, selectObj);
	return newSelectObj;
}
@endsection
