@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                <div class="input-group">
                    {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'placeholder' => 'Código Produto', 'class' => 'form-control input-label']) !!}
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {!! Form::text('descricao_produto', '', ['id' => 'descricao_produto', 'placeholder' => 'Nome Produto']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('marca_produto', '', ['id' => 'marca_produto', 'placeholder' => 'Marca']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('linha_produto', '', ['id' => 'linha_produto', 'placeholder' => 'Linha']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('grupo_produto', '', ['id' => 'grupo_produto', 'placeholder' => 'Grupo']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('subgrupo_produto', '', ['id' => 'subgrupo_produto', 'placeholder' => 'Subgrupo']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'placeholder' => 'Todos Estabelecimentos']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_de', $data_inicial, ['id' => 'data_de', 'class' => 'data', 'placeholder' => 'Data De DD/MM/AAAA', 'maxlenght' => '20']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_ate', $data_final, ['id' => 'data_ate', 'class' => 'data', 'placeholder' => 'Data Até DD/MM/AAAA', 'maxlenght' => '20']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::select('motivo', $motivos, '', ['id' => 'motivo', 'placeholder' => 'Todos Motivos']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::select('tipo', $tipos_ajuste, '', ['id' => 'tipo']) !!}
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('ajuste', 'ok', true,  ['id' => 'ajuste', 'class' => 'form-check-input']) }}
                    {{ Form::label('ajuste', 'Ajuste Estoque', ['class' => 'form-check-label','for' => 'ajuste_estoque']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('transferencia', 'ok', false,  ['id' => 'transferencia', 'class' => 'form-check-input']) }}
                    {{ Form::label('transferencia', 'Transferência', ['class' => 'form-check-label','for' => 'ajuste_estoque']) }}
                </div>
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')<div class="content-table notas-importadas">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-ajuste-estoque-index">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento<br></th>
                <th colspan="3">Quantidade</th>
                <th colspan="3">Valor</th>
            </tr>
            <tr>
                <th class="compras-col tb_number">Positivo</th>
                <th class="compras-col tb_number">Negativo</th>
                <th class="compras-col tb_number">Total</th>
                <th class="lancadas-col tb_number">Positivo</th>
                <th class="lancadas-col tb_number">Negativo</th>
                <th class="lancadas-col tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tfoot>    
    </table>
@endsection
@section('script-footer')
$(document).ready( function () {
    form = $(document).find("#form_filter");

    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 100,
        autoHide: true
    });
    $('#data_de').on('pick.datepicker', function (e) {
        if($('#data_ate').datepicker('getDate') < e.date){
            $('#data_ate').val('');
        }
        $('#data_ate').datepicker('setStartDate', e.date);
        $('#data_ate').datepicker('update');
    });

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    table_filters = $('#table-filters-ajuste-estoque-index').DataTable({
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
            { 
                "class": "compras-col tb_number", 
                targets: "compras-col tb_number"
            },
            { 
                "class": "lancadas-col tb_number", 
                targets: "lancadas-col tb_number"
            }
        ],
    });

    $(document).find("#marca_produto").autocomplete(optionsAutoCompleteMarca());
    $(document).find("#linha_produto").autocomplete(optionsAutoCompleteLinha());
    $(document).find("#grupo_produto").autocomplete(optionsAutoCompleteGrupo());
    $(document).find("#subgrupo_produto").autocomplete(optionsAutoCompleteSubgrupo());
    $(document).find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));

    form.find("#bt-search-produto").off('click');
    form.find("#bt-search-produto").on('click', function(){
        showModalProduto(form);
    });
});

