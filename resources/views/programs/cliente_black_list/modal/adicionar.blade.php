@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_black_list_add" id="form_black_list_add" onsubmit="return false;">
    @csrf
    {!! Form::hidden('titulos_adicional', $titulos_adicional, ['id' => 'titulos_adicional']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12 content-not-estabel">
            {{ Form::label('nome_cliente', 'Cliente') }}
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('nome_cliente', '', ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('motivo', 'Motivo') }}
            {{ Form::select('motivo', $motivos, '', ['id' => 'motivo', 'class' => 'form-control essencial input-label', 'placeholder' => 'Motivo']) }}
        </div>
    </div>
    <br>
    <div class="form-row">
        <div class="content-filter-dialog">	
            <p><strong>Adicionar Título</strong></p>
            <div class="form-row">
                <div class="form-group col-sm-12">
                    {{ Form::label('titulo', 'Titulo', []) }}
                    <div class="input-group" id="usuario_group">
                        {{ Form::text('titulo', '', ['id' => 'titulo', 'class' => 'form-control', 'placeholder' => 'Título', 'maxlength' => '250']) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-titulo">
                            <i class="bt-view m-2"></i>
                        </span>
                        <span class="input-group-addon border-right border-top border-bottom rounded-right btn-line-add-span">
                            <i class="btn-line-add rounded-right" id="btn-add_titulo"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped" id="table-titulos">
                        <thead>
                            <th>Título</th>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('observacao', 'Observação') }}
            {{ Form::text('observacao', '', ['id' => 'motivo', 'class' => 'form-control essencial input-label', 'placeholder' => 'Observação', 'maxlength' => '40']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_add = $(document).find('#form_black_list_add');
    
        form_modal_add.find("#titulo").autocomplete(optionsAutoCompleteTituloPago());

        form_modal_add.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });

        form_modal_add.find("#bt-search-titulo").off("click");
		form_modal_add.find("#bt-search-titulo").on("click", function(event){
            event.stopPropagation();
            showModalTituloPagoModalAdd();
            return false;
        });

        form_modal_add.find("#btn-add_titulo").off('click');
        form_modal_add.find("#btn-add_titulo").on('click', function(){
            adicionarTituloModalAdd(form_modal_add);
        });


        table_titulos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Título inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Título inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: "150px" },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                }
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_titulos = '';
        table_titulos = $(document).find('#table-titulos').DataTable(table_titulos_options);
        table_titulos.draw();

        form_modal_add.find("#bt-search-cliente").off("click");
        form_modal_add.find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"));
            return false;
        });

        form_modal_add.find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());
    });

    function inserirDados(data_form_modal_add){
        $.ajax({
            url: "{{ route('cliente_black_list.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_black_list_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_black_list_add");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function optionsAutoCompleteTituloPago(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.cliente_nome = form_modal_add.find("#nome_cliente").val();
                $.post("{{ route('cliente_black_list.autocomplete_titulo_pago') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_cliente_novo_black_list').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum título encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal_add.find("#titulo").val(ui.item.label);
                return false;
            }
        };
    }

    function showModalTituloPagoModalAdd(){
        var title = "Buscar Título";
        $.ajax({
            url: '{{ route('cliente_black_list.modal.buscar_titulo_pago') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                cliente_nome: form_modal_add.find("#nome_cliente").val(),
            },
            success: function(body){
                createModal("titulo_search_show", title, body, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosTituloPagoModalAdd($(this));
                    });
    
                });
            }
        });
    }

    function returnDadosTituloPagoModalAdd($this){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#titulo_search_show").modal("hide");
        $(document).find('#form_black_list_add').find("#titulo").val($this.find("td").eq(1).text());
    }

    function adicionarTituloModalAdd(form_modal_add){
        data_form_modal_add = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('cliente_black_list.adicionar_titulo') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_modal_add,
            success: function (data){
                linhas = [];
                table_titulos.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        data.response.tabela[posicao],
                    ];
                    linhas.push(linha)
                }
                table_titulos.rows.add(linhas).draw();

                form_modal_add.find("#titulos_adicional").val(data.response.titulos_adicional);
                form_modal_add.find("#status").val("Bloqueado");

                form_modal_add.find('#titulo').val('');
            },
            error: function (data){
                var dados = data.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function showModalCliente(url){
        esconderPopoverTooltip();
		var title = "Busca de Clientes";
        $.ajax({
            url: url,
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(body){
				$(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosCliente($(this).parent('tr'), event);
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
        form_modal_add.find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }
    
    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = '';
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_cliente_novo_black_list').css('z-index')) + 1));
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
                form_modal_add.find("#nome_cliente").val(ui.item.label);
                return false;
            }
        };
    }
</script>
@endsection