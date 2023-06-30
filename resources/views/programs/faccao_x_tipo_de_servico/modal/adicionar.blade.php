@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_faccao_tipo_de_servico_add" id="form_faccao_tipo_de_servico_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('faccao', 'Facção', []) }}
            <div class="input-group">
                {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'Facção', 'maxlength' => '250']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                {!! Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) !!}
            </div>
        </div>
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_de_servico', 'Tipo de Serviço', []) }}
            {!! Form::select("tipo_de_servico", $tipo_de_servicos, '', ["class"=>"form-control"]) !!}
        </div>
        <div class="form-group col-sm-12"> 
            {{ Form::label('preco', 'Preço', []) }}
            {{ Form::text('preco', '', ['id' => 'preco', 'class' => 'form-control', 'placeholder' => 'Preço', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-12"> 
            {{ Form::label('unidade', 'Unidade', []) }}
            <select name="unidade" id="unidade" class="form-control">
                <option value=''>Selecione a Unidade de Medida</option>
                @foreach($unidades as $key => $value)
                <option value='{{ $key }}'>{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_add = $(document).find('#form_faccao_tipo_de_servico_add');
        form_modal_add.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });

        form_modal_add.find("#bt-search-faccao-busca").on("click", function(){
            showModalFaccao($(this).data("route"), "Lista de Facções");
        });
        form_modal_add.find("#preco").maskMoney({thousands:'', decimal:','});
        form_modal_add.find("#faccao").autocomplete(optionsAutoCompleteFaccao());
    });

    function inserirDados(data_form_modal_add){
        $.ajax({
            url: "{{ route('faccao_tipo_de_servico.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
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
        var form_modal_add = $("#form_faccao_tipo_de_servico_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_faccao_tipo_de_servico_add");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        if(input.localeCompare('faccao') == 0){
            var $input = $(form_modal_add).find("#bt-search-faccao-busca");
            $(form_modal_add).find("input[name='faccao']").addClass('error-input');
        }else{
            var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_faccao_tipo_de_servico_adicionar').css('z-index')) + 1));
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
                $(document).find('#form_faccao_tipo_de_servico_add').find("#faccao").val(ui.item.label);
                $(document).find('#form_faccao_tipo_de_servico_add').find("#codigo_faccao").val(ui.item.value);
                return false;
            }
        };
    }

    function showModalFaccao(url, title){
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
        $(document).find('#form_faccao_tipo_de_servico_add').find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
        $(document).find('#form_faccao_tipo_de_servico_add').find("#codigo_faccao").val($this.find("td").eq(0).text());
    }
</script>
@endsection