@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_cliente_black_list" id="form_cliente_black_list" onsubmit="return false">
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-sm-4 col-xl-3">
            <div class="input-group">
                {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar Black List</button>
    </div>
</form>

@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-black_list">
        <thead>
            <tr>
                <th>Nome Cliente</th>
                <th class="tb_number">CPF / CNPJ</th>
                <th class="tb_date">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
$(document).ready(function(){
    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente('cliente_nome'));

    $(document).find('#btn-filterform').on('click', function(event){
        event.stopPropagation();
        filterAjax();
    });
    
    $(document).find("#btn-create").off("click");
    $(document).find("#btn-create").on("click", function(){
        event.stopPropagation();
        showModalCreate();
    });

    table_black_list_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "autoWidth": false,
        "drawCallback": function(settings) {
            $(document).find('.detalhe_produto').tooltip({
                container: 'body',
                html: true,
                show: true,
                trigger: 'manual'
            });
        },
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum Cliente",
            "infoPostFix":    "",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum Cliente",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        },
        "columnDefs": [
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: '150px'},
            { "class": "tb_date", targets: "tb_date"}
        ]
    };
    table_black_list = '';
    table_black_list = $(document).find('#table-filters-black_list').DataTable(table_black_list_options);
    table_black_list.draw();
});

function filterAjax(){
    table_black_list.clear().draw();
    $.ajax({
        url: "{{ route('cliente_black_list.filtro') }}", 
        dataType: 'json',
        data: {
            _token: "{{ csrf_token() }}",
            cliente_nome: $(document).find('#cliente_nome').val()
        },
        method: 'POST',
        success: function(callback){
            dados = callback.response;
            table_black_list.clear().draw();
            if(dados.length > 0){
                var fields_filter = [];
                for(var field in dados){
                    var temp_field = [
                        dados[field].nome_razao,
                        dados[field].cpf_cnpj,
                        dados[field].status,
                        createBtnEdit(dados[field])
                    ];
                    fields_filter.push(temp_field);
                }
                table_black_list.rows.add(fields_filter).draw().nodes();
                
            }
        },
        error: function(callback){
            message('Atenção', 'Nenhum cliente localizado.');
        }
    });
}

function createBtnEdit($dados){
    var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"Editar\" onclick=\"showModalEdit($(this))\"></a>";
    return $html;
}
function optionsAutoCompleteCliente($elemento){
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
            $('.ui-autocomplete').css("z-index", $(document).find("#" + $elemento).parents('.modal').css('z-index') + 1);
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
            $(document).find("#" + $elemento).val(ui.item.label);
            return false;
        }
    };
}

function showModalEdit($this) {
    var $id = $($this).data("id");
    $.ajax({
        url: '{{ route('cliente_black_list.modal.editar') }}',
        data: {_token: '{{ csrf_token() }}', id: $id},
        method: 'POST',
        success: function(body){
            createModal("modal_cliente_editar_black_list", 'Editar Cliente Black List', body, '');
        },
        error: function(data){
            message('Alerta', data.responseJSON.error.msg.user);
        }
    });
}

function showModalCreate() {
    $.ajax({
        url: '{{ route('cliente_black_list.modal.adicionar') }}',
        data: {_token: '{{ csrf_token() }}'},
        method: 'POST',
        success: function(body){
            createModal("modal_cliente_novo_black_list", 'Adicionar Cliente Black List', body, '');
        },
        error: function(data){
            message('Alerta', data.responseJSON.error.msg.user);
        }
    });
}

@endsection