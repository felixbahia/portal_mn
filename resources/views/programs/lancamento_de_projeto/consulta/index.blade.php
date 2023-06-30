@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'placeholder' => 'Estabelecimento' ]) !!}
            </div>
            <div class="col-lg-1">
                <input type="text" name="num_projeto" id="num_projeto" value="" placeholder="NÚMERO PROJETO" maxlength="40"/>
            </div>
            @if(is_array($representantes))
            <div class="col-lg-2">
                <input type="text" name="nome_projeto" id="nome_projeto" value="" placeholder="NOME PROJETO" maxlength="40"/>
            </div>

            <div class="col-lg-3">
                {!! Form::select('representante', $representantes, '', ['id' => 'representante', 'placeholder'=> 'Todos Representantes']) !!}
            </div>

            <div class="col-lg-4">
                <div class="input-group">
                    {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'FACÇÃO']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                    {!! Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) !!}
                </div>
            </div>
            @else
            <div class="col-lg-4">
                <input type="text" name="nome_projeto" id="nome_projeto" value="" placeholder="NOME PROJETO" maxlength="40"/>
            </div>

            {!! Form::hidden('representante', $representantes, ['id' => 'representante']) !!}

            <div class="col-lg-5">
                <div class="input-group">
                    {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'FACÇÃO']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                    {!! Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) !!}
                </div>
            </div>
            @endif
        </div>
        <div class="row">
            <div class="col-sm-3">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                <input type="text" name="linha" id="linha" placeholder="LINHA" value="" maxlength="250">
            </div>
            <div class="col-lg-2">
                <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
            </div>
            <div class="col-lg-2">
                <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
            </div>
            <div class="col-lg-2">
                {!! Form::select('estado', $estados, '', ['id' => 'estado', 'placeholder'=> 'Todos os Status']) !!}
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="atrasado" id="atrasado" value="true" />
                    <label class="form-check-label" for="atrasado">Atrasado</label>
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

@section('content')
<div class="content-table">
    <table class="table table-striped table-filter table-not-edit" id="table-filter">
        <thead>
            <tr>
                <th class="tb_number">Num. Projeto</th>
                <th>Projeto</th>
                <th>Cliente</th>
                <th>Facção</th>
                <th>Linha</th>
                <th class="sort-date">Data Entrada</th>
                <th class="sort-date">Data Previsão Entrega</th>
                <th>Status</th>
                <th class="tb_acao"></th>
                <th class="tb_acao"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
$(document).ready( function () {
    form = $(document).find('#form_filter');
    form.find("#linha").autocomplete(optionsAutoCompleteLinha());
    form.find("#faccao").autocomplete(optionsAutoCompleteFaccao());
    form.find("#cliente").autocomplete(optionsAutoCompleteCliente());

    form.find("#faccao").off('change');
    form.find("#faccao").on('change', function(){
        if(form.find("#faccao").val() == ''){
            form.find("#codigo_faccao").val('');
        }
    });

    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalCliente($(this).data("route"));
        return false;
    });

    $(document).find("#bt-search-faccao-busca").on("click", function(){
        showModalFaccao($(this).data("route"), "Lista de Facções");
    });

    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 100,
        autoHide: true
    });
    $('#data_inicio').on('pick.datepicker', function (e) {
        if($('#data_fim').datepicker('getDate') < e.date){
            $('#data_fim').val('');
        }
        $('#data_fim').datepicker('setStartDate', e.date);
        $('#data_fim').datepicker('update');
    });

    table_filters = $('#table-filter').DataTable({
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
        "createdRow": function (row, data, dataIndex) {
            var status = data[7];
            var previsao_entrega = data[6].split("<");
            var partesData = previsao_entrega[0].split("/");
            var data_teste = new Date(partesData[2], partesData[1] - 1, partesData[0]);
            data_teste.setDate(data_teste.getDate() + 1);
            if(data_teste < new Date() && $(data[7]).text() !== "Finalizado" && $(data[7]).text() !== "CANCELADO"){
                $(row).addClass('error-tr');
            }
        },
        "columnDefs": [
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: "100px" },
            { "class": "tb_date", targets: "sort-date", width: "100px" }
        ],
    });

    $(document).find("#btn-filterform").off("click");
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    $(document).find("#btn-clearform").off("click");
    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });
});

