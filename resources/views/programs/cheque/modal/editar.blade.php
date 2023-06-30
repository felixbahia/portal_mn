@extends('layouts.page-dialog')

@section('content')

<form action="#" onsubmit="return false" id='edita_cheque'>

    @csrf
    {{ Form::hidden("id", $id) }}
    
    <div class="row">
        <div class="col-sm-6">
                {{ Form::label("tipo", 'Tipo de lançamento') }}
                {{ Form::select("tipo", $tipo, $tipo_lancamento, ['id' => 'tipo_modal', 'class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            {{ Form::label("cliente_modal", 'Cliente') }}
            <div class="input-group">
                    {{ Form::text("cliente", $cliente, ['id' => 'cliente_modal', 'class' => 'form-control input-label', 'maxlength' => "250"]) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-modal" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 info-banco">
            {{ Form::label("banco_modal", 'Banco') }}
            {{ Form::text("banco", $banco, ['id' => 'banco_modal', 'class' => 'form-control', 'maxlength' => '250']) }}
        </div>
        <div class="col-sm-6 info-banco">
            {{ Form::label("agencia_modal", 'Agência') }}
            {{ Form::text("agencia", $agencia, ['id' => 'agencia_modal', 'class' => 'form-control', 'maxlength' => '250']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 info-banco">
            {{ Form::label("conta_modal", 'Conta') }}
            {{ Form::text("conta", $conta, ['id' => 'conta_modal', 'class' => 'form-control', 'maxlength' => '250']) }}

    
        </div>
        <div class="col-sm-6 info-banco">
            {{ Form::label("numero_cheque_modal", 'Número do lançamento') }}
            {{ Form::text("numero_cheque", $numero_cheque, ['id' => 'numero_cheque_modal', 'class' => 'form-control', 'maxlength' => '250']) }}

        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            {{ Form::label("valor_modal", 'Valor') }}
            {{ Form::text("valor", $valor, ['id' => 'valor_modal', 'class' => 'form-control', 'maxlength' => '250']) }}

    
        </div>
        <div class="col-sm-6 info-bom-para">
            {{ Form::label("bom_para_modal", 'Bom para') }}
            {{ Form::text("bom_para", $bom_para, ['id' => 'bom_para_modal', 'class' => 'form-control', 'maxlength' => '250', 'placeholder' => 'dd/mm/yyyy']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 info-status">

                @if($status_selecionado == 'devolvido')
                <p class='my-3'>Status: <b>Devolvido</b></p>
                @else
                {{ Form::label("status_modal", 'Status') }}
                <select name="status" id="status_modal" class="form-control">
                    @if($status_selecionado != 'baixado') <option value="aberto" @if($status_selecionado == 'aberto') selected @endif>Aberto</option>@endif
                    @if($status_selecionado != 'aberto') <option value="baixado" @if($status_selecionado == 'baixado') selected @endif>Baixado</option>@endif
                    <option value="devolvido">Devolvido</option>
                </select>
                @endif

        </div>
    </div>

    <div class="content-dialog-table @if(empty($titulos))d-none @endif" id='pedidos-baixar'>
        <div class="content-table">
            <table class="table table-striped table-filter-baixas" id="table-filters-baixas">
                <thead>
                    <tr>
                        @if($tipo_lancamento != 'cancelamento')
                        <th></th>
                        @endif
                        <th class="number_format">Nota</th>
                        <th class="number_format">Saldo</th>
                        <th class="date_format">Emissão</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($titulos as $titulo)
                    <tr>
                        @if($tipo_lancamento != 'cancelamento')
                        <td>
                            <input type='checkbox' name='pedidos_prepagos[]' checked value='{{ $titulo['id'] }}'>
                        </td>
                        @endif
                        <td>
                            {{ $titulo['nota'] }}
                        </td>
                        <td>
                            {{ $titulo['saldo'] }}
                        </td>
                        <td>
                            {{ $titulo['created_at'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(!in_array($status_selecionado, ['devolvido']) && $tipo_lancamento != 'cancelamento')
    <div class="row mt-3 float-right">
        <div class="col-sm text-right">
            {{ Form::button('Enviar', ['id'=> 'submit_modal', 'class' => 'btn btn-success']) }}
        </div>
    </div>
    @endif
</form>

<script>

    $(document).ready( function(){
        $(document).find("#bom_para_modal").datepicker(datepicker_modal_options);
        $(document).find("#bom_para_modal").mask("00/00/0000");

        $(document).find('#valor_modal').maskMoney({thousands:'.', decimal:','})

        $(document).find('#submit_modal').off('click');
        $(document).find('#submit_modal').on('click', function(){
            editar();
        });

        $(document).find("#bt-search-cliente-busca-modal").on("click", function(event){
            event.stopPropagation();
            showModalClienteBuscaModal($(this).data("route"));
            return false;
        });

        $(document).find('#cliente_modal').autocomplete(optionsAutoCompleteClienteModal('cliente_modal'))

        $(document).find('#tipo_modal').on('change', function(){

            if($(this).val() != 'dinheiro'){

                $(document).find('.info-banco').removeClass('d-none');

                if($(this).val() == 'cheque'){
                    $(document).find('.info-bom-para').removeClass('d-none');
                    $(document).find('.info-status').removeClass('d-none');
                }
                else{
                    $(document).find('.info-bom-para').addClass('d-none');
                    $(document).find('.info-status').addClass('d-none');
                }
            }
            else{
                $(document).find('.info-banco').addClass('d-none');
                $(document).find('.info-bom-para').addClass('d-none');
                $(document).find('.info-status').addClass('d-none');
            }
        });

        $(document).find('#tipo_modal').trigger('change');

    });

    table_filters_baixas = $('#table-filters-baixas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": true,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
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
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
            {
                "targets": 0,
                'orderable': false
            }
        ],
        "order": [3, 'asc' ]        
    });
    
    function optionsAutoCompleteClienteModal($elemento){
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

        if(input=='cliente'){
            var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
        }
        else if(input=='pedidos_prepagos'){
            var $input = $(document).find('#pedidos-baixar')
        }
        else{
            var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    datepicker_modal_options = {
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        language: 'pt-BR',
        autoHide: true
    };

    @if($status_selecionado != 'devolvido')
    function editar(){

        var form = $(document).find('#edita_cheque');
        var data_form = form.serializeArray();

        form.find('.error-input').removeClass('error-input');
        form.find('.error-message').remove();

        $.ajax({
            url: "{{ route('cheque.editar') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                filterAjax($(document).find("#form_filter").serialize());
                $(document).find('#editar-modal').modal('hide');
            },
            error: function(data){

                hide_loader();

                if((data.responseJSON.errors)){
                    var errors = data.responseJSON.errors;
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
                else{
                    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
                }
            }
        });

    }
    @endif

    function showModalClienteBuscaModal(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show_modal", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show_modal");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosClienteBuscaModal($(this), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteBuscaModal($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_modal").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show_modal").modal("hide");
    }
</script>
@endsection