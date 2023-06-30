@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'placeholder' => 'Todos Estabelecimentos']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('peca_codigo', '', ['id' => 'peca_codigo', 'placeholder' => 'Peça']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::text('local_de_estoque', '', ['id' => 'local_de_estoque', 'placeholder' => 'Local de Estoque']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_de', '', ['id' => 'data_de', 'class' => 'data', 'placeholder' => 'Data De DD/MM/AAAA', 'maxlenght' => '20']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_ate', '', ['id' => 'data_ate', 'class' => 'data', 'placeholder' => 'Data Até DD/MM/AAAA', 'maxlenght' => '20']) !!}
            </div>
        </div>
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
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-produto-analise" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Código Produto</th>
                <th>Produto</th>
                <th>Peça</th>
                <th>Local de Estoque</th>
                <th class="tb_number">Quantidade Anterior</th>
                <th class="tb_number">Quantidade Ajuste</th>
                <th>Motivo</th>
                <th>Usuário</th>
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
    form = $(document).find("#form_filter");

    $("#btn-create").on("click", function(){
        showModalCreate();
    });

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

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

    $(document).find("#marca_produto").autocomplete(optionsAutoCompleteMarca());
    $(document).find("#linha_produto").autocomplete(optionsAutoCompleteLinha());
    $(document).find("#grupo_produto").autocomplete(optionsAutoCompleteGrupo());
    $(document).find("#subgrupo_produto").autocomplete(optionsAutoCompleteSubgrupo());
    $(document).find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));

    form.find("#bt-search-produto").off('click');
    form.find("#bt-search-produto").on('click', function(){
        showModalProduto(form);
    });

    form.find("#codigo_produto").off('change');
    form.find("#codigo_produto").on('change', function(){
        if(form.find("#codigo_produto").val() != ''){
            pesquisaProdutoCodigo(form);
        }
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: "200px" },
            { "class": "tb_date", targets: "tb_date"},
        ],
        "order": [[ 7, "asc" ], [ 0, "asc" ]]
    });
});

function showModalCreate(){
    $.ajax({
        url: '{{ route('produto.ajuste_estoque.digitacao.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = '{{ CustomView::programaName() }}';
            createModal('modal_digitacao_ajuste_estoque', title, body, 'modal-lg');
        }
    });
}

function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    limparMesagemErro(form);
    data_form = form.serialize();
    $.ajax({
        url: '{{ route('produto.ajuste_estoque.digitacao.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){  
            linhas = [];
            for (var fields in data.response.ajuste_estoque){
                temp_array = [
                    data.response.ajuste_estoque[fields].estabelecimento,
                    data.response.ajuste_estoque[fields].produto_codigo,
                    ajusteTamanhoTable(data.response.ajuste_estoque[fields].produto_descricao),
                    data.response.ajuste_estoque[fields].peca_codigo,
                    ajusteTamanhoTable(data.response.ajuste_estoque[fields].local_de_estoque),
                    data.response.ajuste_estoque[fields].quantidade_anterior,
                    data.response.ajuste_estoque[fields].quantidade_ajuste,
                    ajusteTamanhoTable(data.response.ajuste_estoque[fields].motivo),
                    ajusteTamanhoTable(data.response.ajuste_estoque[fields].usuario),
                    data.response.ajuste_estoque[fields].data,
                ];

                table_filters.row.add(temp_array).draw();
            }
        },
        error: function(data){

        }
    });
}

function filterClear(){
    table_filters.clear().draw();
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

function showModalProduto(form){
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

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function pesquisaProdutoCodigo(){
    form = $(document).find('#form_filter');
    codigo_produto = form.find('#codigo_produto').val();
    $.ajax({
        url: '{{ route('produto.pesquisaprodutocodigo')}}',
        data: {
            _token : "{{ csrf_token() }}",
            codigo_produto: codigo_produto
        },
        method: 'POST',
        async: false,
        success: function(callback){
            console.log(callback.response.descricao);
            form.find('#descricao_produto').val(callback.response.descricao);
        },
        error: function(callback){

        }
    });
}
@endsection