function showModalFaccao(url, title){
    esconderPopoverTooltip();
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("faccao_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFaccao($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFaccao($this){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#faccao_search_show").modal("hide");
    $(document).find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    $(document).find("#codigo_faccao").val($this.find("td").eq(0).text());
}

function optionsAutoCompleteFaccao(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('faccao.autocomplete') }}", request, response);
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
            $(document).find("#faccao").val(ui.item.label);
            $(document).find("#codigo_faccao").val(ui.item.value);
            return false;
        }
    };
}

function filterClear(){
    table_filters.clear().draw();
}

function filterAjax(){
    esconderPopoverTooltip();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    limparMesagemErro(form);
    filterClear();
    $.ajax({
        url: '{{ route('projeto.consulta.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].num_projeto,
                    ajusteTamanhoTable(data.response[fields].nome_projeto),
                    ajusteTamanhoTable(data.response[fields].cliente),
                    ajusteTamanhoTable(data.response[fields].faccao),
                    data.response[fields].linha,
                    data.response[fields].data_entrada,
                    linkAlteracaoData(data.response[fields]),
                    linkAlteracaoStatus(data.response[fields]),
                    createBtView(data.response[fields]),
                    linkImpressaoResumoFaccao(data.response[fields]),
                ];

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

function createBtView($value){

    html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' data-num_projeto=\""+$value.num_projeto+"\" data-nome_projeto=\""+$value.nome_projeto+"\" data-estabelecimento=\""+$value.estabelecimento+"\" data-cliente=\""+$value.cliente+"\" title='Visualizar' onclick=\"abrirProjeto($(this))\"></a>";

    return html;
}

function abrirProjeto($value){
    var $id_projeto = $value.data("num_projeto");
    var $nome_projeto = $value.data("nome_projeto");
    var $estabelecimento = $value.data("estabelecimento");
    var $cliente = $value.data("cliente");
    var title = 'Detalhes do Projeto: '+$id_projeto+' - '+$nome_projeto+' - Estabelecimento: '+$estabelecimento+' - Cliente: '+$cliente;
    esconderPopoverTooltip();
    $.ajax({
        url: '{{ route('lancamento_projeto.modal.view') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_projeto: $id_projeto 
        },
        success: function (data){
            createModal('detalhes_projeto', title, data, 'modal-lg');
        }
    });
}

function linkAlteracaoData($value){
    html = $value.data_previsao_entrega;
    @if(in_array(Auth::id(), [80, 27, 465, 610, 17, 102, 452, 799, 751, 230, 8833, 83]) || Auth::user()->hasRole('Administradores'))
    html = html + "<a href=\"#\" class=\"bt-edit-direita\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Alteração da Data\" data-url=\"{{ route('lancamento_projeto.faccao.modal.editar_data') }}\" data-id_projeto=\""+$value.id_projeto+"\" data-id_faccao=\""+$value.id_faccao+"\" data-title=\"Data Entrada: "+$value.data_entrada+" - "+$value.num_projeto+" - "+$value.nome_projeto+" - "+$value.faccao+"\" onClick=\"modalAlteracaoData($(this))\"></a>";
    @endif
    return html;
}

function linkAlteracaoStatus($value){
    html = $value.status;
    @if(in_array(Auth::id(), [80, 27, 465, 610, 17, 102, 452, 799, 751, 230, 8833, 83]) || Auth::user()->hasRole('Administradores'))
        html = "<div>" + html + "<a href=\"#\" class=\"bt-edit-direita\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Alteração de Status\" data-url=\"{{ route('lancamento_projeto.modal.editar_status') }}\" data-id_projeto=\""+$value.id_projeto+"\" data-id_faccao=\""+$value.id_faccao+"\" data-title=\""+$value.num_projeto+" - "+$value.nome_projeto+" - "+$value.cliente+"\" onClick=\"modalAlteracaoStatus($(this))\"></a></div>";
    @endif
    return html;
}

