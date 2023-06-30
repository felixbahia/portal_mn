@extends("layouts.app")
@section("content")
<div class="filtro-campanha-meta">
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
                        <input type="reset" name="btn-clearform" id="btn-clearform" class="limpar" value="LIMPAR" />
                    </div>
                </div>
            </div>
        </form>	
    </div>
</div>
<div class="campanha-placar">
    <div class="row texto-titulo">
        <div class="texto">
            <strong>VENDEDOR/REPRESENTANTE - Acompanhe o seu resultado!</strong>
        </div>
    </div>
    <div class="placar">
        <div class="titulo-metros">
            <strong>QUANTIDADE/<br>VOLUME</strong>
        </div>
        <div class="titulo-logo">
            <img src="/images/logomn-sem-fundo.png" width="70" height="40" style="margin-top: 2px">
        </div>
        <div class="titulo-valor">
            <div class="valor"><strong>FAT. VALOR LÍQUIDO</strong></div>
        </div>
        <div class="barra">
            <div class="texto-esquerdo">
                <label><b id="texto-esquerdo"></b></label>
            </div>
            <fieldset class="versus">
                <legend><strong>X</strong></legend>
            </fieldset>
            <div class="texto-direito">
                <label><b id="texto-direito"></b></label>
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
<div class="campanha-table">
    <table class="table" id="campanha-filtro">
        <thead>
            <tr>
                <th colspan="5"><div id="ultima_atualizacao"></div></th>
            </tr>
            <tr>
                <th scope="col" class="text-left">POS</th>
                <th scope="col" class="text-left">VENDEDOR/REPRESENTANTE</th>
                <th scope="col" class="text-left">FAT. BRUTO</th>
                <th scope="col" class="text-left">DEVOLUÇÃO</th>
                <th scope="col" class="text-left">FAT. LÍQUIDO</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section("script-footer")
$(document).ready( function () {
    $(document).find('#nome_campanha').autocomplete(optionsAutoCompleteCampanha());
    $(document).find("#texto-direito").empty();
    $(document).find("#texto-esquerdo").empty();
    
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
    
    setInterval(function(){
        $(document).find('th').removeClass('sorting_asc');
    }
    , 1000);

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
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
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
			showModalBruto($(this));
		});

		$(document).find(".view-devolucao-campanha").off("click");
		$(document).find(".view-devolucao-campanha").on("click", function(event){
			event.stopPropagation();
			showModalDevolucao($(this));
		});
	});
});

function showModalBruto($this){
	var code = $($this).data("code");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.valor_bruto") }}",
		data: {_token: "{{ csrf_token() }}", code : code, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_pedidos_valor_bruto", 'Lista de Pedidos', body, 'modal-lg');
		}
	});
}

function showModalDevolucao($this){
	var code = $($this).data("code");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.lista_nota_devolucao") }}",
		data: {_token: "{{ csrf_token() }}", code : code, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_devolucao_campanha", 'Lista de Notas de Devolução', body, 'modal-lg');
		}
	});
}

function filterAjax(data_form){
    var form = $(document).find('#table-filters-campanha');
    
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
            $(document).find("#ultima_atualizacao").empty();

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
            $(document).find("#ultima_atualizacao").append(response.response.retorno.ultima_atualizacao);

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
