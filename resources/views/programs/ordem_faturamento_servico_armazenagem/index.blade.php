@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_gerar" id="form_gerar" onsubmit="return false;">
        @csrf
        <h3>{{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-1">
                <input type="text" name="valor" id="valor" placeholder="Preço Unit/KG" value="" maxlength="20" require>
            </div>
            <div class="col-lg-2">
                <input type="text" class="data" name="periodo" id="periodo" placeholder="Período MM/AAAA" maxlength="20" require>
            </div>
        </div>
        <div class="content-buttons">
            <button type="button" name="btn_gerar_rondonia" id="btn_gerar_rondonia" class="btn btn-success">Gerar Rondônia</button>
            <button type="button" name="btn_gerar_tocantins" id="btn_gerar_tocantins" class="btn btn-success">Gerar Tocantins</button>
        </div>
    </form>
@endsection

@section('content')

@endsection

<script>
@section('script-footer')
$(document).ready( function () {
    $(document).find("#form_gerar").find("#btn_gerar_rondonia").on("click", function(){
        var data_form = $("#form_gerar").serialize();
        limparMesagemErro();
        if(validarDados(data_form)){
            gerarExportacao(data_form, '03');
        }
    });

    $(document).find("#form_gerar").find("#btn_gerar_tocantins").on("click", function(){
        var data_form = $("#form_gerar").serialize();
        limparMesagemErro();
        if(validarDados(data_form)){
            gerarExportacao(data_form, '04');
        }
    });

    $(document).find("#valor").maskMoney({thousands:'', decimal:','});

    $('.data').mask('00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'mm/yyyy',
        zIndex: 100,
        autoHide: true,
        endDate : new Date()
    });
});

function validarDados(data_form){
    var retorno = false;
    $.ajax({
        url: "{{ route('ordem_faturamento_servico_armazenagem.validar') }}", 
        dataType: 'json',
        data: data_form,
        method: 'POST',
        async: false,
        success: function(callback){
            retorno = true;
        },
        error: function(callback){
            var dados = callback.responseJSON;
            mensagemErro(dados);
        }
    });
    return retorno;
}

function mensagemErro(json_error){
    var form = $("#form_gerar");

    if(Object.keys(json_error).length > 0){
        for(var field in json_error.error){
            showErrorsInputs(form, field, json_error.error[field]);
        }
    }
}

function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}

function limparMesagemErro(){
    var form = $("#form_gerar");

    form.find('.error-message').remove();
    form.find('input, select').removeClass('error-input');
}


function gerarExportacao(form ,$estabelecimento){   
    $('<form action="{{ route('ordem_faturamento_servico_armazenagem.gerar') }}" method="POST" target="_blank">\
            <input type="hidden" name="_token" value="{{ csrf_token() }}">\
            <input type="hidden" name="valor" value="'+$("#valor").val()+'"/>\
            <input type="hidden" name="periodo" value="'+$("#periodo").val()+'"/>\
            <input type="hidden" name="estabelecimento" value="'+$estabelecimento+'"/>\
            </form>').appendTo('body').submit().remove()   
}
@endsection
</script>