function modalAlteracaoData($value){
    var url = $value.data("url");
    var $id_projeto = $value.data("id_projeto");
    var $id_faccao = $value.data("id_faccao");
    var title = $value.data("title");
    esconderPopoverTooltip();
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}", 
            id_projeto: $id_projeto,
            id_faccao: $id_faccao
        },
        success: function(body){
            createModal('modal_editar_data', title, body, "");
            var modal = $("#modal_editar_data");
        }
    });
}
function modalAlteracaoStatus($value){
    var url = $value.data("url");
    var $id_projeto = $value.data("id_projeto");
    var title = $value.data("title");
    esconderPopoverTooltip();
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}", 
            id_projeto: $id_projeto
        },
        success: function(body){
            createModal('modal_editar_data', title, body, "");
            var modal = $("#modal_editar_data");
        }
    });
}
function showErrorsInputs(form, input, message){
    if (input == 'faccao'){
        var $input = $(form).find("#bt-search-faccao-busca");
        $input.after("<label class='error-message' for='descricao'>"+message+"</label>");
        $(form).find("#faccao").addClass('error-input');
    }else if (input == 'cliente'){
        var $input = $(form).find("#bt-search-cliente-busca");
        $input.after("<label class='error-message' for='descricao'>"+message+"</label>");
        $(form).find("#cliente").addClass('error-input');
    }
    else{
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');

        var $select = $(form).find("select[name='"+input+"']");
        $select.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $select.addClass('error-input');
    }
}
function limparMesagemErro(form){   
    form.find('.error-message').remove();
    form.find('input, select, span').removeClass('error-input');
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

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function linkImpressaoResumoFaccao($value){
    if($value.impressao_resumo_compra === true){
        var html = "<a href=\"#\" class=\"btn-pedido\" title='Impressão Resumo Facção' data-id_projeto=\""+$value.id_projeto+"\" data-id_faccao=\""+$value.id_faccao+"\" onclick=\"imprimirResumoFaccao($(this))\"></a>";
    }else{
        var html = '';
    }
    
    return html;
}

function imprimirResumoFaccao($this){
    var id_projeto = $this.data("id_projeto");
    var id_faccao = $this.data("id_faccao");

    event.stopPropagation();
    var $form = document.createElement('form');
    $form.name = 'form_imprimir';
    $form.method = 'POST';
    $form.target = '_blanck';
    $form.action = '{{ route("lancamento_projeto.imprimir_resumo_faccao") }}';
    
    var $input_form = document.createElement('INPUT');
    $input_form.type = 'TEXT';
    $input_form.name = 'id_projeto';
    $input_form.value = id_projeto;
    $form.appendChild($input_form);

    var $input_form = document.createElement('INPUT');
    $input_form.type = 'TEXT';
    $input_form.name = 'id_faccao';
    $input_form.value = id_faccao;
    $form.appendChild($input_form);

    var $input_form = document.createElement('INPUT');
    $input_form.type = 'TEXT';
    $input_form.name = '_token';
    $input_form.value = '{{ csrf_token() }}';
    $form.appendChild($input_form);

    document.body.appendChild($form);
    $form.submit();
    $form.remove();
}

function optionsAutoCompleteCliente(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('clientes.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum cliente encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente").val(ui.item.label);
            return false;
        }
    }
}

function showModalCliente(url){
    var title = "Busca de Clientes";
    esconderPopoverTooltip();
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            $(document).find('#cliente_searsh_show').remove();
            createModal("cliente_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosCliente($(this), event);
                    });
                });
            });
        }
    });
}
function returnDadosCliente($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}

function esconderPopoverTooltip(){
    $('[data-toggle="tooltip"]').tooltip('hide');
    $('[data-toggle="popover"]').popover('hide');
}

@endsection