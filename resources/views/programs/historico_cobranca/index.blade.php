@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-sm-3">
                <div class="input-group">
                    {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control input-label', 'placeholder' => "Cliente", 'maxlength' => "250"]) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="input-group">
                    {{ Form::text('data_inicio', $hoje, ['id' => 'data_inicio', 'class' => 'form-control', 'placeholder' => "Data Inícial" ]) }}
                </div>
            </div>
            <div class="col-sm-2">
                <div class="input-group">
                    {{ Form::text('data_fim', $hoje, ['id' => 'data_fim', 'class' => 'form-control', 'placeholder' => "Data Final" ]) }}
                </div>
            </div>
        </div>
        <div class="row">
            @if ($check_gerentes === true || $check_vendedor_representante === true)
            @if($check_gerentes === true)
            <div class="form-group col-lg-2">
                {{ Form::select('gerentes', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
            </div>
            @endif
            @if($check_vendedor_representante === true)
            <div class="form-group col-lg-2">
                {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
            </div>
            @endif
            @endif
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
    <div id='saldos' class="border rounded mt-2 d-none pl-3 p-2">
        <b>Saldos</b><br>  
        <span id="totais"></span> 
    </div>
    <table class="table table-striped table-not-view" id="table-filters-historico">
        <thead>
            <tr>
                <th rowspan='2'>Retorno</th>
                <th class='number_format' rowspan='2'>Clientes</th>
                <th colspan=3>Valores</th>
            </tr>
            <tr>
                <th class='number_format'>Cobrado</th>
                <th class='number_format'>Recuperado</th>
                <th class='number_format'>% de sucesso</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td><b>Totais:</b></td>
                <td id='total-clientes'></td>
                <td id='total-cobrado'></td>
                <td id='total-recuperado'></td>
                <td id='total-sucesso'></th>
            </tr>
        </tfoot>
    </table>
</div>
    
@endsection

@section('script-footer')

    $(document).ready(function(){
        
        $(document).find("#cliente").autocomplete(optionsAutoCompleteCliente('cliente'));
        
        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });

        datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        };

        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });

        $('#data_fim').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') > e.date){
                $('#data_inicio').val('');
            }
            $('#data_inicio').datepicker('setEndDate', e.date);
        });

        $(document).find("#data_inicio").datepicker(datepicker_options);
        $(document).find("#data_inicio").mask("00/00/0000");
        
        $(document).find("#data_fim").datepicker(datepicker_options);
        $(document).find("#data_fim").mask("00/00/0000");

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $(document).find("#btn-clearform").on('click', function(){
            table_filters_historico.clear().draw();
            $(document).find('#total-clientes').html('');
            $(document).find('#total-cobrado').html('');
            $(document).find('#total-recuperado').html('');
            $(document).find('#total-sucesso').html('');
        });

        $(document).find(".busca_left").on('change', function(event){
            var campos = $(document).find("select:visible");
            var indice = campos.index(event.target) + 1;
            var seletor = $(campos[indice]);
            if($(this).val() !== ''){
                checkDadosUser(seletor, $(this).val());
            }
        });
    
    });

    table_filters_historico = $('#table-filters-historico').DataTable({
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
                'targets': 0,
                'width': '60%'
            },
            {
                'targets': 'number_format',
                "className": 'number_format',
                'width': '10%'
            },
        ]
    });


    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}'
            },
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosClienteBusca($(this), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteBusca($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }
    function filterAjax(data_form){
        $("#form_filter").find('.error-message').remove();
        $("#form_filter").find('.error-input').removeClass('error-input');
        $.ajax({
            url: "{{ route('historico_cobranca.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    table_filters_historico.clear().draw();
                    if(callback.response.linhas.length > 0){
                        var data = callback.response.linhas;
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].motivo,
                                createLinkClientes(data[field].clientes, data[field].filtro, data[field].motivo),
                                data[field].valor_cobrado,
                                data[field].valor_recuperado,
                                data[field].sucesso,
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters_historico.columns.adjust().rows.add(fields_filter).draw();

                        $(document).find('#total-clientes').html('<b>'+createLinkClientes(callback.response.totais.clientes, callback.response.totais.filtro, 'Total')+'</b>');
                        $(document).find('#total-cobrado').html('<b>'+callback.response.totais.valor_cobrado+'</b>');
                        $(document).find('#total-recuperado').html('<b>'+callback.response.totais.valor_recuperado+'</b>');
                        $(document).find('#total-sucesso').html('<b>'+callback.response.totais.sucesso+'</b>');
                    }
                }
            },
            error: function(data){
                var form = $("#form_filter");
                var errors = data.responseJSON.error;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
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
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
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

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        if(input == 'cliente'){
            $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        }
        else{
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        }
        $input.addClass('error-input');
    }

    @if ($check_gerentes === true || $check_vendedor_representante === true)
    function checkDadosUser(campo_busca, valor){
        
        primeira_opcao = $(campo_busca).find("option:first").html();
        $(campo_busca).html("");
        var campos = "<option value=\"\">" + primeira_opcao + "</option>";
        $.ajax({
            url: "{{ route('usuario.dados_subordinados') }}",
            dataType: 'json',
            data: {_token:'{{ csrf_token() }}', user: valor},
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    var response = callback.response;
                    for(var line in response){
                        campos += "<option value=\""+response[line].id+"\">"+response[line].name+"</option>";
                    }
                }
            },
            error: function(data){
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        }).done(function(){
            $(campo_busca).html(campos).focus();
        });
    }
    @endif

    function createLinkClientes($clientes, $filtro, $retorno){
        var html = '<a href="#" onclick="modalClientes(\''+$filtro+'\', \''+$retorno+'\')">'+$clientes+'</a>';
     
        return html;
    }
    
    function modalClientes($filtro, $retorno){
        $.ajax({
            url: "{{ route('historico_cobranca.modal.index') }}",
            data: {_token:'{{ csrf_token() }}', filtro: $filtro},
            method: 'POST',
            success: function(callback){
                createModal('modal-clientes', 'Histórico do período: ' + $retorno, callback, 'modal-lg');
            },
            error: function (callback){
                message('Erro!', callback.message, '');
            }
        });
    }
@endsection