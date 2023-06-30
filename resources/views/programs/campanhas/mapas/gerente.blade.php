@extends("layouts.app")
@section("content")
<div class="filtro-campanha-meta-gerente">
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
                        {{ Form::select("periodo", [], "", ["id" => "periodo_filtro", "class" => "form-control"]) }}
                    </div>
                    <div class="form-group lupa-content">
                        <a href="javascript:void(0)" name="btn-filterform" id="btn-filterform" class="lupa"></a>
                    </div>
                    <div class="form-group">
                        <input type="reset" name="btn-clearform" id="btn-clearform" class="limpar" value="LIMPAR" />
                    </div>
                </div>
            </div>
        </form>	
    </div>
</div>
<div class="campanha-placar-gerente">
    <div class="row texto-titulo">
        <div class="texto">
            <strong>Gerencia - Acompanhe o seu resultado</strong>
        </div>
    </div>
    <div class="placar">
        <div class="titulo-logo">
            <b>VALOR LÍQUIDO</b>
        </div>
        <div class="w-100">
            <div class="barra">
                <div class="barra-fundo">
                </div>
                <b id="meta_valor"></b>
            </div>
        </div>
        <div class="periodo-lengeda">
            <strong id="periodo_legenda"></strong>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-4 text-center">
            <div class="campanha-apresentar-volume">
                <div class="nomenclatura-bola">
                    <div class="texto">
                        M
                    </div>
                </div>
                <div class="valor" id="medida_metros">
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-center">
            <div class="campanha-apresentar-volume">
                <div class="nomenclatura-bola">
                    <div class="texto">
                        KG
                    </div>
                </div>
                <div class="valor" id="medida_kilos">
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-center">
            <div class="campanha-apresentar-volume">
                <div class="nomenclatura-bola">
                    <div class="texto">
                        UN
                    </div>
                </div>
                <div class="valor" id="medida_unidade">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="campanha-table-gerentes">
    <div class="tabela ml-5">
        <table class="table" id="campanha-filtro">
            <thead>
                <tr>
                    <th colspan="3"><div class="ultima_atualizacao"></div></th>
                </tr>
                <tr>
                    <th colspan="3"><b>TOP 5 DOS SEGMENTOS MAIS VENDIDOS</b></th>
                </tr>
                <tr>
                    <th scope="col" class="text-left text-width">POS</th>
                    <th scope="col" class="text-left">SEGMENTOS</th>
                    <th scope="col" class="text-right">%</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div class="tabela-rank">
        <table class="table" id="campanha-filtro-rank">
            <thead>
                <tr>
                    <th colspan="4"><div class="ultima_atualizacao"></div></th>
                </tr>
                <tr>
                    <th colspan="4" class="text-left"><b>RANK POR EQUIPES</b></th>
                </tr>
                <tr>
                    <th scope="col" class="text-left text-width">POS</th>
                    <th scope="col" class="text-left">EQUIPES</th>
                    <th scope="col" class="text-right">QUANTIDADE/VOLUME</th>
                    <th scope="col" class="text-right">V. LÍQUIDO</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@endsection
@section("script-footer")
$(document).ready( function () {
    $(document).find('#nome_campanha').autocomplete(optionsAutoCompleteCampanha());
    $(document).find("#texto-direito").empty();
    $(document).find("#texto-esquerdo").empty();

    setInterval(function(){
        $(document).find('th').removeClass('sorting_asc');
    }
    , 1000);

    $(document).find('#nome_campanha').val('Gol de Ouro');

    setTimeout(function(){
        populaPeriodos();
        $(function(){
            function buscaPeriodo(){
                filterAjax($(document).find("#form_filter_campanha").serialize());
            };
            window.setTimeout(buscaPeriodo, 2000);
         });
    }, 1000);

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
    });

    table_filters = $('#campanha-filtro').DataTable({
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

    table_filters.on('draw', function () {
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
    $(document).find(".ultima_atualizacao").empty();

    $.ajax({
        url: "{{ route("campanha.meta.filtro_meta_gerentes") }}",
        dataType: "json",
        data: data_form,
        method: "POST",
        success: function(response){
            table_filters.clear().draw();
            table_filters_rank.clear().draw();
            $(document).find("#texto-direito").empty();
            $(document).find("#texto-esquerdo").empty();
            $(document).find("#periodo_legenda").empty();
            $(document).find("#meta_valor").empty();
            $(document).find(".fundo").css({"bottom": "0%"});
            $(document).find(".fundo").css({"height": "0%"});
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

            $(document).find("#meta_valor").append(response.response.retorno.valor);
            $(document).find("#texto-direito").append(response.response.retorno.valor);
            $(document).find("#texto-esquerdo").append(response.response.retorno.metros);
            $(document).find("#medida_metros").append(response.response.retorno.metros_medida);
            $(document).find("#medida_kilos").append(response.response.retorno.kilo);
            $(document).find("#medida_unidade").append(response.response.retorno.unidade);
            $(document).find(".ultima_atualizacao").append(response.response.retorno.ultima_atualizacao);
            $(document).find("#periodo_legenda").append(response.response.retorno.percentual_tratado+" %");
            $(document).find(".barra-fundo").css({"width": response.response.retorno.percentual+"%"});

            var fields_filter = [];
            var fields_filter_rank = [];
            data = response.response.retorno.segmentos;
            vendedores = response.response.retorno.vendedores;

            for(var field in data){
                var temp_field = [
                    data[field].ordem,
                    data[field].segmento,
                    data[field].percentual,
                ];
                fields_filter.push(temp_field);
            }
            console.log(fields_filter);
            table_filters.rows.add(fields_filter).draw().nodes();

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
            message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
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
	var equipe = $($this).data("equipe");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.equipes_gerentes") }}",
		data: {_token: "{{ csrf_token() }}", unidade : unidade, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_pedidos_valor_bruto", equipe, body, 'modal-lg');
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
