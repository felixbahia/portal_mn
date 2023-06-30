@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_black_list_edit" id="form_black_list_edit" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    {!! Form::hidden('cliente_id', $dados['cliente_id'], ['id' => 'cliente_id']) !!}
    {!! Form::hidden('titulos_adicional', $dados['titulos_adicional'], ['id' => 'titulos_adicional']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12 content-not-estabel">
            {{ Form::label('nome_cliente', 'Cliente') }}
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('nome_cliente', $dados['cliente'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '', 'readonly']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        {{ Form::label('status', 'Status') }}
        {!! Form::text('status', $dados['status'], ['id' => 'status', 'class' => 'form-control', 'readonly']) !!}
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('motivo', 'Motivo') }}
            {{ Form::select('motivo', $dados['motivos'], $dados['motivo'], ['id' => 'motivo', 'class' => 'form-control essencial input-label', 'placeholder' => 'Motivo']) }}
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
                            @foreach ($dados['titulos'] as $titulo)
                                <tr>
                                    <td>{{ $titulo }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('observacao', 'Observação') }}
            {{ Form::text('observacao', $dados['observacao'], ['id' => 'motivo', 'class' => 'form-control essencial input-label', 'placeholder' => 'Observação', 'maxlength' => '40']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        @if($dados['status_cliente_black_lists_id'] === 3)
        {{ Form::button('Liberar', array('class' => 'btn btn-success', 'id' => 'btn-liberar')) }}
        @else 
        {{ Form::button('Bloquear', array('class' => 'btn btn-success', 'id' => 'btn-bloquear')) }}
        @endif
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_edt = $(document).find('#form_black_list_edit');
    
        form_modal_edt.find("#titulo").autocomplete(optionsAutoCompleteTituloPago());

        form_modal_edt.find("#btn-salvar").on('click', function(){
            editarDados(form_modal_edt.serialize());
        });

        form_modal_edt.find('#btn-liberar').off('click');
        form_modal_edt.find('#btn-liberar').on('click', function(){
            liberar(form_modal_edt);
        });

        form_modal_edt.find('#btn-bloquear').off('click');
        form_modal_edt.find('#btn-bloquear').on('click', function(){
            bloquear(form_modal_edt);
        });

        form_modal_edt.find("#bt-search-titulo").off("click");
		form_modal_edt.find("#bt-search-titulo").on("click", function(event){
            event.stopPropagation();
            showModalTituloPagoModalEdt();
            return false;
        });

        form_modal_edt.find("#btn-add_titulo").off('click');
        form_modal_edt.find("#btn-add_titulo").on('click', function(){
            adicionarTituloModalEdt(form_modal_edt);
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
    });

    function editarDados(data_form_modal_edt){
        $.ajax({
            url: "{{ route('cliente_black_list.editar') }}", 
            dataType: 'json',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                $(form_modal_edt).parents('.modal').modal('hide');
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
        var form_modal_edt = $("#form_black_list_edit");
        form_modal_edt.find('.error-message').remove();
        form_modal_edt.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_edt = $("#form_black_list_edit");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_edt, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_edt, input, message){
        var $input = $(form_modal_edt).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    @if($dados['status_cliente_black_lists_id'] === 3)
        function liberar(form_modal_edt){
            data_form_modal_edt = form_modal_edt.serialize()
            $.ajax({
                url: '{{ route('cliente_black_list.liberar') }}',
                type: 'post',
                data: data_form_modal_edt,
                success: function(callback){
                    form_modal_edt.parents(".modal").modal("hide");
                    filterAjax($(document).find("#form_filter").serialize());
                    message("Atenção", "Liberado com sucesso!");
                },
                error: function(callback) {
                    message("Atenção", callback.message);
                }
            });
        }
    @else
        function bloquear(form_modal_edt){
            data_form_modal_edt = form_modal_edt.serialize()
            $.ajax({
                url: '{{ route('cliente_black_list.bloquear') }}',
                type: 'post',
                data: data_form_modal_edt,
                success: function(callback){
                    form_modal_edt.parents(".modal").modal("hide");
                    filterAjax($(document).find("#form_filter").serialize());
                    message("Atenção", "Bloqueado com sucesso!");
                },
                error: function(callback) {
                    message("Atenção", callback.message);
                }
            });
        }
    @endif

    function optionsAutoCompleteTituloPago(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.cliente_nome = form_modal_edt.find("#nome_cliente").val();
                $.post("{{ route('cliente_black_list.autocomplete_titulo_pago') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_cliente_editar_black_list').css('z-index')) + 1));
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
                form_modal_edt.find("#titulo").val(ui.item.label);
                return false;
            }
        };
    }

    function showModalTituloPagoModalEdt(){
        var title = "Buscar Título";
        $.ajax({
            url: '{{ route('cliente_black_list.modal.buscar_titulo_pago') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                cliente_nome: form_modal_edt.find("#nome_cliente").val(),
            },
            success: function(body){
                createModal("titulo_search_show", title, body, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosTituloPagoModalEdt($(this));
                    });
    
                });
            }
        });
    }

    function returnDadosTituloPagoModalEdt($this){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#titulo_search_show").modal("hide");
        $(document).find('#form_black_list_edit').find("#titulo").val($this.find("td").eq(1).text());
    }

    function adicionarTituloModalEdt(form_modal_edt){
        data_form_modal_edt = form_modal_edt.serialize();
        $.ajax({
            url: '{{ route('cliente_black_list.adicionar_titulo') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_modal_edt,
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

                form_modal_edt.find("#titulos_adicional").val(data.response.titulos_adicional);
                form_modal_edt.find("#status").val("Bloqueado");

                form_modal_edt.find('#titulo').val('');
            },
            error: function (data){
                var dados = data.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }
</script>
@endsection