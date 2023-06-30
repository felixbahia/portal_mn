@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class='form-row'>
            <div class="col-lg-2">
            {!! Form::text('data_inicio', '', ['id' => 'data_inicio', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Previsão Inicial']) !!}
        </div>
        <div class="col-lg-2">
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
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th class="tb_number">Grupo</th>
                <th class="tb_number">PCMN</th>
                <th>Proforma</th>
                <th class="tb_number">Quantidade Comprada</th>
                <th class="tb_number">Quantidade Recebida</th>
                <th>unidade</th>
                <th class="tb_date">Data Compra</th>
                <th class="tb_date">Data Previsão</th>
                <th class="tb_number">Dias</th>
                <th class="tb_number">Fob USD</th>
                <th class="tb_number">Custo USD</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    table_filters.on('draw', function () {
        $(document).find(".bt-edit").off("click");
        $(document).find(".bt-edit").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".bt-delete").off("click");
        $(document).find(".bt-delete").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });

    table_filters.destroy();
    table_filters = $('#table-filters').DataTable({
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
});

function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('importacao.consulta_compra_produto')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].grupo,
                    createLinkPedido( data.response[fields],data_form),
                    createBtView(data.response[fields], "PCMN: "+data.response[fields].pcmn + " Proforma: " + data.response[fields].proforma + " Fornecedor: " + data.response[fields].fornecedor),
                    data.response[fields].quantidade_comprada,
                    data.response[fields].quantidade_recebida,
                    data.response[fields].unidade,
                    data.response[fields].data_compra,
                    data.response[fields].data_recebimento,
                    data.response[fields].dias,
                    data.response[fields].fob_usd,
                    data.response[fields].custo_usd,
                ];
                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

        }
    });
}

function filterClear(){
    table_filters.clear().draw();
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