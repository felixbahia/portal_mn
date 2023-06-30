@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class='form-row'>
             
        <div class="col-lg-2">
            <div class="input-group">
                {{ Form::text('fornecedor_filtro', '', ['id' => 'fornecedor_filtro', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedorFiltro($(this))"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor_filtro-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
         <div class="col-lg-2">
            {!! Form::text('proforma_filtro', '', ['id' => 'proforma_filtro', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Proforma']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('pedido_filtro', '', ['id' => 'pedido_filtro', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Pedido']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Grupo']) !!}
        </div>
        <div class="col-lg-1">
            {!! Form::text('data_inicio', '', ['id' => 'data_inicio', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Previsão Inicial']) !!}
        </div>
        <div class="col-lg-1">
            {!! Form::text('data_fim', '', ['id' => 'data_fim', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Previsão Final']) !!}
        </div>
   
    </div>
    <div class="content-buttons mt-2">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table_filters">
        <thead>
            <tr>
            
                <th rowspan="2">Fornecedor</th>
                <th rowspan="2" class="tb_number">PCMN</th>
                <th rowspan="2">Grupo</th>
                <th rowspan="2" class="tb_number">Quantidade</th>
                <th rowspan="2">Unidade</th>
                <th rowspan="2" class="tb_date">Envio Cores</th>
                <th colspan="2">Proforma</th>
                <th colspan="2">quality sample</th>
                <th colspan="2">Laboratório</th>
                <th colspan="2">Produção</th>
                <th colspan="2">Embarque</th>
           </tr>
            <tr>
                <th class="tb_number">Número</th>
                <th class="tb_date">Recebimento</th>
                <th class="tb_date">Data</th>
                <th>Status</th>
                <th class="tb_date">Data</th>
                <th >Status</th>
                <th class="tb_date">Data</th>
                <th>Status</th>
                <th class="tb_date">Autorização</th>
                <th class="tb_date">Data</th>
        
           </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    $(document).find("#bt-search-fornecedor_filtro-busca").on("click", function(){
        showModalFornecedorFiltro($(this).data("route"), "Lista de Fornecedores", "fornecedor_filtro");
    });

    $(document).find("#btn-filterform").on("click", function(){
      filterClear();
        filterAjax();
    });



    table_filters = $('#table_filters').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
        ],
    });
  
    form = $(document).find("#form_filter");

    form.find('.data').datepicker({ 
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        language: 'pt-BR',
        autoHide: true,
    });
    form.find('.data').mask('00/00/0000');
    $('#data_inicio').on('pick.datepicker', function (e) {
        if($('#data_fim').datepicker('getDate') < e.date){
            $('#data_fim').val('');
        }
        $('#data_fim').datepicker('setStartDate', e.date);
    });
    carregarData();
    form.find("#descricao").autocomplete(optionsAutoCompleteFilter("nome"));
    form.find("#bt-search-produto").on('click', function(){
        showModalProduto(form);
    });

});

function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('importacao.consulta_pedido_aberto')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];

            for (var fields in data.response){

                temp_array = [

                    ajusteTamanhoTable(data.response[fields].fornecedor),
                    createLinkPedido( data.response[fields],data_form),
                    data.response[fields].grupo_produto,
                    data.response[fields].quantidade,
                    data.response[fields].unidade,
                    data.response[fields].data_envio_cores,
                    createBtView(data.response[fields], "PCMN: "+data.response[fields].pcmn + " Proforma: " + data.response[fields].proforma + " Fornecedor: " + data.response[fields].fornecedor),
                    data.response[fields].data_recebimento,
                    data.response[fields].data_quatlity,
                    data.response[fields].status_quatlity,
                    data.response[fields].data_laboratorio,
                    data.response[fields].status_laboratorio,
                    data.response[fields].data_producao,
                    data.response[fields].status_producao,
                    data.response[fields].data_embarque_autorizacao,
                    data.response[fields].data_embarque
                           
                 ];
                produtos.push(temp_array)
            }
            console.log(produtos);
            table_filters.rows.add(produtos).draw();            

        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}


function createLinkPedido($this, $form){
    var html = "";
    if($this.id_nota !== ""){
        html = "<a href='#' onclick=\"showComissaoDetalhes('" 
        + $this.id_nota + "', '"
        + $this.unidade + "', '"
        + $(document).find("#form_filter").find('#data').val() + "" 
        + "')\">" + $this.pcmn + "</a>";
    }
    return html;
}



function showComissaoDetalhes(id, unidade, data){
 
    $.ajax({
        url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: id,
            unidade: unidade,
            data: data
        },
        success: function(body){
            createModal("1", "Detalhe do Pedido Compras", body, 'modal-lg');
        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                message = '';
                $.each(data, function(index, el) {
                    message += el+'<br />';
                });
                message("Atenção", message);
            }
        }

    });
}

function showModal($this){
    var url = $($this).data("route");
    var $id = $($this).data("id");
    var modal_class = $($this).data("modal");
    var title = $($this).data("title_modal");
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id: $id},
        success: function(body){
            createModal('modal_importacao_edit_delete', title, body, modal_class);
            var modal = $("#modal_importacao_edit_delete");
        }
    });
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function optionsAutoCompleteFilter($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3
    };
}