function filterAjax(){
    $(table_filters.column(0).footer()).html('');
    $(table_filters.column(1).footer()).html('');
    $(table_filters.column(2).footer()).html('');
    $(table_filters.column(3).footer()).html('');
    $(table_filters.column(4).footer()).html('');
    $(table_filters.column(5).footer()).html('');
    $(table_filters.column(6).footer()).html('');
    filterClear();
    form = $(document).find("#form_filter");
    limparMesagemErro(form);
    data_form = form.serialize();
    $.ajax({
        url: '{{ route('produto.estoque.ajuste_estoque.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){  
            linhas = [];
            for (var fields in data.response.ajuste_estoque){
                temp_array = [
                    data.response.ajuste_estoque[fields].estabelecimento,
                    data.response.ajuste_estoque[fields].positivo,
                    data.response.ajuste_estoque[fields].negativo,
                    linkDialogAjusteEstoque(data.response.ajuste_estoque[fields], data.response.filtro),
                    data.response.ajuste_estoque[fields].valor_positivo,
                    data.response.ajuste_estoque[fields].valor_negativo,
                    linkDialogAjusteEstoqueValor(data.response.ajuste_estoque[fields],data.response.filtro),
                    '',
                    ''
                ];

                $(table_filters.column(0).footer()).html('Total');
                $(table_filters.column(1).footer()).html(data.response.total.quantidade_positiva);
                $(table_filters.column(2).footer()).html(data.response.total.quantidade_negativa);
                $(table_filters.column(3).footer()).html(linkDialogAjusteEstoqueTotal(data.response.total,data.response.filtro));
                $(table_filters.column(4).footer()).html(data.response.total.valor_positivo);
                $(table_filters.column(5).footer()).html(data.response.total.valor_negativo);
                $(table_filters.column(6).footer()).html(linkDialogAjusteEstoqueValorTotal(data.response.total,data.response.filtro));

                table_filters.row.add(temp_array).draw();
            }
        },
        error: function(data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function linkDialogAjusteEstoque($value, $filtro){
    html = "<i data-url=\"\"  data-title=\""+$value.estabelecimento+"\" data-filtro=\""+$filtro+"\" data-codigo_estabelecimento=\""+$value.codigo_estabelecimento+"\" class='bt-view' onclick=\"dialogAjusteMotivoQuantidade($(this));\" data-toggle='tooltip' data-placement='left' title='Visualizar por Motivo'></i><a href=\"#\" data-url=\"\"  data-title=\""+$value.estabelecimento+"\" data-filtro=\""+$filtro+"\" data-codigo_estabelecimento=\""+$value.codigo_estabelecimento+"\" onclick=\"dialogAjusteEstoque($(this));\">"+$value.total+"</a>"

    return html;
}

function linkDialogAjusteEstoqueTotal($value, $filtro){
    html = "<i data-url=\"\"  data-title=\""+$value.estabelecimento+"\" data-filtro=\""+$filtro+"\" data-codigo_estabelecimento='' class='bt-view' onclick=\"dialogAjusteMotivoQuantidade($(this));\" data-toggle='tooltip' data-placement='left' title='Visualizar por Motivo'></i><a href=\"#\" data-url=\"\" data-title='Total Quantidade por Grupo' data-filtro=\""+$filtro+"\" data-codigo_estabelecimento='' onclick=\"dialogAjusteEstoque($(this));\">"+$value.total_quantidade+"</a>"

    return html;
}

function linkDialogAjusteEstoqueValor($value, $filtro){
    html = "<i data-url=\"\"  data-title=\""+$value.estabelecimento+"\" data-filtro=\""+$filtro+"\" data-codigo_estabelecimento=\""+$value.codigo_estabelecimento+"\" class='bt-view' onclick=\"dialogAjusteMotivoValor($(this));\" class='bt-view' data-toggle='tooltip' data-placement='left' title='Visualizar por Motivo'></i><a href=\"#\" data-url=\"\" data-title=\""+$value.estabelecimento+"\" data-filtro=\""+$filtro+"\" data-codigo_estabelecimento=\""+$value.codigo_estabelecimento+"\" onclick=\"dialogAjusteEstoqueValor($(this));\">"+$value.valor_total+"</a>"

    return html;
}

function linkDialogAjusteEstoqueValorTotal($value, $filtro){
    html = "<i data-url=\"\"  data-title=\""+$value.estabelecimento+"\" data-filtro=\""+$filtro+"\" data-codigo_estabelecimento='' class='bt-view' onclick=\"dialogAjusteMotivoValor($(this));\" class='bt-view' data-toggle='tooltip' data-placement='left' title='Visualizar por Motivo'></i><a href=\"#\" data-url=\"\" data-title='Total Valor por Grupo' data-filtro=\""+$filtro+"\" data-codigo_estabelecimento='' onclick=\"dialogAjusteEstoqueValor($(this));\">"+$value.total_valor+"</a>"

    return html;
}

function dialogAjusteEstoque($this){
    var title = "Detalhes Rastreabilidade " + $this.data("title");
    var codigo_estabelecimento = $this.data("codigo_estabelecimento");
    var filtro = $this.data("filtro");
    $.ajax({
        url: "{{ route('produto.estoque.ajuste_estoque.dialog') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            codigo_estabelecimento : codigo_estabelecimento,
            filtro : filtro
        },
        success: function(body){
            createModal('modal_dialog', title, body, "modal-lg");
            var modal = $("#modal_dialog");
        }
    });
}

function dialogRastreabilidade($this){
    var title = "Detalhes Ajuste Estoque " + $this.data("title");
    var codigo_estabelecimento = $this.data("codigo_estabelecimento");
    var filtro = $this.data("filtro");
    $.ajax({
        url: "{{ route('produto.estoque.ajuste_estoque.motivo_rastreabilidade') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            codigo_estabelecimento : codigo_estabelecimento,
            filtro : filtro
        },
        success: function(body){
            createModal('modal_dialog_rastreabilidade', title, body, "modal-lg");
        }
    });
}

function dialogAjusteEstoqueValor($this){
    var title = "Detalhes Ajuste Estoque " + $this.data("title");
    var codigo_estabelecimento = $this.data("codigo_estabelecimento");
    var filtro = $this.data("filtro");
    $.ajax({
        url: "{{ route('produto.estoque.ajuste_estoque.dialog_valor') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            codigo_estabelecimento : codigo_estabelecimento,
            filtro : filtro
        },
        success: function(body){
            createModal('modal_dialog', title, body, "modal-lg");
            var modal = $("#modal_dialog");
        }
    });
}

