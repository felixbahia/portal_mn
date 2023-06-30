@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="nome_cliente" id="nome_cliente" value="" placeholder="Nome / Razão Social" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
        </div>
    </form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-aprovacao-usuario-cliente">
        <thead>

            <tr>
                <th>Cliente</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th class='date_format'>Solicitado</th>
                <th>Aprovar</th>
                <th>Reprovar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {

        $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());

        $("#btn-filterform").on("click", function(){        
            filterAjax($("#form_filter").serialize());
        });

        table_filters.on('draw', function () {
        });

		$(document).find("#bt-search-cliente-busca").off("click");
		$(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
			showModalCliente($(this).data("route"));
            return false;
        });

        $(document).find(".btn-clear").on("click", function(){
            $form = $(this).parents('form');
            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input').not('[class^=btn-]').not('[name=_token]').val('');
                    $form.find('select').each(function(){
                        $(this).val($(this).find('option').eq(0).val());
                    });
                }
            });
        });
        
    });

    table_filters = $('#table-filters-aprovacao-usuario-cliente').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
            }        },
        "columnDefs": [
            {
                "targets": [-1, -2],
                "orderable": false,
            },
            {
                "targets": 'date_format',
                "className": 'date_format'
            }
        ],
        "order": [[ 3, 'desc' ]]
    });

    function showErrorsInputs(form, input, message){
        if (input == 'estabelecimento' || input == 'estado'){
            var $input = $(form).find("select[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

    function filterAjax(data_form){
        var $return;
        var form = $("#form_filter");
        table_filters.clear().draw();

        form.find('.error-message').remove();
        
        $.ajax({
            url: "{{ route('usuario_cliente.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){

                var data = data.response.resultado;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].cliente,
                            data[field].email,
                            data[field].telefone,
                            data[field].data_solicitacao,
                            createBtAprove(data[field]),
                            createBtReprove(data[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    
                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });
                }
            },
            error: function(data){
                var errors = data.responseJSON.errors;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function createBtAprove(obj){

        var html = "<div class=\"bt-aprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Aprovar\" onclick=\"aprovarUsuario('"+obj.id+"')\"></div>";

        return html;
    }

    function createBtReprove(obj){

        var html = "<div class=\"bt-reprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Reprovar\" onclick=\"reprovarUsuario('"+obj.id+"')\"></div>";

        return html;
    }

    function aprovarUsuario($id){
        $.ajax({
            url: '{{ route("usuario_cliente.aprovar")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    filterAjax($("#form_filter").serialize());
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;

                    if(data.message.length > 0){
                        var resposta = data.message;
                    }
                    else{
                        var resposta = 'Houve uma instabilidade, tente novamente mais tarde!';
                    }
                    filterAjax($("#form_filter").serialize());
                    message('Atenção', resposta);
                }
            }
        });
    }

    function reprovarUsuario($id){
        $.ajax({
            url: '{{ route("usuario_cliente.reprovar")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    filterAjax($("#form_filter").serialize());
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', 'Ouve uma instabilidade, tente novamente mais tarde!');
                }
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
                $(document).find("#nome_cliente").val(ui.item.label);
                $.ajax({
                    url: '{{ route('cliente.salvaClientePadrao') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        codcad: ui.item.value
                    }
                });
                return false;
            }
        };
    }

    function showModalCliente(url){
		var title = "Busca de Clientes";
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
        $(document).find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        $.ajax({
            url: '{{ route('cliente.salvaClientePadrao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                codcad: $dados.find("td").eq(0).text()
            }
        });
    }

@endsection