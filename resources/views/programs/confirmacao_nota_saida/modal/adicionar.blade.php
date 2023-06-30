@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_confirmacao_nota_saida_add" id="form_confirmacao_nota_saida_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::radio('tipo_nota', 'nfe', true, ['id' => 'radio_chave']) }}
            {{ Form::label('label_nota', 'NFe', ['class'=>'input-label']) }}&nbsp;
            {{ Form::radio('tipo_nota', 'nfce', false, ['id' => 'radio_numero']) }}
            {{ Form::label('label_nota', 'NFCe', ['class'=>'input-label']) }}
        </div>
        <div class="form-group col-sm-12"> 
            {{ Form::label('estabelecimentos', 'Estabelecimentos', []) }}
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'maxlength' => '40']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('nota_saidas', 'NF', []) }}
            {{ Form::text('nota_saida', '', ['id' => 'nota_saida', 'class' => 'form-control', 'placeholder' => 'Nota Fiscal', 'maxlength' => '15']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-8"> 
            {{ Form::label('cliente', 'Cliente', []) }}
            {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control', 'placeholder' => 'Cliente', 'disabled' => 'disabled']) }}
            {!! Form::hidden('codigo_cliente', '', ['id' => 'codigo_cliente']) !!}
        </div>
        <div class="form-group col-sm-4"> 
            {{ Form::label('data_emissao', 'Data Emissão', []) }}
            {{ Form::text('data_emissao', '', ['id' => 'data_emissao', 'class' => 'form-control data', 'placeholder' => 'Data Emissão', 'maxlength' => '20', 'disabled' => 'disabled']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('data_saida', 'Data Saída', []) }}
            {{ Form::text('data_saida', date('d/m/Y'), ['id' => 'data_saida', 'class' => 'form-control data', 'placeholder' => 'Data Saída', 'maxlength' => '20']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('peso', 'Peso total das mercadorias', []) }}
            {{ Form::text('peso', '', ['id' => 'peso', 'class' => 'form-control text-right', 'placeholder' => 'Peso total das mercadorias', 'maxlength' => '10']) }}
        </div>
    </div>

    <div class="form-row" id="msg-sucesso">
        <h5><strong><p>Nota grava com sucesso !</p></strong></h5>
    </div>

    <div class="col-sm-12 mt-5">
        <div class="form-group">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
        </div>
    </div>
</form>

<script>

    $(document).ready( function () {
        
        $(document).keypress(function(e) {
            var keycode = (e.keyCode ? e.keyCode : e.which);
            if(keycode == '13'){
                inserirDados(form_modal_add);
            }
        });  
      

        form_modal_add = $(document).find('#form_confirmacao_nota_saida_add');
        initMaskCamposAdd(form_modal_add);

        $(document).find("#msg-sucesso").hide();

        form_modal_add.find("#estabelecimento").off("change");
        form_modal_add.find("#estabelecimento").on("change", function(){
            if(form_modal_add.find("#estabelecimento").val() == '' || form_modal_add.find("#nota_saida").val() == ''){
                form_modal_add.find("#cliente").val('');
                form_modal_add.find("#codigo_cliente").val('');
                form_modal_add.find("#data_emissao").val('');
            }else{
                dadosClienteAdd(form_modal_add.serialize());
            }
        });

        form_modal_add.find("#nota_saida").off("blur");
        form_modal_add.find("#nota_saida").on("blur", function(){
            if(form_modal_add.find("#estabelecimento").val() == '' || form_modal_add.find("#nota_saida").val() == ''){
                form_modal_add.find("#cliente").val('');
                form_modal_add.find("#data_emissao").val('');
                form_modal_add.find("#codigo_cliente").val('');
            }else{
                dadosClienteAdd(form_modal_add.serialize());
            }
        });

        form_modal_add.find("#btn-salvar").off("click");
        form_modal_add.find("#btn-salvar").on("click", function(){
            inserirDados(form_modal_add);
        });

    });

    function inserirDados(form_modal_add){
        estabelecimento = form_modal_add.find("#estabelecimento").val();
        nota_saida = form_modal_add.find("#nota_saida").val();
        codigo_cliente = form_modal_add.find("#codigo_cliente").val();
        data_saida = form_modal_add.find("#data_saida").val();
        data_emissao = form_modal_add.find("#data_emissao").val();
        peso = form_modal_add.find("#peso").val();
        tipo_nota = $("input[name='tipo_nota']:checked").val();
        
        var i = 0;
        limparMesagemErroAdd();
        $.ajax({
            url: "{{ route('confirmacao_saida_nota.adicionar') }}", 
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                estabelecimento: estabelecimento,
                nota_saida: nota_saida,
                codigo_cliente: codigo_cliente,
                data_saida: data_saida,
                data_emissao: data_emissao,
                peso: peso,
                tipo_nota : tipo_nota
            },
            method: 'POST',
            async: false,
            success: function(callback){
                filterClear();
                filterAjax($("#form_filter").serialize());
                form_modal_add.find("#msg-sucesso").show();
                form_modal_add.find("#nota_saida").val("");
                form_modal_add.find("#cliente").val("");
                form_modal_add.find("#codigo_cliente").val("");
                form_modal_add.find("#data_emissao").val("");
                form_modal_add.find("#peso").val("");
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_confirmacao_nota_saida_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_confirmacao_nota_saida_add");
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

        form_modal_add.find("#peso").maskMoney({thousands:'', decimal:',', precision: 3});

    }

    function dadosClienteAdd(data_form_modal_add){
        limparMesagemErroAdd();

        form_modal_add = $(document).find('#form_confirmacao_nota_saida_add');

        var nota_saida = form_modal_add.find("#nota_saida").val();
        var estabelecimento = form_modal_add.find("#estabelecimento option:selected").val();
        var tipo_nota = $("input[name='tipo_nota']:checked").val();

        $.ajax({
            url: "{{ route('confirmacao_saida_nota.get_cliente_data_emissao') }}", 
            data: {
                _token : "{{ csrf_token() }}",
                nota_saida : nota_saida,
                tipo_nota : tipo_nota,
                estabelecimento : estabelecimento
            },
            method: 'POST',
            success: function(callback){
                form_modal_add.find("#cliente").val(callback.response.cliente);
                form_modal_add.find("#data_emissao").val(callback.response.data_emissao);
                form_modal_add.find("#codigo_cliente").val(callback.response.codigo_cliente);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
                form_modal_add.find("#cliente").val('');
                form_modal_add.find("#data_emissao").val('');
                form_modal_add.find("#codigo_cliente").val('');
            }
        });
    }
</script>
@endsection