function showModalProduto(form_modal){
    $.ajax({
        url: '{{ route('produto.modal_pesquisa') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (data){
            createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
            table_filters_produtos_busca.on('draw', function () {

                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                    returnDadosProduto($(this), form);
                });

            });
        }
    });
}

function returnDadosProduto($dados, form){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#modal_search_produto").modal("hide");
    
    form.find('#codigo_produto').val($dados.find("td").eq(1).text());
};

function showModalFornecedorFiltro(url, title, campo){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFornecedorFiltro($(this), campo);
                    });
                });
            });
        }
    });
}
function returnDadosFornecedorFiltro($this, campo){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_search_show").modal("hide");
    form.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
}

function optionsFornecedorFiltro($this){
    esconderPopoverTooltip();
    $this.autocomplete(optionsAutoCompleteFornecedorFiltro($this));   
}

function optionsAutoCompleteFornecedorFiltro($this){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('fornecedor.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum fornecedor encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $this.val(ui.item.label);
            return false;
        }
    };
}
function carregarData(){
  
    var d = new Date();
    var anoC = d.getFullYear();
    var mesC = d.getMonth();

    var d1 = new Date (anoC, mesC, 1);
    var d2 = new Date (anoC, mesC+1, 0);
    $('#data_inicio').val(dataAtualFormatada(d1));
    $('#data_fim').val(dataAtualFormatada(d2));
}
function dataAtualFormatada(data){
    dia  = data.getDate().toString().padStart(2, '0'),
    mes  = (data.getMonth()+1).toString().padStart(2, '0'), //+1 pois no getMonth Janeiro começa com zero.
    ano  = data.getFullYear();
  
    return dia+"/"+mes+"/"+ano;
}

function abrirDetalhesImportacaoFollowUp($value){
    var $id = $value.data("id");
    var $title = $value.data("title");

    var title = 'Detalhes do Importação Follow Up ' + $title;
    esconderPopoverTooltip();
    $.ajax({
        url: '{{ route('importacao.dados_complementares_follow_up.modal.detalhes') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: $id 
        },
        success: function (data){
            createModal('detalhes_projeto', title, data, 'modal-lg');
        }
    });
}



function createBtView($value, $title){

    html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-id=\""+$value.id+"\" data-title=\""+$title+"\" title='Visualizar' onclick=\"abrirProjeto($(this))\">"+$value.proforma+"\</a>";

    return html;
}

function abrirProjeto($value){
    var $id = $value.data("id");
    var $title = $value.data("title");
    var title = 'Detalhes do Importação ' + $title;
    esconderPopoverTooltip();
    $.ajax({
        url: '{{ route('importacao.modal.detalhes') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: $id 
        },
        success: function (data){
            createModal('detalhes_projeto', title, data, 'modal-lg');
        }
    });
}
@endsection