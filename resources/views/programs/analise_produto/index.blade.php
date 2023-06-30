@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="filtro-campos" >
            <div class="col-lg-2">
                <select name="estabel" id="estabel">
                    <option value=''>Todos os estabelecimentos</option>
                    @foreach($estabelecimento as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="codigo" id="codigo" value="" placeholder="Código Produto" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="nome" id="nome" value="" placeholder="Nome Produto" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
            </div>
            <div class="col-lg-2">
                {{ Form::select('segmentos', $segmentos, '', ['id' => 'segmentos', 'class' => 'form-control', 'placeholder' => 'Selecione o Segmento']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select('campanha', $campanhas, '', ['id' => 'campanha', 'class' => 'form-control', 'placeholder' => 'Selecione a Campanha']) }}
            </div>            
            <div class="col-lg-1">
                <label><input type="checkbox" name="desativado" id="desativado" value="sim" />Desativado</label>
            </div>
            <div class="col-lg-2">
                <label><input type="checkbox" name="agrupar" id="agrupar" value="sim" />Grupo de Material</label>
            </div>
        </div>
        <div class="filtro-linha">
            <div id="row">
                <div class="col-lg-8" id="filtros-show-param">
                </div>
            </div>
        </div>

    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
<div class="tooltext-click">Clique aqui para mostrar o filtro</div>
@endsection

@section('content')
<div class="content-table">
    <div class="dt-buttons btn-group"><button class="btn btn-secondary buttons-excel buttons-html5" tabindex="0" aria-controls="table-filters-produtos" type="button"><span> </span></button> </div>
    <div id="dados_carrinhos_indice" name="dados_carrinhos_indice">
        <br>
        <a href='#' data-cliente='' data-book_id='' data-filtro='' class='bt-carrinho-book' style="color: #007fff;"></a>&nbsp;<span id='contador_indice' style='font-size: 16px;color: black;'>{{$quantidade}}</span>
    </div>
    <table class="table table-striped table-produto-analise" id="table-filters-produtos-analise">
        <thead>
            <tr>
                <th class='produto_individual'>Código</th>
                <th class='produto_individual'>Descrição</th>
                <th class="grupo">Grupo</th>
                <th class="tb_number">Pronta<br> Entrega</th>
                @foreach($header_meses as $header_mes)
                <th class="tb_number">{!! $header_mes !!}</th>
                @endforeach
                <th class="tb_number">Futuro</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>Total:</td>
                <td>Total:</td>
                <td class="total-pronta_entrega"></td>
                @foreach($header_meses as $key => $header_mes)
                <td class="total-k_{{ $key }}"></td>
                @endforeach
                <td class="total-futuro"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        mostraFiltros();
        $(".tooltext-click").on("click", function(){
            mostraFiltros();
        });
        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $("#form_filter").on('submit', function(){
            $('.dropdown-toggle').focus()
            filterAjax($("#form_filter"));
        });
        $(".buttons-excel").hide();
        $(".buttons-excel").on("click", function(){
            $('<form action="{{ route('analise.produto.export') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="estabel" id="estabel" value="'+$("#estabel").val()+'">\
                <input type="hidden" name="grupo" id="grupo" value="'+$("#grupo").val()+'" />\
                <input type="hidden" name="codigo" id="codigo" value="'+$("#codigo").val()+'"  />\
                <input type="hidden" name="nome" id="nome" value="'+$("#nome").val()+'" />\
                <input type="hidden" name="marca" id="marca" value="'+$("#marca").val()+'" />\
                <input type="hidden" name="linha" id="linha" value="'+$("#linha").val()+'" />\
                <input type="hidden" name="campanha" id="campanha" value="'+$("#campanha").val()+'" />\
                <input type="hidden" name="segmentos" id="segmentos" value="'+$("#segmentos").val()+'" />\
                <input type="checkbox" name="desativado" id="desativado" value="sim" '+(($("#desativado:checked").length)?"checked": "")+' />\
                <input type="checkbox" name="agrupar" id="agrupar" value="sim" '+(($("#agrupar:checked").length)?"checked": "")+' />\
            </form>').appendTo('body').submit().remove();
        });
        $height = $("#app").height() - 300;
        table_filters = $('#table-filters-produtos-analise')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": false,
            "orderMulti": false,
            "language": {
                "thousands":      ",",
                "decimal":        ".",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
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
            "columnDefs": columnOrder(),
            "columns": [
                { "data": "codigo" },
                { "data": "descricao" },
                { "data": "grupo" },
                { "data": "pronta_entrega" },
                @foreach($header_meses as $key => $header_mes)
                { "data": '{{ $key }}' },
                @endforeach 
                { "data": "futuro" }
            ],
            "order": [[ 1, 'asc' ]]
        });

        table_filters.on('xhr', function(){
            table_filters.clear();
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-show").off("click");
            $(document).find(".bt-show").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });

        visibilidadeColunas();

        var verificacao_carrinho = false;

        $(document).find(".bt-carrinho-book").off("click");
        $(document).find(".bt-carrinho-book").on("click", function(event){
            event.stopPropagation();
            modalVisualizarCarrinho($(this));
        });

        @if ($carrinho)
            $(document).find('#dados_carrinhos_indice').show();
        @else
            $(document).find('#dados_carrinhos_indice').hide();
        @endif
    });
    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.grupo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    filterAjax($("#form_filter"), event);
                    
                }, 100);
            }
        };
    }
    function showModal($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        var title = "Visualizar Produto - " + $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codprd: id},
            method: 'POST',
            success: function(body){
                createModal("model_produto_view", title, body, 'modal-lg');
                var modal = $(document).find("#model_produto_view");
                modal.css("z-index", 10);
                $(".modal-backdrop").css("z-index", 9);
                $(document).find("#model_produto_view").find('.content-view-cliente').find('.btn-view-estoque').off("click");
                $(document).find("#model_produto_view").find('.content-view-cliente').find('.btn-view-estoque').on('click', function(){
                    showModalPecaPeca($(this));
                });
            }
        });
    }
    function showModalPecaPeca($this){
        var url = $($this).data("url");
        var codigo = $($this).data("codigo");
        var estabel = $($this).data("estabel");
        var title = $($this).data('title');
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel},
            method: 'POST',
            success: function(body){
                createModal("model_pecapeca_view", title, body, 'modal-lg');
                var modal = $(document).find("#model_pecapeca_view");
                modal.css("z-index", 14);
                $(".modal-backdrop").css("z-index", 13);
                modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').off("click");
                modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').on('click', function(){
                    showModalPecaPecaPedido($(this));
                });
                modal.off('hidden.bs.modal');
                modal.on('hidden.bs.modal', function (e) {
                    $(".modal-backdrop").css("z-index", 9);
                    $("#model_pecapeca_view").remove();
                });
            }
        });
    }
    function showModalPecaPecaPedido($this){
        var url = $($this).data("url");
        var codigo = $($this).data("codigo");
        var estabel = $($this).data("estabel");
        var codigo_item = $($this).data("codigo_item");
        var title = $($this).data('title');
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel, codigo_item: codigo_item},
            method: 'POST',
            success: function(body){
                createModal("model_pecapeca_pedido_view", title, body, 'modal-lg');
                var modal = $(document).find("#model_pecapeca_pedido_view");
                modal.css("z-index", 18);
                $(".modal-backdrop").css("z-index", 17);
                modal.off('hidden.bs.modal');
                modal.on('hidden.bs.modal', function (e) {
                    $(".modal-backdrop").css("z-index", 13);
                    $("#model_pecapeca_pedido_view").remove();
                });
            }
        });
    }
    function columnOrder(){
        $return = [];
        for(var i = 3; i < $('#table-filters-produtos-analise thead th').length; i++){
            $return.push({
                "targets": (i),
                "orderable": true,
                "class": "tb_number",
                "type": 'num-fmt'
            });
        }
        return $return;
    }
    function createTotalUnidade($dados){
        if(!($dados)){
            return "";
        }
        var $html = "<div class='title'>Total</div><div class='content-total'>";
        $.each($dados, function(){
            $html += "<div>"+this.unidade+": "+this.quantidade+"</div>";
        });
        $html += "</div>";
        return $html;
    }
    function filterAjax(form){

        table_filters.clear().draw();
        var $return;
        $("#total_unidade").html("");
        $(document).find('[class^=total-]').each(function(){
            $(this).html('');
        });
        $(".buttons-excel").hide();
        xhr = $.ajax({
            url: "{{ route('analise.produto.filter') }}",
            dataType: 'json',
            data: form.serialize(),
            method: 'POST',
            success: function(callback){
                if(callback.status === "error"){
                    message("Atenção", callback.message);
                    return false;
                }
                visibilidadeColunas();
                callback.data = parserDataJson(callback.data);
                table_filters.clear().draw();
                table_filters.rows.add(callback.data).order([ 1, 'asc' ]).draw().nodes();
                
                $('[data-toggle="popover"]').off('show.bs.popover');
                $('[data-toggle="popover"]').popover('hide');

                $('[data-toggle="popover"]').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                });

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

                if((callback.data).length > 0){
                    $(".buttons-excel").show();
                }else{
                    $(".buttons-excel").hide();
                }
                var filtro = [];

                form.find("select, input[type='text'], input[type='checkbox']").each(function() {
                    var valor;
                    if($(this).attr('type') == 'checkbox'){
                        if($(this).is(":checked") === true){
                            if($(this).attr('name') == 'agrupar'){
                                filtro_linha =  "<b>Grupo de Material:</b> \"Sim\"";
                            }
                            else{
                                filtro_linha =  "<b>" + $(this).attr('name') + ":</b> \"Sim\"";
                            }
                            filtro.push(filtro_linha);
                        }
                    }
                    else{
                        if ($(this).val().length > 0){
                            if($(this).prop('tagName') == "SELECT"){
                                valor = $(this).find(":checked").text();
                            }
                            else{
                                valor = $(this).val();
                            }
                            filtro_linha =  "<b>" + $(this).attr('name') + ":</b> \"" + valor + "\"";
                            filtro.push(filtro_linha);
                        }
                    }

                });
                $.each(callback.total_coluna, function(index){
                    $('.dataTables_scrollFootInner').find('.total-'+index).html('');
                    $('.dataTables_scrollFootInner').find('.total-'+index).html(this);
                });
                $("#filtros-show-param").html(filtro.join(' - '));
                escondeFiltros();
            }
        });
    }
    function escondeFiltros(){
        $(".filtro-campos").slideUp('slow');
        $(".content-buttons").slideUp('slow');

        $(".filtro-linha").slideDown('fast');
        $(".tooltext-click").fadeIn('slow');
    }

    function mostraFiltros(){
        $(".filtro-campos").slideDown('slow');
        $(".filtro-linha").slideUp('fast');
        $(".content-buttons").show('fast');
        $(".tooltext-click").fadeOut('slow');
    }

    function parserDataJson(data){
        var $return = [];
        $.each(data, function(index, el) {
            var fichar_comercial = createdBtFichaComercial(this.codigo, this.descricao);
            var carrinho = createdBtCarrinho(this.link_carrinho);

            var fichar_comercial = createdBtFichaComercial(this.codigo, this.descricao, this.estoque_dados, carrinho);

            var temp = {
                "codigo": carrinho + '&nbsp;&nbsp;&nbsp;' + fichar_comercial + ' ' + this.codigo,
                "descricao": this.descricao,
                "grupo": criarLinkGrupo(this.grupo, this.descricao),
                "pronta_entrega": ((this.pronta_entrega).trim() != '') ? "<div class=\"bt-view-list\" data-toggle=\"popover\" data-trigger='hover' title=\"Estoque por empresa\" data-content=\""+createBodyPopOver(this.estoque_dados)+"\">"+this.pronta_entrega+'<span class=\"bt-view\"></span></div>' : '',
                @foreach($header_meses as $key => $header_mes)
                "{{ $key }}": this.quinzenas.k_{{ $key }}.quantidade,
                @endforeach 
                "futuro": this.futuro
            };
            $return.push(temp);
        });
        return $return;
    }
    function createBodyPopOver($this){
        var $return = "";
        $.each($this,function(index, el) {
            $return += "<p>"+this+"</p>";
        });
        return $return;
    }
    function createBtProntaEntrega($this){
        var html = "";
        html = "<a href=\"#\" class=\"bt-show\" data-route=\"{{ route('produto.view') }}\" data-id=\""+$this.codigo+"\" data-title='"+$this.title_modal+"'>"+$this.pronta_entrega_total+"</a>";
        return html;
    }

    function visibilidadeColunas(){
        if($(document).find('#agrupar').is(':checked')) {
            table_filters.columns('.produto_individual').visible(false);
            table_filters.columns('.grupo').visible(true);
        }
        else{
            table_filters.columns('.produto_individual').visible(true);
            table_filters.columns('.grupo').visible(false);
        }
    }

    function criarLinkGrupo($grupo, $descricao){
        return "<a href=\"#\" onclick=\"pesquisarGrupo('"+$grupo+"')\">"+$grupo+"</a>";
    }

    function pesquisarGrupo($grupo){
        $(document).find('#grupo').val($grupo);
        $(document).find('#agrupar').prop('checked', false);
        filterAjax($("#form_filter"));
    }

    function createdBtFichaComercial($codigo, $descricao, $estoque_dados, $carrinho){
        $estabelecimentos = '';
        $estoque_produto_entrega = '';
        $.each($estoque_dados,function(index, el) {
            $estabelecimentos += ""+this.substring(0,2)+"-";
            $estoque_produto_entrega += ""+this+";";
        });
          
        html = '<a href="#" class="btn-pedido" title="Ficha Técnica Comercial" data-title="Ficha Técnica Comercial" data-codigo="'+$codigo+'" data-estabelecimentos="'+$estabelecimentos+'" data-estoque_produto_entrega="'+$estoque_produto_entrega+'" data-carrinho=\''+$carrinho+'\' onclick="modalFichaComercialDetalhes($(this))" style="display: initial !important;"></a>';

        return html; 
    }

    function createdBtCarrinho($link_carrinho){
        html = '<a href="'+$link_carrinho+'" target="_blank" rel="noopener" title="Adicionar Carrinho" class="bt-carrinho-comprar" style="color: black;"></a>';

        return html; 
    }

    function modalFichaComercialDetalhes($this){
        var $codigo = $this.data("codigo");
        var $estabelecimentos = $this.data("estabelecimentos");
        var $estoque_produto_entrega = $this.data("estoque_produto_entrega");
        var $carrinho = $this.data("carrinho");

        if(checkDevice()){
            var $title = "";
        }else{
           var $title = $this.data("title"); 
        }

        esconderPopoverTooltip();
        $.ajax({
            url: '{{ route('ficha_tecnica_comercial.modal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                codigo: $codigo,
                estabelecimentos: $estabelecimentos,
                estoque_produto_entrega: $estoque_produto_entrega,
                carrinho: $carrinho,
            },
            success: function (data){
                createModal('detalhes_ficha_comercial', $title, data, 'modal-lg');
            }
        });
    }

    function checkDevice() { 
        if( navigator.userAgent.match(/Android/i)
        || navigator.userAgent.match(/webOS/i)
        || navigator.userAgent.match(/iPhone/i)
        || navigator.userAgent.match(/iPad/i)
        || navigator.userAgent.match(/iPod/i)
        || navigator.userAgent.match(/BlackBerry/i)
        || navigator.userAgent.match(/Windows Phone/i)
        ){
            return true; 
        }
        else {
            return false;
        }
    }

    function modalVisualizarCarrinho($this){
        var title = 'Editar Carrinho '+$($this).data("cliente");
        var book_id = $($this).data("book_id");
        var filtro = $($this).data("filtro");
        xhr = $.ajax({
            url: '{{ route('book_virtual_exibicao.modal.editar') }}',
            data: {
                _token: "{{ csrf_token() }}",
                book_id: book_id,
                filtro: filtro
            },
            method: 'POST',
            success: function(body){
                if(body.status === 'success'){
                    if(body.response.itens === false){
                        message("Atenção", "Seu carrinho esta vazio!");
                    }
                }else{
                    createModal("modal_editar_carrinho", title, body, 'modal-lg');
                }
            }
        });
    }  
@endsection
