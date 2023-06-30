@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    {!! Form::hidden('export_excel', '', ['id' => 'export_excel']) !!}
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
        </div>
        <div class="col-lg-1">
            <div class="input-group">
                {{ Form::text('grupos', '', ['id' => 'grupos', 'class' => 'form-control', 'placeholder' => 'Grupos']) }}
            </div>
        </div>
        <div class="col-lg-1">
            <input type="text" class="" name="pcmn" id="pcmn" placeholder="Pedido" maxlength="250">
        </div>
        @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272]))
        <div class="col-lg-1">
            <input type="text" class="" name="proforma" id="proforma" placeholder="Proforma" maxlength="250">
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-1">
            <div class="input-group">
                {{ Form::text('porcentagem', '', ['id' => 'porcentagem', 'class' => 'form-control', 'placeholder' => '% Maior que']) }}
            </div>
        </div>
        @endif
        <div class="col-lg-1">
            <input type="text" class="data-mes data_month" name="data" id="data" placeholder="Data MM/AAAA" maxlength="20">
        </div>
        <div class="col-lg-2">
            <label><input type="checkbox" name="proforma_chk" id="proforma_chk" value="sim" />Somente Importados</label>
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
        <table class="table table-striped"  id="table-filters">
            <thead>
                <tr>
                    <th>Estabel</th>
                    <th>Pedido</th>
                    @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272]))
                    <th>Proforma</th>
                    <th>Fornecedor</th>
                    @endif
                    <th class="sort-date">Data<br>Recebimento</th>
                    <th>Grupo</th>
                    <th class="tb_number">Fob<br>Comp. Dolar</th>
                    <th class="tb_number">Preço<br>Ven. Dolar</th>
                    <th class="tb_number">Qtde<br>Compr.</th>
                    <th class="tb_number">Qtde<br>Vend.</th>
                    <th class="tb_number">%<br>Compra/Venda</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
@section('script-footer')
$(document).ready( function () {
    
    $("#grupos").autocomplete(optionsAutoComplete("grupo"));

    $('.data_month').mask('00/0000');
    $('.data_month').datepicker({
        language: 'pt-BR',
        format: 'mm/yyyy',
        zIndex: 100,
        autoHide: true
    });

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });
    form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });

    table_filters.destroy();
    table_filters = $('#table-filters').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column >= 6 && column < 10){
                                if(data != ''){
                                    numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
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
            { "class": "tb_date", targets: "sort-date" }
        ],
    });

});


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
                filterAjax($("#form_filter"));
            }, 100);
        }
    };
}

function filterAjax($form){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('pedidos_compras.pedidos_abertos.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            table_filters.clear().draw();
            produtos = [];

            if(data.length <= 0){
                return;
            }
            
            for (var fields in data.response.retorno){
                temp_array = [
                    data.response.retorno[fields].unidade,
                    createLinkPedido(data.response.retorno[fields], data_form),
                    @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272]))
                    data.response.retorno[fields].proforma,
                    createRepresentante(data.response.retorno[fields].fornecedor),
                    createLinkModificarData(data.response.retorno[fields], data_form),
                    @else
                    data.response.retorno[fields].data_recebimento,
                    @endif
                    data.response.retorno[fields].grupo,
                    data.response.retorno[fields].preco_compra,
                    data.response.retorno[fields].preco_dolar,
                    data.response.retorno[fields].qtde_comprada,
                    data.response.retorno[fields].qtde_vendida,
                    data.response.retorno[fields].porcetagem
                ];
                produtos.push(temp_array)
            }
            form.find("#export_excel").val(data.response.export);
            table_filters.rows.add(produtos).draw();

        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function createRepresentante($this){
    var representante = "";
 
    representante = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + $this + "'>" + $this + "</div></div>";

    return representante;
}

function createLinkPedido($this, $form){
    var html = "";
    if($this.id !== ""){
        html = "<a href='#' onclick=\"showComissaoDetalhes('" 
        + $this.id + "', '"
        + $this.unidade + "', '"
        + $(document).find("#form_filter").find('#data').val() + "" 
        + "')\">" + $this.pcmn + "</a>";
    }
    return html;
}

function createLinkModificarData($this, $form){
    var html = "";
    if($this.id !== ""){
        html = "<a href='#' onclick=\"showModalData('" 
        + $this.id + "', '"
        + $this.unidade + "', '"
        + $(document).find("#form_filter").find('#data').val() + "" 
        + "')\">" + $this.data_recebimento + "</a>";
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

@if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || in_array(Auth::id(), [230, 731, 11410]))
function showModalData(id, unidade, data){
    $.ajax({
        url: '{{ route('pedidos_compras.pedidos_abertos.modal')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: id,
            unidade: unidade,
            data: data
        },
        success: function(body){
            createModal("alterar-data-modal", "Alterar data de previsão de recebimento", body, 'as-das-das');

            var tamanho = 240;
            $(document).find('#alterar-data-modal').find('.modal-dialog').css('max-height', tamanho+'px');
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
@endif

function showModalFornecedor(url, title){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFornecedor($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFornecedor($this){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_search_show").modal("hide");
    $(document).find("#fornecedor").val($this.find("td").eq(1).text());
}
@endsection