@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
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
            <select name="segmentos" id="segmentos">
                <option value="">Segmentos</option>
                @foreach($segmentos as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <label><input type="checkbox" name="desativado" id="desativado" value="sim" />Desativado</label>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-produtos">
        <thead>
            <tr>
                <th>Grupo</th>
                <th class='codigo'>Código</th>
                <th>Descrição</th>
                <th>Marca</th>
                <th>Linha</th>
                <th>Segmento</th>
                <th>Gml.</th>
                <th>Larg.</th>
                <th>Composição</th>
                <th>Unidade</th>
                <th>Estoque Disponível</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')
<div class="modal fade" id="model_pecapeca_pedido_view" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        $("#grupo").autocomplete(optionsAutoCompleteGrupo("grupo"));
        $("#btn-filterform").on("click", function(){
            table_filters.clear().draw();
            filtro();
        });
        table_filters.on('draw', function () {
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
    });

    table_filters = $('#table-filters-produtos')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        "autowidth": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 7 || column === 10){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
        "drawCallback": function(settings) {
            $('[data-toggle="popover"]').popover({
                container: 'body',
                html: true,
                show: true,
                template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
            });
            $('[data-toggle="popover"]').on('show.bs.popover', function () {
                var $this = $(this);
                $('.popover').not($this).each(function(){
                    $("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
                });
                $("body").on("keyup", function(e){
                    if(e.keyCode == 27){
                        $($this).popover('hide');
                    }
                });
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

        },
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
                "targets": ($('#table-filters-produtos thead th').length - 1),
                "orderable": false
            },
            {
                "targets": ($('#table-filters-produtos thead th').length - 2),
                "orderable": false
            },
            {
                "targets": [6,7],
                "class": 'number_format',
            },
            {
                'targets': 1,
                'width': '120px',
                "orderable": false

            }

        ],
        "order": [[ 2, 'asc' ]]
    });

    function filtro(){
        table_filters.clear().draw();
        $form = $("#form_filter");
        $data = $form.serialize();
        $('label.error-message').remove();
		$.ajax({
			url: '{{ route('produto.filter')}}',
			type: 'POST',
			data: $data,
			success: function(data){
                var out = [];
                if(data.response){
                    for (var fields in data.response.dados){
                        var saida = parserDataJson(data.response.dados[fields]);
                        
                        out.push([
                            saida.grupo,
                            saida.codigo,
                            saida.nome,
                            saida.marca,
                            saida.linha,
                            saida.segmento,
                            saida.gramatura,
                            saida.largura,
                            saida.composicao,
                            saida.unidade,
                            saida.estoque
                        ]);
                    }

                    table_filters.rows.add(out).draw();
                }
                
			},
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
		}).always(function() {
            hide_loader();
        });
    }

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.clear().draw();
                    filtro();
                }, 100);
            }
        };
    }
    function showModal($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
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
                modal.find('.content-view-cliente').find('.btn-view-estoque').off("click");
                modal.find('.content-view-cliente').find('.btn-view-estoque').on('click', function(){
                    showModalPecaPeca($(this));
                });
            }
        });
    }
    function showModalFichaTecnica($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
        var title = "Ficha Tecnica - " + $($this).data("id") + " - " + $($this).data("descricao");
        xhr = $.ajax({
            url: url,
            data:{_token: "{{ csrf_token() }}", codigo_produto: id},
            type: 'POST',
            success: function(body){
                createModal("modal_ficha_tecnica_view", title, body, 'modal-lg');
            }
        });
    }
    function showModalPecaPeca($this){
        var url = $($this).data("url");
        var codigo = $($this).data("codigo");
        var estabel = $($this).data("estabel");
        var title = $($this).data('title');
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
                $("#model_pecapeca_view").off('hidden.bs.modal');
                $("#model_pecapeca_view").on('hidden.bs.modal', function (e) {
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
        $("#model_pecapeca_pedido_view").modal("toggle");
        $("#model_pecapeca_pedido_view").find('.modal-title').html($($this).data('title'));
        $("#model_pecapeca_pedido_view").off('shown.bs.modal');
        $("#model_pecapeca_pedido_view").on('shown.bs.modal', function (event) {
            var modal = $(this);
            modal.css("z-index", 18);
            $(".modal-backdrop").css("z-index", 17);
            xhr = $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel, codigo_item: codigo_item},
                method: 'POST',
                success: function(data){
                    modal.find('.modal-body').html(data);
                }
            });
        });
        $("#model_pecapeca_pedido_view").off('hidden.bs.modal');
        $("#model_pecapeca_pedido_view").on('hidden.bs.modal', function (e) {
            $(".modal-backdrop").css("z-index", 13);
            $("#model_pecapeca_pedido_view").find('.modal-body').html('');
            $("#model_pecapeca_pedido_view").find('.modal-title').html('');
        });
    }
    function parserDataJson(data){
        var temp = {
            "grupo": "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.grupo + "''>" + data.grupo + "</div></div>",
            "codigo": data.foto + ' ' + data.laudo + ' ' + data.codigo ,
            "nome": "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.nome + "''>" + data.nome + "</div></div>",
            "marca": "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.marca + "''>" + data.marca + "</div></div>",
            "linha": "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.linha + "''>" + data.linha + "</div></div>",
            "segmento": "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.segmento + "''>" + data.segmento + "</div></div>",
            "gramatura": data.gramatura,
            "largura":  data.largura,
            "composicao": data.composicao,
            "unidade": data.unidade,
            "estoque": createBtView(data)
        };
        return temp;
    }

    function createBodyPopOver($this){
        var $return = "";
        $.each($this,function(index, el) {
            $return += "<p>"+this+"</p>";
        });
        return $return;
    } 

    function createBtView($this){
        var html = "";
        if($this.estoque == "0,00"){
            html = "<a href=\"#\" data-route=\"{{ route('produto.view') }}\" data-id=\""+$this.codigo+"\" class=\"bt-view\" data-title='"+$this.title_modal+"'>"+$this.estoque+"</a>";
        }else{
            html = "<a href=\"#\" data-route=\"{{ route('produto.view') }}\" data-id=\""+$this.codigo+"\" class=\"bt-view\" data-toggle=\"popover\" data-trigger='hover' title=\"Estoque por empresa\" data-title='"+$this.title_modal+"' data-content=\""+createBodyPopOver($this.total_popover)+"\">"+$this.estoque+"</a>";
        }
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

        if($estabelecimentos == ''){
            var $modal = "modal-md";       
        }else{
            var $modal = "modal-lg"; 
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
                createModal('detalhes_ficha_comercial', $title, data, $modal);
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

    function optionsAutoCompleteGrupo($name){
  
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
                    table_filters.clear().draw();
                    filtro();
                }, 100);
            }
        };
    }
    
@endsection
</script>
