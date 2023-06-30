@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_cad_cliente_limite_credito" name="frm_cad_cliente_limite_credito" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('data', 'Data da última atualização') }}
        {{ Form::text('data', date('d/m/Y'), ['class' => 'form-control data', 'disabled']) }}
    </div>
    <div class="form-group">
        {{ Form::label('nome_cliente', 'Cliente') }}
        <div class="input-group" id="cod_cliente_group">
            {{ Form::text('nome_cliente', '', ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
            <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
        </div>
    </div>
    <div class="form-group">
        {{ Form::label('raiz_cnpj', 'Cliente raiz CNPJ') }}
        {{ Form::text('raiz_cnpj', '', ['class' => 'form-control raiz_cnpj', 'placeholder' => '00.000.000', 'readonly']) }}
    </div>
    <div class="form-group">
        {{ Form::label('valor', 'Valor') }}
        {{ Form::text('valor', '', ['class' => 'form-control valor', 'placeholder' => '0,00']) }}
    </div>
    <div class="form-group">
        {{ Form::label('ultima_consulta_serasa', 'Última consulta no SERASA') }}
        {{ Form::text('ultima_consulta_serasa', '', ['class' => 'form-control ultima_consulta_serasa', 'placeholder' => '00/00/0000']) }}
    </div>
    <div class="form-group">
        {{ Form::label('motivo_reavaliacao', 'Motivo da reavaliação do crédito') }}
        {{ Form::text('motivo_reavaliacao', '', ['class' => 'form-control motivo_reavaliacao', 'maxlength' => '50']) }}
    </div>
    
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
<script>
    $(function(){
		$(document).find("#bt-search-cliente").off("click");
		$(document).find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
			showModalCliente($(this).data("route"));
            return false;
        });
        
        $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteClienteAdd());

        $(document).find(".valor").maskMoney({thousands:'.', decimal:','});

        datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
			endDate: new Date()
		};

        $(document).find(".ultima_consulta_serasa").datepicker(datepicker_options);
        $(document).find(".ultima_consulta_serasa").mask("00/00/0000");

        $(document).find('#frm_cad_cliente_limite_credito').find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            $.ajax({
                url: '{{ route("cliente.limite.adicionar") }}',
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $(form).parents('.modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
    });

    function optionsAutoCompleteClienteAdd(){
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
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find("#frm_cad_cliente_limite_credito").parents('.modal').css('z-index')) + 10));
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
                $(document).find("#raiz_cnpj").val(transformCNPJCliente(ui.item.cpf_cnpj));
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
        $(document).find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        $(document).find("#raiz_cnpj").val(transformCNPJCliente($dados.find("td").eq(3).text()));

    }
    
    function transformCNPJCliente($cnpj){
        if($cnpj.trim().length === 18){
            $cnpj = $cnpj.substr(0, 10);
        }
        return $cnpj;
    }
    
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
    
</script>
@endsection