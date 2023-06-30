@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-1">
            <input type="text" name="num_projeto" id="num_projeto" value="" placeholder="NÚMERO PROJETO" maxlength="40"/>
        </div>
        <div class="col-lg-2">
            <input type="text" name="nome_projeto" id="nome_projeto" value="" placeholder="NOME PROJETO" maxlength="40"/>
        </div>
        <div class="col-lg-3">
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('nome_cliente', '', ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => 'CLIENTE']) }}
                {{ Form::hidden('codigo_cliente', '', ['id' => 'codigo_cliente', 'class' => '']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-1">
            <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
        </div>
        <div class="col-lg-1">
            <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
        </div>
        @if (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16)
        <div class="form-group col-lg-2">
            {{ Form::select('representantes', $representantes, '', ['class' => 'form-control', 'placeholder' => 'Todos'])}}
        </div>
        @endif
        <div class="form-group col-lg-2">
            <div>
                {{ Form::select('estados', $estados, 1, ['class' => 'form-control', 'placeholder' => 'Todos'])}}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create-projeto" class="btn-create">Adicionar</button>

    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th class="tb_number">Núm. Projeto</th>
                <th>Projeto</th>
                <th>Cliente</th>
                <th class="sort-date">Data</th>
                <th class="tb_number">Val. Total do Ped.</th>
                <th>Status</th>
                @if (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16)
                <th>Vendedor</th>
                @endif
                <th class="td_acao"></th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    filterAjax();
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        endDate: new Date(),
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


    $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());
            
    $(document).find("#bt-search-cliente").on('click', function(event){
        event.stopPropagation();
        showModalClienteBuscaDuplicar($(this).data("route"));
        return false;
    });
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });
    $(document).find("#btn-clearform").off("click");
    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });
    $(document).find("#btn-create-projeto").on("click", function(){
        showModalCreateProjeto();
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: "100px" },
            { "class": "tb_date", targets: "sort-date" },
            { "class": "td_acao", targets: "td_acao", "orderable": false}
        ],
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
});

function showModalCreateProjeto(){
    $.ajax({
        url: '{{ route('lancamento_projeto.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_grupo_edit_delete', title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
            var dados = callback.responseJSON;
        }
    });
}

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('lancamento_projeto.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            for (var fields in data.response){
                if(data.response[fields].status_codigo == 0 || data.response[fields].status_codigo == 1 || data.response[fields].status_codigo == 9){
                    temp_array = [
                        data.response[fields].num_projeto,
                        ajusteTamanhoTable(data.response[fields].nome_projeto),
                        ajusteTamanhoTable(data.response[fields].cliente),
                        data.response[fields].data,
                        data.response[fields].valor_total_pedido,
                        data.response[fields].status,
                        @if (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16)
                        ajusteTamanhoTable(data.response[fields].vendedor),
                        @endif
                        createBtDuplicarProjeto(data.response[fields].id),
                        '',
                        createBtnEdit("{{ route('lancamento_projeto.modal.editar') }}", data.response[fields].id),
                        createBtnDelete("{{ route('lancamento_projeto.modal.deletar') }}", data.response[fields].id)
                    ];
                }else{
                    temp_array = [
                        data.response[fields].num_projeto,
                        ajusteTamanhoTable(data.response[fields].nome_projeto),
                        ajusteTamanhoTable(data.response[fields].cliente),
                        data.response[fields].data,
                        data.response[fields].valor_total_pedido,
                        data.response[fields].status,
                        @if (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16)
                        ajusteTamanhoTable(data.response[fields].vendedor),
                        @endif
                        createBtDuplicarProjeto(data.response[fields].id),
                        createBtView(data.response[fields].num_projeto),
                        '',
                        ''
                    ];
                }
                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function createBtnEdit($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
    return $html;
}
function createBtnDelete($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
    return $html;
}

function createBtDuplicarProjeto($this){
    var html = '';
    html = "<a href=\"#\" class=\"bt-duplicar\" data-toggle='tooltip' data-html='true' title='Duplicar' onclick=\"duplicar_projeto('"+$this+"')\"></a>";
    return html;
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
            createModal('modal_grupo_edit_delete', title, body, modal_class);
            var modal = $("#modal_grupo_edit_delete");
        }
    });
}

function duplicar_projeto($this){
    var $id = $this;
    $.ajax({
        url: "{{ route('lancamento_projeto.duplicar') }}",
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id: $id},
        success: function(body){
            createModal('modal_duplicar', "Duplicar Projeto", body, "");
            var modal = $("#modal_duplicar");
        }
    });
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
            $(document).find("#codigo_cliente").val(ui.item.cpf_cnpj);
            $(document).find("#nome_cliente").val(ui.item.label);
            return false;
        }
    };
}
function showModalClienteBuscaDuplicar(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: '{{csrf_token()}}'
        },
        success: function(body){
            $(document).find('#cliente_searsh_duplicar_show').remove();
            createModal("cliente_searsh_duplicar_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_duplicar_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosClienteBuscaDuplicar($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBuscaDuplicar($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#codigo_cliente").val($dados.find("td").eq(3).text());
    $(document).find("#nome_cliente").val($dados.find("td").eq(1).text()+' - '+$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
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

function createBtView($this){

    html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' title='Visualizar' onclick=\"abrirProjeto('"+$this+"')\"></a>"

    return html;
}

function abrirProjeto($id){
    $.ajax({
        url: '{{ route('lancamento_projeto.modal.view') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_projeto: $id 
        },
        success: function (data){
            createModal('detalhes', 'Detalhes do Projeto', data, 'modal-lg');
        }
    });
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}
@endsection