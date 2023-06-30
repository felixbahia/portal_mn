@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_confirmacao_nota_saida_edt" id="form_confirmacao_nota_saida_edt" onsubmit="return false;">
    @csrf
    
    {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
    {!! Form::hidden('tipo_nota', $dados['tipo_nota'], ['id' => 'tipo_nota']) !!}
    
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::radio('tipo_nota', 'nfe', $dados['tipo_nota'] == null ? true : false, ['id' => 'radio_chave']) }}
            {{ Form::label('label_nota', 'NFe', ['class'=>'input-label']) }}&nbsp;
            {{ Form::radio('tipo_nota', 'nfce', $dados['tipo_nota'] == true ? true : false, ['id' => 'radio_numero']) }}
            {{ Form::label('label_nota', 'NFCe', ['class'=>'input-label']) }}
        </div>
        <div class="form-group col-sm-12"> 
            {{ Form::label('estabelecimento', 'Estabelecimento', []) }}
            {{ Form::select('estabelecimento', $estabelecimentos, $dados['estabelecimento'], ['id' => 'estabelecimento', 'class' => 'form-control', 'maxlength' => '40']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('nota_saida', 'NF', []) }}
            {{ Form::text('nota_saida', $dados['nota_saida'], ['id' => 'nota_saida', 'class' => 'form-control', 'placeholder' => 'Nota Fiscal', 'maxlength' => '15']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-8"> 
            {{ Form::label('cliente', 'Cliente', []) }}
            {{ Form::text('cliente', $dados['cliente'], ['id' => 'cliente', 'class' => 'form-control', 'placeholder' => 'Cliente', 'disabled' => 'disabled']) }}
            {!! Form::hidden('codigo_cliente', $dados['codigo_cliente'], ['id' => 'codigo_cliente']) !!}
        </div>
        <div class="form-group col-sm-4"> 
            {{ Form::label('data_emissao', 'Data Emissão', []) }}
            {{ Form::text('data_emissao', $dados['data_emissao'], ['id' => 'data_emissao', 'class' => 'form-control data', 'placeholder' => 'Data Emissão', 'maxlength' => '20', 'disabled' => 'disabled']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('data_saida', 'Data Saída', []) }}
            {{ Form::text('data_saida', $dados['data_saida'], ['id' => 'data_saida', 'class' => 'form-control data', 'placeholder' => 'Data Saída', 'maxlength' => '20']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('peso', 'Peso total das mercadorias', []) }}
            {{ Form::text('peso', $dados['peso'], ['id' => 'peso', 'class' => 'form-control text-right', 'placeholder' => 'Peso total das mercadorias', 'maxlength' => '10']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>


    $(document).ready( function () {
        form_modal_edt = $(document).find('#form_confirmacao_nota_saida_edt');
        initMaskCamposEdt(form_modal_edt);

        $(document).find("#peso").maskMoney({thousands:'', decimal:',', precision: 3});

        form_modal_edt.find("#estabelecimento").off("change");
        form_modal_edt.find("#estabelecimento").on("change", function(){
            if(form_modal_edt.find("#estabelecimento").val() == '' || form_modal_edt.find("#nota_saida").val() == ''){
                form_modal_edt.find("#cliente").val('');
                form_modal_edt.find("#codigo_cliente").val('');
                form_modal_edt.find("#data_emissao").val('');
            }else{
                dadosClienteEdt(form_modal_edt.serialize());
            }
        });

        form_modal_edt.find("#nota_saida").off("blur");
        form_modal_edt.find("#nota_saida").on("blur", function(){
            if(form_modal_edt.find("#estabelecimento").val() == '' || form_modal_edt.find("#nota_saida").val() == ''){
                form_modal_edt.find("#cliente").val('');
                form_modal_edt.find("#data_emissao").val('');
                form_modal_edt.find("#codigo_cliente").val('');
            }else{
                dadosClienteEdt(form_modal_edt.serialize());
            }
        });

        form_modal_edt.find("#btn-salvar").off("click");
        form_modal_edt.find("#btn-salvar").on("click", function(){
            editarDados(form_modal_edt);
        });
    });

    function editarDados(form_modal_edt){
        estabelecimento = form_modal_edt.find("#estabelecimento").val();
        nota_saida = form_modal_edt.find("#nota_saida").val();
        codigo_cliente = form_modal_edt.find("#codigo_cliente").val();
        data_saida = form_modal_edt.find("#data_saida").val();
        data_emissao = form_modal_edt.find("#data_emissao").val();
        id = form_modal_edt.find("#id").val();
        peso = form_modal_edt.find("#peso").val();
        tipo_nota = form_modal_edt.find("#tipo_nota").val();

        $.ajax({
            url: "{{ route('confirmacao_saida_nota.editar') }}", 
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                estabelecimento: estabelecimento,
                nota_saida: nota_saida,
                codigo_cliente: codigo_cliente,
                data_saida: data_saida,
                data_emissao: data_emissao,
                id: id,
                peso: peso,
                tipo_nota: tipo_nota
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_edt).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
            }
        });
    }

    function limparMesagemErroEdt(){      
        var form_modal_edt = $("#form_confirmacao_nota_saida_edt");
        form_modal_edt.find('.error-message').remove();
        form_modal_edt.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroEdt(json_error){
        var form_modal_edt = $("#form_confirmacao_nota_saida_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_edt, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdt(form_modal_edt, input, message){
        var $input = $(form_modal_edt).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function initMaskCamposEdt(form_modal_edt){
        form_modal_edt.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        });
        form_modal_edt.find('.data').mask('00/00/0000');
    }

    function dadosClienteEdt(data_form_modal_edt){
        limparMesagemErroEdt();
        $.ajax({
            url: "{{ route('confirmacao_saida_nota.get_cliente_data_emissao') }}", 
            dataType: 'json',
            data: data_form_modal_edt,
            method: 'POST',
            async: false,
            success: function(callback){
                form_modal_edt.find("#cliente").val(callback.response.cliente);
                form_modal_edt.find("#data_emissao").val(callback.response.data_emissao);
                form_modal_edt.find("#codigo_cliente").val(callback.response.codigo_cliente);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
                form_modal_edt.find("#cliente").val('');
                form_modal_edt.find("#data_emissao").val('');
                form_modal_edt.find("#codigo_cliente").val('');
            }
        });
    }
</script>
@endsection