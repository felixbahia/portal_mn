@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_cad_grupo_empresarial_credito" name="frm_cad_grupo_empresarial_credito" onsubmit="return false;">
    @csrf
    {{ Form::hidden('id', $dados['id'], ['class' => 'form-control']) }}
    
    <div class="form-group">
            {{ Form::label('nome', 'Nome') }}
            {{ Form::text('nome', $dados['nome'], ['class' => 'form-control']) }}
    </div>
    
    <div class="form-group">
        {{ Form::label('nome_cliente', 'Cliente') }}
        <div class="input-group" id="cod_cliente_group">
            {{ Form::text('nome_cliente', $dados['nome_cliente'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
            <span class="input-group-addon border rounded-right bt-view-group" id="bt-search-cliente" data-route="{{ route("cliente.index.dialog") }}">
                <i id="bt-view-cliente" class="bt-view m-2"></i>
            </span>
        </div>
    </div>
    
    <div class="form-group">
        {{ Form::label('raiz_cnpj', 'Raiz CNPJ') }}
        {{ Form::text('raiz_cnpj', $dados['raiz_cnpj'], ['class' => 'form-control raiz_cnpj', 'placeholder' => '00.000.000', 'readonly']) }}
    </div>
    
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('participantes', 'Participantes') }}
        </div>
    </div>

    <div class="participantes">
        @foreach ($participantes as $key => $participante)
        {{-- {{ dd($participante) }} --}}
        <div class="form-row mt-2">
            <div class="input-group col-sm-12">
                {{ Form::text('nome_participante[]', $participante['nome'], ['class' => 'form-control nome_participantes']) }}
                {{ Form::text('participantes[]', $participante['raiz_cnpj'], ['class' => 'form-control raiz_cnpj participantes', 'placeholder' => '00.000.000', 'readonly']) }}
                <span class="input-group-addon border-right border-top border-bottom bt-view-group" data-route="{{ route("cliente.index.dialog") }}">
                    <i class="bt-view bt-view-participante m-2"></i>
                </span>
                @if ($key+1 == sizeof($participantes))
                <span class="input-group-addon border-right border-top border-bottom rounded-right btn-line-add-span">
                    <i class="btn-line-add rounded-right"></i>
                </span>
                @else
                <span class="input-group-addon border-right border-top border-bottom rounded-right btn-line-remove-span">
                    <i class="btn-line-remove rounded-right"></i>
                </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right mt-4', 'onclick'=>'cadastrarForm(event)')) }}
</form>
<script>
  
    function funcoesCampos(){

        $(document).find("#bt-search-cliente").off("click");
        $(document).find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"));
            return false;
        });
        
        $(document).find('.participantes').off('keyup');
        $(document).find('.participantes').on('keyup', function(event) {
            event.stopPropagation();
            if (event.which == 13){
                criarNovoCampo($(this), event);
                return false;
            }
        });
        $(document).find(".btn-line-remove-span").off("click");
        $(document).find(".btn-line-remove-span").on("click", function(){
            var $this = $(this).parent().find('input');
            removeInput($this);
        });
        $(document).find(".btn-line-add-span").off("click");
        $(document).find(".btn-line-add-span").on("click", function(){
            var $this = $(this).parent().find('input');
            createNewInput($this);
        });

        $(document).find(".bt-view-group:not('#bt-search-cliente')").off('click');
        $(document).find(".bt-view-group:not('#bt-search-cliente')").on('click', function(){
            showModalParticipante($(this).data("route"), $(this) );
            return false;
        });

        $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());
        $(document).find(".nome_participantes").autocomplete(optionsAutoCompleteParticipante());
    }

    function createNewInput($this){
        $content = $this.parents('.form-row');
        var input = $content.find('input').last();
        if(input.val() != ''){
            var input_new = $(input).parents('.form-row').clone().appendTo($(input).parents('.participantes'));
            input_new.find('input').val('').focus();
            $(input).parents('.form-row').find('.btn-line-add-span').attr('class', 'btn-line-remove-span');
            $(input).parents('.form-row').find('.btn-line-add').attr('class', 'btn-line-remove');
            funcoesCampos();
        }
    }
    function removeInput($this){
        $content = $this.parent().parent();
        if($content.find('input').length > 1){
            $this.parent().remove();
            funcoesCampos();
        }else{
            $this.val("").focus();
            $this.parent().find(".btn-line-remove-span").attr('class', 'btn-line-remove-span');
            funcoesCampos();
        }
    }

    function criarNovoCampo($this, event){
        if ($($this).val() != ''){
            createNewInput($this);
        }else{
            var campos = $(document).find(".participantes:visible");
            var indice = campos.index(event.target) + 1;
            var seletor = $(campos[indice]).focus();
            if (seletor.length == 0) {
                event.target.focus();
            }
        }
    }

    $(function(){
        funcoesCampos();
    });

    function cadastrarForm(event){
        event.stopPropagation();
        var form = $(document).find('#frm_cad_grupo_empresarial_credito');
        var form_data = form.serialize();
        $.ajax({
            url: '{{ route("grupo_empresarial.editar") }}',
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
    }
    
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
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

    function showModalParticipante(url, elemento){
        var title = "Busca de Participante";

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
                            returnDadosParticipante($(this).parent('tr'), elemento, event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosParticipante($dados, elemento, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }

        $(elemento).parents('.input-group').find(".nome_participantes").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        $(elemento).parents('.input-group').find(".participantes").val(transformCNPJCliente($dados.find("td").eq(3).text()));

    }

    function transformCNPJCliente($cnpj){
        if($cnpj.trim().length === 18){
            $cnpj = $cnpj.substr(0, 10);
        }
        return $cnpj;
    }

    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.retornar_cnpj = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_empresarial_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();

                var raiz_cnpj = transformCNPJCliente(ui.item.cpf_cnpj);
                var ja_digitados = $(document).find(".raiz_cnpj:not(#raiz_cnpj)").map(function (){ return $(this).val()});

                if($.inArray(raiz_cnpj, ja_digitados) == -1){
                    $(document).find("#raiz_cnpj").val(raiz_cnpj);
                    $(document).find("#nome_cliente").val(ui.item.nome);
                }
                else{
                    message('Erro', 'Raiz de CNPJ/CPF já na lista');
                    $(document).find("#raiz_cnpj").val('');
                    $(document).find("#nome_cliente").val('');
                }

                return false;
            }
        };
    }

    function optionsAutoCompleteParticipante(){
        $(document).find(".error-message").remove();

        var elemento;

        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.retornar_cnpj = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_empresarial_edit_delete').css('z-index')) + 1));
                elemento = $(event.target).parents('.form-row');

            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {

                event.stopPropagation();

                var raiz_cnpj = transformCNPJCliente(ui.item.cpf_cnpj);
                var ja_digitados = $(document).find(".raiz_cnpj").map(function (){ return $( this ).val(); }).get();

                if($.inArray(raiz_cnpj, ja_digitados) == -1 || $.inArray(raiz_cnpj, ja_digitados) == $(document).find(".raiz_cnpj").index($(elemento).find('.raiz_cnpj')) ){
                    $(elemento).find(".raiz_cnpj").val(raiz_cnpj);
                    $(elemento).find(".nome_participantes").val(ui.item.nome);
                }
                else{
                    message('Erro', 'Raiz de CNPJ/CPF já na lista');
                    $(elemento).find(".raiz_cnpj").val('');
                    $(elemento).find(".nome_participantes").val('');
                }
                
                return false;
            }
        };
    }
</script>
@endsection