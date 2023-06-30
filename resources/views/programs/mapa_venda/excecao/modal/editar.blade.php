@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_mapa_venda_excecao_add" id="form_mapa_venda_excecao_add" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('estabelecimento', 'Estabelecimento', []) }}
            {{ Form::select('estabelecimento', $estabelecimentos, $dados['estabelecimento'], ['id' => 'estabelecimento', 'class' => 'form-control', 'maxlength' => '40']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('numero_nota', 'NF', []) }}
            {{ Form::text('numero_nota', $dados['nota'], ['id' => 'numero_nota', 'class' => 'form-control', 'placeholder' => 'Nota Fiscal', 'maxlength' => '15']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-8"> 
            {{ Form::label('cliente', 'Cliente', []) }}
            {{ Form::text('cliente', $dados['cliente'], ['id' => 'cliente', 'class' => 'form-control', 'placeholder' => 'Cliente', 'disabled' => 'disabled']) }}
        </div>
        <div class="form-group col-sm-4"> 
            {{ Form::label('data_emissao', 'Data Emissão', []) }}
            {{ Form::text('data_emissao', $dados['data_emissao'], ['id' => 'data_emissao', 'class' => 'form-control data', 'placeholder' => 'Data Emissão', 'maxlength' => '20', 'disabled' => 'disabled']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('equipe', 'Equipe', []) }}
            {{ Form::select('equipe', $unidades_negocios, $dados['equipe'], ['id' => 'equipe', 'class' => 'form-control', 'placeholder' => 'Selecione a Equipe']) }}
        </div>
    </div>

    <div class="col-sm-12 mt-5" >
        <div class="form-group">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
        </div>
    </div>
    
</form>

<script>

    $(document).ready( function () {
        form_modal_add = $(document).find('#form_mapa_venda_excecao_add');
        initMaskCamposAdd(form_modal_add);

        $(document).find("#buttom-finalizar-grid").hide();
        $(document).find("#msg-sucesso").hide();

        form_modal_add.find("#estabelecimento").off("change");
        form_modal_add.find("#estabelecimento").on("change", function(){
            if(form_modal_add.find("#estabelecimento").val() == '' || form_modal_add.find("#numero_nota").val() == ''){
                form_modal_add.find("#cliente").val('');
                form_modal_add.find("#data_emissao").val('');
            }else{
                dadosClienteAdd(form_modal_add.serialize());
            }
        });

        form_modal_add.find("#numero_nota").off("blur");
        form_modal_add.find("#numero_nota").on("blur", function(){
            if(form_modal_add.find("#estabelecimento").val() == '' || form_modal_add.find("#numero_nota").val() == ''){
                form_modal_add.find("#cliente").val('');
                form_modal_add.find("#data_emissao").val('');
            }else{
                dadosClienteAdd(form_modal_add.serialize());
            }
        });

        form_modal_add.find("#btn-salvar").off("click");
        form_modal_add.find("#btn-salvar").on("click", function(){
            inserirDados(form_modal_add);
        });

        form_modal_add.find("#buttom-finalizar-grid").on("cliclk", function(){
            form_moda_add.parents('.modal').modal('hide');
        });
    });

    function inserirDados(form_modal_add){
        estabelecimento = form_modal_add.find("#estabelecimento").val();
        numero_nota = form_modal_add.find("#numero_nota").val();
        data_emissao = form_modal_add.find("#data_emissao").val();
        equipe = form_modal_add.find("#equipe").val();
        id = form_modal_add.find("#id").val();

        limparMesagemErroAdd();
        $.ajax({
            url: "{{ route('mapa_venda.excecao.editar') }}", 
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                estabelecimento: estabelecimento,
                numero_nota: numero_nota,
                data_emissao: data_emissao,
                equipe: equipe,
                id: id
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
                filterClear();
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
        var form_modal_add = $("#form_mapa_venda_excecao_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_mapa_venda_excecao_add");
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

    function initMaskCamposAdd(form_modal_add){
        form_modal_add.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        });
        form_modal_add.find('.data').mask('00/00/0000');

    }

    function dadosClienteAdd(data_form_modal_add){
        limparMesagemErroAdd();
        $.ajax({
            url: "{{ route('mapa_venda.excecao.get_cliente') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                form_modal_add.find("#cliente").val(callback.response.cliente);
                form_modal_add.find("#data_emissao").val(callback.response.data_emissao);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
                form_modal_add.find("#cliente").val('');
                form_modal_add.find("#data_emissao").val('');
            }
        });
    }
</script>
@endsection