function dialogAjusteMotivoQuantidade($this){
    var title = "Detalhes Ajuste Estoque " + $this.data("title");
    var codigo_estabelecimento = $this.data("codigo_estabelecimento");
    var filtro = $this.data("filtro");
    $.ajax({
        url: "{{ route('produto.estoque.ajuste_estoque.motivo_quantidade') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            codigo_estabelecimento : codigo_estabelecimento,
            filtro : filtro
        },
        success: function(body){
            createModal('modal_dialog', title, body, "modal-lg");
            var modal = $("#modal_dialog");
        }
    });
}

function dialogAjusteMotivoValor($this){
    var title = "Detalhes Ajuste Estoque " + $this.data("title");
    var codigo_estabelecimento = $this.data("codigo_estabelecimento");
    var filtro = $this.data("filtro");
    $.ajax({
        url: "{{ route('produto.estoque.ajuste_estoque.motivo_valor') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            codigo_estabelecimento : codigo_estabelecimento,
            filtro : filtro
        },
        success: function(body){
            createModal('modal_dialog', title, body, "modal-lg");
            var modal = $("#modal_dialog");
        }
    });
}

function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');

    var $select = $(form).find("select[name='"+input+"']");
    $select.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $select.addClass('error-input');
}

function limparMesagemErro(form){   
    form.find('.error-message').remove();
    form.find('input, select, span').removeClass('error-input');
}

function optionsAutoCompleteMarca(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.marca.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
                table_filters.draw();
            }, 100);
        }
    };
}

function optionsAutoCompleteLinha(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.linha.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
                table_filters.draw();
            }, 100);
        }
    };
}

function optionsAutoCompleteGrupo(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.grupo.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
                table_filters.draw();
            }, 100);
        }
    };
}

function optionsAutoCompleteSubgrupo(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
                table_filters.draw();
            }, 100);
        }
    };
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
                table_filters.draw();
            }, 100);
        }
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
    form.find('#descricao_produto').val($dados.find("td").eq(2).text());
}
@endsection